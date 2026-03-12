<?php
/**
 * Homepage Template
 */
$page_og_title = 'CacheWarmer - Warm Your Caches. Boost Your Web Performance.';
$page_description = 'What is cache warming and why does your website need it? CacheWarmer automatically warms CDN caches, refreshes social media previews, and notifies search engines for WordPress, Drupal, and Node.js.';
get_header();
?>

<!-- Hero Section -->
<section class="gradient-hero">
    <div class="container text-center">
        <h1>Warm Your Caches.<br>Boost Your Web Performance.</h1>
        <p class="hero-subtitle">CacheWarmer automatically warms CDN caches, updates social media previews, and submits your pages to search engines. Available as a WordPress plugin, Drupal module, or self-hosted Node.js service.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-white btn-lg" title="Get Started with CacheWarmer - Quick Setup Guide">
                <?php cachewarmer_icon('book', '', 20); ?> Get Started
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<!-- What Is Cache Warming - Beginner's Guide -->
<section class="section section-white" id="what-is-cache-warming">
    <div class="container">
        <div class="section-header">
            <h2>What Is <span class="text-gradient">Cache Warming</span>?</h2>
            <p>A plain-language guide for website owners who want faster pages, better social sharing, and quicker search engine visibility.</p>
        </div>

        <div class="article max-w-4xl mx-auto">

            <div class="article-analogy-tile">
                <div class="article-analogy-icon"><?php cachewarmer_icon('zap', '', 28); ?></div>
                <div class="article-analogy-content">
                    <h3>Think of your website like a restaurant kitchen</h3>
                    <p>Imagine a restaurant that only starts cooking after a customer sits down. The first guest waits 20 minutes for their meal, even though every guest after that gets served in 5. That first experience is terrible - and many customers leave before the food arrives.</p>
                    <p>Your website works the same way. When someone visits a page for the first time, the server has to build that page from scratch - pulling content from databases, assembling templates, loading images. This takes time. <strong>Cache warming is like prepping meals before the restaurant opens</strong> so every guest is served instantly.</p>
                </div>
            </div>

            <div class="article-highlight">
                <div class="article-highlight-icon"><?php cachewarmer_icon('zap', '', 24); ?></div>
                <div>
                    <strong>In one sentence:</strong> Cache warming automatically visits all your pages in the background so they are ready and fast when a real visitor arrives.
                </div>
            </div>

            <h3>Why does this matter for your business?</h3>
            <p>Slow websites lose visitors, sales, and search rankings. Studies consistently show that even a one-second delay in page load time can reduce conversions by 7%. Here is what cache warming does for you:</p>

            <div class="grid grid-3 gap-6 article-benefits">
                <div class="card">
                    <div class="card-icon"><?php cachewarmer_icon('clock'); ?></div>
                    <h4 class="card-title">Faster Pages for Every Visitor</h4>
                    <p class="card-description">No visitor ever hits a slow, uncached page. Your site feels instant, even after you publish new content or your hosting provider clears its cache.</p>
                </div>
                <div class="card">
                    <div class="card-icon"><?php cachewarmer_icon('link'); ?></div>
                    <h4 class="card-title">Social Links That Look Right</h4>
                    <p class="card-description">When you share a link on Facebook, LinkedIn, or Twitter, the preview shows the correct title, image, and description - not outdated or blank content.</p>
                </div>
                <div class="card">
                    <div class="card-icon"><?php cachewarmer_icon('search'); ?></div>
                    <h4 class="card-title">Google Finds You Faster</h4>
                    <p class="card-description">Instead of waiting days or weeks for search engines to discover your new pages, cache warming notifies Google and Bing the moment you publish.</p>
                </div>
            </div>

            <h3>What exactly happens without cache warming?</h3>
            <p>Without cache warming, several things go wrong - usually without you noticing:</p>

            <div class="article-list">
                <div class="article-list-item">
                    <span class="article-list-number">1</span>
                    <div>
                        <strong>Your first visitors get the slowest experience.</strong> The page has to be generated from scratch. By the time the cache is filled, those visitors are already gone.
                    </div>
                </div>
                <div class="article-list-item">
                    <span class="article-list-number">2</span>
                    <div>
                        <strong>Social media shows the wrong preview.</strong> Facebook, LinkedIn, and Twitter cache your page information. If you update a title or image, they keep showing the old version until someone manually forces a refresh.
                    </div>
                </div>
                <div class="article-list-item">
                    <span class="article-list-number">3</span>
                    <div>
                        <strong>Search engines are slow to notice changes.</strong> Google and Bing crawl on their own schedule. New pages or updates can take days to appear in search results, costing you traffic and revenue.
                    </div>
                </div>
                <div class="article-list-item">
                    <span class="article-list-number">4</span>
                    <div>
                        <strong>You waste time doing it manually.</strong> Visiting the Facebook Debugger, the LinkedIn Post Inspector, and the Google Search Console one URL at a time is not realistic if you have more than a handful of pages.
                    </div>
                </div>
            </div>

            <h3>How CacheWarmer solves this automatically</h3>
            <p>CacheWarmer reads your website's sitemap - the list of all your pages - and takes care of everything in the background:</p>

            <div class="article-steps">
                <div class="article-step">
                    <div class="article-step-icon"><?php cachewarmer_icon('globe', '', 20); ?></div>
                    <div>
                        <strong>Warms your CDN and server caches</strong> - visits every page so the cached version is ready before a real visitor arrives.
                    </div>
                </div>
                <div class="article-step">
                    <div class="article-step-icon"><?php cachewarmer_icon('facebook', '', 20); ?></div>
                    <div>
                        <strong>Refreshes social media previews</strong> - tells Facebook, LinkedIn, Twitter/X, and Pinterest to fetch the latest title, description, and image for every page.
                    </div>
                </div>
                <div class="article-step">
                    <div class="article-step-icon"><?php cachewarmer_icon('send', '', 20); ?></div>
                    <div>
                        <strong>Notifies search engines</strong> - pings Google, Bing, and other search engines immediately so your new and updated content appears in search results faster.
                    </div>
                </div>
                <div class="article-step">
                    <div class="article-step-icon"><?php cachewarmer_icon('refresh', '', 20); ?></div>
                    <div>
                        <strong>Runs on autopilot</strong> - set it up once and it runs on a schedule. No manual work, no pages to visit, no tools to open.
                    </div>
                </div>
            </div>

            <h3>Do I need technical knowledge?</h3>
            <p>No. If you can install a WordPress plugin or a Drupal module, you can use CacheWarmer. The setup takes about 5 minutes:</p>
            <ol>
                <li>Install the plugin or module on your website</li>
                <li>CacheWarmer automatically detects your sitemap</li>
                <li>Click "Warm Now" or set up a schedule - done</li>
            </ol>
            <p>For the free plan, that is literally all you need. Premium features like social media refreshing and search engine notification require a few API keys, but we provide <a href="<?php echo esc_url(home_url('/api-keys/')); ?>" title="CacheWarmer API Keys Setup Guide">step-by-step guides with screenshots</a> for each one.</p>

            <div class="article-cta">
                <h3>Ready to speed up your website?</h3>
                <p>Start with the free plan - no credit card required. Upgrade to Premium when you want social media and search engine integrations.</p>
                <div class="hero-buttons">
                    <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-primary btn-lg" title="Get Started with CacheWarmer - Quick Setup Guide">
                        <?php cachewarmer_icon('book', '', 20); ?> Get Started Free
                    </a>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                        <?php cachewarmer_icon('tag', '', 20); ?> Compare Plans
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Platforms Section -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Available on <span class="text-gradient">Your Platform</span></h2>
            <p>Choose the integration that fits your stack. Same powerful warming engine, native to your platform.</p>
        </div>
        <div class="grid grid-3 gap-8">
            <a href="<?php echo esc_url(home_url('/wordpress/')); ?>" class="card platform-card" title="CacheWarmer WordPress Plugin - Automated Cache Warming for WordPress">
                <div class="card-icon"><?php cachewarmer_icon('wordpress'); ?></div>
                <h3 class="card-title">WordPress Plugin</h3>
                <p class="card-description">Install directly from your WordPress dashboard. Native admin UI, WP-Cron scheduling, and zero-config setup. Works with any WordPress site.</p>
                <span class="card-link">Learn more <?php cachewarmer_icon('arrow-right', '', 16); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/drupal/')); ?>" class="card platform-card" title="CacheWarmer Drupal Module - Automated Cache Warming for Drupal">
                <div class="card-icon"><?php cachewarmer_icon('drupal'); ?></div>
                <h3 class="card-title">Drupal Module</h3>
                <p class="card-description">A native Drupal module with admin configuration, Drush commands, and Queue API integration. Supports Drupal 10 and 11.</p>
                <span class="card-link">Learn more <?php cachewarmer_icon('arrow-right', '', 16); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/self-hosted/')); ?>" class="card platform-card" title="CacheWarmer Self-Hosted - Deploy with Docker &amp; Node.js">
                <div class="card-icon"><?php cachewarmer_icon('server'); ?></div>
                <h3 class="card-title">Self-Hosted (Node.js)</h3>
                <p class="card-description">Standalone Node.js service with REST API, BullMQ job queue, and React dashboard. Deploy via Docker or install from source.</p>
                <span class="card-link">Learn more <?php cachewarmer_icon('arrow-right', '', 16); ?></span>
            </a>
        </div>
    </div>
