import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";
import { createClient } from "@supabase/supabase-js";
import { GitHubIssueReporter, detectFixableRule, generatePatch } from "@/lib/services/github-issue-reporter";
import type { AutoFixPatch, AutoFixRule } from "@/lib/services/schema-validator/auto-fixer";
import { Octokit } from "@octokit/rest";

const PAGE_TYPES = new Set(["aircraft", "job", "event", "parts-listing", "parts-wanted"]);

// Map page types to the TradeAero source files we know how to patch.
// Discovered during exploration; parts may require a human-authored emitter.
const PATCH_TARGETS: Record<string, string[]> = {
  aircraft: ["src/components/aircraft/AircraftJsonLd.tsx"],
  job: ["src/lib/seo/job-jsonld.ts"],
  event: ["src/lib/seo/event-jsonld.ts"],
  "parts-listing": [],
  "parts-wanted": [],
};

interface FixRequestBody {
  pageType?: string;
}

interface StoredRow {
  id: string;
  url: string;
  canonical_url: string;
  page_type: string;
  schema_type: string;
  status: string;
  raw_errors: Array<{ code: string; message: string; schemaType: string; severity: string }> | null;
  fingerprint: string;
}

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const body = (await request.json().catch(() => ({}))) as FixRequestBody;
  const pageType = body.pageType;
  if (!pageType || !PAGE_TYPES.has(pageType)) {
    return NextResponse.json(
      { error: "pageType must be one of: " + Array.from(PAGE_TYPES).join(", ") },
      { status: 400 }
    );
  }

  const cfg = getConfig().schemaValidation;
  if (!cfg) {
    return NextResponse.json({ error: "schemaValidation not configured" }, { status: 503 });
  }
  if (!cfg.autoFix.enabled) {
    return NextResponse.json({ error: "schemaValidation.autoFix.enabled is false" }, { status: 403 });
  }

  const supaUrl = process.env[cfg.supabase.urlEnv];
  const supaKey = process.env[cfg.supabase.serviceRoleKeyEnv];
  if (!supaUrl || !supaKey) {
    return NextResponse.json({ error: "Supabase env vars not configured" }, { status: 500 });
  }

  const ghToken = process.env[cfg.github.tokenEnv];
  if (!ghToken) {
    return NextResponse.json({ error: "GitHub token env var not set" }, { status: 500 });
  }

  const supabase = createClient(supaUrl, supaKey, {
    auth: { persistSession: false, autoRefreshToken: false },
  });

  const since = new Date(Date.now() - 24 * 3600 * 1000).toISOString();
  const { data, error } = await supabase
    .from(cfg.supabase.table)
    .select("id,url,canonical_url,page_type,schema_type,status,raw_errors,fingerprint")
    .eq("page_type", pageType)
    .eq("status", "fail")
    .gte("created_at", since);

  if (error) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  const rows = (data as StoredRow[]) ?? [];
  if (rows.length === 0) {
    return NextResponse.json({ pageType, patched: 0, message: "No recent failures for this page type." });
  }

  const targets = PATCH_TARGETS[pageType] ?? [];
  if (targets.length === 0) {
    return NextResponse.json({
      pageType,
      patched: 0,
      message: `No known patch targets for ${pageType}. A human-authored fix is required.`,
    });
  }

  // Fetch the current source from GitHub for each target file.
  const octokit = new Octokit({ auth: ghToken });
  const sources: Record<string, string> = {};
  for (const filePath of targets) {
    try {
      const res = await octokit.repos.getContent({
        owner: cfg.github.repoOwner,
        repo: cfg.github.repoName,
        path: filePath,
        ref: cfg.autoFix.baseBranch,
      });
      const file = res.data as { content?: string; encoding?: string };
      if (file.content && file.encoding === "base64") {
        sources[filePath] = Buffer.from(file.content, "base64").toString("utf-8");
      }
    } catch (err) {
      logger.warn({ err, filePath }, "fetching source for patch failed");
    }
  }

  // Collect unique fixable rules across all rows.
  const wantedRules = new Set<AutoFixRule>();
  for (const row of rows) {
    for (const e of row.raw_errors ?? []) {
      if (e.severity !== "error") continue;
      const rule = detectFixableRule({
        code: e.code,
        message: e.message,
        schemaType: e.schemaType,
        severity: "error",
      });
      if (rule && cfg.autoFix.allowedFixes.includes(rule)) wantedRules.add(rule);
    }
  }

  if (wantedRules.size === 0) {
    return NextResponse.json({
      pageType,
      patched: 0,
      message: "No allowlisted auto-fix rules apply to current failures.",
    });
  }

  const patches: AutoFixPatch[] = [];
  for (const filePath of targets) {
    const src = sources[filePath];
    if (!src) continue;
    let working = src;
    for (const rule of wantedRules) {
      const patch = generatePatch(filePath, working, rule);
      if (patch) {
        working = patch.newContent;
        patches.push(patch);
      }
    }
  }

  if (patches.length === 0) {
    return NextResponse.json({
      pageType,
      patched: 0,
      message: "Patch generation produced no diffs (source may already be fixed).",
    });
  }

  // Collapse per-file: keep only the final content per filePath.
  const finalPatches = new Map<string, AutoFixPatch>();
  for (const p of patches) {
    const existing = finalPatches.get(p.filePath);
    if (existing) {
      existing.newContent = p.newContent;
      existing.summary = `${existing.summary}; ${p.summary}`;
    } else {
      finalPatches.set(p.filePath, p);
    }
  }

  const reporter = new GitHubIssueReporter();
  const pr = await reporter.draftBatchedFixPr(pageType, Array.from(finalPatches.values()));
  if (!pr) {
    return NextResponse.json(
      { pageType, patched: 0, message: "PR creation failed; see logs." },
      { status: 502 }
    );
  }

  return NextResponse.json({
    pageType,
    patched: finalPatches.size,
    prNumber: pr.number,
    prUrl: pr.url,
  });
}
