# CacheWarmer — Repository Knowledge Base

## Overview

CacheWarmer is a self-hosted microservice that takes XML sitemaps and systematically warms all contained URLs across CDN edge caches, social media scraper caches (Facebook, LinkedIn, Twitter/X, Pinterest), and search engines (Google, Bing via IndexNow). It also validates structured data (schema.org) on every page before warming and supports direct CDN cache purging via Cloudflare, Imperva, and Akamai APIs.

The product is commercially distributed in three tiers: **Free**, **Premium**, and **Enterprise**.

**Product Website:** https://cachewarmer.drossmedia.de
**Author:** Alexander Dross / Dross:Media

---

## Repository Components

This monorepo contains **6 components**:

| # | Component | Location | Tech Stack | Status |
|---|-----------|----------|------------|--------|
| 1 | **WordPress Theme** (marketing website) | `theme/wp-content/themes/cachewarmer/` | PHP, WordPress, Stripe | v2.4.1 |
| 2 | **CacheWarmer WordPress Plugin** | `wordpress-plugin/cachewarmer/` | PHP 8.0+, WordPress 6.0+ | v1.1.1 |
| 3 | **CacheWarmer Drupal Module** | `drupal-module/cachewarmer/` | PHP 8.1+, Drupal 10/11 | v1.1.1 |
| 4 | **CacheWarmer Node.js / Docker Module** | `nodejs-docker/` | Next.js 16, TypeScript, React 19, Tailwind CSS 4 | v1.1.1 |
| 5 | **License Manager Plugin** | `cachewarmer-license-manager/` | PHP 8.0+, WordPress 6.0+, Stripe | v1.0.1 |
| 6 | **CacheWarmer Edge** (Cloudflare Worker) | `cloudflare-worker/` | TypeScript, Workers, Durable Objects, D1 | v0.1.0 |

> **License Management Architecture:** The standalone `cachewarmer-license-manager` WordPress plugin (`cwlm/v1` API namespace, 6 MySQL tables, Stripe integration, Admin UI) handles all license CRUD, activation/deactivation, heartbeat checks, and Stripe webhooks. The theme's `functions.php` provides a lightweight Stripe Checkout integration for the pricing page. License *validation* logic lives inside each platform module (WordPress plugin's `class-cachewarmer-license.php`, Drupal module's `CacheWarmerLicense.php`).

---

## 1. WordPress Theme (Marketing Website)

**Path:** `theme/wp-content/themes/cachewarmer/`
**Website concept & IA:** `theme/WEBSITE.md`

### Purpose
Marketing/sales website for cachewarmer.drossmedia.de with integrated Stripe payment processing and license key generation.

### Theme Details
- **Theme Name:** CacheWarmer
- **Version:** 2.4.1
- **License:** MIT
- **Text Domain:** cachewarmer
- **Fonts:** Inter (400, 500), Outfit (600, 700) — self-hosted WOFF2

### Key Files
| File | Purpose |
|------|---------|
| `functions.php` | Theme setup, Stripe checkout/webhooks, license generation, Schema.org markup, WP bloat removal |
| `front-page.php` | Homepage template |
| `header.php` / `footer.php` | Global header/footer |
| `page-pricing.php` | Pricing page with tier comparison |
| `page-features.php` | Feature showcase |
| `page-wordpress.php` | WordPress plugin page |
| `page-drupal.php` | Drupal module page |
| `page-self-hosted.php` | Self-hosted (Node.js/Docker) page |
| `page-docs.php` | Documentation page |
| `page-api-keys.php` | API keys setup guide |
| `page-changelog.php` | Changelog page |
| `page-checkout-success.php` | Post-purchase page |
| `page-enterprise.php` | Enterprise plan page |
| `inc/template-tags.php` | Template helper functions |

### Stripe Integration (in functions.php)
- AJAX-based Stripe Checkout session creation
- Webhook handlers for: `checkout.session.completed`, `invoice.payment_succeeded`, `customer.subscription.deleted`, `charge.refunded`, `charge.dispute.created`
- Automatic license key generation and email delivery
- Custom DB table: `wp_cwlm_licenses`

