import { describe, it, expect, vi } from "vitest";

vi.mock("@/lib/config", async () => {
  const helpers = await import("../../helpers");
  return {
    getConfig: () => helpers.testConfig,
    loadConfig: () => helpers.testConfig,
  };
});

vi.mock("puppeteer-core", () => ({ default: { launch: vi.fn() } }));

import { classifyCacheVerdict, type CacheHeaders } from "@/lib/services/cdn-warmer";

const headers = (over: Partial<CacheHeaders> = {}): CacheHeaders => ({ ...over });

/**
 * Every URL already gets two passes (desktop, then mobile). Treating the first
 * as the fill and the second as the probe turns them into a verification with
 * no extra requests.
 */
describe("classifyCacheVerdict", () => {
  it("reports 'warmed' when the fill missed and the probe hit", () => {
    const { verdict } = classifyCacheVerdict(
      headers({ cfCacheStatus: "MISS" }),
      headers({ cfCacheStatus: "HIT" }),
    );
    expect(verdict).toBe("warmed");
  });

  it("treats EXPIRED on the fill as a miss that was then warmed", () => {
    expect(
      classifyCacheVerdict(headers({ cfCacheStatus: "EXPIRED" }), headers({ cfCacheStatus: "HIT" }))
        .verdict,
    ).toBe("warmed");
  });

  it("reports 'already_warm' when both passes hit", () => {
    expect(
      classifyCacheVerdict(headers({ cfCacheStatus: "HIT" }), headers({ cfCacheStatus: "HIT" }))
        .verdict,
    ).toBe("already_warm");
  });

  it("reports 'not_cacheable' when both passes miss", () => {
    const { verdict, reason } = classifyCacheVerdict(
      headers({ cfCacheStatus: "MISS" }),
      headers({ cfCacheStatus: "MISS" }),
    );
    expect(verdict).toBe("not_cacheable");
    expect(reason).toBeTruthy();
  });

  it("names Cache-Control as the reason when it forbids caching", () => {
    expect(
      classifyCacheVerdict(
        headers({ cfCacheStatus: "MISS" }),
        headers({ cfCacheStatus: "MISS", cacheControl: "no-store, max-age=0" }),
      ).reason,
    ).toBe("Cache-Control: no-store");

    expect(
      classifyCacheVerdict(
        headers({ cfCacheStatus: "MISS" }),
        headers({ cfCacheStatus: "MISS", cacheControl: "private" }),
      ).reason,
    ).toBe("Cache-Control: private");
  });

  it("reports 'bypassed' when either pass was bypassed", () => {
    expect(
      classifyCacheVerdict(headers({ cfCacheStatus: "BYPASS" }), headers({ cfCacheStatus: "MISS" }))
        .verdict,
    ).toBe("bypassed");
  });

  it("reports 'zone_not_caching' for DYNAMIC responses", () => {
    expect(
      classifyCacheVerdict(headers({ cfCacheStatus: "MISS" }), headers({ cfCacheStatus: "DYNAMIC" }))
        .verdict,
    ).toBe("zone_not_caching");
  });

  it("refuses to judge when the origin varies on User-Agent", () => {
    // The two passes send different user agents. If the origin varies on that
    // header they address separate cache entries, so a hit on the second says
    // nothing about the first.
    const { verdict, reason } = classifyCacheVerdict(
      headers({ cfCacheStatus: "MISS" }),
      headers({ cfCacheStatus: "HIT", vary: "Accept-Encoding, User-Agent" }),
      "Accept-Encoding, User-Agent",
    );
    expect(verdict).toBe("indeterminate");
    expect(reason).toContain("Vary");
  });

  it("still judges when Vary is present but harmless", () => {
    expect(
      classifyCacheVerdict(
        headers({ cfCacheStatus: "MISS" }),
        headers({ cfCacheStatus: "HIT", vary: "Accept-Encoding" }),
        "Accept-Encoding",
      ).verdict,
    ).toBe("warmed");
  });

  it("reads Akamai and Fastly style x-cache headers", () => {
    expect(
      classifyCacheVerdict(headers({ xCache: "TCP_MISS" }), headers({ xCache: "TCP_HIT" })).verdict,
    ).toBe("warmed");
  });

  it("infers a hit from a non-zero Age when no cache header is present", () => {
    expect(classifyCacheVerdict(headers({ age: "0" }), headers({ age: "30" })).verdict).toBe(
      "warmed",
    );
  });

  it("reports 'unknown' rather than guessing when there are no cache headers", () => {
    expect(classifyCacheVerdict(headers(), headers()).verdict).toBe("unknown");
  });
});
