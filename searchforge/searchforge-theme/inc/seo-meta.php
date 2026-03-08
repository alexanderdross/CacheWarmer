<?php
/**
 * SEO meta tags, Open Graph, and Twitter Cards for every page.
 *
 * @package SearchForge_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output meta description, Open Graph, and Twitter Card tags.
 */
function sf_theme_output_seo_meta(): void {
	$site_name   = 'SearchForge';
	$site_url    = 'https://searchforge.drossmedia.de';
	$default_img = $site_url . '/wp-content/themes/searchforge-theme/assets/images/og-default.png';
	$twitter     = '@drossmedia';
	$locale      = 'en_US';

	// Per-page meta data.
	$meta = sf_theme_get_page_meta();

	$title       = $meta['title'];
	$description = $meta['description'];
	$og_type     = $meta['og_type'] ?? 'website';
	$image       = $meta['image'] ?? $default_img;
	$url         = $meta['url'] ?? sf_theme_current_url();

	// Meta description.
	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";

	// Canonical.
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";

	// Open Graph.
	echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	echo '<meta property="og:image:width" content="1200">' . "\n";
	echo '<meta property="og:image:height" content="630">' . "\n";
	echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '">' . "\n";

	// Twitter Card.
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:site" content="' . esc_attr( $twitter ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";

	// Additional meta.
	echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">' . "\n";
	echo '<meta name="author" content="Dross:Media GmbH">' . "\n";
}
add_action( 'wp_head', 'sf_theme_output_seo_meta', 1 );

/**
 * Get the current canonical URL.
 */
function sf_theme_current_url(): string {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_page() ) {
		return get_permalink();
	}
	return home_url( $_SERVER['REQUEST_URI'] ?? '/' );
}

/**
 * Return per-page SEO title and description.
 *
 * @return array{title: string, description: string, og_type?: string, image?: string, url?: string}
 */
function sf_theme_get_page_meta(): array {
	$site_url = 'https://searchforge.drossmedia.de';

	// Front page.
	if ( is_front_page() ) {
		return [
			'title'       => 'SearchForge — Turn SEO Data into LLM-Ready Intelligence',
			'description' => 'SearchForge is a WordPress plugin that unifies Google Search Console, Bing, GA4, Trends, and Keyword Planner data into AI-ready markdown briefs. Free tier available.',
			'og_type'     => 'website',
		];
	}

	// Detect page template.
	$template = get_page_template_slug();
	$slug     = '';
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
	}

	// Match by template file or slug.
	switch ( true ) {
		case str_contains( $template, 'page-features' ) || $slug === 'features':
			return [
				'title'       => 'Features — SearchForge for WordPress',
				'description' => 'Explore SearchForge features: 8 data sources, SearchForge Score, AI Visibility Monitor, competitor intelligence, keyword clustering, content briefs, and more.',
			];

		case str_contains( $template, 'page-pricing' ) || $slug === 'pricing':
			return [
				'title'       => 'Pricing — SearchForge for WordPress',
				'description' => 'SearchForge pricing plans: Free, Pro ($99/yr), Agency ($249/yr), and Enterprise. Compare features and choose the plan that fits your SEO workflow.',
			];

		case str_contains( $template, 'page-enterprise' ) || $slug === 'enterprise':
			return [
				'title'       => 'Enterprise — SearchForge for WordPress',
				'description' => 'SearchForge Enterprise: multi-site management, white-label reporting, priority support, custom integrations, and unlimited everything for agencies and publishers.',
			];

		case str_contains( $template, 'page-bundle' ) || $slug === 'bundle':
			return [
				'title'       => 'SearchForge + CacheWarmer Bundle — Save 25%',
				'description' => 'Get SearchForge and CacheWarmer together at 25% off. Detect SEO issues, generate AI briefs, and automatically warm caches across CDN, social media, and search engines.',
			];

		case str_contains( $template, 'page-changelog' ) || $slug === 'changelog':
			return [
				'title'       => 'Changelog — SearchForge for WordPress',
				'description' => 'SearchForge release notes and version history. See what is new in every release, from new features to bug fixes and improvements.',
			];

		case str_contains( $template, 'page-docs-getting-started' ) || $slug === 'getting-started':
			return [
				'title'       => 'Getting Started — SearchForge Documentation',
				'description' => 'Learn how to install SearchForge, activate your license, connect Google Search Console, run your first data sync, and export your first AI content brief.',
			];

		case str_contains( $template, 'page-docs-data-sources' ) || $slug === 'data-sources':
			return [
				'title'       => 'Data Sources — SearchForge Documentation',
				'description' => 'Configure SearchForge data sources: Google Search Console, Bing Webmaster Tools, Google Analytics 4, Keyword Planner, Google Trends, and Business Profile.',
			];

		case str_contains( $template, 'page-docs-features' ) || $slug === 'features-docs':
			return [
				'title'       => 'Features Guide — SearchForge Documentation',
				'description' => 'In-depth guide to SearchForge features: Score, AI Visibility Monitor, competitor intelligence, content briefs, keyword clustering, and cannibalization detection.',
			];

		case str_contains( $template, 'page-docs-export-output' ) || $slug === 'export-output':
			return [
				'title'       => 'Export & Output — SearchForge Documentation',
				'description' => 'Learn about SearchForge export formats: markdown briefs, combined master briefs, llms.txt generation, ZIP bulk export, and scheduled exports.',
			];

		case str_contains( $template, 'page-docs-developer' ) || $slug === 'developer':
			return [
				'title'       => 'Developer Guide — SearchForge Documentation',
				'description' => 'SearchForge developer documentation: REST API reference, WP-CLI commands, actions and filters, API key authentication, and webhook events.',
			];

		case str_contains( $template, 'page-docs-integrations' ) || $slug === 'integrations':
			return [
				'title'       => 'Integrations — SearchForge Documentation',
				'description' => 'SearchForge integrations: Yoast SEO, Rank Math, AIOSEO, CacheWarmer, GitHub, GitLab, Notion, and Google Sheets. Connect your entire SEO workflow.',
			];

		case str_contains( $template, 'page-docs' ) || $slug === 'docs':
			return [
				'title'       => 'Documentation — SearchForge for WordPress',
				'description' => 'SearchForge documentation hub. Getting started, data sources, features, export formats, developer API, and integrations — everything you need to know.',
			];

		default:
			if ( is_404() ) {
				return [
					'title'       => 'Page Not Found — SearchForge',
					'description' => 'The page you are looking for does not exist. Return to the SearchForge homepage.',
				];
			}

			// Generic page fallback.
			$title = get_the_title();
			return [
				'title'       => $title . ' — SearchForge',
				'description' => wp_strip_all_tags( get_the_excerpt() ) ?: 'SearchForge — the WordPress plugin that turns SEO data into LLM-ready intelligence.',
			];
	}
}
