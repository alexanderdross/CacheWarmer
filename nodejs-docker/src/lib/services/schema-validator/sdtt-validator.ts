import { createHash } from "crypto";
import { extractJsonLd } from "./extractor";
import { canonicalizeForFingerprint, classifyPageType } from "./url-scope";
import type { SchemaValidator, ValidationIssue, ValidationReport, ValidationStatus } from "./types";

// SDTT has no published types; load it dynamically and type the bits we use.
interface SdttTestResult {
  passed: boolean;
  optional?: boolean;
  type?: string;
  test: string;
  description?: string;
  error?: { type?: string; message: string };
}

interface SdttRunResult {
  passed: SdttTestResult[];
  failed: SdttTestResult[];
  warnings: SdttTestResult[];
}

type SdttFn = (input: string, options?: { presets?: unknown[] }) => Promise<SdttRunResult>;

interface SdttModule {
  structuredDataTest: SdttFn;
  presets?: Record<string, unknown>;
}

let sdttCache: SdttModule | null = null;
async function loadSdtt(): Promise<SdttModule> {
  if (sdttCache) return sdttCache;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const mod = (await import("structured-data-testing-tool")) as any;
  sdttCache = (mod.default ?? mod) as SdttModule;
  return sdttCache;
}

function toIssue(t: SdttTestResult, severity: "error" | "warning"): ValidationIssue {
  return {
    code: t.test,
    message: t.error?.message || t.description || t.test,
    schemaType: t.type || "Unknown",
    severity,
  };
}

function deriveSchemaType(failed: SdttTestResult[], warnings: SdttTestResult[], passed: SdttTestResult[]): string {
  const all = [...failed, ...warnings, ...passed];
  const typed = all.find((t) => t.type && t.type !== "Any");
  return typed?.type || "Unknown";
}

function computeStatus(errors: ValidationIssue[], warnings: ValidationIssue[]): ValidationStatus {
  if (errors.length > 0) return "fail";
  if (warnings.length > 0) return "warn";
  return "pass";
}

export function computeFingerprint(canonicalUrl: string, schemaType: string, errors: ValidationIssue[]): string {
  const codes = errors.map((e) => e.code).sort().join(",");
  return createHash("sha256").update(`${canonicalUrl}|${schemaType}|${codes}`).digest("hex").slice(0, 16);
}

export class SdttSchemaValidator implements SchemaValidator {
  constructor(private presets: string[] = ["Google", "SchemaOrg"]) {}

  async validate(html: string, url: string): Promise<ValidationReport[]> {
    const pageType = classifyPageType(url);
    if (!pageType) return [];

    const canonicalUrl = canonicalizeForFingerprint(url);
    const blocks = extractJsonLd(html);

    if (blocks.length === 0) {
      const errors: ValidationIssue[] = [
        {
          code: "no-jsonld-blocks",
          message: "Page has no <script type=\"application/ld+json\"> blocks.",
          schemaType: "Unknown",
          severity: "error",
        },
      ];
      return [
        {
          url,
          canonicalUrl,
          pageType,
          schemaType: "Unknown",
          status: "fail",
          errors,
          warnings: [],
          fingerprint: computeFingerprint(canonicalUrl, "Unknown", errors),
        },
      ];
    }

    const sdtt = await loadSdtt();
    const presetObjects = (sdtt.presets && this.presets.map((p) => sdtt.presets![p]).filter(Boolean)) || [];

    const result: SdttRunResult = await sdtt.structuredDataTest(html, { presets: presetObjects });

    const errors = result.failed.filter((t) => !t.optional).map((t) => toIssue(t, "error"));
    const warnings = [
      ...result.warnings.map((t) => toIssue(t, "warning")),
      ...result.failed.filter((t) => t.optional).map((t) => toIssue(t, "warning")),
    ];
    const schemaType = deriveSchemaType(result.failed, result.warnings, result.passed);

    return [
      {
        url,
        canonicalUrl,
        pageType,
        schemaType,
        status: computeStatus(errors, warnings),
        errors,
        warnings,
        fingerprint: computeFingerprint(canonicalUrl, schemaType, errors),
      },
    ];
  }
}
