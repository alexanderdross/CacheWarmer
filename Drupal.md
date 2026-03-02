# CacheWarmer — Drupal Module Documentation

## Overview

The CacheWarmer Drupal module processes XML sitemaps and systematically warms CDN edge caches, social media scraper caches (Facebook, LinkedIn, Twitter/X), submits URLs to search engines (Google, Bing, IndexNow), and can directly purge CDN caches via Cloudflare, Imperva, and Akamai APIs (Enterprise).

---

## Requirements

- **Drupal:** 10.x or 11.x
- **PHP:** 8.1+
- **Core modules:** REST, Serialization (enabled via dependencies)

---

## Installation

### 1. Copy Module

Copy the `drupal-module/cachewarmer/` directory to your Drupal installation:

```bash
cp -r drupal-module/cachewarmer/ /path/to/drupal/modules/custom/cachewarmer/
```

### 2. Enable Module

```bash
drush en cachewarmer -y
```

Or via the Drupal admin UI: **Extend** → Search for "CacheWarmer" → Check the box → **Install**.

### 3. Configure Permissions

Navigate to **People** → **Permissions** and grant the **"Administer CacheWarmer"** permission to the appropriate roles (typically Administrator only).

---

## Configuration

Navigate to **Configuration** → **Development** → **CacheWarmer** → **Settings** tab.

### General

| Setting | Description |
|---------|-------------|
| **API Key** | Bearer token for REST API authentication. Used in `Authorization: Bearer <key>` header. |

### CDN Cache Warming

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | Yes | Toggle CDN warming on/off |
| Concurrency | 3 | Number of parallel requests (1–20) |
| Timeout | 30s | Request timeout in seconds (5–120) |
| User Agent | `Mozilla/5.0 (compatible; CacheWarmer/1.0)` | Custom user agent string |

CDN warming sends HTTP GET requests with both desktop and mobile user-agents to trigger CDN edge cache population.

