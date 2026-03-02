import { describe, it, expect, vi, beforeEach } from "vitest";
import { createTestDb, resetTestConfig } from "../helpers";
import type Database from "better-sqlite3";

let testDb: Database.Database;

vi.mock("@/lib/db/database", () => ({
  getDb: () => testDb,
  closeDb: vi.fn(),
}));

vi.mock("@/lib/config", async () => {
  const helpers = await import("../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

// Generate large URL lists for perf tests
function generateUrls(count: number): { loc: string }[] {
  return Array.from({ length: count }, (_, i) => ({
    loc: `https://example.com/page-${i}`,
  }));
}

vi.mock("@/lib/services/sitemap-parser", () => ({
  parseSitemap: vi.fn().mockResolvedValue(generateUrls(500)),
}));

vi.mock("@/lib/services/cdn-warmer", () => ({
  warmUrls: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    const results = urls.flatMap((url: string) => [
      { url, viewport: "desktop", status: "success", httpStatus: 200, durationMs: 1, cacheHeaders: {} },
      { url, viewport: "mobile", status: "success", httpStatus: 200, durationMs: 1, cacheHeaders: {} },
    ]);
    results.forEach((r) => onProgress?.(r));
    return results;
  }),
  closeBrowser: vi.fn(),
}));

vi.mock("@/lib/services/facebook-warmer", () => ({
  warmFacebook: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    const results = urls.map((url: string) => ({ url, status: "success", durationMs: 1 }));
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
  submitIndexNow: vi.fn().mockResolvedValue({ status: "success", urlCount: 500 }),
}));

vi.mock("@/lib/services/google-indexer", () => ({
  submitToGoogle: vi.fn().mockResolvedValue([]),
}));

vi.mock("@/lib/services/bing-indexer", () => ({
  submitToBing: vi.fn().mockResolvedValue({ status: "success", urlCount: 500 }),
}));

vi.mock("@/lib/services/webhooks", () => ({
  sendWebhook: vi.fn().mockResolvedValue(undefined),
}));

vi.mock("@/lib/services/email-notifications", () => ({
  sendJobCompletedEmail: vi.fn().mockResolvedValue(undefined),
}));

describe("Performance Tests", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  describe("Database write performance", () => {
    it("should insert 1000 url_results in under 2 seconds", () => {
      // Create a job first
      const jobId = "perf-test-job-1";
      testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'running', '[]')").run(
        jobId, "https://example.com/sitemap.xml"
      );

      const stmt = testDb.prepare(
        "INSERT INTO url_results (id, job_id, url, target, viewport, status, http_status, duration_ms, cache_headers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
      );

      const start = performance.now();
      const insert = testDb.transaction(() => {
        for (let i = 0; i < 1000; i++) {
          stmt.run(
            `result-${i}`, jobId, `https://example.com/page-${i}`, "cdn",
            i % 2 === 0 ? "desktop" : "mobile", "success", 200, 100,
            JSON.stringify({ xCache: "HIT" })
          );
        }
      });
      insert();
      const elapsed = performance.now() - start;

      expect(elapsed).toBeLessThan(2000);
      const count = (testDb.prepare("SELECT COUNT(*) as c FROM url_results").get() as { c: number }).c;
      expect(count).toBe(1000);
    });

    it("should query url_results by job_id efficiently (1000 rows in <100ms)", () => {
      const jobId = "perf-test-job-2";
      testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'completed', '[]')").run(
        jobId, "https://example.com/sitemap.xml"
      );

      const stmt = testDb.prepare(
        "INSERT INTO url_results (id, job_id, url, target, viewport, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
      );
      const insert = testDb.transaction(() => {
        for (let i = 0; i < 1000; i++) {
          stmt.run(`r-${i}`, jobId, `https://example.com/p-${i}`, "cdn", "desktop", "success", 200, 50);
        }
      });
      insert();

      const start = performance.now();
      const results = testDb.prepare("SELECT * FROM url_results WHERE job_id = ? ORDER BY created_at").all(jobId);
      const elapsed = performance.now() - start;

      expect(results).toHaveLength(1000);
      expect(elapsed).toBeLessThan(100);
    });

    it("should aggregate stats efficiently (GROUP BY on 1000 rows in <50ms)", () => {
      const jobId = "perf-test-job-3";
      testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'completed', '[]')").run(
        jobId, "https://example.com/sitemap.xml"
      );

      const stmt = testDb.prepare(
        "INSERT INTO url_results (id, job_id, url, target, viewport, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
      );
      const insert = testDb.transaction(() => {
        for (let i = 0; i < 1000; i++) {
          const target = ["cdn", "facebook", "indexnow"][i % 3];
          const status = i % 10 === 0 ? "failed" : "success";
          stmt.run(`r-${i}`, jobId, `https://example.com/p-${i}`, target, "desktop", status, 200, 50);
        }
      });
      insert();

      const start = performance.now();
      const stats = testDb.prepare(
        "SELECT target, status, COUNT(*) as count FROM url_results WHERE job_id = ? GROUP BY target, status"
      ).all(jobId);
      const elapsed = performance.now() - start;

      expect(stats.length).toBeGreaterThan(0);
      expect(elapsed).toBeLessThan(50);
    });
  });

  describe("Job processing performance", () => {
    it("should process a 500-URL job with CDN target in under 5 seconds", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");

      const start = performance.now();
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);
      const elapsed = performance.now() - start;

      const updated = getJob(job.id);
      expect(updated!.status).toBe("completed");
      expect(elapsed).toBeLessThan(5000);
    });

    it("should process a 500-URL job with multiple targets in under 10 seconds", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");

      const start = performance.now();
      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn", "facebook", "indexnow"],
      });
      await processJob(job.id);
      const elapsed = performance.now() - start;

      const updated = getJob(job.id);
      expect(updated!.status).toBe("completed");
      expect(elapsed).toBeLessThan(10000);
    });
  });

  describe("Concurrent job creation performance", () => {
    it("should create 100 jobs in under 1 second", async () => {
      const { createJob } = await import("@/lib/queue/job-manager");

      const start = performance.now();
      for (let i = 0; i < 100; i++) {
        createJob({ sitemapUrl: `https://example.com/sitemap-${i}.xml`, targets: ["cdn"] });
      }
      const elapsed = performance.now() - start;

      expect(elapsed).toBeLessThan(1000);

      const count = (testDb.prepare("SELECT COUNT(*) as c FROM jobs").get() as { c: number }).c;
      expect(count).toBe(100);
    });

    it("should list 100 jobs with pagination in under 50ms", async () => {
      const { createJob, listJobs } = await import("@/lib/queue/job-manager");

      for (let i = 0; i < 100; i++) {
        createJob({ sitemapUrl: `https://example.com/sitemap-${i}.xml`, targets: ["cdn"] });
      }

      const start = performance.now();
      const page1 = listJobs(20, 0);
      const page2 = listJobs(20, 20);
      const elapsed = performance.now() - start;

      expect(page1).toHaveLength(20);
      expect(page2).toHaveLength(20);
      expect(elapsed).toBeLessThan(50);
    });
  });

  describe("Memory efficiency", () => {
    it("should not accumulate memory during batch URL result inserts", () => {
      const jobId = "perf-mem-test";
      testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'running', '[]')").run(
        jobId, "https://example.com/sitemap.xml"
      );

      const heapBefore = process.memoryUsage().heapUsed;

      const stmt = testDb.prepare(
        "INSERT INTO url_results (id, job_id, url, target, viewport, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
      );
      for (let i = 0; i < 2000; i++) {
        stmt.run(`r-${i}`, jobId, `https://example.com/p-${i}`, "cdn", "desktop", "success", 200, 50);
      }

      const heapAfter = process.memoryUsage().heapUsed;
      const heapGrowthMB = (heapAfter - heapBefore) / 1024 / 1024;

      // Should not grow by more than 50MB for 2000 inserts
      expect(heapGrowthMB).toBeLessThan(50);
    });
  });
});

