<?php

namespace SearchForge\Notifications;

use SearchForge\Admin\Dashboard;
use SearchForge\Admin\Settings;
use SearchForge\Monitoring\PerformanceTrend;
use SearchForge\Scoring\Score;
use SearchForge\Trends\Engine;

defined( 'ABSPATH' ) || exit;

class WeeklyDigest {

	/**
	 * Send the weekly digest email.
	 */
	public static function send(): bool {
		if ( ! Settings::is_pro() ) {
			return false;
		}

		$enabled = Settings::get( 'weekly_digest_enabled' );
		if ( ! $enabled ) {
			return false;
		}

		$email = Settings::get( 'alert_email' );
		if ( empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		if ( empty( $email ) ) {
			return false;
		}

		$summary    = Dashboard::get_summary();
		$comparison = PerformanceTrend::get_period_comparison( 7 );
		$site_score = Score::calculate_site_score();
		$decaying   = Engine::get_decaying_pages( 'gsc', 5 );
		$top_pages  = Dashboard::get_top_pages( 10 );

		// Alert count from last 7 days.
		global $wpdb;
		$alert_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}sf_alerts
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
		);

		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[SearchForge] Weekly SEO Digest — %s', 'searchforge' ),
			$site_name
		);

		$body = self::build_email_body( [
			'site_name'   => $site_name,
			'site_url'    => $site_url,
			'summary'     => $summary,
			'comparison'  => $comparison,
			'site_score'  => $site_score,
			'decaying'    => $decaying,
			'top_pages'   => $top_pages,
			'alert_count' => $alert_count,
		] );

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		return wp_mail( $email, $subject, $body, $headers );
	}

	/**
	 * Build the HTML email body.
	 */
	private static function build_email_body( array $data ): string {
		$summary    = $data['summary'];
		$comparison = $data['comparison'];
		$site_score = $data['site_score'];

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1d2327; margin: 0; padding: 0; background: #f0f0f1; }
				.container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 6px; overflow: hidden; }
				.header { background: #2271b1; color: #fff; padding: 24px 30px; }
				.header h1 { margin: 0; font-size: 22px; font-weight: 600; }
				.header p { margin: 8px 0 0; opacity: 0.85; font-size: 14px; }
				.content { padding: 30px; }
				.metrics { display: flex; gap: 12px; flex-wrap: wrap; margin: 16px 0 24px; }
				.metric { flex: 1; min-width: 120px; background: #f6f7f7; border-radius: 4px; padding: 12px; text-align: center; }
				.metric-value { font-size: 24px; font-weight: 700; color: #1d2327; }
				.metric-label { font-size: 11px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px; }
				.metric-change { font-size: 12px; margin-top: 4px; }
				.change-up { color: #00a32a; }
				.change-down { color: #d63638; }
				h2 { font-size: 16px; border-bottom: 1px solid #c3c4c7; padding-bottom: 8px; margin: 24px 0 12px; }
				table { width: 100%; border-collapse: collapse; font-size: 13px; }
				th { text-align: left; padding: 6px 8px; background: #f6f7f7; font-weight: 600; }
				td { padding: 6px 8px; border-bottom: 1px solid #f0f0f1; }
				.score-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-weight: 700; font-size: 16px; }
				.score-good { background: #d4edda; color: #155724; }
				.score-ok { background: #fef8ee; color: #856404; }
				.score-low { background: #fef1f1; color: #d63638; }
				.alert-count { color: #d63638; font-weight: 600; }
				.footer { padding: 20px 30px; background: #f6f7f7; font-size: 12px; color: #646970; text-align: center; }
				.btn { display: inline-block; padding: 8px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>SearchForge Weekly Digest</h1>
					<p><?php echo esc_html( $data['site_name'] ); ?> &mdash; <?php echo esc_html( wp_date( 'M j, Y' ) ); ?></p>
				</div>

				<div class="content">
					<?php if ( $site_score ) : ?>
						<p style="text-align:center;margin:0 0 16px;">
							<span class="score-badge <?php echo $site_score['total'] >= 70 ? 'score-good' : ( $site_score['total'] >= 40 ? 'score-ok' : 'score-low' ); ?>">
								SearchForge Score: <?php echo esc_html( $site_score['total'] ); ?>/100
							</span>
						</p>
					<?php endif; ?>

					<div class="metrics">
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( number_format( $summary['total_clicks'] ) ); ?></div>
							<div class="metric-label">Clicks</div>
							<?php if ( $comparison && isset( $comparison['changes']['clicks'] ) && $comparison['changes']['clicks'] !== null ) : ?>
								<div class="metric-change <?php echo $comparison['changes']['clicks'] >= 0 ? 'change-up' : 'change-down'; ?>">
									<?php echo esc_html( ( $comparison['changes']['clicks'] >= 0 ? '+' : '' ) . round( $comparison['changes']['clicks'], 1 ) ); ?>%
								</div>
							<?php endif; ?>
						</div>
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( number_format( $summary['total_impressions'] ) ); ?></div>
							<div class="metric-label">Impressions</div>
							<?php if ( $comparison && isset( $comparison['changes']['impressions'] ) && $comparison['changes']['impressions'] !== null ) : ?>
								<div class="metric-change <?php echo $comparison['changes']['impressions'] >= 0 ? 'change-up' : 'change-down'; ?>">
									<?php echo esc_html( ( $comparison['changes']['impressions'] >= 0 ? '+' : '' ) . round( $comparison['changes']['impressions'], 1 ) ); ?>%
								</div>
							<?php endif; ?>
						</div>
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( $summary['avg_ctr'] ); ?>%</div>
							<div class="metric-label">CTR</div>
						</div>
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( $summary['avg_position'] ); ?></div>
							<div class="metric-label">Avg Position</div>
						</div>
					</div>

					<?php if ( $data['alert_count'] > 0 ) : ?>
						<p>
							<span class="alert-count"><?php echo esc_html( $data['alert_count'] ); ?></span>
							<?php esc_html_e( 'new alert(s) this week.', 'searchforge' ); ?>
						</p>
					<?php endif; ?>

					<!-- Top Pages -->
					<?php if ( ! empty( $data['top_pages'] ) ) : ?>
						<h2><?php esc_html_e( 'Top Pages', 'searchforge' ); ?></h2>
						<table>
							<thead>
								<tr>
									<th>Page</th>
									<th>Clicks</th>
									<th>Pos.</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array_slice( $data['top_pages'], 0, 10 ) as $page ) : ?>
									<tr>
										<td><?php echo esc_html( $page['page_path'] ); ?></td>
										<td><?php echo esc_html( number_format( (int) $page['clicks'] ) ); ?></td>
										<td><?php echo esc_html( round( (float) $page['position'], 1 ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<!-- Content Decay -->
					<?php if ( ! empty( $data['decaying'] ) ) : ?>
						<h2><?php esc_html_e( 'Content Decay Warning', 'searchforge' ); ?></h2>
						<table>
							<thead>
								<tr>
									<th>Page</th>
									<th>Change</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $data['decaying'] as $page ) : ?>
									<tr>
										<td><?php echo esc_html( $page['page_path'] ); ?></td>
										<td class="change-down"><?php echo esc_html( $page['decline_pct'] ); ?>%</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<p style="text-align:center;margin:24px 0 0;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge' ) ); ?>" class="btn">
							<?php esc_html_e( 'View Full Dashboard', 'searchforge' ); ?>
						</a>
					</p>
				</div>

				<div class="footer">
					<?php echo esc_html( sprintf(
						__( 'SearchForge v%s — %s', 'searchforge' ),
						SEARCHFORGE_VERSION,
						$data['site_url']
					) ); ?>
					<br>
					<?php esc_html_e( 'You can disable this digest in SearchForge > Settings > Alerts.', 'searchforge' ); ?>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
