<?php
defined( 'ABSPATH' ) || exit;

$summary  = SearchForge\Admin\Dashboard::get_summary();
$pages    = SearchForge\Admin\Dashboard::get_top_pages( 10 );
$keywords = SearchForge\Admin\Dashboard::get_top_keywords( 15 );
$settings = SearchForge\Admin\Settings::get_all();
$connected = ! empty( $settings['gsc_access_token'] );
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge Dashboard', 'searchforge' ); ?></h1>

	<?php if ( ! $connected ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Google Search Console is not connected.', 'searchforge' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-settings' ) ); ?>">
					<?php esc_html_e( 'Connect now', 'searchforge' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $connected && $summary['total_pages'] === 0 ) : ?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'No data yet. Run your first sync to see results.', 'searchforge' ); ?>
				<button type="button" class="button button-primary" id="sf-sync-btn">
					<?php esc_html_e( 'Sync Now', 'searchforge' ); ?>
				</button>
			</p>
		</div>
	<?php endif; ?>

	<!-- Summary Cards -->
	<div class="sf-cards">
		<div class="sf-card">
			<h3><?php esc_html_e( 'Total Clicks', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( number_format( $summary['total_clicks'] ) ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Total Impressions', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( number_format( $summary['total_impressions'] ) ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Avg CTR', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( $summary['avg_ctr'] ); ?>%</span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Avg Position', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( $summary['avg_position'] ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Pages', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( number_format( $summary['total_pages'] ) ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Keywords', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( number_format( $summary['total_keywords'] ) ); ?></span>
		</div>
	</div>

	<?php if ( $summary['last_sync'] ) : ?>
		<p class="sf-last-sync">
			<?php echo esc_html( sprintf(
				__( 'Last sync: %s (%s)', 'searchforge' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $summary['last_sync'] ) ),
				$summary['sync_status'] ?? 'unknown'
			) ); ?>
			<button type="button" class="button" id="sf-sync-btn">
				<?php esc_html_e( 'Sync Now', 'searchforge' ); ?>
			</button>
		</p>
	<?php endif; ?>

	<!-- Top Pages -->
	<?php if ( ! empty( $pages ) ) : ?>
		<h2><?php esc_html_e( 'Top Pages', 'searchforge' ); ?></h2>
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
						<td><code><?php echo esc_html( $page['page_path'] ); ?></code></td>
						<td><?php echo esc_html( number_format( (int) $page['clicks'] ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) $page['impressions'] ) ); ?></td>
						<td><?php echo esc_html( round( (float) $page['ctr'] * 100, 1 ) ); ?>%</td>
						<td><?php echo esc_html( round( (float) $page['position'], 1 ) ); ?></td>
						<td>
							<button class="button button-small sf-export-btn"
								data-page="<?php echo esc_attr( $page['page_path'] ); ?>">
								<?php esc_html_e( 'Export Brief', 'searchforge' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<!-- Top Keywords -->
	<?php if ( ! empty( $keywords ) ) : ?>
		<h2><?php esc_html_e( 'Top Keywords', 'searchforge' ); ?></h2>
		<table class="widefat sf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Keyword', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $keywords as $kw ) : ?>
					<tr>
						<td><?php echo esc_html( $kw['query'] ); ?></td>
						<td><code><?php echo esc_html( $kw['page_path'] ); ?></code></td>
						<td><?php echo esc_html( number_format( (int) $kw['clicks'] ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) $kw['impressions'] ) ); ?></td>
						<td><?php echo esc_html( round( (float) $kw['ctr'] * 100, 1 ) ); ?>%</td>
						<td><?php echo esc_html( round( (float) $kw['position'], 1 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
