# Plan: Schema Validation Gate for CacheWarmer (scoped to TradeAero detail pages)

## Context

TradeAero-Refactor emits JSON-LD structured data server-side on four high-value detail templates: **aircraft detail (`/aircraft/[id]`)**, **job detail (`/jobs/[slug]`)**, **event detail (`/events/[slug]`)**, and **parts detail (`/parts/listing/[id]` and `/parts/wanted/[id]`)**. Bad or missing schema markup on these pages silently hurts Google rich results (Product cards, Google Jobs entries, Event carousels) — and there is currently no signal in the build or warming pipeline that flags it.

CacheWarmer already fetches every URL with Puppeteer before reporting cache status. We will piggyback on that fetch to validate the page's structured data against Google's Rich Results rules, persist the results to TradeAero's Supabase Postgres (so the website team can query them alongside listing data), and (for the narrow class of safely repairable issues) open issues — and optionally draft PRs — on `alexanderdross/tradeaero-refactor`. The goal: never warm a stale cache of a page whose schema is silently broken, and route every real validation error to a tracked GitHub issue automatically.

## User decisions (confirmed)

- **Scope:** Validate only URLs matching `/aircraft/[id]`, `/jobs/[slug]`, `/events/[slug]`, `/parts/listing/[id]`, `/parts/wanted/[id]` (any locale prefix). Skip everything else.
- **Validator:** `structured-data-testing-tool` (npm, local, Google presets). No Google API auth required.
- **Severity:** Only Google-Rich-Results **errors** open issues. Warnings are persisted but silent.
- **Auto-fix:** OFF by default. When on, opens a **draft PR** on `claude/schema-validation-cache-warmer-ZQNkw` for the narrow allowlist only.
- **Locale dedup:** Strip locale prefix before fingerprinting, so `/de/jobs/x` and `/en/jobs/x` collapse to one issue.
- **Persistence:** Validation results live in **TradeAero's Supabase Postgres** (table `schema_validation_results` in the `public` schema), applied via the Supabase MCP `apply_migration`. CacheWarmer writes to it using `@supabase/supabase-js` with a service-role key.

## Architecture

```
processJob (job-manager.ts)
  └─ warmUrls (cdn-warmer.ts)
        └─ warmSingleUrl  ──[viewport=desktop, status=200, URL matches scoped patterns]──►
              page.content() → html (in-memory only, never persisted)
              ↓
        schema-validator queue (p-limit, concurrency 4)
              ↓
        SDTT validate(html) with Google preset
              ↓
        write row → schema_validation_results (SQLite)
              ↓
        if severity=error and github.enabled:
            github-issue-reporter
              ├─ search open issues by label schema-fp:<hash> → skip if found
              ├─ create issue with labels [schema-validation, automated, schema-fp:<hash>]
              └─ if autoFix.enabled and fix on allowlist:
                    auto-fixer generates patch
                    → octokit-plugin-create-pull-request opens draft PR
                       on branch claude/schema-validation-cache-warmer-ZQNkw
```

The HTML stays in process memory: extracted in `warmSingleUrl`, handed to the validator, dropped. Nothing about page content lands in SQLite.

## URL scoping (the new piece)

A small helper decides whether a URL is in scope and classifies which detail template it hit. Lives in `src/lib/services/schema-validator/url-scope.ts`:

```ts
// Matches detail templates with optional locale prefix
const SCOPE = /^\/(?:[a-z]{2}\/)?(aircraft\/[^/]+|jobs\/[^/]+|events\/[^/]+|parts\/listing\/[^/]+|parts\/wanted\/[^/]+)\/?$/i;
export type PageType = 'aircraft' | 'job' | 'event' | 'parts-listing' | 'parts-wanted';
export function isInSchemaScope(url: string): boolean { /* parse + test */ }
export function classifyPageType(url: string): PageType | null { /* return discriminator */ }
export function canonicalizeForFingerprint(url: string): string { /* strip locale + trailing slash */ }
```

Both `warmSingleUrl` (decides whether to capture HTML at all) and the GitHub reporter (fingerprint, label, page-type column) use this module.

## Critical files

### New files

