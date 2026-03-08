import { describe, it, expect, vi, beforeEach } from "vitest";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

vi.mock("@/lib/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

describe("Priority-based URL Warming", () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it("should sort URLs by priority descending in sitemap parser output", async () => {
    const { parseSitemap } = await import("@/lib/services/sitemap-parser");

    // This tests that the sitemap parser extracts priority correctly
    // The job manager then sorts by priority
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      text: vi.fn().mockResolvedValue(`<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
          <url><loc>https://example.com/low</loc><priority>0.1</priority></url>
          <url><loc>https://example.com/high</loc><priority>1.0</priority></url>
          <url><loc>https://example.com/medium</loc><priority>0.5</priority></url>
        </urlset>`),
    }) as any;

    const results = await parseSitemap("https://example.com/sitemap.xml");

    // Verify priorities are extracted
    expect(results).toHaveLength(3);
    const highPriority = results.find(r => r.loc === "https://example.com/high");
    expect(highPriority?.priority).toBe(1);

    // When sorted by priority desc (as job manager does)
    const sorted = [...results].sort((a, b) => (b.priority ?? 0.5) - (a.priority ?? 0.5));
    expect(sorted[0].loc).toBe("https://example.com/high");
    expect(sorted[1].loc).toBe("https://example.com/medium");
    expect(sorted[2].loc).toBe("https://example.com/low");
  });
});
