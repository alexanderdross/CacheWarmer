import { describe, it, expect, vi, beforeEach } from "vitest";
import {
  resetTestConfig,
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
const mockSetUserAgent = vi.fn();
const mockSetViewport = vi.fn();
const mockClose = vi.fn();
const mockNewPage = vi.fn();
const mockBrowserClose = vi.fn();

vi.mock("puppeteer-core", () => ({
  default: {
    launch: vi.fn().mockResolvedValue({
      connected: true,
      newPage: mockNewPage,
      close: mockBrowserClose,
    }),
  },
}));

describe("CDN Warmer", () => {
  beforeEach(() => {
    resetTestConfig();

    // Reset specific mock behaviors without clearing the mock factory
    mockGoto.mockReset().mockResolvedValue({ status: () => 200 });
    mockSetUserAgent.mockReset().mockResolvedValue(undefined);
    mockSetViewport.mockReset().mockResolvedValue(undefined);
    mockClose.mockReset().mockResolvedValue(undefined);
    mockBrowserClose.mockReset().mockResolvedValue(undefined);
    mockNewPage.mockReset().mockResolvedValue({
      goto: mockGoto,
      setUserAgent: mockSetUserAgent,
      setViewport: mockSetViewport,
      close: mockClose,
    });
  });

  it("should warm URLs with desktop and mobile user agents", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");

    const results = await warmUrls(["https://example.com/page1"]);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("success");
    expect(results[0].httpStatus).toBe(200);
    // Should be called twice per URL (desktop + mobile)
    expect(mockSetUserAgent).toHaveBeenCalledTimes(2);
  });

  it("should report failed status for HTTP errors", async () => {
    mockGoto.mockResolvedValue({ status: () => 500 });

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/page1"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].httpStatus).toBe(500);
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

    expect(results).toHaveLength(3);
    // With concurrency=2, should process in 2 batches
    expect(mockNewPage).toHaveBeenCalledTimes(3);
  });

  it("should call onProgress callback", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const onProgress = vi.fn();

    await warmUrls(["https://example.com/page1"], onProgress);

    expect(onProgress).toHaveBeenCalledTimes(1);
    expect(onProgress).toHaveBeenCalledWith(
      expect.objectContaining({
        url: "https://example.com/page1",
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

    expect(results[0].durationMs).toBeGreaterThanOrEqual(0);
    expect(typeof results[0].durationMs).toBe("number");
  });

  it("should close browser via closeBrowser()", async () => {
    const { closeBrowser } = await import("@/lib/services/cdn-warmer");
    await closeBrowser();
    // Should not throw
  });
});
