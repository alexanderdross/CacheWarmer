import puppeteer, { type Browser } from "puppeteer-core";
import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

let browser: Browser | null = null;

async function getBrowser(): Promise<Browser> {
  if (browser && browser.connected) return browser;

  const config = getConfig();
  browser = await puppeteer.launch({
    executablePath: config.puppeteer.executablePath,
    headless: config.puppeteer.headless,
    args: config.puppeteer.args,
  });

  return browser;
}

export async function closeBrowser(): Promise<void> {
  if (browser) {
    await browser.close();
    browser = null;
  }
}

export interface LinkedInWarmResult {
  url: string;
  status: "success" | "failed" | "skipped";
  durationMs: number;
  error?: string;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function warmLinkedIn(
  urls: string[],
  onProgress?: (result: LinkedInWarmResult) => void
): Promise<LinkedInWarmResult[]> {
  const config = getConfig();

  if (!config.linkedin.enabled) {
    logger.info("LinkedIn warming disabled");
    return urls.map((url) => ({ url, status: "skipped" as const, durationMs: 0 }));
  }

  if (!config.linkedin.sessionCookie) {
    logger.warn("LinkedIn session cookie not configured");
    return urls.map((url) => ({ url, status: "skipped" as const, durationMs: 0 }));
  }

  const results: LinkedInWarmResult[] = [];

  try {
    const b = await getBrowser();

    const page = await b.newPage();

    // Set LinkedIn session cookie
    await page.setCookie({
      name: "li_at",
      value: config.linkedin.sessionCookie,
      domain: ".linkedin.com",
      path: "/",
      httpOnly: true,
      secure: true,
    });

    for (const url of urls) {
      const start = Date.now();
      try {
        const inspectorUrl = `https://www.linkedin.com/post-inspector/inspect/${encodeURIComponent(url)}`;

        await page.goto(inspectorUrl, {
          waitUntil: "networkidle0",
          timeout: 30000,
        });

        // Wait for the inspector to process
        await delay(3000);

        const durationMs = Date.now() - start;
        logger.info({ url, durationMs }, "LinkedIn cache warm success");
        const result: LinkedInWarmResult = { url, status: "success", durationMs };
        results.push(result);
        onProgress?.(result);
      } catch (err) {
        const durationMs = Date.now() - start;
        const error = err instanceof Error ? err.message : String(err);
        logger.error({ url, error }, "LinkedIn cache warm failed");
        const result: LinkedInWarmResult = { url, status: "failed", durationMs, error };
        results.push(result);
        onProgress?.(result);
      }

      await delay(config.linkedin.delayBetweenRequests);
    }

    await page.close();
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ error }, "LinkedIn warming browser error");
  }

  return results;
}
