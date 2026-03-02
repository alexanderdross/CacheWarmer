import { v4 as uuidv4 } from "uuid";
import { getDb } from "@/lib/db/database";
import { loadConfig } from "@/lib/config";
import { parseSitemap } from "@/lib/services/sitemap-parser";
import { warmUrls, closeBrowser, type CacheHeaders } from "@/lib/services/cdn-warmer";
import { warmFacebook } from "@/lib/services/facebook-warmer";
import { warmLinkedIn, closeBrowser as closeLinkedInBrowser } from "@/lib/services/linkedin-warmer";
import { warmTwitter, closeBrowser as closeTwitterBrowser } from "@/lib/services/twitter-warmer";
import { submitIndexNow } from "@/lib/services/indexnow";
import { submitToGoogle } from "@/lib/services/google-indexer";
import { submitToBing } from "@/lib/services/bing-indexer";
import { warmPinterest } from "@/lib/services/pinterest-warmer";
import { purgeCdnCache } from "@/lib/services/cdn-purge-warm";
import { sendWebhook } from "@/lib/services/webhooks";
import { sendJobCompletedEmail } from "@/lib/services/email-notifications";
import logger from "@/lib/logger";

export type WarmTarget = "cdn" | "facebook" | "linkedin" | "twitter" | "google" | "bing" | "indexnow" | "pinterest" | "cdn-purge";

export interface CreateJobParams {
  sitemapUrl: string;
  targets: WarmTarget[];
}

export interface Job {
  id: string;
  sitemap_id: string | null;
  sitemap_url: string;
  status: string;
  total_urls: number;
  processed_urls: number;
  targets: string;
  started_at: string | null;
  completed_at: string | null;
  error: string | null;
  created_at: string;
}

// Track running jobs so we don't process duplicates
const runningJobs = new Set<string>();

export function createJob(params: CreateJobParams): Job {
  const db = getDb();
  const jobId = uuidv4();

  const stmt = db.prepare(`
    INSERT INTO jobs (id, sitemap_url, status, targets)
    VALUES (?, ?, 'queued', ?)
  `);

  stmt.run(jobId, params.sitemapUrl, JSON.stringify(params.targets));

  return db.prepare("SELECT * FROM jobs WHERE id = ?").get(jobId) as Job;
}

export function getJob(jobId: string): Job | undefined {
  const db = getDb();
  return db.prepare("SELECT * FROM jobs WHERE id = ?").get(jobId) as Job | undefined;
}

export function listJobs(limit = 50, offset = 0): Job[] {
  const db = getDb();
  return db.prepare("SELECT * FROM jobs ORDER BY created_at DESC LIMIT ? OFFSET ?").all(limit, offset) as Job[];
}

export function deleteJob(jobId: string): boolean {
  const db = getDb();
  // Delete associated url_results first to avoid FK constraint violation
  db.prepare("DELETE FROM url_results WHERE job_id = ?").run(jobId);
  const result = db.prepare("DELETE FROM jobs WHERE id = ?").run(jobId);
  return result.changes > 0;
}