| Path | Purpose |
|---|---|
| `nodejs-docker/src/lib/services/schema-validator/url-scope.ts` | Detail-page URL matcher + locale-stripping canonicalizer |
| `nodejs-docker/src/lib/services/schema-validator/types.ts` | `SchemaValidator`, `ValidationReport`, `ValidationIssue` interfaces |
| `nodejs-docker/src/lib/services/schema-validator/extractor.ts` | `extractJsonLd(html)` via cheerio |
| `nodejs-docker/src/lib/services/schema-validator/sdtt-validator.ts` | SDTT wrapper, fingerprint computation |
| `nodejs-docker/src/lib/services/schema-validator/index.ts` | Per-job queue + `validateBatch`, `drain` |
| `nodejs-docker/src/lib/services/schema-validator/auto-fixer.ts` | Pure functions returning unified diffs for the safe allowlist |
| `nodejs-docker/src/lib/services/github-issue-reporter.ts` | Octokit wrapper: dedup search, issue create, draft-PR create |
| `nodejs-docker/src/lib/services/schema-validator/supabase-store.ts` | Supabase client wrapper: `upsertResult`, `findByFingerprint`. Uses `@supabase/supabase-js`. |
| `nodejs-docker/src/app/api/jobs/[id]/schema-validation/route.ts` | `GET` endpoint that proxies a query to Supabase for per-job validation rows |
| `nodejs-docker/src/app/api/schema-validation/fix/route.ts` | `POST` endpoint that triggers the auto-fixer for a given `pageType` section (aircraft \| job \| event \| parts-listing \| parts-wanted), iterates the latest fail-status rows for that section, and opens a single batched draft PR with all safe fixes. Authenticated via the CacheWarmer API key. |
| `nodejs-docker/src/components/SchemaValidationPanel.tsx` | Tab inside CacheWarmer's own `JobDetail.tsx` (per-job drilldown, separate from the TradeAero admin GUI) |
| `nodejs-docker/tests/unit/services/schema-extractor.test.ts` | Fixture-driven extractor tests |
| `nodejs-docker/tests/unit/services/sdtt-validator.test.ts` | Golden ValidationReport tests for Aircraft/Job/Event pages |
| `nodejs-docker/tests/unit/services/url-scope.test.ts` | Matcher + canonicalizer tests |
| `nodejs-docker/tests/unit/services/github-issue-reporter.test.ts` | Mocked Octokit (issue dedup, draft-PR path) |
| `nodejs-docker/tests/unit/services/auto-fixer.test.ts` | Patch generation per allowlisted rule |
| `nodejs-docker/tests/integration/schema-validation-flow.test.ts` | `processJob` E2E with mock validator + mock Octokit |
| `nodejs-docker/tests/fixtures/jsonld-aircraft.html` | Real aircraft detail HTML snapshot |
| `nodejs-docker/tests/fixtures/jsonld-job.html` | Real job detail HTML snapshot |
| `nodejs-docker/tests/fixtures/jsonld-event.html` | Real event detail HTML snapshot |

### Existing files to edit

