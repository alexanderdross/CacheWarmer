<?php

namespace SearchForge\Analysis;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * AI Content Brief generator.
 *
 * Generates actionable content briefs using either:
 * 1. Built-in heuristic engine (no API key needed)
 * 2. OpenAI / Anthropic API (user provides own key)
 */
class ContentBrief {

	/**
	 * Generate a content brief for a page.
	 *
	 * @return array|\WP_Error  [ 'brief' => string, 'method' => 'heuristic'|'ai' ]
	 */
	public static function generate( string $page_path ): array|\WP_Error {
		if ( ! Settings::is_pro() ) {
			return new \WP_Error( 'not_pro', __( 'Content briefs require a Pro license.', 'searchforge' ) );
		}

		$context = self::gather_context( $page_path );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		// Try AI generation if API key is available.
		$ai_key     = Settings::get( 'ai_api_key', '' );
		$ai_provider = Settings::get( 'ai_provider', 'openai' );

		if ( ! empty( $ai_key ) ) {
			$result = self::generate_ai_brief( $context, $ai_key, $ai_provider );
			if ( ! is_wp_error( $result ) ) {
				return [ 'brief' => $result, 'method' => 'ai' ];
			}
			// Fall back to heuristic on AI failure.
		}

		return [
			'brief'  => self::generate_heuristic_brief( $context ),
			'method' => 'heuristic',
		];
	}

	/**
	 * Gather all available data for a page to build context.
	 */
	private static function gather_context( string $page_path ): array|\WP_Error {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return new \WP_Error( 'no_data', __( 'No data found for this page.', 'searchforge' ) );
		}

