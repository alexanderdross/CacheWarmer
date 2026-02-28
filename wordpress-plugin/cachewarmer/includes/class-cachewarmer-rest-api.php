<?php
/**
 * REST API endpoints for CacheWarmer.
 *
 * Registers all /wp-json/cachewarmer/v1/ endpoints.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_REST_API {

    private CacheWarmer_Job_Manager $job_manager;
    private CacheWarmer_Database $db;
    private string $namespace = 'cachewarmer/v1';

    public function __construct( CacheWarmer_Job_Manager $job_manager, CacheWarmer_Database $db ) {
        $this->job_manager = $job_manager;
        $this->db          = $db;

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes(): void {
        // POST /warm — Start warming job.
        register_rest_route( $this->namespace, '/warm', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'start_warm' ),
            'permission_callback' => array( $this, 'check_auth' ),
            'args'                => array(
                'sitemapUrl' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => function ( $value ) {
                        return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
                    },
                ),
                'targets' => array(
                    'required' => false,
                    'type'     => 'array',
                    'default'  => array(),
                ),
            ),
        ) );

        // GET /jobs — List jobs.
        register_rest_route( $this->namespace, '/jobs', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'list_jobs' ),
            'permission_callback' => array( $this, 'check_auth' ),
            'args'                => array(
                'limit'  => array( 'default' => 50, 'type' => 'integer' ),
                'offset' => array( 'default' => 0, 'type' => 'integer' ),
            ),
        ) );

        // GET /jobs/{id} — Get job details.
        register_rest_route( $this->namespace, '/jobs/(?P<id>[a-f0-9-]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_job' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // DELETE /jobs/{id} — Delete job.
        register_rest_route( $this->namespace, '/jobs/(?P<id>[a-f0-9-]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_job' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // GET /sitemaps — List registered sitemaps.
        register_rest_route( $this->namespace, '/sitemaps', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'list_sitemaps' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // POST /sitemaps — Register a sitemap.
        register_rest_route( $this->namespace, '/sitemaps', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'register_sitemap' ),
            'permission_callback' => array( $this, 'check_auth' ),
            'args'                => array(
                'url' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => function ( $value ) {
                        return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
                    },
                ),
                'cronExpression' => array(
                    'required' => false,
                    'type'     => 'string',
                    'default'  => null,
                ),
            ),
        ) );

        // DELETE /sitemaps/{id} — Unregister sitemap.
        register_rest_route( $this->namespace, '/sitemaps/(?P<id>[a-f0-9-]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_sitemap' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // GET /status — Health check.
        register_rest_route( $this->namespace, '/status', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_status' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // GET /logs — URL results log.
        register_rest_route( $this->namespace, '/logs', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_logs' ),
            'permission_callback' => array( $this, 'check_auth' ),
            'args'                => array(
                'limit'  => array( 'default' => 100, 'type' => 'integer' ),
                'offset' => array( 'default' => 0, 'type' => 'integer' ),
                'jobId'  => array( 'default' => null, 'type' => 'string' ),
            ),
        ) );
    }

    /**
     * Check authentication.
     * Supports both WP admin capabilities and Bearer token auth.
     */
    public function check_auth( WP_REST_Request $request ): bool {
        // Allow WP admins.
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Check Bearer token.
        $api_key = get_option( 'cachewarmer_api_key', '' );
        if ( empty( $api_key ) ) {
            return false;
        }

        $auth_header = $request->get_header( 'Authorization' );
        if ( $auth_header && preg_match( '/^Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
            return hash_equals( $api_key, $matches[1] );
        }

        return false;
    }

    // ──────────────────────────────────────────────
    // Endpoint handlers
    // ──────────────────────────────────────────────

    public function start_warm( WP_REST_Request $request ): WP_REST_Response {
        $sitemap_url = $request->get_param( 'sitemapUrl' );
        $targets     = $request->get_param( 'targets' ) ?: array();

        $result = $this->job_manager->create_job( $sitemap_url, $targets );

        return new WP_REST_Response( $result, 202 );
    }

    public function list_jobs( WP_REST_Request $request ): WP_REST_Response {
        $limit  = min( (int) $request->get_param( 'limit' ), 100 );
        $offset = max( (int) $request->get_param( 'offset' ), 0 );

        $jobs = $this->db->get_jobs( $limit, $offset );

        // Decode targets JSON for each job.
        $jobs = array_map( function ( $job ) {
            $job->targets = json_decode( $job->targets, true ) ?: array();
            return $job;
        }, $jobs );

        return new WP_REST_Response( $jobs, 200 );
    }

    public function get_job( WP_REST_Request $request ): WP_REST_Response {
        $job_id = $request->get_param( 'id' );
        $data   = $this->job_manager->get_job_with_stats( $job_id );

        if ( ! $data ) {
            return new WP_REST_Response( array( 'error' => 'Job not found' ), 404 );
        }

        $job          = $data['job'];
        $job->targets = json_decode( $job->targets, true ) ?: array();

        $response = array(
            'job'     => $job,
            'stats'   => $data['stats'],
            'results' => $this->db->get_job_results( $job_id ),
        );

        return new WP_REST_Response( $response, 200 );
    }

    public function delete_job( WP_REST_Request $request ): WP_REST_Response {
        $job_id = $request->get_param( 'id' );
        $job    = $this->db->get_job( $job_id );

        if ( ! $job ) {
            return new WP_REST_Response( array( 'error' => 'Job not found' ), 404 );
        }

        $this->db->delete_job( $job_id );

        return new WP_REST_Response( array( 'deleted' => true ), 200 );
    }

    public function list_sitemaps( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( $this->db->get_all_sitemaps(), 200 );
    }

    public function register_sitemap( WP_REST_Request $request ): WP_REST_Response {
        $url    = $request->get_param( 'url' );
        $parsed = wp_parse_url( $url );
        $domain = $parsed['host'] ?? '';

        $data = array(
            'id'              => wp_generate_uuid4(),
            'url'             => $url,
            'domain'          => $domain,
            'cron_expression' => $request->get_param( 'cronExpression' ),
        );

        $this->db->insert_sitemap( $data );

        return new WP_REST_Response( $data, 201 );
    }

    public function delete_sitemap( WP_REST_Request $request ): WP_REST_Response {
        $id      = $request->get_param( 'id' );
        $sitemap = $this->db->get_sitemap( $id );

        if ( ! $sitemap ) {
            return new WP_REST_Response( array( 'error' => 'Sitemap not found' ), 404 );
        }

        $this->db->delete_sitemap( $id );

        return new WP_REST_Response( array( 'deleted' => true ), 200 );
    }

    public function get_status( WP_REST_Request $request ): WP_REST_Response {
        $counts = $this->db->get_job_counts();
        $total  = $this->db->get_total_urls_processed();

        return new WP_REST_Response( array(
            'status'              => 'ok',
            'version'             => CACHEWARMER_VERSION,
            'wordpress'           => get_bloginfo( 'version' ),
            'php'                 => PHP_VERSION,
            'jobs'                => $counts,
            'totalUrlsProcessed'  => $total,
        ), 200 );
    }

    public function get_logs( WP_REST_Request $request ): WP_REST_Response {
        $limit  = min( (int) $request->get_param( 'limit' ), 500 );
        $offset = max( (int) $request->get_param( 'offset' ), 0 );
        $job_id = $request->get_param( 'jobId' );

        $logs = $this->db->get_logs( $limit, $offset, $job_id );

        return new WP_REST_Response( $logs, 200 );
    }
}
