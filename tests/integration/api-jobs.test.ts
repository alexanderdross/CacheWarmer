import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
import { createTestDb, resetTestConfig } from "../helpers";
import type Database from "better-sqlite3";
import { v4 as uuidv4 } from "uuid";

let testDb: Database.Database;

vi.mock("@/lib/db/database", () => ({ getDb: () => testDb, closeDb: vi.fn() }));
vi.mock("@/lib/config", async () => {
  const helpers = await import("../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

function insertJob(id: string, sitemapUrl: string, status = "queued") {
  testDb.prepare("INSERT INTO jobs (id, sitemap_url, status, targets, total_urls, processed_urls) VALUES (?, ?, ?, ?, ?, ?)").run(id, sitemapUrl, status, '["cdn"]', 10, 5);
}
function insertUrlResult(jobId: string, url: string, target: string, status: string) {
  testDb.prepare("INSERT INTO url_results (id, job_id, url, target, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?)").run(uuidv4(), jobId, url, target, status, 200, 100);
}

describe("GET /api/jobs", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should return list of jobs", async () => {
    insertJob("job-1", "https://example.com/sitemap.xml", "completed");
    insertJob("job-2", "https://example.com/sitemap2.xml", "running");
    const { GET } = await import("@/app/api/jobs/route");
    const request = new NextRequest("http://localhost:3000/api/jobs", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request);
    const body = await response.json();
    expect(response.status).toBe(200);
    expect(body.jobs).toHaveLength(2);
    expect(body.jobs[0].targets).toEqual(["cdn"]);
  });

  it("should support pagination", async () => {
    insertJob("job-1", "https://example.com/s1.xml");
    insertJob("job-2", "https://example.com/s2.xml");
    insertJob("job-3", "https://example.com/s3.xml");
    const { GET } = await import("@/app/api/jobs/route");
    const request = new NextRequest("http://localhost:3000/api/jobs?limit=2&offset=0", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request);
    const body = await response.json();
    expect(body.jobs).toHaveLength(2);
    expect(body.limit).toBe(2);
  });

  it("should cap limit at 100", async () => {
    const { GET } = await import("@/app/api/jobs/route");
    const request = new NextRequest("http://localhost:3000/api/jobs?limit=500", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request);
    const body = await response.json();
    expect(body.limit).toBe(100);
  });
});

describe("GET /api/jobs/:id", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should return job details with stats", async () => {
    insertJob("job-1", "https://example.com/sitemap.xml", "completed");
    insertUrlResult("job-1", "https://example.com/page1", "cdn", "success");
    insertUrlResult("job-1", "https://example.com/page2", "cdn", "failed");
    const { GET } = await import("@/app/api/jobs/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/jobs/job-1", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request, { params: Promise.resolve({ id: "job-1" }) });
    const body = await response.json();
    expect(response.status).toBe(200);
    expect(body.id).toBe("job-1");
    expect(body.stats).toBeDefined();
  });

  it("should include results when requested", async () => {
    insertJob("job-1", "https://example.com/sitemap.xml");
    insertUrlResult("job-1", "https://example.com/page1", "cdn", "success");
    const { GET } = await import("@/app/api/jobs/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/jobs/job-1?results=true", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request, { params: Promise.resolve({ id: "job-1" }) });
    const body = await response.json();
    expect(body.results).toHaveLength(1);
  });

  it("should return 404 for non-existent job", async () => {
    const { GET } = await import("@/app/api/jobs/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/jobs/nonexistent", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request, { params: Promise.resolve({ id: "nonexistent" }) });
    expect(response.status).toBe(404);
  });
});

describe("DELETE /api/jobs/:id", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should delete a job", async () => {
    insertJob("job-1", "https://example.com/sitemap.xml");
    const { DELETE } = await import("@/app/api/jobs/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/jobs/job-1", { method: "DELETE", headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await DELETE(request, { params: Promise.resolve({ id: "job-1" }) });
    expect(response.status).toBe(200);
  });

  it("should return 404 when deleting non-existent job", async () => {
    const { DELETE } = await import("@/app/api/jobs/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/jobs/nonexistent", { method: "DELETE", headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await DELETE(request, { params: Promise.resolve({ id: "nonexistent" }) });
    expect(response.status).toBe(404);
  });
});
