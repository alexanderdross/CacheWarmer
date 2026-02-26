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

// Mock googleapis with a stable publish mock
const publishMock = vi.fn().mockResolvedValue({});

vi.mock("googleapis", () => ({
  google: {
    auth: {
      GoogleAuth: vi.fn().mockImplementation(() => ({
        getClient: vi.fn().mockResolvedValue({}),
      })),
    },
    indexing: vi.fn().mockReturnValue({
      urlNotifications: {
        publish: publishMock,
      },
    }),
  },
}));

// Mock fs for service account key check
const mockExistsSync = vi.fn().mockReturnValue(true);

vi.mock("fs", async () => {
  const actual = await vi.importActual("fs");
  return {
    ...actual,
    existsSync: (...args: unknown[]) => mockExistsSync(...args),
  };
});

describe("Google Indexer", () => {
  beforeEach(() => {
    resetTestConfig();
    publishMock.mockReset().mockResolvedValue({});
    mockExistsSync.mockReset().mockReturnValue(true);
  });

  it("should submit URLs to Google Indexing API", async () => {
    const { submitToGoogle } = await import("@/lib/services/google-indexer");

    const urls = ["https://example.com/page1"];
    const results = await submitToGoogle(urls);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("success");
  });

  it("should skip when disabled", async () => {
    const config = createMockConfig();
    config.google.enabled = false;
    setTestConfig(config);

    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const results = await submitToGoogle(["https://example.com/page1"]);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("skipped");
  });

  it("should skip when key file does not exist", async () => {
    mockExistsSync.mockReturnValue(false);

    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const results = await submitToGoogle(["https://example.com/page1"]);

    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("skipped");
  });

  it("should respect daily quota", async () => {
    const config = createMockConfig();
    config.google.dailyQuota = 2;
    setTestConfig(config);

    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const urls = [
      "https://example.com/page1",
      "https://example.com/page2",
      "https://example.com/page3",
    ];
    const results = await submitToGoogle(urls);

    // Only 2 should be processed (quota = 2)
    expect(results).toHaveLength(2);
  });

  it("should call onProgress for each URL", async () => {
    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const onProgress = vi.fn();

    await submitToGoogle(["https://example.com/page1"], onProgress);

    expect(onProgress).toHaveBeenCalledTimes(1);
    expect(onProgress).toHaveBeenCalledWith(
      expect.objectContaining({
        url: "https://example.com/page1",
        status: "success",
      })
    );
  });
});
