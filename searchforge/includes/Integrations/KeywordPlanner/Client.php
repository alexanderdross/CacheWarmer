<?php

namespace SearchForge\Integrations\KeywordPlanner;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Google Keyword Planner API client via Google Ads API.
 *
 * Requires an active Google Ads account (even with $0 spend) and OAuth 2.0.
 * Uses the KeywordPlanIdeaService for volume data and suggestions.
 */
class Client {

	private const API_VERSION = 'v17';
	private const API_BASE    = 'https://googleads.googleapis.com/' . self::API_VERSION;

	/**
	 * Get keyword ideas (search volume, competition, CPC) for seed keywords.
	 *
	 * @param string[] $seed_keywords  Keywords to get ideas for.
	 * @param string   $language_id    Language ID (1000 = English, 1001 = German).
	 * @param string[] $geo_targets    Geo target IDs (e.g., ['2276'] for Germany).
	 * @return array|\WP_Error
	 */
	public static function get_keyword_ideas( array $seed_keywords, string $language_id = '1000', array $geo_targets = [] ): array|\WP_Error {
		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$customer_id = Settings::get( 'kwp_customer_id', '' );
		if ( empty( $customer_id ) ) {
			return new \WP_Error( 'no_customer', __( 'Google Ads customer ID not configured.', 'searchforge' ) );
		}

		$body = [
			'keywordSeed' => [ 'keywords' => $seed_keywords ],
			'language'    => "languageConstants/{$language_id}",
		];

		if ( ! empty( $geo_targets ) ) {
			$body['geoTargetConstants'] = array_map(
				fn( $id ) => "geoTargetConstants/{$id}",
				$geo_targets
			);
		}

		$url = self::API_BASE . "/customers/{$customer_id}:generateKeywordIdeas";

		$response = wp_remote_post( $url, [
			'headers' => [
				'Authorization'        => 'Bearer ' . $token,
				'Content-Type'         => 'application/json',
				'developer-token'      => Settings::get( 'kwp_developer_token', '' ),
				'login-customer-id'    => Settings::get( 'kwp_login_customer_id', $customer_id ),
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'kwp_api', $result['error']['message'] ?? 'Unknown error' );
		}

		return self::parse_keyword_ideas( $result['results'] ?? [] );
	}

	/**
	 * Get historical search volume for specific keywords.
	 *
	 * @param string[] $keywords
	 * @return array|\WP_Error  [ 'keyword' => [ 'volume' => int, 'competition' => string, 'cpc_low' => float, 'cpc_high' => float ] ]
	 */
	public static function get_search_volumes( array $keywords ): array|\WP_Error {
		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$customer_id = Settings::get( 'kwp_customer_id', '' );
		if ( empty( $customer_id ) ) {
			return new \WP_Error( 'no_customer', __( 'Google Ads customer ID not configured.', 'searchforge' ) );
		}

		$body = [
			'keywords'  => $keywords,
			'language'  => 'languageConstants/' . Settings::get( 'kwp_language_id', '1000' ),
		];

		$geo = Settings::get( 'kwp_geo_target', '' );
		if ( $geo ) {
			$body['geoTargetConstants'] = [ "geoTargetConstants/{$geo}" ];
		}

		$url = self::API_BASE . "/customers/{$customer_id}:generateKeywordHistoricalMetrics";

		$response = wp_remote_post( $url, [
			'headers' => [
				'Authorization'        => 'Bearer ' . $token,
				'Content-Type'         => 'application/json',
				'developer-token'      => Settings::get( 'kwp_developer_token', '' ),
				'login-customer-id'    => Settings::get( 'kwp_login_customer_id', $customer_id ),
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'kwp_api', $result['error']['message'] ?? 'Unknown error' );
		}

		$volumes = [];
		foreach ( $result['results'] ?? [] as $item ) {
			$metrics = $item['keywordMetrics'] ?? [];
			$volumes[ $item['text'] ?? '' ] = [
				'volume'      => (int) ( $metrics['avgMonthlySearches'] ?? 0 ),
				'competition' => $metrics['competition'] ?? 'UNSPECIFIED',
				'cpc_low'     => (float) ( $metrics['lowTopOfPageBidMicros'] ?? 0 ) / 1_000_000,
				'cpc_high'    => (float) ( $metrics['highTopOfPageBidMicros'] ?? 0 ) / 1_000_000,
			];
		}

		return $volumes;
	}

	/**
	 * Parse keyword idea results into a clean array.
	 */
	private static function parse_keyword_ideas( array $results ): array {
		$ideas = [];
		foreach ( $results as $item ) {
			$metrics = $item['keywordIdeaMetrics'] ?? [];
			$ideas[] = [
				'keyword'     => $item['text'] ?? '',
				'volume'      => (int) ( $metrics['avgMonthlySearches'] ?? 0 ),
				'competition' => $metrics['competition'] ?? 'UNSPECIFIED',
				'cpc_low'     => (float) ( $metrics['lowTopOfPageBidMicros'] ?? 0 ) / 1_000_000,
				'cpc_high'    => (float) ( $metrics['highTopOfPageBidMicros'] ?? 0 ) / 1_000_000,
			];
		}

		return $ideas;
	}

	/**
	 * Get access token (shared with GSC OAuth if same Google Cloud project).
	 */
	private static function get_access_token(): string|\WP_Error {
		// Keyword Planner uses the same Google OAuth tokens as GSC.
		return \SearchForge\Integrations\GSC\OAuth::get_access_token();
	}
}
