import { describe, it, expect } from "vitest";
import { extractSubresources, scanTags } from "@/lib/services/subresources";

const DOC = "https://example.com/blog/post";

describe("scanTags", () => {
  it("reads attributes in either quoting style, or none", () => {
    const tags = [...scanTags(`<img src="a.png" alt='x' width=100>`)];
    expect(tags[0].attrs).toMatchObject({ src: "a.png", alt: "x", width: "100" });
  });

  it("does not end a tag at a '>' inside an attribute value", () => {
    // A naive /<img[^>]*>/ regex cuts here and loses the src entirely.
    const tags = [...scanTags(`<img alt="a > b" src="real.png">`)];
    expect(tags[0].attrs.src).toBe("real.png");
  });

  it("ignores comments, doctypes and closing tags", () => {
    const tags = [...scanTags(`<!doctype html><!-- <img src="ghost.png"> --></div><img src="real.png">`)];
    expect(tags).toHaveLength(1);
    expect(tags[0].attrs.src).toBe("real.png");
  });

  it("does not scan inside script bodies", () => {
    const html = `<script>var s = '<img src="fake.png">';</script><img src="real.png">`;
    const srcs = [...scanTags(html)].map((t) => t.attrs.src).filter(Boolean);
    expect(srcs).toEqual(["real.png"]);
  });

  it("handles a self-closing tag", () => {
    expect([...scanTags(`<img src="a.png"/><img src="b.png">`)]).toHaveLength(2);
  });

  it("handles a valueless attribute", () => {
    const tags = [...scanTags(`<script async src="a.js"></script>`)];
    expect(tags[0].attrs).toMatchObject({ async: "", src: "a.js" });
  });
});

describe("extractSubresources", () => {
  it("finds the assets a browser would have loaded", () => {
    const html = `
      <link rel="stylesheet" href="/css/site.css">
      <script src="/js/app.js"></script>
      <img src="/img/hero.jpg">
    `;
    expect(extractSubresources(html, DOC)).toEqual([
      "https://example.com/css/site.css",
      "https://example.com/js/app.js",
      "https://example.com/img/hero.jpg",
    ]);
  });

  it("resolves relative URLs against the document, not the origin", () => {
    expect(extractSubresources(`<img src="thumb.jpg">`, DOC)).toEqual([
      "https://example.com/blog/thumb.jpg",
    ]);
  });

  it("honours <base href>", () => {
    const html = `<base href="https://example.com/assets/"><img src="thumb.jpg">`;
    expect(extractSubresources(html, DOC)).toEqual(["https://example.com/assets/thumb.jpg"]);
  });

  it("takes every candidate out of a srcset, without the descriptors", () => {
    const html = `<img srcset="/a.jpg 1x, /b.jpg 2x" src="/a.jpg">`;
    expect(extractSubresources(html, DOC)).toEqual([
      "https://example.com/a.jpg",
      "https://example.com/b.jpg",
    ]);
  });

  it("skips <link> relations that are not a fetch", () => {
    // rel=canonical, rel=alternate and friends are metadata; a browser issues
    // no request for them, and neither should a warmer.
    const html = `
      <link rel="canonical" href="/canonical">
      <link rel="alternate" href="/feed.xml">
      <link rel="stylesheet" href="/site.css">
    `;
    expect(extractSubresources(html, DOC)).toEqual(["https://example.com/site.css"]);
  });

  it("skips third-party hosts, whose CDN we cannot warm", () => {
    const html = `<script src="https://cdn.thirdparty.io/tag.js"></script><script src="/own.js"></script>`;
    expect(extractSubresources(html, DOC)).toEqual(["https://example.com/own.js"]);
  });

  it("allows hosts that were explicitly configured", () => {
    const html = `<img src="https://static.example.net/hero.jpg">`;
    expect(extractSubresources(html, DOC, { assetHosts: ["static.example.net"] })).toEqual([
      "https://static.example.net/hero.jpg",
    ]);
  });

  it("skips data:, javascript: and other unfetchable URLs", () => {
    const html = `
      <img src="data:image/gif;base64,R0lGOD">
      <script src="javascript:void(0)"></script>
      <img src="/real.png">
    `;
    expect(extractSubresources(html, DOC)).toEqual(["https://example.com/real.png"]);
  });

  it("deduplicates repeated references", () => {
    const html = `<img src="/logo.png"><img src="/logo.png"><img src="logo.png">`;
    expect(extractSubresources(html, `https://example.com/logo-page`)).toEqual([
      "https://example.com/logo.png",
    ]);
  });

  it("stops at the cap", () => {
    const html = Array.from({ length: 50 }, (_, i) => `<img src="/i${i}.png">`).join("");
    expect(extractSubresources(html, DOC, { max: 5 })).toHaveLength(5);
  });

  it("returns nothing when the cap is zero", () => {
    expect(extractSubresources(`<img src="/a.png">`, DOC, { max: 0 })).toEqual([]);
  });

  it("survives malformed markup rather than throwing", () => {
    expect(() => extractSubresources(`<img src="unclosed`, DOC)).not.toThrow();
    expect(() => extractSubresources(`<<<>>>`, DOC)).not.toThrow();
  });
});
