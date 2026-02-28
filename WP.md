# CacheWarmer WordPress Plugin

WordPress plugin version of the CacheWarmer microservice. Provides the same cache warming and search engine indexing functionality as the standalone Node.js application, but runs natively within WordPress.

---

## 1. Overview & Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                WordPress + CacheWarmer Plugin                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────────────────────────────┐   │
│  │  WP Admin UI  │    │  WP-Cron Background Processing       │   │
│  │  (Dashboard,  │    │                                      │   │
│  │   Sitemaps,   │    │  ┌─────────┐ ┌─────────┐ ┌────────┐ │   │
│  │   Settings)   │    │  │CDN Warm │ │Social   │ │Search  │ │   │
│  └──────────────┘    │  │(HTTP)   │ │Cache    │ │Index   │ │   │
│                       │  │         │ │(FB,LI,X)│ │(Google │ │   │
│  ┌──────────────┐    │  │         │ │         │ │Bing,   │ │   │
│  │  REST API     │───▶│  │         │ │         │ │IndexNow│ │   │
│  │  /wp-json/    │    │  └─────────┘ └─────────┘ └────────┘ │   │
│  │  cachewarmer/ │    └──────────────────────────────────────┘   │
│  │  v1/          │                                               │
│  └──────────────┘    ┌──────────────┐                           │
│                       │  WP Database  │                           │
│                       │  (Custom Tbls)│                           │
│                       └──────────────┘                           │
└─────────────────────────────────────────────────────────────────┘
```

**Tech-Stack:**
- **Runtime:** PHP 8.0+, WordPress 6.0+
- **Background Processing:** WP-Cron (single event scheduling)
- **HTTP Client:** WordPress HTTP API (`wp_remote_get` / `wp_remote_post`)
- **Database:** WordPress `$wpdb` with custom tables
- **Admin UI:** Native WordPress admin pages with jQuery

### Key Differences from Node.js Version

| Feature | Node.js Version | WordPress Plugin |
|---------|----------------|-----------------|
| Runtime | Node.js 20+ / TypeScript | PHP 8.0+ |
| HTTP Client | Puppeteer (headless browser) | `wp_remote_get/post` (HTTP API) |
| Job Queue | BullMQ / Redis | WP-Cron background events |
| Database | SQLite (better-sqlite3) | WordPress $wpdb (MySQL/MariaDB) |
| Frontend | Next.js / React | Native WP Admin pages |
| Auth | Bearer token | WP capabilities + optional Bearer token |
| Config | config.yaml | WordPress options (wp_options table) |

---

## 2. Plugin Structure

```
wordpress-plugin/cachewarmer/
├── cachewarmer.php                         # Main plugin file (entry point)
├── uninstall.php                           # Clean uninstall handler
├── includes/
│   ├── class-cachewarmer.php               # Main singleton class
│   ├── class-cachewarmer-database.php      # Database operations (CRUD)
│   ├── class-cachewarmer-job-manager.php   # Job orchestration
│   ├── class-cachewarmer-sitemap-parser.php # XML sitemap parsing
│   ├── class-cachewarmer-rest-api.php      # REST API endpoints
│   ├── class-cachewarmer-scheduler.php     # WP-Cron scheduling
│   ├── admin/
│   │   └── class-cachewarmer-admin.php     # Admin pages & AJAX
│   └── services/
│       ├── class-cachewarmer-cdn-warmer.php       # CDN edge cache warming
│       ├── class-cachewarmer-facebook-warmer.php   # Facebook OG cache
│       ├── class-cachewarmer-linkedin-warmer.php   # LinkedIn Post Inspector
│       ├── class-cachewarmer-twitter-warmer.php    # Twitter/X Card Validator
│       ├── class-cachewarmer-google-indexer.php    # Google Indexing API
│       ├── class-cachewarmer-bing-indexer.php      # Bing Webmaster API
│       └── class-cachewarmer-indexnow.php          # IndexNow protocol
├── templates/
│   ├── dashboard.php                       # Dashboard page template
│   ├── sitemaps.php                        # Sitemap management template
│   └── settings.php                        # Settings page template
└── assets/
    ├── css/
    │   └── admin.css                       # Admin styles
    └── js/
        └── admin.js                        # Admin JavaScript (AJAX)
```

---

## 3. Installation

1. Copy the `wordpress-plugin/cachewarmer/` folder to `wp-content/plugins/cachewarmer/`
2. Activate the plugin in WordPress Admin > Plugins
3. Navigate to **CacheWarmer** in the admin menu
4. Configure services under **CacheWarmer > Settings**

---

## 4. Database Schema

The plugin creates three custom tables using `dbDelta()`:

### `{prefix}_cachewarmer_sitemaps`
| Column | Type | Description |
|--------|------|-------------|
| id | VARCHAR(36) PK | UUID |
| url | TEXT | Sitemap URL |
| domain | VARCHAR(255) | Extracted domain |
| cron_expression | VARCHAR(100) | Optional cron expression |
| created_at | DATETIME | Created timestamp |
| last_warmed_at | DATETIME | Last warming run |

### `{prefix}_cachewarmer_jobs`
| Column | Type | Description |
|--------|------|-------------|
| id | VARCHAR(36) PK | UUID |
| sitemap_id | VARCHAR(36) FK | Linked sitemap |
| sitemap_url | TEXT | Sitemap URL |
| status | VARCHAR(20) | queued/running/completed/failed |
| total_urls | INT | Total URLs in sitemap |
| processed_urls | INT | URLs processed so far |
| targets | TEXT | JSON array of warming targets |
| started_at | DATETIME | Job start time |
| completed_at | DATETIME | Job completion time |
| error | TEXT | Error message if failed |
| created_at | DATETIME | Created timestamp |

### `{prefix}_cachewarmer_url_results`
| Column | Type | Description |
|--------|------|-------------|
| id | VARCHAR(36) PK | UUID |
| job_id | VARCHAR(36) FK | Parent job |
| url | TEXT | Warmed URL |
| target | VARCHAR(20) | cdn/facebook/linkedin/twitter/google/bing/indexnow |
| status | VARCHAR(20) | success/failed/skipped/pending |
| http_status | INT | HTTP response code |
| duration_ms | INT | Request duration |
| error | TEXT | Error message |
| created_at | DATETIME | Timestamp |

---

## 5. REST API Endpoints

Base: `/wp-json/cachewarmer/v1/`

Authentication: WordPress admin session **or** Bearer token (configured in Settings).

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/warm` | Start a new warming job |
| GET | `/jobs` | List all jobs (limit, offset) |
| GET | `/jobs/{id}` | Get job details with stats |
| DELETE | `/jobs/{id}` | Delete a job |
| GET | `/sitemaps` | List registered sitemaps |
| POST | `/sitemaps` | Register a sitemap |
| DELETE | `/sitemaps/{id}` | Unregister a sitemap |
| GET | `/status` | Health check & system status |
| GET | `/logs` | URL result logs (limit, offset, jobId) |

### Example: Start warming via REST API

```bash
curl -X POST https://example.com/wp-json/cachewarmer/v1/warm \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{"sitemapUrl": "https://example.com/sitemap.xml", "targets": ["cdn", "facebook", "google"]}'
```

---

## 6. Services

### 6.1 CDN Cache Warming
- Fetches each URL with both desktop and mobile user-agents
- Uses `wp_remote_get()` with configurable timeout and concurrency
- No headless browser required (unlike the Node.js version)

### 6.2 Facebook Sharing Debugger
- Graph API v19.0: `POST ?scrape=true&id={URL}`
- Requires Facebook App ID + App Secret
- Rate-limited (configurable requests/second)

### 6.3 LinkedIn Post Inspector
- Calls LinkedIn's internal Post Inspector API
- Requires `li_at` session cookie from browser DevTools
- Conservative delay between requests (default 5s)

### 6.4 Twitter/X Card Validator
- Fetches the tweet composer URL to trigger card scraping
- No API key needed
- Configurable concurrency and delay

### 6.5 Google Indexing API
- Generates JWT from Service Account JSON, exchanges for access token
- Sends `URL_UPDATED` notifications
- Respects daily quota (default 200/day)

### 6.6 Bing Webmaster Tools
- Batch submission (500 URLs per request)
- Daily quota tracking (default 10,000/day)

### 6.7 IndexNow Protocol
- Batch submission (up to 10,000 URLs per request)
- Supports Bing, Yandex, Seznam, Naver

---

## 7. Configuration (Settings Page)

All settings are stored in the `wp_options` table with the `cachewarmer_` prefix.

| Setting | Default | Description |
|---------|---------|-------------|
| `cachewarmer_api_key` | (empty) | Bearer token for REST API auth |
| `cachewarmer_cdn_enabled` | 1 | Enable CDN warming |
| `cachewarmer_cdn_concurrency` | 3 | Parallel requests |
| `cachewarmer_cdn_timeout` | 30 | Request timeout (seconds) |
| `cachewarmer_facebook_enabled` | 0 | Enable Facebook warming |
| `cachewarmer_facebook_app_id` | (empty) | Facebook App ID |
| `cachewarmer_facebook_app_secret` | (empty) | Facebook App Secret |
| `cachewarmer_linkedin_enabled` | 0 | Enable LinkedIn warming |
| `cachewarmer_linkedin_session_cookie` | (empty) | li_at cookie value |
| `cachewarmer_twitter_enabled` | 0 | Enable Twitter warming |
| `cachewarmer_google_enabled` | 0 | Enable Google Indexing |
| `cachewarmer_google_service_account` | (empty) | Service Account JSON |
| `cachewarmer_bing_enabled` | 0 | Enable Bing submission |
| `cachewarmer_bing_api_key` | (empty) | Bing Webmaster API key |
| `cachewarmer_indexnow_enabled` | 0 | Enable IndexNow |
| `cachewarmer_indexnow_key` | (empty) | IndexNow API key |
| `cachewarmer_scheduler_enabled` | 0 | Enable scheduled warming |
| `cachewarmer_scheduler_cron` | daily | WP-Cron schedule |

---

## 8. Background Processing

Jobs are processed via **WP-Cron** single events:

1. `create_job()` schedules a `cachewarmer_process_job` event
2. `spawn_cron()` triggers immediate execution
3. The job runs with `set_time_limit(0)` for long-running operations
4. Each URL result is saved individually with real-time progress updates
5. Scheduled warming uses `cachewarmer_cron_hook` (configurable: hourly/6h/12h/daily/weekly)

---

## 9. Admin Pages

### Dashboard
- Status cards (queued, running, completed, failed, total URLs)
- Warm form (sitemap URL + target selection)
- Jobs table with progress bars
- Job detail modal with results by target
- Auto-refresh every 10 seconds

### Sitemaps
- Register sitemap URLs for recurring warming
- Optional cron expression per sitemap
- "Warm Now" button for immediate warming
- Delete registered sitemaps

### Settings
- Toggle each service on/off
- Configure API keys, credentials, concurrency, timeouts, quotas
- Scheduler frequency selection
- Sensitive fields use password inputs

---

## 10. Security

- All admin pages require `manage_options` capability
- REST API requires either WP admin session or valid Bearer token
- All inputs sanitized with `sanitize_text_field()` / `esc_url_raw()`
- All outputs escaped with `esc_html()` / `esc_attr()` / `esc_url()`
- AJAX requests use WordPress nonces (`wp_create_nonce` / `check_ajax_referer`)
- Sensitive settings stored in `wp_options` (encrypted at rest if DB supports it)
- Uninstall cleans up all data (tables, options, scheduled events)

---

## 11. Requirements

- WordPress 6.0+
- PHP 8.0+
- PHP OpenSSL extension (for Google Indexing API JWT signing)
- `php-xml` / `simplexml` extension (for sitemap parsing)
- Outbound HTTP access (for external API calls)

---

## 12. API Credentials

| Service | Required | How to obtain |
|---------|----------|--------------|
| Facebook | App ID + App Secret | [developers.facebook.com](https://developers.facebook.com) |
| LinkedIn | li_at session cookie | Browser DevTools after login |
| Twitter/X | None | Uses public composer endpoint |
| Google | Service Account JSON | [Google Cloud Console](https://console.cloud.google.com) |
| Bing | Webmaster API Key | [Bing Webmaster Tools](https://www.bing.com/webmasters) |
| IndexNow | Self-generated key | Host as .txt on your site |
