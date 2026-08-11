/**
 * Cache-state normalisation and warm verdicts.
 *
 * This is the piece the existing warmers lack: they record an HTTP status and
 * call it success. A warm is only proven when a second request comes back as a
 * cache hit, so every warm is a fill request followed by a probe, and the pair
 * is classified here.
 */

/** Normalised cache state, collapsed from whichever CDN served the response. */
export type CacheState =
  | "hit"
  | "miss"
  | "expired"
  | "stale"
  | "revalidated"
  | "updating"
  | "bypass"
  | "dynamic"
  | "unknown";

export type Verdict =
  /** Fill populated the cache and the probe confirmed it. The good case. */
  | "warmed"
  /** Already cached before we started. Nothing to do, still fine. */
  | "already_warm"
  /** Two requests, still no hit. The page genuinely cannot be cached. */
  | "not_cacheable"
  /** A rule, cookie, or Cache-Control explicitly bypassed the cache. */
  | "bypassed"
  /** The zone does not cache this content type at all. */
  | "zone_not_caching"
  /** Fill and probe were served by different data centres — proves nothing. */
  | "indeterminate"
  /** The request itself failed. */
  | "failed";

export interface CacheHeaders {
  cfCacheStatus?: string;
  xCache?: string;
  age?: string;
  cacheControl?: string;
  setCookie?: boolean;
  vary?: string;
}

export interface RequestObservation {
  ok: boolean;
  httpStatus: number;
  /** Data centre that served the response, from the cf-ray suffix. */
  colo?: string;
  state: CacheState;
  headers: CacheHeaders;
  durationMs: number;
  error?: string;
}

/**
 * `cf-ray` looks like `9a1b2c3d4e5f6789-FRA`. The suffix is the IATA-ish code
 * of the data centre that served the request, and it is the only way to tell
 * whether the probe landed in the same place as the fill.
 */
export function coloFromRay(ray: string | null): string | undefined {
  if (!ray) return undefined;
  const dash = ray.lastIndexOf("-");
  if (dash === -1 || dash === ray.length - 1) return undefined;
  const colo = ray.slice(dash + 1).trim().toUpperCase();
  return /^[A-Z]{3}$/.test(colo) ? colo : undefined;
}

/** Cloudflare's own cf-cache-status values, which are already normalised. */
function fromCfCacheStatus(value: string): CacheState | undefined {
  switch (value.trim().toUpperCase()) {
    case "HIT":
      return "hit";
    case "MISS":
      return "miss";
    case "EXPIRED":
      return "expired";
    case "STALE":
      return "stale";
    case "REVALIDATED":
      return "revalidated";
    case "UPDATING":
      return "updating";
    case "BYPASS":
      return "bypass";
    case "DYNAMIC":
      return "dynamic";
    default:
      return undefined;
  }
}

/**
 * `x-cache` is used by Akamai, Fastly, CloudFront and Varnish with mutually
 * incompatible spellings. Fastly reports both tiers comma-separated
 * ("HIT, MISS"); the edge-facing value is the last one.
 */
function fromXCache(value: string): CacheState | undefined {
  const last = value.split(",").pop();
  if (!last) return undefined;
  const v = last.trim().toUpperCase();

  // Akamai: TCP_HIT, TCP_MEM_HIT, TCP_REFRESH_HIT, TCP_IMS_HIT, TCP_MISS.
  if (v.startsWith("TCP_")) {
    if (v.includes("REFRESH_HIT") || v.includes("IMS_HIT")) return "revalidated";
    if (v.includes("HIT")) return "hit";
    if (v.includes("MISS")) return "miss";
    return undefined;
  }

  // CloudFront: "Hit from cloudfront" / "Miss from cloudfront".
  // Varnish and Fastly: bare "HIT" / "MISS".
  if (v.includes("HIT")) return "hit";
  if (v.includes("MISS")) return "miss";
  if (v.includes("ERROR")) return "unknown";
  return undefined;
}

export function normaliseCacheState(headers: CacheHeaders): CacheState {
  if (headers.cfCacheStatus) {
    const state = fromCfCacheStatus(headers.cfCacheStatus);
    if (state) return state;
  }
  if (headers.xCache) {
    const state = fromXCache(headers.xCache);
    if (state) return state;
  }
  // No cache header at all, but a non-zero Age means something cached it.
  if (headers.age && Number(headers.age) > 0) return "hit";
  return "unknown";
}

export function readCacheHeaders(headers: Headers): CacheHeaders {
  return {
    cfCacheStatus: headers.get("cf-cache-status") ?? undefined,
    xCache: headers.get("x-cache") ?? undefined,
    age: headers.get("age") ?? undefined,
    cacheControl: headers.get("cache-control") ?? undefined,
    setCookie: headers.has("set-cookie"),
    vary: headers.get("vary") ?? undefined,
  };
}

/**
 * Why a page refused to cache. Surfacing this is the point of the whole
 * exercise: "47 of your pages cannot be cached, and here is why" is more
 * useful than a success count.
 */
export function explainNotCacheable(probe: RequestObservation): string {
  const { headers } = probe;
  const cc = headers.cacheControl?.toLowerCase() ?? "";

  if (cc.includes("no-store")) return "Cache-Control: no-store";
  if (cc.includes("private")) return "Cache-Control: private";
  if (cc.includes("max-age=0")) return "Cache-Control: max-age=0";
  if (headers.setCookie) return "Response sets a cookie";
  if (headers.vary === "*") return "Vary: *";
  if (probe.state === "dynamic") return "Zone does not cache this content type";
  if (probe.httpStatus >= 400) return `Origin returned HTTP ${probe.httpStatus}`;
  return "Cache miss on both requests, no explicit reason in headers";
}

/**
 * Classify a fill/probe pair.
 *
 * The colo check matters: Cloudflare's cache is per data centre, so a probe
 * served somewhere else says nothing about whether the fill worked. That is
 * reported as indeterminate rather than quietly counted as a failure.
 */
export function classify(
  fill: RequestObservation,
  probe: RequestObservation,
): { verdict: Verdict; reason?: string } {
  if (!fill.ok) return { verdict: "failed", reason: fill.error ?? `HTTP ${fill.httpStatus}` };
  if (!probe.ok) return { verdict: "failed", reason: probe.error ?? `HTTP ${probe.httpStatus}` };

  if (fill.state === "bypass" || probe.state === "bypass") {
    return { verdict: "bypassed", reason: explainNotCacheable(probe) };
  }
  if (probe.state === "dynamic") {
    return { verdict: "zone_not_caching", reason: explainNotCacheable(probe) };
  }

  if (fill.colo && probe.colo && fill.colo !== probe.colo) {
    return {
      verdict: "indeterminate",
      reason: `Fill served by ${fill.colo}, probe by ${probe.colo}`,
    };
  }

  const probeHit = probe.state === "hit" || probe.state === "revalidated" || probe.state === "updating";
  if (probeHit) {
    // A hit on the fill too means the cache was already warm when we arrived.
    return fill.state === "hit" ? { verdict: "already_warm" } : { verdict: "warmed" };
  }

  return { verdict: "not_cacheable", reason: explainNotCacheable(probe) };
}

/** Verdicts that mean the URL is served from cache after this run. */
export const SUCCESS_VERDICTS: ReadonlySet<Verdict> = new Set<Verdict>(["warmed", "already_warm"]);

export function isSuccess(verdict: Verdict): boolean {
  return SUCCESS_VERDICTS.has(verdict);
}
