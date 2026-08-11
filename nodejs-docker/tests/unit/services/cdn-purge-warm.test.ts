import { describe, it, expect, vi } from "vitest";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

import { generateEdgeGridAuth } from "@/lib/services/cdn-purge-warm";

/**
 * Akamai's EG1-HMAC-SHA256 scheme is unforgiving: the timestamp is both a
 * header field and the key the signature is derived from, so a malformed one
 * fails authentication rather than producing a clear error.
 */
describe("generateEdgeGridAuth", () => {
  const auth = () =>
    generateEdgeGridAuth(
      "POST",
      "https://akaa-example.luna.akamaiapis.net/ccu/v3/invalidate/url/production",
      JSON.stringify({ objects: ["https://example.com/"] }),
      "akab-client-token",
      "client-secret-value=",
      "akab-access-token",
    );

  it("formats the timestamp as yyyyMMddTHH:mm:ss+0000", () => {
    const timestamp = /timestamp=([^;]+);/.exec(auth())?.[1];
    expect(timestamp).toBeDefined();
    // Date loses its hyphens, time KEEPS its colons. Stripping both — as this
    // did before — produces 20260811T094500+0000, which Akamai rejects.
    expect(timestamp).toMatch(/^\d{8}T\d{2}:\d{2}:\d{2}\+0000$/);
  });

  it("keeps the timestamp parseable back to the current time", () => {
    const timestamp = /timestamp=([^;]+);/.exec(auth())?.[1] ?? "";
    const [date, time] = timestamp.replace("+0000", "").split("T");
    const iso = `${date.slice(0, 4)}-${date.slice(4, 6)}-${date.slice(6, 8)}T${time}Z`;
    const parsed = Date.parse(iso);
    expect(Number.isNaN(parsed)).toBe(false);
    expect(Math.abs(Date.now() - parsed)).toBeLessThan(60_000);
  });

  it("emits every field the scheme requires", () => {
    const header = auth();
    expect(header).toMatch(/^EG1-HMAC-SHA256 /);
    expect(header).toContain("client_token=akab-client-token;");
    expect(header).toContain("access_token=akab-access-token;");
    expect(header).toMatch(/nonce=[0-9a-f-]{36};/);
    expect(header).toMatch(/signature=[A-Za-z0-9+/]+=*$/);
  });

  it("produces a different signature for a different request body", () => {
    const a = generateEdgeGridAuth(
      "POST",
      "https://host.example/ccu/v3/invalidate/url/production",
      JSON.stringify({ objects: ["https://example.com/a"] }),
      "ct",
      "cs",
      "at",
    );
    const b = generateEdgeGridAuth(
      "POST",
      "https://host.example/ccu/v3/invalidate/url/production",
      JSON.stringify({ objects: ["https://example.com/b"] }),
      "ct",
      "cs",
      "at",
    );
    const sigA = /signature=(.+)$/.exec(a)?.[1];
    const sigB = /signature=(.+)$/.exec(b)?.[1];
    expect(sigA).toBeDefined();
    expect(sigA).not.toBe(sigB);
  });
});