</section>

<!-- Problem Section -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Your Fresh Content Is <span class="text-gradient">Invisible</span></h2>
            <p>You publish new content, but caches are cold, social previews are stale, and search engines don't know about it yet.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card card-problem">
                <div class="card-icon"><?php cachewarmer_icon('server'); ?></div>
                <h3 class="card-title">Cold CDN Caches</h3>
                <p class="card-description">Your visitors hit origin servers while CDN edges serve stale content. First visitors get slow pages.</p>
            </div>
            <div class="card card-problem">
                <div class="card-icon"><?php cachewarmer_icon('facebook'); ?></div>
                <h3 class="card-title">Stale Facebook Previews</h3>
                <p class="card-description">Share a link and Facebook shows the old title, description, or image. Manual scraping doesn't scale.</p>
            </div>
            <div class="card card-problem">
                <div class="card-icon"><?php cachewarmer_icon('linkedin'); ?></div>
                <h3 class="card-title">Outdated LinkedIn Cards</h3>
                <p class="card-description">LinkedIn caches Open Graph data aggressively. Updated content? LinkedIn doesn't know.</p>
            </div>
            <div class="card card-problem">
                <div class="card-icon"><?php cachewarmer_icon('twitter'); ?></div>
                <h3 class="card-title">Broken Twitter Cards</h3>
                <p class="card-description">Twitter/X card validator is manual. New pages have no cached cards until someone shares them.</p>
            </div>
            <div class="card card-problem">
                <div class="card-icon"><?php cachewarmer_icon('search'); ?></div>
                <h3 class="card-title">Delayed Search Indexing</h3>
                <p class="card-description">Google and Bing discover your new pages on their schedule, not yours. Days or weeks of lost traffic.</p>
            </div>
            <div class="card card-problem">
                <div class="card-icon"><?php cachewarmer_icon('clock'); ?></div>
                <h3 class="card-title">Manual Busywork</h3>
                <p class="card-description">Visiting the Facebook Debugger, LinkedIn Inspector, and Google Search Console one URL at a time is not sustainable.</p>
            </div>
        </div>
    </div>
