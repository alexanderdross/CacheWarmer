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
const mockSetCookie = vi.fn();
const mockPageClose = vi.fn();
const mockBrowserClose = vi.fn();
const mockNewPage = vi.fn();

vi.mock("puppeteer-core", () => ({
  default: {
    launch: vi.fn().mockResolvedValue({
      connected: true,
      newPage: mockNewPage,
      close: mockBrowserClose,
    }),
  },
}));

describe("LinkedIn Warmer", () => {
  beforeEach(() => {
    resetTestConfig();

    // Reset specific mock behaviors without clearing the mock factory
    mockGoto.mockReset().mockResolvedValue(undefined);
    mockSetCookie.mockReset().mockResolvedValue(undefined);
    mockPageClose.mockReset().mockResolvedValue(undefined);
    mockBrowserClose.mockReset().mockResolvedValue(undefined);
    mockNewPage.mockReset().mockResolvedValue({
      goto: mockGoto,
      setCookie: mockSetCookie,
      close: mockPageClose,
    });
  });

  it("should warm URLs via LinkedIn Post Inspector", async () => {
    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");

    const results = await warmLinkedIn(["https://example.com/page1"]);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("success");
    expect(results[0].url).toBe("https://example.com/page1");
  });

  it("should set LinkedIn session cookie", async () => {
    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");

    await warmLinkedIn(["https://example.com/page1"]);

    expect(mockSetCookie).toHaveBeenCalledWith(
      expect.objectContaining({
        name: "li_at",
        value: "test-session-cookie",
        domain: ".linkedin.com",
      })
    );
  });

  it("should navigate to post inspector URL", async () => {
    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");

    await warmLinkedIn(["https://example.com/page1"]);

    expect(mockGoto).toHaveBeenCalledWith(
      expect.stringContaining("linkedin.com/post-inspector/inspect/"),
      expect.objectContaining({ waitUntil: "networkidle0" })
    );
  });

  it("should skip when disabled", async () => {
    const config = createMockConfig();
    config.linkedin.enabled = false;
    setTestConfig(config);

    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");
    const results = await warmLinkedIn(["https://example.com/page1"]);

    expect(results[0].status).toBe("skipped");
  });

  it("should skip when session cookie is not configured", async () => {
    const config = createMockConfig();
    config.linkedin.sessionCookie = "";
    setTestConfig(config);

    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");
    const results = await warmLinkedIn(["https://example.com/page1"]);

    expect(results[0].status).toBe("skipped");
  });

  it("should handle navigation errors", async () => {
    mockGoto.mockRejectedValue(new Error("Timeout exceeded"));

    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");
    const results = await warmLinkedIn(["https://example.com/page1"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toBe("Timeout exceeded");
  });

  it("should close page after processing (browser is reused)", async () => {
    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");

    await warmLinkedIn(["https://example.com/page1"]);

    // Page should be closed, but browser stays alive for reuse
    expect(mockPageClose).toHaveBeenCalled();
  });

  it("should close browser via closeBrowser()", async () => {
    const { warmLinkedIn, closeBrowser } = await import("@/lib/services/linkedin-warmer");

    await warmLinkedIn(["https://example.com/page1"]);
    await closeBrowser();

    expect(mockBrowserClose).toHaveBeenCalled();
  });

  it("should call onProgress callback", async () => {
    const { warmLinkedIn } = await import("@/lib/services/linkedin-warmer");
    const onProgress = vi.fn();

    await warmLinkedIn(["https://example.com/page1"], onProgress);

    expect(onProgress).toHaveBeenCalledTimes(1);
  });
});