### Assets
- `assets/css/main.css` — Main stylesheet
- `assets/js/main.js` — Client-side JavaScript
- `assets/fonts/` — Self-hosted WOFF2 fonts
- `assets/images/` — Favicons, logos, OG images

---

## 2. CacheWarmer WordPress Plugin

**Path:** `wordpress-plugin/cachewarmer/`
**Version:** 1.1.1
**Requires:** WordPress 6.0+, PHP 8.0+
**License:** GPL v2+

### Architecture
Singleton-pattern main class (`CacheWarmer`) that initializes all subsystems.

### File Structure
```
wordpress-plugin/cachewarmer/
├── cachewarmer.php                          # Plugin entry point, activation/deactivation hooks
├── uninstall.php                            # Cleanup on uninstall
├── includes/
│   ├── class-cachewarmer.php                # Main class (singleton), default options
│   ├── class-cachewarmer-database.php       # SQLite DB abstraction (3 tables)
│   ├── class-cachewarmer-job-manager.php    # Job orchestration, license limit enforcement
│   ├── class-cachewarmer-license.php        # HMAC-based license validation, feature gating
│   ├── class-cachewarmer-rest-api.php       # REST API (cachewarmer/v1 namespace)
│   ├── class-cachewarmer-sitemap-parser.php # XML sitemap parser (recursive)
│   ├── class-cachewarmer-scheduler.php      # WP-Cron scheduled warming
│   ├── class-cachewarmer-publish-hook.php   # Auto-warm on post publish (Premium+)
│   ├── class-cachewarmer-sitemap-detector.php # Auto-detect local sitemaps
│   ├── class-cachewarmer-webhooks.php       # Webhook notifications
│   ├── class-cachewarmer-email.php          # Email notifications (Enterprise)
│   ├── admin/
│   │   └── class-cachewarmer-admin.php      # Admin menu, AJAX handlers, asset enqueueing
│   └── services/
│       ├── class-cachewarmer-cdn-warmer.php         # CDN edge warming (wp_remote_get)
│       ├── class-cachewarmer-cdn-purge-warmer.php   # CDN purge: Cloudflare/Imperva/Akamai (Enterprise)
│       ├── class-cachewarmer-facebook-warmer.php    # Facebook Graph API scrape
│       ├── class-cachewarmer-linkedin-warmer.php    # LinkedIn Post Inspector
│       ├── class-cachewarmer-twitter-warmer.php     # Twitter/X Card Validator
│       ├── class-cachewarmer-google-indexer.php     # Google Indexing API v3
│       ├── class-cachewarmer-bing-indexer.php       # Bing Webmaster URL Submission
│       ├── class-cachewarmer-indexnow.php           # IndexNow batch protocol
│       └── class-cachewarmer-pinterest-warmer.php   # Pinterest Rich Pin Validator
├── templates/
│   ├── dashboard.php    # Main dashboard UI
│   ├── sitemaps.php     # Sitemap management UI
│   └── settings.php     # Settings form UI
├── assets/
│   ├── css/admin.css    # Admin dashboard styles
│   └── js/admin.js      # AJAX handlers, real-time job status
└── CHANGELOG.md
```

### Database Tables (using wpdb prefix)
1. **wp_cachewarmer_sitemaps** — id, url, domain, cron_expression, created_at, last_warmed_at
2. **wp_cachewarmer_jobs** — id, sitemap_id, sitemap_url, status, total_urls, processed_urls, targets (JSON), started_at, completed_at, error
3. **wp_cachewarmer_url_results** — id, job_id, url, target, status, http_status, duration_ms, error, viewport, cache_headers, created_at

