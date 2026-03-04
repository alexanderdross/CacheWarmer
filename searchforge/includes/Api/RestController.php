<?php

namespace SearchForge\Api;

use SearchForge\Admin\Dashboard;
use SearchForge\Admin\Settings;
use SearchForge\Export\MarkdownExporter;

defined( 'ABSPATH' ) || exit;

class RestController {

	private const NAMESPACE = 'searchforge/v1';

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_status' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/pages', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_pages' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/keywords', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_keywords' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/export/page', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'export_page' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'path' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/export/site', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'export_site' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/sync', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'trigger_sync' ],
			'permission_callback' => [ $this, 'check_admin_permissions' ],
		] );
	}

	public function check_permissions(): bool {
		if ( ! Settings::is_pro() ) {
			return false;
		}
		return current_user_can( 'edit_posts' );
	}

	public function check_admin_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_status(): \WP_REST_Response {
		$summary = Dashboard::get_summary();
		$summary['version']      = SEARCHFORGE_VERSION;
		$summary['tier']         = Settings::get( 'license_tier' );
		$summary['gsc_connected'] = ! empty( Settings::get( 'gsc_access_token' ) );

		return new \WP_REST_Response( $summary );
	}

	public function get_pages( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = min( absint( $request->get_param( 'limit' ) ?: 50 ), 500 );
		$pages = Dashboard::get_top_pages( $limit );

		return new \WP_REST_Response( [
			'pages' => $pages,
			'total' => count( $pages ),
		] );
	}

	public function get_keywords( \WP_REST_Request $request ): \WP_REST_Response {
		$limit    = min( absint( $request->get_param( 'limit' ) ?: 50 ), 500 );
		$keywords = Dashboard::get_top_keywords( $limit );

		return new \WP_REST_Response( [
			'keywords' => $keywords,
			'total'    => count( $keywords ),
		] );
	}

	public function export_page( \WP_REST_Request $request ): \WP_REST_Response {
		$path     = $request->get_param( 'path' );
		$exporter = new MarkdownExporter();
		$markdown = $exporter->generate_page_brief( $path );

		if ( is_wp_error( $markdown ) ) {
			return new \WP_REST_Response( [
				'error' => $markdown->get_error_message(),
			], 404 );
		}

		return new \WP_REST_Response( [
			'markdown' => $markdown,
			'path'     => $path,
		] );
	}

	public function export_site(): \WP_REST_Response {
		$exporter = new MarkdownExporter();
		$markdown = $exporter->generate_site_brief();

		if ( is_wp_error( $markdown ) ) {
			return new \WP_REST_Response( [
				'error' => $markdown->get_error_message(),
			], 404 );
		}

		return new \WP_REST_Response( [ 'markdown' => $markdown ] );
	}

	public function trigger_sync(): \WP_REST_Response {
		$syncer = new \SearchForge\Integrations\GSC\Syncer();
		$result = $syncer->sync_all();

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [
				'error' => $result->get_error_message(),
			], 500 );
		}

		return new \WP_REST_Response( $result );
	}
}
