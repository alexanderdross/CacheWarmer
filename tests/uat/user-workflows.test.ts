import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
import { createTestDb, resetTestConfig } from "../helpers";
import type Database from "better-sqlite3";

/**
 * UAT (User Acceptance Tests)
 *
 * These tests simulate real user workflows end-to-end:
 * 1. User registers a sitemap
 * 2. User starts a warming job
 * 3. User monitors job progress
 * 4. User views logs and results
 * 5. User manages sitemaps (CRUD)
 * 6. User checks system health
 */

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
    { loc: "https://mysite.com/", lastmod: "2026-02-01" },
    { loc: "https://mysite.com/about", lastmod: "2026-01-15" },
    { loc: "https://mysite.com/contact", lastmod: "2026-01-10" },
    { loc: "https://mysite.com/blog/post-1", lastmod: "2026-02-20" },
    { loc: "https://mysite.com/blog/post-2", lastmod: "2026-02-25" },
  ]),
}));

vi.mock("@/lib/services/cdn-warmer", () => ({
  warmUrls: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    return urls.map((url: string) => {
      const result = { url, status: "success" as const, httpStatus: 200, durationMs: Math.floor(Math.random() * 2000) + 500 };
      onProgress?.(result);
      return result;
    });
  }),
  closeBrowser: vi.fn(),
}));

vi.mock("@/lib/services/facebook-warmer", () => ({
  warmFacebook: vi.fn().mockImplementation(async (urls: string[], onProgress?: (r: unknown) => void) => {
    return urls.map((url: string) => {
      const result = { url, status: "success" as const, durationMs: 200 };
      onProgress?.(result);
      return result;
    });
  }),
}));

vi.mock("@/lib/services/linkedin-warmer", () => ({
  warmLinkedIn: vi.fn().mockResolvedValue([]),
}));
vi.mock("@/lib/services/twitter-warmer", () => ({
  warmTwitter: vi.fn().mockResolvedValue([]),
}));
vi.mock("@/lib/services/indexnow", () => ({
  submitIndexNow: vi.fn().mockResolvedValue({ status: "success", urlCount: 5 }),
}));
vi.mock("@/lib/services/google-indexer", () => ({
  submitToGoogle: vi.fn().mockResolvedValue([]),
}));
vi.mock("@/lib/services/bing-indexer", () => ({
  submitToBing: vi.fn().mockResolvedValue({ status: "success", urlCount: 5 }),
}));

const AUTH_HEADER = { Authorization: "Bearer test-api-key-12345" };

