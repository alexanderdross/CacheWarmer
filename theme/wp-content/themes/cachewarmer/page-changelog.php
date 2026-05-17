<?php
/**
 * Template Name: Changelog
 * Changelog page template.
 */
$page_og_title = 'Changelog - CacheWarmer';
$page_description = 'All notable changes to CacheWarmer. Version history and release notes.';
get_header();
cachewarmer_breadcrumb('Changelog');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <h1>Changelog</h1>
        <p>All notable changes to CacheWarmer.</p>
    </div>
</section>

<section class="section section-gray">
    <div class="container max-w-4xl mx-auto">

        <!-- v1.1.0 -->
        <div class="changelog-entry">
            <div class="changelog-entry-header">
                <span class="changelog-version">v1.1.0</span>
                <span class="changelog-date">March 2026</span>
            </div>

            <div class="changelog-category">
                <h2><?php cachewarmer_icon('check-circle', '', 18); ?> Added</h2>

                <h3>New Warming Targets</h3>
                <ul>
                    <li><strong>Schema Validation (Structured Data)</strong> &mdash; Validates JSON-LD, Microdata, and RDFa structured data on every URL before warming. Reports errors and warnings with exportable CSV/JSON reports. Available on Premium and Enterprise plans.</li>
                    <li><strong>Pinterest Rich Pin Validator</strong> &mdash; Refresh rich pin Open Graph metadata for all URLs. Available on Premium and Enterprise plans.</li>
                    <li><strong>Cloudflare Cache Purge + Warm</strong> &mdash; Zone API integration to purge and re-warm Cloudflare-cached pages automatically. Enterprise only.</li>
                    <li><strong>Imperva Cache Purge + Warm</strong> &mdash; Imperva (Incapsula) API integration to purge and re-warm Imperva-cached pages. Enterprise only.</li>
                    <li><strong>Akamai Cache Purge + Warm</strong> &mdash; Akamai Fast Purge (CCU v3) integration to invalidate and re-warm Akamai-cached pages. Enterprise only.</li>
                </ul>

                <h3>Premium Features</h3>
                <ul>
                    <li><strong>Smart Warming</strong> &mdash; Diff-detection via sitemap <code>lastmod</code>: only warm URLs whose content has actually changed.</li>
                    <li><strong>Priority-Based Warming</strong> &mdash; Process high-priority URLs first, based on sitemap <code>&lt;priority&gt;</code> values.</li>
                    <li><strong>Cache Hit/Miss Analysis</strong> &mdash; Parse CDN response headers (<code>X-Cache</code>, <code>CF-Cache-Status</code>) to visualize cache effectiveness.</li>
                    <li><strong>Performance Trending</strong> &mdash; Track response times per URL across warming runs to spot regressions.</li>
                    <li><strong>Service Success Rate Dashboard</strong> &mdash; Per-target success/failure statistics with historical trending.</li>
                    <li><strong>Quota Usage Tracker</strong> &mdash; Visual progress bars for Google/Bing daily quotas with alerts at 80% and 100%.</li>
                    <li><strong>Broken Link Detection</strong> &mdash; Flag URLs returning 404 or 5xx status codes, with exportable reports.</li>
                    <li><strong>SSL Certificate Expiry Warnings</strong> &mdash; 30-day advance notice when SSL certificates are about to expire.</li>
                    <li><strong>Schema Validation Report</strong> &mdash; Dedicated schema report UI with summary badges, per-URL error/warning breakdown, filtering, and CSV/JSON export.</li>
                    <li><strong>Failed/Skipped URL Export</strong> &mdash; Export failed and skipped URLs as CSV for review and action.</li>
                    <li><strong>Custom Timeout per Service</strong> &mdash; Set individual timeout values for each warming target.</li>
                </ul>

                <h3>Enterprise Features</h3>
                <ul>
                    <li><strong>Custom User-Agent Strings</strong> &mdash; Configure custom user-agent strings for CDN-specific cache rules.</li>
                    <li><strong>Custom HTTP Headers</strong> &mdash; Inject authentication tokens or internal markers into warming requests.</li>
                    <li><strong>Custom Viewport Definitions</strong> &mdash; Define tablet, 4K, or custom viewport sizes beyond desktop and mobile.</li>
                    <li><strong>Authenticated Page Warming</strong> &mdash; Warm pages behind authentication using cookies or session tokens.</li>
                    <li><strong>Sitemap Change Polling</strong> &mdash; Automatically detect sitemap changes every N hours and trigger warming.</li>
                    <li><strong>Conditional Warming</strong> &mdash; Skip URLs with fresh CDN cache based on <code>Age</code>/<code>max-age</code> headers.</li>
                    <li><strong>Custom Warm Sequence</strong> &mdash; Define the order in which warming targets are executed.</li>
                    <li><strong>Multi-Site Dashboard</strong> &mdash; Unified management dashboard for multiple domains with per-site statistics.</li>
                    <li><strong>Audit Logging</strong> &mdash; Track all API calls, triggers, and configuration changes for compliance.</li>
                    <li><strong>IP Whitelist</strong> &mdash; Restrict REST API access to specific IP addresses.</li>
                    <li><strong>Performance Regression Alerts</strong> &mdash; Automatic notification when response times increase by more than 50%.</li>
                    <li><strong>Quota Exhaustion Alerts</strong> &mdash; Notifications at 80% and 100% of Google/Bing daily quotas.</li>
                    <li><strong>Automated PDF/HTML Reports</strong> &mdash; Generate and deliver reports per warming job automatically.</li>
                    <li><strong>Zapier/n8n/Make Webhook Compatibility</strong> &mdash; Connect warming events to no-code automation platforms.</li>
                </ul>
            </div>

            <div class="changelog-category">
                <h2><?php cachewarmer_icon('refresh', '', 18); ?> Changed</h2>
                <ul>
                    <li><strong>URL Exclude Patterns</strong> are now an Enterprise-only feature. Free and Premium users will see a locked section in Settings with an upgrade prompt. Existing exclude patterns on non-Enterprise installations will no longer be applied during warming jobs.</li>
                    <li><strong>Email Notifications</strong> have been moved from Premium to Enterprise. Free and Premium users will see a locked section in Settings with an upgrade prompt. Existing notification settings on non-Enterprise installations will no longer trigger emails.</li>
                </ul>
            </div>

        </div>

        <!-- v0.1.0 -->
        <div class="changelog-entry">
            <div class="changelog-entry-header">
                <span class="changelog-version">v0.1.0</span>
                <span class="badge badge-primary">Initial Release</span>
                <span class="changelog-date">February 2026</span>
            </div>

            <div class="changelog-category">
                <h2><?php cachewarmer_icon('check-circle', '', 18); ?> Added</h2>
                <ul>
                    <li>Sitemap XML parser with automatic URL discovery</li>
                    <li>CDN cache warming via Headless Chrome (Puppeteer)</li>
                    <li>Facebook Sharing Debugger integration</li>
                    <li>LinkedIn Post Inspector integration</li>
                    <li>Twitter/X Card cache warming</li>
                    <li>IndexNow protocol support (Bing, Yandex, Seznam, Naver)</li>
                    <li>Google Search Console API integration</li>
                    <li>Bing Webmaster Tools API integration</li>
                    <li>BullMQ-based job queue with configurable rate limiting</li>
                    <li>SQLite database for job tracking and result storage</li>
                    <li>REST API for programmatic access (7 endpoints)</li>
                    <li>React SPA dashboard for monitoring and management</li>
                    <li>Docker support with pre-built images</li>
                    <li>Docker Compose configuration for production deployments</li>
                    <li>Environment-based configuration via .env file</li>
                    <li>Configurable concurrency and delay per service</li>
                    <li>Exponential backoff retry logic</li>
                    <li>Health check endpoint for monitoring</li>
                    <li>Comprehensive logging with configurable log levels</li>
                </ul>
            </div>
        </div>

    </div>
</section>

<?php get_footer(); ?>
