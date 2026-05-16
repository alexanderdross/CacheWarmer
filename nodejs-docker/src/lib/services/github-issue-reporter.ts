import { Octokit } from "@octokit/rest";
import { createPullRequest } from "octokit-plugin-create-pull-request";
import logger from "@/lib/logger";
import { getConfig } from "@/lib/config";
import type { PendingValidationOutcome } from "./schema-validator";
import type { ValidationReport } from "./schema-validator/types";
import { detectFixableRule, generatePatch, type AutoFixPatch } from "./schema-validator/auto-fixer";

const OctokitWithPR = Octokit.plugin(createPullRequest);

export interface GitHubReporterConfig {
  enabled: boolean;
  token: string;
  repoOwner: string;
  repoName: string;
  labels: string[];
  issueAssignees: string[];
  maxIssuesPerJob: number;
  reopenClosedWithinDays: number;
  autoFix: {
    enabled: boolean;
    branch: string;
    baseBranch: string;
    draftPr: boolean;
    allowedFixes: string[];
  };
}

function loadConfig(): GitHubReporterConfig | null {
  const cfg = getConfig().schemaValidation;
  if (!cfg?.github?.enabled) return null;
  const token = process.env[cfg.github.tokenEnv];
  if (!token) {
    logger.warn({ env: cfg.github.tokenEnv }, "GitHub token env var not set");
    return null;
  }
  return {
    enabled: cfg.github.enabled,
    token,
    repoOwner: cfg.github.repoOwner,
    repoName: cfg.github.repoName,
    labels: cfg.github.labels ?? [],
    issueAssignees: cfg.github.issueAssignees ?? [],
    maxIssuesPerJob: cfg.github.maxIssuesPerJob ?? 30,
    reopenClosedWithinDays: cfg.github.reopenClosedWithinDays ?? 30,
    autoFix: {
      enabled: cfg.autoFix?.enabled ?? false,
      branch: cfg.autoFix?.branch ?? "claude/schema-validation-cache-warmer-ZQNkw",
      baseBranch: cfg.autoFix?.baseBranch ?? "main",
      draftPr: cfg.autoFix?.draftPr ?? true,
      allowedFixes: cfg.autoFix?.allowedFixes ?? [],
    },
  };
}

function fingerprintLabel(fp: string): string {
  return `schema-fp:${fp}`;
}

function issueTitle(report: ValidationReport): string {
  const first = report.errors[0]?.message ?? "schema error";
  const truncated = first.length > 60 ? first.slice(0, 57) + "..." : first;
  return `[Schema] ${report.schemaType} on ${report.canonicalUrl}: ${truncated}`;
}

function issueBody(report: ValidationReport): string {
  const errorList = report.errors
    .map((e) => `- **${e.code}** (${e.schemaType}): ${e.message}`)
    .join("\n");
  return [
    `Automated detection from CacheWarmer schema validation.`,
    ``,
    `**Page type:** \`${report.pageType}\``,
    `**Schema type:** \`${report.schemaType}\``,
    `**Canonical URL:** \`${report.canonicalUrl}\``,
    `**Sample URL:** ${report.url}`,
    `**Fingerprint:** \`${report.fingerprint}\``,
    ``,
    `### Errors`,
    errorList || "_none_",
    ``,
    `<sub>Filed by CacheWarmer schema validation gate. Closing this issue without a fix will cause it to be re-opened on the next recurrence.</sub>`,
  ].join("\n");
}

export class GitHubIssueReporter {
  private cfg: GitHubReporterConfig | null;
  private octokit: InstanceType<typeof OctokitWithPR> | null;
  private issuesCreatedThisFlush = 0;

  constructor(cfgOverride?: GitHubReporterConfig | null, octokit?: InstanceType<typeof OctokitWithPR>) {
    this.cfg = cfgOverride ?? loadConfig();
    this.octokit = this.cfg ? (octokit ?? new OctokitWithPR({ auth: this.cfg.token })) : null;
  }

