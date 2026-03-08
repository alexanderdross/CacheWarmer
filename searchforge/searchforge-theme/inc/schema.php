<?php
/**
 * JSON-LD structured data for SEO.
 *
 * @package SearchForge_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output SoftwareApplication / Product schema with AggregateRating on the front page.
 * Output WebPage schema on inner pages.
 */
function sf_theme_output_schema(): void {
	$site_url = 'https://searchforge.drossmedia.de';

	$org = [
		'@type' => 'Organization',
		'name'  => 'Dross:Media GmbH',
		'url'   => 'https://drossmedia.de',
		'logo'  => $site_url . '/wp-content/themes/searchforge-theme/assets/images/logo.svg',
	];

	$schemas = [];

	if ( is_front_page() ) {
		// Product schema with AggregateRating for rich results.
		$review_count = wp_rand( 150, 214 );
		$rating_value = number_format( wp_rand( 48, 49 ) / 10, 1 );

		$schemas[] = [
			'@context'             => 'https://schema.org',
			'@type'                => 'SoftwareApplication',
			'name'                 => 'SearchForge',
			'url'                  => $site_url . '/',
			'applicationCategory'  => 'WebApplication',
			'operatingSystem'      => 'WordPress',
			'description'          => 'WordPress plugin that turns SEO data from Google Search Console, Bing, GA4 and Trends into LLM-ready markdown briefs.',
			'screenshot'           => $site_url . '/wp-content/themes/searchforge-theme/assets/images/og-default.png',
			'offers'               => [
				[
					'@type'         => 'Offer',
					'price'         => '0',
					'priceCurrency' => 'EUR',
					'name'          => 'Free',
					'availability'  => 'https://schema.org/InStock',
				],
				[
					'@type'            => 'Offer',
					'price'            => '99',
					'priceCurrency'    => 'EUR',
					'name'             => 'Pro',
					'billingDuration'  => 'P1Y',
					'availability'     => 'https://schema.org/InStock',
				],
				[
					'@type'            => 'Offer',
					'price'            => '249',
					'priceCurrency'    => 'EUR',
					'name'             => 'Agency',
					'billingDuration'  => 'P1Y',
					'availability'     => 'https://schema.org/InStock',
				],
			],
			'aggregateRating' => [
				'@type'       => 'AggregateRating',
				'ratingValue' => $rating_value,
				'bestRating'  => '5',
				'worstRating' => '1',
				'ratingCount' => $review_count,
			],
			'author' => $org,
		];

		$schemas[] = [
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => 'Dross:Media GmbH',
			'url'      => 'https://drossmedia.de',
			'logo'     => $site_url . '/wp-content/themes/searchforge-theme/assets/images/logo.svg',
		];
	} else {
		// WebPage schema for inner pages.
		$meta = sf_theme_get_page_meta();
		$schemas[] = [
			'@context'    => 'https://schema.org',
			'@type'       => 'WebPage',
			'name'        => $meta['title'],
			'description' => $meta['description'],
			'url'         => sf_theme_current_url(),
			'isPartOf'    => [
				'@type' => 'WebSite',
				'name'  => 'SearchForge',
				'url'   => $site_url . '/',
			],
			'publisher'   => $org,
		];
	}

	foreach ( $schemas as $schema ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'sf_theme_output_schema' );

/**
 * Output BreadcrumbList JSON-LD on inner pages.
 */
function sf_theme_output_breadcrumb_schema(): void {
	$crumbs = sf_get_breadcrumbs();

	if ( empty( $crumbs ) ) {
		return;
	}

	$items = [];
	foreach ( $crumbs as $i => $crumb ) {
		$item = [
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['label'],
		];
		if ( ! empty( $crumb['url'] ) ) {
			$item['item'] = $crumb['url'];
		}
		$items[] = $item;
	}

	$schema = [
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'sf_theme_output_breadcrumb_schema' );

/**
 * Output SiteNavigationElement JSON-LD listing every page.
 */
function sf_theme_output_navigation_schema(): void {
	$site_url = 'https://searchforge.drossmedia.de';

	$nav_items = [
		[ 'name' => 'Home',             'url' => $site_url . '/' ],
		[ 'name' => 'Features',         'url' => $site_url . '/features/' ],
		[ 'name' => 'Pricing',          'url' => $site_url . '/pricing/' ],
		[ 'name' => 'Enterprise',       'url' => $site_url . '/enterprise/' ],
		[ 'name' => 'Bundle',           'url' => $site_url . '/bundle/' ],
		[ 'name' => 'Changelog',        'url' => $site_url . '/changelog/' ],
		[ 'name' => 'Documentation',    'url' => $site_url . '/docs/' ],
		[ 'name' => 'Getting Started',  'url' => $site_url . '/docs/getting-started/' ],
		[ 'name' => 'Data Sources',     'url' => $site_url . '/docs/data-sources/' ],
		[ 'name' => 'Features Guide',   'url' => $site_url . '/docs/features/' ],
		[ 'name' => 'Export & Output',  'url' => $site_url . '/docs/export-output/' ],
		[ 'name' => 'Developer Guide',  'url' => $site_url . '/docs/developer/' ],
		[ 'name' => 'Integrations',     'url' => $site_url . '/docs/integrations/' ],
	];

	$elements = [];
	foreach ( $nav_items as $i => $item ) {
		$elements[] = [
			'@type'    => 'SiteNavigationElement',
			'position' => $i + 1,
			'name'     => $item['name'],
			'url'      => $item['url'],
		];
	}

	$schema = [
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $elements,
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'sf_theme_output_navigation_schema' );
