<?php

namespace SearchForge\Integrations\GSC;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Client {

	private const API_BASE = 'https://www.googleapis.com/webmasters/v3';
	private const SEARCH_ANALYTICS_URL = 'https://searchconsole.googleapis.com/webmasters/v3';

	/**
	 * List verified sites/properties.
	 *
	 * @return array|\WP_Error
	 */
	public static function list_sites(): array|\WP_Error {
		$token = OAuth::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_get( self::API_BASE . '/sites', [
			'headers' => [ 'Authorization' => 'Bearer ' . $token ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new \WP_Error( 'gsc_api', $body['error']['message'] ?? 'Unknown error' );
		}

		return $body['siteEntry'] ?? [];
	}

	/**
	 * Query Search Analytics.
	 *
	 * @param string $property  Site URL (e.g., "https://example.com/")
	 * @param array  $params    Query parameters.
	 * @return array|\WP_Error
	 */
	public static function search_analytics( string $property, array $params ): array|\WP_Error {
		$token = OAuth::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$defaults = [
			'startDate'  => gmdate( 'Y-m-d', strtotime( '-28 days' ) ),
			'endDate'    => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
			'dimensions' => [ 'page' ],
			'rowLimit'   => 1000,
			'startRow'   => 0,
		];

		$body = array_merge( $defaults, $params );

		$url = self::SEARCH_ANALYTICS_URL . '/sites/' . rawurlencode( $property ) . '/searchAnalytics/query';

		$response = wp_remote_post( $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'gsc_api', $result['error']['message'] ?? 'Unknown error' );
		}

		return $result['rows'] ?? [];
	}

	/**
	 * Get page-level data (clicks, impressions, CTR, position).
	 *
	 * @return array|\WP_Error  Array of [ 'page' => ..., 'clicks' => ..., ... ]
	 */
	public static function get_page_data( string $property, string $start_date, string $end_date, int $limit = 1000 ): array|\WP_Error {
		$all_rows  = [];
		$start_row = 0;

		while ( true ) {
			$rows = self::search_analytics( $property, [
				'startDate'  => $start_date,
				'endDate'    => $end_date,
				'dimensions' => [ 'page' ],
				'rowLimit'   => min( $limit - count( $all_rows ), 1000 ),
				'startRow'   => $start_row,
			] );

			if ( is_wp_error( $rows ) ) {
				return $rows;
			}

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$all_rows[] = [
					'page'        => $row['keys'][0],
					'clicks'      => $row['clicks'] ?? 0,
					'impressions' => $row['impressions'] ?? 0,
					'ctr'         => $row['ctr'] ?? 0,
					'position'    => $row['position'] ?? 0,
				];
			}

			$start_row += count( $rows );

			if ( count( $rows ) < 1000 || count( $all_rows ) >= $limit ) {
				break;
			}
		}

		return $all_rows;
	}

	/**
	 * Get keyword data for a specific page or all pages.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_keyword_data( string $property, string $start_date, string $end_date, string $page_url = '', int $limit = 5000 ): array|\WP_Error {
		$all_rows  = [];
		$start_row = 0;

		$params = [
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => [ 'query', 'page' ],
			'rowLimit'   => 1000,
			'startRow'   => 0,
		];

		if ( $page_url ) {
			$params['dimensionFilterGroups'] = [ [
				'filters' => [ [
					'dimension'  => 'page',
					'operator'   => 'equals',
					'expression' => $page_url,
				] ],
			] ];
		}

		while ( true ) {
			$params['startRow'] = $start_row;
			$params['rowLimit'] = min( $limit - count( $all_rows ), 1000 );

			$rows = self::search_analytics( $property, $params );

			if ( is_wp_error( $rows ) ) {
				return $rows;
			}

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$all_rows[] = [
					'query'       => $row['keys'][0],
					'page'        => $row['keys'][1],
					'clicks'      => $row['clicks'] ?? 0,
					'impressions' => $row['impressions'] ?? 0,
					'ctr'         => $row['ctr'] ?? 0,
					'position'    => $row['position'] ?? 0,
				];
			}

			$start_row += count( $rows );

			if ( count( $rows ) < 1000 || count( $all_rows ) >= $limit ) {
				break;
			}
		}

		return $all_rows;
	}
}