### Facebook Sharing Debugger

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle Facebook warming |
| App ID | — | Facebook App ID ([developers.facebook.com](https://developers.facebook.com)) |
| App Secret | — | Facebook App Secret |
| Rate Limit | 10 req/s | Maximum requests per second |

Uses the Facebook Graph API v19.0 `scrape=true` endpoint to force OG tag re-scraping.

### LinkedIn Post Inspector

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle LinkedIn warming |
| Session Cookie | — | `li_at` cookie value (extract from browser DevTools after login) |
| Delay | 5000 ms | Delay between requests |

Calls the LinkedIn Post Inspector API to trigger link preview cache refresh.

### Twitter/X Card Validator

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | Yes | Toggle Twitter/X warming |
| Concurrency | 2 | Batch size (1–10) |
| Delay | 3000 ms | Delay between batches |

Uses the Tweet Composer endpoint (`twitter.com/intent/tweet?url=...`) — no API key required. Loading the composer URL triggers Twitter's card scraper.

### Google Indexing API

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle Google indexing |
| Service Account JSON | — | Full contents of the Google Service Account key JSON file |
| Daily Quota | 200 | Maximum URL submissions per day |

Uses the Google Indexing API with OAuth2 JWT authentication. Requires a Service Account with access to the Google Search Console property.

**Setup Steps:**
1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Enable the "Web Search Indexing API"
3. Create a Service Account and download the JSON key file
4. Add the Service Account email as an owner in Google Search Console
5. Paste the JSON key contents into the settings

### Bing Webmaster Tools

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle Bing indexing |
| API Key | — | Bing Webmaster API key ([bing.com/webmasters](https://www.bing.com/webmasters)) |
| Daily Quota | 10,000 | Maximum URL submissions per day |

Batch-submits up to 500 URLs per request via the Bing Webmaster URL Submission API.

### IndexNow Protocol

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle IndexNow |
| Key | — | Self-generated IndexNow key |
| Key Location | — | URL where the key text file is hosted |

Batch-submits up to 10,000 URLs per request. Supported by Bing, Yandex, Seznam, and Naver.

**Setup Steps:**
1. Generate a random key (e.g. UUID)
2. Create a text file at `https://yoursite.com/{key}.txt` containing the key
3. Enter the key and key location URL in settings

### CDN Cache Purge (Enterprise)

Directly purge CDN caches via provider APIs before re-warming. All three providers are configured independently.

#### Cloudflare

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle Cloudflare cache purge |
| API Token | — | Cloudflare API Token with Zone:Cache Purge permission |
| Zone ID | — | Cloudflare Zone ID (32-char hex string) |

#### Imperva (Incapsula)

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle Imperva cache purge |
| API ID | — | Imperva API ID |
| API Key | — | Imperva API Key |
| Site ID | — | Imperva Site ID (numeric) |

#### Akamai

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle Akamai Fast Purge |
| Host | — | Akamai API hostname (e.g. `akaa-xxxxx.luna.akamaiapis.net`) |
| Client Token | — | EdgeGrid client_token |
| Client Secret | — | EdgeGrid client_secret |
| Access Token | — | EdgeGrid access_token |
| Network | production | `production` or `staging` |

### Scheduled Warming

| Setting | Default | Description |
|---------|---------|-------------|
| Enabled | No | Toggle automatic scheduled warming |
| Frequency | Daily | How often to warm (hourly, 6h, 12h, daily, weekly) |

When enabled, Drupal cron automatically warms all registered sitemaps on the configured schedule.

### Logging

| Setting | Default | Description |
|---------|---------|-------------|
| Log Level | info | Minimum log level: debug, info, warn, error |

Logs are written to Drupal's watchdog/dblog system under the `cachewarmer` channel.

---

## Admin UI

The module adds three admin pages under **Configuration** → **Development** → **CacheWarmer**:

### Dashboard Tab

- **Status cards**: Shows counts of Queued, Running, Completed, Failed jobs and total processed URLs
- **Warm form**: Enter a sitemap URL, select targets (CDN, Facebook, LinkedIn, Twitter/X, Google, Bing, IndexNow), and start warming
- **Jobs table**: Shows recent 20 jobs with status badge, progress bar, target tags, and actions (Details, Delete)
- **Job detail modal**: Shows full job info with per-target result breakdown (success/failed/skipped counts)
- **Auto-refresh**: Dashboard polls every 10 seconds for updated job status

### Sitemaps Tab

- **Register sitemap form**: Add a sitemap URL with optional cron expression
- **Sitemaps table**: Lists all registered sitemaps with domain, URL, cron schedule, last warmed timestamp, and actions (Warm Now, Delete)

### Settings Tab

Standard Drupal config form with collapsible sections for each service. All settings use proper form elements (password fields for secrets, number fields with min/max, select dropdowns).

---

## REST API

The module provides a REST resource at `/api/cachewarmer/{action}`. Enable it via REST UI or configuration.

### Authentication

All API requests require a Bearer token matching the configured API key:

```
Authorization: Bearer YOUR_API_KEY
```

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/cachewarmer/warm` | Start a new warming job |
| `GET` | `/api/cachewarmer/jobs` | List all jobs |
| `GET` | `/api/cachewarmer/jobs/{id}` | Get job details with stats |
| `DELETE` | `/api/cachewarmer/jobs/{id}` | Delete a job |
| `GET` | `/api/cachewarmer/sitemaps` | List registered sitemaps |
| `POST` | `/api/cachewarmer/sitemaps` | Register a new sitemap |
| `DELETE` | `/api/cachewarmer/sitemaps/{id}` | Delete a sitemap |
| `GET` | `/api/cachewarmer/status` | System health check |
| `GET` | `/api/cachewarmer/logs` | View warming logs |

### Example: Start Warming

```bash
curl -X POST https://yoursite.com/api/cachewarmer/warm \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "sitemapUrl": "https://www.example.com/sitemap.xml",
    "targets": ["cdn", "facebook", "google", "indexnow"]
  }'
```

### Example Response

```json
{
  "jobId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "status": "queued",
  "sitemapUrl": "https://www.example.com/sitemap.xml",
  "targets": ["cdn", "facebook", "google", "indexnow"],
  "createdAt": "2026-02-28T12:00:00Z"
}
```

---

## Database Schema

The module creates 3 custom tables via `hook_schema()`:

### `cachewarmer_sitemaps`

| Column | Type | Description |
|--------|------|-------------|
| `id` | VARCHAR(36) | UUID primary key |
| `url` | TEXT | Sitemap URL |
| `domain` | VARCHAR(255) | Extracted domain |
| `cron_expression` | VARCHAR(100) | Optional cron expression |
| `created_at` | VARCHAR(20) | ISO 8601 timestamp |
| `last_warmed_at` | VARCHAR(20) | Last warming timestamp |

### `cachewarmer_jobs`

| Column | Type | Description |
|--------|------|-------------|
| `id` | VARCHAR(36) | UUID primary key |
| `sitemap_id` | VARCHAR(36) | FK to sitemaps (optional) |
| `sitemap_url` | TEXT | Sitemap URL |
| `status` | VARCHAR(20) | queued / running / completed / failed |
| `total_urls` | INT | Total URL count |
| `processed_urls` | INT | Processed URL count |
| `targets` | TEXT | JSON array of targets |
| `started_at` | VARCHAR(20) | Processing start time |
| `completed_at` | VARCHAR(20) | Processing end time |
| `error` | TEXT | Error message |
| `created_at` | VARCHAR(20) | Job creation time |

### `cachewarmer_url_results`

| Column | Type | Description |
|--------|------|-------------|
| `id` | VARCHAR(36) | UUID primary key |
| `job_id` | VARCHAR(36) | FK to jobs |
| `url` | TEXT | Warmed URL |
| `target` | VARCHAR(50) | cdn / facebook / linkedin / twitter / google / bing / indexnow / cdn-purge:cloudflare / cdn-purge:imperva / cdn-purge:akamai |
| `status` | VARCHAR(20) | success / failed / skipped / pending |
| `http_status` | INT | HTTP response code |
| `duration_ms` | INT | Duration in milliseconds |
| `error` | TEXT | Error message |
| `created_at` | VARCHAR(20) | Result timestamp |

---

## Architecture

### Services (Dependency Injection)

All services are registered in `cachewarmer.services.yml` and use Drupal's dependency injection:

| Service ID | Class | Description |
|-----------|-------|-------------|
| `cachewarmer.database` | `CacheWarmerDatabase` | Database CRUD operations |
| `cachewarmer.sitemap_parser` | `CacheWarmerSitemapParser` | XML sitemap parsing |
| `cachewarmer.cdn_warmer` | `CdnWarmer` | CDN edge cache warming |
| `cachewarmer.facebook_warmer` | `FacebookWarmer` | Facebook OG cache |
| `cachewarmer.linkedin_warmer` | `LinkedinWarmer` | LinkedIn Post Inspector |
| `cachewarmer.twitter_warmer` | `TwitterWarmer` | Twitter/X Card Validator |
| `cachewarmer.google_indexer` | `GoogleIndexer` | Google Indexing API |
| `cachewarmer.bing_indexer` | `BingIndexer` | Bing Webmaster API |
| `cachewarmer.indexnow` | `IndexNow` | IndexNow protocol |
| `cachewarmer.cdn_purge_warmer` | `CdnPurgeWarmer` | CDN cache purge (Cloudflare, Imperva, Akamai) |
| `cachewarmer.job_manager` | `CacheWarmerJobManager` | Job orchestration |

### Background Processing

Jobs are processed via Drupal's Queue API:

1. A warming job is created → status = `queued`
2. Job is added to the `cachewarmer_process_job` queue
3. On next cron run, `CacheWarmerProcessJob` queue worker picks up the job
4. Job manager processes each target sequentially, inserting results into the database
5. Job status updates to `completed` or `failed`

The queue worker has a 300-second time limit per cron run. For large sitemaps, processing may span multiple cron runs.

### Cron Integration

- **Queue processing**: Drupal cron processes queued jobs automatically
- **Scheduled warming**: When enabled, `hook_cron()` creates new jobs for all registered sitemaps

---

## Module Structure

```
drupal-module/cachewarmer/
├── cachewarmer.info.yml                    # Module metadata
├── cachewarmer.install                     # Schema & uninstall hooks
├── cachewarmer.module                      # Hook implementations
├── cachewarmer.routing.yml                 # Route definitions
├── cachewarmer.permissions.yml             # Permission definitions
├── cachewarmer.services.yml                # Service container config
├── cachewarmer.libraries.yml               # Asset libraries
├── cachewarmer.links.menu.yml              # Admin menu links
├── cachewarmer.links.task.yml              # Admin tab links
├── config/
│   ├── install/
│   │   └── cachewarmer.settings.yml        # Default config values
│   └── schema/
│       └── cachewarmer.schema.yml          # Config schema
├── src/
│   ├── Controller/
│   │   ├── CacheWarmerDashboardController.php  # Admin page controllers
│   │   └── CacheWarmerAjaxController.php       # AJAX endpoint controller
│   ├── Form/
│   │   └── CacheWarmerSettingsForm.php         # Settings config form
│   ├── Plugin/
│   │   ├── QueueWorker/
│   │   │   └── CacheWarmerProcessJob.php       # Queue worker plugin
│   │   └── rest/
│   │       └── resource/
│   │           └── CacheWarmerResource.php     # REST API resource
│   └── Service/
│       ├── CacheWarmerDatabase.php             # Database operations
│       ├── CacheWarmerJobManager.php           # Job orchestration
│       ├── CacheWarmerSitemapParser.php         # Sitemap parsing
│       ├── CdnWarmer.php                       # CDN warming service
│       ├── FacebookWarmer.php                  # Facebook warming service
│       ├── LinkedinWarmer.php                  # LinkedIn warming service
│       ├── TwitterWarmer.php                   # Twitter/X warming service
│       ├── GoogleIndexer.php                   # Google indexing service
│       ├── BingIndexer.php                     # Bing indexing service
│       ├── IndexNow.php                        # IndexNow service
│       └── CdnPurgeWarmer.php                  # CDN purge (Cloudflare, Imperva, Akamai)
├── templates/
│   ├── cachewarmer-dashboard.html.twig         # Dashboard template
│   └── cachewarmer-sitemaps.html.twig          # Sitemaps template
├── css/
│   └── cachewarmer-admin.css                   # Admin styles
├── js/
│   └── cachewarmer-admin.js                    # Admin JavaScript
└── tests/
    └── src/
        └── Unit/
            └── CacheWarmerTestSuite.php        # Unit tests
```

---

## API Keys & Credentials

| Service | Required | How to Obtain |
|---------|----------|---------------|
| **Facebook** | App ID + App Secret | [developers.facebook.com](https://developers.facebook.com) — Create an app |
| **LinkedIn** | `li_at` Session Cookie | Browser DevTools → Application → Cookies after login |
| **Twitter/X** | None | Works without API key via composer endpoint |
| **Google** | Service Account JSON | [Google Cloud Console](https://console.cloud.google.com) — Enable Indexing API |
| **Bing** | Webmaster API Key | [Bing Webmaster Tools](https://www.bing.com/webmasters) |
| **IndexNow** | Self-generated key | Generate UUID + host as text file on your domain |
| **Cloudflare** | API Token + Zone ID | [Cloudflare Dashboard](https://dash.cloudflare.com) — API Token with Zone:Cache Purge |
| **Imperva** | API ID + API Key + Site ID | [Imperva Console](https://my.imperva.com) — Account Settings → API |
| **Akamai** | EdgeGrid Credentials | [Akamai Control Center](https://control.akamai.com) — Identity & Access → API Clients |

---

## Uninstall

When the module is uninstalled:
- All 3 database tables are dropped (`cachewarmer_sitemaps`, `cachewarmer_jobs`, `cachewarmer_url_results`)
- Configuration (`cachewarmer.settings`) is deleted

To uninstall:
```bash
drush pmu cachewarmer -y
```

---

## Troubleshooting

### Jobs stuck in "queued" status
- Ensure Drupal cron is running: `drush cron`
- Check the cron queue: `drush queue:list`
- Process the queue manually: `drush queue:run cachewarmer_process_job`

### Facebook returns errors
- Verify App ID and App Secret are correct
- Ensure the app has the required permissions
- Check the Facebook Graph API rate limits

### Google returns 403
- Verify the Service Account JSON is valid
- Ensure the Service Account email is added as an owner in Google Search Console
- Check the daily quota hasn't been exceeded

### LinkedIn returns 401/403
- The `li_at` session cookie may have expired
- Re-extract the cookie from browser DevTools after logging in again

### Module not appearing in admin menu
- Clear caches: `drush cr`
- Verify the user has the "Administer CacheWarmer" permission
