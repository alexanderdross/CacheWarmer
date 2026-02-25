import { google } from "googleapis";
import fs from "fs";
import path from "path";
import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export interface GoogleIndexResult {
  url: string;
  status: "success" | "failed" | "skipped";
  error?: string;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function submitToGoogle(
  urls: string[],
  onProgress?: (result: GoogleIndexResult) => void
): Promise<GoogleIndexResult[]> {
  const config = getConfig();

  if (!config.google.enabled) {
    logger.info("Google indexing disabled");
    return urls.map((url) => ({ url, status: "skipped" as const }));
  }

  const keyFilePath = path.resolve(process.cwd(), config.google.serviceAccountKeyFile);
  if (!fs.existsSync(keyFilePath)) {
    logger.warn({ keyFilePath }, "Google service account key file not found");
    return urls.map((url) => ({ url, status: "skipped" as const }));
  }

  const auth = new google.auth.GoogleAuth({
    keyFile: keyFilePath,
    scopes: ["https://www.googleapis.com/auth/indexing"],
  });

  const client = await auth.getClient();
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const indexing = google.indexing({ version: "v3", auth: client as any });

  // Respect daily quota
  const urlsToProcess = urls.slice(0, config.google.dailyQuota);
  if (urls.length > config.google.dailyQuota) {
    logger.warn(
      { total: urls.length, quota: config.google.dailyQuota },
      "URL count exceeds daily quota, processing only first batch"
    );
  }

  const results: GoogleIndexResult[] = [];

  for (const url of urlsToProcess) {
    try {
      await indexing.urlNotifications.publish({
        requestBody: {
          url,
          type: "URL_UPDATED",
        },
      });

      logger.info({ url }, "Google indexing notification sent");
      const result: GoogleIndexResult = { url, status: "success" };
      results.push(result);
      onProgress?.(result);
    } catch (err) {
      const error = err instanceof Error ? err.message : String(err);
      logger.error({ url, error }, "Google indexing notification failed");
      const result: GoogleIndexResult = { url, status: "failed", error };
      results.push(result);
      onProgress?.(result);
    }

    // Small delay to avoid rate limits
    await delay(100);
  }

  return results;
}
