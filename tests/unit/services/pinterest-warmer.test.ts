import { describe, it, expect, vi, beforeEach } from "vitest";
import { resetTestConfig, testConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

vi.mock("@/lib/logger", () => ({
  default: {
    info: vi.fn(),
    warn: vi.fn(),
    error: vi.fn(),
  },
}));

describe("Pinterest Warmer", () => {
  beforeEach(() => {
    resetTestConfig();
    global.fetch = vi.fn();
  });

  it("should skip when pinterest is disabled", async () => {
    (testConfig as any).pinterest = { enabled: false, delay: 100 };
    const { warmPinterest } = await import("@/lib/services/pinterest-warmer");
    const results = await warmPinterest(["https://example.com/page1"]);
    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("skipped");
  });

  it("should warm URLs when pinterest is enabled", async () => {
    (testConfig as any).pinterest = { enabled: true, delay: 10 };
    (global.fetch as any).mockResolvedValue({ status: 200 });

    const { warmPinterest } = await import("@/lib/services/pinterest-warmer");
    const results = await warmPinterest(["https://example.com/page1"]);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("success");
    expect(results[0].httpStatus).toBe(200);
    expect(results[0].durationMs).toBeGreaterThanOrEqual(0);
  });

  it("should report failed for HTTP errors", async () => {
    (testConfig as any).pinterest = { enabled: true, delay: 10 };
    (global.fetch as any).mockResolvedValue({ status: 500 });

    const { warmPinterest } = await import("@/lib/services/pinterest-warmer");
    const results = await warmPinterest(["https://example.com/page1"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toContain("500");
  });

  it("should handle fetch errors gracefully", async () => {
    (testConfig as any).pinterest = { enabled: true, delay: 10 };
    (global.fetch as any).mockRejectedValue(new Error("Network error"));

    const { warmPinterest } = await import("@/lib/services/pinterest-warmer");
    const results = await warmPinterest(["https://example.com/page1"]);

    expect(results[0].status).toBe("failed");
    expect(results[0].error).toBe("Network error");
  });

  it("should call onProgress callback for each URL", async () => {
    (testConfig as any).pinterest = { enabled: true, delay: 10 };
    (global.fetch as any).mockResolvedValue({ status: 200 });

    const { warmPinterest } = await import("@/lib/services/pinterest-warmer");
    const onProgress = vi.fn();
    await warmPinterest(["https://example.com/p1", "https://example.com/p2"], onProgress);

    expect(onProgress).toHaveBeenCalledTimes(2);
  });

  it("should use correct Pinterest URL debugger endpoint", async () => {
    (testConfig as any).pinterest = { enabled: true, delay: 10 };
    (global.fetch as any).mockResolvedValue({ status: 200 });

    const { warmPinterest } = await import("@/lib/services/pinterest-warmer");
    await warmPinterest(["https://example.com/page1"]);

    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringContaining("developers.pinterest.com/tools/url-debugger"),
      expect.any(Object)
    );
  });
});
