<?php
/**
 * Main CacheWarmer plugin class.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-database.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-job-manager.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-sitemap-parser.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-rest-api.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-scheduler.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-license.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-publish-hook.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-sitemap-detector.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-webhooks.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-email.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-cdn-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-facebook-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-linkedin-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-twitter-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-google-indexer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-bing-indexer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-indexnow.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-pinterest-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-cdn-purge-warmer.php';

if ( is_admin() ) {
    require_once CACHEWARMER_PLUGIN_DIR . 'includes/admin/class-cachewarmer-admin.php';
}

class CacheWarmer {

    private static ?CacheWarmer $instance = null;

    private CacheWarmer_Database $database;
    private CacheWarmer_Job_Manager $job_manager;
    private CacheWarmer_REST_API $rest_api;
    private CacheWarmer_Scheduler $scheduler;
    private CacheWarmer_Publish_Hook $publish_hook;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->database     = new CacheWarmer_Database();
        $this->job_manager  = new CacheWarmer_Job_Manager( $this->database );
        $this->rest_api     = new CacheWarmer_REST_API( $this->job_manager, $this->database );
        $this->scheduler    = new CacheWarmer_Scheduler( $this->job_manager, $this->database );
        $this->publish_hook = new CacheWarmer_Publish_Hook( $this->job_manager );

        if ( is_admin() ) {
            new CacheWarmer_Admin( $this->job_manager, $this->database );
        }

        add_action( 'cachewarmer_process_job', array( $this->job_manager, 'process_job' ), 10, 1 );
        add_action( 'cachewarmer_scheduled_warm', array( $this->scheduler, 'run_scheduled_warm' ), 10, 1 );
    }

    public function activate(): void {
        $this->database->create_tables();

        $defaults = self::get_default_options();
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }

        if ( ! wp_next_scheduled( 'cachewarmer_cron_hook' ) ) {
            wp_schedule_event( time(), 'daily', 'cachewarmer_cron_hook' );
        }

        flush_rewrite_rules();
    }

    public function deactivate(): void {
        wp_clear_scheduled_hook( 'cachewarmer_cron_hook' );
        wp_clear_scheduled_hook( 'cachewarmer_process_job' );
        wp_clear_scheduled_hook( 'cachewarmer_scheduled_warm' );
    }

    public static function get_default_options(): array {
        return array(
            'cachewarmer_api_key'                  => '',
            'cachewarmer_cdn_enabled'              => '1',
            'cachewarmer_cdn_concurrency'          => 3,
            'cachewarmer_cdn_timeout'              => 30,
            'cachewarmer_cdn_user_agent'           => 'Mozilla/5.0 (compatible; CacheWarmer/1.0)',
            'cachewarmer_facebook_enabled'         => '0',
            'cachewarmer_facebook_app_id'          => '',
            'cachewarmer_facebook_app_secret'      => '',
            'cachewarmer_facebook_rate_limit'      => 10,
            'cachewarmer_linkedin_enabled'         => '0',
            'cachewarmer_linkedin_session_cookie'  => '',
            'cachewarmer_linkedin_delay'           => 5000,
            'cachewarmer_twitter_enabled'          => '0',
            'cachewarmer_twitter_concurrency'      => 2,
            'cachewarmer_twitter_delay'            => 3000,
            'cachewarmer_google_enabled'           => '0',
            'cachewarmer_google_service_account'   => '',
            'cachewarmer_google_daily_quota'       => 200,
            'cachewarmer_bing_enabled'             => '0',
            'cachewarmer_bing_api_key'             => '',
            'cachewarmer_bing_daily_quota'         => 10000,
            'cachewarmer_indexnow_enabled'         => '0',
            'cachewarmer_indexnow_key'             => '',
            'cachewarmer_indexnow_key_location'    => '',
            'cachewarmer_scheduler_enabled'        => '0',
            'cachewarmer_scheduler_cron'           => 'daily',
            'cachewarmer_log_level'                => 'info',
            'cachewarmer_license_key'              => '',
            'cachewarmer_license_tier'             => 'free',
            'cachewarmer_auto_warm_on_publish'     => '0',
            'cachewarmer_auto_warm_targets'        => array( 'cdn', 'facebook', 'linkedin', 'twitter' ),
            'cachewarmer_exclude_patterns'         => '',
            'cachewarmer_email_notifications'      => '0',
            'cachewarmer_notification_email'       => '',
            'cachewarmer_webhook_url'              => '',
            'cachewarmer_pinterest_enabled'        => '0',
            'cachewarmer_cloudflare_enabled'       => '0',
            'cachewarmer_cloudflare_api_token'     => '',
            'cachewarmer_cloudflare_zone_id'       => '',
            'cachewarmer_imperva_enabled'          => '0',
            'cachewarmer_imperva_api_id'           => '',
            'cachewarmer_imperva_api_key'          => '',
            'cachewarmer_imperva_site_id'          => '',
            'cachewarmer_akamai_enabled'           => '0',
            'cachewarmer_akamai_host'              => '',
            'cachewarmer_akamai_client_token'      => '',
            'cachewarmer_akamai_client_secret'     => '',
            'cachewarmer_akamai_access_token'      => '',
            'cachewarmer_akamai_network'           => 'production',
        );
    }

    public function get_database(): CacheWarmer_Database {
        return $this->database;
    }

    public function get_job_manager(): CacheWarmer_Job_Manager {
        return $this->job_manager;
    }
}
