import { describe, expect, it } from "vitest";
import {
  PURGE_BATCH_SIZE,
  PURGE_BATCH_SIZE_ENTERPRISE,
  purgeBatchSize,
  purgeIntervalMs,
} from "../src/purge";

describe("purgeBatchSize", () => {
  it("uses Cloudflare's documented maximum of 100 per request", () => {
    // The Node module hardcodes 30, costing 3.3x the API calls for no reason.
    expect(PURGE_BATCH_SIZE).toBe(100);
    expect(purgeBatchSize("free")).toBe(100);
    expect(purgeBatchSize("business")).toBe(100);
    expect(purgeBatchSize(undefined)).toBe(100);
  });

  it("uses 500 on Enterprise", () => {
    expect(purgeBatchSize("enterprise")).toBe(PURGE_BATCH_SIZE_ENTERPRISE);
  });
});

describe("purgeIntervalMs", () => {
  it("paces Free at 5 requests per minute", () => {
    expect(purgeIntervalMs("free")).toBe(12_000);
  });

  it("paces the paid plans at their per-second rates", () => {
    expect(purgeIntervalMs("pro")).toBe(200);
    expect(purgeIntervalMs("business")).toBe(100);
    expect(purgeIntervalMs("enterprise")).toBe(20);
  });

  it("falls back to the Free rate for an unknown plan", () => {
    // Rate limits are per account, so guessing high would trip a 429 for every
    // zone on that account, not just this one.
    expect(purgeIntervalMs(undefined)).toBe(12_000);
    expect(purgeIntervalMs("nonsense")).toBe(12_000);
  });
});
