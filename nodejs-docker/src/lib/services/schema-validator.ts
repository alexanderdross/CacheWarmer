import logger from "@/lib/logger";
import { loadConfig } from "@/lib/config";

export interface SchemaIssue {
  type: string;
  test: string;
  message: string;
  severity: "error" | "warning";
}

export interface SchemaValidationResult {
  url: string;
  status: "valid" | "warnings" | "errors" | "failed";
  schemas: string[];
  errors: SchemaIssue[];
  warnings: SchemaIssue[];
  durationMs: number;
  error?: string;
}

type ResultCallback = (result: SchemaValidationResult) => void;

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Validate markup in HTML that has already been retrieved.
 *
 * This is the path that matters: the CDN warmer loads every page anyway, so
 * handing that HTML over here removes a redundant round trip per URL. It also
 * avoids structuredDataTest(url), which fetches the page twice on its own.
 */
export async function validateSchemaHtml(
  url: string,
  html: string
): Promise<SchemaValidationResult> {
  const start = Date.now();

  try {
    const { structuredDataTestHtml } = await import("structured-data-testing-tool");

    const result = await structuredDataTestHtml(html, { url, presets: [] });

    const schemas = result.schemas || [];
    const schemaTypes = schemas.map((s: { schema: string }) => s.schema || String(s));

    const errors: SchemaIssue[] = (result.failed || []).map((t: { schema?: string; test?: string; description?: string; message?: string }) => ({
      type: t.schema || "Unknown",
      test: t.test || t.description || "Unknown test",
      message: t.message || t.description || "Validation failed",
      severity: "error" as const,
    }));

    const warnings: SchemaIssue[] = (result.warnings || []).map((t: { schema?: string; test?: string; description?: string; message?: string }) => ({
      type: t.schema || "Unknown",
      test: t.test || t.description || "Unknown test",
      message: t.message || t.description || "Warning",
      severity: "warning" as const,
    }));

    const status = errors.length > 0 ? "errors" : warnings.length > 0 ? "warnings" : "valid";

    return {
      url,
      status,
      schemas: schemaTypes,
      errors,
      warnings,
      durationMs: Date.now() - start,
    };
  } catch (err) {
    const errorMsg = err instanceof Error ? err.message : String(err);

    // The library throws when there are test failures - parse the error
    if (err && typeof err === "object" && "failed" in err) {
      const testResult = err as {
        schemas?: Array<{ schema: string }>;
        passed?: Array<{ schema?: string; test?: string; description?: string; message?: string }>;
        failed?: Array<{ schema?: string; test?: string; description?: string; message?: string }>;
        warnings?: Array<{ schema?: string; test?: string; description?: string; message?: string }>;
      };

      const schemas = (testResult.schemas || []).map((s) => s.schema || String(s));

      const errors: SchemaIssue[] = (testResult.failed || []).map((t) => ({
        type: t.schema || "Unknown",
        test: t.test || t.description || "Unknown test",
        message: t.message || t.description || "Validation failed",
        severity: "error" as const,
      }));

      const warnings: SchemaIssue[] = (testResult.warnings || []).map((t) => ({
        type: t.schema || "Unknown",
        test: t.test || t.description || "Unknown test",
        message: t.message || t.description || "Warning",
        severity: "warning" as const,
      }));

      return {
        url,
        status: errors.length > 0 ? "errors" : "warnings",
        schemas,
        errors,
        warnings,
        durationMs: Date.now() - start,
      };
    }

    logger.warn({ url, error: errorMsg }, "Schema validation failed for URL");
    return {
      url,
      status: "failed",
      schemas: [],
      errors: [],
      warnings: [],
      durationMs: Date.now() - start,
      error: errorMsg,
    };
  }
}

/**
 * Fetch a page once and validate it.
 *
 * Used only when schema validation runs without CDN warming; when both are
 * active the warmer supplies the HTML and no fetch happens here at all.
 */
async function validateSingleUrl(url: string): Promise<SchemaValidationResult> {
  const config = loadConfig();
  const timeout = config.schemaValidation?.timeout || 15000;
  const start = Date.now();

  try {
    const response = await fetch(url, {
      signal: AbortSignal.timeout(timeout),
      headers: { "user-agent": "Mozilla/5.0 (compatible; CacheWarmer/1.0)" },
    });

    if (!response.ok) {
      return {
        url,
        status: "failed",
        schemas: [],
        errors: [],
        warnings: [],
        durationMs: Date.now() - start,
        error: `HTTP ${response.status}`,
      };
    }

    const html = await response.text();
    if (!html) {
      return {
        url,
        status: "failed",
        schemas: [],
        errors: [],
        warnings: [],
        durationMs: Date.now() - start,
        error: "No HTML returned",
      };
    }

    return await validateSchemaHtml(url, html);
  } catch (err) {
    const errorMsg = err instanceof Error ? err.message : String(err);
    logger.warn({ url, error: errorMsg }, "Schema validation failed for URL");
    return {
      url,
      status: "failed",
      schemas: [],
      errors: [],
      warnings: [],
      durationMs: Date.now() - start,
      error: errorMsg,
    };
  }
}

export async function validateSchemaMarkup(
  urls: string[],
  onResult: ResultCallback
): Promise<void> {
  const config = loadConfig();
  const schemaConfig = config.schemaValidation || { concurrency: 2, delayBetweenRequests: 1000, timeout: 15000 };
  const concurrency = schemaConfig.concurrency || 2;
  const delayMs = schemaConfig.delayBetweenRequests || 1000;

  let index = 0;

  async function worker() {
    while (index < urls.length) {
      const currentIndex = index++;
      const url = urls[currentIndex];

      const result = await validateSingleUrl(url);
      onResult(result);

      if (currentIndex < urls.length - 1 && delayMs > 0) {
        await delay(delayMs);
      }
    }
  }

  const workers = Array.from({ length: Math.min(concurrency, urls.length) }, () => worker());
  await Promise.all(workers);
}