</section>

<!-- Solution Section -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Automate Everything with <span class="text-gradient">One Sitemap</span></h2>
            <p>Point CacheWarmer at your XML sitemap and it handles the rest. Every cache warmed, every preview updated, every search engine notified.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card card-solution">
                <div class="card-icon"><?php cachewarmer_icon('zap'); ?></div>
                <h3 class="card-title">Instant Cache Warming</h3>
                <p class="card-description">Visits every URL from your sitemap, filling CDN and edge caches before real users arrive.</p>
            </div>
            <div class="card card-solution">
                <div class="card-icon"><?php cachewarmer_icon('facebook'); ?></div>
                <h3 class="card-title">Facebook Always Fresh</h3>
                <p class="card-description">Automatically hits the Facebook Sharing Debugger API for every URL. Open Graph previews are always current.</p>
            </div>
            <div class="card card-solution">
                <div class="card-icon"><?php cachewarmer_icon('linkedin'); ?></div>
                <h3 class="card-title">LinkedIn Stays Current</h3>
                <p class="card-description">Triggers LinkedIn Post Inspector to refresh cached metadata. Shared links always show the latest content.</p>
            </div>
            <div class="card card-solution">
                <div class="card-icon"><?php cachewarmer_icon('twitter'); ?></div>
                <h3 class="card-title">Twitter Cards Ready</h3>
                <p class="card-description">Pre-warms Twitter/X card cache so every shared link renders correctly from the first share.</p>
            </div>
            <div class="card card-solution">
                <div class="card-icon"><?php cachewarmer_icon('send'); ?></div>
                <h3 class="card-title">Instant Indexing</h3>
                <p class="card-description">Submits URLs via IndexNow, Google Search Console API, and Bing Webmaster Tools. Search engines know immediately.</p>
            </div>
            <div class="card card-solution">
                <div class="card-icon"><?php cachewarmer_icon('refresh'); ?></div>
                <h3 class="card-title">Set It and Forget It</h3>
                <p class="card-description">Configure once. CacheWarmer runs on a schedule, processing your entire sitemap automatically.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Up and Running in <span class="text-gradient">3 Easy Steps</span></h2>
        </div>
        <div class="grid grid-3 gap-8">
            <?php
            cachewarmer_step(1, 'Install on Your Platform', 'Install the WordPress plugin, Drupal module, or deploy the Node.js service via Docker. Takes under 5 minutes.');
            cachewarmer_step(2, 'Configure Your Targets', 'Choose which warming targets to enable: CDN, Facebook, LinkedIn, Twitter/X, Pinterest, IndexNow, Google, Bing. Add your API keys.');
            cachewarmer_step(3, 'Warm Automatically', 'CacheWarmer reads your sitemap, queues all URLs, and warms every cache on schedule. Monitor progress in the dashboard.');
            ?>
        </div>
    </div>
