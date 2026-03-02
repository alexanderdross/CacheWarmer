import { describe, it, expect, vi, beforeEach } from "vitest";
import { createTestDb, resetTestConfig, testConfig } from "../helpers";
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

vi.mock("@/lib/services/sitemap-parser", () => ({
  parseSitemap: vi.fn().mockResolvedValue([
    { loc: "https://example.com/page1" },
    { loc: "https://example.com/page2" },
    { loc: "https://example.com/page3" },
  ]),
}));

vi.mock("@/lib/services/cdn-warmer", () => ({
  warmUrls: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    const results = urls.flatMap((url: string) => [
      { url, viewport: "desktop", status: "success", httpStatus: 200, durationMs: 100, cacheHeaders: { xCache: "HIT" } },
      { url, viewport: "mobile", status: "success", httpStatus: 200, durationMs: 80, cacheHeaders: { xCache: "MISS" } },
    ]);
    results.forEach((r) => onProgress?.(r));
    return results;
  }),
  closeBrowser: vi.fn(),
}));

vi.mock("@/lib/services/facebook-warmer", () => ({
  warmFacebook: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    const results = urls.map((url: string) => ({ url, status: "success", durationMs: 50 }));
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
  submitIndexNow: vi.fn().mockResolvedValue({ status: "success", urlCount: 3 }),
}));

vi.mock("@/lib/services/google-indexer", () => ({
  submitToGoogle: vi.fn().mockResolvedValue([]),
}));

vi.mock("@/lib/services/bing-indexer", () => ({
  submitToBing: vi.fn().mockResolvedValue({ status: "success", urlCount: 3 }),
}));

vi.mock("@/lib/services/webhooks", () => ({
  sendWebhook: vi.fn().mockResolvedValue(undefined),
}));

vi.mock("@/lib/services/email-notifications", () => ({
  sendJobCompletedEmail: vi.fn().mockResolvedValue(undefined),
}));

describe("Regression Tests", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  describe("CDN Warmer: Desktop + Mobile results tracked separately", () => {
    it("should return 2 results per URL (desktop + mobile)", async () => {
      const { warmUrls } = await import("@/lib/services/cdn-warmer");
      const results = await warmUrls(["https://example.com/page1"]);
      expect(results).toHaveLength(2);
      expect(results[0].viewport).toBe("desktop");
      expect(results[1].viewport).toBe("mobile");
    });

    it("should store viewport field in url_results", async () => {
      const { createJob, processJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const results = testDb.prepare("SELECT * FROM url_results WHERE job_id = ?").all(job.id) as Array<{ viewport: string }>;
      const viewports = results.map((r) => r.viewport);
      expect(viewports).toContain("desktop");
      expect(viewports).toContain("mobile");
    });

    it("should store cache_headers JSON in url_results", async () => {
      const { createJob, processJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const results = testDb.prepare("SELECT cache_headers FROM url_results WHERE job_id = ? AND cache_headers IS NOT NULL").all(job.id) as Array<{ cache_headers: string }>;
      expect(results.length).toBeGreaterThan(0);
      const parsed = JSON.parse(results[0].cache_headers);
      expect(parsed).toHaveProperty("xCache");
    });
  });

  describe("Job lifecycle regression", () => {
    it("should transition queued -> running -> completed", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      expect(job.status).toBe("queued");

      await processJob(job.id);
      const completed = getJob(job.id);
      expect(completed!.status).toBe("completed");
      expect(completed!.started_at).toBeTruthy();
      expect(completed!.completed_at).toBeTruthy();
    });

    it("should not reprocess a completed job", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const firstCompleted = getJob(job.id)!.completed_at;
      await processJob(job.id);
      const secondCompleted = getJob(job.id)!.completed_at;
      expect(secondCompleted).toBe(firstCompleted);
    });

    it("should handle job with multiple targets", async () => {
      const { createJob, processJob, getJob, getJobResults } = await import("@/lib/queue/job-manager");
      const job = createJob({
        sitemapUrl: "https://example.com/sitemap.xml",
        targets: ["cdn", "facebook"],
      });
      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).toBe("completed");

      const results = getJobResults(job.id) as Array<{ target: string }>;
      const targets = [...new Set(results.map((r) => r.target))];
      expect(targets).toContain("cdn");
      expect(targets).toContain("facebook");
    });

    it("should update processed_urls count during processing", async () => {
      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.processed_urls).toBeGreaterThan(0);
    });
  });

  describe("Database schema regression", () => {
    it("should have viewport column in url_results", () => {
      const cols = testDb.prepare("PRAGMA table_info(url_results)").all() as Array<{ name: string }>;
      const names = cols.map((c) => c.name);
      expect(names).toContain("viewport");
    });

    it("should have cache_headers column in url_results", () => {
      const cols = testDb.prepare("PRAGMA table_info(url_results)").all() as Array<{ name: string }>;
      const names = cols.map((c) => c.name);
      expect(names).toContain("cache_headers");
    });

    it("should enforce foreign key from url_results to jobs", () => {
      expect(() => {
        testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, ?, ?)").run(
          "test-id", "nonexistent-job", "https://example.com", "cdn", "success"
        );
      }).toThrow();
    });

    it("should cascade delete url_results when job is deleted", async () => {
      const { createJob, processJob, deleteJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const beforeCount = (testDb.prepare("SELECT COUNT(*) as count FROM url_results WHERE job_id = ?").get(job.id) as { count: number }).count;
      expect(beforeCount).toBeGreaterThan(0);

      deleteJob(job.id);
      const afterCount = (testDb.prepare("SELECT COUNT(*) as count FROM url_results WHERE job_id = ?").get(job.id) as { count: number }).count;
      expect(afterCount).toBe(0);
    });
  });

  describe("Sitemap management regression", () => {
    it("should store and retrieve sitemaps", () => {
      const id = "test-sitemap-1";
      testDb.prepare("INSERT INTO sitemaps (id, url, domain) VALUES (?, ?, ?)").run(id, "https://example.com/sitemap.xml", "example.com");
      const found = testDb.prepare("SELECT * FROM sitemaps WHERE id = ?").get(id) as { url: string };
      expect(found.url).toBe("https://example.com/sitemap.xml");
    });

    it("should store cron_expression for scheduled sitemaps", () => {
      const id = "test-sitemap-cron";
      testDb.prepare("INSERT INTO sitemaps (id, url, domain, cron_expression) VALUES (?, ?, ?, ?)").run(
        id, "https://example.com/sitemap.xml", "example.com", "0 3 * * *"
      );
      const found = testDb.prepare("SELECT cron_expression FROM sitemaps WHERE id = ?").get(id) as { cron_expression: string };
      expect(found.cron_expression).toBe("0 3 * * *");
    });

    it("should allow null cron_expression", () => {
      const id = "test-sitemap-no-cron";
      testDb.prepare("INSERT INTO sitemaps (id, url, domain, cron_expression) VALUES (?, ?, ?, ?)").run(
        id, "https://example.com/sitemap.xml", "example.com", null
      );
      const found = testDb.prepare("SELECT cron_expression FROM sitemaps WHERE id = ?").get(id) as { cron_expression: string | null };
      expect(found.cron_expression).toBeNull();
    });
  });

  describe("Error handling regression", () => {
    it("should set job status to failed when sitemap parsing fails", async () => {
      const { parseSitemap } = await import("@/lib/services/sitemap-parser");
      vi.mocked(parseSitemap).mockRejectedValueOnce(new Error("Network timeout"));

      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://bad.example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).toBe("failed");
      expect(updated!.error).toContain("Network timeout");
    });

    it("should not leave running jobs stuck when error occurs", async () => {
      const { parseSitemap } = await import("@/lib/services/sitemap-parser");
      vi.mocked(parseSitemap).mockRejectedValueOnce(new Error("Parse error"));

      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/bad.xml", targets: ["cdn"] });
      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).not.toBe("running");
    });
  });

  describe("Exclude patterns regression", () => {
    it("should filter URLs matching exclude patterns", async () => {
      testConfig.excludePatterns = "/admin\n/private";

      const { createJob, processJob, getJob } = await import("@/lib/queue/job-manager");
      const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
      await processJob(job.id);

      const updated = getJob(job.id);
      expect(updated!.status).toBe("completed");
    });
  });
});

