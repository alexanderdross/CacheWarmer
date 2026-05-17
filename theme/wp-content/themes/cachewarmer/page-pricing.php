<?php
/**
 * Template Name: Pricing
 * Pricing page template with Stripe Checkout integration.
 */
$page_og_title = 'Pricing - CacheWarmer';
$page_description = 'Choose the right CacheWarmer plan: Free, Premium, or Enterprise. Transparent pricing for WordPress, Drupal, and self-hosted Node.js cache warming starting at €0.';
get_header();
cachewarmer_breadcrumb('Pricing');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <h1>Simple, Transparent Pricing</h1>
        <p>Start free. Upgrade when you need more power.</p>
    </div>
</section>

<!-- Billing Toggle -->
<section class="section section-gray">
    <div class="container">

        <div class="billing-toggle-wrapper">
            <div class="billing-toggle">
                <button type="button" class="billing-toggle-btn billing-toggle-active" data-billing="yearly">
                    Yearly <span class="billing-save">Save 17%</span>
                </button>
                <button type="button" class="billing-toggle-btn" data-billing="monthly">
                    Monthly
                </button>
            </div>
        </div>

        <!-- Platform Tabs -->
        <div class="platform-tabs-wrapper">
            <div class="tabs platform-tabs">
                <button type="button" class="tab-btn tab-btn-active" data-tab="wordpress">
                    <?php cachewarmer_icon('wordpress', '', 18); ?> WordPress
                </button>
                <button type="button" class="tab-btn" data-tab="drupal">
                    <?php cachewarmer_icon('drupal', '', 18); ?> Drupal
                </button>
                <button type="button" class="tab-btn" data-tab="selfhosted">
                    <?php cachewarmer_icon('docker', '', 18); ?> Self-Hosted
                </button>
            </div>
        </div>

        <!-- Pricing Grid -->
        <div class="pricing-grid grid grid-3 gap-8">

            <!-- Free -->
            <div class="pricing-card">
                <span class="badge badge-free">Free</span>
                <div class="pricing-price">&euro;0</div>
                <p class="pricing-period">Free forever</p>
                <ul class="pricing-features">
                    <li><?php cachewarmer_icon('check', '', 18); ?> CDN cache warming</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> IndexNow submissions</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Up to 50 URLs per job</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 2 sitemaps (1 external)</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 3 warming jobs per day</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 7-day log retention</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Manual warming only</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Community support</li>
                </ul>
                <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-outline btn-lg w-full" title="Get Started with CacheWarmer Free Plan">
                    <?php cachewarmer_icon('download', '', 20); ?> Get Started Free
                </a>
            </div>

            <!-- Premium (Featured) -->
            <div class="pricing-card pricing-card-featured">
                <span class="badge badge-pro">Premium</span>
                <div class="pricing-price">
                    <span class="price-yearly">&euro;99<span class="pricing-price-suffix">/year</span></span>
                    <span class="price-monthly" style="display:none;">&euro;9.90<span class="pricing-price-suffix">/month</span></span>
                </div>
                <p class="pricing-period">
                    <span class="period-wordpress">
                        <span class="period-yearly">WordPress &middot; Billed annually</span>
                        <span class="period-monthly" style="display:none;">WordPress &middot; Billed monthly</span>
                    </span>
                    <span class="period-drupal" style="display:none;">
                        <span class="period-yearly">Drupal &middot; <strong>&euro;129/year</strong></span>
                        <span class="period-monthly" style="display:none;">Drupal &middot; <strong>&euro;12.90/month</strong></span>
                    </span>
                    <span class="period-selfhosted" style="display:none;">
                        <span class="period-yearly">Self-Hosted &middot; <strong>&euro;129/year</strong></span>
                        <span class="period-monthly" style="display:none;">Self-Hosted &middot; <strong>&euro;12.90/month</strong></span>
                    </span>
                </p>
                <ul class="pricing-features">
                    <li><?php cachewarmer_icon('check', '', 18); ?> All 12 warming targets</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Schema Validation, CDN, IndexNow, Facebook, LinkedIn, Twitter/X, Google, Bing, Pinterest</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Up to 10,000 URLs per job</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 25 sitemaps (10 external)</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 50 warming jobs per day</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 90-day log retention</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Automatic scheduler</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Smart warming (diff-detection)</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Analytics &amp; monitoring</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> REST API access</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> CSV/JSON export</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Email support</li>
                </ul>
                <button type="button"
                    class="btn btn-primary btn-lg w-full cwlm-buy-button"
                    data-price-wp-yearly=""
                    data-price-wp-monthly=""
                    data-price-drupal-yearly=""
                    data-price-drupal-monthly=""
                    data-price-sh-yearly=""
                    data-price-sh-monthly=""
                    title="Get CacheWarmer Premium License - All Warming Targets">
                    <?php cachewarmer_icon('zap', '', 20); ?> Get Premium
                </button>
            </div>

            <!-- Enterprise -->
            <div class="pricing-card">
                <span class="badge badge-pro">Enterprise</span>
                <div class="pricing-price" style="font-size: var(--text-4xl);">
                    <span class="price-yearly">from &euro;599<span class="pricing-price-suffix">/year</span></span>
                    <span class="price-monthly" style="display:none;">from &euro;59.90<span class="pricing-price-suffix">/month</span></span>
                </div>
                <p class="pricing-period">
                    <span class="period-yearly">Up to 5 sites &middot; Billed annually</span>
                    <span class="period-monthly" style="display:none;">Up to 5 sites &middot; Billed monthly</span>
                </p>
                <ul class="pricing-features">
                    <li><?php cachewarmer_icon('check', '', 18); ?> Everything in Premium</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> CDN purge + warm (Cloudflare, Imperva, Akamai)</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Unlimited URLs &amp; sitemaps</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Unlimited warming jobs</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> 365-day log retention</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Multi-site dashboard</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Webhook integrations</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Audit logging</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Automated PDF/HTML reports</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> White-label option</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Priority support &amp; SLA</li>
                </ul>
                <a href="<?php echo esc_url(home_url('/enterprise/')); ?>" class="btn btn-accent btn-lg w-full" title="CacheWarmer Enterprise - Features &amp; Plans">
                    <?php cachewarmer_icon('building', '', 20); ?> Explore Enterprise Plans
                </a>
            </div>

        </div>
    </div>
