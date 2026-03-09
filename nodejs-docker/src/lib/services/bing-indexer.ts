import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export interface BingIndexResult {
  status: "success" | "failed" | "skipped";
  urlCount: number;
  error?: string;
}

export async function submitToBing(urls: string[]): Promise<BingIndexResult> {
  const config = getConfig();

  if (!config.bing.enabled) {
    logger.info("Bing indexing disabled");
    return { status: "skipped", urlCount: urls.length };
  }

  if (!config.bing.apiKey) {
    logger.warn("Bing Webmaster API key not configured");
    return { status: "skipped", urlCount: urls.length };
  }

  // Respect daily quota
  const urlsToProcess = urls.slice(0, config.bing.dailyQuota);

  try {
    // Bing supports batch submission
    const batchSize = 500;
    for (let i = 0; i < urlsToProcess.length; i += batchSize) {
      const batch = urlsToProcess.slice(i, i + batchSize);

      const siteUrl = new URL(batch[0]).origin;
      const response = await fetch(
        `https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch?apikey=${config.bing.apiKey}`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            siteUrl,
            urlList: batch,
          }),
        }
      );

      if (!response.ok) {
        const text = await response.text();
        throw new Error(`Bing API error: ${response.status} - ${text}`);
      }

      logger.info({ batchSize: batch.length, batchIndex: i / batchSize }, "Bing batch submitted");
    }

    return { status: "success", urlCount: urlsToProcess.length };
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ error }, "Bing submission failed");
    return { status: "failed", urlCount: urlsToProcess.length, error };
  }
}
