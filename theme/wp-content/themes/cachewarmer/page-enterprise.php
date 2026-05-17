<?php
/**
 * Template Name: Enterprise
 * Enterprise features and plans page.
 */
$page_og_title = 'Enterprise - CacheWarmer';
$page_description = 'CacheWarmer Enterprise: multi-site management, white-label branding, audit logging, automated reports, and priority support for agencies and large-scale operations.';
get_header();
cachewarmer_breadcrumb('Enterprise');
?>

<!-- Hero -->
<section class="gradient-hero">
    <div class="container text-center">
        <h1>CacheWarmer for Enterprise</h1>
        <p class="hero-subtitle">Multi-site management, advanced configuration, automated reporting, and priority support. Built for agencies, networks, and mission-critical deployments.</p>
        <div class="hero-buttons">
            <a href="https://dross.net/contact/?topic=cachewarmer" class="btn btn-white btn-lg" title="Contact Sales for CacheWarmer Enterprise">
                <?php cachewarmer_icon('send', '', 20); ?> Contact Sales
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Enterprise Pricing">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<!-- Everything in Premium + More -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Everything in Premium, <span class="text-gradient">Plus More</span></h2>
            <p>Enterprise includes all 12 warming targets, smart warming, analytics, and monitoring &mdash; plus exclusive features for large-scale operations.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('users'); ?></div>
                <h3 class="card-title">Multi-Site Dashboard</h3>
                <p class="card-description">Manage all your domains from a single unified dashboard. Per-site statistics, centralized configuration, and one-click warming across your entire network.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('star'); ?></div>
                <h3 class="card-title">White-Label Branding</h3>
                <p class="card-description">Remove CacheWarmer branding and replace it with your own. Present cache warming as part of your own service to clients.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('cloudflare'); ?></div>
                <h3 class="card-title">CDN Purge + Warm</h3>
                <p class="card-description">Purge and re-warm edge caches via API for Cloudflare, Imperva, and Akamai. Auto-detection of zones makes setup effortless.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('file-check'); ?></div>
                <h3 class="card-title">Automated Reports</h3>
                <p class="card-description">Generate PDF or HTML reports per warming job automatically. Ideal for client reporting, compliance documentation, and audit trails.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('shield'); ?></div>
                <h3 class="card-title">Audit Logging</h3>
                <p class="card-description">Full audit trail of all API calls, warming triggers, and configuration changes. Meet compliance requirements with detailed records.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('bell'); ?></div>
                <h3 class="card-title">Advanced Alerts</h3>
                <p class="card-description">Performance regression alerts (>50% slowdown), quota exhaustion notifications, and SSL expiry warnings delivered automatically.</p>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Configuration -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Advanced <span class="text-gradient">Configuration</span></h2>
            <p>Fine-tune every aspect of the warming process to match your infrastructure.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('settings'); ?></div>
                <h3 class="card-title">Custom User-Agent &amp; Headers</h3>
                <p class="card-description">Configure custom user-agent strings for CDN-specific cache rules. Inject authentication tokens or internal markers into warming requests.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('layout'); ?></div>
                <h3 class="card-title">Custom Viewports</h3>
                <p class="card-description">Define tablet, 4K, or custom viewport sizes beyond the default desktop and mobile profiles. Warm caches for every device your visitors use.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('lock'); ?></div>
                <h3 class="card-title">Authenticated Warming</h3>
                <p class="card-description">Warm pages behind login walls using cookies or session tokens. Essential for intranets, paywalled content, and member-only areas.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('activity'); ?></div>
                <h3 class="card-title">Conditional Warming</h3>
                <p class="card-description">Skip URLs with fresh CDN cache by checking <code>Age</code> and <code>max-age</code> headers. Avoid unnecessary warming and save resources.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('refresh'); ?></div>
                <h3 class="card-title">Sitemap Change Polling</h3>
                <p class="card-description">Automatically detect sitemap changes every N hours and trigger warming jobs. No manual intervention required.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('queue'); ?></div>
                <h3 class="card-title">Custom Warm Sequence</h3>
                <p class="card-description">Define the exact order in which warming targets are executed. Prioritize CDN warming before social media, or any other sequence.</p>
            </div>
        </div>
    </div>
