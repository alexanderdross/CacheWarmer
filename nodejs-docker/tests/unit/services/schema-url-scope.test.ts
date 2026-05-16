import { describe, it, expect } from "vitest";
import {
  isInSchemaScope,
  classifyPageType,
  canonicalizeForFingerprint,
} from "@/lib/services/schema-validator/url-scope";

describe("url-scope", () => {
  describe("isInSchemaScope", () => {
    it("matches aircraft detail at any locale", () => {
      expect(isInSchemaScope("https://trade.aero/aircraft/cessna-c172-101")).toBe(true);
      expect(isInSchemaScope("https://trade.aero/de/aircraft/cessna-c172-101")).toBe(true);
      expect(isInSchemaScope("/fr/aircraft/abc")).toBe(true);
    });

    it("matches job, event, parts-listing, parts-wanted detail", () => {
      expect(isInSchemaScope("https://trade.aero/jobs/some-slug")).toBe(true);
      expect(isInSchemaScope("https://trade.aero/events/some-slug")).toBe(true);
      expect(isInSchemaScope("https://trade.aero/parts/listing/123")).toBe(true);
      expect(isInSchemaScope("https://trade.aero/parts/wanted/456")).toBe(true);
    });

    it("rejects index pages and unrelated routes", () => {
      expect(isInSchemaScope("https://trade.aero/aircraft")).toBe(false);
      expect(isInSchemaScope("https://trade.aero/jobs")).toBe(false);
      expect(isInSchemaScope("https://trade.aero/parts/listing")).toBe(false);
      expect(isInSchemaScope("https://trade.aero/dashboard/admin")).toBe(false);
      expect(isInSchemaScope("https://trade.aero/")).toBe(false);
    });

    it("does not match locale-only prefix without resource", () => {
      expect(isInSchemaScope("https://trade.aero/de/aircraft")).toBe(false);
    });
  });

  describe("classifyPageType", () => {
    it("returns the right page type discriminator", () => {
      expect(classifyPageType("/aircraft/x")).toBe("aircraft");
      expect(classifyPageType("/de/jobs/x")).toBe("job");
      expect(classifyPageType("/fr/events/x")).toBe("event");
      expect(classifyPageType("/parts/listing/x")).toBe("parts-listing");
      expect(classifyPageType("/de/parts/wanted/x")).toBe("parts-wanted");
      expect(classifyPageType("/about")).toBeNull();
    });
  });

  describe("canonicalizeForFingerprint", () => {
    it("strips locale prefix so /de and /en collapse", () => {
      expect(canonicalizeForFingerprint("https://trade.aero/de/jobs/some-slug")).toBe(
        canonicalizeForFingerprint("https://trade.aero/jobs/some-slug")
      );
      expect(canonicalizeForFingerprint("/fr/aircraft/abc")).toBe("/aircraft/abc");
    });

    it("strips trailing slashes", () => {
      expect(canonicalizeForFingerprint("/aircraft/x/")).toBe("/aircraft/x");
    });
  });
});
