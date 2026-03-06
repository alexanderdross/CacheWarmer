<?php
defined( 'ABSPATH' ) || exit;

$is_pro     = SearchForge\Admin\Settings::is_pro();
$ssl_info   = SearchForge\Monitoring\SslChecker::check();
$quota      = SearchForge\Monitoring\QuotaTracker::get_summary();
$comparison = SearchForge\Monitoring\PerformanceTrend::get_period_comparison( 7 );
$daily      = SearchForge\Monitoring\PerformanceTrend::get_daily_trends( 30 );
$broken     = $is_pro ? SearchForge\Monitoring\BrokenLinks::get_latest() : [];
$tab        = sanitize_text_field( $_GET['tab'] ?? 'overview' );
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge Monitoring', 'searchforge' ); ?>
		<?php if ( ! $is_pro ) : ?>
			<span class="sf-pro-badge">Pro</span>
		<?php endif; ?>
	</h1>

	<nav class="nav-tab-wrapper" role="tablist">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-monitoring&tab=overview' ) ); ?>"
			class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>"
			role="tab" aria-selected="<?php echo $tab === 'overview' ? 'true' : 'false'; ?>"
			aria-controls="sf-monitoring-overview">
			<?php esc_html_e( 'Overview', 'searchforge' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-monitoring&tab=performance' ) ); ?>"
			class="nav-tab <?php echo $tab === 'performance' ? 'nav-tab-active' : ''; ?>"
			role="tab" aria-selected="<?php echo $tab === 'performance' ? 'true' : 'false'; ?>"
			aria-controls="sf-monitoring-performance">
			<?php esc_html_e( 'Performance', 'searchforge' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-monitoring&tab=audit' ) ); ?>"
			class="nav-tab <?php echo $tab === 'audit' ? 'nav-tab-active' : ''; ?>"
			role="tab" aria-selected="<?php echo $tab === 'audit' ? 'true' : 'false'; ?>"
			aria-controls="sf-monitoring-audit">
			<?php esc_html_e( 'Audit Log', 'searchforge' ); ?>
		</a>
	</nav>

	<?php if ( $tab === 'overview' ) : ?>

		<!-- Period Comparison -->
		<?php if ( $comparison['current'] ) : ?>
			<h2><?php esc_html_e( '7-Day Comparison', 'searchforge' ); ?></h2>
			<div class="sf-cards">
				<div class="sf-card">
					<h3><?php esc_html_e( 'Clicks', 'searchforge' ); ?></h3>
					<span class="sf-card-value"><?php echo esc_html( number_format( (int) ( $comparison['current']['clicks'] ?? 0 ) ) ); ?></span>
					<?php if ( $comparison['changes']['clicks'] !== null ) : ?>
						<span class="sf-change sf-change-<?php echo $comparison['changes']['clicks'] >= 0 ? 'up' : 'down'; ?>">
							<?php echo esc_html( ( $comparison['changes']['clicks'] >= 0 ? '+' : '' ) . $comparison['changes']['clicks'] . '%' ); ?>
						</span>
					<?php endif; ?>
				</div>
				<div class="sf-card">
					<h3><?php esc_html_e( 'Impressions', 'searchforge' ); ?></h3>
					<span class="sf-card-value"><?php echo esc_html( number_format( (int) ( $comparison['current']['impressions'] ?? 0 ) ) ); ?></span>
					<?php if ( $comparison['changes']['impressions'] !== null ) : ?>
						<span class="sf-change sf-change-<?php echo $comparison['changes']['impressions'] >= 0 ? 'up' : 'down'; ?>">
							<?php echo esc_html( ( $comparison['changes']['impressions'] >= 0 ? '+' : '' ) . $comparison['changes']['impressions'] . '%' ); ?>
						</span>
					<?php endif; ?>
				</div>
				<div class="sf-card">
					<h3><?php esc_html_e( 'Avg Position', 'searchforge' ); ?></h3>
					<span class="sf-card-value"><?php echo esc_html( $comparison['current']['avg_position'] ?? '—' ); ?></span>
				</div>
				<div class="sf-card">
					<h3><?php esc_html_e( 'Avg CTR', 'searchforge' ); ?></h3>
					<span class="sf-card-value"><?php echo esc_html( round( (float) ( $comparison['current']['avg_ctr'] ?? 0 ) * 100, 1 ) ); ?>%</span>
				</div>
			</div>
		<?php endif; ?>

		<!-- SSL Certificate -->
		<?php if ( $ssl_info ) : ?>
			<h2><?php esc_html_e( 'SSL Certificate', 'searchforge' ); ?></h2>
			<div class="sf-ssl-card sf-ssl-<?php echo esc_attr( $ssl_info['status'] ); ?>">
				<?php if ( $ssl_info['status'] === 'valid' ) : ?>
					<span class="sf-ssl-icon dashicons dashicons-lock"></span>
					<div>
						<strong><?php esc_html_e( 'Valid', 'searchforge' ); ?></strong>
						<?php echo esc_html( sprintf(
							__( '%d days remaining — expires %s', 'searchforge' ),
							$ssl_info['days_left'],
							$ssl_info['valid_to']
						) ); ?>
						<br>
						<small><?php echo esc_html( sprintf( __( 'Issuer: %s', 'searchforge' ), $ssl_info['issuer'] ) ); ?></small>
					</div>
				<?php elseif ( $ssl_info['status'] === 'warning' || $ssl_info['status'] === 'critical' ) : ?>
					<span class="sf-ssl-icon dashicons dashicons-warning"></span>
					<div>
						<strong><?php echo esc_html( sprintf( __( 'Expires in %d days!', 'searchforge' ), $ssl_info['days_left'] ) ); ?></strong>
						<br><?php echo esc_html( sprintf( __( 'Expiry: %s | Issuer: %s', 'searchforge' ), $ssl_info['valid_to'], $ssl_info['issuer'] ) ); ?>
					</div>
				<?php elseif ( $ssl_info['status'] === 'expired' ) : ?>
					<span class="sf-ssl-icon dashicons dashicons-dismiss"></span>
					<div>
						<strong><?php esc_html_e( 'Certificate Expired!', 'searchforge' ); ?></strong>
						<br><?php echo esc_html( sprintf( __( 'Expired: %s', 'searchforge' ), $ssl_info['valid_to'] ) ); ?>
					</div>
				<?php else : ?>
					<span class="sf-ssl-icon dashicons dashicons-info"></span>
					<div><?php echo esc_html( $ssl_info['message'] ?? __( 'Unable to check SSL.', 'searchforge' ) ); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- API Quota Usage -->
		<h2><?php esc_html_e( 'API Quota Usage (Today)', 'searchforge' ); ?></h2>
		<div class="sf-quota-grid">
			<?php foreach ( $quota as $service => $info ) : ?>
				<div class="sf-quota-item sf-quota-<?php echo esc_attr( $info['status'] ); ?>">
					<div class="sf-quota-header">
						<strong><?php echo esc_html( $info['label'] ); ?></strong>
						<span><?php echo esc_html( number_format( $info['used'] ) . ' / ' . number_format( $info['limit'] ) ); ?></span>
					</div>
					<div class="sf-quota-bar">
						<div class="sf-quota-fill" style="width: <?php echo esc_attr( min( 100, $info['pct'] ) ); ?>%"></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Sitemap Discovery -->
		<h2><?php esc_html_e( 'Sitemap Discovery', 'searchforge' ); ?></h2>
		<p>
			<button type="button" class="button" id="sf-discover-sitemaps">
				<?php esc_html_e( 'Discover Sitemaps', 'searchforge' ); ?>
			</button>
		</p>
		<div id="sf-sitemap-results"></div>

		<!-- Broken Links -->
		<?php if ( $is_pro ) : ?>
			<h2>
				<?php
				if ( ! empty( $broken ) ) {
					echo esc_html( sprintf( __( 'Broken Links (%d found)', 'searchforge' ), count( $broken ) ) );
				} else {
					esc_html_e( 'Broken Links', 'searchforge' );
				}
				?>
			</h2>
			<p>
				<button type="button" class="button" id="sf-scan-broken-links">
					<?php esc_html_e( 'Scan Now', 'searchforge' ); ?>
				</button>
			</p>
			<div id="sf-broken-link-results"></div>
		<?php endif; ?>

		<?php if ( $is_pro && ! empty( $broken ) ) : ?>
			<table class="widefat sf-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Broken URL', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Type', 'searchforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( $broken, 0, 20 ) as $link ) : ?>
						<tr>
							<td><code><?php echo esc_html( $link['page_path'] ); ?></code></td>
							<td class="sf-broken-url"><?php echo esc_html( $link['url'] ); ?></td>
							<td>
								<span class="sf-severity-badge sf-severity-high">
									<?php echo esc_html( $link['status_code'] ?: 'Error' ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $link['type'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	<?php elseif ( $tab === 'performance' ) : ?>

		<h2><?php esc_html_e( '30-Day Performance Trend', 'searchforge' ); ?></h2>

		<?php if ( empty( $daily ) ) : ?>
			<p class="description"><?php esc_html_e( 'No performance data available yet. Run a sync first.', 'searchforge' ); ?></p>
		<?php else : ?>
			<table class="widefat sf-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Avg Position', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $daily as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['snapshot_date'] ); ?></td>
							<td><?php echo esc_html( number_format( (int) $row['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format( (int) $row['impressions'] ) ); ?></td>
							<td><?php echo esc_html( $row['avg_position'] ); ?></td>
							<td><?php echo esc_html( round( (float) $row['avg_ctr'] * 100, 2 ) ); ?>%</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	<?php elseif ( $tab === 'audit' ) : ?>

		<h2><?php esc_html_e( 'Audit Log', 'searchforge' ); ?></h2>

		<?php
		$audit_entries = SearchForge\Monitoring\AuditLog::get_entries( 50 );
		if ( empty( $audit_entries ) ) :
		?>
			<p class="description"><?php esc_html_e( 'No audit log entries yet.', 'searchforge' ); ?></p>
		<?php else : ?>
			<table class="widefat sf-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'User', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Action', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Details', 'searchforge' ); ?></th>
						<th scope="col"><?php esc_html_e( 'IP', 'searchforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $audit_entries as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $entry['created_at'] ) ) ); ?></td>
							<td><?php echo esc_html( $entry['user_login'] ); ?></td>
							<td><code><?php echo esc_html( $entry['action'] ); ?></code></td>
							<td class="sf-audit-details"><?php echo esc_html( mb_substr( $entry['details'] ?? '', 0, 200 ) ); ?></td>
							<td><?php echo esc_html( $entry['ip_address'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	<?php endif; ?>
</div>
