import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export interface FacebookWarmResult {
  url: string;
  status: "success" | "failed" | "skipped";
  durationMs: number;
  error?: string;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function warmFacebook(
  urls: string[],
  onProgress?: (result: FacebookWarmResult) => void
): Promise<FacebookWarmResult[]> {
  const config = getConfig();

  if (!config.facebook.enabled) {
    logger.info("Facebook warming disabled");
    return urls.map((url) => ({ url, status: "skipped" as const, durationMs: 0 }));
  }

  if (!config.facebook.appId || !config.facebook.appSecret) {
    logger.warn("Facebook App ID/Secret not configured");
    return urls.map((url) => ({ url, status: "skipped" as const, durationMs: 0 }));
  }

  const accessToken = `${config.facebook.appId}|${config.facebook.appSecret}`;
  const delayMs = Math.ceil(1000 / config.facebook.rateLimitPerSecond);
  const results: FacebookWarmResult[] = [];

  for (const url of urls) {
    const start = Date.now();
    const apiUrl = `https://graph.facebook.com/v19.0/?scrape=true&id=${encodeURIComponent(url)}&access_token=${accessToken}`;
    try {
      const response = await fetch(apiUrl, { method: "POST" });

      const durationMs = Date.now() - start;

      if (response.ok) {
        logger.info({ url, durationMs }, "Facebook cache warm success");
        const result: FacebookWarmResult = { url, status: "success", durationMs };
        results.push(result);
        onProgress?.(result);
      } else {
        const body = await response.text();
        logger.error({ url, status: response.status, body }, "Facebook cache warm failed");
        const result: FacebookWarmResult = { url, status: "failed", durationMs, error: `HTTP ${response.status}` };
        results.push(result);
        onProgress?.(result);
      }
    } catch (err) {
      const durationMs = Date.now() - start;
      const rawError = err instanceof Error ? err.message : String(err);
      const error = rawError.replace(/access_token=[^&\s]+/g, "access_token=REDACTED");
      logger.error({ url, error }, "Facebook cache warm error");
      const result: FacebookWarmResult = { url, status: "failed", durationMs, error };
      results.push(result);
      onProgress?.(result);
    }

    await delay(delayMs);
  }

  return results;
}
