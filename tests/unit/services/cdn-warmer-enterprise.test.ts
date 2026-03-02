import { describe, it, expect, vi, beforeEach } from "vitest";
import { resetTestConfig, testConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

const mockGoto = vi.fn();
const mockSetUserAgent = vi.fn();
const mockSetViewport = vi.fn();
const mockSetExtraHTTPHeaders = vi.fn();
const mockSetCookie = vi.fn();
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

describe("CDN Warmer - Enterprise Features", () => {
  beforeEach(() => {
    resetTestConfig();
    mockGoto.mockReset().mockResolvedValue({ status: () => 200, headers: () => ({}) });
    mockSetUserAgent.mockReset().mockResolvedValue(undefined);
    mockSetViewport.mockReset().mockResolvedValue(undefined);
    mockSetExtraHTTPHeaders.mockReset().mockResolvedValue(undefined);
    mockSetCookie.mockReset().mockResolvedValue(undefined);
    mockClose.mockReset().mockResolvedValue(undefined);
    mockBrowserClose.mockReset().mockResolvedValue(undefined);
    mockNewPage.mockReset().mockResolvedValue({
      goto: mockGoto,
      setUserAgent: mockSetUserAgent,
      setViewport: mockSetViewport,
      setExtraHTTPHeaders: mockSetExtraHTTPHeaders,
      setCookie: mockSetCookie,
      close: mockClose,
    });
  });

  it("should use custom user agent when configured", async () => {
    (testConfig as any).cdnWarming.customUserAgent = "CustomBot/1.0";

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/page1"]);

    // First call should be desktop with custom UA
    expect(mockSetUserAgent).toHaveBeenCalledWith("CustomBot/1.0");
  });

  it("should set custom HTTP headers when configured", async () => {
    (testConfig as any).cdnWarming.customHeaders = { "X-Warm": "true", "X-Source": "cachewarmer" };

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/page1"]);

    expect(mockSetExtraHTTPHeaders).toHaveBeenCalledWith(
      expect.objectContaining({ "X-Warm": "true", "X-Source": "cachewarmer" })
    );
  });

  it("should inject auth cookies when configured", async () => {
    (testConfig as any).cdnWarming.authCookies = [
      { name: "session", value: "abc123", domain: "example.com" },
    ];

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/page1"]);

    expect(mockSetCookie).toHaveBeenCalledWith(
      expect.objectContaining({ name: "session", value: "abc123", domain: "example.com" })
    );
  });

  it("should warm custom viewports in addition to desktop and mobile", async () => {
    (testConfig as any).cdnWarming.customViewports = [
      { width: 1024, height: 768, label: "tablet" },
    ];

    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    const results = await warmUrls(["https://example.com/page1"]);

    // 1 URL * (desktop + mobile + tablet) = 3 results
    expect(results).toHaveLength(3);
    expect(results.map(r => r.viewport)).toContain("tablet");
  });

  it("should not set custom headers when not configured", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/page1"]);

    expect(mockSetExtraHTTPHeaders).not.toHaveBeenCalled();
  });

  it("should not inject cookies when not configured", async () => {
    const { warmUrls } = await import("@/lib/services/cdn-warmer");
    await warmUrls(["https://example.com/page1"]);

    expect(mockSetCookie).not.toHaveBeenCalled();
  });
});
