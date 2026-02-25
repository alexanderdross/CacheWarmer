import { XMLParser } from "fast-xml-parser";
import logger from "@/lib/logger";

export interface SitemapUrl {
  loc: string;
  lastmod?: string;
  priority?: number;
  changefreq?: string;
}

const parser = new XMLParser({
  ignoreAttributes: false,
  attributeNamePrefix: "@_",
});

export async function parseSitemap(sitemapUrl: string): Promise<SitemapUrl[]> {
  logger.info({ sitemapUrl }, "Fetching sitemap");

  const response = await fetch(sitemapUrl);
  if (!response.ok) {
    throw new Error(`Failed to fetch sitemap: ${response.status} ${response.statusText}`);
  }

  const xml = await response.text();
  const parsed = parser.parse(xml);

  // Handle sitemap index (recursive)
  if (parsed.sitemapindex?.sitemap) {
    const sitemaps = Array.isArray(parsed.sitemapindex.sitemap)
      ? parsed.sitemapindex.sitemap
      : [parsed.sitemapindex.sitemap];

    logger.info({ count: sitemaps.length }, "Found sitemap index, fetching child sitemaps");

    const allUrls: SitemapUrl[] = [];
    for (const sitemap of sitemaps) {
      const loc = typeof sitemap === "string" ? sitemap : sitemap.loc;
      if (loc) {
        const childUrls = await parseSitemap(loc);
        allUrls.push(...childUrls);
      }
    }
    return deduplicateUrls(allUrls);
  }

  // Handle regular urlset
  if (parsed.urlset?.url) {
    const urls = Array.isArray(parsed.urlset.url)
      ? parsed.urlset.url
      : [parsed.urlset.url];

    const result: SitemapUrl[] = urls.map((entry: Record<string, unknown>) => ({
      loc: String(entry.loc || ""),
      lastmod: entry.lastmod ? String(entry.lastmod) : undefined,
      priority: entry.priority ? Number(entry.priority) : undefined,
      changefreq: entry.changefreq ? String(entry.changefreq) : undefined,
    })).filter((u: SitemapUrl) => u.loc);

    logger.info({ count: result.length }, "Parsed URLs from sitemap");
    return deduplicateUrls(result);
  }

  logger.warn({ sitemapUrl }, "No URLs found in sitemap");
  return [];
}

function deduplicateUrls(urls: SitemapUrl[]): SitemapUrl[] {
  const seen = new Set<string>();
  return urls.filter((u) => {
    if (seen.has(u.loc)) return false;
    seen.add(u.loc);
    return true;
  });
}
