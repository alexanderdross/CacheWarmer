/**
 * Site configuration and Worker bindings.
 *
 * One deploy per Cloudflare account, each warming only the zones of its own
 * account — that is the only way to get full `cf` cache control, since the cf
 * object is dropped across account boundaries.
 */

import type { RegionWarmer } from "./region-warmer";
import type { WarmOptions } from "./warm";

/** Durable Object location hints, best-effort placement. */
export const REGIONS = [
  "wnam",
  "enam",
  "weur",
  "eeur",
  "apac",
  "apac-ne",
  "apac-se",
  "oc",
] as const;

export type Region = (typeof REGIONS)[number];

/**
 * sam, afr and me are accepted by the API but currently spawn in a nearby
 * region instead, so they are deliberately absent from REGIONS: offering them
 * would promise placement that does not happen.
 */
export const REGIONS_WITHOUT_PLACEMENT = ["sam", "afr", "me"] as const;

export interface SiteConfig {
  /** Stable identifier used in job ids and reports. */
  id: string;
  zone: string;
  zoneId: string;
  sitemapUrl: string;
  /** Cloudflare plan of the zone, which sets purge batch size and pacing. */
  plan?: "free" | "pro" | "business" | "enterprise";
  /** Regions to warm from. A single region still fills the upper tier. */
  regions?: Region[];
  /** Purge before warming. Off by default: purging costs a cold origin hit. */
  purgeBeforeWarm?: boolean;
  /** Cap per run, to stay inside the daily request budget. */
  maxUrls?: number;
  /** Warm only URLs whose sitemap lastmod changed since the previous run. */
  smartWarming?: boolean;
  cf?: WarmOptions["cf"];
}

export interface WorkerConfig {
  /** Account this deploy belongs to, for reports and sanity checks. */
  account: string;
  accountId: string;
  sites: SiteConfig[];
  /** Where satellites push their job summaries. Unset on the hub itself. */
  hubReportUrl?: string;
}

export interface Env {
  CONFIG: string;
  /** Zone:Cache Purge token. Scoped to a user, so it can span accounts. */
  CF_PURGE_TOKEN?: string;
  /** Shared secret for satellite -> hub reporting. */
  HUB_SECRET?: string;
  /** Guards the manual trigger and spike endpoints. */
  ADMIN_TOKEN?: string;
  /** Typed so the RPC methods on RegionWarmer are visible on the stub. */
  REGION_WARMER: DurableObjectNamespace<RegionWarmer>;
  DB?: D1Database;
}

export function loadConfig(env: Env): WorkerConfig {
  let parsed: unknown;
  try {
    parsed = JSON.parse(env.CONFIG);
  } catch (err) {
    throw new Error(
      `CONFIG is not valid JSON: ${err instanceof Error ? err.message : String(err)}`,
    );
  }

  const config = parsed as WorkerConfig;
  if (!config.accountId) throw new Error("CONFIG.accountId is required");
  if (!Array.isArray(config.sites) || config.sites.length === 0) {
    throw new Error("CONFIG.sites must list at least one site");
  }

  for (const site of config.sites) {
    if (!site.id) throw new Error("Every site needs an id");
    if (!site.zoneId) throw new Error(`Site ${site.id} is missing zoneId`);
    if (!site.sitemapUrl) throw new Error(`Site ${site.id} is missing sitemapUrl`);
    for (const region of site.regions ?? []) {
      if (!REGIONS.includes(region)) {
        throw new Error(
          `Site ${site.id} requests region "${region}", which is not one of: ${REGIONS.join(", ")}`,
        );
      }
    }
  }

  return config;
}

/**
 * Workers Free allows 50 external subrequests per invocation and the cap
 * cannot be raised. At two subrequests per URL that is 25 URLs, which is why
 * work is chunked and chained across Durable Object alarms rather than run in
 * one pass.
 */
export const FREE_PLAN_SUBREQUEST_BUDGET = 50;
export const URLS_PER_CHUNK = 25;

/** Cloudflare permits 6 simultaneous outgoing connections on both plans. */
export const MAX_CONNECTIONS = 6;
