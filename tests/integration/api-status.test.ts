import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
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

describe("GET /api/status", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should return healthy status", async () => {
    const { GET } = await import("@/app/api/status/route");

    const request = new NextRequest("http://localhost:3000/api/status", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(response.status).toBe(200);
    expect(body.status).toBe("healthy");
    expect(typeof body.uptime).toBe("number");
    expect(body.totalUrlsProcessed).toBeDefined();
  });

  it("should return job counts by status", async () => {
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("j1", "https://example.com/s.xml", "completed", '["cdn"]');
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("j2", "https://example.com/s.xml", "running", '["cdn"]');
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("j3", "https://example.com/s.xml", "completed", '["cdn"]');

    const { GET } = await import("@/app/api/status/route");

    const request = new NextRequest("http://localhost:3000/api/status", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(body.jobs.completed).toBe(2);
    expect(body.jobs.running).toBe(1);
  });

  it("should return recent jobs", async () => {
    testDb.prepare(
      "INSERT INTO jobs (id, sitemap_url, status, targets) VALUES (?, ?, ?, ?)"
    ).run("j1", "https://example.com/s.xml", "completed", '["cdn"]');

    const { GET } = await import("@/app/api/status/route");

    const request = new NextRequest("http://localhost:3000/api/status", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    expect(body.recentJobs).toHaveLength(1);
  });
});
