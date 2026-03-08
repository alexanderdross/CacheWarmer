import { describe, it, expect, vi, beforeEach } from "vitest";
import { createMockConfig, setTestConfig, resetTestConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

describe("IndexNow Service", () => {
  beforeEach(() => {
    resetTestConfig();
    vi.spyOn(globalThis, "fetch").mockReset();
  });

  it("should submit URLs to IndexNow API", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("", { status: 200 }));
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const result = await submitIndexNow(["https://example.com/page1", "https://example.com/page2"]);
    expect(result.status).toBe("success");
    expect(result.urlCount).toBe(2);
    expect(fetchSpy).toHaveBeenCalledWith("https://api.indexnow.org/indexnow", expect.objectContaining({ method: "POST" }));
  });

  it("should send correct payload structure", async () => {
    let capturedBody: string | undefined;
    vi.spyOn(globalThis, "fetch").mockImplementation(async (_url, init) => { capturedBody = init?.body as string; return new Response("", { status: 200 }); });
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    await submitIndexNow(["https://example.com/page1"]);
    const body = JSON.parse(capturedBody!);
    expect(body.host).toBe("example.com");
    expect(body.key).toBe("test-indexnow-key");
    expect(body.urlList).toEqual(["https://example.com/page1"]);
  });

  it("should accept 202 as success", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("", { status: 202 }));
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const result = await submitIndexNow(["https://example.com/page1"]);
    expect(result.status).toBe("success");
  });

  it("should handle API errors", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("Bad Request", { status: 400 }));
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const result = await submitIndexNow(["https://example.com/page1"]);
    expect(result.status).toBe("failed");
    expect(result.error).toContain("IndexNow API error: 400");
  });

  it("should handle network errors", async () => {
    vi.spyOn(globalThis, "fetch").mockRejectedValue(new Error("Connection refused"));
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const result = await submitIndexNow(["https://example.com/page1"]);
    expect(result.status).toBe("failed");
    expect(result.error).toBe("Connection refused");
  });

  it("should skip when disabled", async () => {
    const config = createMockConfig(); config.indexNow.enabled = false; setTestConfig(config);
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const result = await submitIndexNow(["https://example.com/page1"]);
    expect(result.status).toBe("skipped");
  });

  it("should skip when key is not configured", async () => {
    const config = createMockConfig(); config.indexNow.key = ""; setTestConfig(config);
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const result = await submitIndexNow(["https://example.com/page1"]);
    expect(result.status).toBe("skipped");
  });

  it("should batch URLs in groups of 10000", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("", { status: 200 }));
    const { submitIndexNow } = await import("@/lib/services/indexnow");
    const urls = Array.from({ length: 10001 }, (_, i) => "https://example.com/page" + i);
    const result = await submitIndexNow(urls);
    expect(result.status).toBe("success");
    expect(fetchSpy).toHaveBeenCalledTimes(2);
  });
});
