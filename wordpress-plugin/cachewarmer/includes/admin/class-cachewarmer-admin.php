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
        add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );

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
        add_action( 'wp_ajax_cachewarmer_export_failed', array( $this, 'ajax_export_failed' ) );
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
            'cachewarmer_pinterest_enabled',
            'cachewarmer_cloudflare_enabled',
            'cachewarmer_cloudflare_api_token',
            'cachewarmer_cloudflare_zone_id',
            'cachewarmer_imperva_enabled',
            'cachewarmer_imperva_api_id',
            'cachewarmer_imperva_api_key',
            'cachewarmer_imperva_site_id',
            'cachewarmer_akamai_enabled',
            'cachewarmer_akamai_host',
            'cachewarmer_akamai_client_token',
            'cachewarmer_akamai_client_secret',
            'cachewarmer_akamai_access_token',
            'cachewarmer_akamai_network',
            'cachewarmer_scheduler_enabled',
            'cachewarmer_scheduler_cron',
            'cachewarmer_log_level',
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

        // License key needs a dedicated callback that triggers activation.
        register_setting( 'cachewarmer_settings', 'cachewarmer_license_key', array(
            'sanitize_callback' => array( $this, 'sanitize_license_key' ),
        ) );
    }

    public function sanitize_setting( $value ) {
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        }
        return $value;
    }

    /**
     * Sanitize the license key and set tier/expiry options.
     *
     * We call validate_key() directly instead of activate() because
     * activate() calls update_option('cachewarmer_license_key') which
     * would re-trigger this sanitize callback, causing infinite recursion.
     *
     * @param mixed $value The submitted license key.
     * @return string The sanitized license key.
     */
    public function sanitize_license_key( $value ) {
        $key = sanitize_text_field( (string) $value );

        $parsed = CacheWarmer_License::validate_key( $key );

        if ( false === $parsed ) {
            update_option( 'cachewarmer_license_tier', 'free' );
            delete_option( 'cachewarmer_license_activated_at' );
            delete_option( 'cachewarmer_license_expires_at' );
        } else {
            update_option( 'cachewarmer_license_tier', $parsed['tier'] );
            update_option( 'cachewarmer_license_activated_at', time() );
            $expires_at = $parsed['duration_days'] > 0
                ? time() + ( $parsed['duration_days'] * DAY_IN_SECONDS )
                : 0;
            update_option( 'cachewarmer_license_expires_at', $expires_at );
        }

        return $key;
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

    /**
     * Validate that a URL is safe to request (SSRF protection).
     *
     * Ensures the URL uses HTTPS and does not resolve to a private/internal IP.
     *
     * @param string $url The URL to validate.
     * @return true|\WP_Error True if safe, WP_Error otherwise.
     */
    private function validate_safe_url( string $url ) {
        $parsed = wp_parse_url( $url );

        // Require HTTPS scheme.
        if ( empty( $parsed['scheme'] ) || 'https' !== strtolower( $parsed['scheme'] ) ) {
            return new \WP_Error( 'invalid_scheme', 'Only HTTPS URLs are allowed.' );
        }

        $host = $parsed['host'] ?? '';
        if ( empty( $host ) ) {
            return new \WP_Error( 'missing_host', 'URL must contain a valid host.' );
        }

        // Resolve the hostname to an IP address.
        $ip = gethostbyname( $host );

        // gethostbyname returns the hostname on failure.
        if ( $ip === $host ) {
            return new \WP_Error( 'dns_failure', 'Could not resolve hostname.' );
        }

        // Check for private/reserved IP ranges.
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
            return new \WP_Error( 'private_ip', 'URLs pointing to private or reserved IP addresses are not allowed.' );
        }

        return true;
    }

    public function ajax_start_warm(): void {
        $this->verify_ajax();

        $sitemap_url = isset( $_POST['sitemapUrl'] ) ? esc_url_raw( wp_unslash( $_POST['sitemapUrl'] ) ) : '';
        $targets     = isset( $_POST['targets'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['targets'] ) ) : array();

        if ( empty( $sitemap_url ) || ! filter_var( $sitemap_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => 'Invalid sitemap URL' ) );
        }

        $safe = $this->validate_safe_url( $sitemap_url );
        if ( is_wp_error( $safe ) ) {
            wp_send_json_error( array( 'message' => $safe->get_error_message() ) );
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

        $safe = $this->validate_safe_url( $url );
        if ( is_wp_error( $safe ) ) {
            wp_send_json_error( array( 'message' => $safe->get_error_message() ) );
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

            $safe = $this->validate_safe_url( $url );
            if ( is_wp_error( $safe ) ) {
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

    /**
     * AJAX: Export failed/skipped URLs as CSV.
     */
    public function ajax_export_failed(): void {
        check_ajax_referer( 'cachewarmer_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

        if ( ! CacheWarmer_License::can( 'failed_export' ) ) {
            wp_send_json_error( 'This feature requires a Premium or Enterprise license.', 403 );
        }

        $job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
        if ( empty( $job_id ) || ! preg_match( '/^[0-9a-f\-]{36}$/i', $job_id ) ) {
            wp_send_json_error( 'Invalid job ID' );
        }

        $db = new CacheWarmer_Database();
        $results = $db->get_failed_skipped_results( $job_id );

        $csv = "url,target,status,http_status,duration_ms,error,created_at\n";
        foreach ( $results as $r ) {
            $url    = str_replace( '"', '""', $r['url'] ?? '' );
            $target = $r['target'] ?? '';
            $status = $r['status'] ?? '';
            $http   = $r['http_status'] ?? '';
            $dur    = $r['duration_ms'] ?? '';
            $error  = str_replace( '"', '""', $r['error'] ?? '' );
            $date   = $r['created_at'] ?? '';
            $csv   .= "\"{$url}\",\"{$target}\",\"{$status}\",{$http},{$dur},\"{$error}\",\"{$date}\"\n";
        }

        wp_send_json_success( array(
            'csv'      => $csv,
            'filename' => "cachewarmer-failed-{$job_id}.csv",
            'count'    => count( $results ),
        ) );
    }

    /**
     * Register the WordPress Dashboard widget.
     */
    public function register_dashboard_widget(): void {
        wp_add_dashboard_widget(
            'cachewarmer_overview',
            __( 'CacheWarmer', 'cachewarmer' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    /**
     * Render the WordPress Dashboard widget with quick stats and links.
     */
    public function render_dashboard_widget(): void {
        $sitemaps      = $this->db->get_all_sitemaps();
        $sitemap_count = count( $sitemaps );
        $recent_jobs  = $this->db->get_jobs( 5 );
        $running      = 0;
        $completed    = 0;
        $failed       = 0;
        $last_run     = null;

        foreach ( $recent_jobs as $job ) {
            if ( 'running' === $job->status || 'queued' === $job->status ) {
                ++$running;
            } elseif ( 'completed' === $job->status ) {
                ++$completed;
            } elseif ( 'failed' === $job->status ) {
                ++$failed;
            }
            if ( ! empty( $job->completed_at ) && null === $last_run ) {
                $last_run = $job->completed_at;
            }
        }

        $tier = get_option( 'cachewarmer_license_tier', 'free' );

        $services = array();
        if ( get_option( 'cachewarmer_cdn_enabled' ) ) {
            $services[] = 'CDN';
        }
        if ( get_option( 'cachewarmer_facebook_enabled' ) ) {
            $services[] = 'Facebook';
        }
        if ( get_option( 'cachewarmer_linkedin_enabled' ) ) {
            $services[] = 'LinkedIn';
        }
        if ( get_option( 'cachewarmer_twitter_enabled' ) ) {
            $services[] = 'Twitter/X';
        }
        if ( get_option( 'cachewarmer_indexnow_enabled' ) ) {
            $services[] = 'IndexNow';
        }
        if ( get_option( 'cachewarmer_google_enabled' ) ) {
            $services[] = 'Google';
        }
        if ( get_option( 'cachewarmer_bing_enabled' ) ) {
            $services[] = 'Bing';
        }
        ?>
        <style>
            .cw-widget-kpis { display: flex; gap: 10px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e4e7; }
            .cw-widget-kpi { flex: 1; text-align: center; padding: 8px 4px; background: #f6f7f7; border-radius: 4px; }
            .cw-widget-kpi .cw-wk-value { font-size: 22px; font-weight: 700; line-height: 1.2; color: #1d2327; }
            .cw-widget-kpi .cw-wk-label { font-size: 11px; color: #646970; }
            .cw-widget-kpi.cw-wk-warn .cw-wk-value { color: #dba617; }
            .cw-widget-kpi.cw-wk-success .cw-wk-value { color: #00a32a; }
            .cw-widget-kpi.cw-wk-danger .cw-wk-value { color: #d63638; }
            .cw-widget-services { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 12px; }
            .cw-widget-services .cw-svc-tag { display: inline-block; padding: 2px 8px; background: #e7f5e7; color: #00450c; border-radius: 3px; font-size: 11px; font-weight: 600; }
            .cw-widget-services .cw-svc-tag.cw-svc-off { background: #f0f0f1; color: #a7aaad; }
            .cw-widget-meta { font-size: 12px; color: #646970; margin-bottom: 12px; }
            .cw-widget-meta strong { color: #1d2327; }
            .cw-widget-links { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
            .cw-widget-link { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: #f6f7f7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: background 0.15s; }
            .cw-widget-link:hover { background: #e2e4e7; color: #0073aa; }
            .cw-widget-link .dashicons { font-size: 18px; width: 18px; height: 18px; color: #646970; }
            .cw-widget-link:hover .dashicons { color: #0073aa; }
            .cw-widget-link-text { line-height: 1.3; }
            .cw-widget-link-label { font-weight: 600; font-size: 13px; }
            .cw-widget-link-desc { font-size: 11px; color: #646970; }
        </style>

        <div class="cw-widget-kpis">
            <div class="cw-widget-kpi">
                <div class="cw-wk-value"><?php echo esc_html( $sitemap_count ); ?></div>
                <div class="cw-wk-label"><?php esc_html_e( 'Sitemaps', 'cachewarmer' ); ?></div>
            </div>
            <div class="cw-widget-kpi cw-wk-success">
                <div class="cw-wk-value"><?php echo esc_html( $completed ); ?></div>
                <div class="cw-wk-label"><?php esc_html_e( 'Completed', 'cachewarmer' ); ?></div>
            </div>
            <div class="cw-widget-kpi <?php echo $running > 0 ? 'cw-wk-warn' : ''; ?>">
                <div class="cw-wk-value"><?php echo esc_html( $running ); ?></div>
                <div class="cw-wk-label"><?php esc_html_e( 'Running', 'cachewarmer' ); ?></div>
            </div>
            <div class="cw-widget-kpi <?php echo $failed > 0 ? 'cw-wk-danger' : ''; ?>">
                <div class="cw-wk-value"><?php echo esc_html( $failed ); ?></div>
                <div class="cw-wk-label"><?php esc_html_e( 'Failed', 'cachewarmer' ); ?></div>
            </div>
        </div>

        <div class="cw-widget-meta">
            <strong><?php esc_html_e( 'Tier:', 'cachewarmer' ); ?></strong> <?php echo esc_html( ucfirst( $tier ) ); ?>
            <?php if ( $last_run ) : ?>
                &nbsp;&bull;&nbsp;
                <strong><?php esc_html_e( 'Last run:', 'cachewarmer' ); ?></strong> <?php echo esc_html( human_time_diff( strtotime( $last_run ), time() ) ); ?> <?php esc_html_e( 'ago', 'cachewarmer' ); ?>
            <?php endif; ?>
        </div>

        <div class="cw-widget-services">
            <?php
            $all_services = array( 'CDN', 'Facebook', 'LinkedIn', 'Twitter/X', 'IndexNow', 'Google', 'Bing' );
            foreach ( $all_services as $svc ) :
                $active = in_array( $svc, $services, true );
                ?>
                <span class="cw-svc-tag <?php echo $active ? '' : 'cw-svc-off'; ?>"><?php echo esc_html( $svc ); ?></span>
            <?php endforeach; ?>
        </div>

        <div class="cw-widget-links">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cachewarmer' ) ); ?>" class="cw-widget-link">
                <span class="dashicons dashicons-chart-area"></span>
                <span class="cw-widget-link-text">
                    <span class="cw-widget-link-label"><?php esc_html_e( 'Dashboard', 'cachewarmer' ); ?></span>
                    <br><span class="cw-widget-link-desc"><?php esc_html_e( 'Jobs & Status', 'cachewarmer' ); ?></span>
                </span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cachewarmer-sitemaps' ) ); ?>" class="cw-widget-link">
                <span class="dashicons dashicons-networking"></span>
                <span class="cw-widget-link-text">
                    <span class="cw-widget-link-label"><?php esc_html_e( 'Sitemaps', 'cachewarmer' ); ?></span>
                    <br><span class="cw-widget-link-desc"><?php echo esc_html( sprintf( __( '%d registered', 'cachewarmer' ), $sitemap_count ) ); ?></span>
                </span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cachewarmer-settings' ) ); ?>" class="cw-widget-link">
                <span class="dashicons dashicons-admin-generic"></span>
                <span class="cw-widget-link-text">
                    <span class="cw-widget-link-label"><?php esc_html_e( 'Settings', 'cachewarmer' ); ?></span>
                    <br><span class="cw-widget-link-desc"><?php esc_html_e( 'Configuration', 'cachewarmer' ); ?></span>
                </span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cachewarmer' ) ); ?>" class="cw-widget-link">
                <span class="dashicons dashicons-performance"></span>
                <span class="cw-widget-link-text">
                    <span class="cw-widget-link-label"><?php esc_html_e( 'Warm Now', 'cachewarmer' ); ?></span>
                    <br><span class="cw-widget-link-desc"><?php esc_html_e( 'Start warming', 'cachewarmer' ); ?></span>
                </span>
            </a>
        </div>
        <?php
    }
}
