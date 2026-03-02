import { describe, it, expect, vi, beforeEach } from "vitest";
import { createTestDb, resetTestConfig } from "../../helpers";
import type Database from "better-sqlite3";
import { v4 as uuidv4 } from "uuid";

let testDb: Database.Database;

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

describe("Export Failed/Skipped Results", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should return only failed and skipped results", async () => {
    const { getFailedSkippedResults } = await import("@/lib/queue/job-manager");

    // Insert a job
    const jobId = uuidv4();
    testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'completed', '[]')").run(jobId, "https://example.com/sitemap.xml");

    // Insert mixed results
    testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, 'cdn', 'success')").run(uuidv4(), jobId, "https://example.com/ok");
    testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status, error) VALUES (?, ?, ?, 'cdn', 'failed', 'HTTP 500')").run(uuidv4(), jobId, "https://example.com/err");
    testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, 'facebook', 'skipped')").run(uuidv4(), jobId, "https://example.com/skip");

    const results = getFailedSkippedResults(jobId) as any[];
    expect(results).toHaveLength(2);
    expect(results.every((r: any) => r.status === "failed" || r.status === "skipped")).toBe(true);
  });

  it("should return empty array when no failures exist", async () => {
    const { getFailedSkippedResults } = await import("@/lib/queue/job-manager");

    const jobId = uuidv4();
    testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'completed', '[]')").run(jobId, "https://example.com/sitemap.xml");
    testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, 'cdn', 'success')").run(uuidv4(), jobId, "https://example.com/ok");

    const results = getFailedSkippedResults(jobId) as any[];
    expect(results).toHaveLength(0);
  });

  it("should only return results for the specified job", async () => {
    const { getFailedSkippedResults } = await import("@/lib/queue/job-manager");

    const jobId1 = uuidv4();
    const jobId2 = uuidv4();
    testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'completed', '[]')").run(jobId1, "https://example.com/s1.xml");
    testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, 'completed', '[]')").run(jobId2, "https://example.com/s2.xml");

    testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, 'cdn', 'failed')").run(uuidv4(), jobId1, "https://example.com/err1");
    testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, 'cdn', 'failed')").run(uuidv4(), jobId2, "https://example.com/err2");

    const results = getFailedSkippedResults(jobId1) as any[];
    expect(results).toHaveLength(1);
    expect(results[0].url).toBe("https://example.com/err1");
  });
});
