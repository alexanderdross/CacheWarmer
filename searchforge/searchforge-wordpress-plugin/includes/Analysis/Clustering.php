<?php

namespace SearchForge\Analysis;

defined( 'ABSPATH' ) || exit;

/**
 * Keyword clustering using n-gram Jaccard similarity.
 *
 * Groups keywords into topic clusters without requiring
 * external AI APIs — runs entirely in PHP.
 */
class Clustering {

	/**
	 * Cluster keywords into topic groups.
	 *
	 * @return array  [ [ 'name' => ..., 'keywords' => [...], 'total_clicks' => ... ], ... ]
	 */
	public static function cluster_keywords( float $threshold = 0.3, int $limit = 500 ): array {
		global $wpdb;

		$latest_date = $wpdb->get_var(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_keywords WHERE source = 'gsc'"
		);

		if ( ! $latest_date ) {
			return [];
		}

		$keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT query, SUM(clicks) as total_clicks, SUM(impressions) as total_impressions,
				AVG(position) as avg_position
			FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc' AND snapshot_date = %s
			GROUP BY query
			ORDER BY total_clicks DESC
			LIMIT %d",
			$latest_date,
			$limit
		), ARRAY_A );

		if ( count( $keywords ) < 2 ) {
			return [];
		}

		// Generate n-grams for each keyword.
		$ngrams = [];
		foreach ( $keywords as $i => $kw ) {
			$ngrams[ $i ] = self::get_ngrams( $kw['query'], 2 );
		}

		// Build inverted index: bigram -> list of keyword indices.
		$bigram_index = [];
		foreach ( $ngrams as $i => $grams ) {
			foreach ( $grams as $gram ) {
				// Only index bigrams (contain a space) for candidate pair generation.
				if ( str_contains( $gram, ' ' ) ) {
					$bigram_index[ $gram ][] = $i;
				}
			}
		}

		// Build candidate pairs: only compare keywords sharing at least one bigram.
		$candidates = [];
		foreach ( $bigram_index as $indices ) {
			$count = count( $indices );
			for ( $a = 0; $a < $count; $a++ ) {
				for ( $b = $a + 1; $b < $count; $b++ ) {
					$i = $indices[ $a ];
					$j = $indices[ $b ];
					$key = $i < $j ? "{$i}:{$j}" : "{$j}:{$i}";
					$candidates[ $key ] = true;
				}
			}
		}

		// Also consider keywords sharing unigrams (single words) for short queries.
		// Build unigram index for keywords with no bigrams.
		$unigram_index = [];
		foreach ( $ngrams as $i => $grams ) {
			$has_bigram = false;
			foreach ( $grams as $gram ) {
				if ( str_contains( $gram, ' ' ) ) {
					$has_bigram = true;
					break;
				}
			}
			if ( ! $has_bigram ) {
				foreach ( $grams as $gram ) {
					$unigram_index[ $gram ][] = $i;
				}
			}
		}
		foreach ( $unigram_index as $indices ) {
			$count = count( $indices );
			for ( $a = 0; $a < $count; $a++ ) {
				for ( $b = $a + 1; $b < $count; $b++ ) {
					$i = $indices[ $a ];
					$j = $indices[ $b ];
					$key = $i < $j ? "{$i}:{$j}" : "{$j}:{$i}";
					$candidates[ $key ] = true;
				}
			}
		}

		// Precompute similarity only for candidate pairs.
		$similar_pairs = [];
		foreach ( $candidates as $key => $_ ) {
			[ $i, $j ] = explode( ':', $key );
			$i = (int) $i;
			$j = (int) $j;
			$similarity = self::jaccard( $ngrams[ $i ], $ngrams[ $j ] );
			if ( $similarity >= $threshold ) {
				$similar_pairs[ $i ][] = $j;
				$similar_pairs[ $j ][] = $i;
			}
		}

		// Single-pass greedy clustering using precomputed similar pairs.
		$assigned  = [];
		$clusters  = [];

		foreach ( $keywords as $i => $kw ) {
			if ( isset( $assigned[ $i ] ) ) {
				continue;
			}

			$cluster = [
				'keywords'          => [ $kw ],
				'total_clicks'      => (int) $kw['total_clicks'],
				'total_impressions' => (int) $kw['total_impressions'],
			];
			$assigned[ $i ] = true;

			if ( isset( $similar_pairs[ $i ] ) ) {
				foreach ( $similar_pairs[ $i ] as $j ) {
					if ( isset( $assigned[ $j ] ) ) {
						continue;
					}

					$cluster['keywords'][]        = $keywords[ $j ];
					$cluster['total_clicks']      += (int) $keywords[ $j ]['total_clicks'];
					$cluster['total_impressions'] += (int) $keywords[ $j ]['total_impressions'];
					$assigned[ $j ]               = true;
				}
			}

			// Only include clusters with 2+ keywords.
			if ( count( $cluster['keywords'] ) >= 2 ) {
				// Name the cluster after the highest-traffic keyword.
				$cluster['name'] = $cluster['keywords'][0]['query'];
				$clusters[] = $cluster;
			}
		}

		// Sort clusters by total clicks.
		usort( $clusters, fn( $a, $b ) => $b['total_clicks'] - $a['total_clicks'] );

		return $clusters;
	}

	/**
	 * Generate character n-grams from a string.
	 */
	private static function get_ngrams( string $text, int $n = 2 ): array {
		$text   = mb_strtolower( trim( $text ) );
		$words  = preg_split( '/\s+/', $text );
		$ngrams = [];

		// Word-level unigrams and bigrams.
		foreach ( $words as $word ) {
			$ngrams[] = $word;
		}

		for ( $i = 0; $i < count( $words ) - 1; $i++ ) {
			$ngrams[] = $words[ $i ] . ' ' . $words[ $i + 1 ];
		}

		return array_unique( $ngrams );
	}

	/**
	 * Jaccard similarity between two n-gram sets.
	 */
	private static function jaccard( array $a, array $b ): float {
		if ( empty( $a ) || empty( $b ) ) {
			return 0.0;
		}

		$intersection = count( array_intersect( $a, $b ) );
		$union        = count( array_unique( array_merge( $a, $b ) ) );

		return $union > 0 ? $intersection / $union : 0.0;
	}
}