</section>

<!-- Key Modules -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>11 Warming Targets, <span class="text-gradient">One Unified Service</span></h2>
            <p>Each module handles a specific warming task. Together, they keep your entire web presence fresh.</p>
        </div>
        <div class="grid grid-3 gap-6">
            <?php
            cachewarmer_card(
                'CDN Cache Warming',
                'Visits every URL in your sitemap, triggering CDN edge nodes and reverse proxies to cache fully rendered pages.',
                'globe',
                home_url('/features/#cdn-cache-warming')
            );
            cachewarmer_card(
                'Facebook Sharing Debugger',
                'Calls the Facebook Graph API scrape endpoint, forcing Facebook to re-fetch your Open Graph tags for every URL.',
                'facebook',
                home_url('/features/#facebook')
            );
            cachewarmer_card(
                'LinkedIn Post Inspector',
                'Automates what the LinkedIn Post Inspector does manually, refreshing cached metadata across your sitemap.',
                'linkedin',
                home_url('/features/#linkedin')
            );
            cachewarmer_card(
                'Twitter/X Card Cache',
                'Pre-warms Twitter Card cache so shared links always render with the correct title, description, and image.',
                'twitter',
                home_url('/features/#twitter')
            );
            cachewarmer_card(
                'Pinterest Rich Pins',
                'Refreshes Rich Pin Open Graph metadata so Pinterest always shows your latest content and images.',
                'pinterest',
                home_url('/features/#pinterest')
            );
            cachewarmer_card(
                'IndexNow Protocol',
                'Submits URLs instantly to Bing, Yandex, Seznam, and other search engines via the IndexNow open protocol.',
                'zap',
                home_url('/features/#indexnow')
            );
            cachewarmer_card(
                'Google Search Console',
                'Submits URLs directly to Google for inspection and indexing via the Search Console API.',
                'search',
                home_url('/features/#google')
            );
            cachewarmer_card(
                'Bing Webmaster Tools',
                'Direct URL submission to Bing via their Webmaster API, complementing IndexNow for comprehensive coverage.',
                'search',
                home_url('/features/#bing')
            );
            cachewarmer_card(
                'Cloudflare Cache Purge',
                'Purge and re-warm Cloudflare edge caches via the Zone API. Enterprise only.',
                'cloudflare',
                home_url('/features/#cloudflare')
            );
            cachewarmer_card(
                'Imperva Cache Purge',
                'Purge and re-warm Imperva (Incapsula) CDN caches via their API. Enterprise only.',
                'imperva',
                home_url('/features/#imperva')
            );
            cachewarmer_card(
                'Akamai Cache Purge',
                'Purge and re-warm Akamai CDN caches via the Fast Purge API. Enterprise only.',
                'akamai',
                home_url('/features/#akamai')
            );
            ?>
        </div>
    </div>
</section>

