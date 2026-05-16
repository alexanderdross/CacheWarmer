import { describe, it, expect } from "vitest";
import { detectFixableRule, generatePatch } from "@/lib/services/schema-validator/auto-fixer";

describe("detectFixableRule", () => {
  it("maps the http context error", () => {
    expect(
      detectFixableRule({
        code: "context-protocol",
        message: "@context uses http://schema.org",
        schemaType: "Product",
        severity: "error",
      })
    ).toBe("http-to-https-context");
  });

  it("maps missing context", () => {
    expect(
      detectFixableRule({
        code: "context-missing",
        message: "@context is missing",
        schemaType: "Product",
        severity: "error",
      })
    ).toBe("missing-context");
  });

  it("returns null for unfixable errors", () => {
    expect(
      detectFixableRule({
        code: "offers-price-missing",
        message: "Offer.price is required",
        schemaType: "Product",
        severity: "error",
      })
    ).toBeNull();
  });
});

describe("generatePatch", () => {
  it("rewrites http to https schema context", () => {
    const src = `const ld = { "@context": "http://schema.org", "@type": "Product" };`;
    const patch = generatePatch("foo.ts", src, "http-to-https-context");
    expect(patch).not.toBeNull();
    expect(patch!.newContent).toContain("https://schema.org");
    expect(patch!.newContent).not.toContain("http://schema.org");
  });

  it("returns null when there is nothing to fix", () => {
    const src = `const ld = { "@context": "https://schema.org" };`;
    expect(generatePatch("foo.ts", src, "http-to-https-context")).toBeNull();
  });

  it("normalises type literal casing", () => {
    const src = `const ld = { "@type": "product" };`;
    const patch = generatePatch("foo.ts", src, "type-literal-case");
    expect(patch).not.toBeNull();
    expect(patch!.newContent).toContain('"@type": "Product"');
  });

  it("injects Organization.name when site title is provided", () => {
    const src = `const ld = { "@type": "Organization", "url": "x" };`;
    const patch = generatePatch("foo.ts", src, "missing-organization-name", {
      siteTitle: "TradeAero",
    });
    expect(patch).not.toBeNull();
    expect(patch!.newContent).toContain('"name": "TradeAero"');
  });
});
