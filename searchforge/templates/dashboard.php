<?php
defined( 'ABSPATH' ) || exit;

$summary    = SearchForge\Admin\Dashboard::get_summary();
$pages      = SearchForge\Admin\Dashboard::get_top_pages( 10 );
$keywords   = SearchForge\Admin\Dashboard::get_top_keywords( 15 );
$settings   = SearchForge\Admin\Settings::get_all();
$connected  = ! empty( $settings['gsc_access_token'] );
$site_score = SearchForge\Scoring\Score::calculate_site_score();
$decaying   = SearchForge\Trends\Engine::get_decaying_pages( 'gsc', 5 );
$is_pro     = SearchForge\Admin\Settings::is_pro();

// 14-day trend for dashboard chart.
$daily_trend = SearchForge\Monitoring\PerformanceTrend::get_daily_trends( 14 );
$cannibal_count = 0;
if ( $is_pro ) {
	$cannibalization = SearchForge\Analysis\Cannibalization::detect( 5 );
	$cannibal_count  = count( $cannibalization );
}

// Recent alerts.
global $wpdb;
$recent_alerts = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}sf_alerts ORDER BY created_at DESC LIMIT 5",
	ARRAY_A
);
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
		<?php if ( $site_score ) : ?>
			<div class="sf-card sf-card-score">
				<h3><?php esc_html_e( 'SearchForge Score', 'searchforge' ); ?></h3>
				<span class="sf-card-value sf-score-<?php echo $site_score['total'] >= 70 ? 'good' : ( $site_score['total'] >= 40 ? 'ok' : 'low' ); ?>">
					<?php echo esc_html( $site_score['total'] ); ?>/100
				</span>
			</div>
		<?php endif; ?>
	</div>

	<!-- 14-Day Trend Chart -->
	<?php if ( ! empty( $daily_trend ) ) : ?>
		<div class="sf-chart-container">
			<h2><?php esc_html_e( '14-Day Performance', 'searchforge' ); ?></h2>
			<canvas id="sf-dashboard-chart" height="200"></canvas>
		</div>
		<script>
			var sfDashboardTrend = <?php echo wp_json_encode( $daily_trend ); ?>;
		</script>
	<?php endif; ?>

	<!-- Recent Alerts -->
	<?php if ( ! empty( $recent_alerts ) ) : ?>
		<div class="sf-alerts-section">
			<h2><?php esc_html_e( 'Recent Alerts', 'searchforge' ); ?></h2>
			<?php foreach ( $recent_alerts as $alert ) : ?>
				<div class="sf-alert sf-alert-<?php echo esc_attr( $alert['severity'] ); ?>">
					<strong>[<?php echo esc_html( strtoupper( $alert['severity'] ) ); ?>]</strong>
					<?php echo esc_html( $alert['title'] ); ?>
					<span class="sf-alert-date"><?php echo esc_html( wp_date( 'M j, H:i', strtotime( $alert['created_at'] ) ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- Content Decay Warning -->
	<?php if ( ! empty( $decaying ) ) : ?>
		<div class="sf-decay-section">
			<h2><?php esc_html_e( 'Content Decay Warning', 'searchforge' ); ?></h2>
			<table class="widefat sf-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Recent Clicks', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Previous Clicks', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Change', 'searchforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $decaying as $page ) : ?>
						<tr>
							<td><code><?php echo esc_html( $page['page_path'] ); ?></code></td>
							<td><?php echo esc_html( number_format( (int) $page['recent_clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format( (int) $page['prev_clicks'] ) ); ?></td>
							<td class="sf-decay-value"><?php echo esc_html( $page['decline_pct'] ); ?>%</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<!-- Cannibalization Warning -->
	<?php if ( $is_pro && $cannibal_count > 0 ) : ?>
		<div class="sf-cannibal-summary">
			<h2>
				<?php echo esc_html( sprintf(
					__( 'Keyword Cannibalization (%d issues)', 'searchforge' ),
					$cannibal_count
				) ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Multiple pages compete for the same keywords, splitting ranking signals.', 'searchforge' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-analysis&tab=cannibalization' ) ); ?>">
					<?php esc_html_e( 'View all', 'searchforge' ); ?> &rarr;
				</a>
			</p>
			<?php
			$top_cannibal = array_filter( $cannibalization, fn( $c ) => $c['severity'] === 'high' );
			if ( empty( $top_cannibal ) ) {
				$top_cannibal = array_slice( $cannibalization, 0, 3 );
			} else {
				$top_cannibal = array_slice( $top_cannibal, 0, 3 );
			}
			?>
			<?php foreach ( $top_cannibal as $item ) : ?>
				<div class="sf-alert sf-alert-<?php echo $item['severity'] === 'high' ? 'high' : 'medium'; ?>">
					<strong>&ldquo;<?php echo esc_html( $item['query'] ); ?>&rdquo;</strong>
					<?php echo esc_html( sprintf(
						__( '%d pages competing | %s impressions', 'searchforge' ),
						$item['page_count'],
						number_format( $item['total_impressions'] )
					) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

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
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-page-detail&path=' . urlencode( $page['page_path'] ) ) ); ?>">
								<code><?php echo esc_html( $page['page_path'] ); ?></code>
							</a>
						</td>
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
