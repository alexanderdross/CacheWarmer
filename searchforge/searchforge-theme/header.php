<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/svg+xml" href="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/logo-mark.svg">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content">
	<?php esc_html_e( 'Skip to content', 'searchforge-theme' ); ?>
</a>

<header class="sf-header" role="banner">
	<div class="sf-container sf-header__inner">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-header__logo" aria-label="<?php esc_attr_e( 'SearchForge Home', 'searchforge-theme' ); ?>">
			<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/logo-mark.svg" alt="" width="32" height="32">
			<span class="sf-header__logo-text"><span class="sf-header__logo-search">Search</span>Forge</span>
		</a>

		<nav class="sf-header__nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'searchforge-theme' ); ?>">
			<?php
			wp_nav_menu( [
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'sf-nav-list',
				'depth'          => 2,
				'fallback_cb'    => 'sf_default_nav',
			] );
			?>
		</nav>

		<div class="sf-header__actions">
			<a href="/pricing/" class="sf-btn sf-btn--primary sf-btn--sm">Get Pro</a>
		</div>

		<button class="sf-header__toggle" aria-expanded="false" aria-controls="sf-mobile-menu" aria-label="<?php esc_attr_e( 'Toggle navigation', 'searchforge-theme' ); ?>">
			<span class="sf-hamburger"></span>
		</button>
	</div>

	<div id="sf-mobile-menu" class="sf-mobile-menu" hidden>
		<?php
		wp_nav_menu( [
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'sf-mobile-nav-list',
			'depth'          => 1,
		] );
		?>
		<a href="/pricing/" class="sf-btn sf-btn--primary sf-btn--block">Get Pro</a>
	</div>
</header>

<main id="main-content" role="main">
<?php

/**
 * Default navigation fallback.
 */
function sf_default_nav(): void {
	echo '<ul class="sf-nav-list">';
	echo '<li><a href="#features">Features</a></li>';
	echo '<li><a href="/pricing/">Pricing</a></li>';
	echo '<li><a href="/docs/">Docs</a></li>';
	echo '<li><a href="/changelog/">Changelog</a></li>';
	echo '<li><a href="/enterprise/">Enterprise</a></li>';
	echo '</ul>';
}
