/**
 * CacheWarmer Edge — entry point.
 *
 * One deploy per Cloudflare account. Each warms only its own account's zones,
 * because the `cf` cache-control object is dropped across account boundaries.
 */

import { loadConfig, URLS_PER_CHUNK, type Env, type Region, type SiteConfig } from "./config";
import { chunk, fetchSitemapUrls, sortByPriority } from "./sitemap";
import { purgeUrls } from "./purge";
import { DEFAULT_WARM_OPTIONS } from "./warm";
import { spike } from "./spike";
import type { JobProgress, WarmJobSpec } from "./region-warmer";

export { RegionWarmer } from "./region-warmer";

const json = (body: unknown, status = 200) =>
  new Response(JSON.stringify(body, null, 2), {
    status,
    headers: { "content-type": "application/json; charset=utf-8" },
  });

function authorised(request: Request, token: string | undefined): boolean {
  if (!token) return false;
  const header = request.headers.get("authorization");
  return header === `Bearer ${token}`;
}

/** Route a job to one Durable Object per region, each placed by locationHint. */
async function dispatch(
  env: Env,
  site: SiteConfig,
  urls: string[],
  jobId: string,
  reportUrl?: string,
): Promise<JobProgress[]> {
  const regions: Array<Region | "auto"> = site.regions?.length ? site.regions : ["auto"];

  return Promise.all(
    regions.map(async (region) => {
      const id = env.REGION_WARMER.idFromName(`${site.id}:${region}`);
      const stub =
        region === "auto"
          ? env.REGION_WARMER.get(id)
          : env.REGION_WARMER.get(id, { locationHint: region });

      const spec: WarmJobSpec = {
        jobId,
        siteId: site.id,
        region,
        urls,
        options: { cf: site.cf },
        reportUrl,
        reportSecret: env.HUB_SECRET,
      };

      return stub.start(spec);
    }),
  );
}

async function runSite(env: Env, site: SiteConfig, hubReportUrl?: string) {
  const limit = site.maxUrls ?? 1000;

  const { entries, sitemapsFetched, truncated } = await fetchSitemapUrls(site.sitemapUrl, {
    userAgent: DEFAULT_WARM_OPTIONS.userAgent,
    timeoutMs: DEFAULT_WARM_OPTIONS.timeoutMs,
    maxDepth: 3,
    // Collect beyond the run limit so the sort below can actually reorder;
    // slicing during the fetch would cut in document order instead.
    maxUrls: Math.max(limit * 10, limit),
  });

  // Sort first, then slice — a shortened run then still covers the pages that
  // matter most.
  const urls = sortByPriority(entries).slice(0, limit).map((e) => e.loc);

  // Purge first. The Node module does this last and throws away the cache it
  // just built.
  let purge = null;
  if (site.purgeBeforeWarm && env.CF_PURGE_TOKEN) {
    purge = await purgeUrls(urls, {
      zoneId: site.zoneId,
      apiToken: env.CF_PURGE_TOKEN,
      plan: site.plan,
    });
  }

  const jobId = `${site.id}-${new Date().toISOString().replace(/[:.]/g, "-")}`;
  const dispatched = await dispatch(env, site, urls, jobId, hubReportUrl);

  return {
    jobId,
    site: site.id,
    zone: site.zone,
    urls: urls.length,
    sitemapsFetched,
    truncated: truncated || entries.length > limit,
    chunks: chunk(urls, URLS_PER_CHUNK).length,
    purge,
    regions: dispatched,
  };
}

export default {
  /** Scheduled warming. Cron triggers cap at 15 minutes, so the DO chain does
   *  the long tail — this handler only fans out and returns. */
  async scheduled(_event: ScheduledController, env: Env, ctx: ExecutionContext): Promise<void> {
    const config = loadConfig(env);
    ctx.waitUntil(
      Promise.all(
        config.sites.map((site) =>
          runSite(env, site, config.hubReportUrl).catch((err) => {
            console.error(`Site ${site.id} failed to start`, err);
          }),
        ),
      ),
    );
  },

  async fetch(request: Request, env: Env, _ctx: ExecutionContext): Promise<Response> {
    const url = new URL(request.url);

    if (url.pathname === "/health") {
      return json({ ok: true, service: "cachewarmer-edge" });
    }

    // Hub endpoint: satellites in other accounts POST their job summaries here.
    if (url.pathname === "/report" && request.method === "POST") {
      if (!authorised(request, env.HUB_SECRET)) return json({ error: "unauthorised" }, 401);
      const progress = (await request.json()) as JobProgress;
      if (env.DB) {
        await env.DB.prepare(
          `INSERT OR REPLACE INTO job_reports
             (job_id, site_id, region, status, total, processed, colos, verdicts, completed_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        )
          .bind(
            progress.jobId,
            progress.siteId,
            progress.region,
            progress.status,
            progress.total,
            progress.processed,
            JSON.stringify(progress.colos),
            JSON.stringify(progress.verdicts),
            progress.completedAt ?? null,
          )
          .run();
      }
      return json({ ok: true });
    }

    // Everything below changes state or exposes detail, so it needs the token.
    if (!authorised(request, env.ADMIN_TOKEN)) return json({ error: "unauthorised" }, 401);

    const config = loadConfig(env);

    if (url.pathname === "/status") {
      const sites = await Promise.all(
        config.sites.map(async (site) => {
          const regions: Array<Region | "auto"> = site.regions?.length ? site.regions : ["auto"];
          const progress = await Promise.all(
            regions.map(async (region) => {
              const id = env.REGION_WARMER.idFromName(`${site.id}:${region}`);
              return env.REGION_WARMER.get(id).progress();
            }),
          );
          return { site: site.id, zone: site.zone, regions: progress.filter(Boolean) };
        }),
      );
      return json({ account: config.account, accountId: config.accountId, sites });
    }

    if (url.pathname === "/results") {
      const siteId = url.searchParams.get("site");
      const region = (url.searchParams.get("region") ?? "auto") as Region | "auto";
      if (!siteId) return json({ error: "site query parameter is required" }, 400);
      const stub = env.REGION_WARMER.get(env.REGION_WARMER.idFromName(`${siteId}:${region}`));
      // Progress belongs next to the failures: only failures are stored per
      // URL, so a run where everything worked returns an empty list, and the
      // counts and colos are the only evidence it ran at all.
      const [progress, results] = await Promise.all([stub.progress(), stub.results()]);
      return json({ site: siteId, region, progress, results });
    }

    if (url.pathname === "/warm" && request.method === "POST") {
      const siteId = url.searchParams.get("site");
      const sites = siteId ? config.sites.filter((s) => s.id === siteId) : config.sites;
      if (sites.length === 0) return json({ error: `unknown site: ${siteId}` }, 404);
      // Report per site rather than failing the whole call: one site whose
      // regions are still busy must not hide the others' results, and a purge
      // that already ran needs to stay visible.
      const started = await Promise.all(
        sites.map(async (site) => {
          try {
            return await runSite(env, site, config.hubReportUrl);
          } catch (err) {
            return { site: site.id, error: err instanceof Error ? err.message : String(err) };
          }
        }),
      );
      return json({ started });
    }

    if (url.pathname === "/spike") {
      const target = url.searchParams.get("url");
      if (!target) return json({ error: "url query parameter is required" }, 400);
      const regions = (url.searchParams.get("regions") ?? "weur,enam")
        .split(",")
        .map((r) => r.trim())
        .filter(Boolean) as Region[];
      return json(await spike(env, target, regions));
    }

    return json({ error: "not found" }, 404);
  },
};