</section>

<!-- Integrations -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Integrations &amp; <span class="text-gradient">Automation</span></h2>
            <p>Connect CacheWarmer to your existing tools and workflows.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('zap'); ?></div>
                <h3 class="card-title">Webhook Notifications</h3>
                <p class="card-description">Receive notifications when warming jobs complete, fail, or encounter issues. Integrate with your monitoring stack.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('link'); ?></div>
                <h3 class="card-title">Zapier / n8n / Make</h3>
                <p class="card-description">Connect warming events to Zapier, n8n, or Make for no-code automation. Trigger actions based on warming results.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('key'); ?></div>
                <h3 class="card-title">IP Whitelist &amp; API Security</h3>
                <p class="card-description">Restrict REST API access to specific IP addresses. Unlimited API rate limits for high-volume integrations.</p>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Built for <span class="text-gradient">Your Use Case</span></h2>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('building'); ?></div>
                <h3 class="card-title">Agencies</h3>
                <p class="card-description">White-label dashboard, manage all client sites centrally, webhook notifications for job completion, and automated client reports.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('shopping-cart'); ?></div>
                <h3 class="card-title">E-Commerce</h3>
                <p class="card-description">Keep product pages, categories, and inventory current across all cache layers. Integrate with CI/CD deployment pipelines.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('file-text'); ?></div>
                <h3 class="card-title">Publishing &amp; Media</h3>
                <p class="card-description">Time-sensitive content with immediate social media preview refresh. Automatic warming on publish for breaking news.</p>
            </div>
        </div>
    </div>
</section>

<!-- Enterprise Plans -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Enterprise Plans</h2>
            <p>Scale from a handful of sites to an entire network.</p>
        </div>
        <div class="grid grid-3 gap-8">
            <div class="card">
                <h3 class="card-title">Starter</h3>
                <div class="pricing-price" style="font-size: var(--text-3xl);">&euro;599<span style="font-size: var(--text-base); font-weight: 500;">/year</span></div>
                <ul class="pricing-features">
                    <li><?php cachewarmer_icon('check', '', 18); ?> Up to 5 sites</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> All Enterprise features</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Email support</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Lifetime option: &euro;1,499</li>
                </ul>
            </div>
            <div class="card">
                <h3 class="card-title">Professional</h3>
                <div class="pricing-price" style="font-size: var(--text-3xl);">&euro;1,799<span style="font-size: var(--text-base); font-weight: 500;">/year</span></div>
                <ul class="pricing-features">
                    <li><?php cachewarmer_icon('check', '', 18); ?> Up to 25 sites</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> All Enterprise features</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Dedicated account manager</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Lifetime option: &euro;4,499</li>
                </ul>
            </div>
            <div class="card">
                <h3 class="card-title">Corporate</h3>
                <div class="pricing-price" style="font-size: var(--text-3xl);">from &euro;5,999<span style="font-size: var(--text-base); font-weight: 500;">/year</span></div>
                <ul class="pricing-features">
                    <li><?php cachewarmer_icon('check', '', 18); ?> Unlimited sites</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> All Enterprise features</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Custom SLA &amp; white-label</li>
                    <li><?php cachewarmer_icon('check', '', 18); ?> Dedicated support</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Ready to Scale Your Cache Warming?</h2>
        <p class="hero-subtitle">Talk to our team about your requirements. We will build a plan around your needs.</p>
        <div class="hero-buttons">
            <a href="https://dross.net/contact/?topic=cachewarmer" class="btn btn-white btn-lg" title="Contact Sales for CacheWarmer Enterprise">
                <?php cachewarmer_icon('send', '', 20); ?> Contact Sales
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing Comparison">
                <?php cachewarmer_icon('tag', '', 20); ?> Compare Plans
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
