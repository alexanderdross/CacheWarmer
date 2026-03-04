<?php

namespace SearchForge\Integrations\Bing;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Client {

	private const API_BASE = 'https://ssl.bing.com/webmaster/api.svc/json';

	/**
	 * Get the API key from settings.
	 */
	private static function get_api_key(): string {
		return Settings::get( 'bing_api_key', '' );
	}

	/**
	 * Get the configured site URL.
	 */
	private static function get_site_url(): string {
		return Settings::get( 'bing_site_url', '' );
	}

	/**
	 * Verify the site is registered in Bing Webmaster Tools.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_sites(): array|\WP_Error {
		return self::api_get( '/GetUserSites' );
	}

	/**
	 * Get page-level traffic data (query stats grouped by page).
	 *
	 * @return array|\WP_Error
	 */
	public static function get_page_stats( string $site_url = '' ): array|\WP_Error {
		if ( ! $site_url ) {
			$site_url = self::get_site_url();
		}

		$result = self::api_get( '/GetPageStats', [ 'siteUrl' => $site_url ] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result;
	}

	/**
	 * Get query-level traffic data.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_query_stats( string $site_url = '' ): array|\WP_Error {
		if ( ! $site_url ) {
			$site_url = self::get_site_url();
		}

		$result = self::api_get( '/GetQueryStats', [ 'siteUrl' => $site_url ] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result;
	}

	/**
	 * Get page traffic data for a specific page.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_page_query_stats( string $page_url, string $site_url = '' ): array|\WP_Error {
		if ( ! $site_url ) {
			$site_url = self::get_site_url();
		}

		return self::api_get( '/GetPageQueryStats', [
			'siteUrl' => $site_url,
			'page'    => $page_url,
		] );
	}

	/**
	 * Get ranking data for keywords.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_rank_and_traffic_stats( string $site_url = '' ): array|\WP_Error {
		if ( ! $site_url ) {
			$site_url = self::get_site_url();
		}

		return self::api_get( '/GetRankAndTrafficStats', [ 'siteUrl' => $site_url ] );
	}

	/**
	 * Make a GET request to the Bing Webmaster API.
	 *
	 * @return array|\WP_Error
	 */
	private static function api_get( string $endpoint, array $params = [] ): array|\WP_Error {
		$api_key = self::get_api_key();
		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'Bing API key not configured.', 'searchforge' ) );
		}

		$params['apikey'] = $api_key;
		$url = self::API_BASE . $endpoint . '?' . http_build_query( $params );

		$response = wp_remote_get( $url, [
			'timeout' => 30,
			'headers' => [ 'Content-Type' => 'application/json' ],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$message = $body['Message'] ?? $body['ErrorMessage'] ?? "HTTP {$code}";
			return new \WP_Error( 'bing_api', $message );
		}

		// Bing API wraps results in a 'd' key.
		return $body['d'] ?? $body;
	}
}
