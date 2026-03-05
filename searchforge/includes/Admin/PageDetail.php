<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class PageDetail {

	/**
	 * Get full detail data for a single page.
	 */
	public static function get_page_data( string $page_path ): ?array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc' AND device = 'all'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return null;
		}

		// Current metrics.
		$metrics = $wpdb->get_row( $wpdb->prepare(
			"SELECT clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc' AND device = 'all'",
			$page_path,
			$latest_date
		), ARRAY_A );

		if ( ! $metrics ) {
			return null;
		}

		return [
			'page_path'     => $page_path,
			'snapshot_date'  => $latest_date,
			'clicks'         => (int) $metrics['clicks'],
			'impressions'    => (int) $metrics['impressions'],
			'ctr'            => (float) $metrics['ctr'],
			'position'       => (float) $metrics['position'],
		];
	}

	/**
	 * Get all keywords ranking for a given page.
	 */
	public static function get_page_keywords( string $page_path, int $limit = 100 ): array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return [];
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT query, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc'
			ORDER BY clicks DESC
			LIMIT %d",
			$page_path,
			$latest_date,
			$limit
		), ARRAY_A );
	}

	/**
	 * Get device breakdown for a page.
	 */
	public static function get_device_breakdown( string $page_path ): array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return [];
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT device, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc'
				AND device != 'all'
			ORDER BY clicks DESC",
			$page_path,
			$latest_date
		), ARRAY_A );
	}

	/**
	 * Get daily trend data for charts (last N days).
	 */
	public static function get_daily_trend( string $page_path, int $days = 30 ): array {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT snapshot_date, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc' AND device = 'all'
				AND snapshot_date >= %s
			ORDER BY snapshot_date ASC",
			$page_path,
			$cutoff
		), ARRAY_A );
	}

	/**
	 * Get Bing data for comparison (if available).
	 */
	public static function get_bing_data( string $page_path ): ?array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'bing' AND device = 'all'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND snapshot_date = %s AND source = 'bing' AND device = 'all'",
			$page_path,
			$latest_date
		), ARRAY_A );
	}

	/**
	 * Get GA4 behavior data for the page (if available).
	 */
	public static function get_ga4_data( string $page_path ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'sf_ga4_metrics';

		// Check if table exists.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT page_path, sessions, bounce_rate, avg_session_duration,
				conversions, page_views
			FROM {$table}
			WHERE page_path = %s
			ORDER BY snapshot_date DESC
			LIMIT 1",
			$page_path
		), ARRAY_A );
	}

	/**
	 * Get keyword position distribution for a page.
	 */
	public static function get_position_distribution( string $page_path ): array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return [];
		}

		$buckets = [
			'1-3'   => 0,
			'4-10'  => 0,
			'11-20' => 0,
			'21-50' => 0,
			'50+'   => 0,
		];

		$keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT position FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc'",
			$page_path,
			$latest_date
		), ARRAY_A );

		foreach ( $keywords as $kw ) {
			$pos = (float) $kw['position'];
			if ( $pos <= 3 ) {
				$buckets['1-3']++;
			} elseif ( $pos <= 10 ) {
				$buckets['4-10']++;
			} elseif ( $pos <= 20 ) {
				$buckets['11-20']++;
			} elseif ( $pos <= 50 ) {
				$buckets['21-50']++;
			} else {
				$buckets['50+']++;
			}
		}

		return $buckets;
	}

	/**
	 * Get cannibalization issues for a specific page.
	 */
	public static function get_page_cannibalization( string $page_path ): array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return [];
		}

		// Find queries where this page competes with other pages.
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT k1.query, k1.clicks AS my_clicks, k1.position AS my_position,
				k2.page_path AS competing_page, k2.clicks AS their_clicks, k2.position AS their_position
			FROM {$wpdb->prefix}sf_keywords k1
			INNER JOIN {$wpdb->prefix}sf_keywords k2
				ON k1.query = k2.query
				AND k1.source = k2.source
				AND k1.snapshot_date = k2.snapshot_date
				AND k1.page_path != k2.page_path
			WHERE k1.page_path = %s AND k1.snapshot_date = %s AND k1.source = 'gsc'
			ORDER BY k1.clicks DESC
			LIMIT 20",
			$page_path,
			$latest_date
		), ARRAY_A );
	}
}