describe("UAT: Complete User Workflow", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("Workflow 1: Register sitemap -> Start warming -> Check status -> View results", async () => {
    // Step 1: Register a sitemap
    const { POST: registerSitemap } = await import("@/app/api/sitemaps/route");
    const registerReq = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST",
      headers: { "Content-Type": "application/json", ...AUTH_HEADER },
      body: JSON.stringify({ url: "https://mysite.com/sitemap.xml", cronExpression: "0 3 * * *" }),
    });

    const registerRes = await registerSitemap(registerReq);
    const registeredSitemap = await registerRes.json();
    expect(registerRes.status).toBe(201);
    expect(registeredSitemap.domain).toBe("mysite.com");

    // Step 2: Start a warming job
    const { POST: startWarm } = await import("@/app/api/warm/route");
    const warmReq = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", ...AUTH_HEADER },
      body: JSON.stringify({
        sitemapUrl: "https://mysite.com/sitemap.xml",
        targets: ["cdn", "facebook"],
      }),
    });

    const warmRes = await startWarm(warmReq);
    const warmBody = await warmRes.json();
    expect(warmRes.status).toBe(202);
    expect(warmBody.jobId).toBeTruthy();
    const jobId = warmBody.jobId;

    // Step 3: Wait for job to process (in tests, processJob runs inline)
    // Give async processJob time to complete
    await new Promise((resolve) => setTimeout(resolve, 500));

    // Step 4: Check job status
    const { GET: getJobDetail } = await import("@/app/api/jobs/[id]/route");
    const statusReq = new NextRequest(`http://localhost:3000/api/jobs/${jobId}?results=true`, {
      headers: AUTH_HEADER,
    });

    const statusRes = await getJobDetail(statusReq, { params: Promise.resolve({ id: jobId }) });
    const jobDetail = await statusRes.json();
    expect(statusRes.status).toBe(200);
    expect(["completed", "running"]).toContain(jobDetail.status);

    // Step 5: Check system health
    const { GET: getStatus } = await import("@/app/api/status/route");
    const healthReq = new NextRequest("http://localhost:3000/api/status", {
      headers: AUTH_HEADER,
    });

    const healthRes = await getStatus(healthReq);
    const healthBody = await healthRes.json();
    expect(healthBody.status).toBe("healthy");
    expect(healthBody.totalUrlsProcessed).toBeGreaterThanOrEqual(0);
  });

  it("Workflow 2: List jobs -> View specific job -> Delete job", async () => {
    // Create a job first
    const { POST: startWarm } = await import("@/app/api/warm/route");
    const warmReq = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", ...AUTH_HEADER },
      body: JSON.stringify({
        sitemapUrl: "https://mysite.com/sitemap.xml",
        targets: ["cdn"],
      }),
    });

    const warmRes = await startWarm(warmReq);
    const { jobId } = await warmRes.json();

    // List all jobs
    const { GET: listJobs } = await import("@/app/api/jobs/route");
    const listReq = new NextRequest("http://localhost:3000/api/jobs", {
      headers: AUTH_HEADER,
    });

    const listRes = await listJobs(listReq);
    const listBody = await listRes.json();
    expect(listBody.jobs.length).toBeGreaterThanOrEqual(1);

    // Delete the job
    const { DELETE: deleteJob } = await import("@/app/api/jobs/[id]/route");
    const deleteReq = new NextRequest(`http://localhost:3000/api/jobs/${jobId}`, {
      method: "DELETE",
      headers: AUTH_HEADER,
    });

    const deleteRes = await deleteJob(deleteReq, { params: Promise.resolve({ id: jobId }) });
    expect(deleteRes.status).toBe(200);

    // Verify job is gone
    const { GET: getJob } = await import("@/app/api/jobs/[id]/route");
    const verifyReq = new NextRequest(`http://localhost:3000/api/jobs/${jobId}`, {
      headers: AUTH_HEADER,
    });

    const verifyRes = await getJob(verifyReq, { params: Promise.resolve({ id: jobId }) });
    expect(verifyRes.status).toBe(404);
  });

  it("Workflow 3: Manage sitemaps (register, list, delete)", async () => {
    const { POST: registerSitemap, GET: listSitemaps } = await import("@/app/api/sitemaps/route");
    const { DELETE: deleteSitemap } = await import("@/app/api/sitemaps/[id]/route");

    // Register 2 sitemaps
    for (const domain of ["site1.com", "site2.com"]) {
      const req = new NextRequest("http://localhost:3000/api/sitemaps", {
        method: "POST",
        headers: { "Content-Type": "application/json", ...AUTH_HEADER },
        body: JSON.stringify({ url: `https://${domain}/sitemap.xml` }),
      });
      const res = await registerSitemap(req);
      expect(res.status).toBe(201);
    }

    // List sitemaps
    const listReq = new NextRequest("http://localhost:3000/api/sitemaps", {
      headers: AUTH_HEADER,
    });
    const listRes = await listSitemaps(listReq);
    const listBody = await listRes.json();
    expect(listBody.sitemaps).toHaveLength(2);

    // Delete first sitemap
    const sitemapId = listBody.sitemaps[0].id;
    const deleteReq = new NextRequest(`http://localhost:3000/api/sitemaps/${sitemapId}`, {
      method: "DELETE",
      headers: AUTH_HEADER,
    });
    const deleteRes = await deleteSitemap(deleteReq, { params: Promise.resolve({ id: sitemapId }) });
    expect(deleteRes.status).toBe(200);

    // Verify only 1 remains
    const listReq2 = new NextRequest("http://localhost:3000/api/sitemaps", {
      headers: AUTH_HEADER,
    });
    const listRes2 = await listSitemaps(listReq2);
    const listBody2 = await listRes2.json();
    expect(listBody2.sitemaps).toHaveLength(1);
  });

  it("Workflow 4: View warming logs with filtering", async () => {
    // Setup: Create job and results
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("job-logs-test", "https://mysite.com/sitemap.xml", "completed", '["cdn","facebook"]');

    for (const [target, status] of [
      ["cdn", "success"],
      ["cdn", "failed"],
      ["facebook", "success"],
    ]) {
      testDb.prepare(
        "INSERT INTO url_results (id, job_id, url, target, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?)"
      ).run(
        `result-${Math.random().toString(36).slice(2)}`,
        "job-logs-test",
        "https://mysite.com/page1",
        target,
        status,
        status === "success" ? 200 : 500,
        Math.floor(Math.random() * 1000)
      );
    }

    const { GET: getLogs } = await import("@/app/api/logs/route");

    // View all logs
    const allLogsReq = new NextRequest("http://localhost:3000/api/logs", {
      headers: AUTH_HEADER,
    });
    const allLogsRes = await getLogs(allLogsReq);
    const allLogs = await allLogsRes.json();
    expect(allLogs.logs.length).toBe(3);

    // Filter by job
    const filteredReq = new NextRequest("http://localhost:3000/api/logs?jobId=job-logs-test", {
      headers: AUTH_HEADER,
    });
    const filteredRes = await getLogs(filteredReq);
    const filteredLogs = await filteredRes.json();
    expect(filteredLogs.logs.length).toBe(3);
  });
});