| Path | Edit |
|---|---|
| **Supabase (TradeAero project) — applied via `mcp__supabase__apply_migration`** | Create table `schema_validation_results` + indexes (see §SQL below). Migration name: `create_schema_validation_results`. No changes to CacheWarmer's local SQLite schema. |
| `nodejs-docker/src/lib/services/cdn-warmer.ts` (`warmSingleUrl`, line 55) | After `response = await page.goto(...)` resolves with status 2xx, if `viewport === 'desktop'` AND `isInSchemaScope(url)`, set `__html = await page.content()` on the returned `WarmResult`. Field is `__`-prefixed because it is transient and never persisted. |
| `nodejs-docker/src/lib/services/cdn-warmer.ts` (`WarmResult` interface, line 34) | Add `__html?: string` (non-persisted, in-memory only). |
| `nodejs-docker/src/lib/queue/job-manager.ts` (callback at line 173) | When `result.__html` is set, enqueue `{jobId, url, html: result.__html}` onto the schema-validator queue, then null out `result.__html` before saving the url_result. After `closeBrowser()` at line 181, `await schemaValidator.drain(jobId)` and `await githubReporter.flush(jobId)` (both no-ops if disabled). |
| `nodejs-docker/src/lib/queue/job-manager.ts` (`saveUrlResult` signature/call site at line 89, 174) | Do not store `__html`; existing `cache_headers` JSON payload is untouched. |
| `nodejs-docker/src/lib/config.ts` (`AppConfig` interface) | Add `schemaValidation` block (see §config YAML). Resolve `github.tokenEnv` via `process.env[name]` at load time. |
| `nodejs-docker/config.yaml` | Append `schemaValidation:` block with defaults disabled (see §config YAML). |
| `nodejs-docker/src/components/JobDetail.tsx` | Add a "Schema Validation" tab that fetches `/api/jobs/[id]/schema-validation` and renders `<SchemaValidationPanel/>`. |
| `nodejs-docker/package.json` | Add deps: `structured-data-testing-tool`, `@octokit/rest`, `octokit-plugin-create-pull-request`, `p-limit`, `cheerio`, `@supabase/supabase-js` (verify not already transitively present). |
| **TradeAero** `src/components/dashboard/admin/AdminCrawlerTab.tsx` | Append a new `<SchemaValidationSection/>` block (collapsible card) below the existing crawler sections. Renders five sub-cards — one per `page_type` (Aircraft, Job, Event, Parts Listing, Parts Wanted) — each with: fail/warn counts, the top 5 unique fingerprints (linked to the GitHub issues), and a **Run fixes** button. Button calls `POST /api/admin/schema-validation/fix` with `{ pageType }` and toasts the resulting PR URL. |
| **TradeAero** `src/app/api/admin/schema-validation/route.ts` (new) | `GET` aggregates from Supabase `schema_validation_results` grouped by `page_type`, returning counts + sample rows. Uses `verifyAdminAuth` and the service-role client like `/api/admin/cachewarmer/route.ts`. |
| **TradeAero** `src/app/api/admin/schema-validation/fix/route.ts` (new) | `POST { pageType }` — proxies to CacheWarmer's `/api/schema-validation/fix` with the CacheWarmer API key. CSRF-protected via `requireCsrfHeader`. Writes an entry to `admin_activity_logs` (mirroring the cachewarmer route pattern at line 158–166). |
| **TradeAero** `src/components/dashboard/admin/AdminTabsNav.tsx` | No edit needed — Crawler tab already exists. Only the tab content (`AdminCrawlerTab.tsx`) gains a new section. |

### Files NOT to modify

TradeAero-Refactor source is untouched by this plan. The Cache Warmer only **reads** rendered HTML and **files issues / draft PRs** against the GitHub repo. Any code changes to `AircraftJsonLd.tsx`, `job-jsonld.ts`, `event-jsonld.ts` happen via the draft-PR mechanism, reviewed by a human before merge.

## SQL — `schema_validation_results` table (Supabase Postgres)

Applied via `mcp__supabase__apply_migration` against TradeAero's Supabase project (project id resolved via `mcp__supabase__list_projects` at execution time). Migration name: `create_schema_validation_results`.

```sql
-- Page-type enum
DO $$ BEGIN
  CREATE TYPE schema_page_type AS ENUM ('aircraft','job','event','parts-listing','parts-wanted');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE TYPE schema_validation_status AS ENUM ('pass','warn','fail');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

CREATE TABLE IF NOT EXISTS public.schema_validation_results (
  id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  job_id          text NOT NULL,                 -- CacheWarmer job UUID (text; lives in SQLite, not FK-linked)
  url             text NOT NULL,
  canonical_url   text NOT NULL,                 -- locale-stripped
  page_type       schema_page_type NOT NULL,
  schema_type     text NOT NULL,                 -- 'Product' | 'JobPosting' | 'Event' | ...
  status          schema_validation_status NOT NULL,
  error_count     integer NOT NULL DEFAULT 0,
  warning_count   integer NOT NULL DEFAULT 0,
  raw_errors      jsonb,                         -- ValidationIssue[]
  fingerprint     text NOT NULL,                 -- sha256(canonical_url + '|' + schema_type + '|' + sortedErrorCodes)
  github_issue_number integer,
  github_pr_number    integer,
  created_at      timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_schema_val_job ON public.schema_validation_results(job_id);
CREATE INDEX IF NOT EXISTS idx_schema_val_fp  ON public.schema_validation_results(fingerprint);
CREATE UNIQUE INDEX IF NOT EXISTS idx_schema_val_fp_job ON public.schema_validation_results(job_id, fingerprint);

-- RLS: lock down; only service-role writes, anon/authenticated can read for the future dashboard.
ALTER TABLE public.schema_validation_results ENABLE ROW LEVEL SECURITY;
CREATE POLICY "service writes" ON public.schema_validation_results FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "authenticated reads" ON public.schema_validation_results FOR SELECT TO authenticated USING (true);
```

