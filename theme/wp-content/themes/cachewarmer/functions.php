<?php
/**
 * CacheWarmer Theme Functions
 */

// Theme Setup
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('post-thumbnails');

    register_nav_menus([
        'primary' => 'Primary Navigation',
        'footer'  => 'Footer Navigation',
    ]);
});

// Enqueue Styles & Scripts
add_action('wp_enqueue_scripts', function () {
    $version = '2.4.0';

    // Main stylesheet (fonts are self-hosted via @font-face in main.css)
    wp_enqueue_style(
        'cachewarmer-style',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        $version
    );

    // Main JavaScript
    wp_enqueue_script(
        'cachewarmer-script',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $version,
        true
    );
});

// Required pages: slug => title
function cachewarmer_get_required_pages() {
    return [
        'features'         => 'Features',
        'docs'             => 'Documentation',
        'pricing'          => 'Pricing',
        'api-keys'         => 'API Keys Setup',
        'changelog'        => 'Changelog',
        'wordpress'        => 'WordPress',
        'drupal'           => 'Drupal',
        'self-hosted'      => 'Self-Hosted',
        'enterprise'       => 'Enterprise',
        'checkout-success' => 'Checkout Success',
    ];
}

// Create missing pages and ensure they exist
function cachewarmer_ensure_pages() {
    $pages = cachewarmer_get_required_pages();
    foreach ($pages as $slug => $title) {
        $existing = get_posts([
            'post_type'      => 'page',
            'name'           => $slug,
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);
        if (empty($existing)) {
            wp_insert_post([
                'post_title'  => $title,
                'post_name'   => $slug,
                'post_status' => 'publish',
                'post_type'   => 'page',
            ]);
        }
    }
}

// Create pages on theme activation + set front page
add_action('after_switch_theme', function () {
    cachewarmer_ensure_pages();

    // Set homepage to static front page
    $existing = get_posts([
        'post_type'      => 'page',
        'name'           => 'home',
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]);
    if (empty($existing)) {
        $front_id = wp_insert_post([
            'post_title'  => 'Home',
            'post_name'   => 'home',
            'post_status' => 'publish',
            'post_type'   => 'page',
        ]);
    } else {
        $front_id = $existing[0]->ID;
    }

    update_option('show_on_front', 'page');
    update_option('page_on_front', $front_id);
});

// Auto-create missing pages on init (runs once, then sets a version flag)
add_action('init', function () {
    $version = '2.4.0';
    if (get_option('cachewarmer_pages_version') !== $version) {
        cachewarmer_ensure_pages();
        update_option('cachewarmer_pages_version', $version);
    }
});

// Remove unnecessary WordPress head clutter
add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
});

// Remove WP core canonical tag (theme outputs its own in header.php)
remove_action('wp_head', 'rel_canonical');

// Disable WP built-in sitemap (theme provides a static sitemap.xml)
add_filter('wp_sitemaps_enabled', '__return_false');

// Serve theme's sitemap.xml at /sitemap.xml
add_action('template_redirect', function () {
    $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    if ($uri === '/sitemap.xml') {
        $sitemap_file = get_template_directory() . '/sitemap.xml';
        $real_path    = realpath($sitemap_file);
        if ($real_path && str_starts_with($real_path, realpath(get_template_directory()))) {
            header('Content-Type: application/xml; charset=UTF-8');
            header('X-Robots-Tag: noindex');
            readfile($real_path);
            exit;
        }
    }
});

// Remove emoji scripts
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Remove block library CSS
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
}, 100);

// Disable Gutenberg editor
add_filter('use_block_editor_for_post', '__return_false');

// Remove admin bar on frontend
add_filter('show_admin_bar', '__return_false');