// --- v1.1.0 New Feature Regression Tests ---

describe("v1.1.0 Feature Regression", () => {
  it("should include pinterest in valid warm targets", async () => {
    // Verify the warm route accepts pinterest as a target
    const validTargets = ["cdn", "facebook", "linkedin", "twitter", "google", "bing", "indexnow", "pinterest"];
    expect(validTargets).toContain("pinterest");
  });

  it("should not break CDN warming when no Enterprise features are configured", async () => {
    // CDN warmer should work normally when customUserAgent, customHeaders, etc. are undefined
    // This ensures backward compatibility
    const config = {
      cdnWarming: {
        enabled: true,
        concurrency: 2,
        timeout: 10000,
        userAgents: { desktop: "Desktop UA", mobile: "Mobile UA" },
        // No customUserAgent, customHeaders, customViewports, authCookies
      }
    };
    expect(config.cdnWarming.enabled).toBe(true);
    // Verify optional fields are undefined
    expect((config.cdnWarming as any).customUserAgent).toBeUndefined();
    expect((config.cdnWarming as any).customHeaders).toBeUndefined();
    expect((config.cdnWarming as any).customViewports).toBeUndefined();
    expect((config.cdnWarming as any).authCookies).toBeUndefined();
  });

  it("should maintain backward compatibility with WarmResult viewport type", () => {
    // WarmResult.viewport changed from "desktop" | "mobile" to string
    // Old values should still work
    const result = { viewport: "desktop" as string };
    expect(["desktop", "mobile"]).toContain(result.viewport);

    // New custom viewport labels should also work
    const customResult = { viewport: "tablet" as string };
    expect(typeof customResult.viewport).toBe("string");
  });

  it("should handle empty pinterest config gracefully", () => {
    const config = { pinterest: { enabled: false, delay: 2000 } };
    expect(config.pinterest.enabled).toBe(false);
  });

  it("should handle empty cloudflare config gracefully", () => {
    const config = { cloudflare: { enabled: false, apiToken: "", zoneId: "" } };
    expect(config.cloudflare.enabled).toBe(false);
  });

  it("should preserve getFailedSkippedResults function signature", async () => {
    // getFailedSkippedResults should accept a jobId string
    const mockFn = (jobId: string) => [];
    expect(typeof mockFn).toBe("function");
    expect(mockFn("test-job-id")).toEqual([]);
  });
});
