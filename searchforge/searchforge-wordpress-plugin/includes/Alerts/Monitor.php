<?php

namespace SearchForge\Alerts;

use SearchForge\Admin\Settings;
use SearchForge\Trends\Engine;

defined( 'ABSPATH' ) || exit;

class Monitor {

	public function __construct() {
		add_action( 'searchforge_daily_sync', [ $this, 'check_alerts' ], 20 );
		add_action( 'searchforge_weekly_digest', [ $this, 'send_weekly_digest' ] );
	}

	/**
	 * Run all alert checks after a sync.
	 */
	public function check_alerts(): void {
		if ( ! Settings::is_pro() || ! Settings::get( 'alerts_enabled' ) ) {
			return;
		}

		$alerts = [];

		$ranking_drops = $this->check_ranking_drops();
		if ( ! empty( $ranking_drops ) ) {
			$alerts[] = $ranking_drops;
		}

		$traffic_anomalies = $this->check_traffic_anomalies();
		if ( ! empty( $traffic_anomalies ) ) {
			$alerts[] = $traffic_anomalies;
		}

		$new_keywords = $this->check_new_keywords();
		if ( ! empty( $new_keywords ) ) {
			$alerts[] = $new_keywords;
		}

		$decay = $this->check_content_decay();
		if ( ! empty( $decay ) ) {
			$alerts[] = $decay;
		}

		if ( ! empty( $alerts ) ) {
			$this->send_alert_email( $alerts );
			$this->store_alerts( $alerts );
		}
	}

