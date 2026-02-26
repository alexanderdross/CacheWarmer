import { describe, it, expect, vi, beforeEach } from "vitest";
import { parseSitemap, type SitemapUrl } from "@/lib/services/sitemap-parser";
import { generateSitemapXml, generateSitemapIndexXml } from "../../helpers";

describe("Sitemap Parser", () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  describe("parseSitemap - regular urlset", () => {
    it("should parse a simple sitemap with multiple URLs", async () => {
      const urls = [
        "https://example.com/page1",
        "https://example.com/page2",
        "https://example.com/page3",
      ];
      const xml = generateSitemapXml(urls);

      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response(xml, { status: 200 })
      );

      const result = await parseSitemap("https://example.com/sitemap.xml");

      expect(result).toHaveLength(3);
      expect(result[0].loc).toBe("https://example.com/page1");
      expect(result[1].loc).toBe("https://example.com/page2");
      expect(result[2].loc).toBe("https://example.com/page3");
    });

    it("should parse lastmod and priority fields", async () => {
      const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/page1</loc>
    <lastmod>2026-02-01</lastmod>
    <priority>0.9</priority>
    <changefreq>daily</changefreq>
  </url>
</urlset>`;

      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response(xml, { status: 200 })
      );

      const result = await parseSitemap("https://example.com/sitemap.xml");

      expect(result).toHaveLength(1);
      expect(result[0].lastmod).toBe("2026-02-01");
      expect(result[0].priority).toBe(0.9);
      expect(result[0].changefreq).toBe("daily");
    });

    it("should handle a single URL (non-array)", async () => {
      const xml = generateSitemapXml(["https://example.com/single"]);

      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response(xml, { status: 200 })
      );

      const result = await parseSitemap("https://example.com/sitemap.xml");

      expect(result).toHaveLength(1);
      expect(result[0].loc).toBe("https://example.com/single");
    });

    it("should deduplicate URLs", async () => {
      const urls = [
        "https://example.com/page1",
        "https://example.com/page1",
        "https://example.com/page2",
      ];
      const xml = generateSitemapXml(urls);

      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response(xml, { status: 200 })
      );

      const result = await parseSitemap("https://example.com/sitemap.xml");

      expect(result).toHaveLength(2);
    });

    it("should return empty array for empty sitemap", async () => {
      const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>`;

      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response(xml, { status: 200 })
      );

      const result = await parseSitemap("https://example.com/sitemap.xml");

      expect(result).toHaveLength(0);
    });
  });

  describe("parseSitemap - sitemap index", () => {
    it("should recursively parse sitemap index", async () => {
      const indexXml = generateSitemapIndexXml([
        "https://example.com/sitemap-1.xml",
        "https://example.com/sitemap-2.xml",
      ]);
      const sitemap1 = generateSitemapXml(["https://example.com/page1"]);
      const sitemap2 = generateSitemapXml(["https://example.com/page2"]);

      const fetchMock = vi
        .spyOn(globalThis, "fetch")
        .mockResolvedValueOnce(new Response(indexXml, { status: 200 }))
        .mockResolvedValueOnce(new Response(sitemap1, { status: 200 }))
        .mockResolvedValueOnce(new Response(sitemap2, { status: 200 }));

      const result = await parseSitemap("https://example.com/sitemap-index.xml");

      expect(fetchMock).toHaveBeenCalledTimes(3);
      expect(result).toHaveLength(2);
      expect(result[0].loc).toBe("https://example.com/page1");
      expect(result[1].loc).toBe("https://example.com/page2");
    });

    it("should deduplicate URLs across child sitemaps", async () => {
      const indexXml = generateSitemapIndexXml([
        "https://example.com/sitemap-1.xml",
        "https://example.com/sitemap-2.xml",
      ]);
      const sitemap1 = generateSitemapXml([
        "https://example.com/page1",
        "https://example.com/shared",
      ]);
      const sitemap2 = generateSitemapXml([
        "https://example.com/page2",
        "https://example.com/shared",
      ]);

      vi.spyOn(globalThis, "fetch")
        .mockResolvedValueOnce(new Response(indexXml, { status: 200 }))
        .mockResolvedValueOnce(new Response(sitemap1, { status: 200 }))
        .mockResolvedValueOnce(new Response(sitemap2, { status: 200 }));

      const result = await parseSitemap("https://example.com/sitemap-index.xml");

      expect(result).toHaveLength(3); // page1, shared, page2
    });
  });

  describe("parseSitemap - error handling", () => {
    it("should throw error on HTTP failure", async () => {
      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response("Not Found", { status: 404, statusText: "Not Found" })
      );

      await expect(
        parseSitemap("https://example.com/sitemap.xml")
      ).rejects.toThrow("Failed to fetch sitemap: 404 Not Found");
    });

    it("should handle malformed XML gracefully", async () => {
      const malformedXml = "<not-a-sitemap><random>data</random></not-a-sitemap>";

      vi.spyOn(globalThis, "fetch").mockResolvedValueOnce(
        new Response(malformedXml, { status: 200 })
      );

      const result = await parseSitemap("https://example.com/sitemap.xml");
      expect(result).toHaveLength(0);
    });

    it("should handle network errors", async () => {
      vi.spyOn(globalThis, "fetch").mockRejectedValueOnce(
        new Error("Network error")
      );

      await expect(
        parseSitemap("https://example.com/sitemap.xml")
      ).rejects.toThrow("Network error");
    });
  });
});