function saveUrlResult(
  jobId: string,
  url: string,
  target: string,
  status: string,
  httpStatus?: number,
  durationMs?: number,
  error?: string,
  viewport?: string,
  cacheHeaders?: CacheHeaders
) {
  const db = getDb();
  db.prepare(`
    INSERT INTO url_results (id, job_id, url, target, viewport, status, http_status, duration_ms, error, cache_headers)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    uuidv4(), jobId, url, target, viewport ?? null, status,
    httpStatus ?? null, durationMs ?? null, error ?? null,
    cacheHeaders ? JSON.stringify({ ...cacheHeaders }) : null
  );
}

function updateJobProgress(jobId: string, processedUrls: number) {
  const db = getDb();
  db.prepare("UPDATE jobs SET processed_urls = ? WHERE id = ?").run(processedUrls, jobId);
}

function updateJobStatus(jobId: string, status: string, error?: string) {
  const db = getDb();
  const now = new Date().toISOString();
  if (status === "running") {
    db.prepare("UPDATE jobs SET status = ?, started_at = ? WHERE id = ?").run(status, now, jobId);
  } else {
    db.prepare("UPDATE jobs SET status = ?, completed_at = ?, error = ? WHERE id = ?").run(
      status, now, error ?? null, jobId
    );
  }
}

export async function processJob(jobId: string): Promise<void> {
  if (runningJobs.has(jobId)) return;
  runningJobs.add(jobId);

  const db = getDb();
  const job = getJob(jobId);
  if (!job || job.status !== "queued") {
    runningJobs.delete(jobId);
    return;
  }

  try {

    updateJobStatus(jobId, "running");
    const targets: WarmTarget[] = JSON.parse(job.targets);

    // Parse sitemap
    logger.info({ jobId, sitemapUrl: job.sitemap_url }, "Parsing sitemap");
    const sitemapUrls = await parseSitemap(job.sitemap_url);
    let urls = sitemapUrls.map((u) => u.loc);

    // Apply exclude patterns
    const config = loadConfig();
    const excludeRaw = config.excludePatterns || "";
    if (excludeRaw.trim()) {
      const patterns = excludeRaw.split("\n").map((p) => p.trim()).filter((p) => p.length > 0);
      const beforeCount = urls.length;
      urls = urls.filter((url) => !patterns.some((pattern) => url.includes(pattern)));
      if (urls.length < beforeCount) {
        logger.info({ jobId, excluded: beforeCount - urls.length }, "URLs excluded by patterns");
      }
    }

    // Priority-based warming: sort by priority descending
    sitemapUrls.sort((a, b) => (b.priority ?? 0.5) - (a.priority ?? 0.5));

    db.prepare("UPDATE jobs SET total_urls = ? WHERE id = ?").run(urls.length, jobId);

    sendWebhook("job.started", { jobId, sitemapUrl: job.sitemap_url, urlCount: urls.length, targets }).catch((err) => logger.warn({ err }, "notification failed"));

    let processed = 0;

    // CDN Warming
    if (targets.includes("cdn")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting CDN warming");
      await warmUrls(urls, (result) => {
        saveUrlResult(
          jobId, result.url, "cdn", result.status, result.httpStatus,
          result.durationMs, result.error, result.viewport, result.cacheHeaders
        );
        processed++;
        updateJobProgress(jobId, processed);
      });
      await closeBrowser();
    }

    // Facebook
    if (targets.includes("facebook")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting Facebook warming");
      await warmFacebook(urls, (result) => {
        saveUrlResult(jobId, result.url, "facebook", result.status, undefined, result.durationMs, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
    }

    // LinkedIn
    if (targets.includes("linkedin")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting LinkedIn warming");
      await warmLinkedIn(urls, (result) => {
        saveUrlResult(jobId, result.url, "linkedin", result.status, undefined, result.durationMs, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
      await closeLinkedInBrowser();
    }

    // Twitter
    if (targets.includes("twitter")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting Twitter warming (Tweet Composer)");
      await warmTwitter(urls, (result) => {
        saveUrlResult(jobId, result.url, "twitter", result.status, undefined, result.durationMs, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
      await closeTwitterBrowser();
    }

    // IndexNow
    if (targets.includes("indexnow")) {
      logger.info({ jobId, urlCount: urls.length }, "Submitting to IndexNow");
      const result = await submitIndexNow(urls);
      for (const url of urls) {
        saveUrlResult(jobId, url, "indexnow", result.status, undefined, undefined, result.error);
      }
      processed += urls.length;
      updateJobProgress(jobId, processed);
    }

    // Google
    if (targets.includes("google")) {
      logger.info({ jobId, urlCount: urls.length }, "Submitting to Google Indexing API");
      await submitToGoogle(urls, (result) => {
        saveUrlResult(jobId, result.url, "google", result.status, undefined, undefined, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
    }

    // Bing
    if (targets.includes("bing")) {
      logger.info({ jobId, urlCount: urls.length }, "Submitting to Bing Webmaster Tools");
      const result = await submitToBing(urls);
      for (const url of urls) {
        saveUrlResult(jobId, url, "bing", result.status, undefined, undefined, result.error);
      }
      processed += urls.length;
      updateJobProgress(jobId, processed);
    }

    // Pinterest
    if (targets.includes("pinterest")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting Pinterest warming");
      await warmPinterest(urls, (result) => {
        saveUrlResult(jobId, result.url, "pinterest", result.status, result.httpStatus, result.durationMs, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
    }

    // CDN Purge + Warm (Cloudflare, Imperva, Akamai)
    if (targets.includes("cdn-purge")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting CDN cache purge (Cloudflare/Imperva/Akamai)");
      await purgeCdnCache(urls, (result) => {
        saveUrlResult(jobId, result.url, `cdn-purge:${result.provider}`, result.status, result.httpStatus, result.durationMs, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
    }

    updateJobStatus(jobId, "completed");
    logger.info({ jobId, processed }, "Job completed");

    // Send notifications
    const notifData = { jobId, status: "completed", sitemapUrl: job.sitemap_url, totalUrls: urls.length, processedUrls: processed };
    sendWebhook("job.completed", notifData).catch((err) => logger.warn({ err }, "notification failed"));
    sendJobCompletedEmail(notifData).catch((err) => logger.warn({ err }, "notification failed"));
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ jobId, error }, "Job failed");
    updateJobStatus(jobId, "failed", error);

    // Send failure notifications
    sendWebhook("job.failed", { jobId, error }).catch((err) => logger.warn({ err }, "notification failed"));
    sendJobCompletedEmail({ jobId, status: "failed", sitemapUrl: job.sitemap_url, totalUrls: 0, processedUrls: 0 }).catch((err) => logger.warn({ err }, "notification failed"));
  } finally {
    runningJobs.delete(jobId);
  }
}

export function getJobResults(jobId: string) {
  const db = getDb();
  return db.prepare("SELECT * FROM url_results WHERE job_id = ? ORDER BY created_at").all(jobId);
}

export function getJobStats(jobId: string) {
  const db = getDb();
  return db.prepare(`
    SELECT target, status, COUNT(*) as count
    FROM url_results WHERE job_id = ?
    GROUP BY target, status
  `).all(jobId);
}

export function getFailedSkippedResults(jobId: string) {
  const db = getDb();
  return db.prepare(
    "SELECT * FROM url_results WHERE job_id = ? AND status IN ('failed', 'skipped') ORDER BY created_at"
  ).all(jobId);
}
