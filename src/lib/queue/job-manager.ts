import { v4 as uuidv4 } from "uuid";
import { getDb } from "@/lib/db/database";
import { parseSitemap } from "@/lib/services/sitemap-parser";
import { warmUrls, closeBrowser } from "@/lib/services/cdn-warmer";
import { warmFacebook } from "@/lib/services/facebook-warmer";
import { warmLinkedIn } from "@/lib/services/linkedin-warmer";
import { warmTwitter } from "@/lib/services/twitter-warmer";
import { submitIndexNow } from "@/lib/services/indexnow";
import { submitToGoogle } from "@/lib/services/google-indexer";
import { submitToBing } from "@/lib/services/bing-indexer";
import logger from "@/lib/logger";

export type WarmTarget = "cdn" | "facebook" | "linkedin" | "twitter" | "google" | "bing" | "indexnow";

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
  error?: string
) {
  const db = getDb();
  db.prepare(`
    INSERT INTO url_results (id, job_id, url, target, status, http_status, duration_ms, error)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `).run(uuidv4(), jobId, url, target, status, httpStatus ?? null, durationMs ?? null, error ?? null);
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

  try {
    const job = getJob(jobId);
    if (!job || job.status !== "queued") return;

    updateJobStatus(jobId, "running");
    const targets: WarmTarget[] = JSON.parse(job.targets);

    // Parse sitemap
    logger.info({ jobId, sitemapUrl: job.sitemap_url }, "Parsing sitemap");
    const sitemapUrls = await parseSitemap(job.sitemap_url);
    const urls = sitemapUrls.map((u) => u.loc);

    db.prepare("UPDATE jobs SET total_urls = ? WHERE id = ?").run(urls.length, jobId);

    let processed = 0;

    // CDN Warming
    if (targets.includes("cdn")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting CDN warming");
      await warmUrls(urls, (result) => {
        saveUrlResult(jobId, result.url, "cdn", result.status, result.httpStatus, result.durationMs, result.error);
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
    }

    // Twitter
    if (targets.includes("twitter")) {
      logger.info({ jobId, urlCount: urls.length }, "Starting Twitter warming (Tweet Composer)");
      await warmTwitter(urls, (result) => {
        saveUrlResult(jobId, result.url, "twitter", result.status, undefined, result.durationMs, result.error);
        processed++;
        updateJobProgress(jobId, processed);
      });
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

    updateJobStatus(jobId, "completed");
    logger.info({ jobId, processed }, "Job completed");
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ jobId, error }, "Job failed");
    updateJobStatus(jobId, "failed", error);
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
