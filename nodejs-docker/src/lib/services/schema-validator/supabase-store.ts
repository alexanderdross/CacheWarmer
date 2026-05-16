import { createClient, type SupabaseClient } from "@supabase/supabase-js";
import logger from "@/lib/logger";
import type { ValidationReport } from "./types";

export interface SupabaseStoreConfig {
  url: string;
  serviceRoleKey: string;
  table: string;
}

export interface StoredRow {
  id: string;
  job_id: string;
  url: string;
  canonical_url: string;
  page_type: string;
  schema_type: string;
  status: string;
  error_count: number;
  warning_count: number;
  raw_errors: unknown;
  fingerprint: string;
  github_issue_number: number | null;
  github_pr_number: number | null;
  created_at: string;
}

export class SupabaseValidationStore {
  private client: SupabaseClient;
  constructor(private cfg: SupabaseStoreConfig) {
    this.client = createClient(cfg.url, cfg.serviceRoleKey, {
      auth: { persistSession: false, autoRefreshToken: false },
    });
  }

  async upsertResult(jobId: string, report: ValidationReport): Promise<string | null> {
    const row = {
      job_id: jobId,
      url: report.url,
      canonical_url: report.canonicalUrl,
      page_type: report.pageType,
      schema_type: report.schemaType,
      status: report.status,
      error_count: report.errors.length,
      warning_count: report.warnings.length,
      raw_errors: [...report.errors, ...report.warnings],
      fingerprint: report.fingerprint,
    };

    const { data, error } = await this.client
      .from(this.cfg.table)
      .upsert(row, { onConflict: "job_id,fingerprint" })
      .select("id")
      .single();

    if (error) {
      logger.warn({ err: error, jobId, url: report.url }, "supabase upsert failed");
      return null;
    }
    return (data as { id: string } | null)?.id ?? null;
  }

  async attachGithubIssue(rowId: string, issueNumber: number): Promise<void> {
    const { error } = await this.client
      .from(this.cfg.table)
      .update({ github_issue_number: issueNumber })
      .eq("id", rowId);
    if (error) logger.warn({ err: error, rowId }, "attach issue number failed");
  }

  async attachGithubPr(rowId: string, prNumber: number): Promise<void> {
    const { error } = await this.client
      .from(this.cfg.table)
      .update({ github_pr_number: prNumber })
      .eq("id", rowId);
    if (error) logger.warn({ err: error, rowId }, "attach pr number failed");
  }

  async listFailuresForPageType(pageType: string, sinceHours = 24): Promise<StoredRow[]> {
    const since = new Date(Date.now() - sinceHours * 3600 * 1000).toISOString();
    const { data, error } = await this.client
      .from(this.cfg.table)
      .select("*")
      .eq("page_type", pageType)
      .eq("status", "fail")
      .gte("created_at", since);
    if (error) {
      logger.warn({ err: error, pageType }, "list failures failed");
      return [];
    }
    return (data as StoredRow[]) ?? [];
  }
}
