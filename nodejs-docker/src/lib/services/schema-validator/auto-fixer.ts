import type { ValidationIssue } from "./types";

export type AutoFixRule =
  | "missing-context"
  | "http-to-https-context"
  | "type-literal-case"
  | "missing-organization-name";

export interface AutoFixPatch {
  filePath: string;
  rule: AutoFixRule;
  oldContent: string;
  newContent: string;
  summary: string;
}

const CANONICAL_TYPES = ["Product", "JobPosting", "Event", "Organization", "Offer", "BreadcrumbList", "WebPage"];

export function detectFixableRule(issue: ValidationIssue): AutoFixRule | null {
  const code = issue.code.toLowerCase();
  const msg = issue.message.toLowerCase();

  if (code.includes("context") && msg.includes("missing")) return "missing-context";
  if (msg.includes("http://schema.org")) return "http-to-https-context";
  if (code.includes("type") && (msg.includes("misspell") || msg.includes("case"))) return "type-literal-case";
  if (code.includes("organization") && msg.includes("name") && msg.includes("missing")) return "missing-organization-name";

  return null;
}

export function generatePatch(
  filePath: string,
  source: string,
  rule: AutoFixRule,
  context?: { siteTitle?: string }
): AutoFixPatch | null {
  switch (rule) {
    case "http-to-https-context": {
      const next = source.replace(/http:\/\/schema\.org/g, "https://schema.org");
      if (next === source) return null;
      return {
        filePath,
        rule,
        oldContent: source,
        newContent: next,
        summary: "Rewrote @context http://schema.org → https://schema.org",
      };
    }
    case "missing-context": {
      const next = source.replace(
        /("@type"\s*:\s*"([^"]+)"\s*,?)/,
        '"@context": "https://schema.org",\n  $1'
      );
      if (next === source) return null;
      return {
        filePath,
        rule,
        oldContent: source,
        newContent: next,
        summary: "Inserted missing @context: https://schema.org",
      };
    }
    case "type-literal-case": {
      let next = source;
      for (const t of CANONICAL_TYPES) {
        const re = new RegExp(`"@type"\\s*:\\s*"(${t})"`, "gi");
        next = next.replace(re, (_m, captured) => (captured === t ? _m : `"@type": "${t}"`));
      }
      if (next === source) return null;
      return {
        filePath,
        rule,
        oldContent: source,
        newContent: next,
        summary: "Normalised @type literal casing against Schema.org canonical names",
      };
    }
    case "missing-organization-name": {
      const title = context?.siteTitle?.trim();
      if (!title) return null;
      const next = source.replace(
        /("@type"\s*:\s*"Organization"\s*,?)(?![^{}]*"name")/,
        `$1\n      "name": ${JSON.stringify(title)},`
      );
      if (next === source) return null;
      return {
        filePath,
        rule,
        oldContent: source,
        newContent: next,
        summary: `Injected Organization.name = ${JSON.stringify(title)}`,
      };
    }
  }
}
