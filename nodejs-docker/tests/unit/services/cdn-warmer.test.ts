import { describe, it, expect, vi, beforeEach } from "vitest";
import { resetTestConfig, testConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

// Mock puppeteer-core with stable module-level mock functions
const mockGoto = vi.fn();
const mockSetUserAgent = vi.fn();
const mockSetViewport = vi.fn();
const mockClose = vi.fn();
const mockNewPage = vi.fn();
const mockBrowserClose = vi.fn();
const mockContent = vi.fn();

vi.mock("puppeteer-core", () => ({
  default: {
    launch: vi.fn().mockResolvedValue({
      connected: true,
      newPage: mockNewPage,
      close: mockBrowserClose,
    }),
  },
}));

describe("CDN Warmer (browser engine)", () => {
  beforeEach(() => {
    resetTestConfig();
    // Chromium is no longer the default path; these tests are about it.
    testConfig.cdnWarming.engine = "browser";

    // Reset specific mock behaviors without clearing the mock factory
    mockGoto.mockReset().mockResolvedValue({ status: () => 200, headers: () => ({}) });
    mockSetUserAgent.mockReset().mockResolvedValue(undefined);
    mockSetViewport.mockReset().mockResolvedValue(undefined);
    mockClose.mockReset().mockResolvedValue(undefined);
    mockBrowserClose.mockReset().mockResolvedValue(undefined);
    mockContent.mockReset().mockResolvedValue("<html><body>page</body></html>");
    mockNewPage.mockReset().mockResolvedValue({
      goto: mockGoto,
      setUserAgent: mockSetUserAgent,
      setViewport: mockSetViewport,
      close: mockClose,
      content: mockContent,
    });
  });

  describe("onHtml", () => {
    it("hands the loaded document to the consumer once per URL", async () => {
      const { warmUrls } = await import("@/lib/services/cdn-warmer");

      const seen: Array<{ url: string; html: string }> = [];
      await warmUrls(["https://example.com/a", "https://example.com/b"], undefined, (url, html) => {
        seen.push({ url, html });
      });

      // Once per URL, not once per pass — the desktop pass supplies it.
      expect(seen).toHaveLength(2);
      expect(seen.map((s) => s.url).sort()).toEqual([
        "https://example.com/a",
        "https://example.com/b",
      ]);
      expect(seen[0].html).toContain("page");
    });

    it("does not read the page body when no consumer is registered", async () => {
      const { warmUrls } = await import("@/lib/services/cdn-warmer");

      await warmUrls(["https://example.com/a"]);

      // Serialising the DOM is not free, so it only happens on demand.
      expect(mockContent).not.toHaveBeenCalled();
    });

    it("skips the consumer for URLs that failed to load", async () => {
      mockGoto.mockResolvedValue({ status: () => 500, headers: () => ({}) });
      const { warmUrls } = await import("@/lib/services/cdn-warmer");

      const seen: string[] = [];
      await warmUrls(["https://example.com/a"], undefined, (url) => {
        seen.push(url);
      });

      expect(seen).toHaveLength(0);
    });

    it("keeps warming when the consumer throws", async () => {
      const { warmUrls } = await import("@/lib/services/cdn-warmer");

      const results = await warmUrls(["https://example.com/a"], undefined, () => {
        throw new Error("validation blew up");
      });

      // A schema validation failure must not abort the warming run.
      expect(results).toHaveLength(2);
      expect(results[0].status).toBe("success");
    });

    it("awaits an async consumer before moving on", async () => {
      const { warmUrls } = await import("@/lib/services/cdn-warmer");

      let settled = false;
      await warmUrls(["https://example.com/a"], undefined, async () => {
        await new Promise((resolve) => setTimeout(resolve, 10));
        settled = true;
      });

      // Awaiting is what keeps HTML from piling up across a large sitemap.
      expect(settled).toBe(true);
    });
  });

  it("should warm URLs with desktop and mobile user agents", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");

    const results = await warmUrls(["https://example.com/page1"]);

    // Returns 2 results per URL (desktop + mobile)
    expect(results).toHaveLength(2);
    expect(results[0].viewport).toBe("desktop");
    expect(results[0].status).toBe("success");
    expect(results[0].httpStatus).toBe(200);
    expect(results[1].viewport).toBe("mobile");
    expect(results[1].status).toBe("success");
    // Should be called twice per URL (desktop + mobile)
    expect(mockSetUserAgent).toHaveBeenCalledTimes(2);
  });

  it("should report failed status for HTTP errors", async () => {
    mockGoto.mockResolvedValue({ status: () => 500, headers: () => ({}) });

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/page1"]);

    // Both desktop and mobile should fail
    expect(results).toHaveLength(2);
    expect(results[0].status).toBe("failed");
    expect(results[0].httpStatus).toBe(500);
    expect(results[1].status).toBe("failed");
    expect(results[1].httpStatus).toBe(500);
  });

  it("should handle page navigation errors", async () => {
    mockGoto.mockRejectedValue(new Error("Navigation timeout"));

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/page1"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toBe("Navigation timeout");
  });

  it("should process URLs in batches based on concurrency", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");

    const urls = [
      "https://example.com/page1",
      "https://example.com/page2",
      "https://example.com/page3",
    ];
    const results = await warmUrls(urls);

    // 3 URLs * 2 viewports (desktop + mobile) = 6 results
    expect(results).toHaveLength(6);
    // With concurrency=2, should process in 2 batches (one page per URL)
    expect(mockNewPage).toHaveBeenCalledTimes(3);
  });

  it("should call onProgress callback", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const onProgress = vi.fn();

    await warmUrls(["https://example.com/page1"], onProgress);

    // Called twice: once for desktop, once for mobile
    expect(onProgress).toHaveBeenCalledTimes(2);
    expect(onProgress).toHaveBeenCalledWith(
      expect.objectContaining({
        url: "https://example.com/page1",
        viewport: "desktop",
        status: "success",
      })
    );
    expect(onProgress).toHaveBeenCalledWith(
      expect.objectContaining({
        url: "https://example.com/page1",
        viewport: "mobile",
        status: "success",
      })
    );
  });

  it("should always close pages after processing", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");

    await warmUrls(["https://example.com/page1"]);

    expect(mockClose).toHaveBeenCalled();
  });

  it("should measure duration in milliseconds", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/page1"]);

    // Both desktop and mobile results should have duration
    for (const result of results) {
      expect(result.durationMs).toBeGreaterThanOrEqual(0);
      expect(typeof result.durationMs).toBe("number");
    }
  });

  it("should close browser via closeBrowser()", async () => {
    const { closeBrowser } = await import("@/lib/services/cdn-warmer");
    await closeBrowser();
    // Should not throw
  });
});
