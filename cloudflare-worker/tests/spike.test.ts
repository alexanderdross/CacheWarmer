import { describe, expect, it } from "vitest";
import { bust } from "../src/spike";

describe("bust", () => {
  it("gives each measurement its own cache key", () => {
    // Without this the first measurement warms the cache the second is meant
    // to find cold, and the spike reports already_warm as a false positive.
    expect(bust("https://example.com/", "a")).not.toBe(bust("https://example.com/", "b"));
  });

  it("keeps the path and any query the page already had", () => {
    const busted = new URL(bust("https://example.com/blog?page=2", "run1"));
    expect(busted.pathname).toBe("/blog");
    expect(busted.searchParams.get("page")).toBe("2");
    expect(busted.searchParams.get("cw-spike")).toBe("run1");
  });

  it("replaces its own marker rather than stacking them", () => {
    const once = bust("https://example.com/", "first");
    const twice = new URL(bust(once, "second"));
    expect(twice.searchParams.getAll("cw-spike")).toEqual(["second"]);
  });
});
