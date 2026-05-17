<?php
/**
 * Template Name: Features
 * Features page template.
 */
$page_og_title = 'Features - CacheWarmer';
$page_description = 'Explore all 12 warming targets: Schema Validation, CDN, Facebook, LinkedIn, Twitter/X, IndexNow, Google, Bing, Pinterest, Cloudflare, Imperva, and Akamai. Plus smart warming, analytics, and monitoring.';
get_header();
cachewarmer_breadcrumb('Features');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <h1>Features</h1>
        <p>Every module, explained in detail.</p>
    </div>
</section>

<!-- Schema Validation -->
<section class="section section-white" id="schema-validation">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('file-check'); ?></div>
                    <h2>Schema Validation (Structured Data)</h2>
                </div>
                <p>Before CacheWarmer warms a single cache, it validates the structured data on every page in your sitemap. This pre-flight check ensures your JSON-LD, Microdata, and RDFa markup is correct before search engines and social platforms see it.</p>
                <p>The validator detects missing required properties, invalid schema types, and formatting errors according to Google's structured data guidelines. Results are shown in a dedicated report with per-URL error and warning breakdowns.</p>
                <h3 class="mt-6 mb-4">Key Capabilities</h3>
                <ul class="feature-list">
                    <li>Validates JSON-LD, Microdata, and RDFa structured data</li>
                    <li>Detects missing required properties and invalid types</li>
                    <li>Per-URL error and warning breakdown</li>
                    <li>Filterable report (all, errors only, warnings, valid)</li>
                    <li>Export schema report as CSV or JSON</li>
                    <li>Runs locally - no external API keys required</li>
                </ul>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <span class="badge badge-primary" style="margin-top: var(--space-4);">New in v1.1.0</span>
                <p class="mt-4"><a href="https://developers.google.com/search/docs/appearance/structured-data?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Google Structured Data Documentation"><?php cachewarmer_icon('external-link', '', 14); ?> Google Structured Data Guide</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# config.yaml
schemaValidation:
  enabled: true
  concurrency: 2
  timeout: 15000
  delayBetweenRequests: 1000

# Schema report API
GET /api/schema-report?jobId=xxx
POST /api/schema-report/export
  { "jobId": "xxx", "format": "csv" }', 'Configuration'); ?>
            </div>
        </div>
    </div>
</section>

<!-- CDN Cache Warming -->
<section class="section section-gray" id="cdn-cache-warming">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('globe'); ?></div>
                    <h2>CDN Cache Warming</h2>
                </div>
                <p>CacheWarmer uses Puppeteer to launch a headless Chromium browser and visit every URL in your sitemap. This triggers CDN edge nodes and reverse proxies to cache the fully rendered page.</p>
                <p>Unlike simple HTTP requests, Headless Chrome executes JavaScript, loads all resources, and generates the same response a real browser would. This means your CDN caches the complete, rendered page &mdash; including dynamically loaded content.</p>
                <h3 class="mt-6 mb-4">Key Capabilities</h3>
                <ul class="feature-list">
                    <li>Configurable concurrency (process multiple URLs in parallel)</li>
                    <li>Adjustable delay between requests to respect server limits</li>
                    <li>Custom User-Agent string for cache-specific behavior</li>
                    <li>Configurable viewport size for responsive cache warming</li>
                    <li>Wait-until options (load, DOMContentLoaded, networkidle)</li>
                </ul>
                <span class="badge badge-free" style="margin-top: var(--space-4);">Available in all plans</span>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
