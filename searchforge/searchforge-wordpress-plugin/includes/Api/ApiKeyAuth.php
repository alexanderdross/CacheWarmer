<?php

namespace SearchForge\Api;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class ApiKeyAuth {

	/**
	 * Generate a new API key.
	 */
	public static function generate_key(): string {
		$key = 'sf_' . bin2hex( random_bytes( 24 ) );
		Settings::update( 'api_key', wp_hash( $key ) );
		return $key;
	}

	/**
	 * Validate an API key from the request.
	 *
	 * Accepts key via:
	 * - Authorization: Bearer sf_xxx header
	 * - X-SearchForge-Key: sf_xxx header
	 */
	public static function validate( \WP_REST_Request $request ): bool {
		$key = self::extract_key( $request );
		if ( ! $key ) {
			return false;
		}

		$stored_hash = Settings::get( 'api_key' );
		if ( empty( $stored_hash ) ) {
			return false;
		}

		return wp_hash( $key ) === $stored_hash;
	}

	/**
	 * Extract the API key from the request.
	 */
	private static function extract_key( \WP_REST_Request $request ): string {
		// Authorization: Bearer sf_xxx
		$auth_header = $request->get_header( 'authorization' );
		if ( $auth_header && preg_match( '/^Bearer\s+(sf_.+)$/i', $auth_header, $m ) ) {
			return $m[1];
		}

		// X-SearchForge-Key: sf_xxx
		$custom_header = $request->get_header( 'x-searchforge-key' );
		if ( $custom_header && str_starts_with( $custom_header, 'sf_' ) ) {
			return $custom_header;
		}

		return '';
	}

	/**
	 * Check if an API key is configured.
	 */
	public static function has_key(): bool {
		return ! empty( Settings::get( 'api_key' ) );
	}

	/**
	 * Revoke the current API key.
	 */
	public static function revoke(): void {
		Settings::update( 'api_key', '' );
	}
}
