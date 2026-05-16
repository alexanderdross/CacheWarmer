import pLimit from "p-limit";
import logger from "@/lib/logger";
import { getConfig } from "@/lib/config";
import { SdttSchemaValidator } from "./sdtt-validator";
import { SupabaseValidationStore } from "./supabase-store";
import type { ValidationReport, ValidationJobItem } from "./types";

export interface PendingValidationOutcome {
  jobId: string;
  report: ValidationReport;
  rowId: string | null;
}

let storeCache: SupabaseValidationStore | null = null;

function getStore(): SupabaseValidationStore | null {
  if (storeCache) return storeCache;
  const cfg = getConfig().schemaValidation;
  if (!cfg?.supabase) return null;
  const url = process.env[cfg.supabase.urlEnv];
  const key = process.env[cfg.supabase.serviceRoleKeyEnv];
  if (!url || !key) {
    logger.warn(
      { urlEnv: cfg.supabase.urlEnv, keyEnv: cfg.supabase.serviceRoleKeyEnv },
      "schemaValidation.supabase env vars missing; results will not be persisted"
    );
    return null;
  }
  storeCache = new SupabaseValidationStore({ url, serviceRoleKey: key, table: cfg.supabase.table });
  return storeCache;
}

export class SchemaValidatorQueue {
  private validator = new SdttSchemaValidator(getConfig().schemaValidation?.presets);
  private limit = pLimit(Math.max(1, getConfig().schemaValidation?.concurrency ?? 4));
  private pending: Array<Promise<PendingValidationOutcome[]>> = [];
  private outcomes: PendingValidationOutcome[] = [];

  enqueue(item: ValidationJobItem): void {
    this.pending.push(
      this.limit(async () => {
        const start = Date.now();
        try {
          const reports = await this.validator.validate(item.html, item.url);
          const store = getStore();
          const results: PendingValidationOutcome[] = [];
          for (const report of reports) {
            const rowId = store ? await store.upsertResult(item.jobId, report) : null;
            results.push({ jobId: item.jobId, report, rowId });
          }
          logger.info(
            { url: item.url, ms: Date.now() - start, reports: results.length },
            "schema validated"
          );
          return results;
        } catch (err) {
          const message = err instanceof Error ? err.message : String(err);
          logger.error({ url: item.url, error: message }, "schema validation failed");
          return [];
        }
      })
    );
  }

  async drain(): Promise<PendingValidationOutcome[]> {
    const settled = await Promise.all(this.pending);
    this.pending = [];
    for (const batch of settled) this.outcomes.push(...batch);
    const drained = this.outcomes;
    this.outcomes = [];
    return drained;
  }
}

export function isSchemaValidationEnabled(): boolean {
  return Boolean(getConfig().schemaValidation?.enabled);
}
