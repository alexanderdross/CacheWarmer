/**
 * Cloudflare cache purge.
 *
 * Purge runs BEFORE warming. The Node module has this the wrong way round —
 * it purges as the last target, discarding the cache the job just built.
 */

/**
 * Cloudflare accepts 100 URLs per single-file purge request (500 on
 * Enterprise). The Node module hardcodes 30, which costs 3.3x the API calls
 * for no reason.
 */
export const PURGE_BATCH_SIZE = 100;
export const PURGE_BATCH_SIZE_ENTERPRISE = 500;

/**
 * Purge request rate, per ACCOUNT, by the zone's Cloudflare plan. Exceeding it
 * returns 429, so a run over several zones on one account has to pace itself.
 */
export const PURGE_RATE_PER_SECOND: Record<string, number> = {
  free: 5 / 60,
  pro: 5,
  business: 10,
  enterprise: 50,
};

export interface PurgeOptions {
  zoneId: string;
  apiToken: string;
  /** Drives both batch size and pacing. */
  plan?: keyof typeof PURGE_RATE_PER_SECOND;
  timeoutMs?: number;
}

export interface PurgeResult {
  requested: number;
  purged: number;
  batches: number;
  errors: string[];
}

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

export function purgeBatchSize(plan: string | undefined): number {
  return plan === "enterprise" ? PURGE_BATCH_SIZE_ENTERPRISE : PURGE_BATCH_SIZE;
}

/** Delay needed between purge calls to stay inside the account's rate. */
export function purgeIntervalMs(plan: string | undefined): number {
  const perSecond = PURGE_RATE_PER_SECOND[plan ?? "free"] ?? PURGE_RATE_PER_SECOND.free;
  return Math.ceil(1000 / perSecond);
}

/**
 * Purge URLs from a zone.
 *
 * Works across account boundaries: an API token is scoped to a user, not an
 * account, so one token held by an admin can purge every zone they can reach.
 * That is the one operation that does not need a Worker per account.
 */
export async function purgeUrls(urls: string[], options: PurgeOptions): Promise<PurgeResult> {
  const result: PurgeResult = { requested: urls.length, purged: 0, batches: 0, errors: [] };
  if (urls.length === 0) return result;

  const size = purgeBatchSize(options.plan);
  const interval = purgeIntervalMs(options.plan);
  const endpoint = `https://api.cloudflare.com/client/v4/zones/${options.zoneId}/purge_cache`;

  for (let i = 0; i < urls.length; i += size) {
    const batch = urls.slice(i, i + size);
    if (result.batches > 0) await sleep(interval);
    result.batches++;

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: {
          authorization: `Bearer ${options.apiToken}`,
          "content-type": "application/json",
        },
        body: JSON.stringify({ files: batch }),
        signal: AbortSignal.timeout(options.timeoutMs ?? 30_000),
      });

      const body = (await response.json()) as {
        success?: boolean;
        errors?: Array<{ message?: string }>;
      };

      if (response.ok && body.success) {
        result.purged += batch.length;
      } else {
        const message =
          body.errors?.map((e) => e.message).filter(Boolean).join("; ") ||
          `HTTP ${response.status}`;
        result.errors.push(`Batch ${result.batches}: ${message}`);
      }
    } catch (err) {
      result.errors.push(
        `Batch ${result.batches}: ${err instanceof Error ? err.message : String(err)}`,
      );
    }
  }

  return result;
}

/**
 * Purge by cache tag. One request instead of N when the origin emits
 * Cache-Tag headers, which sidesteps the rate limit entirely.
 */
export async function purgeTags(tags: string[], options: PurgeOptions): Promise<PurgeResult> {
  const result: PurgeResult = { requested: tags.length, purged: 0, batches: 0, errors: [] };
  if (tags.length === 0) return result;

  const endpoint = `https://api.cloudflare.com/client/v4/zones/${options.zoneId}/purge_cache`;
  result.batches = 1;

  try {
    const response = await fetch(endpoint, {
      method: "POST",
      headers: {
        authorization: `Bearer ${options.apiToken}`,
        "content-type": "application/json",
      },
      body: JSON.stringify({ tags }),
      signal: AbortSignal.timeout(options.timeoutMs ?? 30_000),
    });

    const body = (await response.json()) as { success?: boolean; errors?: Array<{ message?: string }> };
    if (response.ok && body.success) result.purged = tags.length;
    else {
      result.errors.push(
        body.errors?.map((e) => e.message).filter(Boolean).join("; ") || `HTTP ${response.status}`,
      );
    }
  } catch (err) {
    result.errors.push(err instanceof Error ? err.message : String(err));
  }

  return result;
}
