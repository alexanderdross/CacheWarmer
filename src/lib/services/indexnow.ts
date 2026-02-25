import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export interface IndexNowResult {
  status: "success" | "failed" | "skipped";
  urlCount: number;
  error?: string;
}

export async function submitIndexNow(urls: string[]): Promise<IndexNowResult> {
  const config = getConfig();

  if (!config.indexNow.enabled) {
    logger.info("IndexNow disabled");
    return { status: "skipped", urlCount: urls.length };
  }

  if (!config.indexNow.key) {
    logger.warn("IndexNow key not configured");
    return { status: "skipped", urlCount: urls.length };
  }

  try {
    // Extract host from first URL
    const host = new URL(urls[0]).host;

    // IndexNow supports batch of up to 10,000 URLs
    const batches: string[][] = [];
    for (let i = 0; i < urls.length; i += 10000) {
      batches.push(urls.slice(i, i + 10000));
    }

    for (const batch of batches) {
      const body = {
        host,
        key: config.indexNow.key,
        keyLocation: config.indexNow.keyLocation || `https://${host}/${config.indexNow.key}.txt`,
        urlList: batch,
      };

      const response = await fetch("https://api.indexnow.org/indexnow", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });

      if (!response.ok && response.status !== 202) {
        const text = await response.text();
        throw new Error(`IndexNow API error: ${response.status} - ${text}`);
      }

      logger.info({ urlCount: batch.length, status: response.status }, "IndexNow batch submitted");
    }

    return { status: "success", urlCount: urls.length };
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ error }, "IndexNow submission failed");
    return { status: "failed", urlCount: urls.length, error };
  }
}
