import { describe, it, expect } from "vitest";
import { computeFingerprint } from "@/lib/services/schema-validator/sdtt-validator";
import type { ValidationIssue } from "@/lib/services/schema-validator/types";

function err(code: string): ValidationIssue {
  return { code, message: code, schemaType: "Product", severity: "error" };
}

describe("computeFingerprint", () => {
  it("is stable across error ordering", () => {
    const a = computeFingerprint("/aircraft/x", "Product", [err("a"), err("b"), err("c")]);
    const b = computeFingerprint("/aircraft/x", "Product", [err("c"), err("a"), err("b")]);
    expect(a).toBe(b);
  });

  it("differs when canonical url differs", () => {
    const a = computeFingerprint("/aircraft/x", "Product", [err("a")]);
    const b = computeFingerprint("/aircraft/y", "Product", [err("a")]);
    expect(a).not.toBe(b);
  });

  it("differs when schema type differs", () => {
    const a = computeFingerprint("/aircraft/x", "Product", [err("a")]);
    const b = computeFingerprint("/aircraft/x", "JobPosting", [err("a")]);
    expect(a).not.toBe(b);
  });

  it("differs when error set differs", () => {
    const a = computeFingerprint("/aircraft/x", "Product", [err("a")]);
    const b = computeFingerprint("/aircraft/x", "Product", [err("a"), err("b")]);
    expect(a).not.toBe(b);
  });
});
