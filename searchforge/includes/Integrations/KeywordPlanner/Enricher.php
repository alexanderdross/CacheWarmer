<?php

namespace SearchForge\Integrations\KeywordPlanner;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Enriches existing GSC/Bing keyword data with Keyword Planner volume data.
 */
class Enricher {

	/**
	 * Enrich keywords in the database with search volume data.
	 *
	 * @return array|\WP_Error  [ 'enriched' => int, 'errors' => int ]
	 */
	public function enrich_keywords(): array|\WP_Error {
		global $wpdb;

		if ( ! Settings::is_pro() ) {
			return new \WP_Error( 'not_pro', __( 'Keyword Planner requires a Pro license.', 'searchforge' ) );
		}

		// Get unique keywords without volume data.
		$keywords = $wpdb->get_col(
			"SELECT DISTINCT query FROM {$wpdb->prefix}sf_keywords
			WHERE search_volume IS NULL
			ORDER BY clicks DESC
			LIMIT 500"
		);

		if ( empty( $keywords ) ) {
			return [ 'enriched' => 0, 'errors' => 0 ];
		}

		$enriched = 0;
		$errors   = 0;

		// Process in batches of 50.
		$batches = array_chunk( $keywords, 50 );

		foreach ( $batches as $batch ) {
			$volumes = Client::get_search_volumes( $batch );
			if ( is_wp_error( $volumes ) ) {
				$errors += count( $batch );
				continue;
			}

			foreach ( $volumes as $keyword => $data ) {
				if ( empty( $keyword ) ) {
					continue;
				}

				$updated = $wpdb->query( $wpdb->prepare(
					"UPDATE {$wpdb->prefix}sf_keywords
					SET search_volume = %d, competition = %s
					WHERE query = %s AND search_volume IS NULL",
					$data['volume'],
					$data['competition'],
					$keyword
				) );

				if ( $updated ) {
					$enriched++;
				}
			}

			// Rate limiting: 1 second between batches.
			if ( count( $batches ) > 1 ) {
				sleep( 1 );
			}
		}

		return [ 'enriched' => $enriched, 'errors' => $errors ];
	}

	/**
	 * Get content gap analysis: keywords where competitors rank but you don't.
	 * Uses Keyword Planner suggestions based on your top queries.
	 *
	 * @return array  [ [ 'keyword' => ..., 'volume' => ..., 'competition' => ... ], ... ]
	 */
	public function get_content_gaps( int $limit = 20 ): array {
		global $wpdb;

		// Get your top existing keywords as seeds.
		$seeds = $wpdb->get_col(
			"SELECT DISTINCT query FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc'
			ORDER BY clicks DESC
			LIMIT 10"
		);

		if ( empty( $seeds ) ) {
			return [];
		}

		$ideas = Client::get_keyword_ideas( $seeds );
		if ( is_wp_error( $ideas ) ) {
			return [];
		}

		// Filter out keywords you already rank for.
		$existing = $wpdb->get_col(
			"SELECT DISTINCT query FROM {$wpdb->prefix}sf_keywords WHERE source = 'gsc'"
		);
		$existing_set = array_flip( $existing );

		$gaps = [];
		foreach ( $ideas as $idea ) {
			if ( isset( $existing_set[ $idea['keyword'] ] ) ) {
				continue;
			}
			if ( $idea['volume'] < 10 ) {
				continue;
			}
			$gaps[] = $idea;
		}

		// Sort by volume descending.
		usort( $gaps, fn( $a, $b ) => $b['volume'] - $a['volume'] );

		return array_slice( $gaps, 0, $limit );
	}
}
