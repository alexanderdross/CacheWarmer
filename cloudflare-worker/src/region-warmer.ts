/**
 * RegionWarmer — a Durable Object that warms a slice of URLs from one region.
 *
 * Work is chained across alarms rather than done in one pass. Each alarm is a
 * fresh Worker invocation with its own subrequest and CPU budget, which is
 * what makes this run on Workers Free, where the 50-subrequest cap cannot be
 * raised. It also makes a run resumable: if an alarm fails, only its chunk is
 * retried.
 */

import { DurableObject } from "cloudflare:workers";
import { MAX_CONNECTIONS, URLS_PER_CHUNK, type Env, type Region } from "./config";
import { DEFAULT_WARM_OPTIONS, warmBatch, type WarmOptions, type WarmResult } from "./warm";
import { isSuccess, type Verdict } from "./verdict";

export interface WarmJobSpec {
  jobId: string;
  siteId: string;
  region: Region | "auto";
  urls: string[];
  options?: Partial<WarmOptions>;
  /** Where to POST the summary when the job finishes. */
  reportUrl?: string;
  reportSecret?: string;
}

export interface JobProgress {
  jobId: string;
  siteId: string;
  region: string;
  status: "queued" | "running" | "completed" | "failed";
  total: number;
  processed: number;
  /** Data centres that actually served the work, from cf-ray. */
  colos: string[];
  verdicts: Record<Verdict, number>;
  startedAt?: string;
  completedAt?: string;
  error?: string;
}

const EMPTY_VERDICTS: Record<Verdict, number> = {
  warmed: 0,
  already_warm: 0,
  not_cacheable: 0,
  bypassed: 0,
  zone_not_caching: 0,
  indeterminate: 0,
  failed: 0,
};

export class RegionWarmer extends DurableObject<Env> {
  constructor(ctx: DurableObjectState, env: Env) {
    super(ctx, env);
    ctx.blockConcurrencyWhile(async () => {
      this.ctx.storage.sql.exec(`
        CREATE TABLE IF NOT EXISTS results (
          url TEXT PRIMARY KEY,
          verdict TEXT NOT NULL,
          reason TEXT,
          colo TEXT,
          fill_state TEXT,
          probe_state TEXT,
          http_status INTEGER,
          duration_ms INTEGER,
          created_at TEXT NOT NULL
        )
      `);
    });
  }

  /** Queue a job and fire the first chunk immediately. */
  async start(spec: WarmJobSpec): Promise<JobProgress> {
    const existing = await this.ctx.storage.get<JobProgress>("progress");
    if (existing && existing.status === "running") {
      throw new Error(`Job ${existing.jobId} is already running in this region`);
    }

    this.ctx.storage.sql.exec("DELETE FROM results");

    const progress: JobProgress = {
      jobId: spec.jobId,
      siteId: spec.siteId,
      region: spec.region,
      status: "queued",
      total: spec.urls.length,
      processed: 0,
      colos: [],
      verdicts: { ...EMPTY_VERDICTS },
      startedAt: new Date().toISOString(),
    };

    await this.ctx.storage.put({ spec, progress, cursor: 0 });
    await this.ctx.storage.setAlarm(Date.now());
    return progress;
  }

  async progress(): Promise<JobProgress | null> {
    return (await this.ctx.storage.get<JobProgress>("progress")) ?? null;
  }

  /** Per-URL detail. Failures first — that is what anyone actually reads. */
  async results(limit = 200): Promise<Array<Record<string, unknown>>> {
    const cursor = this.ctx.storage.sql.exec(
      `SELECT * FROM results
       ORDER BY CASE WHEN verdict IN ('warmed','already_warm') THEN 1 ELSE 0 END, url
       LIMIT ?`,
      limit,
    );
    return cursor.toArray() as Array<Record<string, unknown>>;
  }

  /**
   * One chunk per alarm. Keeping the chunk at or below URLS_PER_CHUNK is what
   * holds the invocation inside the Free-plan subrequest budget.
   */
  override async alarm(): Promise<void> {
    const spec = await this.ctx.storage.get<WarmJobSpec>("spec");
    const progress = await this.ctx.storage.get<JobProgress>("progress");
    const cursor = (await this.ctx.storage.get<number>("cursor")) ?? 0;

    if (!spec || !progress) return;

    const slice = spec.urls.slice(cursor, cursor + URLS_PER_CHUNK);
    if (slice.length === 0) {
      await this.finish(spec, progress);
      return;
    }

    progress.status = "running";

    const options: WarmOptions = { ...DEFAULT_WARM_OPTIONS, ...spec.options };

    try {
      const results = await warmBatch(slice, options, MAX_CONNECTIONS);
      this.record(results, progress);

      progress.processed = cursor + slice.length;
      await this.ctx.storage.put({ progress, cursor: progress.processed });

      // Chain straight into the next chunk; the alarm resets the budget.
      await this.ctx.storage.setAlarm(Date.now());
    } catch (err) {
      progress.status = "failed";
      progress.error = err instanceof Error ? err.message : String(err);
      progress.completedAt = new Date().toISOString();
      await this.ctx.storage.put({ progress });
      await this.report(spec, progress);
    }
  }

  private record(results: WarmResult[], progress: JobProgress): void {
    const colos = new Set(progress.colos);
    const now = new Date().toISOString();

    for (const result of results) {
      progress.verdicts[result.verdict] = (progress.verdicts[result.verdict] ?? 0) + 1;
      if (result.colo) colos.add(result.colo);

      // Only failures and oddities are stored per URL. Successes are counted,
      // not listed — otherwise storage grows with no one reading it.
      if (!isSuccess(result.verdict)) {
        this.ctx.storage.sql.exec(
          `INSERT OR REPLACE INTO results
             (url, verdict, reason, colo, fill_state, probe_state, http_status, duration_ms, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
          result.url,
          result.verdict,
          result.reason ?? null,
          result.colo ?? null,
          result.fill.state,
          result.probe.state,
          result.probe.httpStatus,
          result.totalMs,
          now,
        );
      }
    }

    progress.colos = [...colos].sort();
  }

  private async finish(spec: WarmJobSpec, progress: JobProgress): Promise<void> {
    progress.status = "completed";
    progress.completedAt = new Date().toISOString();
    await this.ctx.storage.put({ progress });
    await this.report(spec, progress);
  }

  /**
   * Push the summary to the hub. Service bindings do not cross account
   * boundaries, so satellites report over plain HTTPS with a shared secret.
   */
  private async report(spec: WarmJobSpec, progress: JobProgress): Promise<void> {
    if (!spec.reportUrl) return;
    try {
      await fetch(spec.reportUrl, {
        method: "POST",
        headers: {
          "content-type": "application/json",
          ...(spec.reportSecret ? { authorization: `Bearer ${spec.reportSecret}` } : {}),
        },
        body: JSON.stringify(progress),
        signal: AbortSignal.timeout(10_000),
      });
    } catch {
      // Reporting is best-effort: a hub that is down must not fail the warm.
    }
  }
}
