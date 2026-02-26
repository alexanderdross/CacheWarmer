import { describe, it, expect, vi, beforeEach } from "vitest";
import {
  createMockConfig,
  resetTestConfig,
  setTestConfig,
} from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

// Mock puppeteer-core with stable module-level mock functions
const mockGoto = vi.fn();
const mockPageClose = vi.fn();
const mockBrowserClose = vi.fn();
const mockNewPage = vi.fn();

vi.mock("puppeteer-core", () => ({
  default: {
    launch: vi.fn().mockResolvedValue({
      newPage: mockNewPage,
      close: mockBrowserClose,
    }),
  },
}));

describe("Twitter Warmer", () => {
  beforeEach(() => {
    resetTestConfig();

    // Reset specific mock behaviors without clearing the mock factory
    mockGoto.mockReset().mockResolvedValue(undefined);
    mockPageClose.mockReset().mockResolvedValue(undefined);
    mockBrowserClose.mockReset().mockResolvedValue(undefined);
    mockNewPage.mockReset().mockResolvedValue({
      goto: mockGoto,
      close: mockPageClose,
    });
  });

  it("should warm URLs via Tweet Composer", async () => {
    const { warmTwitter } = await import("@/lib/services/twitter-warmer");

    const results = await warmTwitter(["https://example.com/page1"]);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("success");
  });

  it("should navigate to Tweet Composer URL", async () => {
    const { warmTwitter } = await import("@/lib/services/twitter-warmer");

    await warmTwitter(["https://example.com/page1"]);

    expect(mockGoto).toHaveBeenCalledWith(
      expect.stringContaining("twitter.com/intent/tweet?url="),
      expect.objectContaining({ waitUntil: "networkidle0" })
    );
  });

  it("should URL-encode the target URL in composer link", async () => {
    const { warmTwitter } = await import("@/lib/services/twitter-warmer");

    await warmTwitter(["https://example.com/page?q=test&lang=en"]);

    expect(mockGoto).toHaveBeenCalledWith(
      expect.stringContaining(encodeURIComponent("https://example.com/page?q=test&lang=en")),
      expect.any(Object)
    );
  });

  it("should skip when disabled", async () => {
    const config = createMockConfig();
    config.twitter.enabled = false;
    setTestConfig(config);

    const { warmTwitter } = await import("@/lib/services/twitter-warmer");
    const results = await warmTwitter(["https://example.com/page1"]);

    expect(results[0].status).toBe("skipped");
  });

  it("should handle navigation timeout", async () => {
    mockGoto.mockRejectedValue(new Error("Navigation timeout of 5000ms exceeded"));

    const { warmTwitter } = await import("@/lib/services/twitter-warmer");
    const results = await warmTwitter(["https://example.com/page1"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toContain("timeout");
  });

  it("should process URLs in batches based on concurrency", async () => {
    const { warmTwitter } = await import("@/lib/services/twitter-warmer");

    const urls = [
      "https://example.com/page1",
      "https://example.com/page2",
      "https://example.com/page3",
    ];
    const results = await warmTwitter(urls);

    expect(results).toHaveLength(3);
  });

  it("should close pages and browser after processing", async () => {
    const { warmTwitter } = await import("@/lib/services/twitter-warmer");

    await warmTwitter(["https://example.com/page1"]);

    expect(mockPageClose).toHaveBeenCalled();
    expect(mockBrowserClose).toHaveBeenCalled();
  });

  it("should call onProgress callback", async () => {
    const { warmTwitter } = await import("@/lib/services/twitter-warmer");
    const onProgress = vi.fn();

    await warmTwitter(["https://example.com/page1", "https://example.com/page2"], onProgress);

    expect(onProgress).toHaveBeenCalledTimes(2);
  });
});
