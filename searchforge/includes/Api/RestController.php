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

		register_rest_route( self::NAMESPACE, '/cannibalization', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_cannibalization' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/clusters', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_clusters' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/content-brief', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_content_brief' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'path' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/content-gaps', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_content_gaps' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/performance', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_performance' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'days' => [
					'default'           => 30,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/quota', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_quota' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/ssl', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_ssl_status' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/audit-log', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_audit_log' ],
			'permission_callback' => [ $this, 'check_admin_permissions' ],
			'args'                => [
				'limit'  => [ 'default' => 50, 'sanitize_callback' => 'absint' ],
				'offset' => [ 'default' => 0, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/trends', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_trends' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'keyword' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/page-detail', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_page_detail' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'path' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/competitors', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_competitors' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/competitors/overlap', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_competitor_overlap' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'limit' => [ 'default' => 50, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/competitors/gaps', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_competitor_gaps' ],
			'permission_callback' => [ $this, 'check_permissions' ],
			'args'                => [
				'limit' => [ 'default' => 50, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/competitors/visibility', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_competitor_visibility' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );
	}

	public function check_permissions( \WP_REST_Request $request = null ): bool {
		if ( ! Settings::is_pro() ) {
			return false;
		}

		// Allow API key auth for external access.
		if ( $request && ApiKeyAuth::validate( $request ) ) {
			return true;
		}

		return current_user_can( 'edit_posts' );
	}

	public function check_admin_permissions( \WP_REST_Request $request = null ): bool {
		if ( $request && ApiKeyAuth::validate( $request ) ) {
			return true;
		}
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

	public function get_cannibalization( \WP_REST_Request $request ): \WP_REST_Response {
		$limit  = min( absint( $request->get_param( 'limit' ) ?: 50 ), 200 );
		$result = \SearchForge\Analysis\Cannibalization::detect( $limit );

		return new \WP_REST_Response( [
			'cannibalization' => $result,
			'total'           => count( $result ),
		] );
	}

	public function get_clusters( \WP_REST_Request $request ): \WP_REST_Response {
		$limit  = min( absint( $request->get_param( 'limit' ) ?: 500 ), 1000 );
		$result = \SearchForge\Analysis\Clustering::cluster_keywords( 0.3, $limit );

		return new \WP_REST_Response( [
			'clusters' => $result,
			'total'    => count( $result ),
		] );
	}

	public function get_content_brief( \WP_REST_Request $request ): \WP_REST_Response {
		$path   = $request->get_param( 'path' );
		$result = \SearchForge\Analysis\ContentBrief::generate( $path );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( [
				'error' => $result->get_error_message(),
			], 400 );
		}

		return new \WP_REST_Response( $result );
	}

	public function get_content_gaps( \WP_REST_Request $request ): \WP_REST_Response {
		$limit   = min( absint( $request->get_param( 'limit' ) ?: 20 ), 100 );
		$enricher = new \SearchForge\Integrations\KeywordPlanner\Enricher();
		$result   = $enricher->get_content_gaps( $limit );

		return new \WP_REST_Response( [
			'gaps'  => $result,
			'total' => count( $result ),
		] );
	}

	public function get_performance( \WP_REST_Request $request ): \WP_REST_Response {
		$days = min( absint( $request->get_param( 'days' ) ?: 30 ), 365 );

		return new \WP_REST_Response( [
			'daily'      => \SearchForge\Monitoring\PerformanceTrend::get_daily_trends( $days ),
			'comparison' => \SearchForge\Monitoring\PerformanceTrend::get_period_comparison( min( $days, 30 ) ),
		] );
	}

	public function get_quota(): \WP_REST_Response {
		return new \WP_REST_Response( \SearchForge\Monitoring\QuotaTracker::get_summary() );
	}

	public function get_ssl_status(): \WP_REST_Response {
		$result = \SearchForge\Monitoring\SslChecker::check();
		return new \WP_REST_Response( $result ?: [ 'status' => 'not_https' ] );
	}

	public function get_audit_log( \WP_REST_Request $request ): \WP_REST_Response {
		$limit  = min( absint( $request->get_param( 'limit' ) ?: 50 ), 200 );
		$offset = absint( $request->get_param( 'offset' ) ?: 0 );

		return new \WP_REST_Response( [
			'entries' => \SearchForge\Monitoring\AuditLog::get_entries( $limit, $offset ),
			'total'   => \SearchForge\Monitoring\AuditLog::get_total(),
		] );
	}

	public function get_trends( \WP_REST_Request $request ): \WP_REST_Response {
		$keyword = $request->get_param( 'keyword' );
		$geo     = sanitize_text_field( $request->get_param( 'geo' ) ?? '' );

		$interest = \SearchForge\Integrations\Trends\Client::get_interest_over_time( $keyword, $geo );
		if ( is_wp_error( $interest ) ) {
			return new \WP_REST_Response( [ 'error' => $interest->get_error_message() ], 400 );
		}

		$related = \SearchForge\Integrations\Trends\Client::get_related_queries( $keyword, $geo );
		$seasonality = \SearchForge\Integrations\Trends\Client::detect_seasonality( $keyword, $geo );

		return new \WP_REST_Response( [
			'interest'    => $interest,
			'related'     => is_wp_error( $related ) ? null : $related,
			'seasonality' => $seasonality,
		] );
	}

	public function get_competitors(): \WP_REST_Response {
		$competitors = \SearchForge\Analysis\Competitors::get_all();

		return new \WP_REST_Response( [
			'competitors' => $competitors,
			'total'       => count( $competitors ),
		] );
	}

	public function get_competitor_overlap( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = min( absint( $request->get_param( 'limit' ) ?: 50 ), 200 );

		return new \WP_REST_Response( [
			'overlap' => \SearchForge\Analysis\Competitors::get_keyword_overlap( $limit ),
		] );
	}

	public function get_competitor_gaps( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = min( absint( $request->get_param( 'limit' ) ?: 50 ), 200 );

		return new \WP_REST_Response( [
			'gaps' => \SearchForge\Analysis\Competitors::get_competitor_only_keywords( $limit ),
		] );
	}

	public function get_competitor_visibility(): \WP_REST_Response {
		return new \WP_REST_Response(
			\SearchForge\Analysis\Competitors::get_visibility_comparison()
		);
	}

	public function get_page_detail( \WP_REST_Request $request ): \WP_REST_Response {
		$path = $request->get_param( 'path' );

		$page_data = \SearchForge\Admin\PageDetail::get_page_data( $path );
		if ( ! $page_data ) {
			return new \WP_REST_Response( [ 'error' => 'No data for this page.' ], 404 );
		}

		$response = [
			'page'         => $page_data,
			'keywords'     => \SearchForge\Admin\PageDetail::get_page_keywords( $path ),
			'devices'      => \SearchForge\Admin\PageDetail::get_device_breakdown( $path ),
			'daily_trend'  => \SearchForge\Admin\PageDetail::get_daily_trend( $path ),
			'position_distribution' => \SearchForge\Admin\PageDetail::get_position_distribution( $path ),
		];

		$bing = \SearchForge\Admin\PageDetail::get_bing_data( $path );
		if ( $bing ) {
			$response['bing'] = $bing;
		}

		$ga4 = \SearchForge\Admin\PageDetail::get_ga4_data( $path );
		if ( $ga4 ) {
			$response['ga4'] = $ga4;
		}

		$score = \SearchForge\Scoring\Score::calculate_page_score( $path );
		if ( $score ) {
			$response['score'] = $score;
		}

		return new \WP_REST_Response( $response );
	}
}
