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

export interface TwitterWarmResult {
  url: string;
  status: "success" | "failed" | "skipped";
  durationMs: number;
  error?: string;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function warmTwitter(
  urls: string[],
  onProgress?: (result: TwitterWarmResult) => void
): Promise<TwitterWarmResult[]> {
  const config = getConfig();

  if (!config.twitter.enabled) {
    logger.info("Twitter warming disabled");
    return urls.map((url) => ({ url, status: "skipped" as const, durationMs: 0 }));
  }

  const results: TwitterWarmResult[] = [];

  try {
    const b = await getBrowser();

    const { concurrency, delayBetweenRequests, timeout } = config.twitter;

    // Process in batches
    for (let i = 0; i < urls.length; i += concurrency) {
      const batch = urls.slice(i, i + concurrency);

      const batchResults = await Promise.all(
        batch.map(async (url) => {
          const page = await b.newPage();
          const start = Date.now();
          try {
            // Open Tweet Composer — this triggers Twitter's card scraper
            const composerUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}`;

            await page.goto(composerUrl, {
              waitUntil: "networkidle0",
              timeout,
            });

            const durationMs = Date.now() - start;
            logger.info({ url, durationMs }, "Twitter card warm success (composer)");
            return { url, status: "success" as const, durationMs };
          } catch (err) {
            const durationMs = Date.now() - start;
            const error = err instanceof Error ? err.message : String(err);
            logger.error({ url, error }, "Twitter card warm failed");
            return { url, status: "failed" as const, durationMs, error };
          } finally {
            await page.close();
          }
        })
      );

      for (const r of batchResults) {
        results.push(r);
        onProgress?.(r);
      }

      if (i + concurrency < urls.length) {
        await delay(delayBetweenRequests);
      }
    }
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ error }, "Twitter warming browser error");
  }

  return results;
}
