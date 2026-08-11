import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

import { validateSchemaHtml, validateSchemaMarkup } from "@/lib/services/schema-validator";

const ARTICLE_HTML = `<!doctype html>
<html><head><title>Test</title>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "A headline",
  "author": { "@type": "Person", "name": "Alexander Dross" },
  "datePublished": "2026-08-11"
}
</script>
</head><body><p>Body</p></body></html>`;

describe("validateSchemaHtml", () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    global.fetch = vi.fn(() => {
      throw new Error("validateSchemaHtml must not perform any network request");
    }) as unknown as typeof fetch;
  });

  afterEach(() => {
    global.fetch = originalFetch;
  });

  it("validates supplied HTML without fetching anything", async () => {
    // The whole point of the refactor: the CDN warmer already loaded this
    // page, so validating it must not cost another round trip.
    const result = await validateSchemaHtml("https://example.com/article", ARTICLE_HTML);

    expect(global.fetch).not.toHaveBeenCalled();
    expect(result.url).toBe("https://example.com/article");
    expect(result.status).not.toBe("failed");
  });

  it("detects the schema types present in the markup", async () => {
    const result = await validateSchemaHtml("https://example.com/article", ARTICLE_HTML);
    expect(result.schemas.join(" ")).toMatch(/Article/i);
  });

  it("reports a document with no structured data without throwing", async () => {
    const result = await validateSchemaHtml(
      "https://example.com/plain",
      "<!doctype html><html><body><p>Nothing here</p></body></html>",
    );
    expect(result).toHaveProperty("status");
    expect(result.durationMs).toBeGreaterThanOrEqual(0);
  });

  it("survives malformed JSON-LD rather than failing the job", async () => {
    const result = await validateSchemaHtml(
      "https://example.com/broken",
      `<html><head><script type="application/ld+json">{ not valid json </script></head><body></body></html>`,
    );
    expect(result).toHaveProperty("status");
  });
});

describe("validateSchemaMarkup", () => {
  const originalFetch = global.fetch;

  afterEach(() => {
    global.fetch = originalFetch;
  });

  it("fetches each URL exactly once when no HTML is supplied", async () => {
    // structuredDataTest(url) fetched the page twice on its own — once to
    // check the body was non-empty, then again inside structuredDataTestUrl.
    // Fetching here and validating the HTML directly makes it one.
    const fetchMock = vi.fn(async () => new Response(ARTICLE_HTML, { status: 200 }));
    global.fetch = fetchMock as unknown as typeof fetch;

    const results: unknown[] = [];
    await validateSchemaMarkup(["https://example.com/a"], (r) => results.push(r));

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(results).toHaveLength(1);
  });

  it("records an HTTP error without throwing", async () => {
    global.fetch = vi.fn(async () => new Response("Not found", { status: 404 })) as unknown as typeof fetch;

    const results: Array<{ status: string; error?: string }> = [];
    await validateSchemaMarkup(["https://example.com/missing"], (r) => results.push(r));

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toContain("404");
  });

  it("records an empty body as a failure", async () => {
    global.fetch = vi.fn(async () => new Response("", { status: 200 })) as unknown as typeof fetch;

    const results: Array<{ status: string; error?: string }> = [];
    await validateSchemaMarkup(["https://example.com/empty"], (r) => results.push(r));

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toBe("No HTML returned");
  });

  it("records a network failure without throwing", async () => {
    global.fetch = vi.fn(async () => {
      throw new Error("ECONNREFUSED");
    }) as unknown as typeof fetch;

    const results: Array<{ status: string; error?: string }> = [];
    await validateSchemaMarkup(["https://unreachable.test/"], (r) => results.push(r));

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toContain("ECONNREFUSED");
  });
});
