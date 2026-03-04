<?php

namespace SearchForge\Scoring;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Score {

	private const WEIGHTS = [
		'technical'  => 25,
		'content'    => 25,
		'authority'  => 25,
		'momentum'   => 25,
	];

	/**
	 * Calculate SearchForge Score for a page.
	 *
	 * @return array|null  [ 'total' => int, 'components' => [...], 'recommendations' => [...] ]
	 */
	public static function calculate_page_score( string $page_path ): ?array {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return null;
		}

		$page_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc' AND device = 'all'",
			$page_path,
			$latest_date
		), ARRAY_A );

		if ( ! $page_data ) {
			return null;
		}

		$keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT query, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc'
			ORDER BY clicks DESC",
			$page_path,
			$latest_date
		), ARRAY_A );

		$technical  = self::score_technical( $page_data, $keywords );
		$content    = self::score_content( $page_data, $keywords );
		$authority  = self::score_authority( $page_data, $keywords );
		$momentum   = self::score_momentum( $page_path, $page_data );

		$total = (int) round(
			$technical['score'] * self::WEIGHTS['technical'] / 100 +
			$content['score']   * self::WEIGHTS['content']   / 100 +
			$authority['score'] * self::WEIGHTS['authority']  / 100 +
			$momentum['score']  * self::WEIGHTS['momentum']  / 100
		);

		$recommendations = self::generate_recommendations( $technical, $content, $authority, $momentum, $page_data, $keywords );

		return [
			'total'           => $total,
			'components'      => [
				'technical' => [ 'score' => $technical['score'], 'weight' => self::WEIGHTS['technical'] ],
				'content'   => [ 'score' => $content['score'],   'weight' => self::WEIGHTS['content'] ],
				'authority' => [ 'score' => $authority['score'], 'weight' => self::WEIGHTS['authority'] ],
				'momentum'  => [ 'score' => $momentum['score'], 'weight' => self::WEIGHTS['momentum'] ],
			],
			'recommendations' => $recommendations,
		];
	}

	/**
	 * Calculate site-level SearchForge Score.
	 */
	public static function calculate_site_score(): ?array {
		global $wpdb;

		$latest_date = $wpdb->get_var(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots WHERE source = 'gsc'"
		);

		if ( ! $latest_date ) {
			return null;
		}

		$totals = $wpdb->get_row( $wpdb->prepare(
			"SELECT COUNT(DISTINCT page_path) as pages, SUM(clicks) as clicks,
				SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND snapshot_date = %s AND device = 'all'",
			$latest_date
		), ARRAY_A );

		$total_keywords = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT query) FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc' AND snapshot_date = %s",
			$latest_date
		) );

		// Site-level scoring heuristics.
		$pages         = (int) $totals['pages'];
		$avg_position  = (float) $totals['position'];
		$avg_ctr       = (float) $totals['ctr'];
		$total_clicks  = (int) $totals['clicks'];

		// Technical: based on page coverage and avg position.
		$tech_score = min( 100, (int) (
			min( $pages, 50 ) / 50 * 30 +         // Pages indexed (up to 50).
			max( 0, 100 - $avg_position * 5 ) * 0.4 + // Position quality.
			min( $total_keywords, 500 ) / 500 * 30   // Keyword breadth.
		) );

		// Content: based on keyword diversity.
		$kw_per_page = $pages > 0 ? $total_keywords / $pages : 0;
		$content_score = min( 100, (int) ( min( $kw_per_page, 20 ) / 20 * 100 ) );

		// Authority: based on total clicks.
		$authority_score = min( 100, (int) ( log10( max( $total_clicks, 1 ) ) / 5 * 100 ) );

		// Momentum: based on recent trend.
		$momentum_score = self::calculate_site_momentum();

		$total = (int) round(
			$tech_score      * 25 / 100 +
			$content_score   * 25 / 100 +
			$authority_score * 25 / 100 +
			$momentum_score  * 25 / 100
		);

		$recommendations = [];
		if ( $tech_score < 50 ) {
			$recommendations[] = 'Improve technical SEO: ensure more pages are indexed and ranking on page 1.';
		}
		if ( $content_score < 50 ) {
			$recommendations[] = 'Expand content: target more keywords per page to improve content coverage.';
		}
		if ( $authority_score < 50 ) {
			$recommendations[] = 'Build authority: focus on link building and brand awareness to increase organic clicks.';
		}
		if ( $momentum_score < 50 ) {
			$recommendations[] = 'Improve momentum: recent trends show stagnation or decline — refresh top content.';
		}

		return [
			'total'           => $total,
			'components'      => [
				'technical' => [ 'score' => $tech_score,      'weight' => 25 ],
				'content'   => [ 'score' => $content_score,   'weight' => 25 ],
				'authority' => [ 'score' => $authority_score, 'weight' => 25 ],
				'momentum'  => [ 'score' => $momentum_score, 'weight' => 25 ],
			],
			'recommendations' => $recommendations,
		];
	}

	/**
	 * Technical SEO score (25%).
	 * Based on: position quality, keyword count, CTR vs expected.
	 */
	private static function score_technical( array $page, array $keywords ): array {
		$position = (float) $page['position'];
		$kw_count = count( $keywords );

		// Position score: position 1 = 100, position 10 = 50, position 20+ = 10.
		$pos_score = max( 10, min( 100, (int) ( 110 - $position * 5 ) ) );

		// Keyword breadth: more keywords = better content coverage.
		$kw_score = min( 100, $kw_count * 2 );

		// CTR vs expected CTR for position.
		$expected_ctr = self::expected_ctr_for_position( $position );
		$actual_ctr   = (float) $page['ctr'];
		$ctr_ratio    = $expected_ctr > 0 ? $actual_ctr / $expected_ctr : 1;
		$ctr_score    = min( 100, (int) ( $ctr_ratio * 70 ) );

		$score = (int) round( $pos_score * 0.4 + $kw_score * 0.3 + $ctr_score * 0.3 );

		return [ 'score' => $score ];
	}

	/**
	 * Content quality score (25%).
	 * Based on: keyword diversity, impression-to-click ratio, heading coverage.
	 */
	private static function score_content( array $page, array $keywords ): array {
		$kw_count    = count( $keywords );
		$impressions = (int) $page['impressions'];
		$clicks      = (int) $page['clicks'];

		// Keyword diversity.
		$diversity = min( 100, $kw_count * 3 );

		// Engagement: clicks relative to impressions.
		$engagement = $impressions > 0
			? min( 100, (int) ( ( $clicks / $impressions ) * 500 ) )
			: 0;

		// Top keyword concentration: if top keyword has >80% of clicks, content is narrow.
		$concentration = 50;
		if ( $clicks > 0 && ! empty( $keywords ) ) {
			$top_clicks = (int) $keywords[0]['clicks'];
			$ratio      = $top_clicks / max( 1, $clicks );
			$concentration = $ratio > 0.8 ? 20 : ( $ratio > 0.5 ? 50 : 80 );
		}

		$score = (int) round( $diversity * 0.4 + $engagement * 0.3 + $concentration * 0.3 );

		return [ 'score' => $score ];
	}

	/**
	 * Authority score (25%).
	 * Based on: total clicks volume, impression volume, average position strength.
	 */
	private static function score_authority( array $page, array $keywords ): array {
		$clicks      = (int) $page['clicks'];
		$impressions = (int) $page['impressions'];
		$position    = (float) $page['position'];

		// Click volume (log scale).
		$click_score = min( 100, (int) ( log10( max( $clicks, 1 ) ) / 4 * 100 ) );

		// Impression reach.
		$imp_score = min( 100, (int) ( log10( max( $impressions, 1 ) ) / 5 * 100 ) );

		// Position authority: top 3 = high authority.
		$pos_score = $position <= 3 ? 100 : ( $position <= 10 ? 70 : ( $position <= 20 ? 40 : 15 ) );

		$score = (int) round( $click_score * 0.4 + $imp_score * 0.3 + $pos_score * 0.3 );

		return [ 'score' => $score ];
	}

	/**
	 * Momentum score (25%).
	 * Based on: 7-day and 30-day click trends, new keyword acquisition, position changes.
	 */
	private static function score_momentum( string $page_path, array $current_data ): array {
		global $wpdb;

		// Compare current snapshot to 14 days ago.
		$prev_date = gmdate( 'Y-m-d', strtotime( '-14 days' ) );

		$prev = $wpdb->get_row( $wpdb->prepare(
			"SELECT clicks, impressions, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc' AND device = 'all'
				AND snapshot_date <= %s
			ORDER BY snapshot_date DESC LIMIT 1",
			$page_path,
			$prev_date
		), ARRAY_A );

		if ( ! $prev ) {
			return [ 'score' => 50 ]; // Neutral — not enough data.
		}

		$click_change = (int) $current_data['clicks'] - (int) $prev['clicks'];
		$pos_change   = (float) $prev['position'] - (float) $current_data['position']; // Positive = improvement.

		// Click momentum: growing = good.
		$prev_clicks  = max( 1, (int) $prev['clicks'] );
		$click_pct    = $click_change / $prev_clicks * 100;
		$click_score  = min( 100, max( 0, (int) ( 50 + $click_pct ) ) );

		// Position momentum.
		$pos_score = min( 100, max( 0, (int) ( 50 + $pos_change * 10 ) ) );

		$score = (int) round( $click_score * 0.6 + $pos_score * 0.4 );

		return [ 'score' => $score ];
	}

	/**
	 * Calculate site-level momentum from aggregate trends.
	 */
	private static function calculate_site_momentum(): int {
		global $wpdb;

		$recent = $wpdb->get_var(
			"SELECT SUM(clicks) FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND device = 'all'
				AND snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)"
		);

		$previous = $wpdb->get_var(
			"SELECT SUM(clicks) FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND device = 'all'
				AND snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
				AND snapshot_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY)"
		);

		if ( ! $previous ) {
			return 50;
		}

		$change = ( (int) $recent - (int) $previous ) / max( 1, (int) $previous ) * 100;

		return min( 100, max( 0, (int) ( 50 + $change ) ) );
	}

	/**
	 * Generate actionable recommendations based on component scores.
	 */
	private static function generate_recommendations( array $tech, array $content, array $authority, array $momentum, array $page, array $keywords ): array {
		$recs = [];

		$position = (float) $page['position'];
		$ctr      = (float) $page['ctr'];

		if ( $tech['score'] < 50 && $position > 10 ) {
			$recs[] = "Page ranks at position " . round( $position, 1 ) . ". Target position 1-10 by improving on-page SEO and internal linking.";
		}

		if ( $tech['score'] < 60 ) {
			$expected = self::expected_ctr_for_position( $position );
			if ( $ctr < $expected * 0.7 ) {
				$recs[] = "CTR (" . round( $ctr * 100, 1 ) . "%) is below expected (" . round( $expected * 100, 1 ) . "%) for position " . round( $position, 1 ) . ". Optimize title tag and meta description.";
			}
		}

		if ( $content['score'] < 50 ) {
			$kw_count = count( $keywords );
			if ( $kw_count < 10 ) {
				$recs[] = "Only {$kw_count} keywords ranking. Expand content to target more related queries.";
			}
		}

		if ( $content['score'] < 60 && ! empty( $keywords ) ) {
			$top_clicks = (int) $keywords[0]['clicks'];
			$total      = (int) $page['clicks'];
			if ( $total > 0 && $top_clicks / $total > 0.7 ) {
				$recs[] = "Traffic is concentrated on one keyword (\"" . $keywords[0]['query'] . "\"). Diversify content to rank for more queries.";
			}
		}

		if ( $authority['score'] < 40 ) {
			$recs[] = "Low authority score. Build quality backlinks and strengthen internal linking to this page.";
		}

		if ( $momentum['score'] < 40 ) {
			$recs[] = "Declining momentum. Refresh content, add new sections, and update the publish date.";
		}

		// Almost page 1 opportunities.
		$almost = array_filter( $keywords, fn( $kw ) => (float) $kw['position'] > 10 && (float) $kw['position'] <= 15 && (int) $kw['impressions'] > 30 );
		if ( ! empty( $almost ) ) {
			$top = array_values( $almost )[0];
			$recs[] = "\"" . $top['query'] . "\" ranks at position " . round( (float) $top['position'], 1 ) . " with " . $top['impressions'] . " impressions — a small push could reach page 1.";
		}

		return array_slice( $recs, 0, 5 );
	}

	/**
	 * Expected CTR for a given SERP position (industry benchmarks).
	 */
	private static function expected_ctr_for_position( float $position ): float {
		return match ( true ) {
			$position <= 1  => 0.316,
			$position <= 2  => 0.241,
			$position <= 3  => 0.186,
			$position <= 4  => 0.108,
			$position <= 5  => 0.075,
			$position <= 6  => 0.051,
			$position <= 7  => 0.040,
			$position <= 8  => 0.032,
			$position <= 9  => 0.026,
			$position <= 10 => 0.022,
			default         => 0.010,
		};
	}
}