CACHE_WARM_ENABLED=true
CACHE_WARM_CONCURRENCY=5
CACHE_WARM_DELAY_MS=1000
CACHE_WARM_USER_AGENT=CacheWarmer/1.0
CACHE_WARM_VIEWPORT_WIDTH=1920
CACHE_WARM_VIEWPORT_HEIGHT=1080
CACHE_WARM_WAIT_UNTIL=networkidle0', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Facebook -->
<section class="section section-white" id="facebook">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('facebook'); ?></div>
                    <h2>Facebook Sharing Debugger</h2>
                </div>
                <p>When you share a link on Facebook, it uses cached Open Graph metadata. If you've updated your page title, description, or image, Facebook may still show the old version.</p>
                <p>CacheWarmer calls the Facebook Graph API scrape endpoint for each URL, forcing Facebook to re-fetch your Open Graph tags. This ensures that when anyone shares your link, the preview is always current.</p>
                <?php cachewarmer_callout('Requires a Facebook App ID and App Secret. See the <a href="' . esc_url(home_url('/api-keys/#facebook')) . '" title="Facebook API Keys Setup Guide">API Keys setup guide</a> for instructions.', 'info'); ?>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <p class="mt-4"><a href="https://developers.facebook.com/tools/debug/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Facebook Sharing Debugger - Test Open Graph Tags"><?php cachewarmer_icon('external-link', '', 14); ?> Facebook Sharing Debugger</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('POST https://graph.facebook.com/
  ?id=https://example.com/page
  &scrape=true
  &access_token=YOUR_TOKEN

Response:
{
  "url": "https://example.com/page",
  "title": "Your Page Title",
  "description": "Updated description",
  "image": [{ "url": "..." }]
}', 'API Call'); ?>
            </div>
        </div>
    </div>
</section>

<!-- LinkedIn -->
<section class="section section-gray" id="linkedin">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('linkedin'); ?></div>
                    <h2>LinkedIn Post Inspector</h2>
                </div>
                <p>LinkedIn caches link preview data aggressively. CacheWarmer automates what the LinkedIn Post Inspector does manually &mdash; forcing LinkedIn to re-crawl and update its cached metadata for your URLs.</p>
                <p>This ensures that when your content is shared on LinkedIn, the preview card always shows the latest title, description, and image.</p>
                <?php cachewarmer_callout('LinkedIn has strict rate limits. CacheWarmer respects them with configurable delays and exponential backoff.', 'warning'); ?>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <p class="mt-4"><a href="https://www.linkedin.com/post-inspector/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="LinkedIn Post Inspector - Test Link Previews"><?php cachewarmer_icon('external-link', '', 14); ?> LinkedIn Post Inspector</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
LINKEDIN_ENABLED=true
LINKEDIN_SESSION_COOKIE=your_session_cookie
LINKEDIN_DELAY_MS=3000
LINKEDIN_MAX_RETRIES=3', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Twitter/X -->
<section class="section section-white" id="twitter">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('twitter'); ?></div>
                    <h2>Twitter/X Card Cache</h2>
                </div>
                <p>Twitter/X caches card previews when a URL is first shared. CacheWarmer pre-warms this cache by triggering Twitter's card validator for each URL in your sitemap.</p>
                <p>This means every link shared on Twitter/X will display the correct card with your latest title, description, and image &mdash; right from the first share.</p>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <p class="mt-4"><a href="https://cards-dev.x.com/validator?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="X Card Validator - Test Twitter Card Previews"><?php cachewarmer_icon('external-link', '', 14); ?> X Card Validator</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
TWITTER_ENABLED=true
TWITTER_DELAY_MS=2000
TWITTER_MAX_RETRIES=3', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Pinterest -->
<section class="section section-gray" id="pinterest">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('pinterest'); ?></div>
                    <h2>Pinterest Rich Pin Validator</h2>
                </div>
                <p>Pinterest Rich Pins pull metadata from your pages to display enhanced previews. When you update your content, Pinterest may continue showing outdated information.</p>
                <p>CacheWarmer refreshes your Rich Pin Open Graph metadata for every URL in your sitemap, ensuring Pinterest always displays current titles, descriptions, and images.</p>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <span class="badge badge-primary" style="margin-top: var(--space-4);">New in v1.1.0</span>
                <p class="mt-4"><a href="https://developers.pinterest.com/tools/url-debugger/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Pinterest Rich Pin Validator - Test Rich Pin Metadata"><?php cachewarmer_icon('external-link', '', 14); ?> Pinterest URL Debugger</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
PINTEREST_ENABLED=true
PINTEREST_DELAY_MS=2000
PINTEREST_MAX_RETRIES=3', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- IndexNow -->
<section class="section section-white" id="indexnow">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('zap'); ?></div>
                    <h2>IndexNow Protocol</h2>
                </div>
                <p>IndexNow is an open protocol that enables websites to instantly notify participating search engines about content changes. Supported by Bing, Yandex, Seznam, Naver, and others.</p>
                <p>CacheWarmer submits your URLs directly to the IndexNow API, ensuring search engines discover your new and updated content immediately instead of waiting for the next crawl cycle.</p>
                <?php cachewarmer_callout('IndexNow requires a key file hosted at your domain root. See the <a href="' . esc_url(home_url('/api-keys/#indexnow')) . '" title="IndexNow API Key Setup Guide">setup guide</a>.', 'info'); ?>
                <span class="badge badge-free" style="margin-top: var(--space-4);">Available in all plans</span>
                <p class="mt-4"><a href="https://www.indexnow.org/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="IndexNow Protocol - Instant Search Engine Notification"><?php cachewarmer_icon('external-link', '', 14); ?> indexnow.org</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('POST https://api.indexnow.org/indexnow
Content-Type: application/json

{
  "host": "example.com",
  "key": "your-indexnow-key",
  "urlList": [
    "https://example.com/page-1",
    "https://example.com/page-2",
    "https://example.com/page-3"
  ]
}', 'API Call'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Google Search Console -->
<section class="section section-gray" id="google">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('search'); ?></div>
                    <h2>Google Search Console API</h2>
                </div>
                <p>Submit URLs directly to Google for inspection and indexing via the Google Search Console API. This is the fastest way to get Google to discover and index your new or updated content.</p>
                <p>CacheWarmer manages Google's daily quota (200 URL inspections per day) automatically, prioritizing new and recently updated URLs.</p>
                <?php cachewarmer_callout('Requires a Google Cloud service account with Search Console API access. Daily limit: 200 URLs. See the <a href="' . esc_url(home_url('/api-keys/#google')) . '" title="Google Search Console API Setup Guide">setup guide</a>.', 'info'); ?>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <p class="mt-4"><a href="https://search.google.com/search-console/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Google Search Console - Monitor Search Performance"><?php cachewarmer_icon('external-link', '', 14); ?> Google Search Console</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
GOOGLE_ENABLED=true
GOOGLE_SERVICE_ACCOUNT_JSON=./config/google-sa.json
GOOGLE_DAILY_QUOTA=200
GOOGLE_DELAY_MS=1500', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Bing Webmaster Tools -->
<section class="section section-white" id="bing">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('search'); ?></div>
                    <h2>Bing Webmaster Tools API</h2>
                </div>
                <p>Direct URL submission to Bing via their Webmaster API, complementing IndexNow for comprehensive Bing coverage. This ensures Bing discovers your content through multiple channels.</p>
                <p>CacheWarmer handles authentication, rate limiting, and error handling automatically. Supports batch submission of up to 500 URLs per request.</p>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Premium</span>
                <p class="mt-4"><a href="https://www.bing.com/webmasters/?ref=cachewarmer" class="tool-link" target="_blank" rel="noopener" title="Bing Webmaster Tools - Monitor Bing Search Performance"><?php cachewarmer_icon('external-link', '', 14); ?> Bing Webmaster Tools</a></p>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
BING_ENABLED=true
BING_API_KEY=your_bing_api_key
BING_DELAY_MS=1000
BING_MAX_RETRIES=3', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Cloudflare -->
<section class="section section-gray" id="cloudflare">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('cloudflare'); ?></div>
                    <h2>Cloudflare Cache Purge + Warm</h2>
                </div>
                <p>For sites behind Cloudflare, CacheWarmer integrates directly with the Cloudflare Zone API to purge stale cache entries and immediately re-warm them with fresh content.</p>
                <p>This two-step process ensures your Cloudflare edge caches are never stale: first purge the old content, then immediately warm with the new version. Auto-detection of Cloudflare zones makes setup straightforward.</p>
                <?php cachewarmer_callout('Requires a Cloudflare API token with Zone.Cache Purge permissions. Enterprise plan only. See the <a href="' . esc_url(home_url('/api-keys/#cloudflare')) . '" title="Cloudflare API Setup Guide">API Keys setup guide</a>.', 'info'); ?>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Enterprise</span>
                <span class="badge badge-primary" style="margin-top: var(--space-4);">New in v1.1.0</span>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
CLOUDFLARE_ENABLED=true
CLOUDFLARE_API_TOKEN=your_cf_api_token
CLOUDFLARE_ZONE_ID=your_zone_id
CLOUDFLARE_PURGE_BEFORE_WARM=true', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Imperva -->
<section class="section section-white" id="imperva">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('imperva'); ?></div>
                    <h2>Imperva Cache Purge + Warm</h2>
                </div>
                <p>For sites protected by Imperva (formerly Incapsula), CacheWarmer integrates with the Imperva API to purge stale cache entries and re-warm them with fresh content.</p>
                <p>Works the same way as Cloudflare integration: purge first, then immediately warm. Supports both site-level and resource-level cache purging for granular control.</p>
                <?php cachewarmer_callout('Requires an Imperva API ID and API key with cache purge permissions. Enterprise plan only. See the <a href="' . esc_url(home_url('/api-keys/#imperva')) . '" title="Imperva API Setup Guide">API Keys setup guide</a>.', 'info'); ?>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Enterprise</span>
                <span class="badge badge-primary" style="margin-top: var(--space-4);">New in v1.1.0</span>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
IMPERVA_ENABLED=true
IMPERVA_API_ID=your_imperva_api_id
IMPERVA_API_KEY=your_imperva_api_key
IMPERVA_SITE_ID=your_site_id
IMPERVA_PURGE_BEFORE_WARM=true', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Akamai -->
<section class="section section-gray" id="akamai">
    <div class="container">
        <div class="feature-detail">
            <div class="feature-detail-content">
                <div class="flex items-center gap-4 mb-6">
                    <div class="card-icon"><?php cachewarmer_icon('akamai'); ?></div>
                    <h2>Akamai Cache Purge + Warm</h2>
                </div>
                <p>For sites behind Akamai CDN, CacheWarmer integrates with the Akamai Fast Purge API (CCU v3) to invalidate stale cache entries and immediately re-warm them.</p>
                <p>Supports both URL-level and CP code-based purging. Fast Purge invalidation typically completes within seconds, followed by immediate re-warming with fresh content.</p>
                <?php cachewarmer_callout('Requires Akamai EdgeGrid API credentials with CCU (Content Control Utility) access. Enterprise plan only. See the <a href="' . esc_url(home_url('/api-keys/#akamai')) . '" title="Akamai API Setup Guide">API Keys setup guide</a>.', 'info'); ?>
                <span class="badge badge-pro" style="margin-top: var(--space-4);">Enterprise</span>
                <span class="badge badge-primary" style="margin-top: var(--space-4);">New in v1.1.0</span>
            </div>
            <div class="feature-detail-code">
                <?php cachewarmer_code_block('# .env configuration
AKAMAI_ENABLED=true
AKAMAI_CLIENT_SECRET=your_client_secret
AKAMAI_HOST=your_host.purge.akamaiapis.net
AKAMAI_ACCESS_TOKEN=your_access_token
AKAMAI_CLIENT_TOKEN=your_client_token
AKAMAI_PURGE_BEFORE_WARM=true', 'Environment'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Smart Warming -->
<section class="section section-gray" id="smart-warming">
    <div class="container">
        <div class="section-header">
            <h2>Smart Warming <span class="badge badge-pro">Premium</span></h2>
            <p>Warm smarter, not harder. Only process URLs that have actually changed.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('activity'); ?></div>
                <h3 class="card-title">Diff-Detection</h3>
                <p class="card-description">Compares sitemap <code>lastmod</code> timestamps between runs. Only URLs with updated content are re-warmed, saving time and API quotas.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('trending-up'); ?></div>
                <h3 class="card-title">Priority-Based</h3>
                <p class="card-description">Process high-priority URLs first based on sitemap <code>&lt;priority&gt;</code> values. Your most important pages are always warmed first.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('shield'); ?></div>
                <h3 class="card-title">Conditional (Enterprise)</h3>
                <p class="card-description">Skip URLs that already have a fresh CDN cache by checking <code>Age</code> and <code>max-age</code> response headers before warming.</p>
            </div>
        </div>
    </div>
</section>

<!-- Analytics & Reporting -->
<section class="section section-white" id="analytics">
    <div class="container">
        <div class="section-header">
            <h2>Analytics &amp; Reporting <span class="badge badge-pro">Premium</span></h2>
            <p>Understand your cache performance with detailed insights.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('bar-chart'); ?></div>
                <h3 class="card-title">Cache Hit/Miss Analysis</h3>
                <p class="card-description">Parse CDN response headers (<code>X-Cache</code>, <code>CF-Cache-Status</code>) to visualize cache effectiveness across your site.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('trending-up'); ?></div>
                <h3 class="card-title">Performance Trending</h3>
                <p class="card-description">Track response times per URL across warming runs. Spot performance regressions before they impact your users.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('activity'); ?></div>
                <h3 class="card-title">Success Rate Dashboard</h3>
                <p class="card-description">Per-target success and failure statistics with historical trending. See which warming targets perform best.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('clock'); ?></div>
                <h3 class="card-title">Quota Usage Tracker</h3>
                <p class="card-description">Visual progress bars for Google and Bing daily quotas. Automatic alerts at 80% and 100% usage thresholds.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('download'); ?></div>
                <h3 class="card-title">CSV/JSON Export</h3>
                <p class="card-description">Export warming results, failed URLs, and skipped URLs in CSV or JSON format for further analysis or reporting.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('file-check'); ?></div>
                <h3 class="card-title">Automated Reports (Enterprise)</h3>
                <p class="card-description">Generate PDF or HTML reports per warming job automatically. Perfect for client reporting and audit trails.</p>
            </div>
        </div>
    </div>
</section>

<!-- Monitoring & Alerting -->
<section class="section section-gray" id="monitoring">
    <div class="container">
        <div class="section-header">
            <h2>Monitoring &amp; Alerting <span class="badge badge-pro">Premium</span></h2>
            <p>Catch issues before they affect your visitors.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('link'); ?></div>
                <h3 class="card-title">Broken Link Detection</h3>
                <p class="card-description">Flag URLs returning 404 or 5xx status codes during warming. Export broken links as a report for your team to fix.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('lock'); ?></div>
                <h3 class="card-title">SSL Expiry Warnings</h3>
                <p class="card-description">Get notified 30 days before your SSL certificates expire. Prevent unexpected downtime and SEO penalties.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('bell'); ?></div>
                <h3 class="card-title">Performance Alerts (Enterprise)</h3>
                <p class="card-description">Automatic alerts when response times increase by more than 50%. Catch performance regressions early.</p>
            </div>
        </div>
    </div>
</section>

<!-- Rate Limiting -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Built-in Rate Limiting &amp; Throttling</h2>
            <p>Every module respects service-specific rate limits with configurable delays and intelligent retry logic.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th scope="col">Service</th>
                        <th scope="col">Default Rate</th>
                        <th scope="col">Configurable</th>
                        <th scope="col">Retry Strategy</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Schema Validation</strong></td>
                        <td>2 concurrent, 1s delay</td>
                        <td class="table-check">Yes</td>
                        <td>Continue on failure</td>
                    </tr>
                    <tr>
                        <td><strong>CDN Warming</strong></td>
                        <td>5 concurrent, 1s delay</td>
                        <td class="table-check">Yes</td>
                        <td>Exponential backoff</td>
                    </tr>
                    <tr>
                        <td><strong>Facebook</strong></td>
                        <td>1 req/s</td>
                        <td class="table-check">Yes</td>
                        <td>Exponential backoff, max 3</td>
                    </tr>
                    <tr>
                        <td><strong>LinkedIn</strong></td>
                        <td>1 req/3s</td>
                        <td class="table-check">Yes</td>
                        <td>Exponential backoff, max 3</td>
                    </tr>
                    <tr>
                        <td><strong>Twitter/X</strong></td>
                        <td>1 req/2s</td>
                        <td class="table-check">Yes</td>
                        <td>Exponential backoff, max 3</td>
                    </tr>
                    <tr>
                        <td><strong>Pinterest</strong></td>
                        <td>1 req/2s</td>
                        <td class="table-check">Yes</td>
                        <td>Exponential backoff, max 3</td>
                    </tr>
                    <tr>
                        <td><strong>IndexNow</strong></td>
                        <td>Batch of 10,000/req</td>
                        <td class="table-check">Yes</td>
                        <td>Retry on 429/5xx</td>
                    </tr>
                    <tr>
                        <td><strong>Google</strong></td>
                        <td>200/day quota</td>
                        <td class="table-check">Yes</td>
                        <td>Quota-aware scheduling</td>
                    </tr>
                    <tr>
                        <td><strong>Bing</strong></td>
                        <td>1 req/s</td>
                        <td class="table-check">Yes</td>
                        <td>Exponential backoff, max 3</td>
                    </tr>
                    <tr>
                        <td><strong>Cloudflare</strong></td>
                        <td>30 URLs/batch</td>
                        <td class="table-check">Yes</td>
                        <td>Rate-limit aware</td>
                    </tr>
                    <tr>
                        <td><strong>Imperva</strong></td>
                        <td>100 URLs/batch</td>
                        <td class="table-check">Yes</td>
                        <td>Rate-limit aware</td>
                    </tr>
                    <tr>
                        <td><strong>Akamai</strong></td>
                        <td>50 URLs/batch</td>
                        <td class="table-check">Yes</td>
                        <td>Rate-limit aware</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Ready to Automate Your Cache Warming?</h2>
        <p class="hero-subtitle">Get started in minutes with Docker or install from source.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-white btn-lg" title="CacheWarmer Documentation - Installation &amp; Configuration Guide">
                <?php cachewarmer_icon('book', '', 20); ?> Read the Docs
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
