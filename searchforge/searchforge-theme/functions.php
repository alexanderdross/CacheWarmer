<?php
/**
 * SearchForge Theme — functions and definitions.
 *
 * @package SearchForge_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'SF_THEME_VERSION', '1.0.0' );
define( 'SF_THEME_DIR', get_template_directory() );
define( 'SF_THEME_URI', get_template_directory_uri() );

/**
 * Theme setup.
 */
function sf_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', [
		'height'      => 40,
		'width'       => 180,
		'flex-height' => true,
		'flex-width'  => true,
	] );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script' ] );

	register_nav_menus( [
		'primary' => __( 'Primary Navigation', 'searchforge-theme' ),
		'footer'  => __( 'Footer Navigation', 'searchforge-theme' ),
	] );
}
add_action( 'after_setup_theme', 'sf_theme_setup' );

/**
 * Enqueue styles and scripts.
 */
function sf_theme_enqueue_assets(): void {
	// Fonts.
	wp_enqueue_style(
		'sf-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700&family=JetBrains+Mono:wght@400&display=swap',
		[],
		null
	);

	// Stylesheets.
	$css_files = [ 'variables', 'base', 'components', 'sections', 'responsive' ];
	foreach ( $css_files as $file ) {
		wp_enqueue_style(
			"sf-{$file}",
			SF_THEME_URI . "/assets/css/{$file}.css",
			$file === 'variables' ? [ 'sf-fonts' ] : [ "sf-" . $css_files[ array_search( $file, $css_files ) - 1 ] ],
			SF_THEME_VERSION
		);
	}

	// Scripts.
	$js_files = [ 'navigation', 'faq', 'pricing', 'animations' ];
	foreach ( $js_files as $file ) {
		wp_enqueue_script(
			"sf-{$file}",
			SF_THEME_URI . "/assets/js/{$file}.js",
			[],
			SF_THEME_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sf_theme_enqueue_assets' );

/**
 * Remove unnecessary WordPress head output.
 */
function sf_theme_cleanup_head(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'after_setup_theme', 'sf_theme_cleanup_head' );

/**
 * Redirect legal/contact pages to dross.net.
 */
function sf_theme_legal_redirects(): void {
	$redirects = [
		'/imprint/'  => 'https://dross.net/imprint/',
		'/privacy/'  => 'https://dross.net/privacy-policy/',
		'/contact/'  => 'https://dross.net/contact/?topic=searchforge',
	];

	$path = trailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );

	if ( isset( $redirects[ $path ] ) ) {
		wp_redirect( $redirects[ $path ], 301 );
		exit;
	}
}
add_action( 'template_redirect', 'sf_theme_legal_redirects' );

/**
 * Build breadcrumb trail for the current page.
 *
 * @return array<int, array{label: string, url?: string, external?: bool}>
 */
function sf_get_breadcrumbs(): array {
	if ( is_front_page() ) {
		return [];
	}

	$crumbs = [
		[
			'label'    => 'Dross:Media',
			'url'      => 'https://dross.net/media/',
			'external' => true,
		],
		[
			'label' => 'SearchForge',
			'url'   => home_url( '/' ),
		],
	];

	if ( is_page() ) {
		$post      = get_queried_object();
		$ancestors = array_reverse( get_post_ancestors( $post ) );

		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = [
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			];
		}

		$crumbs[] = [ 'label' => get_the_title( $post ) ];
	} elseif ( is_404() ) {
		$crumbs[] = [ 'label' => '404' ];
	} else {
		$crumbs[] = [ 'label' => get_the_title() ];
	}

	return $crumbs;
}

// Load includes.
require_once SF_THEME_DIR . '/inc/schema.php';
require_once SF_THEME_DIR . '/inc/security.php';
require_once SF_THEME_DIR . '/inc/performance.php';