Pre-flight check before applying: run `mcp__supabase__list_tables` to verify no collision with an existing `schema_validation_results`. Run `mcp__supabase__get_advisors` after applying to confirm no new RLS or security warnings.

## `config.yaml` additions

```yaml
schemaValidation:
  enabled: false                      # opt-in
  validationEngine: "sdtt"
  presets: ["Google", "SchemaOrg"]
  scopedPaths:                        # regex patterns this validator runs against
    - "^/(?:[a-z]{2}/)?aircraft/[^/]+/?$"
    - "^/(?:[a-z]{2}/)?jobs/[^/]+/?$"
    - "^/(?:[a-z]{2}/)?events/[^/]+/?$"
    - "^/(?:[a-z]{2}/)?parts/listing/[^/]+/?$"
    - "^/(?:[a-z]{2}/)?parts/wanted/[^/]+/?$"
  concurrency: 4                      # separate from cdnWarming.concurrency
  severityThreshold: "error"          # error | warning
  supabase:
    urlEnv: "SUPABASE_URL"                   # env var name
    serviceRoleKeyEnv: "SUPABASE_SERVICE_ROLE_KEY"
    table: "schema_validation_results"
  github:
    enabled: false
    tokenEnv: "GITHUB_TOKEN"          # env var name; never put the raw token in YAML
    repoOwner: "alexanderdross"
    repoName: "tradeaero-refactor"
    labels: ["schema-validation", "automated"]
    issueAssignees: []
    maxIssuesPerJob: 30               # safety cap
    reopenClosedWithinDays: 30
  autoFix:
    enabled: false                    # default OFF
    branch: "claude/schema-validation-cache-warmer-ZQNkw"
    baseBranch: "main"
    draftPr: true
    allowedFixes:
      - missing-context
      - http-to-https-context
      - type-literal-case
      - missing-organization-name
```

## Auto-fix allowlist (narrow & safe)

| Rule | Detects | Patch |
|---|---|---|
| `missing-context` | JSON-LD block with no `@context` | Insert `"@context": "https://schema.org"` |
| `http-to-https-context` | `@context: "http://schema.org"` | Rewrite to `https://schema.org` |
| `type-literal-case` | `@type` value misspelled or wrong case vs. Schema.org canonical (e.g., `"product"`, `"jobposting"`) | Replace with canonical literal (`Product`, `JobPosting`, `Event`) |
| `missing-organization-name` | Publisher/Organization node missing `name` | Inject site title from `<title>` tag |

Everything else (missing fields, missing schema blocks entirely, business-data fixes) only files an issue — no PR.

The auto-fixer never executes git directly; it emits a unified diff string against the relevant source file in TradeAero-Refactor (`src/components/aircraft/AircraftJsonLd.tsx`, `src/lib/seo/job-jsonld.ts`, `src/lib/seo/event-jsonld.ts`, and the parts-listing/parts-wanted JSON-LD emitter — to be located in `src/components/parts/` or `src/lib/seo/` during implementation) and lets `octokit-plugin-create-pull-request` create the branch and PR via the GitHub API.

> **Note on parts schema:** Exploration confirmed Product JSON-LD exists for aircraft and rentals but parts emitters were not surfaced. Step 1 of implementation is to grep TradeAero-Refactor for parts JSON-LD; if absent, the very first validation run will surface "no structured data" errors on parts pages and the fix will be a human task (adding a `PartsJsonLd.tsx` component), not auto-fixable.

## Admin GUI (TradeAero → Dashboard → Crawler tab)

Lives inside the existing `AdminCrawlerTab.tsx` as a new collapsible section, **"Schema Markup Health"**, placed below the existing crawler-run history. It does **not** create a new tab — the Crawler tab already exists in `AdminTabsNav.tsx` and is the user's chosen home for this.