</section>

<!-- Enterprise Tiers -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Enterprise Plans</h2>
            <p>Scale from a handful of sites to an entire network.</p>
        </div>
        <div class="grid grid-3 gap-8">
            <div class="card enterprise-tier-card">
                <h3 class="card-title">Enterprise Starter</h3>
                <div class="pricing-price" style="font-size: var(--text-3xl);">
                    <span class="price-yearly">&euro;599<span class="pricing-price-suffix">/year</span></span>
                    <span class="price-monthly" style="display:none;">&euro;59.90<span class="pricing-price-suffix">/month</span></span>
                </div>
                <p class="card-description">Up to <strong>5 sites</strong>. All Enterprise features included. Lifetime option: &euro;1,499.</p>
                <button type="button"
                    class="btn btn-accent btn-lg w-full cwlm-buy-button"
                    style="margin-top: var(--space-4);"
                    data-price-yearly=""
                    data-price-monthly=""
                    title="Buy Enterprise Starter">
                    <?php cachewarmer_icon('zap', '', 20); ?> Buy Starter
                </button>
            </div>
            <div class="card enterprise-tier-card">
                <h3 class="card-title">Enterprise Professional</h3>
                <div class="pricing-price" style="font-size: var(--text-3xl);">
                    <span class="price-yearly">&euro;1,799<span class="pricing-price-suffix">/year</span></span>
                    <span class="price-monthly" style="display:none;">&euro;179.90<span class="pricing-price-suffix">/month</span></span>
                </div>
                <p class="card-description">Up to <strong>25 sites</strong>. Dedicated account manager. Lifetime option: &euro;4,499.</p>
                <button type="button"
                    class="btn btn-accent btn-lg w-full cwlm-buy-button"
                    style="margin-top: var(--space-4);"
                    data-price-yearly=""
                    data-price-monthly=""
                    title="Buy Enterprise Professional">
                    <?php cachewarmer_icon('zap', '', 20); ?> Buy Professional
                </button>
            </div>
            <div class="card enterprise-tier-card">
                <h3 class="card-title">Enterprise Corporate</h3>
                <div class="pricing-price" style="font-size: var(--text-3xl);">from &euro;5,999<span class="pricing-price-suffix">/year</span></div>
                <p class="card-description"><strong>Unlimited sites</strong>. Custom SLA, dedicated support, white-label branding.</p>
                <a href="https://dross.net/contact/?topic=cachewarmer" class="btn btn-outline btn-lg w-full" style="margin-top: var(--space-4);" title="Contact Sales for Enterprise Corporate">
                    <?php cachewarmer_icon('send', '', 20); ?> Contact Sales
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Payment Methods & Trust -->
<section class="section section-gray">
    <div class="container">
        <div class="payment-trust">
            <div class="payment-trust-item">
                <?php cachewarmer_icon('shield', '', 24); ?>
                <div>
                    <strong>Secure Checkout</strong>
                    <p>Powered by Stripe. PCI-DSS compliant. Your card data never touches our servers.</p>
                </div>
            </div>
            <div class="payment-trust-item">
                <?php cachewarmer_icon('refresh', '', 24); ?>
                <div>
                    <strong>Cancel Anytime</strong>
                    <p>No lock-in. Cancel your subscription anytime from your account dashboard.</p>
                </div>
            </div>
            <div class="payment-trust-item">
                <?php cachewarmer_icon('key', '', 24); ?>
                <div>
                    <strong>Instant License Key</strong>
                    <p>Receive your license key via email within seconds after payment.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Warming Targets Comparison -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Warming Targets</h2>
            <p>See which warming targets are available in each plan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th scope="col">Target</th>
                        <th scope="col">Free</th>
                        <th scope="col">Premium</th>
                        <th scope="col">Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Schema Validation</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>CDN Cache Warming</td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>IndexNow Protocol</td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Facebook Sharing Debugger</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>LinkedIn Post Inspector</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Twitter/X Card Cache</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Google Search Console API</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Bing Webmaster Tools API</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Pinterest Rich Pin Validator</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Cloudflare Cache Purge + Warm</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Imperva Cache Purge + Warm</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Akamai Cache Purge + Warm</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Core Features Comparison -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Core Features</h2>
            <p>See exactly what is included in each plan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th scope="col">Feature</th>
                        <th scope="col">Free</th>
                        <th scope="col">Premium</th>
                        <th scope="col">Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Manual Warming</td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Automatic Scheduler</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Smart Warming (Diff-Detection)</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Priority-Based Warming</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>REST API</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Cache Hit/Miss Analysis</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Performance Trending</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Broken Link Detection</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>SSL Expiry Warnings</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>CSV/JSON Export</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Conditional Warming</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Custom User-Agent &amp; Headers</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>URL Exclude Patterns</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Webhook Integrations</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Multi-site Support</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Audit Logging</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>Automated Reports (PDF/HTML)</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                    <tr>
                        <td>White-label Option</td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-cross"><?php cachewarmer_icon('x-mark', '', 16); ?></td>
                        <td class="table-check"><?php cachewarmer_icon('check', '', 16); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Enterprise Contact -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Need a Custom Enterprise Plan?</h2>
        <p class="hero-subtitle">We tailor plans for large-scale operations, agencies, and multi-site networks. Get in touch and we will build a plan around your needs.</p>
        <div class="hero-buttons">
            <a href="https://dross.net/contact/?topic=cachewarmer" class="btn btn-white btn-lg" title="Contact Sales for Custom CacheWarmer Enterprise Plan">
                <?php cachewarmer_icon('send', '', 20); ?> Contact Sales
            </a>
        </div>
    </div>
