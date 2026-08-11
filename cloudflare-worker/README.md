# CacheWarmer Edge

Cache warming on Cloudflare Workers, with **verified** edge cache fills.

Unlike the other CacheWarmer editions, this one does not treat an HTTP 200 as
success. Every URL is warmed with a fill request and then confirmed with a
probe request, and the pair is classified — so a run reports *"187 of 200 pages
are provably in the edge cache, and here is why the other 13 are not"* rather
than a request count.

Status: **first increment.** The warm/verify core, sitemap parsing, purge and
the regional fan-out are implemented and unit-tested. Nothing has been deployed
or measured against a live zone yet — see [Spike](#spike-run-this-first).

See [`../docs/CLOUDFLARE-WORKERS-EVALUATION.md`](../docs/CLOUDFLARE-WORKERS-EVALUATION.md)
for why this exists and what it is expected to be worth.

---

## Why one deploy per account

Cloudflare drops the `cf` cache-control object across account boundaries:

> Workers operating on behalf of different zones cannot affect each other's
> cache. […] that zone fully controls how its own content is cached within
> Cloudflare; you cannot override it.

Warming, `cf-cache-status` verification and purge all still work across
accounts. Only `cacheEverything`, `cacheTtl`, `cacheKey` and `cacheTags` do
not. Since all three accounts here need warming, each gets its own deploy of
the same codebase rather than two of them running permanently in the weaker
mode.

| Account | Account ID | Role |
|---|---|---|
| `mail@drossmedia.de` | `e8d2e50aa1f1d61d8c68ba490d7cdec1` | Warmer **+ hub** (D1, `/report`) |
| `alexander.dross@me.com` | `c1df5fbc1f923a0ea0a81889306082c5` | Warmer (satellite) |
| `webmaster@trade.aero` | `34d3b942d12ae7cbcf19142fead79259` | Warmer (satellite) |

Account IDs are identifiers, not secrets — they belong in checked-in wrangler
config, where a pinned `account_id` stops a deploy landing in the wrong place.

## Why it runs on the free plan

Workers Free allows **50 external subrequests** per invocation and the cap
cannot be raised. A warm costs two (fill + probe), so a chunk is **25 URLs**.
The limit is per *invocation*, and a Durable Object alarm is its own
invocation with a fresh budget — so work is chained across alarms rather than
run in one pass. That also makes a run resumable.

Durable Objects with the SQLite backend are available on Free, so the regional
fan-out costs nothing either. The constraint that actually bites first is the
**10 ms CPU limit** per invocation, not the subrequests.

---

## Layout

```
src/
├── verdict.ts        Cache-state normalisation and fill/probe classification
├── warm.ts           warmAndVerify — the core; two subrequests per URL
├── sitemap.ts        Sitemap + sitemap-index parsing via HTMLRewriter
├── purge.ts          Cloudflare purge, batched at 100, paced per account plan
├── region-warmer.ts  Durable Object; chains 25-URL chunks across alarms
├── config.ts         Site config, bindings, budget constants
└── index.ts          Cron, HTTP endpoints, spike harness
deploy/
├── drossmedia/       hub  — pins account_id, binds D1
├── privat/           satellite
└── trade-aero/       satellite
```

## Endpoints

All except `/health` and `/report` require `Authorization: Bearer $ADMIN_TOKEN`.

| Method | Path | Purpose |
|---|---|---|
| GET | `/health` | Liveness |
| GET | `/status` | Per-site, per-region job progress |
| GET | `/results?site=&region=` | Per-URL detail, failures first |
| POST | `/warm[?site=]` | Trigger a run now |
| GET | `/spike?url=&regions=` | Verification harness, see below |
| POST | `/report` | Hub only: satellites push job summaries here |

## Verdicts

| Verdict | Meaning |
|---|---|
| `warmed` | Fill missed, probe hit. The work did something. |
| `already_warm` | Hit on both. Nothing to do, still fine. |
| `not_cacheable` | Two requests, still no hit — with the reason from the headers. |
| `bypassed` | A rule, cookie or `Cache-Control` skipped the cache. |
| `zone_not_caching` | Zone returns `DYNAMIC` for this content. |
| `indeterminate` | Fill and probe hit different data centres, so it proves nothing. |
| `failed` | The request itself failed. |

Note that `MISS` on a fill request is the *success* signal, not a warning — the
Node module's dashboard has this backwards.

---

## Setup

```sh
npm install
npm test
npm run typecheck
```

Per account, once:

```sh
wrangler auth create drossmedia            # pick the account in the OAuth flow
wrangler auth activate drossmedia .

wrangler d1 create cachewarmer-reports     # hub only; paste the id into the config
wrangler secret put ADMIN_TOKEN --config deploy/drossmedia/wrangler.jsonc
wrangler secret put HUB_SECRET --config deploy/drossmedia/wrangler.jsonc
wrangler secret put CF_PURGE_TOKEN --config deploy/drossmedia/wrangler.jsonc
```

Then fill in the `REPLACE_WITH_*` placeholders in each
`deploy/*/wrangler.jsonc` — zone IDs, the D1 database id, and the hub hostname
the satellites report to.

```sh
npm run deploy:drossmedia
```

## Spike: run this first

Before deploying all three, answer the questions the design rests on:

```sh
curl -H "Authorization: Bearer $ADMIN_TOKEN" \
  "https://<worker>/spike?url=https://example.com/&regions=weur,enam"
```

1. **Does a Worker `fetch()` actually fill the edge cache?**
   `question1_fillWorks.verdict` should be `warmed`. If it is `not_cacheable`
   on a page that is plainly cacheable, the whole verification premise fails.
2. **Do fill and probe land in the same data centre?**
   `question2_sameColo.agree` must be `true`, or verification proves nothing.
3. **Does `locationHint` move the work?** Poll `/status` afterwards and compare
   the `colos` reported per region. If they are identical, the regional
   fan-out — the only thing Workers uniquely adds — is not real.

Also worth measuring before investing further: the TTFB difference between
cold, upper-tier-warm and lower-tier-warm. That number decides whether
multi-region warming is worth any effort at all.

## Not implemented yet

- **Asset warming** via HTMLRewriter (extract `<link>`/`<script>`/`<img>` and
  fetch them). This is what replaces Puppeteer's only real benefit.
- **Smart warming** — `changedSince` exists and is tested, but nothing persists
  the previous run's `lastmod` map yet.
- **Non-Cloudflare purge** (Imperva, Akamai, Fastly). When porting Akamai
  EdgeGrid from the Node module, note the two traps recorded in the evaluation:
  the timestamp must keep the colons in the time portion, and the second HMAC
  must be keyed on the base64 *string*, not its decoded bytes.
- **Workflows.** Durable Object alarms are used instead, because the docs are
  ambiguous about whether a Workflow step gets a fresh subrequest budget —
  which on Free decides between workable and not.
