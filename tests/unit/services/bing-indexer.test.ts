import { describe, it, expect, vi, beforeEach } from "vitest";
import { createMockConfig, setTestConfig, resetTestConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

describe("Bing Indexer", () => {
  beforeEach(() => {
    resetTestConfig();
    vi.spyOn(globalThis, "fetch").mockReset();
  });

  it("should submit URLs to Bing Webmaster API", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("", { status: 200 }));
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    const result = await submitToBing(["https://example.com/page1", "https://example.com/page2"]);
    expect(result.status).toBe("success");
    expect(result.urlCount).toBe(2);
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining("ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch"), expect.objectContaining({ method: "POST" }));
  });

  it("should include API key in URL", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("", { status: 200 }));
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    await submitToBing(["https://example.com/page1"]);
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining("apikey=test-bing-key"), expect.any(Object));
  });

  it("should send correct payload", async () => {
    let capturedBody: string | undefined;
    vi.spyOn(globalThis, "fetch").mockImplementation(async (_url, init) => { capturedBody = init?.body as string; return new Response("", { status: 200 }); });
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    await submitToBing(["https://example.com/page1", "https://example.com/page2"]);
    const body = JSON.parse(capturedBody!);
    expect(body.siteUrl).toBe("https://example.com");
    expect(body.urlList).toEqual(["https://example.com/page1", "https://example.com/page2"]);
  });

  it("should handle API errors", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("Unauthorized", { status: 401 }));
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    const result = await submitToBing(["https://example.com/page1"]);
    expect(result.status).toBe("failed");
    expect(result.error).toContain("Bing API error: 401");
  });

  it("should handle network errors", async () => {
    vi.spyOn(globalThis, "fetch").mockRejectedValue(new Error("Timeout"));
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    const result = await submitToBing(["https://example.com/page1"]);
    expect(result.status).toBe("failed");
    expect(result.error).toBe("Timeout");
  });

  it("should skip when disabled", async () => {
    const config = createMockConfig(); config.bing.enabled = false; setTestConfig(config);
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    const result = await submitToBing(["https://example.com/page1"]);
    expect(result.status).toBe("skipped");
  });

  it("should skip when API key is not configured", async () => {
    const config = createMockConfig(); config.bing.apiKey = ""; setTestConfig(config);
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    const result = await submitToBing(["https://example.com/page1"]);
    expect(result.status).toBe("skipped");
  });

  it("should respect daily quota limit", async () => {
    const config = createMockConfig(); config.bing.dailyQuota = 2; setTestConfig(config);
    let capturedBody: string | undefined;
    vi.spyOn(globalThis, "fetch").mockImplementation(async (_url, init) => { capturedBody = init?.body as string; return new Response("", { status: 200 }); });
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    await submitToBing(["https://example.com/1", "https://example.com/2", "https://example.com/3"]);
    const body = JSON.parse(capturedBody!);
    expect(body.urlList).toHaveLength(2);
  });

  it("should batch submissions in groups of 500", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("", { status: 200 }));
    const { submitToBing } = await import("@/lib/services/bing-indexer");
    const urls = Array.from({ length: 501 }, (_, i) => "https://example.com/page" + i);
    await submitToBing(urls);
    expect(fetchSpy).toHaveBeenCalledTimes(2);
  });
});
