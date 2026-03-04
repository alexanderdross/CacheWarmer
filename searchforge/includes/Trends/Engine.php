<?php

namespace SearchForge\Trends;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Engine {

	/**
	 * Get historical trend data for a page.
	 *
	 * Returns weekly snapshots with change percentages and decay detection.
	 */
	public static function get_page_trend( string $page_path, string $source = 'gsc' ): ?array {
		global $wpdb;

		if ( ! Settings::is_pro() ) {
			return null;
		}

		$retention = Settings::get_retention_days();
		$cutoff    = gmdate( 'Y-m-d', strtotime( "-{$retention} days" ) );

		// Get weekly aggregated snapshots.
		$snapshots = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				MIN(snapshot_date) as date,
				SUM(clicks) as clicks,
				SUM(impressions) as impressions,
				AVG(position) as position,
				AVG(ctr) as ctr,
				YEARWEEK(snapshot_date, 1) as yearweek
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = %s AND device = 'all'
				AND snapshot_date >= %s
			GROUP BY yearweek
			ORDER BY yearweek ASC",
			$page_path,
			$source,
			$cutoff
		), ARRAY_A );

		if ( count( $snapshots ) < 2 ) {
			return null;
		}

		// Calculate period-over-period changes.
		$processed = [];
		$prev      = null;

		foreach ( $snapshots as $snap ) {
			$entry = [
				'date'        => $snap['date'],
				'clicks'      => (int) $snap['clicks'],
				'impressions' => (int) $snap['impressions'],
				'position'    => round( (float) $snap['position'], 1 ),
				'ctr'         => round( (float) $snap['ctr'] * 100, 1 ),
			];

			if ( $prev ) {
				$prev_clicks         = max( 1, $prev['clicks'] );
				$entry['clicks_change']      = round( ( $entry['clicks'] - $prev['clicks'] ) / $prev_clicks * 100, 1 );
				$entry['impressions_change'] = round( ( $entry['impressions'] - $prev['impressions'] ) / max( 1, $prev['impressions'] ) * 100, 1 );
				$entry['position_change']    = round( $prev['position'] - $entry['position'], 1 ); // Positive = improvement.
			}

			$processed[] = $entry;
			$prev        = $entry;
		}

		// Decay detection: compare most recent 2 weeks to 2 weeks before that.
		$decay = self::detect_decay( $processed );

		return [
			'snapshots'         => $processed,
			'decay_detected'    => $decay['detected'],
			'decay_percentage'  => $decay['percentage'],
			'decay_period_days' => $decay['period_days'],
		];
	}

	/**
	 * Get YoY comparison for a page.
	 *
	 * @return array|null  [ 'current' => [...], 'previous' => [...], 'changes' => [...] ]
	 */
	public static function get_yoy_comparison( string $page_path, string $source = 'gsc' ): ?array {
		global $wpdb;

		if ( ! Settings::is_pro() ) {
			return null;
		}

		// Current period: last 28 days.
		$end   = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
		$start = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		// YoY period: same 28 days one year ago.
		$prev_end   = gmdate( 'Y-m-d', strtotime( '-2 days -1 year' ) );
		$prev_start = gmdate( 'Y-m-d', strtotime( '-30 days -1 year' ) );

		$current = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(clicks) as clicks, SUM(impressions) as impressions,
				AVG(position) as position, AVG(ctr) as ctr
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = %s AND device = 'all'
				AND snapshot_date BETWEEN %s AND %s",
			$page_path,
			$source,
			$start,
			$end
		), ARRAY_A );

		$previous = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(clicks) as clicks, SUM(impressions) as impressions,
				AVG(position) as position, AVG(ctr) as ctr
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = %s AND device = 'all'
				AND snapshot_date BETWEEN %s AND %s",
			$page_path,
			$source,
			$prev_start,
			$prev_end
		), ARRAY_A );

		if ( ! $previous || ! (int) $previous['clicks'] ) {
			return null;
		}

		$changes = [
			'clicks'      => self::pct_change( (int) $previous['clicks'], (int) $current['clicks'] ),
			'impressions' => self::pct_change( (int) $previous['impressions'], (int) $current['impressions'] ),
			'position'    => round( (float) $previous['position'] - (float) $current['position'], 1 ),
		];

		return [
			'current'  => $current,
			'previous' => $previous,
			'changes'  => $changes,
			'period'   => "{$start} to {$end} vs {$prev_start} to {$prev_end}",
		];
	}

	/**
	 * Get all pages with content decay (declining clicks over 30 days).
	 *
	 * @return array  List of [ 'page_path' => ..., 'decline_pct' => ..., ... ]
	 */
	public static function get_decaying_pages( string $source = 'gsc', int $limit = 20 ): array {
		global $wpdb;

		$recent_end   = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
		$recent_start = gmdate( 'Y-m-d', strtotime( '-16 days' ) );
		$prev_end     = gmdate( 'Y-m-d', strtotime( '-16 days' ) );
		$prev_start   = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				r.page_path,
				r.clicks AS recent_clicks,
				p.clicks AS prev_clicks,
				ROUND((r.clicks - p.clicks) / GREATEST(p.clicks, 1) * 100, 1) AS decline_pct,
				r.position AS recent_position,
				p.position AS prev_position
			FROM (
				SELECT page_path, SUM(clicks) AS clicks, AVG(position) AS position
				FROM {$wpdb->prefix}sf_snapshots
				WHERE source = %s AND device = 'all'
					AND snapshot_date BETWEEN %s AND %s
				GROUP BY page_path
			) r
			INNER JOIN (
				SELECT page_path, SUM(clicks) AS clicks, AVG(position) AS position
				FROM {$wpdb->prefix}sf_snapshots
				WHERE source = %s AND device = 'all'
					AND snapshot_date BETWEEN %s AND %s
				GROUP BY page_path
			) p ON r.page_path = p.page_path
			WHERE p.clicks > 5 AND r.clicks < p.clicks
			ORDER BY decline_pct ASC
			LIMIT %d",
			$source,
			$recent_start,
			$recent_end,
			$source,
			$prev_start,
			$prev_end,
			$limit
		), ARRAY_A );

		return $results ?: [];
	}

	/**
	 * Get pages with new keyword acquisitions.
	 *
	 * @return array
	 */
	public static function get_new_keyword_pages( string $source = 'gsc', int $days = 7 ): array {
		global $wpdb;

		$recent_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		$prev_date   = gmdate( 'Y-m-d', strtotime( '-' . ( $days * 2 ) . ' days' ) );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT r.page_path, COUNT(DISTINCT r.query) AS new_keywords
			FROM {$wpdb->prefix}sf_keywords r
			LEFT JOIN {$wpdb->prefix}sf_keywords p
				ON r.query = p.query AND r.page_path = p.page_path
				AND p.source = %s AND p.snapshot_date = %s
			WHERE r.source = %s AND r.snapshot_date >= %s AND p.id IS NULL
			GROUP BY r.page_path
			HAVING new_keywords > 0
			ORDER BY new_keywords DESC
			LIMIT 20",
			$source,
			$prev_date,
			$source,
			$recent_date
		), ARRAY_A );

		return $results ?: [];
	}

	/**
	 * Detect content decay from processed snapshots.
	 */
	private static function detect_decay( array $snapshots ): array {
		$count = count( $snapshots );
		if ( $count < 4 ) {
			return [ 'detected' => false, 'percentage' => 0, 'period_days' => 0 ];
		}

		// Compare last 2 entries to 2 entries before that.
		$recent = array_slice( $snapshots, -2 );
		$prior  = array_slice( $snapshots, -4, 2 );

		$recent_clicks = array_sum( array_column( $recent, 'clicks' ) );
		$prior_clicks  = array_sum( array_column( $prior, 'clicks' ) );

		if ( $prior_clicks < 10 ) {
			return [ 'detected' => false, 'percentage' => 0, 'period_days' => 0 ];
		}

		$change = ( $recent_clicks - $prior_clicks ) / $prior_clicks * 100;

		// Decay threshold: >20% decline.
		$detected = $change < -20;

		return [
			'detected'    => $detected,
			'percentage'  => round( $change, 1 ),
			'period_days' => 28, // 4 weeks.
		];
	}

	private static function pct_change( int $old, int $new ): float {
		if ( $old === 0 ) {
			return $new > 0 ? 100.0 : 0.0;
		}
		return round( ( $new - $old ) / $old * 100, 1 );
	}
}
