import { describe, it, expect } from "vitest";
import { extractJsonLd, extractDocumentTitle } from "@/lib/services/schema-validator/extractor";

const HTML_WITH_TWO_BLOCKS = `
<html>
  <head>
    <title>Cessna C172 — TradeAero</title>
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"Product","name":"Cessna 172"}</script>
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"BreadcrumbList"}</script>
  </head>
  <body></body>
</html>
`;

const HTML_NO_JSONLD = `<html><head><title>x</title></head><body><p>no schema</p></body></html>`;

const HTML_BROKEN_JSONLD = `
<html><head>
  <script type="application/ld+json">{ this is not json }</script>
</head></html>`;

describe("extractJsonLd", () => {
  it("returns parsed JSON-LD blocks in document order", () => {
    const blocks = extractJsonLd(HTML_WITH_TWO_BLOCKS);
    expect(blocks).toHaveLength(2);
    expect((blocks[0].parsed as { "@type": string })["@type"]).toBe("Product");
    expect((blocks[1].parsed as { "@type": string })["@type"]).toBe("BreadcrumbList");
  });

  it("returns empty array when no JSON-LD blocks present", () => {
    expect(extractJsonLd(HTML_NO_JSONLD)).toEqual([]);
  });

  it("captures raw text but parsed=null for unparseable blocks", () => {
    const blocks = extractJsonLd(HTML_BROKEN_JSONLD);
    expect(blocks).toHaveLength(1);
    expect(blocks[0].parsed).toBeNull();
    expect(blocks[0].raw).toContain("this is not json");
  });
});

describe("extractDocumentTitle", () => {
  it("returns the <title> trimmed", () => {
    expect(extractDocumentTitle(HTML_WITH_TWO_BLOCKS)).toBe("Cessna C172 — TradeAero");
  });

  it("returns null when no title present", () => {
    expect(extractDocumentTitle("<html><body></body></html>")).toBeNull();
  });
});
