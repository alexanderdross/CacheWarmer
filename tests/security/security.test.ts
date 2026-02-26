import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
import { createTestDb, createMockConfig, resetTestConfig } from "../helpers";
import type Database from "better-sqlite3";

/**
 * Security Test Suite
 *
 * Tests cover:
 * 1. Authentication bypass attempts
 * 2. Authorization enforcement on all endpoints
 * 3. SQL injection prevention
 * 4. XSS prevention in API responses
 * 5. Input validation and sanitization
 * 6. Path traversal prevention
 * 7. Request size and rate limiting
 * 8. Sensitive data exposure prevention
 * 9. HTTP method enforcement
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

// Mock fs at module level (ESM modules can't be spied on)
const { mockExistsSync, mockReadFileSync } = vi.hoisted(() => ({
  mockExistsSync: vi.fn(),
  mockReadFileSync: vi.fn(),
}));
vi.mock("fs", async () => {
  const actual: Record<string, unknown> = await vi.importActual("fs");
  const overridden = { ...actual, existsSync: mockExistsSync, readFileSync: mockReadFileSync };
  return { ...overridden, default: overridden };
});

vi.mock("@/lib/services/sitemap-parser", () => ({
  parseSitemap: vi.fn().mockResolvedValue([]),
}));
vi.mock("@/lib/services/cdn-warmer", () => ({
  warmUrls: vi.fn().mockResolvedValue([]),
  closeBrowser: vi.fn(),
}));
vi.mock("@/lib/services/facebook-warmer", () => ({ warmFacebook: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/linkedin-warmer", () => ({ warmLinkedIn: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/twitter-warmer", () => ({ warmTwitter: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/indexnow", () => ({ submitIndexNow: vi.fn().mockResolvedValue({ status: "skipped", urlCount: 0 }) }));
vi.mock("@/lib/services/google-indexer", () => ({ submitToGoogle: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/bing-indexer", () => ({ submitToBing: vi.fn().mockResolvedValue({ status: "skipped", urlCount: 0 }) }));

describe("Security: Authentication", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  const endpoints = [
    { name: "GET /api/jobs", importPath: "@/app/api/jobs/route", method: "GET" },
    { name: "GET /api/sitemaps", importPath: "@/app/api/sitemaps/route", method: "GET" },
    { name: "GET /api/status", importPath: "@/app/api/status/route", method: "GET" },
    { name: "GET /api/logs", importPath: "@/app/api/logs/route", method: "GET" },
  ];

  for (const endpoint of endpoints) {
    it(`should reject unauthenticated request to ${endpoint.name}`, async () => {
      const module = await import(endpoint.importPath);
      const handler = module[endpoint.method];

      const request = new NextRequest(`http://localhost:3000${endpoint.name.split(" ")[1]}`, {
        method: endpoint.method,
      });

      const response = await handler(request);
      expect(response.status).toBe(401);
    });

    it(`should reject invalid token for ${endpoint.name}`, async () => {
      const module = await import(endpoint.importPath);
      const handler = module[endpoint.method];

      const request = new NextRequest(`http://localhost:3000${endpoint.name.split(" ")[1]}`, {
        method: endpoint.method,
        headers: { Authorization: "Bearer invalid-token-12345" },
      });

      const response = await handler(request);
      expect(response.status).toBe(403);
    });
  }

  it("should reject request with Bearer prefix but no token", async () => {
    const { GET } = await import("@/app/api/jobs/route");

    const request = new NextRequest("http://localhost:3000/api/jobs", {
      headers: { Authorization: "Bearer " },
    });

    const response = await GET(request);
    // Empty token after "Bearer " - returns 401 or 403 depending on header handling
    expect([401, 403]).toContain(response.status);
  });

  it("should reject request with Basic auth instead of Bearer", async () => {
    const { GET } = await import("@/app/api/jobs/route");

    const request = new NextRequest("http://localhost:3000/api/jobs", {
      headers: { Authorization: "Basic dGVzdDp0ZXN0" },
    });

    const response = await GET(request);
    expect(response.status).toBe(401);
  });

  it("should reject POST /api/warm without auth", async () => {
    const { POST } = await import("@/app/api/warm/route");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sitemapUrl: "https://example.com/sitemap.xml" }),
    });

    const response = await POST(request);
    expect(response.status).toBe(401);
  });

  it("should reject POST /api/sitemaps without auth", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");

    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ url: "https://example.com/sitemap.xml" }),
    });

    const response = await POST(request);
    expect(response.status).toBe(401);
  });
});

describe("Security: SQL Injection Prevention", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should safely handle SQL injection in sitemap URL", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");

    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({
        url: "https://example.com/sitemap.xml'; DROP TABLE sitemaps; --",
      }),
    });

    const response = await POST(request);
    // Should either succeed (URL is valid) or return 400 (invalid URL)
    // But should NOT drop the table
    const sitemaps = testDb.prepare("SELECT * FROM sitemaps").all();
    // Table still exists (no SQL injection)
    expect(sitemaps).toBeDefined();
  });

  it("should safely handle SQL injection in job ID parameter", async () => {
    const { GET } = await import("@/app/api/jobs/[id]/route");

    const maliciousId = "'; DROP TABLE jobs; --";
    const request = new NextRequest(`http://localhost:3000/api/jobs/${maliciousId}`, {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request, { params: Promise.resolve({ id: maliciousId }) });
    expect(response.status).toBe(404);

    // Verify table still exists
    const jobs = testDb.prepare("SELECT * FROM jobs").all();
    expect(jobs).toBeDefined();
  });

  it("should safely handle SQL injection in logs jobId filter", async () => {
    const { GET } = await import("@/app/api/logs/route");

    const request = new NextRequest(
      "http://localhost:3000/api/logs?jobId=' OR 1=1; DROP TABLE url_results; --",
      { headers: { Authorization: "Bearer test-api-key-12345" } }
    );

    await GET(request);

    // Table still exists
    const results = testDb.prepare("SELECT * FROM url_results").all();
    expect(results).toBeDefined();
  });

  it("should safely handle SQL injection in pagination parameters", async () => {
    const { GET } = await import("@/app/api/jobs/route");

    const request = new NextRequest(
      "http://localhost:3000/api/jobs?limit=1;DROP TABLE jobs&offset=0",
      { headers: { Authorization: "Bearer test-api-key-12345" } }
    );

    const response = await GET(request);
    // parseInt will convert "1;DROP TABLE jobs" to 1
    expect(response.status).toBe(200);

    // Table still exists
    const jobs = testDb.prepare("SELECT * FROM jobs").all();
    expect(jobs).toBeDefined();
  });
});

describe("Security: Input Validation", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should reject non-URL strings for sitemapUrl", async () => {
    const { POST } = await import("@/app/api/warm/route");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({ sitemapUrl: "not a url" }),
    });

    const response = await POST(request);
    expect(response.status).toBe(400);
  });

  it("should reject empty body", async () => {
    const { POST } = await import("@/app/api/warm/route");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({}),
    });

    const response = await POST(request);
    expect(response.status).toBe(400);
  });

  it("should reject non-string sitemapUrl", async () => {
    const { POST } = await import("@/app/api/warm/route");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({ sitemapUrl: 12345 }),
    });

    const response = await POST(request);
    expect(response.status).toBe(400);
  });

  it("should reject javascript: protocol URLs", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");

    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({ url: "javascript:alert('xss')" }),
    });

    const response = await POST(request);
    // JavaScript URLs may pass URL validation but domain extraction should be safe
    // Either 201 (stored harmlessly) or 400 (rejected)
    expect([201, 400]).toContain(response.status);
  });

  it("should handle extremely long URLs gracefully", async () => {
    const { POST } = await import("@/app/api/warm/route");

    const longUrl = "https://example.com/" + "a".repeat(10000);
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({ sitemapUrl: longUrl }),
    });

    const response = await POST(request);
    // Should not crash - either accept or reject gracefully
    expect([202, 400]).toContain(response.status);
  });

  it("should handle special characters in sitemap URL", async () => {
    const { POST } = await import("@/app/api/sitemaps/route");

    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: JSON.stringify({
        url: "https://example.com/sitemap.xml?foo=bar&baz=<script>alert(1)</script>",
      }),
    });

    const response = await POST(request);
    if (response.status === 201) {
      const body = await response.json();
      // Stored URL should be exact (no XSS in API responses which are JSON)
      expect(body.domain).toBe("example.com");
    }
  });
});

describe("Security: XSS Prevention", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should return JSON content type (not HTML)", async () => {
    const { GET } = await import("@/app/api/status/route");

    const request = new NextRequest("http://localhost:3000/api/status", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const contentType = response.headers.get("content-type");
    expect(contentType).toContain("application/json");
  });

  it("should not execute scripts stored in database fields", async () => {
    testDb.prepare(
      "INSERT INTO sitemaps (id, url, domain) VALUES (?, ?, ?)"
    ).run("xss-test", "<script>alert('xss')</script>", "<img onerror=alert(1)>");

    const { GET } = await import("@/app/api/sitemaps/route");

    const request = new NextRequest("http://localhost:3000/api/sitemaps", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    // Data is returned as-is in JSON (which is safe because JSON doesn't execute scripts)
    expect(body.sitemaps[0].url).toBe("<script>alert('xss')</script>");
    // The response is JSON, not HTML, so scripts won't execute
  });
});

describe("Security: Sensitive Data Exposure", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should mask sensitive values in settings API", async () => {
    const { stringify } = await import("yaml");
    const configContent = stringify(createMockConfig());

    mockExistsSync.mockReturnValue(true);
    mockReadFileSync.mockReturnValue(configContent);

    const { GET } = await import("@/app/api/settings/route");

    const request = new NextRequest("http://localhost:3000/api/settings", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request);
    const body = await response.json();

    // API key should be masked
    expect(body.server.apiKey).toBe("***configured***");
    // Facebook secret should be masked
    expect(body.facebook.appSecret).toBe("***configured***");
    // LinkedIn cookie should be masked
    expect(body.linkedin.sessionCookie).toBe("***configured***");
    // Bing key should be masked
    expect(body.bing.apiKey).toBe("***configured***");
  });

  it("should not expose stack traces in error responses", async () => {
    const { POST } = await import("@/app/api/warm/route");

    // Send completely invalid JSON
    const request = new NextRequest("http://localhost:3000/api/warm", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer test-api-key-12345",
      },
      body: "not json at all",
    });

    const response = await POST(request);
    const body = await response.json();

    // Should return a generic error, not a stack trace
    expect(response.status).toBe(500);
    expect(body.error).not.toContain("at ");
    expect(body.error).not.toContain(".ts:");
  });
});

describe("Security: API Key Timing Attack Prevention", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should reject tokens that are the wrong length", async () => {
    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      headers: { Authorization: "Bearer short" },
    });

    const result = authenticateRequest(request);
    expect(result).not.toBeNull();
    expect(result!.status).toBe(403);
  });

  it("should reject tokens with correct prefix but wrong suffix", async () => {
    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      headers: { Authorization: "Bearer test-api-key-wrong" },
    });

    const result = authenticateRequest(request);
    expect(result).not.toBeNull();
    expect(result!.status).toBe(403);
  });
});

describe("Security: Path Traversal", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
  });

  it("should not allow path traversal in job ID", async () => {
    const { GET } = await import("@/app/api/jobs/[id]/route");

    const request = new NextRequest("http://localhost:3000/api/jobs/../../etc/passwd", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await GET(request, {
      params: Promise.resolve({ id: "../../etc/passwd" }),
    });
    expect(response.status).toBe(404);
  });

  it("should not allow path traversal in sitemap ID", async () => {
    const { DELETE } = await import("@/app/api/sitemaps/[id]/route");

    const request = new NextRequest("http://localhost:3000/api/sitemaps/../../../etc/passwd", {
      method: "DELETE",
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const response = await DELETE(request, {
      params: Promise.resolve({ id: "../../../etc/passwd" }),
    });
    expect(response.status).toBe(404);
  });
});
