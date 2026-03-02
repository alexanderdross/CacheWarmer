import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export interface PinterestWarmResult {
  url: string;
  status: "success" | "failed" | "skipped";
  httpStatus?: number;
  durationMs: number;
  error?: string;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function warmPinterest(
  urls: string[],
  onProgress?: (result: PinterestWarmResult) => void
): Promise<PinterestWarmResult[]> {
  const config = getConfig();

  if (!config.pinterest?.enabled) {
    logger.info("Pinterest warming disabled");
    return urls.map((url) => ({ url, status: "skipped" as const, durationMs: 0 }));
  }

  const results: PinterestWarmResult[] = [];
  const delayMs = config.pinterest.delay || 2000;

  for (const url of urls) {
    const start = Date.now();

    try {
      const response = await fetch(
        `https://developers.pinterest.com/tools/url-debugger/?link=${encodeURIComponent(url)}`,
        {
          method: "GET",
          headers: {
            "User-Agent": "Mozilla/5.0 (compatible; CacheWarmer/1.0)",
          },
          signal: AbortSignal.timeout(30000),
        }
      );

      const durationMs = Date.now() - start;
      const httpStatus = response.status;
      const status = httpStatus >= 200 && httpStatus < 400 ? "success" : "failed";

      logger.info({ url, httpStatus, durationMs }, "Pinterest warm complete");

      const result: PinterestWarmResult = {
        url,
        status: status as "success" | "failed",
        httpStatus,
        durationMs,
        error: httpStatus >= 400 ? `HTTP ${httpStatus}` : undefined,
      };
      results.push(result);
      onProgress?.(result);
    } catch (err) {
      const durationMs = Date.now() - start;
      const error = err instanceof Error ? err.message : String(err);
      logger.error({ url, error, durationMs }, "Pinterest warm failed");
      const result: PinterestWarmResult = { url, status: "failed", durationMs, error };
      results.push(result);
      onProgress?.(result);
    }

    await delay(delayMs);
  }

  return results;
}
