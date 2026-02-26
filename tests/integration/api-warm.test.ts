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
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

vi.mock("@/lib/services/sitemap-parser", () => ({
  parseSitemap: vi.fn().mockResolvedValue([{ loc: "https://example.com/page1" }, { loc: "https://example.com/page2" }]),
}));
vi.mock("@/lib/services/cdn-warmer", () => ({ warmUrls: vi.fn().mockResolvedValue([]), closeBrowser: vi.fn() }));
vi.mock("@/lib/services/facebook-warmer", () => ({ warmFacebook: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/linkedin-warmer", () => ({ warmLinkedIn: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/twitter-warmer", () => ({ warmTwitter: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/indexnow", () => ({ submitIndexNow: vi.fn().mockResolvedValue({ status: "success", urlCount: 0 }) }));
vi.mock("@/lib/services/google-indexer", () => ({ submitToGoogle: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/bing-indexer", () => ({ submitToBing: vi.fn().mockResolvedValue({ status: "success", urlCount: 0 }) }));

describe("POST /api/warm", () => {
  beforeEach(() => { testDb = createTestDb(); resetTestConfig(); });

  it("should create a warming job and return 202", async () => {
    const { POST } = await import("@/app/api/warm/route");
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn", "facebook"] }),
    });
    const response = await POST(request);
    const body = await response.json();
    expect(response.status).toBe(202);
    expect(body.jobId).toBeTruthy();
    expect(body.status).toBe("queued");
    expect(body.targets).toEqual(["cdn", "facebook"]);
  });

  it("should return 400 when sitemapUrl is missing", async () => {
    const { POST } = await import("@/app/api/warm/route");
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({}),
    });
    const response = await POST(request);
    expect(response.status).toBe(400);
  });

  it("should return 400 for invalid URL", async () => {
    const { POST } = await import("@/app/api/warm/route");
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ sitemapUrl: "not-a-url" }),
    });
    const response = await POST(request);
    expect(response.status).toBe(400);
  });

  it("should use all targets when none specified", async () => {
    const { POST } = await import("@/app/api/warm/route");
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ sitemapUrl: "https://example.com/sitemap.xml" }),
    });
    const response = await POST(request);
    const body = await response.json();
    expect(response.status).toBe(202);
    expect(body.targets).toEqual(["cdn", "facebook", "linkedin", "twitter", "google", "bing", "indexnow"]);
  });

  it("should filter out invalid targets", async () => {
    const { POST } = await import("@/app/api/warm/route");
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: "Bearer test-api-key-12345" },
      body: JSON.stringify({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn", "invalid-target", "facebook"] }),
    });
    const response = await POST(request);
    const body = await response.json();
    expect(body.targets).toEqual(["cdn", "facebook"]);
  });
});