### Layout

```
┌─ Schema Markup Health ───────────────────────────────────────── [Refresh]
│  Last validated: 2 min ago · Job #abc123 · 312 URLs scanned
│
│  ┌─ Aircraft detail ────────────────────────────────────────┐
│  │ ✓ 87 pass   ⚠ 3 warn   ✗ 12 fail                          │
│  │ Top issues:                                                │
│  │  • Product.offers.priceCurrency missing  (4 URLs)  #142 ↗ │
│  │  • Product.image format invalid          (3 URLs)  #143 ↗ │
│  │  • @context using http://                (5 URLs)  #144 ↗ │
│  │                              [ Run fixes →  draft PR ]    │
│  └────────────────────────────────────────────────────────────┘
│  ┌─ Job detail ───────────────────────────────────────────────┐ … same shape
│  ┌─ Event detail ─────────────────────────────────────────────┐ … same shape
│  ┌─ Parts (listing) ──────────────────────────────────────────┐ … same shape
│  ┌─ Parts (wanted) ───────────────────────────────────────────┐ … same shape
└────────────────────────────────────────────────────────────────────────────
```

### Per-section "Run fixes" button behaviour

1. Confirms via shadcn `<AlertDialog>` ("Open draft PR with N proposed fixes for {section}?").
2. On confirm, `POST /api/admin/schema-validation/fix` with `{ pageType }` and CSRF header `X-Requested-With: XMLHttpRequest`.
3. TradeAero API verifies admin auth, forwards to CacheWarmer's `/api/schema-validation/fix` with the CacheWarmer API key.
4. CacheWarmer pulls the latest `fail`-status rows for that `page_type`, filters to fingerprints whose top error is on the auto-fix allowlist, generates a single batched patch, opens **one draft PR** on `claude/schema-validation-cache-warmer-ZQNkw`, and returns the PR URL.
5. TradeAero toasts the PR URL via `sonner` and logs the action to `admin_activity_logs` (same pattern as `/api/admin/cachewarmer/route.ts` lines 158–166).
6. Button is **disabled** when (a) zero fixable fingerprints exist for the section, or (b) `cachewarmer_config.schemaValidation.autoFix.enabled === false` in the existing `cachewarmer_config` Supabase table.

### Data source

The GUI reads from `schema_validation_results` (Supabase) via `GET /api/admin/schema-validation`. The query:

```sql
SELECT page_type,
       COUNT(*) FILTER (WHERE status = 'pass') AS pass_count,
       COUNT(*) FILTER (WHERE status = 'warn') AS warn_count,
       COUNT(*) FILTER (WHERE status = 'fail') AS fail_count
FROM public.schema_validation_results
WHERE created_at > now() - interval '24 hours'
GROUP BY page_type;
```

Plus a second query for the top-5 fingerprints per page_type with their linked GitHub issue numbers (rendered as `#142 ↗` deep-link to `https://github.com/alexanderdross/tradeaero-refactor/issues/142`).

### Wiring into the existing tab

`AdminCrawlerTab.tsx` already pre-fetches `crawlerRuns` server-side (CLAUDE.md: "pre-fetched server-side and passed as props for instant render"). Mirror that pattern: `src/app/[locale]/dashboard/admin/page.tsx` pre-fetches the Schema Markup Health aggregate and passes it as `initialSchemaHealth` prop to `<AdminCrawlerTab/>`. Inside the tab, a `useQuery` keyed on `['schema-health']` with a 2-minute stale time backs the Refresh button.

### Reused TradeAero patterns

- Auth: `verifyAdminAuth` + `requireCsrfHeader` from `src/lib/adminAuth.ts` (already used by the CacheWarmer admin route).
- Supabase service-role client: `createServiceClient()` pattern from `src/app/api/admin/cachewarmer/route.ts:27`.
- Audit logging: `admin_activity_logs` insert pattern from `src/app/api/admin/cachewarmer/route.ts:158`.
- UI primitives: `Card`, `Button`, `Badge`, `AlertDialog`, `Tooltip` from `src/components/ui/*` (already imported by `AdminCrawlerTab.tsx`).
- Toast: `sonner` (already imported at `AdminCrawlerTab.tsx:56`).

