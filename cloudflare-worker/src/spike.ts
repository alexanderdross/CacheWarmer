/**
 * The verification spike.
 *
 * This is the harness that decides whether the whole Workers approach is worth
 * building: it measures, against a real zone, the three things the design
 * assumes. It lives apart from the router because it is testable on its own —
 * index.ts re-exports the Durable Object and so cannot be imported outside the
 * Workers runtime.
 */

import { DEFAULT_WARM_OPTIONS, warmAndVerify } from "./warm";
import type { Env, Region } from "./config";

/**
 * Give a URL its own cache key.
 *
 * The measurements below must not warm each other: whichever ran first would
 * leave the cache hot and the next would report already_warm no matter what
 * the zone actually does.
 *
 * The caveat this introduces belongs in the result: a query string changes the
 * cache key, and a zone configured not to cache query-string URLs at all will
 * therefore look worse here than it is. If every measurement comes back
 * not_cacheable, suspect this before believing it.
 */
export function bust(url: string, tag: string): string {
  const busted = new URL(url);
  busted.searchParams.set("cw-spike", tag);
  return busted.href;
}

function summarise(result: Awaited<ReturnType<typeof warmAndVerify>>) {
  return {
    url: result.url,
    verdict: result.verdict,
    reason: result.reason,
    fillState: result.fill.state,
    probeState: result.probe.state,
    fillColo: result.fill.colo,
    probeColo: result.probe.colo,
    fillMs: result.fill.durationMs,
    probeMs: result.probe.durationMs,
  };
}

/**
 * Spike endpoint — answers the questions that gate the whole design:
 *
 *   1a. Does the zone cache HTML on its own?
 *   1b. Does the cf override make it cache when it otherwise would not?
 *   2.  Do fill and probe land in the same data centre?
 *   3.  Does locationHint move the work to another region?
 *
 * 1a and 1b are measured separately because they lead to opposite conclusions.
 * A single run with the cf object hard-coded — which is what this did before —
 * cannot tell "the zone caches" from "our override caches", and the second is
 * the one the design depends on.
 *
 * Deliberately synchronous and chatty: it exists to be read by a human once.
 */
export async function spike(env: Env, url: string, regions: Region[]) {
  const run = crypto.randomUUID().slice(0, 8);

  // No cf object: what an ordinary visitor's request would achieve.
  const plain = await warmAndVerify(bust(url, `${run}-plain`), DEFAULT_WARM_OPTIONS);

  // With the override that only applies to zones in this Worker's own account.
  const overridden = await warmAndVerify(bust(url, `${run}-cf`), {
    ...DEFAULT_WARM_OPTIONS,
    cf: { cacheEverything: true, cacheTtl: 300 },
  });

  const byRegion = await Promise.all(
    regions.map(async (region) => {
      // Named `spike:<region>` so /results?site=spike&region=<region> reaches
      // the same object. Including the URL in the name made every run create a
      // fresh, unaddressable object.
      const id = env.REGION_WARMER.idFromName(`spike:${region}`);
      const stub = env.REGION_WARMER.get(id, { locationHint: region });
      const target = bust(url, `${run}-${region}`);
      await stub.start({
        jobId: `spike-${run}-${region}`,
        siteId: "spike",
        region,
        urls: [target],
      });
      return { region, url: target, poll: `/results?site=spike&region=${region}` };
    }),
  );

  return {
    question1a_zoneCachesHtml: {
      ...summarise(plain),
      expectation:
        "'warmed' or 'already_warm' means the zone caches HTML by itself; " +
        "'zone_not_caching' is normal for a default zone and makes 1b the deciding measurement",
    },
    question1b_cfOverrideWorks: {
      ...summarise(overridden),
      expectation:
        "'warmed' means the cf override fills the edge cache. If this is not_cacheable " +
        "while 1a reported a clean result, the verification premise does not hold",
    },
    question2_sameColo: {
      plain: { fill: plain.fill.colo, probe: plain.probe.colo, agree: plain.fill.colo === plain.probe.colo },
      overridden: {
        fill: overridden.fill.colo,
        probe: overridden.probe.colo,
        agree: overridden.fill.colo === overridden.probe.colo,
      },
      expectation: "fill and probe must share a colo, or verification proves nothing",
    },
    question3_regions: {
      dispatched: byRegion,
      next: "poll each 'poll' URL and compare progress.colos across regions; identical colos mean locationHint did nothing",
    },
    caveat:
      "Each measurement uses a ?cw-spike= cache buster so the runs stay independent. " +
      "A zone that does not cache query-string URLs will look worse here than it is.",
  };
}

