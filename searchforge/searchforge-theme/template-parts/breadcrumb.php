<?php
/**
 * Breadcrumb navigation.
 *
 * @package SearchForge_Theme
 */

defined( 'ABSPATH' ) || exit;

$crumbs = sf_get_breadcrumbs();

if ( empty( $crumbs ) ) {
	return;
}
?>
<nav class="sf-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'searchforge-theme' ); ?>">
	<ol class="sf-breadcrumb__list">
		<?php foreach ( $crumbs as $i => $crumb ) : ?>
			<li class="sf-breadcrumb__item">
				<?php if ( ! empty( $crumb['url'] ) ) : ?>
					<a href="<?php echo esc_url( $crumb['url'] ); ?>"<?php echo ! empty( $crumb['external'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $crumb['label'] ); ?></a>
				<?php else : ?>
					<span aria-current="page"><?php echo esc_html( $crumb['label'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
