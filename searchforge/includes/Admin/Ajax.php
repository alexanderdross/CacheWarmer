<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class Ajax {

	public function __construct() {
		add_action( 'wp_ajax_searchforge_sync_gsc', [ $this, 'sync_gsc' ] );
		add_action( 'wp_ajax_searchforge_disconnect_gsc', [ $this, 'disconnect_gsc' ] );
		add_action( 'wp_ajax_searchforge_export_brief', [ $this, 'export_brief' ] );
	}

	public function sync_gsc(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		$settings = Settings::get_all();
		if ( empty( $settings['gsc_access_token'] ) ) {
			wp_send_json_error( [ 'message' => __( 'GSC not connected. Please authenticate first.', 'searchforge' ) ] );
		}

		$syncer = new \SearchForge\Integrations\GSC\Syncer();
		$result = $syncer->sync_all();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( $result );
	}

	public function disconnect_gsc(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		Settings::update_many( [
			'gsc_access_token'  => '',
			'gsc_refresh_token' => '',
			'gsc_token_expires' => 0,
			'gsc_property'      => '',
		] );

		wp_send_json_success( [ 'message' => __( 'GSC disconnected.', 'searchforge' ) ] );
	}

	public function export_brief(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		$page_path  = sanitize_text_field( $_POST['page_path'] ?? '' );
		$brief_type = sanitize_text_field( $_POST['brief_type'] ?? 'page' );

		if ( empty( $page_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Page path is required.', 'searchforge' ) ] );
		}

		$exporter = new \SearchForge\Export\MarkdownExporter();
		$markdown = $exporter->generate_page_brief( $page_path );

		if ( is_wp_error( $markdown ) ) {
			wp_send_json_error( [ 'message' => $markdown->get_error_message() ] );
		}

		wp_send_json_success( [
			'markdown' => $markdown,
			'filename' => 'searchforge-' . sanitize_file_name( trim( $page_path, '/' ) ?: 'homepage' ) . '.md',
		] );
	}
}
