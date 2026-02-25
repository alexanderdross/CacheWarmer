import puppeteer, { type Browser, type Page } from "puppeteer-core";
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

export interface WarmResult {
  url: string;
  status: "success" | "failed";
  httpStatus?: number;
  durationMs: number;
  error?: string;
}

async function warmSingleUrl(page: Page, url: string, userAgent: string, timeout: number): Promise<WarmResult> {
  const start = Date.now();
  try {
    await page.setUserAgent(userAgent);
    const response = await page.goto(url, {
      waitUntil: "networkidle0",
      timeout,
    });

    const durationMs = Date.now() - start;
    const httpStatus = response?.status() ?? 0;

    logger.info({ url, httpStatus, durationMs }, "CDN warm complete");

    return {
      url,
      status: httpStatus >= 200 && httpStatus < 400 ? "success" : "failed",
      httpStatus,
      durationMs,
    };
  } catch (err) {
    const durationMs = Date.now() - start;
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ url, error, durationMs }, "CDN warm failed");
    return { url, status: "failed", durationMs, error };
  }
}

export async function warmUrls(
  urls: string[],
  onProgress?: (result: WarmResult) => void
): Promise<WarmResult[]> {
  const config = getConfig();
  const { concurrency, timeout, userAgents } = config.cdnWarming;
  const b = await getBrowser();
  const results: WarmResult[] = [];

  // Process in batches
  for (let i = 0; i < urls.length; i += concurrency) {
    const batch = urls.slice(i, i + concurrency);
    const batchResults = await Promise.all(
      batch.map(async (url) => {
        const page = await b.newPage();
        try {
          // Desktop request
          const desktopResult = await warmSingleUrl(page, url, userAgents.desktop, timeout);
          // Mobile request
          await page.setViewport({ width: 375, height: 812 });
          await warmSingleUrl(page, url, userAgents.mobile, timeout);
          return desktopResult;
        } finally {
          await page.close();
        }
      })
    );

    for (const r of batchResults) {
      results.push(r);
      onProgress?.(r);
    }
  }

  return results;
}