	/**
	 * Check for significant ranking drops.
	 */
	private function check_ranking_drops(): ?array {
		global $wpdb;

		$threshold = (int) Settings::get( 'alert_ranking_drop_threshold', 3 );
		$recent    = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
		$previous  = gmdate( 'Y-m-d', strtotime( '-9 days' ) );

		$drops = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				r.query, r.page_path,
				p.position AS prev_position,
				r.position AS curr_position,
				(r.position - p.position) AS position_drop,
				p.clicks AS prev_clicks
			FROM {$wpdb->prefix}sf_keywords r
			INNER JOIN {$wpdb->prefix}sf_keywords p
				ON r.query = p.query AND r.page_path = p.page_path
				AND p.source = 'gsc' AND p.snapshot_date = %s
			WHERE r.source = 'gsc' AND r.snapshot_date = %s
				AND (r.position - p.position) >= %d
				AND p.clicks >= 3
			ORDER BY position_drop DESC
			LIMIT 10",
			$previous,
			$recent,
			$threshold
		), ARRAY_A );

		if ( empty( $drops ) ) {
			return null;
		}

		return [
			'type'    => 'ranking_drop',
			'title'   => sprintf(
				__( '%d keywords dropped %d+ positions', 'searchforge' ),
				count( $drops ),
				$threshold
			),
			'items'   => $drops,
			'severity' => count( $drops ) > 5 ? 'high' : 'medium',
		];
	}

	/**
	 * Check for traffic anomalies (unusual spikes or drops).
	 */
	private function check_traffic_anomalies(): ?array {
		if ( ! Settings::get( 'alert_traffic_anomaly' ) ) {
			return null;
		}

		global $wpdb;

		// Get last 4 weeks of daily click totals.
		$daily_clicks = $wpdb->get_results(
			"SELECT snapshot_date, SUM(clicks) as total_clicks
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND device = 'all'
				AND snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
			GROUP BY snapshot_date
			ORDER BY snapshot_date ASC",
			ARRAY_A
		);

		if ( count( $daily_clicks ) < 7 ) {
			return null;
		}

		$values = array_column( $daily_clicks, 'total_clicks' );
		$values = array_map( 'intval', $values );

		// Calculate mean and standard deviation.
		$mean   = array_sum( $values ) / count( $values );
		$sq_sum = array_sum( array_map( fn( $v ) => pow( $v - $mean, 2 ), $values ) );
		$stddev = sqrt( $sq_sum / count( $values ) );

		if ( $stddev < 1 ) {
			return null;
		}

		// Check the most recent value.
		$latest = end( $values );
		$zscore = ( $latest - $mean ) / $stddev;

		// Anomaly if z-score > 2 (spike) or < -2 (drop).
		if ( abs( $zscore ) < 2 ) {
			return null;
		}

		$type = $zscore > 0 ? 'spike' : 'drop';
		$pct  = round( ( $latest - $mean ) / max( 1, $mean ) * 100, 1 );

		return [
			'type'     => 'traffic_anomaly',
			'title'    => sprintf(
				__( 'Traffic %s detected: %s%% %s average', 'searchforge' ),
				$type,
				abs( $pct ),
				$type === 'spike' ? 'above' : 'below'
			),
			'items'    => [
				[
					'date'    => end( $daily_clicks )['snapshot_date'],
					'clicks'  => $latest,
					'average' => round( $mean ),
					'change'  => "{$pct}%",
				],
			],
			'severity' => abs( $zscore ) > 3 ? 'high' : 'medium',
		];
	}

	/**
	 * Check for new keyword acquisitions.
	 */
	private function check_new_keywords(): ?array {
		$new_pages = Engine::get_new_keyword_pages( 'gsc', 7 );

		if ( empty( $new_pages ) ) {
			return null;
		}

		$total_new = array_sum( array_column( $new_pages, 'new_keywords' ) );

		return [
			'type'     => 'new_keywords',
			'title'    => sprintf(
				__( '%d new keywords detected across %d pages', 'searchforge' ),
				$total_new,
				count( $new_pages )
			),
			'items'    => array_slice( $new_pages, 0, 5 ),
			'severity' => 'info',
		];
	}

	/**
	 * Check for content decay.
	 */
	private function check_content_decay(): ?array {
		$decaying = Engine::get_decaying_pages( 'gsc', 10 );

		if ( empty( $decaying ) ) {
			return null;
		}

		// Only alert on significant decay (>20%).
		$significant = array_filter( $decaying, fn( $p ) => (float) $p['decline_pct'] < -20 );
		if ( empty( $significant ) ) {
			return null;
		}

		return [
			'type'     => 'content_decay',
			'title'    => sprintf(
				__( '%d pages showing content decay (>20%% click decline)', 'searchforge' ),
				count( $significant )
			),
			'items'    => array_values( $significant ),
			'severity' => count( $significant ) > 3 ? 'high' : 'medium',
		];
	}

	/**
	 * Send alert email.
	 */
	private function send_alert_email( array $alerts ): void {
		$email = Settings::get( 'alert_email' );
		if ( ! $email ) {
			$email = get_option( 'admin_email' );
		}

		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf( '[SearchForge] %d alert(s) for %s', count( $alerts ), $site_name );

		$body  = "SearchForge Alert Summary\n";
		$body .= "========================\n\n";
		$body .= "Site: " . home_url() . "\n";
		$body .= "Date: " . wp_date( 'Y-m-d H:i' ) . "\n\n";

		foreach ( $alerts as $alert ) {
			$severity = strtoupper( $alert['severity'] ?? 'info' );
			$body .= "[{$severity}] {$alert['title']}\n";

			foreach ( $alert['items'] as $item ) {
				if ( isset( $item['query'] ) ) {
					$body .= "  - \"{$item['query']}\" on {$item['page_path']}: "
						. "position {$item['prev_position']} → {$item['curr_position']}\n";
				} elseif ( isset( $item['page_path'] ) ) {
					$body .= "  - {$item['page_path']}: {$item['decline_pct']}% clicks\n";
				} elseif ( isset( $item['new_keywords'] ) ) {
					$body .= "  - {$item['page_path']}: {$item['new_keywords']} new keywords\n";
				} else {
					$body .= "  - " . wp_json_encode( $item ) . "\n";
				}
			}
			$body .= "\n";
		}

		$body .= "---\n";
		$body .= "View details: " . admin_url( 'admin.php?page=searchforge' ) . "\n";

		wp_mail( $email, $subject, $body );
	}

	/**
	 * Store alerts in the database for dashboard display.
	 */
	private function store_alerts( array $alerts ): void {
		global $wpdb;

		foreach ( $alerts as $alert ) {
			$wpdb->insert( "{$wpdb->prefix}sf_alerts", [
				'alert_type' => $alert['type'],
				'title'      => $alert['title'],
				'severity'   => $alert['severity'] ?? 'info',
				'data'       => wp_json_encode( $alert['items'] ),
				'created_at' => current_time( 'mysql', true ),
				'is_read'    => 0,
			] );
		}
	}

	/**
	 * Send weekly digest email.
	 *
	 * Delegates to the dedicated WeeklyDigest class to avoid duplicate implementations.
	 */
	public function send_weekly_digest(): void {
		\SearchForge\Notifications\WeeklyDigest::send();
	}
}