### REST API (namespace: `cachewarmer/v1`)
| Method | Route | Purpose |
|--------|-------|---------|
| POST | `/warm` | Start warming job |
| GET | `/jobs` | List jobs |
| GET | `/jobs/{id}` | Job details + results |
| DELETE | `/jobs/{id}` | Cancel/delete job |
| GET | `/sitemaps` | List registered sitemaps |
| POST | `/sitemaps` | Register sitemap |
| DELETE | `/sitemaps/{id}` | Remove sitemap |
| GET | `/status` | Health check |
| GET | `/logs` | URL results log |

**Auth:** Bearer token OR WordPress admin capability

### License System
- **Format:** `CW-{PRO|ENT}-{DURATION_HEX(4)}{HMAC_SHA256(12)}`
- **Validation:** HMAC-based with secret `cw-drossmedia-lic-2026-s3cr3t`
- **Tiers:** Free (no key), Premium (CW-PRO-*), Enterprise (CW-ENT-*)

### Admin AJAX Endpoints
`cachewarmer_start_warm`, `cachewarmer_get_jobs`, `cachewarmer_add_sitemap`, `cachewarmer_detect_sitemaps`, `cachewarmer_export_results`, `cachewarmer_export_failed`

---

## 3. CacheWarmer Drupal Module

**Path:** `drupal-module/cachewarmer/`
**Version:** 1.1.1
**Requires:** Drupal 10/11, PHP 8.1+
**Package:** Performance
**Dependencies:** drupal:rest, drupal:serialization

### File Structure
```
drupal-module/cachewarmer/
├── cachewarmer.info.yml          # Module metadata
├── cachewarmer.module            # Hooks: help, cron, mail, theme
├── cachewarmer.install           # DB schema (3 tables)
├── cachewarmer.services.yml      # 16 service definitions (DI) + logger channel
├── cachewarmer.routing.yml       # 15+ routes
├── cachewarmer.permissions.yml   # "Administer CacheWarmer" permission
├── cachewarmer.links.menu.yml    # Admin menu
├── cachewarmer.links.task.yml    # Task links
├── cachewarmer.libraries.yml     # CSS/JS assets
├── config/
│   └── install/
│       └── cachewarmer.settings.yml  # Default configuration
├── src/
│   ├── Controller/
│   │   ├── CacheWarmerDashboardController.php  # Dashboard + sitemaps pages
│   │   └── CacheWarmerAjaxController.php       # 11 AJAX endpoints
│   ├── Form/
│   │   └── CacheWarmerSettingsForm.php          # Configuration form
│   ├── Plugin/
│   │   ├── QueueWorker/
│   │   │   └── CacheWarmerProcessJob.php        # Background job queue worker
│   │   └── rest/
│   │       └── resource/
│   │           └── CacheWarmerResource.php      # REST API plugin
│   └── Service/
│       ├── CacheWarmerDatabase.php       # DB abstraction
│       ├── CacheWarmerJobManager.php     # Job orchestration
│       ├── CacheWarmerSitemapParser.php   # XML sitemap parsing
│       ├── CacheWarmerLicense.php        # License validation
│       ├── CacheWarmerSitemapDetector.php # Auto-detect sitemaps
│       ├── CacheWarmerWebhooks.php       # Webhook notifications
│       ├── CacheWarmerEmail.php          # Email notifications
│       ├── CdnWarmer.php                 # CDN edge warming
│       ├── FacebookWarmer.php            # Facebook OG scraping
│       ├── LinkedinWarmer.php            # LinkedIn card caching
│       ├── TwitterWarmer.php             # Twitter/X card caching
│       ├── GoogleIndexer.php             # Google Indexing API
│       ├── BingIndexer.php               # Bing Webmaster API
│       ├── IndexNow.php                  # IndexNow protocol
│       ├── PinterestWarmer.php           # Pinterest Rich Pins
│       └── CdnPurgeWarmer.php            # CDN purge: Cloudflare/Imperva/Akamai (Enterprise)
├── templates/
│   ├── cachewarmer-dashboard.html.twig   # Dashboard template
│   └── cachewarmer-sitemaps.html.twig    # Sitemap management template
├── js/cachewarmer-admin.js               # Admin JavaScript
├── css/cachewarmer-admin.css             # Admin styles
└── tests/                                # Unit tests
```

