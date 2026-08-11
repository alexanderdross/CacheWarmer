import { describe, expect, it } from "vitest";
import {
  classify,
  coloFromRay,
  isSuccess,
  normaliseCacheState,
  type RequestObservation,
} from "../src/verdict";

function observation(overrides: Partial<RequestObservation> = {}): RequestObservation {
  return {
    ok: true,
    httpStatus: 200,
    colo: "FRA",
    state: "miss",
    headers: {},
    durationMs: 10,
    ...overrides,
  };
}

describe("coloFromRay", () => {
  it("extracts the data centre from a cf-ray header", () => {
    expect(coloFromRay("9a1b2c3d4e5f6789-FRA")).toBe("FRA");
  });

  it("uppercases lowercase codes", () => {
    expect(coloFromRay("9a1b2c3d4e5f6789-ams")).toBe("AMS");
  });

  it("returns undefined when the header is missing or malformed", () => {
    expect(coloFromRay(null)).toBeUndefined();
    expect(coloFromRay("no-dash-suffix-here")).toBeUndefined();
    expect(coloFromRay("9a1b2c3d4e5f6789-")).toBeUndefined();
    expect(coloFromRay("9a1b2c3d4e5f6789-FRANKFURT")).toBeUndefined();
  });
});

describe("normaliseCacheState", () => {
  it("reads Cloudflare's cf-cache-status", () => {
    expect(normaliseCacheState({ cfCacheStatus: "HIT" })).toBe("hit");
    expect(normaliseCacheState({ cfCacheStatus: "miss" })).toBe("miss");
    expect(normaliseCacheState({ cfCacheStatus: "DYNAMIC" })).toBe("dynamic");
    expect(normaliseCacheState({ cfCacheStatus: "BYPASS" })).toBe("bypass");
  });

  it("reads Akamai's TCP_ prefixed x-cache values", () => {
    expect(normaliseCacheState({ xCache: "TCP_HIT" })).toBe("hit");
    expect(normaliseCacheState({ xCache: "TCP_MEM_HIT" })).toBe("hit");
    expect(normaliseCacheState({ xCache: "TCP_MISS" })).toBe("miss");
    expect(normaliseCacheState({ xCache: "TCP_REFRESH_HIT" })).toBe("revalidated");
  });

  it("takes the edge-facing value from Fastly's two-tier x-cache", () => {
    expect(normaliseCacheState({ xCache: "MISS, HIT" })).toBe("hit");
    expect(normaliseCacheState({ xCache: "HIT, MISS" })).toBe("miss");
  });

  it("reads CloudFront's prose x-cache", () => {
    expect(normaliseCacheState({ xCache: "Hit from cloudfront" })).toBe("hit");
    expect(normaliseCacheState({ xCache: "Miss from cloudfront" })).toBe("miss");
  });

  it("treats REVALIDATED, STALE and UPDATING as served from cache", () => {
    // The PHP editions once let these fall through to unknown, reporting
    // genuinely cached pages as unverified.
    expect(normaliseCacheState({ cfCacheStatus: "REVALIDATED" })).toBe("revalidated");
    expect(normaliseCacheState({ cfCacheStatus: "STALE" })).toBe("stale");
    expect(normaliseCacheState({ cfCacheStatus: "UPDATING" })).toBe("updating");
  });

  it("prefers cf-cache-status when both headers are present", () => {
    expect(normaliseCacheState({ cfCacheStatus: "MISS", xCache: "TCP_HIT" })).toBe("miss");
  });

  it("infers a hit from a non-zero Age when no cache header exists", () => {
    expect(normaliseCacheState({ age: "42" })).toBe("hit");
    expect(normaliseCacheState({ age: "0" })).toBe("unknown");
    expect(normaliseCacheState({})).toBe("unknown");
  });
});

describe("classify", () => {
  it("reports 'warmed' when a miss is followed by a hit", () => {
    const result = classify(observation({ state: "miss" }), observation({ state: "hit" }));
    expect(result.verdict).toBe("warmed");
    expect(isSuccess(result.verdict)).toBe(true);
  });

  it("reports 'already_warm' when the cache was hot before we arrived", () => {
    const result = classify(observation({ state: "hit" }), observation({ state: "hit" }));
    expect(result.verdict).toBe("already_warm");
    expect(isSuccess(result.verdict)).toBe(true);
  });

  it("treats an expired fill followed by a hit as warmed", () => {
    expect(classify(observation({ state: "expired" }), observation({ state: "hit" })).verdict).toBe(
      "warmed",
    );
  });

  it("reports 'not_cacheable' when two requests both miss", () => {
    const result = classify(observation({ state: "miss" }), observation({ state: "miss" }));
    expect(result.verdict).toBe("not_cacheable");
    expect(isSuccess(result.verdict)).toBe(false);
  });

  it("names the reason a page refuses to cache", () => {
    const probe = observation({ state: "miss", headers: { cacheControl: "private, no-store" } });
    expect(classify(observation({ state: "miss" }), probe).reason).toBe("Cache-Control: no-store");

    const cookied = observation({ state: "miss", headers: { setCookie: true } });
    expect(classify(observation({ state: "miss" }), cookied).reason).toBe(
      "Response sets a cookie",
    );
  });

  it("reports 'indeterminate' when fill and probe hit different data centres", () => {
    const result = classify(
      observation({ state: "miss", colo: "FRA" }),
      observation({ state: "miss", colo: "AMS" }),
    );
    expect(result.verdict).toBe("indeterminate");
    expect(result.reason).toContain("FRA");
    expect(result.reason).toContain("AMS");
  });

  it("does not claim success when a differing colo produced the hit", () => {
    const result = classify(
      observation({ state: "miss", colo: "FRA" }),
      observation({ state: "hit", colo: "AMS" }),
    );
    expect(result.verdict).toBe("indeterminate");
  });

  it("reports 'bypassed' when the cache was explicitly skipped", () => {
    expect(classify(observation({ state: "bypass" }), observation()).verdict).toBe("bypassed");
  });

  it("reports 'zone_not_caching' for DYNAMIC responses", () => {
    const result = classify(observation({ state: "miss" }), observation({ state: "dynamic" }));
    expect(result.verdict).toBe("zone_not_caching");
  });

  it("reports 'failed' when either request errored", () => {
    expect(classify(observation({ ok: false, error: "timeout" }), observation()).verdict).toBe(
      "failed",
    );
    expect(classify(observation(), observation({ ok: false, httpStatus: 502 })).verdict).toBe(
      "failed",
    );
  });

  it("tolerates a missing colo rather than calling it indeterminate", () => {
    const result = classify(
      observation({ state: "miss", colo: undefined }),
      observation({ state: "hit", colo: undefined }),
    );
    expect(result.verdict).toBe("warmed");
  });
});
