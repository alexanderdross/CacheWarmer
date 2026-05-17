<?php
/**
 * Template Name: Drupal
 * Drupal module platform page.
 */
$page_og_title = 'CacheWarmer for Drupal - Cache Warming Module';
$page_description = 'Drupal cache warming module. Warm CDN caches, update social media previews, and submit URLs to search engines. Native Drupal 10 and 11 module.';
get_header();
cachewarmer_breadcrumb('Drupal');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <div class="flex items-center justify-center gap-4 mb-4">
            <?php cachewarmer_icon('drupal', '', 40); ?>
        </div>
        <h1>CacheWarmer for Drupal</h1>
        <p>A native Drupal module with admin configuration, Drush commands, and Queue API integration. Supports Drupal 10 and 11.</p>
    </div>
</section>

<!-- Quick Install -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Install with <span class="text-gradient">Composer</span></h2>
        </div>
        <div class="grid grid-3 gap-8">
            <?php
            cachewarmer_step(1, 'Install via Composer', 'Require the module with Composer and enable it via Drush or the Extend page.');
            cachewarmer_step(2, 'Configure Targets', 'Navigate to Configuration &rarr; System &rarr; CacheWarmer. Enter your API keys and select warming targets.');
            cachewarmer_step(3, 'Start Warming', 'Run warming manually, via Drush, or let the cron scheduler handle it automatically.');
            ?>
        </div>
        <div class="text-center mt-8">
            <?php cachewarmer_code_block('composer require drupal/cachewarmer
drush en cachewarmer -y', 'Shell'); ?>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Built for <span class="text-gradient">Drupal</span></h2>
            <p>A proper Drupal module that follows Drupal coding standards and integrates with the Drupal ecosystem.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('layout'); ?></div>
                <h3 class="card-title">Admin Configuration Form</h3>
                <p class="card-description">Full configuration form under Configuration &rarr; System. Manage all warming targets, API keys, and scheduling from the Drupal admin.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('terminal'); ?></div>
                <h3 class="card-title">Drush Commands</h3>
                <p class="card-description">Trigger warming jobs, check status, and manage sitemaps directly from the command line with custom Drush commands.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('queue'); ?></div>
                <h3 class="card-title">Queue API Integration</h3>
                <p class="card-description">Uses Drupal's Queue API for reliable job processing. Works with the default database queue or advanced queue backends.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('clock'); ?></div>
                <h3 class="card-title">Cron Integration</h3>
                <p class="card-description">Automatic warming via Drupal cron. Compatible with the Ultimate Cron module for fine-grained scheduling control.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('sitemap'); ?></div>
                <h3 class="card-title">Sitemap Auto-Detection</h3>
                <p class="card-description">Automatically detects sitemaps from the Simple XML Sitemap module, XML Sitemap module, or custom sitemap paths.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('shield'); ?></div>
                <h3 class="card-title">Drupal Standards</h3>
                <p class="card-description">Follows Drupal coding standards, uses the Configuration API, permission system, and proper dependency injection.</p>
            </div>
        </div>
    </div>
</section>

<!-- Drush Commands -->
<section class="section section-white">
    <div class="container max-w-4xl mx-auto">
        <div class="section-header">
            <h2>Drush Commands</h2>
            <p>Full control from the command line.</p>
        </div>
        <?php cachewarmer_code_block('# Warm all URLs from all configured sitemaps
drush cachewarmer:warm

# Warm a specific sitemap
drush cachewarmer:warm --sitemap=https://example.com/sitemap.xml

# Warm only specific targets
drush cachewarmer:warm --targets=cdn,facebook,indexnow

# Check status of the last warming job
drush cachewarmer:status

# List all configured sitemaps
drush cachewarmer:sitemaps', 'Shell'); ?>
    </div>
</section>

<!-- All 12 Targets -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Up to 12 <span class="text-gradient">Warming Targets</span></h2>
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
<section class="section section-white">
    <div class="container max-w-4xl mx-auto">
        <div class="section-header">
            <h2>Requirements</h2>
        </div>
        <div class="grid grid-2 gap-6">
            <div class="card">
                <h3 class="card-title">System Requirements</h3>
                <ul class="feature-list">
                    <li>Drupal 10.2+ or Drupal 11</li>
                    <li>PHP 8.1 or later</li>
                    <li>Composer for installation</li>
                    <li>Drush 12+ recommended</li>
                </ul>
            </div>
            <div class="card">
                <h3 class="card-title">Compatible With</h3>
                <ul class="feature-list">
                    <li>Simple XML Sitemap module</li>
                    <li>XML Sitemap module</li>
                    <li>Drupal Commerce sites</li>
                    <li>Multisite installations (Enterprise plan)</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Ready to Warm Your Drupal Caches?</h2>
        <p class="hero-subtitle">Install with Composer and start warming in minutes. Free plan available.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-white btn-lg" title="CacheWarmer Documentation - Drupal Installation Guide">
                <?php cachewarmer_icon('book', '', 20); ?> Read the Docs
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
