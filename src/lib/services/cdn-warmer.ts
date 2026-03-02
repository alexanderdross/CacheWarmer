import puppeteer, { type Browser, type Page, type HTTPResponse } from "puppeteer-core";
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

export interface CacheHeaders {
  xCache?: string;
  cfCacheStatus?: string;
  age?: string;
  cacheControl?: string;
}

export interface WarmResult {
  url: string;
  viewport: string;
  status: "success" | "failed";
  httpStatus?: number;
  durationMs: number;
  error?: string;
  cacheHeaders?: CacheHeaders;
}

function extractCacheHeaders(response: HTTPResponse | null): CacheHeaders {
  if (!response) return {};
  const headers = response.headers();
  return {
    xCache: headers["x-cache"] || undefined,
    cfCacheStatus: headers["cf-cache-status"] || undefined,
    age: headers["age"] || undefined,
    cacheControl: headers["cache-control"] || undefined,
  };
}

async function warmSingleUrl(
  page: Page,
  url: string,
  userAgent: string,
  viewport: string,
  timeout: number
): Promise<WarmResult> {
  const start = Date.now();
  try {
    await page.setUserAgent(userAgent);
    const response = await page.goto(url, {
      waitUntil: "networkidle0",
      timeout,
    });

    const durationMs = Date.now() - start;
    const httpStatus = response?.status() ?? 0;
    const cacheHeaders = extractCacheHeaders(response);

    logger.info({ url, viewport, httpStatus, durationMs, cacheHeaders }, "CDN warm complete");

    return {
      url,
      viewport,
      status: httpStatus >= 200 && httpStatus < 400 ? "success" : "failed",
      httpStatus,
      durationMs,
      cacheHeaders,
    };
  } catch (err) {
    const durationMs = Date.now() - start;
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ url, viewport, error, durationMs }, "CDN warm failed");
    return { url, viewport, status: "failed", durationMs, error };
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

  // Enterprise: custom user agent override
  const desktopUA = config.cdnWarming.customUserAgent || userAgents.desktop;
  const mobileUA = userAgents.mobile;

  // Enterprise: custom HTTP headers
  const customHeaders = config.cdnWarming.customHeaders || {};

  // Enterprise: custom viewports
  const customViewports = config.cdnWarming.customViewports || [];

  // Enterprise: auth cookies
  const authCookies = config.cdnWarming.authCookies || [];

  // Process in batches
  for (let i = 0; i < urls.length; i += concurrency) {
    const batch = urls.slice(i, i + concurrency);
    const batchResults = await Promise.all(
      batch.map(async (url) => {
        const page = await b.newPage();
        try {
          // Set custom headers if any
          if (Object.keys(customHeaders).length > 0) {
            await page.setExtraHTTPHeaders(customHeaders);
          }

          // Set auth cookies if any
          if (authCookies.length > 0) {
            const urlObj = new URL(url);
            const cookies = authCookies.map((c) => ({
              name: c.name,
              value: c.value,
              domain: c.domain || urlObj.hostname,
            }));
            await page.setCookie(...cookies);
          }

          const urlResults: WarmResult[] = [];

          // Desktop request
          const desktopResult = await warmSingleUrl(page, url, desktopUA, "desktop", timeout);
          urlResults.push(desktopResult);

          // Mobile request
          await page.setViewport({ width: 375, height: 812 });
          const mobileResult = await warmSingleUrl(page, url, mobileUA, "mobile", timeout);
          urlResults.push(mobileResult);

          // Custom viewport requests (Enterprise)
          for (const vp of customViewports) {
            await page.setViewport({ width: vp.width, height: vp.height });
            const vpResult = await warmSingleUrl(page, url, desktopUA, vp.label as "desktop" | "mobile", timeout);
            urlResults.push(vpResult);
          }

          return urlResults;
        } finally {
          await page.close();
        }
      })
    );

    for (const urlResults of batchResults) {
      for (const r of urlResults) {
        results.push(r);
        onProgress?.(r);
      }
    }
  }

  return results;
}
