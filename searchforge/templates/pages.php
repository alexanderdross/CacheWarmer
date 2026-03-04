<?php
defined( 'ABSPATH' ) || exit;

$pages    = SearchForge\Admin\Dashboard::get_top_pages( 100 );
$settings = SearchForge\Admin\Settings::get_all();
$is_pro   = SearchForge\Admin\Settings::is_pro();
$limit    = SearchForge\Admin\Settings::get_page_limit();
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge — Pages', 'searchforge' ); ?></h1>

	<?php if ( $limit > 0 && count( $pages ) >= $limit ) : ?>
		<div class="notice notice-info">
			<p>
				<?php echo esc_html( sprintf(
					__( 'Free tier is limited to %d pages. Upgrade to Pro for unlimited pages.', 'searchforge' ),
					$limit
				) ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $pages ) ) : ?>
		<p><?php esc_html_e( 'No page data available. Run a GSC sync first.', 'searchforge' ); ?></p>
	<?php else : ?>
		<table class="widefat sf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'searchforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pages as $page ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( home_url( $page['page_path'] ) ); ?>" target="_blank">
								<?php echo esc_html( $page['page_path'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( number_format( (int) $page['clicks'] ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) $page['impressions'] ) ); ?></td>
						<td><?php echo esc_html( round( (float) $page['ctr'] * 100, 1 ) ); ?>%</td>
						<td><?php echo esc_html( round( (float) $page['position'], 1 ) ); ?></td>
						<td>
							<?php if ( $is_pro ) : ?>
								<button class="button button-small sf-export-btn"
									data-page="<?php echo esc_attr( $page['page_path'] ); ?>">
									<?php esc_html_e( 'Export Brief', 'searchforge' ); ?>
								</button>
								<button class="button button-small sf-ai-brief-btn"
									data-page="<?php echo esc_attr( $page['page_path'] ); ?>">
									<?php esc_html_e( 'AI Brief', 'searchforge' ); ?>
								</button>
							<?php else : ?>
								<span class="sf-pro-badge" title="<?php esc_attr_e( 'Pro feature', 'searchforge' ); ?>">Pro</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
