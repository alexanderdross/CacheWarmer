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
  vary?: string;
  /** Set on the second (probe) pass — see classifyCacheVerdict. */
  verdict?: CacheVerdict;
  verdictReason?: string;
}

/**
 * Outcome of a warm, judged by comparing the two passes we already make.
 *
 * Note that MISS on the first pass is the SUCCESS signal for a warmer: it
 * means the fill did the work. HIT on the first pass means the cache was
 * already warm and the job changed nothing.
 */
export type CacheVerdict =
  | "warmed"
  | "already_warm"
  | "not_cacheable"
  | "bypassed"
  | "zone_not_caching"
  | "indeterminate"
  | "unknown";

function cacheState(headers: CacheHeaders): "hit" | "miss" | "bypass" | "dynamic" | "unknown" {
  // Fastly reports both tiers comma-separated ("MISS, HIT"); only the last
  // segment describes the edge that answered us. Matching the whole string
  // would read "HIT, MISS" as a hit.
  const source = headers.cfCacheStatus || headers.xCache || "";
  const raw = (source.split(",").pop() ?? "").trim().toUpperCase();
  if (!raw) return Number(headers.age) > 0 ? "hit" : "unknown";
  if (raw.includes("BYPASS")) return "bypass";
  if (raw.includes("DYNAMIC")) return "dynamic";
  // REVALIDATED, STALE and UPDATING are all served from cache; treating them
  // as unknown would report genuinely cached pages as unverified.
  if (raw.includes("HIT") || raw.includes("REVALIDATED") || raw.includes("STALE") || raw.includes("UPDATING")) return "hit";
  if (raw.includes("MISS") || raw.includes("EXPIRED")) return "miss";
  return "unknown";
}

/**
 * Classify the desktop pass as the fill and the mobile pass as the probe.
 *
 * These two requests are already made for every URL, so verification costs
 * nothing extra. The catch is Vary: if the origin varies on User-Agent then
 * the two passes address different cache objects and the pair proves nothing.
 */
export function classifyCacheVerdict(
  fill: CacheHeaders,
  probe: CacheHeaders,
  probeVary?: string
): { verdict: CacheVerdict; reason?: string } {
  if (probeVary && /user-agent/i.test(probeVary)) {
    return {
      verdict: "indeterminate",
      reason: "Origin sends Vary: User-Agent, so the two passes are separate cache entries",
    };
  }

  const fillState = cacheState(fill);
  const probeState = cacheState(probe);

  if (fillState === "bypass" || probeState === "bypass") {
    return { verdict: "bypassed", reason: probe.cacheControl || "Cache bypassed" };
  }
  if (probeState === "dynamic") {
    return { verdict: "zone_not_caching", reason: "CDN reports the response as DYNAMIC" };
  }
  if (probeState === "hit") {
    return fillState === "hit" ? { verdict: "already_warm" } : { verdict: "warmed" };
  }
  if (probeState === "miss") {
    const cc = (probe.cacheControl || "").toLowerCase();
    if (cc.includes("no-store")) return { verdict: "not_cacheable", reason: "Cache-Control: no-store" };
    if (cc.includes("private")) return { verdict: "not_cacheable", reason: "Cache-Control: private" };
    return { verdict: "not_cacheable", reason: "Still a miss after the fill request" };
  }
  return { verdict: "unknown" };
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
    vary: headers["vary"] || undefined,
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

/**
 * Warm a list of URLs.
 *
 * `onHtml` receives the document from the desktop pass. The browser has the
 * HTML in hand already, so passing it out lets schema validation run without
 * fetching every page a second time. It is awaited, so nothing accumulates in
 * memory across a large sitemap.
 */
export async function warmUrls(
  urls: string[],
  onProgress?: (result: WarmResult) => void,
  onHtml?: (url: string, html: string) => Promise<void> | void
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

          // Hand the loaded document to whoever asked for it, before the page
          // navigates away on the mobile pass.
          if (onHtml && desktopResult.status === "success") {
            try {
              await onHtml(url, await page.content());
            } catch (err) {
              logger.warn(
                { url, error: err instanceof Error ? err.message : String(err) },
                "HTML consumer failed"
              );
            }
          }

          // Mobile request. This doubles as the verification probe: the
          // desktop pass filled the cache, so this one should come back a HIT.
          await page.setViewport({ width: 375, height: 812 });
          const mobileResult = await warmSingleUrl(page, url, mobileUA, "mobile", timeout);

          if (desktopResult.cacheHeaders && mobileResult.cacheHeaders) {
            const { verdict, reason } = classifyCacheVerdict(
              desktopResult.cacheHeaders,
              mobileResult.cacheHeaders,
              mobileResult.cacheHeaders.vary
            );
            mobileResult.cacheHeaders.verdict = verdict;
            mobileResult.cacheHeaders.verdictReason = reason;
            logger.info({ url, verdict, reason }, "Cache warm verdict");
          }

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
