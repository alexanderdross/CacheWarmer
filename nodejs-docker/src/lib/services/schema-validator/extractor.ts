import * as cheerio from "cheerio";

export interface ExtractedJsonLd {
  raw: string;
  parsed: unknown;
}

export function extractJsonLd(html: string): ExtractedJsonLd[] {
  const $ = cheerio.load(html);
  const blocks: ExtractedJsonLd[] = [];

  $('script[type="application/ld+json"]').each((_, el) => {
    const raw = $(el).contents().text();
    if (!raw.trim()) return;
    try {
      blocks.push({ raw, parsed: JSON.parse(raw) });
    } catch {
      blocks.push({ raw, parsed: null });
    }
  });

  return blocks;
}

export function extractDocumentTitle(html: string): string | null {
  const $ = cheerio.load(html);
  const title = $("title").first().text().trim();
  return title || null;
}
