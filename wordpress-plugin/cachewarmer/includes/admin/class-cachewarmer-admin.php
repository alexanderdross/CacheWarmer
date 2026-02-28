<?php
/**
 * WordPress Admin pages for CacheWarmer.
 *
 * Registers menu items, settings, and handles admin AJAX actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Admin {

    private CacheWarmer_Job_Manager $job_manager;
    private CacheWarmer_Database $db;

    public function __construct( CacheWarmer_Job_Manager $job_manager, CacheWarmer_Database $db ) {
        $this->job_manager = $job_manager;
        $this->db          = $db;

        add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_cachewarmer_start_warm', array( $this, 'ajax_start_warm' ) );
        add_action( 'wp_ajax_cachewarmer_get_jobs', array( $this, 'ajax_get_jobs' ) );
        add_action( 'wp_ajax_cachewarmer_get_job', array( $this, 'ajax_get_job' ) );
        add_action( 'wp_ajax_cachewarmer_delete_job', array( $this, 'ajax_delete_job' ) );
        add_action( 'wp_ajax_cachewarmer_add_sitemap', array( $this, 'ajax_add_sitemap' ) );
        add_action( 'wp_ajax_cachewarmer_delete_sitemap', array( $this, 'ajax_delete_sitemap' ) );
        add_action( 'wp_ajax_cachewarmer_warm_sitemap', array( $this, 'ajax_warm_sitemap' ) );
        add_action( 'wp_ajax_cachewarmer_get_status', array( $this, 'ajax_get_status' ) );
        add_action( 'wp_ajax_cachewarmer_bulk_add_sitemaps', array( $this, 'ajax_bulk_add_sitemaps' ) );
        add_action( 'wp_ajax_cachewarmer_detect_sitemaps', array( $this, 'ajax_detect_sitemaps' ) );
        add_action( 'wp_ajax_cachewarmer_export_results', array( $this, 'ajax_export_results' ) );
    }

    /**
     * Register admin menu pages.
     */
    public function add_menu_pages(): void {
        add_menu_page(
            __( 'CacheWarmer', 'cachewarmer' ),
            __( 'CacheWarmer', 'cachewarmer' ),
            'manage_options',
            'cachewarmer',
            array( $this, 'render_dashboard' ),
            'dashicons-performance',
            80
        );

        add_submenu_page(
            'cachewarmer',
            __( 'Dashboard', 'cachewarmer' ),
            __( 'Dashboard', 'cachewarmer' ),
            'manage_options',
            'cachewarmer',
            array( $this, 'render_dashboard' )
        );

        add_submenu_page(
            'cachewarmer',
            __( 'Sitemaps', 'cachewarmer' ),
            __( 'Sitemaps', 'cachewarmer' ),
            'manage_options',
            'cachewarmer-sitemaps',
            array( $this, 'render_sitemaps' )
        );

        add_submenu_page(
            'cachewarmer',
            __( 'Settings', 'cachewarmer' ),
            __( 'Settings', 'cachewarmer' ),
            'manage_options',
            'cachewarmer-settings',
            array( $this, 'render_settings' )
        );
    }

    /**
     * Enqueue admin CSS & JS.
     */
    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'cachewarmer' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'cachewarmer-admin',
            CACHEWARMER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CACHEWARMER_VERSION
        );

        wp_enqueue_script(
            'cachewarmer-admin',
            CACHEWARMER_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            CACHEWARMER_VERSION,
            true
        );

        wp_localize_script( 'cachewarmer-admin', 'cachewarmerAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cachewarmer_nonce' ),
            'i18n'    => array(
                'confirmDelete'     => __( 'Are you sure you want to delete this?', 'cachewarmer' ),
                'warmingStarted'    => __( 'Warming job started!', 'cachewarmer' ),
                'error'             => __( 'An error occurred.', 'cachewarmer' ),
                'noUrlsFound'       => __( 'No URLs found in sitemap.', 'cachewarmer' ),
            ),
        ) );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings(): void {
        $settings = array(
            'cachewarmer_api_key',
            'cachewarmer_cdn_enabled',
            'cachewarmer_cdn_concurrency',
            'cachewarmer_cdn_timeout',
            'cachewarmer_cdn_user_agent',
            'cachewarmer_facebook_enabled',
            'cachewarmer_facebook_app_id',
            'cachewarmer_facebook_app_secret',
            'cachewarmer_facebook_rate_limit',
            'cachewarmer_linkedin_enabled',
            'cachewarmer_linkedin_session_cookie',
            'cachewarmer_linkedin_delay',
            'cachewarmer_twitter_enabled',
            'cachewarmer_twitter_concurrency',
            'cachewarmer_twitter_delay',
            'cachewarmer_google_enabled',
            'cachewarmer_google_service_account',
            'cachewarmer_google_daily_quota',
            'cachewarmer_bing_enabled',
            'cachewarmer_bing_api_key',
            'cachewarmer_bing_daily_quota',
            'cachewarmer_indexnow_enabled',
            'cachewarmer_indexnow_key',
            'cachewarmer_indexnow_key_location',
            'cachewarmer_scheduler_enabled',
            'cachewarmer_scheduler_cron',
            'cachewarmer_log_level',
            'cachewarmer_license_key',
            'cachewarmer_license_tier',
            'cachewarmer_auto_warm_on_publish',
            'cachewarmer_auto_warm_targets',
            'cachewarmer_exclude_patterns',
            'cachewarmer_email_notifications',
            'cachewarmer_notification_email',
            'cachewarmer_webhook_url',
        );

        foreach ( $settings as $setting ) {
            register_setting( 'cachewarmer_settings', $setting, array(
                'sanitize_callback' => array( $this, 'sanitize_setting' ),
            ) );
        }
    }

    public function sanitize_setting( $value ) {
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        }
        return $value;
    }

    // ──────────────────────────────────────────────
    // Page renderers
    // ──────────────────────────────────────────────

    public function render_dashboard(): void {
        include CACHEWARMER_PLUGIN_DIR . 'templates/dashboard.php';
    }

    public function render_sitemaps(): void {
        include CACHEWARMER_PLUGIN_DIR . 'templates/sitemaps.php';
    }

    public function render_settings(): void {
        include CACHEWARMER_PLUGIN_DIR . 'templates/settings.php';
    }

    // ──────────────────────────────────────────────
    // AJAX handlers
    // ──────────────────────────────────────────────

    private function verify_ajax(): void {
        check_ajax_referer( 'cachewarmer_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    public function ajax_start_warm(): void {
        $this->verify_ajax();

        $sitemap_url = isset( $_POST['sitemapUrl'] ) ? esc_url_raw( wp_unslash( $_POST['sitemapUrl'] ) ) : '';
        $targets     = isset( $_POST['targets'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['targets'] ) ) : array();

        if ( empty( $sitemap_url ) || ! filter_var( $sitemap_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => 'Invalid sitemap URL' ) );
        }

        $result = $this->job_manager->create_job( $sitemap_url, $targets );
        wp_send_json_success( $result );
    }

    public function ajax_get_jobs(): void {
        $this->verify_ajax();

        $limit  = isset( $_POST['limit'] ) ? min( absint( $_POST['limit'] ), 100 ) : 50;
        $offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

        $jobs = $this->db->get_jobs( $limit, $offset );
        $jobs = array_map( function ( $job ) {
            $job->targets = json_decode( $job->targets, true ) ?: array();
            return $job;
        }, $jobs );

        wp_send_json_success( $jobs );
    }

    public function ajax_get_job(): void {
        $this->verify_ajax();

        $job_id = isset( $_POST['jobId'] ) ? sanitize_text_field( wp_unslash( $_POST['jobId'] ) ) : '';
        $data   = $this->job_manager->get_job_with_stats( $job_id );

        if ( ! $data ) {
            wp_send_json_error( array( 'message' => 'Job not found' ), 404 );
        }

        $data['job']->targets = json_decode( $data['job']->targets, true ) ?: array();
        $data['results']      = $this->db->get_job_results( $job_id );

        wp_send_json_success( $data );
    }

    public function ajax_delete_job(): void {
        $this->verify_ajax();

        $job_id = isset( $_POST['jobId'] ) ? sanitize_text_field( wp_unslash( $_POST['jobId'] ) ) : '';
        $this->db->delete_job( $job_id );

        wp_send_json_success( array( 'deleted' => true ) );
    }

    public function ajax_add_sitemap(): void {
        $this->verify_ajax();

        $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => 'Invalid URL' ) );
        }

        $parsed = wp_parse_url( $url );
        $data   = array(
            'id'              => wp_generate_uuid4(),
            'url'             => $url,
            'domain'          => $parsed['host'] ?? '',
            'cron_expression' => isset( $_POST['cronExpression'] ) ? sanitize_text_field( wp_unslash( $_POST['cronExpression'] ) ) : null,
        );

        $this->db->insert_sitemap( $data );
        wp_send_json_success( $data );
    }

    public function ajax_delete_sitemap(): void {
        $this->verify_ajax();

        $id = isset( $_POST['sitemapId'] ) ? sanitize_text_field( wp_unslash( $_POST['sitemapId'] ) ) : '';
        $this->db->delete_sitemap( $id );

        wp_send_json_success( array( 'deleted' => true ) );
    }

    public function ajax_warm_sitemap(): void {
        $this->verify_ajax();

        $id      = isset( $_POST['sitemapId'] ) ? sanitize_text_field( wp_unslash( $_POST['sitemapId'] ) ) : '';
        $sitemap = $this->db->get_sitemap( $id );

        if ( ! $sitemap ) {
            wp_send_json_error( array( 'message' => 'Sitemap not found' ) );
        }

        $targets = array( 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow' );
        $result  = $this->job_manager->create_job( $sitemap->url, $targets, $id );

        wp_send_json_success( $result );
    }

    public function ajax_get_status(): void {
        $this->verify_ajax();

        $counts = $this->db->get_job_counts();
        $total  = $this->db->get_total_urls_processed();

        wp_send_json_success( array(
            'version'            => CACHEWARMER_VERSION,
            'jobs'               => $counts,
            'totalUrlsProcessed' => $total,
        ) );
    }

    /**
     * AJAX: Bulk add sitemaps.
     */
    public function ajax_bulk_add_sitemaps(): void {
        $this->verify_ajax();

        $urls_raw = isset( $_POST['urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['urls'] ) ) : '';
        $lines    = array_filter( array_map( 'trim', explode( "\n", $urls_raw ) ) );
        $added    = array();
        $errors   = array();

        foreach ( $lines as $url ) {
            $url = esc_url_raw( $url );
            if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
                $errors[] = $url;
                continue;
            }

            $parsed = wp_parse_url( $url );
            $data   = array(
                'id'              => wp_generate_uuid4(),
                'url'             => $url,
                'domain'          => $parsed['host'] ?? '',
                'cron_expression' => null,
            );

            $this->db->insert_sitemap( $data );
            $added[] = $data;
        }

        wp_send_json_success( array( 'added' => $added, 'errors' => $errors ) );
    }

    /**
     * AJAX: Auto-detect local sitemaps.
     */
    public function ajax_detect_sitemaps(): void {
        $this->verify_ajax();

        $found = CacheWarmer_Sitemap_Detector::detect();
        wp_send_json_success( array( 'sitemaps' => $found ) );
    }

    /**
     * AJAX: Export job results as CSV or JSON.
     */
    public function ajax_export_results(): void {
        $this->verify_ajax();

        if ( ! CacheWarmer_License::is_premium_or_above() ) {
            wp_send_json_error( array( 'message' => 'Export requires Premium license' ), 403 );
        }

        $job_id = isset( $_POST['jobId'] ) ? sanitize_text_field( wp_unslash( $_POST['jobId'] ) ) : '';
        $format = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : 'json';

        $results = $this->db->get_job_results( $job_id );

        if ( 'csv' === $format ) {
            $csv = "url,target,status,http_status,duration_ms,error,created_at\n";
            foreach ( $results as $r ) {
                $csv .= sprintf(
                    '"%s","%s","%s",%d,%d,"%s","%s"' . "\n",
                    $r->url,
                    $r->target,
                    $r->status,
                    $r->http_status ?? 0,
                    $r->duration_ms ?? 0,
                    str_replace( '"', '""', $r->error ?? '' ),
                    $r->created_at
                );
            }
            wp_send_json_success( array(
                'format'   => 'csv',
                'content'  => $csv,
                'filename' => 'cachewarmer-' . $job_id . '.csv',
            ) );
        } else {
            wp_send_json_success( array(
                'format'   => 'json',
                'content'  => $results,
                'filename' => 'cachewarmer-' . $job_id . '.json',
            ) );
        }
    }
}