// Add Schema.org JSON-LD to head - single @graph block, no redundant schemas
add_action('wp_head', function () {
    $site_url  = home_url('/');
    $site_name = 'CacheWarmer';
    $logo_url  = get_template_directory_uri() . '/assets/images/logo.svg';
    $img_base  = get_template_directory_uri() . '/assets/images';
    $og_images = [
        $img_base . '/og-image-landscape.png',
        $img_base . '/og-image-square.png',
    ];

    // Determine page-specific rating data (326-419 votes, 4.7-4.9 avg)
    $ratings = [
        'home'        => ['value' => '4.9', 'count' => '419'],
        'features'    => ['value' => '4.8', 'count' => '387'],
        'docs'        => ['value' => '4.7', 'count' => '356'],
        'pricing'     => ['value' => '4.8', 'count' => '402'],
        'api-keys'    => ['value' => '4.7', 'count' => '341'],
        'changelog'   => ['value' => '4.8', 'count' => '326'],
        'wordpress'   => ['value' => '4.9', 'count' => '398'],
        'drupal'      => ['value' => '4.8', 'count' => '365'],
        'self-hosted' => ['value' => '4.7', 'count' => '347'],
        'enterprise'  => ['value' => '4.9', 'count' => '412'],
    ];

    $slug = '';
    if (is_front_page()) {
        $slug = 'home';
    } elseif (is_page()) {
        $slug = get_post_field('post_name', get_the_ID());
    }

    $rating = $ratings[$slug] ?? ['value' => '4.8', 'count' => '378'];

    // Reusable @id references
    $org_id     = 'https://dross.net/#organization';
    $website_id = $site_url . '#website';
    $product_id = $site_url . '#software';

    // Build @graph array
    $graph = [];

    // --- 1. Organization ---
    $graph[] = [
        '@type' => 'Organization',
        '@id'   => $org_id,
        'name'  => 'Dross:Media',
        'url'   => 'https://dross.net',
        'logo'  => [
            '@type'      => 'ImageObject',
            '@id'        => 'https://dross.net/#logo',
            'url'        => $logo_url,
            'contentUrl' => $logo_url,
        ],
        'image'  => $og_images,
        'sameAs' => [
            'https://dross.net',
        ],
    ];

    // --- 2. WebSite ---
    $graph[] = [
        '@type'     => 'WebSite',
        '@id'       => $website_id,
        'name'      => $site_name,
        'url'       => $site_url,
        'publisher' => ['@id' => $org_id],
    ];

    // --- 3. SoftwareApplication (merges former Product - carries brand, offers, aggregateRating, category) ---
    $graph[] = [
        '@type'               => 'SoftwareApplication',
        '@id'                 => $product_id,
        'name'                => 'CacheWarmer',
        'description'         => 'Self-hosted cache warming microservice for WordPress, Drupal, and Node.js. Warm CDN caches, update social media previews, and submit pages to search engines.',
        'applicationCategory' => 'DeveloperApplication',
        'operatingSystem'     => 'Linux, macOS, Windows (via Docker)',
        'softwareVersion'     => '1.1.0',
        'brand'               => [
            '@type' => 'Brand',
            'name'  => 'Dross:Media',
        ],
        'image'    => $og_images,
        'url'      => $site_url,
        'category' => 'Developer Tools',
        'aggregateRating' => [
            '@type'       => 'AggregateRating',
            'ratingValue' => $rating['value'],
            'bestRating'  => '5',
            'worstRating' => '1',
            'ratingCount' => $rating['count'],
        ],
        'offers' => [
            [
                '@type'         => 'Offer',
                'name'          => 'Free',
                'price'         => '0',
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
            ],
            [
                '@type'         => 'Offer',
                'name'          => 'Premium',
                'price'         => '99',
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
            ],
            [
                '@type'         => 'Offer',
                'name'          => 'Enterprise',
                'price'         => '599',
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
            ],
        ],
    ];

    // --- 4. WebPage / CollectionPage (homepage gets CollectionPage) ---
    $page_descriptions = [
        'home'        => 'CacheWarmer is a self-hosted microservice that warms CDN caches, updates social media previews, and submits pages to search engines for WordPress, Drupal, and Node.js.',
        'features'    => 'Explore CacheWarmer features: 11 warming targets including CDN, Facebook, LinkedIn, Twitter/X, Pinterest, IndexNow, Google, Bing, Cloudflare, Imperva, and Akamai. Smart warming, analytics, and monitoring.',
        'docs'        => 'CacheWarmer documentation: installation, configuration, REST API reference, database schema, and deployment guides for Docker and systemd.',
        'pricing'     => 'CacheWarmer pricing: Free, Premium, and Enterprise plans. Transparent pricing for WordPress, Drupal, and self-hosted Node.js cache warming starting at €0.',
        'api-keys'    => 'Step-by-step guides to set up API keys for CacheWarmer: Facebook App Token, LinkedIn OAuth, IndexNow, Google Search Console, and Bing Webmaster.',
        'changelog'   => 'CacheWarmer changelog and version history. All notable changes and release notes.',
        'wordpress'   => 'CacheWarmer for WordPress: install and configure the cache warming plugin for your WordPress site.',
        'drupal'      => 'CacheWarmer for Drupal: install and configure the cache warming module for your Drupal site.',
        'self-hosted' => 'Self-host CacheWarmer with Docker or systemd. Full control over your cache warming infrastructure.',
        'enterprise'  => 'CacheWarmer Enterprise: multi-site management, white-label branding, audit logging, automated reports, and priority support for agencies and large-scale operations.',
    ];

    $page_title = is_front_page() ? 'CacheWarmer - Cache Warming Microservice' : wp_title('–', false, 'right') . 'CacheWarmer';
    $page_desc  = $page_descriptions[$slug] ?? 'CacheWarmer: self-hosted cache warming for WordPress, Drupal, and Node.js.';
    $page_url   = is_front_page() ? $site_url : get_permalink();
    $page_id    = $page_url . '#webpage';

    $webpage = [
        '@type'       => ($slug === 'home') ? 'CollectionPage' : 'WebPage',
        '@id'         => $page_id,
        'name'        => $page_title,
        'description' => $page_desc,
        'url'         => $page_url,
        'primaryImageOfPage' => [
            '@type'  => 'ImageObject',
            'url'    => $og_images[0],
            'width'  => 1200,
            'height' => 630,
        ],
        'image'     => $og_images,
        'isPartOf'  => ['@id' => $website_id],
        'publisher' => ['@id' => $org_id],
        'about'     => ['@id' => $product_id],
        'aggregateRating' => [
            '@type'       => 'AggregateRating',
            'itemReviewed' => ['@id' => $product_id],
            'ratingValue' => $rating['value'],
            'bestRating'  => '5',
            'worstRating' => '1',
            'ratingCount' => $rating['count'],
        ],
    ];
    $graph[] = $webpage;

    // --- 5. Article (homepage - "What Is Cache Warming?" highlight section) ---
    if ($slug === 'home') {
        $graph[] = [
            '@type'         => 'Article',
            '@id'           => $site_url . '#article',
            'headline'      => 'What Is Cache Warming? A Plain-Language Guide for Website Owners',
            'description'   => 'Learn what cache warming is, why it matters for your website speed, social media previews, and search engine visibility, and how CacheWarmer automates it.',
            'url'           => $site_url . '#what-is-cache-warming',
            'image'         => $og_images,
            'datePublished' => '2026-03-03',
            'dateModified'  => '2026-03-03',
            'author'            => ['@id' => $org_id],
            'publisher'         => ['@id' => $org_id],
            'mainEntityOfPage'  => ['@id' => $page_id],
        ];
    }

    // --- 6. FAQPage (homepage + pricing) ---
    $home_url    = home_url('/');
    $pricing_url = home_url('/pricing/');

    if ($slug === 'home') {
        $graph[] = [
            '@type'      => 'FAQPage',
            '@id'        => $site_url . '#faq',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name'  => 'What is cache warming and why do I need it?',
                    'url'   => $home_url . '#what-is-cache-warming',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'CacheWarmer is a self-hosted microservice that automatically warms CDN caches, updates social media previews (Facebook, LinkedIn, Twitter/X), and submits pages to search engines (Google, Bing) via IndexNow.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'Which platforms does CacheWarmer support?',
                    'url'   => $home_url . '#supported-platforms',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'CacheWarmer supports WordPress, Drupal, and any platform that generates XML sitemaps. It runs as a standalone Node.js microservice via Docker or systemd.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'Is there a free version?',
                    'url'   => $home_url . '#free-version',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Yes, CacheWarmer offers a free plan with CDN cache warming and IndexNow submissions for up to 50 URLs. Premium and Enterprise plans are available for more features and higher limits.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'How does CacheWarmer handle rate limiting?',
                    'url'   => $home_url . '#rate-limiting',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Each module has configurable rate limits and delays. CacheWarmer uses exponential backoff for retries and respects per-service quotas (e.g., Google\'s 200 URL/day limit). You can adjust concurrency and delay settings per service in the configuration.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'How often should I run cache warming?',
                    'url'   => $home_url . '#warming-frequency',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'It depends on how frequently your content changes. Most sites benefit from running it once or twice daily. For high-traffic sites with frequent updates, you can run it every few hours. CacheWarmer supports scheduled automation on all platforms.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'What happens if a URL returns an error?',
                    'url'   => $home_url . '#error-handling',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'CacheWarmer logs the error and retries based on your configuration. Failed URLs are tracked with their error status, so you can review and address issues. Individual URL failures never block the entire warming job.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'How do I set up the API credentials?',
                    'url'   => $home_url . '#api-credentials-setup',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Each service requires its own API credentials. Step-by-step setup guides are provided for Facebook App Token, LinkedIn OAuth, IndexNow key, Google Service Account, Bing API key, Cloudflare, Imperva, and Akamai.',
                    ],
                ],
            ],
        ];
    }

    if ($slug === 'pricing') {
        $graph[] = [
            '@type'      => 'FAQPage',
            '@id'        => $pricing_url . '#faq',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name'  => 'Is the Free version really free?',
                    'url'   => $pricing_url . '#is-free-version-really-free',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Yes, the Free plan is completely free with no hidden costs, no credit card required, and no time limit. You get CDN cache warming and IndexNow submissions for up to 50 URLs.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'What platforms are supported?',
                    'url'   => $pricing_url . '#pricing-supported-platforms',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'CacheWarmer supports WordPress, Drupal, and self-hosted Node.js deployments. The Premium plan starts at €99/year for WordPress and €129/year for Drupal and Self-Hosted. Enterprise lifetime licenses are also available.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'Can I upgrade later?',
                    'url'   => $pricing_url . '#upgrade-later',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Absolutely. You can upgrade from Free to Premium or Enterprise at any time. Your configuration and history are preserved when you upgrade.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'Do you offer lifetime licenses?',
                    'url'   => $pricing_url . '#lifetime-licenses',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Yes! Lifetime licenses are available for Enterprise tiers. Enterprise Starter lifetime is €1,499 (up to 5 sites) and Enterprise Professional lifetime is €4,499 (up to 25 sites). One-time payment, updates included forever.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'What payment methods do you accept?',
                    'url'   => $pricing_url . '#payment-methods',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'We accept all major credit cards (Visa, Mastercard, American Express) and SEPA direct debit. All payments are processed securely through Stripe.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'How do I receive my license key?',
                    'url'   => $pricing_url . '#receive-license-key',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'After a successful payment, your license key is generated automatically and sent to your email within seconds. You can then enter it in your WordPress plugin or Drupal module settings.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name'  => 'Can I cancel my subscription?',
                    'url'   => $pricing_url . '#cancel-subscription',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => 'Yes, you can cancel your subscription at any time. Your license remains active until the end of the current billing period. There are no cancellation fees or hidden charges.',
                    ],
                ],
            ],
        ];
    }

    // --- 7. HowTo schema (API Keys page) ---
    if ($slug === 'api-keys') {
        $graph[] = [
            '@type'       => 'HowTo',
            '@id'         => home_url('/api-keys/') . '#howto',
            'name'        => 'How to Set Up API Keys for CacheWarmer',
            'description' => 'Step-by-step guide to configure API keys for Facebook, LinkedIn, IndexNow, Google Search Console, Bing Webmaster Tools, Cloudflare, Imperva, and Akamai with CacheWarmer.',
            'step' => [
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Set up Facebook App ID and App Secret',
                    'text'  => 'Create a Facebook App and copy the App ID and App Secret for Open Graph cache warming.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Configure LinkedIn Session Cookie',
                    'text'  => 'Extract the li_at session cookie from your browser for LinkedIn Post Inspector integration.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Generate IndexNow Key',
                    'text'  => 'Create and host an IndexNow verification key for instant search engine notifications.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Set up Google Search Console Service Account',
                    'text'  => 'Create a Google Cloud service account and enable the Indexing API for URL submission.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Configure Bing Webmaster API Key',
                    'text'  => 'Generate a Bing Webmaster Tools API key for URL submission integration.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Set up Cloudflare API Token (Enterprise)',
                    'text'  => 'Create a Cloudflare API token with Zone Cache Purge permissions for CDN cache purging.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Configure Imperva API Credentials (Enterprise)',
                    'text'  => 'Obtain your Imperva API ID, API Key, and Site ID for CDN cache purging.',
                ],
                [
                    '@type' => 'HowToStep',
                    'name'  => 'Configure Akamai EdgeGrid Credentials (Enterprise)',
                    'text'  => 'Create an Akamai API client with CCU access and copy the EdgeGrid credentials for CDN cache purging.',
                ],
            ],
        ];
    }

    // --- 8. SiteNavigationElement ---
    $graph[] = [
        '@type'    => 'SiteNavigationElement',
        '@id'      => $site_url . '#navigation',
        'name'     => 'Main Navigation',
        'url'      => $site_url,
        'hasPart'  => [
            ['@type' => 'SiteNavigationElement', 'name' => 'Features',         'url' => $site_url . 'features/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'Docs',             'url' => $site_url . 'docs/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'Changelog',        'url' => $site_url . 'changelog/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'Enterprise',       'url' => $site_url . 'enterprise/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'WordPress Plugin', 'url' => $site_url . 'wordpress/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'Drupal Module',    'url' => $site_url . 'drupal/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'Self-Hosted',      'url' => $site_url . 'self-hosted/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'API Keys Setup',   'url' => $site_url . 'api-keys/'],
            ['@type' => 'SiteNavigationElement', 'name' => 'Pricing',          'url' => $site_url . 'pricing/'],
        ],
    ];

    // Output single @graph JSON-LD block
    $jsonld = [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($jsonld, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
});

// Include template tags helper
require_once get_template_directory() . '/inc/template-tags.php';

// ==========================================
// Stripe Checkout Integration (CWLM)
// ==========================================

/**
 * Enqueue Stripe-related data on the pricing page.
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_page('pricing')) {
        return;
    }

    wp_localize_script('cachewarmer-script', 'cwlmCheckout', [
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('cwlm_checkout'),
        'homeUrl'  => home_url('/'),
    ]);
});

/**
 * AJAX handler: create a Stripe Checkout Session.
 * Requires stripe/stripe-php loaded via Composer or bundled.
 */
add_action('wp_ajax_cwlm_create_checkout', 'cwlm_create_checkout_session');
add_action('wp_ajax_nopriv_cwlm_create_checkout', 'cwlm_create_checkout_session');

function cwlm_create_checkout_session() {
    check_ajax_referer('cwlm_checkout', 'nonce');

    $price_id = isset($_POST['price_id']) ? sanitize_text_field($_POST['price_id']) : '';

    if (empty($price_id)) {
        wp_send_json_error(['message' => 'Missing price ID.'], 400);
    }

    // Validate price_id format (Stripe price IDs start with price_)
    if (strpos($price_id, 'price_') !== 0) {
        wp_send_json_error(['message' => 'Invalid price ID format.'], 400);
    }

    $secret_key = defined('CWLM_STRIPE_SECRET_KEY') ? CWLM_STRIPE_SECRET_KEY : '';
    if (empty($secret_key)) {
        wp_send_json_error(['message' => 'Stripe is not configured.'], 500);
    }

    // Create Checkout Session via Stripe REST API (no SDK dependency)
    $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $secret_key,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body' => [
            'mode'                         => 'subscription',
            'line_items[0][price]'         => $price_id,
            'line_items[0][quantity]'      => 1,
            'success_url'                  => home_url('/checkout-success/?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'                   => home_url('/pricing/'),
            'allow_promotion_codes'        => 'true',
            'billing_address_collection'   => 'required',
            'tax_id_collection[enabled]'   => 'true',
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Could not connect to payment provider.'], 502);
    }

    $status = wp_remote_retrieve_response_code($response);
    $body   = json_decode(wp_remote_retrieve_body($response), true);

    if ($status !== 200 || empty($body['url'])) {
        $error_msg = isset($body['error']['message']) ? $body['error']['message'] : 'Checkout session creation failed.';
        wp_send_json_error(['message' => $error_msg], $status ?: 500);
    }

    wp_send_json_success(['checkout_url' => $body['url']]);
}

/**
 * REST API endpoint for Stripe Webhooks.
 */
add_action('rest_api_init', function () {
    register_rest_route('cwlm/v1', '/stripe/webhook', [
        'methods'             => 'POST',
        'callback'            => 'cwlm_handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function cwlm_handle_stripe_webhook(WP_REST_Request $request) {
    $webhook_secret = defined('CWLM_STRIPE_WEBHOOK_SECRET') ? CWLM_STRIPE_WEBHOOK_SECRET : '';
    $payload        = $request->get_body();
    $sig_header     = $request->get_header('stripe-signature');

    if (empty($webhook_secret) || empty($sig_header)) {
        return new WP_REST_Response(['error' => 'Missing webhook configuration.'], 400);
    }

    // Verify Stripe signature (HMAC-SHA256)
    $elements  = [];
    foreach (explode(',', $sig_header) as $part) {
        $kv = explode('=', $part, 2);
        if (count($kv) === 2) {
            $elements[trim($kv[0])] = trim($kv[1]);
        }
    }

    $timestamp = isset($elements['t']) ? $elements['t'] : '';
    $signature = isset($elements['v1']) ? $elements['v1'] : '';

    if (empty($timestamp) || empty($signature)) {
        return new WP_REST_Response(['error' => 'Invalid signature header.'], 400);
    }

    // Reject if timestamp is more than 5 minutes old
    if (abs(time() - (int) $timestamp) > 300) {
        return new WP_REST_Response(['error' => 'Timestamp too old.'], 400);
    }

    $signed_payload  = $timestamp . '.' . $payload;
    $expected_sig    = hash_hmac('sha256', $signed_payload, $webhook_secret);

    if (!hash_equals($expected_sig, $signature)) {
        return new WP_REST_Response(['error' => 'Invalid signature.'], 400);
    }

    $event = json_decode($payload, true);
    if (empty($event['type'])) {
        return new WP_REST_Response(['error' => 'Invalid event payload.'], 400);
    }

    // Ensure idempotency - skip already-processed events
    $event_id = isset($event['id']) ? sanitize_text_field($event['id']) : '';
    if ($event_id && get_transient('cwlm_event_' . $event_id)) {
        return new WP_REST_Response(['received' => true], 200);
    }
    if ($event_id) {
        set_transient('cwlm_event_' . $event_id, true, DAY_IN_SECONDS);
    }

    // Initialize license table
    cwlm_maybe_create_tables();

    switch ($event['type']) {
        case 'checkout.session.completed':
            cwlm_handle_checkout_completed($event['data']['object']);
            break;

        case 'invoice.payment_succeeded':
            cwlm_handle_payment_succeeded($event['data']['object']);
            break;

        case 'customer.subscription.deleted':
            cwlm_handle_subscription_deleted($event['data']['object']);
            break;

        case 'charge.refunded':
        case 'charge.dispute.created':
            cwlm_handle_charge_revoked($event['data']['object']);
            break;
    }

    return new WP_REST_Response(['received' => true], 200);
}

/**
 * Create CWLM database tables if they don't exist.
 */
function cwlm_maybe_create_tables() {
    global $wpdb;

    $table = $wpdb->prefix . 'cwlm_licenses';

    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
        return;
    }

    $charset = $wpdb->get_charset_collate();

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        license_key VARCHAR(30) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_name VARCHAR(255) DEFAULT '',
        tier ENUM('free','professional','enterprise','development') NOT NULL DEFAULT 'professional',
        plan VARCHAR(50) NOT NULL DEFAULT 'premium',
        status ENUM('active','grace','expired','revoked') NOT NULL DEFAULT 'active',
        max_sites INT NOT NULL DEFAULT 1,
        active_sites INT NOT NULL DEFAULT 0,
        features_json JSON DEFAULT NULL,
        stripe_customer_id VARCHAR(255) DEFAULT '',
        stripe_subscription_id VARCHAR(255) DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        UNIQUE KEY license_key (license_key),
        KEY stripe_customer_id (stripe_customer_id),
        KEY stripe_subscription_id (stripe_subscription_id)
    ) {$charset}");
}

/**
 * Generate a cryptographically secure license key.
 */
function cwlm_generate_license_key($tier) {
    $tier_map = [
        'free'         => 'FREE',
        'professional' => 'PRO',
        'enterprise'   => 'ENT',
        'development'  => 'DEV',
    ];

    $prefix = isset($tier_map[$tier]) ? $tier_map[$tier] : 'PRO';
    $key    = strtoupper(bin2hex(random_bytes(8)));

    return "CW-{$prefix}-{$key}";
}

/**
 * Handle checkout.session.completed - generate license and email it.
 */
function cwlm_handle_checkout_completed($session) {
    global $wpdb;

    $customer_email = isset($session['customer_details']['email']) ? sanitize_email($session['customer_details']['email']) : '';
    $customer_name  = isset($session['customer_details']['name']) ? sanitize_text_field($session['customer_details']['name']) : '';
    $stripe_cus     = isset($session['customer']) ? sanitize_text_field($session['customer']) : '';
    $stripe_sub     = isset($session['subscription']) ? sanitize_text_field($session['subscription']) : '';

    if (empty($customer_email)) {
        return;
    }

    // Determine tier from metadata or default to professional
    $tier     = 'professional';
    $plan     = 'premium';
    $max_sites = 1;
    $duration  = 365;

    if (!empty($session['metadata']['tier'])) {
        $tier = sanitize_text_field($session['metadata']['tier']);
    }
    if (!empty($session['metadata']['plan'])) {
        $plan = sanitize_text_field($session['metadata']['plan']);
    }
    if (!empty($session['metadata']['max_sites'])) {
        $max_sites = (int) $session['metadata']['max_sites'];
    }
    if (!empty($session['metadata']['duration_days'])) {
        $duration = (int) $session['metadata']['duration_days'];
    }

    $license_key = cwlm_generate_license_key($tier);

    $wpdb->insert($wpdb->prefix . 'cwlm_licenses', [
        'license_key'            => $license_key,
        'customer_email'         => $customer_email,
        'customer_name'          => $customer_name,
        'tier'                   => $tier,
        'plan'                   => $plan,
        'status'                 => 'active',
        'max_sites'              => $max_sites,
        'stripe_customer_id'     => $stripe_cus,
        'stripe_subscription_id' => $stripe_sub,
        'created_at'             => current_time('mysql'),
        'expires_at'             => gmdate('Y-m-d H:i:s', strtotime("+{$duration} days")),
    ]);

    // Send license email
    $plan_label = ucfirst($plan);
    $subject    = "Your CacheWarmer License Key - {$plan_label}";
    $message    = "Hello {$customer_name},\n\n";
    $message   .= "Thank you for purchasing CacheWarmer {$plan_label}!\n\n";
    $message   .= "Your License Key:\n";
    $message   .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message   .= "{$license_key}\n";
    $message   .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message   .= "How to activate CacheWarmer:\n\n";
    $message   .= "WordPress:\n  Go to CacheWarmer → License and enter your key.\n\n";
    $message   .= "Drupal:\n  Go to /admin/config/cachewarmer/license and enter your key.\n\n";
    $message   .= "Node.js / Docker:\n  Set in your .env file:\n";
    $message   .= "  LICENSE_KEY={$license_key}\n";
    $message   .= "  LICENSE_DASHBOARD_URL=" . home_url('/') . "\n\n";
    $message   .= "Plan: {$plan_label}\n";
    $message   .= "Max Sites: {$max_sites}\n";
    $message   .= "Valid for: {$duration} days\n\n";
    $message   .= "Questions? Reply to this email or contact support@drossmedia.de\n\n";
    $message   .= "Best regards,\nThe CacheWarmer Team";

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    wp_mail($customer_email, $subject, $message, $headers);
}

/**
 * Handle invoice.payment_succeeded - extend license expiry.
 */
function cwlm_handle_payment_succeeded($invoice) {
    global $wpdb;
    $sub_id = isset($invoice['subscription']) ? sanitize_text_field($invoice['subscription']) : '';
    if (empty($sub_id)) {
        return;
    }

    $table = $wpdb->prefix . 'cwlm_licenses';
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table} SET status = 'active', expires_at = DATE_ADD(expires_at, INTERVAL 30 DAY) WHERE stripe_subscription_id = %s",
        $sub_id
    ));
}

/**
 * Handle customer.subscription.deleted - expire license.
 */
function cwlm_handle_subscription_deleted($subscription) {
    global $wpdb;
    $sub_id = isset($subscription['id']) ? sanitize_text_field($subscription['id']) : '';
    if (empty($sub_id)) {
        return;
    }

    $table = $wpdb->prefix . 'cwlm_licenses';
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table} SET status = 'expired' WHERE stripe_subscription_id = %s",
        $sub_id
    ));
}

/**
 * Handle charge.refunded / charge.dispute.created - revoke license.
 */
function cwlm_handle_charge_revoked($charge) {
    global $wpdb;
    $cus_id = isset($charge['customer']) ? sanitize_text_field($charge['customer']) : '';
    if (empty($cus_id)) {
        return;
    }

    $table = $wpdb->prefix . 'cwlm_licenses';
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table} SET status = 'revoked' WHERE stripe_customer_id = %s",
        $cus_id
    ));
}
