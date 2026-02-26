import { describe, it, expect, vi, beforeEach } from "vitest";
import { createMockConfig, resetTestConfig, setTestConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

// Use vi.hoisted so these variables are available when vi.mock factories run (they get hoisted)
const { publishMock, mockExistsSync } = vi.hoisted(() => {
  return {
    publishMock: vi.fn().mockResolvedValue({}),
    mockExistsSync: vi.fn().mockReturnValue(true),
  };
});

// Mock googleapis - GoogleAuth must be a class for `new` to work
vi.mock("googleapis", () => ({
  google: {
    auth: {
      GoogleAuth: class MockGoogleAuth {
        async getClient() { return {}; }
      },
    },
    indexing: () => ({
      urlNotifications: { publish: publishMock },
    }),
  },
}));

// Mock fs - need to override default export for `import fs from "fs"` to work
vi.mock("fs", async () => {
  const actual: Record<string, unknown> = await vi.importActual("fs");
  const overridden = { ...actual, existsSync: mockExistsSync };
  return { ...overridden, default: overridden };
});

describe("Google Indexer", () => {
  beforeEach(() => {
    resetTestConfig();
    publishMock.mockReset().mockResolvedValue({});
    mockExistsSync.mockReset().mockReturnValue(true);
  });

  it("should submit URLs to Google Indexing API", async () => {
    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const results = await submitToGoogle(["https://example.com/page1"]);
    expect(results).toHaveLength(1);
    expect(results[0].status).toBe("success");
  });

  it("should skip when disabled", async () => {
    const config = createMockConfig(); config.google.enabled = false; setTestConfig(config);
    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const results = await submitToGoogle(["https://example.com/page1"]);
    expect(results[0].status).toBe("skipped");
  });

  it("should skip when key file does not exist", async () => {
    mockExistsSync.mockReturnValue(false);
    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const results = await submitToGoogle(["https://example.com/page1"]);
    expect(results[0].status).toBe("skipped");
  });

  it("should respect daily quota", async () => {
    const config = createMockConfig(); config.google.dailyQuota = 2; setTestConfig(config);
    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const results = await submitToGoogle(["https://example.com/page1", "https://example.com/page2", "https://example.com/page3"]);
    expect(results).toHaveLength(2);
  });

  it("should call onProgress for each URL", async () => {
    const { submitToGoogle } = await import("@/lib/services/google-indexer");
    const onProgress = vi.fn();
    await submitToGoogle(["https://example.com/page1"], onProgress);
    expect(onProgress).toHaveBeenCalledTimes(1);
    expect(onProgress).toHaveBeenCalledWith(expect.objectContaining({ url: "https://example.com/page1", status: "success" }));
  });
});
