<?php
/**
 * Template Name: WordPress
 * WordPress plugin platform page.
 */
$page_og_title = 'CacheWarmer for WordPress - Cache Warming Plugin';
$page_description = 'WordPress cache warming plugin. Warm CDN caches, update Facebook, LinkedIn, Twitter previews, and submit URLs to Google and Bing. Install in minutes.';
get_header();
cachewarmer_breadcrumb('WordPress');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <div class="flex items-center justify-center gap-4 mb-4">
            <?php cachewarmer_icon('wordpress', '', 40); ?>
        </div>
        <h1>CacheWarmer for WordPress</h1>
        <p>A native WordPress plugin that warms your caches, updates social media previews, and notifies search engines. Install and configure from your WordPress dashboard.</p>
    </div>
</section>

<!-- Quick Install -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Install in <span class="text-gradient">Under 5 Minutes</span></h2>
        </div>
        <div class="grid grid-3 gap-8">
            <?php
            cachewarmer_step(1, 'Install the Plugin', 'Upload the plugin via your WordPress dashboard or install from the plugin directory. Activate it with one click.');
            cachewarmer_step(2, 'Configure Targets', 'Go to Settings &rarr; CacheWarmer. Enter your API keys and choose which warming targets to enable.');
            cachewarmer_step(3, 'Start Warming', 'Click "Warm Now" or let the scheduler run automatically. Monitor progress from the CacheWarmer dashboard in your admin panel.');
            ?>
        </div>
        <div class="text-center mt-8">
            <?php cachewarmer_code_block('wp plugin install cachewarmer --activate', 'WP-CLI'); ?>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Built for <span class="text-gradient">WordPress</span></h2>
            <p>Not a wrapper around an external service. A true WordPress plugin that uses native APIs and conventions.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('layout'); ?></div>
                <h3 class="card-title">Native Admin UI</h3>
                <p class="card-description">Full settings page in your WordPress admin. Configure all 12 warming targets, view logs, and trigger manual warming without leaving your dashboard.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('clock'); ?></div>
                <h3 class="card-title">WP-Cron Scheduling</h3>
                <p class="card-description">Automatic scheduling via WP-Cron. Set daily, twice-daily, or custom intervals. Works with external cron setups for reliability.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('sitemap'); ?></div>
                <h3 class="card-title">Auto Sitemap Detection</h3>
                <p class="card-description">Automatically detects your XML sitemap from popular plugins like Yoast SEO, Rank Math, All in One SEO, or the WordPress core sitemap.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('zap'); ?></div>
                <h3 class="card-title">Post-Publish Hook</h3>
                <p class="card-description">Optionally trigger cache warming when you publish or update a post. Individual URL warming without processing the entire sitemap.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('database'); ?></div>
                <h3 class="card-title">WordPress Database</h3>
                <p class="card-description">Uses WordPress database tables for job tracking and result storage. No external database required. Clean uninstall removes all data.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('shield'); ?></div>
                <h3 class="card-title">WordPress Standards</h3>
                <p class="card-description">Follows WordPress coding standards, uses nonces for security, capability checks for permissions, and proper escaping throughout.</p>
            </div>
        </div>
    </div>
</section>

<!-- All 12 Targets -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Up to 12 <span class="text-gradient">Warming Targets</span></h2>
            <p>Enable the targets you need. Disable the rest. Each one is independently configurable.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th scope="col">Target</th>
                        <th scope="col">What It Does</th>
                        <th scope="col">Free</th>
                        <th scope="col">Premium</th>
                        <th scope="col">Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Schema Validation</strong></td>
                        <td>Validates structured data markup</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>CDN Cache Warming</strong></td>
                        <td>Visits URLs to fill CDN edge caches</td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>IndexNow</strong></td>
                        <td>Notifies Bing, Yandex, Seznam</td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Facebook</strong></td>
                        <td>Refreshes Open Graph previews</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>LinkedIn</strong></td>
                        <td>Updates LinkedIn post previews</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Twitter/X</strong></td>
                        <td>Pre-warms Twitter Card cache</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pinterest</strong></td>
                        <td>Refreshes Rich Pin metadata</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Google</strong></td>
                        <td>Submits URLs via Indexing API</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Bing</strong></td>
                        <td>Submits URLs via Webmaster API</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cloudflare</strong></td>
                        <td>Purge + warm Cloudflare cache</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Imperva</strong></td>
                        <td>Purge + warm Imperva cache</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Akamai</strong></td>
                        <td>Purge + warm Akamai cache</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Requirements -->
<section class="section section-gray">
    <div class="container max-w-4xl mx-auto">
        <div class="section-header">
            <h2>Requirements</h2>
        </div>
        <div class="grid grid-2 gap-6">
            <div class="card">
                <h3 class="card-title">System Requirements</h3>
                <ul class="feature-list">
                    <li>WordPress 6.0 or later</li>
                    <li>PHP 8.0 or later</li>
                    <li>PHP OpenSSL extension (for Google Indexing API)</li>
                    <li>PHP SimpleXML extension (for sitemap parsing)</li>
                    <li>MySQL 5.7+ or MariaDB 10.3+</li>
                    <li>HTTPS recommended</li>
                </ul>
            </div>
            <div class="card">
                <h3 class="card-title">Compatible With</h3>
                <ul class="feature-list">
                    <li>Yoast SEO, Rank Math, AIOSEO sitemaps</li>
                    <li>WordPress core XML sitemaps</li>
                    <li>WP Super Cache, W3 Total Cache, LiteSpeed</li>
                    <li>Multisite networks (Enterprise plan)</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Ready to Warm Your WordPress Caches?</h2>
        <p class="hero-subtitle">Install the plugin and start warming in under 5 minutes. Free plan available.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-white btn-lg" title="CacheWarmer Documentation - WordPress Installation Guide">
                <?php cachewarmer_icon('book', '', 20); ?> Read the Docs
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
