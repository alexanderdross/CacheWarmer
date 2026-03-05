<?php
defined( 'ABSPATH' ) || exit;

$page_path = sanitize_text_field( $_GET['path'] ?? '' );
if ( empty( $page_path ) ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'No page path specified.', 'searchforge' ) . '</p></div></div>';
	return;
}

$page_data   = SearchForge\Admin\PageDetail::get_page_data( $page_path );
if ( ! $page_data ) {
	echo '<div class="wrap"><div class="notice notice-warning"><p>' . esc_html__( 'No data found for this page. Run a GSC sync first.', 'searchforge' ) . '</p></div></div>';
	return;
}

$keywords     = SearchForge\Admin\PageDetail::get_page_keywords( $page_path );
$devices      = SearchForge\Admin\PageDetail::get_device_breakdown( $page_path );
$daily_trend  = SearchForge\Admin\PageDetail::get_daily_trend( $page_path, 30 );
$bing_data    = SearchForge\Admin\PageDetail::get_bing_data( $page_path );
$ga4_data     = SearchForge\Admin\PageDetail::get_ga4_data( $page_path );
$pos_dist     = SearchForge\Admin\PageDetail::get_position_distribution( $page_path );
$is_pro       = SearchForge\Admin\Settings::is_pro();
$score        = SearchForge\Scoring\Score::calculate_page_score( $page_path );
$trend        = $is_pro ? SearchForge\Trends\Engine::get_page_trend( $page_path ) : null;
$yoy          = $is_pro ? SearchForge\Trends\Engine::get_yoy_comparison( $page_path ) : null;
$cannibal     = $is_pro ? SearchForge\Admin\PageDetail::get_page_cannibalization( $page_path ) : [];
?>