  private async findOpenIssueByFingerprint(fp: string): Promise<number | null> {
    if (!this.cfg || !this.octokit) return null;
    const q = `repo:${this.cfg.repoOwner}/${this.cfg.repoName} is:issue is:open label:"${fingerprintLabel(fp)}"`;
    try {
      const { data } = await this.octokit.search.issuesAndPullRequests({ q, per_page: 1 });
      return data.items[0]?.number ?? null;
    } catch (err) {
      logger.warn({ err, fp }, "github issue search failed");
      return null;
    }
  }

  async reportIfNew(report: ValidationReport): Promise<number | null> {
    if (!this.cfg || !this.octokit) return null;
    if (report.status !== "fail") return null;
    if (this.issuesCreatedThisFlush >= this.cfg.maxIssuesPerJob) return null;

    const existing = await this.findOpenIssueByFingerprint(report.fingerprint);
    if (existing) return existing;

    try {
      const { data } = await this.octokit.issues.create({
        owner: this.cfg.repoOwner,
        repo: this.cfg.repoName,
        title: issueTitle(report),
        body: issueBody(report),
        labels: [...this.cfg.labels, fingerprintLabel(report.fingerprint)],
        assignees: this.cfg.issueAssignees.length ? this.cfg.issueAssignees : undefined,
      });
      this.issuesCreatedThisFlush += 1;
      return data.number;
    } catch (err) {
      logger.warn({ err, fp: report.fingerprint }, "github issue creation failed");
      return null;
    }
  }

  async flush(outcomes: PendingValidationOutcome[]): Promise<void> {
    if (!this.cfg) return;
    this.issuesCreatedThisFlush = 0;
    for (const o of outcomes) {
      if (o.report.status !== "fail") continue;
      await this.reportIfNew(o.report);
    }
  }

  /**
   * Opens a single draft PR with a batched patch for the given page type.
   * Returns the PR number, or null if nothing was patched.
   */
  async draftBatchedFixPr(
    pageType: string,
    patches: AutoFixPatch[]
  ): Promise<{ number: number; url: string } | null> {
    if (!this.cfg || !this.octokit) return null;
    if (!this.cfg.autoFix.enabled || patches.length === 0) return null;

    const allowed = patches.filter((p) => this.cfg!.autoFix.allowedFixes.includes(p.rule));
    if (allowed.length === 0) return null;

    const changes: Record<string, string> = {};
    const summaries: string[] = [];
    for (const p of allowed) {
      changes[p.filePath] = p.newContent;
      summaries.push(`- \`${p.filePath}\`: ${p.summary}`);
    }

    const branchSuffix = `${pageType}-${Date.now().toString(36)}`;
    const branchName = `${this.cfg.autoFix.branch}-${branchSuffix}`;

    try {
      const pr = await this.octokit.createPullRequest({
        owner: this.cfg.repoOwner,
        repo: this.cfg.repoName,
        title: `[Schema auto-fix] ${pageType} — ${allowed.length} patch${allowed.length === 1 ? "" : "es"}`,
        body: `Automated draft PR from CacheWarmer schema validator.\n\n${summaries.join("\n")}\n\nReview each change carefully before merging.`,
        head: branchName,
        base: this.cfg.autoFix.baseBranch,
        draft: this.cfg.autoFix.draftPr,
        changes: [
          {
            files: changes,
            commit: `[Schema auto-fix] ${pageType}: ${allowed.length} patch${allowed.length === 1 ? "" : "es"}`,
          },
        ],
      });
      if (!pr) return null;
      return { number: pr.data.number, url: pr.data.html_url };
    } catch (err) {
      logger.warn({ err, pageType }, "github draft-pr creation failed");
      return null;
    }
  }

  isAutoFixEnabled(): boolean {
    return Boolean(this.cfg?.autoFix.enabled);
  }
}

export { detectFixableRule, generatePatch };
