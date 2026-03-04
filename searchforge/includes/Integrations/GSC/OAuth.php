<?php

namespace SearchForge\Integrations\GSC;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class OAuth {

	private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
	private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const SCOPES    = 'https://www.googleapis.com/auth/webmasters.readonly';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'handle_oauth_callback' ] );
	}

	/**
	 * Generate the OAuth authorization URL.
	 */
	public static function get_auth_url(): string {
		$settings = Settings::get_all();
		$redirect = self::get_redirect_uri();

		$params = [
			'client_id'     => $settings['gsc_client_id'],
			'redirect_uri'  => $redirect,
			'response_type' => 'code',
			'scope'         => self::SCOPES,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => wp_create_nonce( 'searchforge_oauth' ),
		];

		return self::AUTH_URL . '?' . http_build_query( $params );
	}

	/**
	 * Get the redirect URI for OAuth callback.
	 */
	public static function get_redirect_uri(): string {
		return admin_url( 'admin.php?page=searchforge-settings' );
	}

	/**
	 * Handle OAuth callback — exchange code for tokens.
	 */
	public function handle_oauth_callback(): void {
		if ( ! isset( $_GET['page'], $_GET['code'] ) || 'searchforge-settings' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$state = sanitize_text_field( $_GET['state'] ?? '' );
		if ( ! wp_verify_nonce( $state, 'searchforge_oauth' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' .
					esc_html__( 'SearchForge: OAuth verification failed. Please try again.', 'searchforge' ) .
					'</p></div>';
			} );
			return;
		}

		$code     = sanitize_text_field( $_GET['code'] );
		$settings = Settings::get_all();

		$response = wp_remote_post( self::TOKEN_URL, [
			'body' => [
				'code'          => $code,
				'client_id'     => $settings['gsc_client_id'],
				'client_secret' => $settings['gsc_client_secret'],
				'redirect_uri'  => self::get_redirect_uri(),
				'grant_type'    => 'authorization_code',
			],
		] );

		if ( is_wp_error( $response ) ) {
			add_action( 'admin_notices', function () use ( $response ) {
				echo '<div class="notice notice-error"><p>' .
					esc_html( sprintf(
						__( 'SearchForge: Token exchange failed: %s', 'searchforge' ),
						$response->get_error_message()
					) ) .
					'</p></div>';
			} );
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			add_action( 'admin_notices', function () use ( $body ) {
				echo '<div class="notice notice-error"><p>' .
					esc_html( sprintf(
						__( 'SearchForge: Google OAuth error: %s', 'searchforge' ),
						$body['error_description'] ?? $body['error']
					) ) .
					'</p></div>';
			} );
			return;
		}

		Settings::update_many( [
			'gsc_access_token'  => $body['access_token'],
			'gsc_refresh_token' => $body['refresh_token'] ?? $settings['gsc_refresh_token'],
			'gsc_token_expires' => time() + ( $body['expires_in'] ?? 3600 ),
		] );

		// Redirect to remove code from URL.
		wp_safe_redirect( admin_url( 'admin.php?page=searchforge-settings&gsc_connected=1' ) );
		exit;
	}

	/**
	 * Get a valid access token, refreshing if needed.
	 */
	public static function get_access_token(): string|\WP_Error {
		$settings = Settings::get_all();

		if ( empty( $settings['gsc_access_token'] ) ) {
			return new \WP_Error( 'no_token', __( 'GSC not connected.', 'searchforge' ) );
		}

		// Token still valid.
		if ( $settings['gsc_token_expires'] > time() + 60 ) {
			return $settings['gsc_access_token'];
		}

		// Refresh.
		if ( empty( $settings['gsc_refresh_token'] ) ) {
			return new \WP_Error( 'no_refresh_token', __( 'No refresh token. Please reconnect GSC.', 'searchforge' ) );
		}

		$response = wp_remote_post( self::TOKEN_URL, [
			'body' => [
				'client_id'     => $settings['gsc_client_id'],
				'client_secret' => $settings['gsc_client_secret'],
				'refresh_token' => $settings['gsc_refresh_token'],
				'grant_type'    => 'refresh_token',
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new \WP_Error( 'refresh_failed', $body['error_description'] ?? $body['error'] );
		}

		$new_token = $body['access_token'];
		$expires   = time() + ( $body['expires_in'] ?? 3600 );

		Settings::update_many( [
			'gsc_access_token'  => $new_token,
			'gsc_token_expires' => $expires,
		] );

		return $new_token;
	}
}
