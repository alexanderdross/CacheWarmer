<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class Ajax {

	public function __construct() {
		add_action( 'wp_ajax_searchforge_sync_gsc', [ $this, 'sync_gsc' ] );
		add_action( 'wp_ajax_searchforge_sync_bing', [ $this, 'sync_bing' ] );
		add_action( 'wp_ajax_searchforge_disconnect_gsc', [ $this, 'disconnect_gsc' ] );
		add_action( 'wp_ajax_searchforge_export_brief', [ $this, 'export_brief' ] );
		add_action( 'wp_ajax_searchforge_dismiss_alert', [ $this, 'dismiss_alert' ] );
		add_action( 'wp_ajax_searchforge_generate_content_brief', [ $this, 'generate_content_brief' ] );
		add_action( 'wp_ajax_searchforge_export_data', [ $this, 'export_data' ] );
		add_action( 'wp_ajax_searchforge_discover_sitemaps', [ $this, 'discover_sitemaps' ] );
		add_action( 'wp_ajax_searchforge_scan_broken_links', [ $this, 'scan_broken_links' ] );
		add_action( 'wp_ajax_searchforge_generate_api_key', [ $this, 'generate_api_key' ] );
		add_action( 'wp_ajax_searchforge_revoke_api_key', [ $this, 'revoke_api_key' ] );
		add_action( 'wp_ajax_searchforge_add_competitor', [ $this, 'add_competitor' ] );
		add_action( 'wp_ajax_searchforge_remove_competitor', [ $this, 'remove_competitor' ] );
		add_action( 'wp_ajax_searchforge_sync_competitor', [ $this, 'sync_competitor' ] );
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

	public function sync_bing(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'Bing integration requires a Pro license.', 'searchforge' ) ] );
		}

		$syncer = new \SearchForge\Integrations\Bing\Syncer();
		$result = $syncer->sync_all();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( $result );
	}

	public function dismiss_alert(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		$alert_id = absint( $_POST['alert_id'] ?? 0 );
		if ( ! $alert_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid alert ID.', 'searchforge' ) ] );
		}

		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}sf_alerts", [ 'is_read' => 1 ], [ 'id' => $alert_id ] );

		wp_send_json_success();
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

	public function generate_content_brief(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'Content briefs require a Pro license.', 'searchforge' ) ] );
		}

		$page_path = sanitize_text_field( $_POST['page_path'] ?? '' );
		if ( empty( $page_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Page path is required.', 'searchforge' ) ] );
		}

		$result = \SearchForge\Analysis\ContentBrief::generate( $page_path );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [
			'brief'    => $result['brief'],
			'method'   => $result['method'],
			'filename' => 'content-brief-' . sanitize_file_name( trim( $page_path, '/' ) ?: 'homepage' ) . '.md',
		] );
	}

	public function discover_sitemaps(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		$sitemaps = \SearchForge\Sitemap\Discovery::discover();

		$results = [];
		foreach ( $sitemaps as $url ) {
			$count = \SearchForge\Sitemap\Discovery::count_urls( $url );
			$results[] = [
				'url'       => $url,
				'url_count' => $count,
			];
		}

		wp_send_json_success( [ 'sitemaps' => $results ] );
	}

	public function scan_broken_links(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'Broken link scanning requires a Pro license.', 'searchforge' ) ] );
		}

		$broken = \SearchForge\Monitoring\BrokenLinks::scan( 20 );

		wp_send_json_success( [
			'count'  => count( $broken ),
			'broken' => array_slice( $broken, 0, 50 ),
		] );
	}

	public function export_data(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'Data export requires a Pro license.', 'searchforge' ) ] );
		}

		$type   = sanitize_text_field( $_POST['export_type'] ?? 'pages' );
		$format = sanitize_text_field( $_POST['export_format'] ?? 'csv' );

		$exporter = new \SearchForge\Export\CsvExporter();

		switch ( $type ) {
			case 'keywords':
				$data     = $format === 'json' ? \SearchForge\Export\CsvExporter::export_keywords_json() : \SearchForge\Export\CsvExporter::export_keywords_csv();
				$filename = 'searchforge-keywords.' . $format;
				break;
			case 'alerts':
				$data     = \SearchForge\Export\CsvExporter::export_alerts_csv();
				$filename = 'searchforge-alerts.csv';
				$format   = 'csv';
				break;
			default:
				$data     = $format === 'json' ? \SearchForge\Export\CsvExporter::export_pages_json() : \SearchForge\Export\CsvExporter::export_pages_csv();
				$filename = 'searchforge-pages.' . $format;
				break;
		}

		if ( empty( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'No data to export.', 'searchforge' ) ] );
		}

		$mime = $format === 'json' ? 'application/json' : 'text/csv';

		wp_send_json_success( [
			'data'     => $data,
			'filename' => $filename,
			'mime'     => $mime,
		] );
	}

	public function generate_api_key(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'REST API access requires a Pro license.', 'searchforge' ) ] );
		}

		$key = \SearchForge\Api\ApiKeyAuth::generate_key();

		wp_send_json_success( [ 'key' => $key ] );
	}

	public function revoke_api_key(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		\SearchForge\Api\ApiKeyAuth::revoke();

		wp_send_json_success( [ 'message' => __( 'API key revoked.', 'searchforge' ) ] );
	}

	public function add_competitor(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'Competitor tracking requires a Pro license.', 'searchforge' ) ] );
		}

		$domain = sanitize_text_field( $_POST['domain'] ?? '' );
		$label  = sanitize_text_field( $_POST['label'] ?? '' );

		if ( empty( $domain ) ) {
			wp_send_json_error( [ 'message' => __( 'Domain is required.', 'searchforge' ) ] );
		}

		$result = \SearchForge\Analysis\Competitors::add( $domain, $label );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Could not add competitor. Limit reached or domain already exists.', 'searchforge' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Competitor added.', 'searchforge' ) ] );
	}

	public function remove_competitor(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		$id = absint( $_POST['competitor_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid competitor ID.', 'searchforge' ) ] );
		}

		\SearchForge\Analysis\Competitors::remove( $id );

		wp_send_json_success( [ 'message' => __( 'Competitor removed.', 'searchforge' ) ] );
	}

	public function sync_competitor(): void {
		check_ajax_referer( 'searchforge_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'searchforge' ) ], 403 );
		}

		if ( ! Settings::is_pro() ) {
			wp_send_json_error( [ 'message' => __( 'Competitor tracking requires a Pro license.', 'searchforge' ) ] );
		}

		$id = absint( $_POST['competitor_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid competitor ID.', 'searchforge' ) ] );
		}

		$count = \SearchForge\Analysis\Competitors::sync_from_gsc( $id );

		wp_send_json_success( [
			'message'  => sprintf( __( 'Synced %d keywords.', 'searchforge' ), $count ),
			'keywords' => $count,
		] );
	}
}