### Database Tables
1. **cachewarmer_sitemaps** — Same schema as WordPress version
2. **cachewarmer_jobs** — Same schema as WordPress version
3. **cachewarmer_url_results** — Same schema as WordPress version (`viewport` and `cache_headers` added by `cachewarmer_update_10001`)

### Admin Routes
- `/admin/config/performance/cachewarmer` — Dashboard
- `/admin/config/performance/cachewarmer/sitemaps` — Sitemap management
- `/admin/config/performance/cachewarmer/settings` — Settings form
- `/admin/cachewarmer/ajax/*` — AJAX endpoints

### Drupal Hooks
- `hook_help()` — Module help text
- `hook_cron()` — Scheduled warming triggers
- `hook_mail()` — Email notification formatting
- `hook_theme()` — Template registration

---

## 4. CacheWarmer Node.js / Docker Module

**Path:** `nodejs-docker/`
**Version:** 1.1.1
**Framework:** Next.js 16 (App Router) with React 19
**Runtime:** Node.js 20+
**Package Manager:** pnpm 10.29.3

> **Important:** Despite the original concept document describing Fastify, the actual implementation uses **Next.js** with API route handlers.

### Tech Stack
| Category | Technology |
|----------|-----------|
| Framework | Next.js 16 (App Router) |
| UI | React 19, Tailwind CSS 4 |
| Database | SQLite via better-sqlite3 |
| Job Execution | In-process from the API route; no broker |
| Browser | puppeteer-core |
| Sitemap Parsing | fast-xml-parser |
| Google API | googleapis |
| Logging | pino + pino-pretty |
| Config | YAML (yaml package) |
| IDs | uuid |
| Testing | Vitest 4, @testing-library/react, jsdom |

### Source Structure
```
nodejs-docker/src/
├── app/
│   ├── layout.tsx              # Root layout
│   ├── page.tsx                # Dashboard page (home)
│   ├── globals.css             # Global styles (Tailwind)
│   ├── settings/
│   │   └── page.tsx            # Settings page
│   ├── sitemaps/
│   │   └── page.tsx            # Sitemap management page
│   └── api/
│       ├── warm/route.ts       # POST — Start warming job
│       ├── jobs/
│       │   ├── route.ts        # GET — List jobs
│       │   └── [id]/route.ts   # GET/DELETE — Job details/cancel
│       ├── sitemaps/
│       │   ├── route.ts        # GET/POST — List/register sitemaps
│       │   ├── [id]/route.ts   # DELETE — Remove sitemap
│       │   ├── bulk/route.ts   # POST — Bulk sitemap operations
│       │   └── detect/route.ts # POST — Auto-detect sitemaps
│       ├── status/route.ts     # GET — Health check
│       ├── logs/route.ts       # GET — URL results log
│       ├── settings/route.ts   # GET/PUT — Configuration
│       ├── export/route.ts     # GET — CSV/JSON export
│       ├── export-failed/route.ts # GET — Failed URLs export
│       └── schema-report/
│           ├── route.ts        # GET — Schema validation report
│           └── export/route.ts # POST — Schema report CSV/JSON export
├── components/
│   ├── InputField.tsx          # Reusable input component
│   ├── JobDetail.tsx           # Job detail view (integrates SchemaReport)
│   ├── JobTable.tsx            # Jobs listing table
│   ├── NavBar.tsx              # Navigation bar
│   ├── SettingsSection.tsx     # Settings group component
│   ├── SitemapManager.tsx      # Sitemap CRUD UI
│   ├── SchemaReport.tsx        # Schema validation report (filterable, exportable)
│   ├── StatusCard.tsx          # Status metric card
│   └── WarmForm.tsx            # Warming initiation form
└── lib/
    ├── auth.ts                 # API key authentication
    ├── config.ts               # YAML config loader
    ├── logger.ts               # Pino logger setup
    ├── db/
    │   └── database.ts         # SQLite schema + CRUD operations
    ├── queue/
    │   └── job-manager.ts      # Job orchestration (in-process, no broker)
    └── services/
        ├── sitemap-parser.ts       # XML sitemap parser
        ├── cdn-warmer.ts           # CDN edge cache warming
        ├── cdn-purge-warm.ts       # CDN purge (Cloudflare/Imperva/Akamai)
        ├── facebook-warmer.ts      # Facebook Graph API
        ├── linkedin-warmer.ts      # LinkedIn Post Inspector
        ├── twitter-warmer.ts       # Twitter/X Card Validator
        ├── google-indexer.ts       # Google Indexing API
        ├── bing-indexer.ts         # Bing Webmaster API
        ├── indexnow.ts             # IndexNow protocol
        ├── pinterest-warmer.ts     # Pinterest Rich Pins
        ├── schema-validator.ts    # Google Structured Data validation
        ├── webhooks.ts             # Webhook notifications
        └── email-notifications.ts  # Email notifications
```

