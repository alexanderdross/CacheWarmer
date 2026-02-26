import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
import { createTestDb, resetTestConfig } from "../helpers";
import type Database from "better-sqlite3";

let testDb: Database.Database;
vi.mock("@/lib/db/database", () => ({ getDb: () => testDb, closeDb: vi.fn() }));
vi.mock("@/lib/config", async () => {
  const helpers = await import("../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

describe("GET /api/sitemaps", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should return list of registered sitemaps", async () => {
    testDb.prepare("INSERT INTO sitemaps (id, url, domain) VALUES (?, ?, ?)").run("sm-1", "https://example.com/sitemap.xml", "example.com");
    const { GET } = await import("@/app/api/sitemaps/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request);
    const body = await response.json();
    expect(response.status).toBe(200);
    expect(body.sitemaps).toHaveLength(1);
  });

  it("should return empty array when no sitemaps", async () => {
    const { GET } = await import("@/app/api/sitemaps/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps", { headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await GET(request);
    const body = await response.json();
    expect(body.sitemaps).toHaveLength(0);
  });
});

describe("POST /api/sitemaps", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should register a new sitemap", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST", headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ url: "https://example.com/sitemap.xml" }),
    });
    const response = await POST(request);
    const body = await response.json();
    expect(response.status).toBe(201);
    expect(body.domain).toBe("example.com");
  });

  it("should accept optional cron expression", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST", headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ url: "https://example.com/sitemap.xml", cronExpression: "0 3 * * *" }),
    });
    const response = await POST(request);
    const body = await response.json();
    expect(body.cron_expression).toBe("0 3 * * *");
  });

  it("should return 400 when url is missing", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST", headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({}),
    });
    expect((await POST(request)).status).toBe(400);
  });

  it("should return 400 for invalid URL", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST", headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ url: "not-a-valid-url" }),
    });
    expect((await POST(request)).status).toBe(400);
  });
});

describe("DELETE /api/sitemaps/:id", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should delete an existing sitemap", async () => {
    testDb.prepare("INSERT INTO sitemaps (id, url, domain) VALUES (?, ?, ?)").run("sm-1", "https://example.com/sitemap.xml", "example.com");
    const { DELETE } = await import("@/app/api/sitemaps/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps/sm-1", { method: "DELETE", headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await DELETE(request, { params: Promise.resolve({ id: "sm-1" }) });
    expect(response.status).toBe(200);
  });

  it("should return 404 for non-existent sitemap", async () => {
    const { DELETE } = await import("@/app/api/sitemaps/[id]/route");
    const request = new NextRequest("http://localhost:3000/api/sitemaps/nonexistent", { method: "DELETE", headers: { Authorization: "Bearer test-api-key-12345" } });
    const response = await DELETE(request, { params: Promise.resolve({ id: "nonexistent" }) });
    expect(response.status).toBe(404);
  });
});