</section>

<!-- Pricing FAQ -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Pricing FAQ</h2>
        </div>
        <div class="max-w-3xl mx-auto">
            <?php
            cachewarmer_faq(
                'Is the Free version really free?',
                '<p>Yes, the Free plan is completely free with no hidden costs, no credit card required, and no time limit. You get CDN cache warming and IndexNow submissions for up to 50 URLs. It is a great way to try CacheWarmer before committing to a paid plan.</p>',
                'is-free-version-really-free'
            );
            cachewarmer_faq(
                'What platforms are supported?',
                '<p>CacheWarmer supports WordPress, Drupal, and self-hosted Node.js deployments. The Premium plan starts at &euro;99/year for WordPress and &euro;129/year for Drupal. The Free plan works with all platforms.</p>',
                'pricing-supported-platforms'
            );
            cachewarmer_faq(
                'Can I upgrade later?',
                '<p>Absolutely. You can upgrade from Free to Premium or Enterprise at any time. Your configuration and history are preserved when you upgrade. If you start with Premium and outgrow it, upgrading to Enterprise is seamless.</p>',
                'upgrade-later'
            );
            cachewarmer_faq(
                'Do you offer lifetime licenses?',
                '<p>Yes! Lifetime licenses are available for Enterprise tiers. Enterprise Starter lifetime is &euro;1,499 (up to 5 sites) and Enterprise Professional lifetime is &euro;4,499 (up to 25 sites). One-time payment, updates included forever.</p>',
                'lifetime-licenses'
            );
            cachewarmer_faq(
                'What payment methods do you accept?',
                '<p>We accept all major credit cards (Visa, Mastercard, American Express) and SEPA direct debit. All payments are processed securely through Stripe. Your card data never touches our servers.</p>',
                'payment-methods'
            );
            cachewarmer_faq(
                'How do I receive my license key?',
                '<p>After a successful payment, your license key is generated automatically and sent to your email within seconds. You can then enter it in your WordPress plugin or Drupal module settings, or set it as an environment variable for self-hosted deployments.</p>',
                'receive-license-key'
            );
            cachewarmer_faq(
                'Can I cancel my subscription?',
                '<p>Yes, you can cancel your subscription at any time. Your license remains active until the end of the current billing period. There are no cancellation fees or hidden charges.</p>',
                'cancel-subscription'
            );
            ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
