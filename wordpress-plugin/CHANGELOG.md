# Changelog

All notable changes to the CacheWarmer WordPress plugin will be documented in this file.

## [1.1.0] - 2026-03-08

### Added
- URL normalization for sitemap registration to prevent duplicate entries with cosmetically different URLs (trailing slashes, case differences, default ports).

### Fixed
- Duplicate sitemaps could be registered when URLs differed only by trailing slash, hostname casing, or default port.

## [1.0.0] - 2026-02-25

### Added
- CDN edge cache warming via headless browser (desktop + mobile viewports).
- Facebook Sharing Debugger integration (OG tag cache refresh).
- LinkedIn Post Inspector integration (card cache refresh).
- Twitter/X Card Validator integration.
- Google Indexing API submission (URL_UPDATED notifications).
- Bing Webmaster Tools URL Submission API.
- IndexNow protocol support (Bing, Yandex, Seznam, Naver).
- Pinterest Rich Pin Validator.
- CDN cache purge + warm for Cloudflare, Imperva, and Akamai (Enterprise).
- XML sitemap parser with recursive sitemap index support.
- Cron-based scheduled warming with configurable frequency.
- Auto-warm on post/page publish.
- REST API with Bearer token authentication.
- WordPress Dashboard widget with quick stats.
- Job queue with per-URL status tracking and retry logic.
- CSV/JSON export of job results.
- Export of failed/skipped URLs as CSV.
- Bulk sitemap import (up to 1000 URLs).
- Auto-detection of local sitemaps (sitemap.xml, sitemap_index.xml, wp-sitemap.xml).
- SSRF protection for all outbound URL requests.
- Tiered licensing (Free / Premium / Enterprise) with feature gating.
- Email notifications and webhook support (Enterprise).
- URL exclusion patterns (Enterprise).
