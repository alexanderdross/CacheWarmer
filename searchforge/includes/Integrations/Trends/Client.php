<?php

namespace SearchForge\Integrations\Trends;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Google Trends client via SerpApi.
 *
 * Uses SerpApi's Google Trends engine to fetch interest over time,
 * related queries, and rising queries for keywords.
 */
class Client {

	private const SERPAPI_BASE = 'https://serpapi.com/search.json';

	/**
	 * Get interest over time for a keyword.
	 *
	 * @return array|\WP_Error  [ 'timeline' => [...], 'averages' => [...] ]
	 */
	public static function get_interest_over_time( string $keyword, string $geo = '', string $timeframe = 'today 12-m' ): array|\WP_Error {
		$params = [
			'engine'    => 'google_trends',
			'q'         => $keyword,
			'data_type' => 'TIMESERIES',
			'date'      => $timeframe,
		];

		if ( $geo ) {
			$params['geo'] = $geo;
		}

		$result = self::api_request( $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$timeline = [];
		foreach ( $result['interest_over_time']['timeline_data'] ?? [] as $point ) {
			$timeline[] = [
				'date'  => $point['date'] ?? '',
				'value' => (int) ( $point['values'][0]['extracted_value'] ?? 0 ),
			];
		}

		return [
			'keyword'  => $keyword,
			'timeline' => $timeline,
			'averages' => $result['interest_over_time']['averages'] ?? [],
		];
	}

	/**
	 * Get related queries for a keyword.
	 *
	 * @return array|\WP_Error  [ 'top' => [...], 'rising' => [...] ]
	 */
	public static function get_related_queries( string $keyword, string $geo = '' ): array|\WP_Error {
		$params = [
			'engine'    => 'google_trends',
			'q'         => $keyword,
			'data_type' => 'RELATED_QUERIES',
		];

		if ( $geo ) {
			$params['geo'] = $geo;
		}

		$result = self::api_request( $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$top    = [];
		$rising = [];

		foreach ( $result['related_queries']['top'] ?? [] as $item ) {
			$top[] = [
				'query'           => $item['query'] ?? '',
				'relative_value'  => (int) ( $item['extracted_value'] ?? 0 ),
			];
		}

		foreach ( $result['related_queries']['rising'] ?? [] as $item ) {
			$rising[] = [
				'query'           => $item['query'] ?? '',
				'relative_value'  => $item['extracted_value'] ?? 'Breakout',
			];
		}

		return [ 'top' => $top, 'rising' => $rising ];
	}

	/**
	 * Detect seasonal patterns for a keyword.
	 *
	 * @return array|null  [ 'peak_months' => [...], 'low_months' => [...], 'seasonal' => bool ]
	 */
	public static function detect_seasonality( string $keyword, string $geo = '' ): ?array {
		$data = self::get_interest_over_time( $keyword, $geo, 'today 5-y' );
		if ( is_wp_error( $data ) || empty( $data['timeline'] ) ) {
			return null;
		}

		// Group by month.
		$monthly = [];
		foreach ( $data['timeline'] as $point ) {
			$date = $point['date'] ?? '';
			// SerpApi returns dates like "Jan 1 – Jan 7, 2025".
			$parsed = strtotime( explode( '–', $date )[0] ?? $date );
			if ( ! $parsed ) {
				continue;
			}
			$month = (int) gmdate( 'n', $parsed );
			$monthly[ $month ][] = $point['value'];
		}

		if ( count( $monthly ) < 6 ) {
			return null;
		}

		// Average per month.
		$averages = [];
		foreach ( $monthly as $month => $values ) {
			$averages[ $month ] = array_sum( $values ) / count( $values );
		}

		$overall_avg = array_sum( $averages ) / count( $averages );
		$std_dev     = sqrt( array_sum( array_map(
			fn( $v ) => pow( $v - $overall_avg, 2 ),
			$averages
		) ) / count( $averages ) );

		// Seasonal if std dev > 20% of mean.
		$is_seasonal = $overall_avg > 0 && ( $std_dev / $overall_avg ) > 0.2;

		// Find peaks and lows.
		arsort( $averages );
		$peak_months = array_slice( array_keys( $averages ), 0, 3, true );

		asort( $averages );
		$low_months = array_slice( array_keys( $averages ), 0, 3, true );

		$month_names = [ 1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

		return [
			'seasonal'    => $is_seasonal,
			'peak_months' => array_map( fn( $m ) => $month_names[ $m ] ?? $m, $peak_months ),
			'low_months'  => array_map( fn( $m ) => $month_names[ $m ] ?? $m, $low_months ),
			'monthly_avg' => $averages,
		];
	}

	/**
	 * Make a request to SerpApi.
	 */
	private static function api_request( array $params ): array|\WP_Error {
		$api_key = Settings::get( 'serpapi_key', '' );
		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'SerpApi key not configured.', 'searchforge' ) );
		}

		$params['api_key'] = $api_key;

		$url = self::SERPAPI_BASE . '?' . http_build_query( $params );

		$response = wp_remote_get( $url, [ 'timeout' => 30 ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new \WP_Error( 'serpapi', $body['error'] );
		}

		return $body;
	}
}