### Configuration
Central config via `nodejs-docker/config.yaml`. Supports all warming services, SQLite path, Puppeteer settings, rate limits, CDN purge providers, scheduler, logging, and notification settings. See the file for full schema.

### Docker Deployment
- **Dockerfile:** Multi-stage build (Node.js 20-slim + Chromium), runs as non-root `nextjs` user
- **docker-compose.yml:** CacheWarmer service only — jobs run in-process and state lives in SQLite, so there is no broker to run

### Testing
```
nodejs-docker/tests/
├── setup.ts                            # Global test setup
├── helpers.ts                          # Test utilities
├── unit/
│   ├── core/
│   │   ├── auth.test.ts                # API key auth tests
│   │   ├── config.test.ts              # Config loader tests
│   │   ├── database.test.ts            # DB operations tests
│   │   ├── export-failed.test.ts       # Failed URL export tests
│   │   ├── job-manager.test.ts         # Job orchestration tests
│   │   └── priority-warming.test.ts    # Priority-based warming tests
│   └── services/
│       ├── cdn-warmer.test.ts          # CDN warming tests
│       ├── cdn-warmer-enterprise.test.ts # Enterprise CDN features
│       ├── facebook-warmer.test.ts
│       ├── google-indexer.test.ts
│       ├── indexnow.test.ts
│       ├── linkedin-warmer.test.ts
│       ├── pinterest-warmer.test.ts
│       ├── sitemap-parser.test.ts
│       └── twitter-warmer.test.ts
├── integration/
│   ├── api-jobs.test.ts
│   ├── api-logs.test.ts
│   ├── api-sitemaps.test.ts
│   ├── api-status.test.ts
│   └── api-warm.test.ts
├── uat/
│   └── user-workflows.test.ts
├── performance/
│   └── performance.test.ts
├── regression/
│   └── regression.test.ts
└── security/
    └── security.test.ts
```

**Test commands:**
- `pnpm test` — Run all tests
- `pnpm test:unit` — Unit tests only
- `pnpm test:integration` — Integration tests
- `pnpm test:uat` — User acceptance tests
- `pnpm test:security` — Security tests
- `pnpm test:coverage` — With coverage report

---

## 5. License Manager Plugin

**Path:** `cachewarmer-license-manager/`
**Version:** 1.0.1
**Requires:** WordPress 6.0+, PHP 8.0+

### Architecture
Standalone WordPress plugin with `cwlm/v1` REST API namespace. Handles license CRUD, installation tracking, Stripe webhooks, and admin dashboard.

