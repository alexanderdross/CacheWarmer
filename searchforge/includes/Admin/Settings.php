<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

	private const OPTION_KEY = 'searchforge_settings';

	private const DEFAULTS = [
		'gsc_client_id'     => '',
		'gsc_client_secret' => '',
		'gsc_access_token'  => '',
		'gsc_refresh_token' => '',
		'gsc_token_expires' => 0,
		'gsc_property'      => '',
		'gsc_max_pages'     => 0, // 0 = unlimited (Pro), limited in Free.
		'sync_frequency'    => 'daily',
		'data_retention'    => 30, // days. Free = 30, Pro = 365.
		'llms_txt_enabled'  => true,
		'license_key'       => '',
		'license_tier'      => 'free',
	];

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings(): void {
		register_setting( 'searchforge_settings', self::OPTION_KEY, [
			'sanitize_callback' => [ $this, 'sanitize' ],
			'default'           => self::DEFAULTS,
		] );
	}

	public function sanitize( array $input ): array {
		$current  = self::get_all();
		$sanitized = [];

		$sanitized['gsc_client_id']     = sanitize_text_field( $input['gsc_client_id'] ?? $current['gsc_client_id'] );
		$sanitized['gsc_client_secret'] = sanitize_text_field( $input['gsc_client_secret'] ?? $current['gsc_client_secret'] );
		$sanitized['gsc_property']      = esc_url_raw( $input['gsc_property'] ?? $current['gsc_property'] );
		$sanitized['llms_txt_enabled']  = ! empty( $input['llms_txt_enabled'] );
		$sanitized['license_key']       = sanitize_text_field( $input['license_key'] ?? $current['license_key'] );

		// Preserve tokens — these are set programmatically via OAuth callback.
		$sanitized['gsc_access_token']  = $current['gsc_access_token'];
		$sanitized['gsc_refresh_token'] = $current['gsc_refresh_token'];
		$sanitized['gsc_token_expires'] = $current['gsc_token_expires'];

		$sanitized['gsc_max_pages']   = absint( $input['gsc_max_pages'] ?? $current['gsc_max_pages'] );
		$sanitized['sync_frequency']  = in_array( $input['sync_frequency'] ?? '', [ 'daily', 'twicedaily', 'weekly' ], true )
			? $input['sync_frequency']
			: $current['sync_frequency'];
		$sanitized['data_retention']  = absint( $input['data_retention'] ?? $current['data_retention'] );
		$sanitized['license_tier']    = $current['license_tier']; // Set via license validation only.

		return $sanitized;
	}

	public static function get_all(): array {
		return wp_parse_args( get_option( self::OPTION_KEY, [] ), self::DEFAULTS );
	}

	public static function get( string $key, $default = null ) {
		$settings = self::get_all();
		return $settings[ $key ] ?? $default;
	}

	public static function update( string $key, $value ): bool {
		$settings          = self::get_all();
		$settings[ $key ]  = $value;
		return update_option( self::OPTION_KEY, $settings );
	}

	public static function update_many( array $values ): bool {
		$settings = self::get_all();
		$settings = array_merge( $settings, $values );
		return update_option( self::OPTION_KEY, $settings );
	}

	public static function is_pro(): bool {
		return in_array( self::get( 'license_tier' ), [ 'pro', 'agency', 'enterprise' ], true );
	}

	public static function get_page_limit(): int {
		$tier = self::get( 'license_tier' );
		return match ( $tier ) {
			'pro', 'agency', 'enterprise' => 0, // unlimited
			default                        => 10,
		};
	}

	public static function get_retention_days(): int {
		$tier = self::get( 'license_tier' );
		return match ( $tier ) {
			'enterprise' => 730,
			'agency'     => 730,
			'pro'        => 365,
			default      => 30,
		};
	}
}
