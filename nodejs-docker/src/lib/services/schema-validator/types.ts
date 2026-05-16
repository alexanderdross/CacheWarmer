import type { PageType } from "./url-scope";

export type ValidationStatus = "pass" | "warn" | "fail";
export type Severity = "error" | "warning";

export interface ValidationIssue {
  code: string;
  message: string;
  schemaType: string;
  pointer?: string;
  severity: Severity;
}

export interface ValidationReport {
  url: string;
  canonicalUrl: string;
  pageType: PageType;
  schemaType: string;
  status: ValidationStatus;
  errors: ValidationIssue[];
  warnings: ValidationIssue[];
  fingerprint: string;
}

export interface SchemaValidator {
  validate(html: string, url: string): Promise<ValidationReport[]>;
}

export interface ValidationJobItem {
  jobId: string;
  url: string;
  html: string;
}
