<?php
/**
 * 404 error page.
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-section--centered" style="min-height: 60vh; display: flex; align-items: center;">
	<div class="sf-container" style="text-align: center;">
		<?php get_template_part( 'template-parts/breadcrumb' ); ?>
		<h1 style="font-size: 4rem; margin-bottom: 0.5rem;">404</h1>
		<p class="sf-text--large">This page could not be found.</p>
		<p class="sf-text--muted">The page you're looking for doesn't exist or has been moved.</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-btn sf-btn--primary" style="margin-top: 2rem;">Back to Home</a>
	</div>
</section>

<?php
get_footer();