		$page_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc' AND device = 'all'",
			$page_path,
			$latest_date
		), ARRAY_A );

		$keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT query, clicks, impressions, ctr, position, search_volume, competition
			FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc'
			ORDER BY clicks DESC
			LIMIT 30",
			$page_path,
			$latest_date
		), ARRAY_A );

		$trend = \SearchForge\Trends\Engine::get_page_trend( $page_path );
		$score = \SearchForge\Scoring\Score::calculate_page_score( $page_path );

		// GA4 data if available.
		$ga4 = null;
		if ( Settings::get( 'ga4_property_id' ) ) {
			$ga4 = \SearchForge\Integrations\GA4\Syncer::get_page_behavior( $page_path );
		}

		// Cannibalization check — batch query instead of per-keyword.
		$cannibalizing = [];
		if ( ! empty( $keywords ) ) {
			$query_list    = array_column( $keywords, 'query' );
			$placeholders  = implode( ',', array_fill( 0, count( $query_list ), '%s' ) );
			$prepare_args  = array_merge( $query_list, [ $latest_date ] );

			$cannibalizing = $wpdb->get_col( $wpdb->prepare(
				"SELECT query
				FROM {$wpdb->prefix}sf_keywords
				WHERE query IN ({$placeholders}) AND source = 'gsc' AND snapshot_date = %s
				GROUP BY query
				HAVING COUNT(DISTINCT page_path) > 1",
				$prepare_args
			) ) ?: [];
		}

		return [
			'page_path'      => $page_path,
			'page_data'      => $page_data,
			'keywords'       => $keywords,
			'trend'          => $trend,
			'score'          => $score,
			'ga4'            => $ga4,
			'cannibalizing'  => $cannibalizing,
			'latest_date'    => $latest_date,
		];
	}

	/**
	 * Generate a heuristic-based brief (no AI API needed).
	 */
	private static function generate_heuristic_brief( array $ctx ): string {
		$md  = "# Content Brief: {$ctx['page_path']}\n";
		$md .= "**Generated:** " . gmdate( 'Y-m-d H:i' ) . " UTC\n";
		$md .= "**Method:** Heuristic Analysis\n\n---\n\n";

		$page = $ctx['page_data'];
		$keywords = $ctx['keywords'];

		// Summary.
		$md .= "## Current Performance\n";
		$md .= "- **Clicks:** " . number_format( (int) $page['clicks'] ) . "\n";
		$md .= "- **Impressions:** " . number_format( (int) $page['impressions'] ) . "\n";
		$md .= "- **CTR:** " . round( (float) $page['ctr'] * 100, 1 ) . "%\n";
		$md .= "- **Avg Position:** " . round( (float) $page['position'], 1 ) . "\n\n";

		// Score.
		if ( $ctx['score'] ) {
			$md .= "**SearchForge Score:** {$ctx['score']['total']}/100\n\n";
		}

		// Trend.
		if ( ! empty( $ctx['trend']['decay_detected'] ) ) {
			$md .= "> **Warning:** Content decay detected — "
				. abs( $ctx['trend']['decay_percentage'] ) . "% click decline.\n\n";
		}

		// GA4 behavior.
		if ( $ctx['ga4'] ) {
			$ga4 = $ctx['ga4'];
			$md .= "## On-Page Behavior (GA4)\n";
			$md .= "| Metric | Value | Signal |\n";
			$md .= "|--------|-------|--------|\n";

			$bounce = (float) $ga4['bounce_rate'];
			$bounce_signal = $bounce > 60 ? 'High — potential content mismatch' : ( $bounce > 40 ? 'Normal' : 'Good' );
			$md .= "| Bounce Rate | {$bounce}% | {$bounce_signal} |\n";

			$dur = (float) $ga4['avg_session_dur'];
			$dur_signal = $dur < 60 ? 'Low — users leave quickly' : ( $dur < 180 ? 'Average' : 'Good engagement' );
			$md .= "| Avg Session Duration | " . gmdate( 'i:s', (int) $dur ) . " | {$dur_signal} |\n";

			$conv = (int) $ga4['conversions'];
			$md .= "| Conversions | {$conv} | " . ( $conv > 0 ? 'Converting' : 'No conversions tracked' ) . " |\n";
			$md .= "\n";
		}

		// Recommendations.
		$md .= "## Recommendations\n\n";
		$recs = [];

		// CTR optimization.
		$expected_ctr = self::expected_ctr( (float) $page['position'] );
		$actual_ctr   = (float) $page['ctr'];
		if ( $actual_ctr < $expected_ctr * 0.7 ) {
			$recs[] = "**Optimize title tag and meta description.** Your CTR ("
				. round( $actual_ctr * 100, 1 ) . "%) is below expected ("
				. round( $expected_ctr * 100, 1 ) . "%) for position "
				. round( (float) $page['position'], 1 ) . ".";
		}

		// Almost page 1.
		$almost = array_filter( $keywords, fn( $kw ) => (float) $kw['position'] > 10 && (float) $kw['position'] <= 20 );
		if ( ! empty( $almost ) ) {
			$kw_list = implode( ', ', array_map( fn( $kw ) => '"' . $kw['query'] . '"', array_slice( $almost, 0, 3 ) ) );
			$recs[] = "**Push to page 1.** Keywords near page 1: {$kw_list}. "
				. "Add more topical depth, improve internal linking, and strengthen these sections.";
		}

		// Low CTR keywords.
		$low_ctr = array_filter( $keywords, fn( $kw ) => (int) $kw['impressions'] > 50 && (float) $kw['ctr'] < 0.02 );
		if ( ! empty( $low_ctr ) ) {
			$kw_list = implode( ', ', array_map( fn( $kw ) => '"' . $kw['query'] . '"', array_slice( $low_ctr, 0, 3 ) ) );
			$recs[] = "**Improve snippet appeal** for high-impression, low-CTR keywords: {$kw_list}.";
		}

		// Cannibalization.
		if ( ! empty( $ctx['cannibalizing'] ) ) {
			$kw_list = implode( ', ', array_map( fn( $q ) => '"' . $q . '"', array_slice( $ctx['cannibalizing'], 0, 3 ) ) );
			$recs[] = "**Resolve keyword cannibalization.** Multiple pages compete for: {$kw_list}. "
				. "Consider consolidating content or differentiating intent.";
		}

		// Content decay.
		if ( ! empty( $ctx['trend']['decay_detected'] ) ) {
			$recs[] = "**Refresh declining content.** Update stats, examples, and date references. "
				. "Add new sections addressing emerging search intent.";
		}

		// GA4 bounce.
		if ( $ctx['ga4'] && (float) $ctx['ga4']['bounce_rate'] > 60 ) {
			$recs[] = "**Reduce bounce rate.** High bounce ({$ctx['ga4']['bounce_rate']}%) suggests "
				. "content doesn't match search intent. Review top queries and ensure the page "
				. "delivers what users expect above the fold.";
		}

		if ( empty( $recs ) ) {
			$recs[] = "Page is performing well. Monitor for changes and continue current strategy.";
		}

		foreach ( $recs as $i => $rec ) {
			$md .= ( $i + 1 ) . ". {$rec}\n";
		}

		// Target keywords.
		$md .= "\n## Target Keywords\n";
		$md .= "| Keyword | Position | Clicks | Volume | Action |\n";
		$md .= "|---------|----------|--------|--------|--------|\n";

		foreach ( array_slice( $keywords, 0, 15 ) as $kw ) {
			$pos    = round( (float) $kw['position'], 1 );
			$vol    = $kw['search_volume'] ? number_format( (int) $kw['search_volume'] ) : '—';
			$action = self::keyword_action( (float) $kw['position'], (float) $kw['ctr'], (int) $kw['impressions'] );

			$md .= sprintf(
				"| %s | %s | %s | %s | %s |\n",
				$kw['query'],
				$pos,
				number_format( (int) $kw['clicks'] ),
				$vol,
				$action
			);
		}

		$md .= "\n";

		return $md;
	}

	/**
	 * Generate an AI-powered brief via OpenAI or Anthropic.
	 */
	private static function generate_ai_brief( array $ctx, string $api_key, string $provider ): string|\WP_Error {
		$prompt = self::build_ai_prompt( $ctx );

		if ( $provider === 'anthropic' ) {
			return self::call_anthropic( $prompt, $api_key );
		}

		return self::call_openai( $prompt, $api_key );
	}

	/**
	 * Build the prompt for AI brief generation.
	 */
	private static function build_ai_prompt( array $ctx ): string {
		$page = $ctx['page_data'];
		$prompt  = "You are an SEO content strategist. Generate a detailed, actionable content brief for the following page.\n\n";
		$prompt .= "Page: {$ctx['page_path']}\n";
		$prompt .= "Clicks: {$page['clicks']}, Impressions: {$page['impressions']}, ";
		$prompt .= "CTR: " . round( (float) $page['ctr'] * 100, 1 ) . "%, ";
		$prompt .= "Avg Position: " . round( (float) $page['position'], 1 ) . "\n\n";

		$prompt .= "Top keywords:\n";
		foreach ( array_slice( $ctx['keywords'], 0, 15 ) as $kw ) {
			$prompt .= "- \"{$kw['query']}\" — pos {$kw['position']}, {$kw['clicks']} clicks, {$kw['impressions']} impr\n";
		}

		if ( $ctx['score'] ) {
			$prompt .= "\nSearchForge Score: {$ctx['score']['total']}/100\n";
		}

		if ( ! empty( $ctx['cannibalizing'] ) ) {
			$prompt .= "\nCannibalization detected for: " . implode( ', ', $ctx['cannibalizing'] ) . "\n";
		}

		if ( ! empty( $ctx['trend']['decay_detected'] ) ) {
			$prompt .= "\nContent decay detected: " . abs( $ctx['trend']['decay_percentage'] ) . "% click decline\n";
		}

		if ( $ctx['ga4'] ) {
			$prompt .= "\nGA4: Bounce {$ctx['ga4']['bounce_rate']}%, Avg duration {$ctx['ga4']['avg_session_dur']}s, ";
			$prompt .= "Conversions: {$ctx['ga4']['conversions']}\n";
		}

		$prompt .= "\nGenerate a markdown content brief with:\n";
		$prompt .= "1. Executive summary of current performance\n";
		$prompt .= "2. Content recommendations (what to add, change, remove)\n";
		$prompt .= "3. Title tag and meta description suggestions (2-3 variants)\n";
		$prompt .= "4. Internal linking opportunities\n";
		$prompt .= "5. Priority actions ranked by expected impact\n";

		return $prompt;
	}

	/**
	 * Call OpenAI API.
	 */
	private static function call_openai( string $prompt, string $api_key ): string|\WP_Error {
		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
			'timeout' => 60,
			'headers' => [
				'Authorization' => "Bearer {$api_key}",
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'    => 'gpt-4o-mini',
				'messages' => [
					[ 'role' => 'user', 'content' => $prompt ],
				],
				'max_tokens'  => 2000,
				'temperature' => 0.3,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['error'] ) ) {
			return new \WP_Error( 'openai', $body['error']['message'] ?? 'OpenAI API error' );
		}

		return $body['choices'][0]['message']['content'] ?? '';
	}

	/**
	 * Call Anthropic API.
	 */
	private static function call_anthropic( string $prompt, string $api_key ): string|\WP_Error {
		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
			'timeout' => 60,
			'headers' => [
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'      => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'      => 'claude-sonnet-4-5-20250514',
				'max_tokens' => 2000,
				'messages'   => [
					[ 'role' => 'user', 'content' => $prompt ],
				],
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['error'] ) ) {
			return new \WP_Error( 'anthropic', $body['error']['message'] ?? 'Anthropic API error' );
		}

		return $body['content'][0]['text'] ?? '';
	}

	/**
	 * Expected CTR for a given position (industry benchmarks).
	 */
	private static function expected_ctr( float $position ): float {
		$benchmarks = [
			1 => 0.316, 2 => 0.241, 3 => 0.186, 4 => 0.133, 5 => 0.095,
			6 => 0.063, 7 => 0.046, 8 => 0.033, 9 => 0.028, 10 => 0.022,
		];

		$rounded = max( 1, min( 10, (int) round( $position ) ) );
		return $benchmarks[ $rounded ] ?? 0.01;
	}

	/**
	 * Suggest action for a keyword based on metrics.
	 */
	private static function keyword_action( float $position, float $ctr, int $impressions ): string {
		if ( $position <= 3 ) {
			return 'Defend';
		}
		if ( $position <= 10 && $ctr < self::expected_ctr( $position ) * 0.7 ) {
			return 'Optimize CTR';
		}
		if ( $position > 10 && $position <= 20 && $impressions > 20 ) {
			return 'Push to page 1';
		}
		if ( $position <= 10 ) {
			return 'Maintain';
		}
		return 'Monitor';
	}
}
