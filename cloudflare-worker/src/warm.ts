/**
 * Warm a URL and prove it.
 *
 * A fill request populates the edge cache, a probe request confirms it landed.
 * Both observations are kept so the caller can tell "warmed" from "was already
 * warm" from "this page cannot be cached, and here is why".
 */

import {
  classify,
  coloFromRay,
  normaliseCacheState,
  readCacheHeaders,
  type RequestObservation,
  type Verdict,
} from "./verdict";

export interface WarmOptions {
  /** Custom UA so warming traffic is identifiable in origin logs. */
  userAgent: string;
  /** Extra headers, e.g. a marker your analytics can filter on. */
  headers?: Record<string, string>;
  timeoutMs: number;
  /**
   * Milliseconds to wait between fill and probe. A cache write is not always
   * visible to the very next request; the right value is measured, not assumed.
   */
  probeDelayMs: number;
  /**
   * Cache overrides. Only honoured for zones in the same account as the Worker
   * — Cloudflare drops the cf object across account boundaries.
   */
  cf?: {
    cacheEverything?: boolean;
    cacheTtl?: number;
    cacheTtlByStatus?: Record<string, number>;
    cacheTags?: string[];
  };
}

export interface WarmResult {
  url: string;
  verdict: Verdict;
  reason?: string;
  fill: RequestObservation;
  probe: RequestObservation;
  /** Data centre that did the work, when fill and probe agree. */
  colo?: string;
  totalMs: number;
}

export const DEFAULT_WARM_OPTIONS: WarmOptions = {
  userAgent: "Mozilla/5.0 (compatible; CacheWarmer/2.0; +https://cachewarmer.drossmedia.de)",
  timeoutMs: 15_000,
  probeDelayMs: 250,
};

async function observe(
  url: string,
  options: WarmOptions,
  init: RequestInit,
): Promise<RequestObservation> {
  const start = Date.now();
  try {
    const response = await fetch(url, {
      ...init,
      signal: AbortSignal.timeout(options.timeoutMs),
      headers: {
        "user-agent": options.userAgent,
        ...options.headers,
        ...(init.headers as Record<string, string> | undefined),
      },
    });

    // The body must be drained, otherwise the edge may not complete the cache
    // write. Discarding it keeps memory flat on large pages.
    await response.body?.cancel();

    const headers = readCacheHeaders(response.headers);
    return {
      ok: response.status >= 200 && response.status < 400,
      httpStatus: response.status,
      colo: coloFromRay(response.headers.get("cf-ray")),
      state: normaliseCacheState(headers),
      headers,
      durationMs: Date.now() - start,
    };
  } catch (err) {
    return {
      ok: false,
      httpStatus: 0,
      state: "unknown",
      headers: {},
      durationMs: Date.now() - start,
      error: err instanceof Error ? err.message : String(err),
    };
  }
}

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Fill, wait, probe, classify. Costs two subrequests per URL — the budgeting
 * unit everything upstream is sized against.
 */
export async function warmAndVerify(
  url: string,
  options: WarmOptions = DEFAULT_WARM_OPTIONS,
): Promise<WarmResult> {
  const start = Date.now();

  // The cf object is what makes this more than an ordinary GET — but it is
  // silently dropped when the target zone lives in another Cloudflare account,
  // which is why each account gets its own deploy.
  const fill = await observe(url, options, { method: "GET", cf: options.cf });

  if (!fill.ok) {
    return {
      url,
      verdict: "failed",
      reason: fill.error ?? `HTTP ${fill.httpStatus}`,
      fill,
      probe: fill,
      colo: fill.colo,
      totalMs: Date.now() - start,
    };
  }

  if (options.probeDelayMs > 0) await sleep(options.probeDelayMs);

  // The probe deliberately carries no cf overrides: it must observe what a real
  // visitor would get, not what we can talk the cache into.
  const probe = await observe(url, options, { method: "GET" });

  const { verdict, reason } = classify(fill, probe);

  return {
    url,
    verdict,
    reason,
    fill,
    probe,
    colo: fill.colo === probe.colo ? fill.colo : undefined,
    totalMs: Date.now() - start,
  };
}

/**
 * Warm a batch with bounded concurrency.
 *
 * Cloudflare allows 6 simultaneous outgoing connections per invocation on both
 * Free and Paid, so there is nothing to gain above that. A worker pool is used
 * rather than fixed slices, so one slow URL cannot stall the others.
 */
export async function warmBatch(
  urls: string[],
  options: WarmOptions = DEFAULT_WARM_OPTIONS,
  concurrency = 6,
  onResult?: (result: WarmResult) => void,
): Promise<WarmResult[]> {
  const results: WarmResult[] = new Array(urls.length);
  let next = 0;

  const worker = async () => {
    for (;;) {
      const index = next++;
      if (index >= urls.length) return;
      const result = await warmAndVerify(urls[index], options);
      results[index] = result;
      onResult?.(result);
    }
  };

  await Promise.all(Array.from({ length: Math.min(concurrency, urls.length) }, worker));
  return results;
}

/**
 * Subrequests consumed per URL: one fill, one probe.
 *
 * Workers Free caps an invocation at 50 external subrequests and cannot be
 * raised, so a chunk must stay at or below 25 URLs.
 */
export const SUBREQUESTS_PER_URL = 2;

export function maxUrlsPerInvocation(subrequestBudget: number): number {
  return Math.floor(subrequestBudget / SUBREQUESTS_PER_URL);
}
