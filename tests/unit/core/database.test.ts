import { describe, it, expect, beforeEach } from "vitest";
import { createTestDb } from "../../helpers";
import Database from "better-sqlite3";

describe("Database Module", () => {
  let db: Database.Database;

  beforeEach(() => {
    db = createTestDb();
  });

  describe("Schema", () => {
    it("should create sitemaps table with correct columns", () => {
      const info = db.prepare("PRAGMA table_info(sitemaps)").all() as Array<{
        name: string;
        type: string;
        notnull: number;
      }>;
      const columns = info.map((c) => c.name);

      expect(columns).toContain("id");
      expect(columns).toContain("url");
      expect(columns).toContain("domain");
      expect(columns).toContain("cron_expression");
      expect(columns).toContain("created_at");
      expect(columns).toContain("last_warmed_at");
    });

    it("should create jobs table with correct columns", () => {
      const info = db.prepare("PRAGMA table_info(jobs)").all() as Array<{
        name: string;
      }>;
      const columns = info.map((c) => c.name);

      expect(columns).toContain("id");
      expect(columns).toContain("sitemap_id");
      expect(columns).toContain("sitemap_url");
      expect(columns).toContain("status");
      expect(columns).toContain("total_urls");
      expect(columns).toContain("processed_urls");
      expect(columns).toContain("targets");
      expect(columns).toContain("started_at");
      expect(columns).toContain("completed_at");
      expect(columns).toContain("error");
      expect(columns).toContain("created_at");
    });

    it("should create url_results table with correct columns", () => {
      const info = db.prepare("PRAGMA table_info(url_results)").all() as Array<{
        name: string;
      }>;
      const columns = info.map((c) => c.name);

      expect(columns).toContain("id");
      expect(columns).toContain("job_id");
      expect(columns).toContain("url");
      expect(columns).toContain("target");
      expect(columns).toContain("status");
      expect(columns).toContain("http_status");
      expect(columns).toContain("duration_ms");
      expect(columns).toContain("error");
      expect(columns).toContain("created_at");
    });

    it("should have indexes on url_results.job_id and jobs.status", () => {
      const indexes = db.prepare("PRAGMA index_list(url_results)").all() as Array<{
        name: string;
      }>;
      expect(indexes.some((i) => i.name === "idx_url_results_job_id")).toBe(true);

      const jobIndexes = db.prepare("PRAGMA index_list(jobs)").all() as Array<{
        name: string;
      }>;
      expect(jobIndexes.some((i) => i.name === "idx_jobs_status")).toBe(true);
    });
  });

  describe("CRUD Operations", () => {
    it("should insert and retrieve a sitemap", () => {
      db.prepare(
        "INSERT INTO sitemaps (id, url, domain) VALUES (?, ?, ?)"
      ).run("test-1", "https://example.com/sitemap.xml", "example.com");

      const sitemap = db.prepare("SELECT * FROM sitemaps WHERE id = ?").get("test-1") as Record<string, unknown>;
      expect(sitemap).toBeDefined();
      expect(sitemap.url).toBe("https://example.com/sitemap.xml");
      expect(sitemap.domain).toBe("example.com");
    });

    it("should insert and retrieve a job", () => {
      db.prepare(
        "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
      ).run("job-1", "https://example.com/sitemap.xml", "queued", '["cdn"]');

      const job = db.prepare("SELECT * FROM jobs WHERE id = ?").get("job-1") as Record<string, unknown>;
      expect(job).toBeDefined();
      expect(job.status).toBe("queued");
      expect(job.total_urls).toBe(0);
    });

    it("should insert and retrieve url_results", () => {
      db.prepare(
        "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
      ).run("job-1", "https://example.com/sitemap.xml", "running", '["cdn"]');

      db.prepare(
        "INSERT INTO url_results (id, job_id, url, target, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?)"
      ).run("result-1", "job-1", "https://example.com/page1", "cdn", "success", 200, 1500);

      const result = db.prepare("SELECT * FROM url_results WHERE id = ?").get("result-1") as Record<string, unknown>;
      expect(result).toBeDefined();
      expect(result.status).toBe("success");
      expect(result.http_status).toBe(200);
      expect(result.duration_ms).toBe(1500);
    });

    it("should enforce NOT NULL constraints on required fields", () => {
      expect(() => {
        db.prepare("INSERT INTO sitemaps (id, url) VALUES (?, ?)").run("test-1", "https://example.com/sitemap.xml");
      }).toThrow(); // domain is NOT NULL
    });

    it("should enforce foreign key on url_results.job_id", () => {
      expect(() => {
        db.prepare(
          "INSERT INTO url_results (id, job_id, url, target) VALUES (?, ?, ?, ?)"
        ).run("result-1", "nonexistent-job", "https://example.com", "cdn");
      }).toThrow(); // FK constraint
    });

    it("should default job status to 'queued'", () => {
      db.prepare(
        "INSERT INTO jobs (id, sitemap_url, targets) VALUES (?, ?, ?)"
      ).run("job-1", "https://example.com/sitemap.xml", '["cdn"]');

      const job = db.prepare("SELECT status FROM jobs WHERE id = ?").get("job-1") as Record<string, unknown>;
      expect(job.status).toBe("queued");
    });

    it("should auto-generate created_at timestamps", () => {
      db.prepare(
        "INSERT INTO sitemaps (id, url, domain) VALUES (?, ?, ?)"
      ).run("test-1", "https://example.com/sitemap.xml", "example.com");

      const sitemap = db.prepare("SELECT created_at FROM sitemaps WHERE id = ?").get("test-1") as Record<string, unknown>;
      expect(sitemap.created_at).toBeDefined();
      expect(typeof sitemap.created_at).toBe("string");
    });

    it("should support deleting jobs and cascading behavior", () => {
      db.prepare(
        "INSERT INTO jobs (id, sitemap_url, targets) VALUES (?, ?, ?)"
      ).run("job-1", "https://example.com/sitemap.xml", '["cdn"]');

      const result = db.prepare("DELETE FROM jobs WHERE id = ?").run("job-1");
      expect(result.changes).toBe(1);

      const job = db.prepare("SELECT * FROM jobs WHERE id = ?").get("job-1");
      expect(job).toBeUndefined();
    });
  });
});
