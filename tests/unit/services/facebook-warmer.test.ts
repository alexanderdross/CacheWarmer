import { describe, it, expect, vi, beforeEach } from "vitest";
import { createMockConfig, setTestConfig, resetTestConfig } from "../../helpers";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

describe("Facebook Warmer", () => {
  beforeEach(() => {
    resetTestConfig();
    vi.spyOn(globalThis, "fetch").mockReset();
  });

  it("should successfully warm URLs via Facebook Graph API", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response(JSON.stringify({ id: "test" }), { status: 200 }));
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const results = await warmFacebook(["https://example.com/page1", "https://example.com/page2"]);
    expect(results).toHaveLength(2);
    expect(results[0].status).toBe("success");
    expect(results[1].status).toBe("success");
  });

  it("should call correct Facebook Graph API endpoint", async () => {
    const fetchSpy = vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response(JSON.stringify({ id: "test" }), { status: 200 }));
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    await warmFacebook(["https://example.com/page1"]);
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining("graph.facebook.com/v19.0/?scrape=true"), expect.objectContaining({ method: "POST" }));
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining("access_token=test-app-id|test-app-secret"), expect.any(Object));
  });

  it("should handle API errors gracefully", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response("Rate limited", { status: 429 }));
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const results = await warmFacebook(["https://example.com/page1"]);
    expect(results[0].status).toBe("failed");
    expect(results[0].error).toContain("429");
  });

  it("should handle network errors gracefully", async () => {
    vi.spyOn(globalThis, "fetch").mockRejectedValue(new Error("Network error"));
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const results = await warmFacebook(["https://example.com/page1"]);
    expect(results[0].status).toBe("failed");
    expect(results[0].error).toBe("Network error");
  });

  it("should skip when disabled in config", async () => {
    const config = createMockConfig(); config.facebook.enabled = false; setTestConfig(config);
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const results = await warmFacebook(["https://example.com/page1"]);
    expect(results[0].status).toBe("skipped");
  });

  it("should skip when credentials are not configured", async () => {
    const config = createMockConfig(); config.facebook.appId = ""; config.facebook.appSecret = ""; setTestConfig(config);
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const results = await warmFacebook(["https://example.com/page1"]);
    expect(results[0].status).toBe("skipped");
  });

  it("should call onProgress callback for each URL", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response(JSON.stringify({ id: "test" }), { status: 200 }));
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const onProgress = vi.fn();
    await warmFacebook(["https://example.com/page1", "https://example.com/page2"], onProgress);
    expect(onProgress).toHaveBeenCalledTimes(2);
  });

  it("should measure duration in milliseconds", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(new Response(JSON.stringify({ id: "test" }), { status: 200 }));
    const { warmFacebook } = await import("@/lib/services/facebook-warmer");
    const results = await warmFacebook(["https://example.com/page1"]);
    expect(results[0].durationMs).toBeGreaterThanOrEqual(0);
  });
});