### Key Files
| File | Purpose |
|------|---------|
| `cachewarmer-license-manager.php` | Plugin entry point, main class |
| `includes/class-cwlm-license-manager.php` | License CRUD, validation, listing |
| `includes/class-cwlm-activator.php` | DB table creation (6 tables) |
| `includes/class-cwlm-installation-tracker.php` | Site activation/deactivation tracking |
| `includes/class-cwlm-feature-flags.php` | Tier-based feature gating |
| `includes/class-cwlm-jwt-handler.php` | JWT token generation/validation |
| `includes/class-cwlm-audit-logger.php` | Audit logging |
| `includes/class-cwlm-geoip.php` | GeoIP data for installations |
| `includes/class-cwlm-email.php` | License email notifications |
| `api/class-cwlm-rest-controller.php` | Base REST controller |
| `api/class-cwlm-validate-endpoint.php` | POST /validate |
| `api/class-cwlm-activate-endpoint.php` | POST /activate |
| `api/class-cwlm-deactivate-endpoint.php` | POST /deactivate |
| `api/class-cwlm-check-endpoint.php` | POST /check (24h heartbeat) |
| `api/class-cwlm-stripe-webhook.php` | POST /stripe/webhook |
| `admin/class-cwlm-admin.php` | Admin menu, pages, assets |

### Database Tables (6, prefix: `cwlm_`)
1. **cwlm_licenses** — License keys, customer data, tier, status, Stripe IDs
2. **cwlm_installations** — Active installations per license (fingerprint, domain, platform)
3. **cwlm_geo_data** — GeoIP data per installation
4. **cwlm_audit_logs** — All actions logged with actor, IP, details
5. **cwlm_stripe_events** — Idempotent Stripe webhook event log
6. **cwlm_stripe_product_map** — Maps Stripe products to tiers/plans

### REST API (namespace: `cwlm/v1`)
| Method | Route | Purpose |
|--------|-------|---------|
| POST | `/validate` | Validate license key without activation |
| POST | `/activate` | Register installation, get JWT token |
| POST | `/deactivate` | Release installation slot |
| POST | `/check` | 24h heartbeat, refresh JWT + features |
| POST | `/stripe/webhook` | Stripe payment event handler |
| GET | `/health` | Health check |

### License Key Format
- **Format:** `CW-{TIER}-{16 Hex chars}` (e.g., `CW-PRO-A1B2C3D4E5F6G7H8`)
- **Tiers:**
  - **Free** — No key required. CDN + IndexNow only. 50 URLs/job, 2 sitemaps, 3 jobs/day.
  - **Premium** — All social + search engine targets. 10,000 URLs/job, 25 sitemaps, 50 jobs/day.
  - **Enterprise** — Everything + CDN purge, webhooks, multi-site, custom config. Unlimited.

---

## Warming Targets

Availability depends on the licence tier **and** on the edition. Earlier
revisions of this file listed only tiers, which implied every target exists
everywhere — it does not.

| Target | Free | Premium | Enterprise | Node | WP | Drupal | Method |
|--------|:----:|:-------:|:----------:|:----:|:--:|:------:|--------|
| Schema Validation | -- | Yes | Yes | Yes | -- | -- | Local structured data testing (JSON-LD, Microdata, RDFa) |
| CDN Edge Cache | Yes | Yes | Yes | Yes | Yes | Yes | HTTP GET (desktop + mobile user-agents) |
| IndexNow | Yes | Yes | Yes | Yes | Yes | Yes | Batch POST to api.indexnow.org |
| Facebook | -- | Yes | Yes | Yes | Yes | Yes | Graph API v19.0 scrape endpoint |
| LinkedIn | -- | Yes | Yes | Yes | Yes | Yes | Post Inspector API |
| Twitter/X | -- | Yes | Yes | Yes | Yes | Yes | Tweet Composer intent URL |
| Google | -- | Yes | Yes | Yes | Yes | Yes | Indexing API v3 (URL_UPDATED) |
| Bing | -- | Yes | Yes | Yes | Yes | Yes | Webmaster URL Submission API |
| Pinterest | -- | Yes | Yes | Yes | Yes | Yes | Rich Pin Validator |
| Cloudflare Purge | -- | -- | Yes | Yes | Yes | Yes | API v4 zone cache purge (100 URLs/batch) |
| Imperva Purge | -- | -- | Yes | Yes | Yes | Yes | Cloud WAF API v1 |
| Akamai Purge | -- | -- | Yes | Yes | Yes | Yes | Fast Purge API v3 (50 URLs/batch, EdgeGrid auth) |

