<?php

namespace SearchForge\Analysis;

defined( 'ABSPATH' ) || exit;

/**
 * Keyword cannibalization detection.
 *
 * Identifies queries where multiple pages from the same site compete
 * for the same keyword (both ranking in GSC data).
 */
class Cannibalization {

	/**
	 * Detect cannibalization: queries ranking for multiple pages.
	 *
	 * @return array  [ [ 'query' => ..., 'pages' => [...], 'severity' => ... ], ... ]
	 */
	public static function detect( int $limit = 50 ): array {
		global $wpdb;

		$latest_date = $wpdb->get_var(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_keywords WHERE source = 'gsc'"
		);

		if ( ! $latest_date ) {
			return [];
		}

		// Find queries ranking for 2+ different pages.
		$candidates = $wpdb->get_results( $wpdb->prepare(
			"SELECT query, COUNT(DISTINCT page_path) AS page_count,
				SUM(clicks) AS total_clicks, SUM(impressions) AS total_impressions
			FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc' AND snapshot_date = %s
			GROUP BY query
			HAVING page_count >= 2
			ORDER BY total_impressions DESC
			LIMIT %d",
			$latest_date,
			$limit
		), ARRAY_A );

		if ( empty( $candidates ) ) {
			return [];
		}

		$results = [];

		foreach ( $candidates as $candidate ) {
			$pages = $wpdb->get_results( $wpdb->prepare(
				"SELECT page_path, clicks, impressions, position, ctr
				FROM {$wpdb->prefix}sf_keywords
				WHERE source = 'gsc' AND snapshot_date = %s AND query = %s
				ORDER BY position ASC",
				$latest_date,
				$candidate['query']
			), ARRAY_A );

			// Calculate severity based on position spread and click distribution.
			$positions = array_column( $pages, 'position' );
			$min_pos   = min( array_map( 'floatval', $positions ) );
			$max_pos   = max( array_map( 'floatval', $positions ) );
			$spread    = $max_pos - $min_pos;

			// High severity: multiple pages on page 1, or pages within 5 positions.
			if ( $min_pos <= 10 && count( $pages ) > 1 && $spread < 10 ) {
				$severity = 'high';
			} elseif ( $min_pos <= 20 && $spread < 15 ) {
				$severity = 'medium';
			} else {
				$severity = 'low';
			}

			$results[] = [
				'query'             => $candidate['query'],
				'page_count'        => (int) $candidate['page_count'],
				'total_clicks'      => (int) $candidate['total_clicks'],
				'total_impressions' => (int) $candidate['total_impressions'],
				'pages'             => $pages,
				'severity'          => $severity,
				'position_spread'   => round( $spread, 1 ),
			];
		}

		// Sort by severity (high > medium > low), then by impressions.
		$severity_order = [ 'high' => 0, 'medium' => 1, 'low' => 2 ];
		usort( $results, function ( $a, $b ) use ( $severity_order ) {
			$sa = $severity_order[ $a['severity'] ] ?? 3;
			$sb = $severity_order[ $b['severity'] ] ?? 3;
			if ( $sa !== $sb ) {
				return $sa - $sb;
			}
			return $b['total_impressions'] - $a['total_impressions'];
		} );

		return $results;
	}
}
