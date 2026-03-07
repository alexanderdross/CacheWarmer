<?php
/**
 * JSON-LD structured data for SEO.
 *
 * @package SearchForge_Theme
 */

defined( 'ABSPATH' ) || exit;

function sf_theme_output_schema(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$schema = [
		[
			'@context' => 'https://schema.org',
			'@type'    => 'SoftwareApplication',
			'name'     => 'SearchForge',
			'url'      => 'https://searchforge.drossmedia.de/',
			'applicationCategory' => 'WebApplication',
			'operatingSystem'     => 'WordPress',
			'description'         => 'WordPress plugin that turns SEO data from Google Search Console, Bing, GA4 and Trends into LLM-ready markdown briefs.',
			'offers' => [
				[
					'@type'         => 'Offer',
					'price'         => '0',
					'priceCurrency' => 'EUR',
					'name'          => 'Free',
				],
				[
					'@type'         => 'Offer',
					'price'         => '99',
					'priceCurrency' => 'EUR',
					'name'          => 'Pro',
					'billingDuration' => 'P1Y',
				],
				[
					'@type'         => 'Offer',
					'price'         => '249',
					'priceCurrency' => 'EUR',
					'name'          => 'Agency',
					'billingDuration' => 'P1Y',
				],
			],
			'author' => [
				'@type' => 'Organization',
				'name'  => 'Dross:Media GmbH',
				'url'   => 'https://drossmedia.de',
			],
		],
		[
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => 'Dross:Media GmbH',
			'url'      => 'https://drossmedia.de',
			'logo'     => 'https://searchforge.drossmedia.de/wp-content/themes/searchforge-theme/assets/images/logo.svg',
		],
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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