Target identifiers accepted by the APIs: `schema`, `cdn`, `facebook`,
`linkedin`, `twitter`, `google`, `bing`, `indexnow`, `pinterest`, `cdn-purge`.
The Drupal module accepts all of these except `schema`. Structured-data
validation is deliberately Node.js-only — it is a content-quality check rather
than a warming target, and porting it to the PHP editions is not planned.

**Warm verification.** All three editions treat the desktop pass as a cache
fill and the following pass as a probe, recording a verdict (`warmed`,
`already_warm`, `not_cacheable`, `bypassed`, `zone_not_caching`,
`indeterminate`, `unknown`) alongside the raw cache headers. MISS on a fill
request is the success signal, not a warning — a probe that comes back HIT is
what proves the fill landed.

Where the origin sends `Vary: User-Agent` the two passes address different
cache entries, so the verdict is `indeterminate` rather than claimed either
way.

---

## Database Schema (Shared Across All Platforms)

All three platforms (WordPress, Drupal, Node.js) use the same logical schema:

### sitemaps
| Column | Type | Description |
|--------|------|-------------|
| id | TEXT (UUID) | Primary key |
| url | TEXT | Sitemap URL |
| domain | TEXT | Extracted domain |
| cron_expression | TEXT | Cron for scheduled warming (optional) |
| created_at | DATETIME | Creation timestamp |
| last_warmed_at | DATETIME | Last warming run |

### jobs
| Column | Type | Description |
|--------|------|-------------|
| id | TEXT (UUID) | Primary key |
| sitemap_id | TEXT (FK) | Reference to sitemap |
| sitemap_url | TEXT | Sitemap URL snapshot |
| status | TEXT | queued / running / completed / failed |
| total_urls | INTEGER | Total URL count |
| processed_urls | INTEGER | Processed URL count |
| targets | TEXT (JSON) | Active warming targets |
| started_at | DATETIME | Start time |
| completed_at | DATETIME | End time |
| error | TEXT | Error message (optional) |

### url_results
| Column | Type | Description |
|--------|------|-------------|
| id | TEXT (UUID) | Primary key |
| job_id | TEXT (FK) | Reference to job |
| url | TEXT | The warmed URL |
| target | TEXT | schema / cdn / facebook / linkedin / twitter / google / bing / indexnow / pinterest |
| status | TEXT | success / failed / skipped / pending |
| http_status | INTEGER | HTTP status code |
| duration_ms | INTEGER | Duration in milliseconds |
| error | TEXT | Error message (optional) |
| viewport | TEXT | Which pass produced the row: desktop / mobile / custom label |
| cache_headers | TEXT (JSON) | CDN cache headers plus the warm verdict |
| created_at | DATETIME | Timestamp |

### schema_results (Node.js only)
| Column | Type | Description |
|--------|------|-------------|
| id | TEXT (UUID) | Primary key |
| job_id | TEXT (FK) | Reference to job |
| url | TEXT | Validated URL |
| status | TEXT | valid / warnings / errors / pending |
| schemas | TEXT (JSON) | Detected schema types (e.g. ["Article", "Organization"]) |
| errors | TEXT (JSON) | Array of error objects |
| warnings | TEXT (JSON) | Array of warning objects |
| duration_ms | INTEGER | Duration in milliseconds |
| error | TEXT | Error message (optional) |
| created_at | DATETIME | Timestamp |

---

