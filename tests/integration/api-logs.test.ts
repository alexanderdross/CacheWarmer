import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
import { createTestDb, resetTestConfig } from "../helpers";
import { v4 as uuidv4 } from "uuid";
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

describe("GET /api/logs", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should return url_results as logs", async () => {
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("job-1", "https://example.com/s.xml", "completed", '["cdn"]');
    testDb.prepare(
      "INSERT INTO url_results (id, job_id, url, target, status, http_status, duration_ms) VALUES (?, ?, ?, ?, ?, ?, ?)"
    ).run(uuidv4(), "job-1", "https://example.com/page1", "cdn", "success", 200, 150);

    const { GET } = await import("@/app/api/logs/route");

    const request = new NextRequest("http://localhost:3000/api/logs", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(response.status).toBe(200);
    expect(body.logs).toHaveLength(1);
    expect(body.logs[0].url).toBe("https://example.com/page1");
  });

  it("should filter by jobId", async () => {
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("job-1", "https://example.com/s.xml", "completed", '["cdn"]');
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("job-2", "https://example.com/s.xml", "completed", '["cdn"]');

    testDb.prepare(
      "INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, ?, ?)"
    ).run(uuidv4(), "job-1", "https://example.com/page1", "cdn", "success");
    testDb.prepare(
      "INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, ?, ?)"
    ).run(uuidv4(), "job-2", "https://example.com/page2", "cdn", "success");

    const { GET } = await import("@/app/api/logs/route");

    const request = new NextRequest("http://localhost:3000/api/logs?jobId=job-1", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(body.logs).toHaveLength(1);
    expect(body.logs[0].job_id).toBe("job-1");
  });

  it("should support pagination", async () => {
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("job-1", "https://example.com/s.xml", "completed", '["cdn"]');

    for (let i = 0; i < 5; i++) {
      testDb.prepare(
        "INSERT INTO url_results (id, job_id, url, target, status) VALUES (?, ?, ?, ?, ?)"
      ).run(uuidv4(), "job-1", `https://example.com/page${i}`, "cdn", "success");
    }

    const { GET } = await import("@/app/api/logs/route");

    const request = new NextRequest("http://localhost:3000/api/logs?limit=2&offset=0", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(body.logs).toHaveLength(2);
    expect(body.limit).toBe(2);
  });

  it("should cap limit at 500", async () => {
    const { GET } = await import("@/app/api/logs/route");

    const request = new NextRequest("http://localhost:3000/api/logs?limit=9999", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(body.limit).toBe(500);
  });
});