## Reused components

- **Puppeteer Page** already created in `warmSingleUrl` (cdn-warmer.ts:55) — reused for `page.content()`. No second fetch.
- **Pino logger** at `src/lib/logger.ts` — reused for validator logs.
- **YAML config loader** at `src/lib/config.ts` — extended, not replaced.
- **TradeAero Supabase** (existing Postgres) — new table written via service-role key.
- **`uuidv4()` ID generation pattern** from `job-manager.ts` — used for the validator's in-memory job ID linkage; the Supabase `id` column itself uses `gen_random_uuid()`.
- **Vitest setup** under `nodejs-docker/tests/` — new tests follow existing structure.

## Verification

1. `mcp__supabase__list_projects` → pick the TradeAero project id. `mcp__supabase__list_tables` to confirm no `schema_validation_results` collision.
2. `mcp__supabase__apply_migration` with the §SQL block above. Then `mcp__supabase__get_advisors` to confirm zero new security/perf warnings.
3. `cd /home/user/CacheWarmer/nodejs-docker && pnpm install` — installs new deps (`structured-data-testing-tool`, `@octokit/rest`, `octokit-plugin-create-pull-request`, `p-limit`, `cheerio`, `@supabase/supabase-js`).
4. `pnpm test:unit -- schema` — all new unit tests pass against captured HTML fixtures for aircraft / job / event / parts-listing / parts-wanted pages.
5. `pnpm test:integration -- schema-validation-flow` — full `processJob` exercise with mocked validator, mocked Octokit, and a stubbed Supabase client; asserts (a) dedup hit on second run, (b) issue payload shape, (c) draft-PR path is reached only when `autoFix.enabled`.
6. Start TradeAero-Refactor locally: `cd /home/user/TradeAero-Refactor && pnpm dev` (port 3000).
7. In CacheWarmer, create `config.local.yaml` with `schemaValidation.enabled: true`, `github.enabled: false` for first dry run. Set `SUPABASE_URL` + `SUPABASE_SERVICE_ROLE_KEY` env vars. `pnpm dev`, then `POST /api/warm` against the local sitemap.
8. `mcp__supabase__execute_sql` with `SELECT page_type, status, COUNT(*) FROM schema_validation_results WHERE job_id=$1 GROUP BY 1,2;` — confirm rows exist for aircraft / job / event / parts URLs only; other URLs are skipped. Same data also exposed via `GET /api/jobs/{id}/schema-validation`.
9. Flip `github.enabled: true` against a **sandbox repo** (not `tradeaero-refactor` until verified). Set `GITHUB_TOKEN` env var (scope: `repo`, `issues:write`). Re-warm. Confirm one GitHub issue per unique fingerprint; second run is a silent no-op.
10. Plant a known fixable bug — e.g., edit `AircraftJsonLd.tsx` to set `@context: "http://schema.org"`. Flip `autoFix.enabled: true`. Warm. Confirm draft PR appears on the sandbox repo on `claude/schema-validation-cache-warmer-ZQNkw` with a one-line diff fixing the context URL.
11. **GUI smoke test:** Open TradeAero `/dashboard/admin` → Crawler tab. Scroll to the new **Schema Markup Health** section. Confirm the five per-section cards render with realistic counts. Click **Run fixes** on the Aircraft card; confirm the AlertDialog, then the toast with the PR URL. Inspect the PR on GitHub (also reachable via `mcp__github__pull_request_read`).
12. Verify GitHub MCP tools (`mcp__github__list_issues`, `mcp__github__pull_request_read`) can read the created issue/PR for inspection during dev.
13. Audit log check: query `admin_activity_logs` for `target_type = 'schema-validation'` rows confirming the Run-fixes action was recorded.

## Out of scope (deliberate)

- Validating non-detail pages (homepage, hub, sitemap, etc.). Adding them later is a one-line addition to `scopedPaths`.
- The Google Rich Results Test API. Architecture leaves a `SchemaValidator` interface so a `RichResultsApiValidator` can drop in later if OAuth becomes available.
- Modifying TradeAero source directly. All site fixes go through the draft-PR mechanism with human review.
- Auto-merging PRs. Draft only.