<!-- Comparison Table -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Why Use CacheWarmer Instead of <span class="text-gradient">Manual Processes</span>?</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th scope="col">Feature</th>
                        <th scope="col">Manual Process</th>
                        <th scope="col">CacheWarmer</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>CDN Cache Warming</strong></td>
                        <td class="table-cross">Visit each URL manually</td>
                        <td class="table-check">Automated via headless browser</td>
                    </tr>
                    <tr>
                        <td><strong>Facebook Preview</strong></td>
                        <td class="table-cross">Paste URLs in Sharing Debugger</td>
                        <td class="table-check">API-based, all URLs</td>
                    </tr>
                    <tr>
                        <td><strong>LinkedIn Preview</strong></td>
                        <td class="table-cross">Open Post Inspector per URL</td>
                        <td class="table-check">Automated refresh</td>
                    </tr>
                    <tr>
                        <td><strong>Twitter/X Cards</strong></td>
                        <td class="table-cross">Use Card Validator per URL</td>
                        <td class="table-check">Pre-warmed automatically</td>
                    </tr>
                    <tr>
                        <td><strong>Search Indexing</strong></td>
                        <td class="table-cross">Wait for crawlers</td>
                        <td class="table-check">IndexNow + API submission</td>
                    </tr>
                    <tr>
                        <td><strong>URL Discovery</strong></td>
                        <td class="table-cross">Monitor sitemaps manually</td>
                        <td class="table-check">Automatic sitemap parsing</td>
                    </tr>
                    <tr>
                        <td><strong>Scheduling</strong></td>
                        <td class="table-cross">Remember to do it</td>
                        <td class="table-check">Cron-based, fully automated</td>
                    </tr>
                    <tr>
                        <td><strong>Rate Limiting</strong></td>
                        <td class="table-cross">Risk getting blocked</td>
                        <td class="table-check">Built-in throttling &amp; retries</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Frequently Asked Questions</h2>
        </div>
        <div class="max-w-3xl mx-auto">
            <?php
            cachewarmer_faq(
                'What is cache warming and why do I need it?',
                '<p>Cache warming is the process of proactively loading pages into a cache (CDN, reverse proxy, or browser) before real users request them. Without it, the first visitor to each page experiences slow load times as the server generates the response from scratch. CacheWarmer automates this for your entire sitemap.</p>',
                'what-is-cache-warming'
            );
            cachewarmer_faq(
                'Which platforms does CacheWarmer support?',
                '<p>CacheWarmer is available as a <a href="' . esc_url(home_url('/wordpress/')) . '" title="CacheWarmer WordPress Plugin">WordPress plugin</a>, a <a href="' . esc_url(home_url('/drupal/')) . '" title="CacheWarmer Drupal Module">Drupal module</a>, and a <a href="' . esc_url(home_url('/self-hosted/')) . '" title="CacheWarmer Self-Hosted Node.js Service">standalone Node.js service</a> that you can deploy via Docker. All platforms share the same warming engine and support up to 11 targets.</p>',
                'supported-platforms'
            );
            cachewarmer_faq(
                'Is there a free version?',
                '<p>Yes. The Free plan includes CDN cache warming and IndexNow submissions for up to 50 URLs at no cost. No credit card required. <a href="' . esc_url(home_url('/pricing/')) . '" title="CacheWarmer Pricing Plans">See all plans</a>.</p>',
                'free-version'
            );
            cachewarmer_faq(
                'How does CacheWarmer handle rate limiting?',
                '<p>Each module has configurable rate limits and delays. CacheWarmer uses exponential backoff for retries and respects per-service quotas (e.g., Google\'s 200 URL/day limit). You can adjust concurrency and delay settings per service in the configuration.</p>',
                'rate-limiting'
            );
            cachewarmer_faq(
                'How often should I run cache warming?',
                '<p>It depends on how frequently your content changes. Most sites benefit from running it once or twice daily. For high-traffic sites with frequent updates, you can run it every few hours. CacheWarmer supports scheduled automation on all platforms.</p>',
                'warming-frequency'
            );
            cachewarmer_faq(
                'What happens if a URL returns an error?',
                '<p>CacheWarmer logs the error and retries based on your configuration. Failed URLs are tracked with their error status, so you can review and address issues. Individual URL failures never block the entire warming job.</p>',
                'error-handling'
            );
            cachewarmer_faq(
                'How do I set up the API credentials?',
                '<p>Each service requires its own API credentials. We have step-by-step setup guides for <a href="' . esc_url(home_url('/api-keys/')) . '" title="CacheWarmer API Keys Setup Guide">every integration</a>, including Facebook App Token, LinkedIn OAuth, IndexNow key, Google Service Account, and Bing API key.</p>',
                'api-credentials-setup'
            );
            ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Start Warming Your Caches Today</h2>
        <p class="hero-subtitle">Free plan available. No credit card required.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-white btn-lg" title="Get Started with CacheWarmer - Quick Setup Guide">
                <?php cachewarmer_icon('book', '', 20); ?> Get Started
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
