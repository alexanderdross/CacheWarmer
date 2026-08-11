import { describe, expect, it } from "vitest";
import { changedSince, chunk, sortByPriority, type SitemapEntry } from "../src/sitemap";
import { maxUrlsPerInvocation, SUBREQUESTS_PER_URL } from "../src/warm";
import { FREE_PLAN_SUBREQUEST_BUDGET, URLS_PER_CHUNK } from "../src/config";

const entry = (loc: string, priority?: number, lastmod?: string): SitemapEntry => ({
  loc,
  priority,
  lastmod,
});

describe("sortByPriority", () => {
  it("puts the highest priority first", () => {
    const sorted = sortByPriority([entry("/c", 0.1), entry("/a", 0.9), entry("/b", 0.5)]);
    expect(sorted.map((e) => e.loc)).toEqual(["/a", "/b", "/c"]);
  });

  it("defaults a missing priority to 0.5", () => {
    const sorted = sortByPriority([entry("/low", 0.2), entry("/none"), entry("/high", 0.8)]);
    expect(sorted.map((e) => e.loc)).toEqual(["/high", "/none", "/low"]);
  });

  it("returns a new array so the sort cannot be discarded by accident", () => {
    // The Node module sorts a copy that is never read, which silently disables
    // priority warming. Returning a new array makes that mistake impossible.
    const input = [entry("/c", 0.1), entry("/a", 0.9)];
    const sorted = sortByPriority(input);
    expect(sorted).not.toBe(input);
    expect(input.map((e) => e.loc)).toEqual(["/c", "/a"]);
  });
});

describe("priority ordering under a run limit", () => {
  it("keeps high-priority pages that appear late in the document", () => {
    // Slicing during the fetch would cut in document order, dropping the
    // important page purely because it is listed last.
    const entries = [
      entry("/low-a", 0.1),
      entry("/low-b", 0.1),
      entry("/important", 0.9),
    ];
    const kept = sortByPriority(entries).slice(0, 2).map((e) => e.loc);
    expect(kept).toContain("/important");
  });
});

describe("changedSince", () => {
  it("keeps only entries whose lastmod moved", () => {
    const previous = new Map([
      ["/a", "2026-01-01"],
      ["/b", "2026-01-01"],
    ]);
    const changed = changedSince(
      [entry("/a", 0.5, "2026-01-01"), entry("/b", 0.5, "2026-08-01")],
      previous,
    );
    expect(changed.map((e) => e.loc)).toEqual(["/b"]);
  });

  it("keeps entries with no lastmod, since they may have changed", () => {
    expect(changedSince([entry("/a")], new Map()).map((e) => e.loc)).toEqual(["/a"]);
  });

  it("keeps entries it has never seen", () => {
    const previous = new Map([["/known", "2026-01-01"]]);
    expect(changedSince([entry("/new", 0.5, "2026-01-01")], previous)).toHaveLength(1);
  });
});

describe("chunk", () => {
  it("splits into fixed-size groups with a short tail", () => {
    expect(chunk([1, 2, 3, 4, 5], 2)).toEqual([[1, 2], [3, 4], [5]]);
  });

  it("returns nothing for an empty input", () => {
    expect(chunk([], 25)).toEqual([]);
  });

  it("rejects a chunk size below 1, which would loop forever", () => {
    expect(() => chunk([1], 0)).toThrow(/at least 1/);
  });
});

describe("free-plan budget", () => {
  it("fits the chunk size inside the 50-subrequest cap", () => {
    // Two subrequests per URL: one fill, one probe.
    expect(maxUrlsPerInvocation(FREE_PLAN_SUBREQUEST_BUDGET)).toBe(25);
    expect(URLS_PER_CHUNK).toBeLessThanOrEqual(maxUrlsPerInvocation(FREE_PLAN_SUBREQUEST_BUDGET));
  });

  it("keeps a chunk's subrequests within budget", () => {
    expect(URLS_PER_CHUNK * SUBREQUESTS_PER_URL).toBeLessThanOrEqual(
      FREE_PLAN_SUBREQUEST_BUDGET,
    );
  });
});
