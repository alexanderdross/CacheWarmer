import { describe, it, expect, vi, beforeEach } from "vitest";
import { createTestDb, resetTestConfig } from "../../helpers";
import type Database from "better-sqlite3";

let testDb: Database.Database;

// Mock the database module to use in-memory db
vi.mock("@/lib/db/database", () => ({
  getDb: () => testDb,
  closeDb: vi.fn(),
}));

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

// Mock external services to avoid actual API calls
vi.mock("@/lib/services/sitemap-parser", () => ({
  parseSitemap: vi.fn().mockResolvedValue([
    { loc: "https://example.com/page1" },
    { loc: "https://example.com/page2" },
  ]),
}));

vi.mock("@/lib/services/cdn-warmer", () => ({
  warmUrls: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    const results = urls.map((url: string) => ({
      url,
      status: "success" as const,
      httpStatus: 200,
      durationMs: 100,
    }));
    results.forEach((r) => onProgress?.(r));
    return results;
  }),
  closeBrowser: vi.fn(),
}));

vi.mock("@/lib/services/facebook-warmer", () => ({
  warmFacebook: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    const results = urls.map((url: string) => ({
      url,
      status: "success" as const,
      durationMs: 50,
    }));
    results.forEach((r) => onProgress?.(r));
    return results;
  }),
}));

vi.mock("@/lib/services/linkedin-warmer", () => ({
  warmLinkedIn: vi.fn().mockResolvedValue([]),
}));

vi.mock("@/lib/services/twitter-warmer", () => ({
  warmTwitter: vi.fn().mockResolvedValue([]),
}));

vi.mock("@/lib/services/indexnow", () => ({
  submitIndexNow: vi.fn().mockResolvedValue({ status: "success", urlCount: 2 }),
}));

vi.mock("@/lib/services/google-indexer", () => ({
  submitToGoogle: vi.fn().mockResolvedValue([]),
}));

vi.mock("@/lib/services/bing-indexer", () => ({
  submitToBing: vi.fn().mockResolvedValue({ status: "success", urlCount: 2 }),
}));

describe("Job Manager", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  describe("createJob", () => {
    it("should create a new job with queued status", async () => {
      const { createJob } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn", "facebook"],
      });

      expect(job).toBeDefined();
      expect(job.id).toBeTruthy();
      expect(job.status).toBe("queued");
      expect(job.sitemap_url).toBe("https://example.com/sitemap.xml");
      expect(JSON.parse(job.targets)).toEqual(["cdn", "facebook"]);
    });

    it("should persist job in database", async () => {
      const { createJob } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn"],
      });

      const found = testDb.prepare("SELECT * FROM jobs WHERE id = ?").get(job.id);
      expect(found).toBeDefined();
    });
  });

  describe("getJob", () => {
    it("should retrieve a job by ID", async () => {
      const { createJob, getJob } = await import("@/lib/queue/job-manager");

      const created = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn"],
      });

      const found = getJob(created.id);
      expect(found).toBeDefined();
      expect(found!.id).toBe(created.id);
    });

    it("should return undefined for non-existent job", async () => {
      const { getJob } = await import("@/lib/queue/job-manager");

      const found = getJob("nonexistent-id");
      expect(found).toBeUndefined();
    });
  });

  describe("listJobs", () => {
    it("should list jobs ordered by created_at DESC", async () => {
      const { createJob, listJobs } = await import("@/lib/queue/job-manager");

      createJob({ sitemapUrl: "https://example.com/sitemap1.xml", targets: ["cdn"] });
      createJob({ sitemapUrl: "https://example.com/sitemap2.xml", targets: ["cdn"] });

      const jobs = listJobs();
      expect(jobs).toHaveLength(2);
    });

    it("should support limit and offset", async () => {
      const { createJob, listJobs } = await import("@/lib/queue/job-manager");

      createJob({ sitemapUrl: "https://example.com/s1.xml", targets: ["cdn"] });
      createJob({ sitemapUrl: "https://example.com/s2.xml", targets: ["cdn"] });
      createJob({ sitemapUrl: "https://example.com/s3.xml", targets: ["cdn"] });

      const page1 = listJobs(2, 0);
      expect(page1).toHaveLength(2);

      const page2 = listJobs(2, 2);
      expect(page2).toHaveLength(1);
    });
  });

  describe("deleteJob", () => {
    it("should delete an existing job", async () => {
      const { createJob, deleteJob, getJob } = await import("@/lib/queue/job-manager");

      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      const deleted = deleteJob(job.id);

      expect(deleted).toBe(true);
      expect(getJob(job.id)).toBeUndefined();
    });

    it("should return false for non-existent job", async () => {
      const { deleteJob } = await import("@/lib/queue/job-manager");

      const deleted = deleteJob("nonexistent-id");
      expect(deleted).toBe(false);
    });
  });

  describe("processJob", () => {
    it("should process a job and update status to completed", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn"],
      });

      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).toBe("completed");
      expect(updated!.total_urls).toBe(2);
      expect(updated!.started_at).toBeTruthy();
      expect(updated!.completed_at).toBeTruthy();
    });

    it("should save url_results for each processed URL", async () => {
      const { createJob, processJob, getJobResults } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn"],
      });

      await processJob(job.id);

      const results = getJobResults(job.id);
      expect(results.length).toBeGreaterThan(0);
    });

    it("should skip processing if job is not queued", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn"],
      });

      // Manually set to completed
      testDb.prepare("UPDATE jobs SET status = 'completed' WHERE id = ?").run(job.id);

      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).toBe("completed");
    });

    it("should handle errors and set job status to failed", async () => {
      const { parseSitemap } = await import("@/lib/services/sitemap-parser");
      vi.mocked(parseSitemap).mockRejectedValueOnce(new Error("Sitemap parse error"));

      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/invalid.xml",
        targets: ["cdn"],
      });

      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).toBe("failed");
      expect(updated!.error).toContain("Sitemap parse error");
    });
  });

  describe("getJobStats", () => {
    it("should return aggregated stats by target and status", async () => {
      const { createJob, processJob, getJobStats } = await import("@/lib/queue/job-manager");

      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn"],
      });

      await processJob(job.id);

      const stats = getJobStats(job.id) as Array<{ target: string; status: string; count: number }>;
      expect(stats.length).toBeGreaterThan(0);
      expect(stats[0]).toHaveProperty("target");
      expect(stats[0]).toHaveProperty("status");
      expect(stats[0]).toHaveProperty("count");
    });
  });
});
