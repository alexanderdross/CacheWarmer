<?php
/**
 * Template Name: Documentation
 * Documentation page template with platform tabs.
 */
$page_og_title = 'Documentation - CacheWarmer';
$page_description = 'Installation guides, configuration reference, and API documentation for CacheWarmer on WordPress, Drupal, and Node.js.';
get_header();
cachewarmer_breadcrumb('Documentation');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <h1>Documentation</h1>
        <p>Everything you need to install, configure, and run CacheWarmer.</p>
    </div>
</section>

<!-- Docs Layout -->
<div class="container">
    <div class="docs-layout">
        <!-- Sidebar -->
        <aside class="docs-sidebar">
            <nav class="docs-sidebar-nav" aria-label="Documentation navigation">
                <a href="#getting-started" class="docs-sidebar-link docs-sidebar-link-active" title="Getting Started with CacheWarmer">Getting Started</a>
                <a href="#installation" class="docs-sidebar-link" title="CacheWarmer Installation Instructions">Installation</a>
                <a href="#configuration" class="docs-sidebar-link" title="CacheWarmer Configuration Reference">Configuration</a>
                <a href="#warming-targets" class="docs-sidebar-link" title="CacheWarmer Warming Targets Overview">Warming Targets</a>
                <a href="#api-reference" class="docs-sidebar-link" title="CacheWarmer REST API Reference">API Reference</a>
                <a href="#deployment" class="docs-sidebar-link" title="CacheWarmer Deployment with Docker">Deployment</a>
                <a href="#troubleshooting" class="docs-sidebar-link" title="CacheWarmer Troubleshooting Guide">Troubleshooting</a>
            </nav>
        </aside>

        <!-- Content -->
        <div class="docs-content">

            <!-- Getting Started -->
            <section id="getting-started">
                <h2>Getting Started</h2>
                <p>CacheWarmer automatically warms CDN caches, updates social media previews, and notifies search engines about your content. It is available as a WordPress plugin, Drupal module, or standalone Node.js service.</p>

                <h3>Choose Your Platform</h3>
                <div class="grid grid-3 gap-4 mb-8">
                    <a href="#install-wordpress" class="card platform-card" style="padding: var(--space-4);" title="WordPress Plugin Installation Guide">
                        <div class="flex items-center gap-3">
                            <?php cachewarmer_icon('wordpress', '', 20); ?>
                            <strong>WordPress</strong>
                        </div>
                    </a>
                    <a href="#install-drupal" class="card platform-card" style="padding: var(--space-4);" title="Drupal Module Installation Guide">
                        <div class="flex items-center gap-3">
                            <?php cachewarmer_icon('drupal', '', 20); ?>
                            <strong>Drupal</strong>
                        </div>
                    </a>
                    <a href="#install-nodejs" class="card platform-card" style="padding: var(--space-4);" title="Self-Hosted Node.js Installation Guide">
                        <div class="flex items-center gap-3">
                            <?php cachewarmer_icon('server', '', 20); ?>
                            <strong>Self-Hosted</strong>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Installation -->
            <section id="installation">
                <h2>Installation</h2>

                <h3 id="install-wordpress">WordPress Plugin</h3>
                <p>Install the CacheWarmer plugin from the WordPress dashboard or via WP-CLI.</p>
                <?php cachewarmer_code_block('# Option 1: WP-CLI
wp plugin install cachewarmer --activate

# Option 2: Upload via Dashboard
# Go to Plugins → Add New → Upload Plugin
# Upload the cachewarmer.zip file and activate', 'Shell'); ?>

                <p>After activation, navigate to <strong>Settings &rarr; CacheWarmer</strong> to configure your warming targets and API keys.</p>

                <?php cachewarmer_callout('WordPress 6.0+ and PHP 8.0+ required. Works with Yoast SEO, Rank Math, and WordPress core sitemaps.', 'info'); ?>

                <h3 id="install-drupal">Drupal Module</h3>
                <p>Install the CacheWarmer module via Composer.</p>
                <?php cachewarmer_code_block('# Install with Composer
composer require drupal/cachewarmer

# Enable the module
drush en cachewarmer -y

# Clear cache
drush cr', 'Shell'); ?>

                <p>After enabling, navigate to <strong>Configuration &rarr; System &rarr; CacheWarmer</strong> to configure your settings.</p>

                <?php cachewarmer_callout('Drupal 10.2+ or Drupal 11 with PHP 8.1+ required. Compatible with Simple XML Sitemap and XML Sitemap modules.', 'info'); ?>

                <h3 id="install-nodejs">Self-Hosted (Node.js)</h3>
                <p>Deploy via Docker or install from source.</p>

                <h4>Docker (Recommended)</h4>
                <?php cachewarmer_code_block('# Pull and run with Docker Compose
docker compose up -d

# Or run directly
docker run -d \\
  --name cachewarmer \\
  -p 3000:3000 \\
  -v $(pwd)/data:/app/data \\
  -v $(pwd)/config.yaml:/app/config.yaml:ro \\
  drossmedia/cachewarmer:latest', 'Shell'); ?>

                <h4>From Source</h4>
                <?php cachewarmer_code_block('# Download the latest release from the CacheWarmer website
cd cachewarmer
npm install
cp .env.example .env
# Edit .env with your configuration
npm start', 'Shell'); ?>

                <?php cachewarmer_callout('Node.js 20+ required. Redis needed for BullMQ job queue. Docker image bundles all dependencies including Chromium.', 'info'); ?>
            </section>

            <!-- Configuration -->
            <section id="configuration">
                <h2>Configuration</h2>
                <p>All platforms share the same warming targets. Configuration methods differ per platform.</p>

                <h3>WordPress Configuration</h3>
                <p>All settings are managed via the admin UI at <strong>Settings &rarr; CacheWarmer</strong>. You can also use <code>wp-config.php</code> constants:</p>
                <?php cachewarmer_code_block("define('CACHEWARMER_LICENSE_KEY', 'CW-PRO-XXXXXXXXXXXX');
define('CACHEWARMER_FACEBOOK_APP_ID', 'your_app_id');
define('CACHEWARMER_FACEBOOK_APP_SECRET', 'your_app_secret');
define('CACHEWARMER_INDEXNOW_KEY', 'your_key');
define('CACHEWARMER_GOOGLE_SA_JSON', '/path/to/service-account.json');
define('CACHEWARMER_BING_API_KEY', 'your_key');

// Enterprise: CDN Cache Purge
define('CACHEWARMER_CLOUDFLARE_API_TOKEN', 'your_token');
define('CACHEWARMER_CLOUDFLARE_ZONE_ID', 'your_zone_id');
define('CACHEWARMER_IMPERVA_API_ID', 'your_api_id');
define('CACHEWARMER_IMPERVA_API_KEY', 'your_api_key');
define('CACHEWARMER_IMPERVA_SITE_ID', 'your_site_id');
define('CACHEWARMER_AKAMAI_HOST', 'your_host');
define('CACHEWARMER_AKAMAI_CLIENT_TOKEN', 'your_client_token');
define('CACHEWARMER_AKAMAI_CLIENT_SECRET', 'your_client_secret');
define('CACHEWARMER_AKAMAI_ACCESS_TOKEN', 'your_access_token');", 'PHP'); ?>

                <h3>Drupal Configuration</h3>
                <p>Configured via the admin form or <code>settings.php</code>:</p>
                <?php cachewarmer_code_block("\$config['cachewarmer.settings']['license_key'] = 'CW-PRO-XXXXXXXXXXXX';
\$config['cachewarmer.settings']['facebook_app_id'] = 'your_app_id';
\$config['cachewarmer.settings']['facebook_app_secret'] = 'your_app_secret';
\$config['cachewarmer.settings']['indexnow_key'] = 'your_key';

// Enterprise: CDN Cache Purge
\$config['cachewarmer.settings']['cloudflare_api_token'] = 'your_token';
\$config['cachewarmer.settings']['cloudflare_zone_id'] = 'your_zone_id';
\$config['cachewarmer.settings']['imperva_api_id'] = 'your_api_id';
\$config['cachewarmer.settings']['imperva_api_key'] = 'your_api_key';
\$config['cachewarmer.settings']['imperva_site_id'] = 'your_site_id';
\$config['cachewarmer.settings']['akamai_host'] = 'your_host';
\$config['cachewarmer.settings']['akamai_client_token'] = 'your_client_token';
\$config['cachewarmer.settings']['akamai_client_secret'] = 'your_client_secret';
\$config['cachewarmer.settings']['akamai_access_token'] = 'your_access_token';", 'PHP'); ?>

                <h3>Self-Hosted Configuration</h3>
                <p>Configured via environment variables or <code>config.yaml</code>:</p>
                <div class="overflow-x-auto">
                <table class="config-table">
                    <thead>
                        <tr>
                            <th scope="col">Variable</th>
                            <th scope="col">Default</th>
                            <th scope="col">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>PORT</code></td>
                            <td>3000</td>
                            <td>HTTP server port</td>
                        </tr>
                        <tr>
                            <td><code>API_KEY</code></td>
                            <td>&mdash;</td>
                            <td>Secret key for API authentication</td>
                        </tr>
                        <tr>
                            <td><code>LICENSE_KEY</code></td>
                            <td>&mdash;</td>
                            <td>CacheWarmer license key (Premium/Enterprise)</td>
                        </tr>
                        <tr>
                            <td><code>FACEBOOK_APP_ID</code></td>
                            <td>&mdash;</td>
                            <td>Facebook App ID</td>
                        </tr>
                        <tr>
                            <td><code>FACEBOOK_APP_SECRET</code></td>
                            <td>&mdash;</td>
                            <td>Facebook App Secret</td>
                        </tr>
                        <tr>
                            <td><code>LINKEDIN_SESSION_COOKIE</code></td>
                            <td>&mdash;</td>
                            <td>LinkedIn <code>li_at</code> session cookie</td>
                        </tr>
                        <tr>
                            <td><code>INDEXNOW_KEY</code></td>
                            <td>&mdash;</td>
                            <td>IndexNow verification key</td>
                        </tr>
                        <tr>
                            <td><code>GOOGLE_SERVICE_ACCOUNT_JSON</code></td>
                            <td>&mdash;</td>
                            <td>Path to Google service account JSON</td>
                        </tr>
                        <tr>
                            <td><code>BING_API_KEY</code></td>
                            <td>&mdash;</td>
                            <td>Bing Webmaster Tools API key</td>
                        </tr>
                        <tr>
                            <td><code>DATABASE_PATH</code></td>
                            <td>./data/cachewarmer.db</td>
                            <td>SQLite database file path</td>
                        </tr>
                        <tr>
                            <td><code>LOG_LEVEL</code></td>
                            <td>info</td>
                            <td>Logging level (debug, info, warn, error)</td>
                        </tr>
                        <tr>
                            <td><code>CLOUDFLARE_API_TOKEN</code></td>
                            <td>&mdash;</td>
                            <td>Cloudflare API token (Zone:Cache Purge) <span class="badge badge-pro">Enterprise</span></td>
                        </tr>
                        <tr>
                            <td><code>CLOUDFLARE_ZONE_ID</code></td>
                            <td>&mdash;</td>
                            <td>Cloudflare Zone ID</td>
                        </tr>
                        <tr>
                            <td><code>IMPERVA_API_ID</code></td>
                            <td>&mdash;</td>
                            <td>Imperva API ID <span class="badge badge-pro">Enterprise</span></td>
                        </tr>
                        <tr>
                            <td><code>IMPERVA_API_KEY</code></td>
                            <td>&mdash;</td>
                            <td>Imperva API Key</td>
                        </tr>
                        <tr>
                            <td><code>IMPERVA_SITE_ID</code></td>
                            <td>&mdash;</td>
                            <td>Imperva Site ID</td>
                        </tr>
                        <tr>
                            <td><code>AKAMAI_HOST</code></td>
                            <td>&mdash;</td>
                            <td>Akamai API hostname <span class="badge badge-pro">Enterprise</span></td>
                        </tr>
                        <tr>
                            <td><code>AKAMAI_CLIENT_TOKEN</code></td>
                            <td>&mdash;</td>
                            <td>Akamai EdgeGrid client token</td>
                        </tr>
                        <tr>
                            <td><code>AKAMAI_CLIENT_SECRET</code></td>
                            <td>&mdash;</td>
                            <td>Akamai EdgeGrid client secret</td>
                        </tr>
                        <tr>
                            <td><code>AKAMAI_ACCESS_TOKEN</code></td>
                            <td>&mdash;</td>
                            <td>Akamai EdgeGrid access token</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </section>

            <!-- Warming Targets -->
            <section id="warming-targets">
                <h2>Warming Targets</h2>
                <p>CacheWarmer supports 12 warming targets. Each can be enabled or disabled independently.</p>

                <h3>Schema Validation <span class="badge badge-pro">Premium</span></h3>
                <p>Validates structured data (JSON-LD, Microdata, RDFa) on every URL in your sitemap before warming begins. Detects missing required properties, invalid schema types, and formatting errors. Results are displayed in a dedicated report with per-URL error and warning breakdowns, and can be exported as CSV or JSON.</p>
                <p>Schema validation runs as the first step in every warming job, giving you a pre-flight quality check before caches are warmed and search engines are notified. No API keys or external services required - validation runs locally.</p>
                <p><a href="https://developers.google.com/search/docs/appearance/structured-data?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Google Structured Data Documentation"><?php cachewarmer_icon('external-link', '', 14); ?> Google Structured Data Guide</a></p>

                <h3>CDN Cache Warming</h3>
                <p>Visits every URL from your sitemap, triggering CDN edge nodes and reverse proxies to cache the response. Uses HTTP requests by default, with optional headless browser rendering for JavaScript-heavy sites.</p>

                <h3>IndexNow</h3>
                <p>Submits URLs to participating search engines (Bing, Yandex, Seznam, Naver) via the IndexNow protocol. Supports batch submission of up to 10,000 URLs per request.</p>
                <p><a href="https://www.indexnow.org/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="IndexNow Protocol"><?php cachewarmer_icon('external-link', '', 14); ?> indexnow.org</a></p>

                <h3>Facebook Sharing Debugger <span class="badge badge-pro">Premium</span></h3>
                <p>Calls the Facebook Graph API scrape endpoint for each URL, forcing Facebook to re-fetch Open Graph metadata. Requires a Facebook App ID and App Secret.</p>
                <p><a href="https://developers.facebook.com/tools/debug/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Facebook Sharing Debugger"><?php cachewarmer_icon('external-link', '', 14); ?> Facebook Sharing Debugger</a></p>

                <h3>LinkedIn Post Inspector <span class="badge badge-pro">Premium</span></h3>
                <p>Automates the LinkedIn Post Inspector to refresh cached link previews. Uses session-based authentication.</p>
                <p><a href="https://www.linkedin.com/post-inspector/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="LinkedIn Post Inspector"><?php cachewarmer_icon('external-link', '', 14); ?> LinkedIn Post Inspector</a></p>

                <h3>Twitter/X Card Cache <span class="badge badge-pro">Premium</span></h3>
                <p>Pre-warms Twitter Card cache by triggering the card validator endpoint. No API key required.</p>
                <p><a href="https://cards-dev.x.com/validator?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="X Card Validator"><?php cachewarmer_icon('external-link', '', 14); ?> X Card Validator</a></p>

                <h3>Google Search Console <span class="badge badge-pro">Premium</span></h3>
                <p>Submits URLs via the Google Indexing API. Requires a Google Cloud service account with Search Console access. Daily quota: 200 URLs per property.</p>
                <p><a href="https://search.google.com/search-console/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Google Search Console"><?php cachewarmer_icon('external-link', '', 14); ?> Google Search Console</a></p>

                <h3>Bing Webmaster Tools <span class="badge badge-pro">Premium</span></h3>
                <p>Direct URL submission via the Bing Webmaster API, complementing IndexNow for comprehensive Bing coverage.</p>
                <p><a href="https://www.bing.com/webmasters/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Bing Webmaster Tools"><?php cachewarmer_icon('external-link', '', 14); ?> Bing Webmaster Tools</a></p>

                <h3>Pinterest Rich Pins <span class="badge badge-pro">Premium</span></h3>
                <p>Refreshes Rich Pin Open Graph metadata so Pinterest always shows your latest content and images.</p>
                <p><a href="https://developers.pinterest.com/tools/url-debugger/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Pinterest Rich Pin Validator"><?php cachewarmer_icon('external-link', '', 14); ?> Pinterest URL Debugger</a></p>

                <h3>Cloudflare Cache Purge + Warm <span class="badge badge-enterprise">Enterprise</span></h3>
                <p>Purge and re-warm Cloudflare edge caches via the Zone API. Auto-detects Cloudflare zones for easy setup.</p>

                <h3>Imperva Cache Purge + Warm <span class="badge badge-enterprise">Enterprise</span></h3>
                <p>Purge and re-warm Imperva (Incapsula) CDN caches via their API. Supports site-level and resource-level cache purging.</p>

                <h3>Akamai Cache Purge + Warm <span class="badge badge-enterprise">Enterprise</span></h3>
                <p>Purge and re-warm Akamai CDN caches via the Fast Purge API (CCU v3). Supports URL-level and CP code-based purging.</p>
            </section>

            <!-- API Reference -->
            <section id="api-reference">
                <h2>API Reference</h2>
                <p>The self-hosted Node.js version exposes a REST API. WordPress and Drupal provide their own admin interfaces and CLI commands.</p>

                <?php
                cachewarmer_api_endpoint(
                    'POST', '/api/warm',
                    'Submit a sitemap or URLs for warming.',
                    '{
  "sitemapUrl": "https://example.com/sitemap.xml",
  "targets": ["cdn", "facebook", "indexnow"],
  "priority": "normal"
}',
                    '{
  "jobId": "warm-abc123",
  "status": "queued",
  "urlCount": 42,
  "targets": ["cdn", "facebook", "indexnow"],
  "createdAt": "2026-02-28T12:00:00Z"
}'
                );

                cachewarmer_api_endpoint(
                    'GET', '/api/jobs',
                    'List all warming jobs with status and progress.'
                );

                cachewarmer_api_endpoint(
                    'GET', '/api/jobs/:id',
                    'Get detailed status of a specific warming job.',
                    '',
                    '{
  "id": "warm-abc123",
  "status": "completed",
  "urlCount": 42,
  "processed": 42,
  "failed": 1,
  "targets": ["cdn", "facebook", "indexnow"],
  "startedAt": "2026-02-28T12:00:00Z",
  "completedAt": "2026-02-28T12:02:15Z"
}'
                );

                cachewarmer_api_endpoint(
                    'DELETE', '/api/jobs/:id',
                    'Cancel a running or queued warming job.'
                );

                cachewarmer_api_endpoint(
                    'GET', '/api/status',
                    'Health check and system status.',
                    '',
                    '{
  "status": "ok",
  "version": "1.0.0",
  "uptime": 86400,
  "database": "connected",
  "redis": "connected"
}'
                );
                ?>
            </section>

            <!-- Deployment -->
            <section id="deployment">
                <h2>Deployment</h2>

                <h3>Docker Compose (Production)</h3>
                <?php cachewarmer_code_block('version: "3.8"
services:
  cachewarmer:
    image: drossmedia/cachewarmer:latest
    ports:
      - "127.0.0.1:3000:3000"
    volumes:
      - ./data:/app/data
      - ./credentials:/app/credentials:ro
      - ./config.yaml:/app/config.yaml:ro
    depends_on:
      - redis
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    volumes:
      - redis-data:/data
    restart: unless-stopped

volumes:
  redis-data:', 'docker-compose.yml'); ?>

                <h3>Nginx Reverse Proxy</h3>
                <?php cachewarmer_code_block('server {
    listen 443 ssl http2;
    server_name cachewarmer.example.com;

    ssl_certificate /etc/letsencrypt/live/cachewarmer.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cachewarmer.example.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}', 'Nginx'); ?>
            </section>

            <!-- Troubleshooting -->
            <section id="troubleshooting">
                <h2>Troubleshooting</h2>

                <h3>WordPress</h3>
                <ul>
                    <li><strong>Warming doesn't run on schedule:</strong> Ensure WP-Cron is working. For high-traffic sites, consider a system cron job: <code>*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php</code></li>
                    <li><strong>Plugin conflicts:</strong> Deactivate other cache or SEO plugins temporarily to isolate the issue.</li>
                </ul>

                <h3>Drupal</h3>
                <ul>
                    <li><strong>Cron not running:</strong> Verify Drupal cron is configured. Use <code>drush cron</code> to trigger manually.</li>
                    <li><strong>Permission denied:</strong> Ensure the web server user has write access to the module's data directory.</li>
                </ul>

                <h3>Self-Hosted</h3>
                <ul>
                    <li><strong>Chromium errors:</strong> Install system dependencies: <code>apt-get install -y libnss3 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 libgbm1</code></li>
                    <li><strong>Redis connection failed:</strong> Verify Redis is running: <code>redis-cli ping</code></li>
                    <li><strong>Rate limit errors (429):</strong> Increase delay settings in your configuration. Each target has independent rate limiting.</li>
                    <li><strong>Debug logging:</strong> Set <code>LOG_LEVEL=debug</code> for detailed output.</li>
                </ul>
            </section>

        </div>
    </div>
</div>

<!-- CTA -->
<section class="gradient-hero cta-section-sm">
    <div class="container text-center">
        <h2>Need Help Setting Up API Keys?</h2>
        <p class="hero-subtitle">Step-by-step guides for every integration.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/api-keys/')); ?>" class="btn btn-white btn-lg" title="CacheWarmer API Keys Setup - Facebook, LinkedIn, Google &amp; Bing">
                <?php cachewarmer_icon('key', '', 20); ?> API Keys Setup Guide
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
