import { describe, it, expect, vi, beforeEach } from "vitest";
import { NextRequest } from "next/server";
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

describe("Authentication Module", () => {
  beforeEach(() => {
    resetTestConfig();
  });

  it("should return null (allow) when valid Bearer token is provided", async () => {
    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      headers: { Authorization: "Bearer test-api-key-12345" },
    });

    const result = authenticateRequest(request);
    expect(result).toBeNull();
  });

  it("should return 401 when no Authorization header is present", async () => {
    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm");

    const result = authenticateRequest(request);
    expect(result).not.toBeNull();

    const body = await result!.json();
    expect(result!.status).toBe(401);
    expect(body.error).toContain("Missing");
  });

  it("should return 401 when Authorization header has wrong format", async () => {
    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      headers: { Authorization: "Basic dGVzdDp0ZXN0" },
    });

    const result = authenticateRequest(request);
    expect(result).not.toBeNull();
    expect(result!.status).toBe(401);
  });

  it("should return 403 when token is invalid", async () => {
    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm", {
      headers: { Authorization: "Bearer wrong-key" },
    });

    const result = authenticateRequest(request);
    expect(result).not.toBeNull();

    const body = await result!.json();
    expect(result!.status).toBe(403);
    expect(body.error).toContain("Invalid");
  });

  it("should allow all requests when apiKey is the default 'change-me-in-production'", async () => {
    const config = createMockConfig();
    config.server.apiKey = "change-me-in-production";
    setTestConfig(config);

    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm");
    const result = authenticateRequest(request);
    expect(result).toBeNull(); // Allowed
  });

  it("should allow all requests when apiKey is empty", async () => {
    const config = createMockConfig();
    config.server.apiKey = "";
    setTestConfig(config);

    const { authenticateRequest } = await import("@/lib/auth");

    const request = new NextRequest("http://localhost:3000/api/warm");
    const result = authenticateRequest(request);
    expect(result).toBeNull();
  });
});
