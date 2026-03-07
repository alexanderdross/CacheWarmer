<?php
/**
 * Generic page template.
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	</div>
</section>

<section class="sf-section sf-section--page">
	<div class="sf-container sf-container--narrow">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<h1 class="sf-page-title"><?php the_title(); ?></h1>
				<div class="sf-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	</div>
</section>

<?php
get_footer();
