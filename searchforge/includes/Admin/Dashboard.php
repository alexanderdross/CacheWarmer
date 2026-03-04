<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class Dashboard {

	public function __construct() {
		// Dashboard-specific hooks if needed.
	}

	/**
	 * Get summary stats for the dashboard.
	 */
	public static function get_summary(): array {
		global $wpdb;

		$latest_date = $wpdb->get_var(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots WHERE source = 'gsc'"
		);

		if ( ! $latest_date ) {
			return [
				'total_pages'       => 0,
				'total_clicks'      => 0,
				'total_impressions' => 0,
				'avg_position'      => 0,
				'avg_ctr'           => 0,
				'total_keywords'    => 0,
				'last_sync'         => null,
				'date_range'        => null,
			];
		}

		// Get stats for the most recent snapshot date.
		$page_stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(DISTINCT page_path) as total_pages,
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions,
				AVG(position) as avg_position,
				AVG(ctr) as avg_ctr
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND snapshot_date = %s AND device = 'all'",
			$latest_date
		) );

		$keyword_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT query) FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc' AND snapshot_date = %s",
			$latest_date
		) );

		$last_sync = $wpdb->get_row(
			"SELECT started_at, status FROM {$wpdb->prefix}sf_sync_log
			WHERE source = 'gsc' ORDER BY id DESC LIMIT 1"
		);

		return [
			'total_pages'       => (int) ( $page_stats->total_pages ?? 0 ),
			'total_clicks'      => (int) ( $page_stats->total_clicks ?? 0 ),
			'total_impressions' => (int) ( $page_stats->total_impressions ?? 0 ),
			'avg_position'      => round( (float) ( $page_stats->avg_position ?? 0 ), 1 ),
			'avg_ctr'           => round( (float) ( $page_stats->avg_ctr ?? 0 ) * 100, 1 ),
			'total_keywords'    => $keyword_count,
			'last_sync'         => $last_sync->started_at ?? null,
			'sync_status'       => $last_sync->status ?? null,
			'date_range'        => $latest_date,
		];
	}

	/**
	 * Get top pages by clicks.
	 */
	public static function get_top_pages( int $limit = 10, string $date = '' ): array {
		global $wpdb;

		if ( ! $date ) {
			$date = $wpdb->get_var(
				"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots WHERE source = 'gsc'"
			);
		}

		if ( ! $date ) {
			return [];
		}

		$page_limit = Settings::get_page_limit();
		$query_limit = $page_limit > 0 ? min( $limit, $page_limit ) : $limit;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT page_path, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND snapshot_date = %s AND device = 'all'
			ORDER BY clicks DESC
			LIMIT %d",
			$date,
			$query_limit
		), ARRAY_A );
	}

	/**
	 * Get top keywords by clicks.
	 */
	public static function get_top_keywords( int $limit = 20, string $date = '' ): array {
		global $wpdb;

		if ( ! $date ) {
			$date = $wpdb->get_var(
				"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_keywords WHERE source = 'gsc'"
			);
		}

		if ( ! $date ) {
			return [];
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT query, page_path, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc' AND snapshot_date = %s
			ORDER BY clicks DESC
			LIMIT %d",
			$date,
			$limit
		), ARRAY_A );
	}
}