## API Endpoints (Node.js Module)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/warm` | Start warming from sitemap URL |
| GET | `/api/jobs` | List all jobs |
| GET | `/api/jobs/:id` | Get job details + results |
| DELETE | `/api/jobs/:id` | Cancel/delete job |
| GET | `/api/sitemaps` | List registered sitemaps |
| POST | `/api/sitemaps` | Register sitemap |
| DELETE | `/api/sitemaps/:id` | Remove sitemap |
| POST | `/api/sitemaps/bulk` | Bulk sitemap operations |
| POST | `/api/sitemaps/detect` | Auto-detect sitemaps |
| GET | `/api/status` | Health check & system status |
| GET | `/api/logs` | URL results log |
| GET | `/api/settings` | Read configuration |
| PUT | `/api/settings` | Update configuration |
| GET | `/api/export` | CSV/JSON export |
| GET | `/api/export-failed` | Export failed URLs |
| GET | `/api/schema-report` | Schema validation report |
| POST | `/api/schema-report/export` | Schema report CSV/JSON export |

---

## Required API Credentials

| Service | Credentials | Source |
|---------|------------|--------|
| Facebook | App ID + App Secret | developers.facebook.com |
| LinkedIn | `li_at` session cookie | Browser DevTools |
| Google | Service Account JSON | Google Cloud Console (Indexing API) |
| Bing | Webmaster API Key | Bing Webmaster Tools |
| IndexNow | Self-generated key | Host as .txt on website |
| Cloudflare | API Token + Zone ID | Cloudflare Dashboard |
| Imperva | API ID + API Key + Site ID | Imperva Console |
| Akamai | EdgeGrid credentials (host, client_token, client_secret, access_token) | Akamai Control Center |

Setup guide: `docs/API_KEYS_SETUP.md`

---

## Documentation Files

| File | Content |
|------|---------|
| `CLAUDE.md` | This file — repository knowledge base |
| `wordpress-plugin/WP.md` | WordPress plugin architecture & documentation |
| `drupal-module/Drupal.md` | Drupal module architecture & documentation |
| `theme/WEBSITE.md` | Website IA, content strategy, design system for cachewarmer.drossmedia.de |
| `PRICING-TIERS.md` | Detailed tier definitions, feature matrices, pricing recommendations |
| `docs/API_KEYS_SETUP.md` | Step-by-step credential setup for all services |
| `docs/CLOUDFLARE-WORKERS-EVALUATION.md` | Evaluation of a Workers-based warming engine, and the defect list it produced |
| `cloudflare-worker/README.md` | CacheWarmer Edge — architecture, deploy targets, verification spike |
| `wordpress-plugin/CHANGELOG.md` | WordPress plugin version history |

---

## Development

### Prerequisites
- Node.js 20+
- pnpm 10.29+
- Chromium (for Puppeteer CDN warming)

### Commands (run from `nodejs-docker/`)
```bash
pnpm install          # Install dependencies
pnpm dev              # Start Next.js dev server
pnpm build            # Production build
pnpm test             # Run all tests (Vitest)
pnpm test:unit        # Unit tests only
pnpm test:integration # Integration tests
pnpm test:uat         # User acceptance tests
pnpm test:security    # Security tests
pnpm test:coverage    # Tests with coverage
pnpm lint             # ESLint
```

### Docker (from `nodejs-docker/`)
```bash
docker compose up -d  # Start CacheWarmer
```

### Configuration
Copy and edit `nodejs-docker/config.yaml` for service credentials. For local overrides, use `config.local.yaml` (gitignored).

### Key Design Principles
- **Rate-limiting is critical:** All external APIs have limits. Every worker must respect them.
- **Fault tolerance:** Single URL failures must not abort the entire job. Log errors and continue.
- **Idempotency:** Re-warming the same URLs should cause no issues.
- **Security:** API key auth for all endpoints. Credentials never in git. Input validation on all boundaries.
- **Structured logging:** Pino with configurable log levels.

---

## .gitignore Summary
Ignored: `node_modules/`, `.next/`, `data/`, `credentials/`, `.env*`, `config.local.yaml`, `dist/`, `*.zip` (build artifacts), `cachewarmer-license-manager/vendor/`