<div class="wrap searchforge-wrap sf-page-detail">
	<h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-pages' ) ); ?>" class="sf-back-link">
			&larr; <?php esc_html_e( 'Pages', 'searchforge' ); ?>
		</a>
		<?php echo esc_html( $page_path ); ?>
		<a href="<?php echo esc_url( home_url( $page_path ) ); ?>" target="_blank" class="sf-external-link">&#8599;</a>
	</h1>

	<p class="sf-page-meta">
		<?php echo esc_html( sprintf(
			__( 'Data from %s', 'searchforge' ),
			wp_date( get_option( 'date_format' ), strtotime( $page_data['snapshot_date'] ) )
		) ); ?>
	</p>

	<!-- Metric Cards -->
	<div class="sf-cards">
		<div class="sf-card">
			<h3><?php esc_html_e( 'Clicks', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( number_format( $page_data['clicks'] ) ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Impressions', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( number_format( $page_data['impressions'] ) ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'CTR', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( round( $page_data['ctr'] * 100, 1 ) ); ?>%</span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Position', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( round( $page_data['position'], 1 ) ); ?></span>
		</div>
		<div class="sf-card">
			<h3><?php esc_html_e( 'Keywords', 'searchforge' ); ?></h3>
			<span class="sf-card-value"><?php echo esc_html( count( $keywords ) ); ?></span>
		</div>
		<?php if ( $score ) : ?>
			<div class="sf-card sf-card-score">
				<h3><?php esc_html_e( 'Score', 'searchforge' ); ?></h3>
				<span class="sf-card-value sf-score-<?php echo $score['total'] >= 70 ? 'good' : ( $score['total'] >= 40 ? 'ok' : 'low' ); ?>">
					<?php echo esc_html( $score['total'] ); ?>/100
				</span>
			</div>
		<?php endif; ?>
	</div>

	<!-- Action Buttons -->
	<div class="sf-detail-actions">
		<button class="button sf-export-btn" data-page="<?php echo esc_attr( $page_path ); ?>">
			<?php esc_html_e( 'Export Brief', 'searchforge' ); ?>
		</button>
		<?php if ( $is_pro ) : ?>
			<button class="button sf-ai-brief-btn" data-page="<?php echo esc_attr( $page_path ); ?>">
				<?php esc_html_e( 'AI Content Brief', 'searchforge' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<!-- Tabs -->
	<nav class="nav-tab-wrapper">
		<a href="#sf-tab-overview" class="nav-tab nav-tab-active" data-tab="sf-tab-overview">
			<?php esc_html_e( 'Overview', 'searchforge' ); ?>
		</a>
		<a href="#sf-tab-keywords" class="nav-tab" data-tab="sf-tab-keywords">
			<?php esc_html_e( 'Keywords', 'searchforge' ); ?>
			<span class="sf-tab-count">(<?php echo esc_html( count( $keywords ) ); ?>)</span>
		</a>
		<a href="#sf-tab-trends" class="nav-tab" data-tab="sf-tab-trends">
			<?php esc_html_e( 'Trends', 'searchforge' ); ?>
		</a>
		<?php if ( $score && $is_pro ) : ?>
			<a href="#sf-tab-score" class="nav-tab" data-tab="sf-tab-score">
				<?php esc_html_e( 'Score', 'searchforge' ); ?>
			</a>
		<?php endif; ?>
	</nav>

	<!-- Tab: Overview -->
	<div id="sf-tab-overview" class="sf-tab-panel sf-tab-active">

		<!-- Trend Chart -->
		<?php if ( ! empty( $daily_trend ) ) : ?>
			<div class="sf-chart-container">
				<h2><?php esc_html_e( '30-Day Trend', 'searchforge' ); ?></h2>
				<canvas id="sf-trend-chart" height="280"></canvas>
			</div>
		<?php endif; ?>

		<!-- Device Breakdown -->
		<?php if ( ! empty( $devices ) ) : ?>
			<div class="sf-section">
				<h2><?php esc_html_e( 'Device Breakdown', 'searchforge' ); ?></h2>
				<div class="sf-cards sf-device-cards">
					<?php foreach ( $devices as $dev ) : ?>
						<div class="sf-card">
							<h3><?php echo esc_html( ucfirst( $dev['device'] ) ); ?></h3>
							<span class="sf-card-value"><?php echo esc_html( number_format( (int) $dev['clicks'] ) ); ?></span>
							<span class="sf-card-sub"><?php echo esc_html( number_format( (int) $dev['impressions'] ) ); ?> impr</span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Cross-Engine Comparison -->
		<?php if ( $bing_data ) : ?>
			<div class="sf-section">
				<h2><?php esc_html_e( 'Google vs Bing', 'searchforge' ); ?></h2>
				<table class="widefat sf-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Engine', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>Google</strong></td>
							<td><?php echo esc_html( number_format( $page_data['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format( $page_data['impressions'] ) ); ?></td>
							<td><?php echo esc_html( round( $page_data['ctr'] * 100, 1 ) ); ?>%</td>
							<td><?php echo esc_html( round( $page_data['position'], 1 ) ); ?></td>
						</tr>
						<tr>
							<td><strong>Bing</strong></td>
							<td><?php echo esc_html( number_format( (int) $bing_data['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format( (int) $bing_data['impressions'] ) ); ?></td>
							<td><?php echo esc_html( round( (float) $bing_data['ctr'] * 100, 1 ) ); ?>%</td>
							<td><?php echo esc_html( round( (float) $bing_data['position'], 1 ) ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<!-- GA4 Behavior -->
		<?php if ( $ga4_data ) : ?>
			<div class="sf-section">
				<h2><?php esc_html_e( 'On-Page Behavior (GA4)', 'searchforge' ); ?></h2>
				<div class="sf-cards">
					<div class="sf-card">
						<h3><?php esc_html_e( 'Sessions', 'searchforge' ); ?></h3>
						<span class="sf-card-value"><?php echo esc_html( number_format( (int) $ga4_data['sessions'] ) ); ?></span>
					</div>
					<div class="sf-card">
						<h3><?php esc_html_e( 'Bounce Rate', 'searchforge' ); ?></h3>
						<span class="sf-card-value"><?php echo esc_html( round( (float) $ga4_data['bounce_rate'], 1 ) ); ?>%</span>
					</div>
					<div class="sf-card">
						<h3><?php esc_html_e( 'Avg Duration', 'searchforge' ); ?></h3>
						<span class="sf-card-value"><?php echo esc_html( gmdate( 'i:s', (int) $ga4_data['avg_session_duration'] ) ); ?></span>
					</div>
					<div class="sf-card">
						<h3><?php esc_html_e( 'Conversions', 'searchforge' ); ?></h3>
						<span class="sf-card-value"><?php echo esc_html( number_format( (int) $ga4_data['conversions'] ) ); ?></span>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Position Distribution Chart -->
		<?php if ( ! empty( $pos_dist ) && array_sum( $pos_dist ) > 0 ) : ?>
			<div class="sf-chart-container">
				<h2><?php esc_html_e( 'Keyword Position Distribution', 'searchforge' ); ?></h2>
				<canvas id="sf-position-chart" height="200"></canvas>
			</div>
		<?php endif; ?>

		<!-- YoY Comparison -->
		<?php if ( $yoy ) : ?>
			<div class="sf-section">
				<h2><?php esc_html_e( 'Year-over-Year Comparison', 'searchforge' ); ?></h2>
				<table class="widefat sf-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Metric', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'This Year', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Last Year', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Change', 'searchforge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Clicks', 'searchforge' ); ?></td>
							<td><?php echo esc_html( number_format( (int) $yoy['current']['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format( (int) $yoy['previous']['clicks'] ) ); ?></td>
							<td class="<?php echo $yoy['changes']['clicks'] >= 0 ? 'sf-change-up' : 'sf-change-down'; ?>">
								<?php echo esc_html( ( $yoy['changes']['clicks'] >= 0 ? '+' : '' ) . $yoy['changes']['clicks'] ); ?>%
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Impressions', 'searchforge' ); ?></td>
							<td><?php echo esc_html( number_format( (int) $yoy['current']['impressions'] ) ); ?></td>
							<td><?php echo esc_html( number_format( (int) $yoy['previous']['impressions'] ) ); ?></td>
							<td class="<?php echo $yoy['changes']['impressions'] >= 0 ? 'sf-change-up' : 'sf-change-down'; ?>">
								<?php echo esc_html( ( $yoy['changes']['impressions'] >= 0 ? '+' : '' ) . $yoy['changes']['impressions'] ); ?>%
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Position', 'searchforge' ); ?></td>
							<td><?php echo esc_html( round( (float) $yoy['current']['position'], 1 ) ); ?></td>
							<td><?php echo esc_html( round( (float) $yoy['previous']['position'], 1 ) ); ?></td>
							<td class="<?php echo $yoy['changes']['position'] >= 0 ? 'sf-change-up' : 'sf-change-down'; ?>">
								<?php echo esc_html( ( $yoy['changes']['position'] >= 0 ? '+' : '' ) . $yoy['changes']['position'] ); ?> pos
							</td>
						</tr>
					</tbody>
				</table>
				<p class="description"><?php echo esc_html( $yoy['period'] ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Tab: Keywords -->
	<div id="sf-tab-keywords" class="sf-tab-panel">
		<?php if ( empty( $keywords ) ) : ?>
			<p><?php esc_html_e( 'No keyword data for this page.', 'searchforge' ); ?></p>
		<?php else : ?>
			<table class="widefat sf-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Keyword', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Status', 'searchforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $keywords as $kw ) :
						$pos = (float) $kw['position'];
						if ( $pos <= 3 ) {
							$status_class = 'sf-pos-top3';
							$status_label = __( 'Top 3', 'searchforge' );
						} elseif ( $pos <= 10 ) {
							$status_class = 'sf-pos-page1';
							$status_label = __( 'Page 1', 'searchforge' );
						} elseif ( $pos <= 20 ) {
							$status_class = 'sf-pos-page2';
							$status_label = __( 'Page 2', 'searchforge' );
						} else {
							$status_class = 'sf-pos-deep';
							$status_label = __( 'Deep', 'searchforge' );
						}
					?>
						<tr>
							<td><?php echo esc_html( $kw['query'] ); ?></td>
							<td><?php echo esc_html( number_format( (int) $kw['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format( (int) $kw['impressions'] ) ); ?></td>
							<td><?php echo esc_html( round( (float) $kw['ctr'] * 100, 1 ) ); ?>%</td>
							<td><?php echo esc_html( round( $pos, 1 ) ); ?></td>
							<td><span class="sf-pos-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<!-- Cannibalization Issues -->
		<?php if ( ! empty( $cannibal ) ) : ?>
			<div class="sf-section" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Keyword Cannibalization', 'searchforge' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Keywords where this page competes with other pages on your site.', 'searchforge' ); ?></p>
				<table class="widefat sf-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Keyword', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Your Pos.', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Your Clicks', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Competing Page', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Their Pos.', 'searchforge' ); ?></th>
							<th><?php esc_html_e( 'Their Clicks', 'searchforge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $cannibal as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item['query'] ); ?></td>
								<td><?php echo esc_html( round( (float) $item['my_position'], 1 ) ); ?></td>
								<td><?php echo esc_html( number_format( (int) $item['my_clicks'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-page-detail&path=' . urlencode( $item['competing_page'] ) ) ); ?>">
										<code><?php echo esc_html( $item['competing_page'] ); ?></code>
									</a>
								</td>
								<td><?php echo esc_html( round( (float) $item['their_position'], 1 ) ); ?></td>
								<td><?php echo esc_html( number_format( (int) $item['their_clicks'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<!-- Tab: Trends -->
	<div id="sf-tab-trends" class="sf-tab-panel">
		<?php if ( ! $is_pro ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Trend analysis requires a Pro license.', 'searchforge' ); ?></p>
			</div>
		<?php elseif ( $trend && ! empty( $trend['snapshots'] ) ) : ?>
			<div class="sf-chart-container">
				<h2><?php esc_html_e( 'Weekly Click Trend', 'searchforge' ); ?></h2>
				<canvas id="sf-weekly-trend-chart" height="280"></canvas>
			</div>

			<?php if ( $trend['decay_detected'] ) : ?>
				<div class="notice notice-warning sf-decay-notice">
					<p>
						<strong><?php esc_html_e( 'Content Decay Detected', 'searchforge' ); ?></strong> &mdash;
						<?php echo esc_html( sprintf(
							__( 'Clicks declined %s%% over the last %d days.', 'searchforge' ),
							$trend['decay_percentage'],
							$trend['decay_period_days']
						) ); ?>
					</p>
				</div>
			<?php endif; ?>

			<table class="widefat sf-table" style="margin-top: 16px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Week', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Change', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
						<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_reverse( $trend['snapshots'] ) as $snap ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'M j', strtotime( $snap['date'] ) ) ); ?></td>
							<td><?php echo esc_html( number_format( $snap['clicks'] ) ); ?></td>
							<td>
								<?php if ( isset( $snap['clicks_change'] ) ) : ?>
									<span class="<?php echo $snap['clicks_change'] >= 0 ? 'sf-change-up' : 'sf-change-down'; ?>">
										<?php echo esc_html( ( $snap['clicks_change'] >= 0 ? '+' : '' ) . $snap['clicks_change'] ); ?>%
									</span>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( number_format( $snap['impressions'] ) ); ?></td>
							<td><?php echo esc_html( $snap['position'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'Not enough historical data yet. Trend data requires at least 2 weeks of snapshots.', 'searchforge' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Tab: Score Breakdown -->
	<?php if ( $score && $is_pro ) : ?>
		<div id="sf-tab-score" class="sf-tab-panel">
			<div class="sf-score-overview">
				<div class="sf-score-big sf-score-<?php echo $score['total'] >= 70 ? 'good' : ( $score['total'] >= 40 ? 'ok' : 'low' ); ?>">
					<?php echo esc_html( $score['total'] ); ?><span class="sf-score-max">/100</span>
				</div>
			</div>

			<div class="sf-score-components">
				<?php foreach ( $score['components'] as $name => $comp ) :
					$bar_class = $comp['score'] >= 70 ? 'sf-bar-good' : ( $comp['score'] >= 40 ? 'sf-bar-ok' : 'sf-bar-low' );
				?>
					<div class="sf-score-component">
						<div class="sf-score-component-header">
							<span class="sf-score-component-name"><?php echo esc_html( ucfirst( $name ) ); ?></span>
							<span class="sf-score-component-value"><?php echo esc_html( $comp['score'] ); ?>/100</span>
						</div>
						<div class="sf-score-bar">
							<div class="sf-score-bar-fill <?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo esc_attr( $comp['score'] ); ?>%"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $score['recommendations'] ) ) : ?>
				<div class="sf-section" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Recommendations', 'searchforge' ); ?></h2>
					<ul class="sf-recommendations">
						<?php foreach ( $score['recommendations'] as $rec ) : ?>
							<li><?php echo esc_html( $rec ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<!-- Export Modal (reuse from admin.js) -->
<div id="sf-export-modal" class="sf-modal" style="display:none;">
	<div class="sf-modal-content">
		<span class="sf-modal-close">&times;</span>
		<h2 id="sf-modal-title"></h2>
		<pre id="sf-modal-body"></pre>
		<button class="button button-primary" id="sf-modal-download">
			<?php esc_html_e( 'Download', 'searchforge' ); ?>
		</button>
	</div>
</div>

<?php
// Pass chart data to JS.
$chart_data = [
	'daily_trend' => $daily_trend,
	'pos_dist'    => $pos_dist,
	'weekly_trend' => ( $trend && ! empty( $trend['snapshots'] ) ) ? $trend['snapshots'] : [],
];
?>
<script>
	var sfChartData = <?php echo wp_json_encode( $chart_data ); ?>;
</script>