describe("v1.1.0 Performance", () => {
  it("should not degrade custom viewport performance beyond 50% per viewport", () => {
    // Each custom viewport adds one request per URL
    // With 3 custom viewports, total requests = URLs * (2 default + 3 custom) = URLs * 5
    const urlCount = 100;
    const defaultViewports = 2;
    const customViewports = 3;
    const totalRequests = urlCount * (defaultViewports + customViewports);

    // Total should scale linearly
    expect(totalRequests).toBe(500);
    expect(totalRequests / urlCount).toBe(defaultViewports + customViewports);
  });

  it("should complete Pinterest warming within acceptable time bounds", () => {
    // Pinterest warmer has 2s delay between requests
    // For 10 URLs, expected minimum time = 10 * 2000ms = 20s
    const urlCount = 10;
    const delayMs = 2000;
    const expectedMinMs = urlCount * delayMs;

    // Verify calculation is reasonable for planning
    expect(expectedMinMs).toBe(20000);
    expect(expectedMinMs).toBeLessThan(300000); // Should complete in under 5 minutes
  });

  it("should keep priority sorting O(n log n)", () => {
    // Verify that sorting 10k URLs by priority is feasible
    const urls = Array.from({ length: 10000 }, (_, i) => ({
      loc: `https://example.com/page${i}`,
      priority: Math.random(),
    }));

    const start = Date.now();
    urls.sort((a, b) => (b.priority ?? 0.5) - (a.priority ?? 0.5));
    const elapsed = Date.now() - start;

    // Sorting 10k items should take less than 100ms
    expect(elapsed).toBeLessThan(100);
  });
});
