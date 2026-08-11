import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { resetTestConfig, testConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

// puppeteer-core is still imported by the module under test, for the browser
// engine. It must never be launched on the fetch path.
const mockLaunch = vi.fn();
vi.mock("puppeteer-core", () => ({ default: { launch: mockLaunch } }));

interface Recorded {
  url: string;
  headers: Record<string, string>;
}

let recorded: Recorded[];

/** A minimal Response stand-in; only what cdn-warmer reads is implemented. */
function respond(body: string, headers: Record<string, string> = {}) {
  const merged = { "content-type": "text/html; charset=utf-8", ...headers };
  return {
    status: 200,
    headers: new Headers(merged),
    text: async () => body,
    arrayBuffer: async () => new ArrayBuffer(0),
  } as unknown as Response;
}

function install(handler: (url: string, init: RequestInit) => Response | Promise<Response>) {
  vi.stubGlobal(
    "fetch",
    vi.fn(async (input: string, init: RequestInit = {}) => {
      recorded.push({
        url: String(input),
        headers: (init.headers as Record<string, string>) ?? {},
      });
      return handler(String(input), init);
    })
  );
}

describe("CDN Warmer (fetch engine)", () => {
  beforeEach(() => {
    resetTestConfig();
    recorded = [];
    mockLaunch.mockReset();
    install(() => respond("<html><body>page</body></html>"));
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("is the default engine, and never launches a browser", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    expect(testConfig.cdnWarming.engine).toBe("fetch");

    await warmUrls(["https://example.com/a"]);

    expect(mockLaunch).not.toHaveBeenCalled();
  });

  it("makes a desktop fill pass and a mobile probe pass", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");

    const results = await warmUrls(["https://example.com/a"]);

    expect(results.map((r) => r.viewport)).toEqual(["desktop", "mobile"]);
    expect(recorded.filter((r) => r.url === "https://example.com/a")).toHaveLength(2);
    expect(recorded[0].headers["User-Agent"]).toBe("Mozilla/5.0 Desktop");
    expect(recorded[1].headers["User-Agent"]).toBe("Mozilla/5.0 Mobile");
  });

  it("records the verdict on the probe, from the pair of passes", async () => {
    let call = 0;
    install(() =>
      respond("<html></html>", { "cf-cache-status": call++ === 0 ? "MISS" : "HIT" })
    );

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/a"]);

    // MISS on the fill is the success signal; the HIT on the probe proves it.
    expect(results[1].cacheHeaders?.verdict).toBe("warmed");
    expect(results[0].cacheHeaders?.verdict).toBeUndefined();
  });

  it("hands the fill pass's HTML to the consumer, without a second request", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");

    const seen: string[] = [];
    await warmUrls(["https://example.com/a"], undefined, (_url, html) => {
      seen.push(html);
    });

    expect(seen).toEqual(["<html><body>page</body></html>"]);
    expect(recorded.filter((r) => r.url === "https://example.com/a")).toHaveLength(2);
  });

  it("warms the subresources the page references", async () => {
    install((url) =>
      url.endsWith("/a")
        ? respond(`<link rel="stylesheet" href="/site.css"><img src="/hero.jpg">`)
        : respond("", { "content-type": "text/css" })
    );

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/a"]);

    const fetched = recorded.map((r) => r.url);
    expect(fetched).toContain("https://example.com/site.css");
    expect(fetched).toContain("https://example.com/hero.jpg");
  });

  it("warms a shared asset once per run, not once per page", async () => {
    install((url) =>
      url.includes(".css") ? respond("", { "content-type": "text/css" }) : respond(`<link rel="stylesheet" href="/site.css">`)
    );

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/a", "https://example.com/b", "https://example.com/c"]);

    expect(recorded.filter((r) => r.url === "https://example.com/site.css")).toHaveLength(1);
  });

  it("does not warm assets when the cap is zero", async () => {
    testConfig.cdnWarming.maxAssetsPerPage = 0;
    install(() => respond(`<img src="/hero.jpg">`));

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/a"]);

    expect(recorded.map((r) => r.url)).not.toContain("https://example.com/hero.jpg");
  });

  it("does not let a failing asset fail the page", async () => {
    install((url) => {
      if (url.endsWith(".jpg")) throw new Error("connection reset");
      return respond(`<img src="/hero.jpg">`);
    });

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/a"]);

    expect(results.every((r) => r.status === "success")).toBe(true);
    expect(results.map((r) => r.url)).toEqual(["https://example.com/a", "https://example.com/a"]);
  });

  it("only parses HTML responses", async () => {
    install(() => respond("%PDF-1.7 not markup", { "content-type": "application/pdf" }));

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const seen: string[] = [];
    await warmUrls(["https://example.com/doc.pdf"], undefined, (_u, html) => {
      seen.push(html);
    });

    expect(seen).toEqual([]);
  });

  it("reports a non-2xx response as failed", async () => {
    install(() => ({ ...respond(""), status: 503 }) as unknown as Response);

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/a"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].httpStatus).toBe(503);
  });

  it("reports a thrown request as failed, and keeps going", async () => {
    install((url) => {
      if (url.endsWith("/a")) throw new Error("ETIMEDOUT");
      return respond("<html></html>");
    });

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/a", "https://example.com/b"]);

    expect(results.find((r) => r.url === "https://example.com/a")?.error).toBe("ETIMEDOUT");
    expect(results.find((r) => r.url === "https://example.com/b")?.status).toBe("success");
  });

  describe("Enterprise options", () => {
    it("applies the custom user agent to both passes", async () => {
      (testConfig as any).cdnWarming.customUserAgent = "CustomBot/1.0";

      const { warmUrls } = await import("@/lib/services/cdn-warmer");
      await warmUrls(["https://example.com/a"]);

      expect(recorded[0].headers["User-Agent"]).toBe("CustomBot/1.0");
      expect(recorded[1].headers["User-Agent"]).toBe("CustomBot/1.0");
    });

    it("sends custom headers on every request", async () => {
      (testConfig as any).cdnWarming.customHeaders = { "X-Warm": "true" };

      const { warmUrls } = await import("@/lib/services/cdn-warmer");
      await warmUrls(["https://example.com/a"]);

      expect(recorded.every((r) => r.headers["X-Warm"] === "true")).toBe(true);
    });

    it("sends auth cookies as a Cookie header", async () => {
      (testConfig as any).cdnWarming.authCookies = [
        { name: "session", value: "abc" },
        { name: "role", value: "admin" },
      ];

      const { warmUrls } = await import("@/lib/services/cdn-warmer");
      await warmUrls(["https://example.com/a"]);

      expect(recorded[0].headers.Cookie).toBe("session=abc; role=admin");
    });

    it("adds a pass per custom viewport, expressed as a client hint", async () => {
      (testConfig as any).cdnWarming.customViewports = [{ width: 1440, height: 900, label: "wide" }];

      const { warmUrls } = await import("@/lib/services/cdn-warmer");
      const results = await warmUrls(["https://example.com/a"]);

      expect(results.map((r) => r.viewport)).toEqual(["desktop", "mobile", "wide"]);
      // A pixel size means nothing without a layout engine; the client hint is
      // the only form of it a CDN can vary on.
      expect(recorded[2].headers["Sec-CH-Viewport-Width"]).toBe("1440");
    });
  });
});
