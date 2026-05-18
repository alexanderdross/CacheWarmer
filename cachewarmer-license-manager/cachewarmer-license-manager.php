<?php
/**
 * Plugin Name: CacheWarmer License Manager
 * Plugin URI: https://cachewarmer.drossmedia.de
 * Description: Central license management dashboard for CacheWarmer (WordPress, Drupal, Node.js/Docker). Manages license keys, tracks installations, processes Stripe payments, and provides a REST API for license validation and heartbeat checks.
 * Version: 1.0.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Alexander Dross / Dross:Media
 * Author URI: https://dross.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cwlm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CWLM_VERSION', '1.0.1' );
define( 'CWLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CWLM_PLUGIN_FILE', __FILE__ );
define( 'CWLM_DB_PREFIX', 'cwlm_' );

// Grace period in days after license expiration.
define( 'CWLM_GRACE_PERIOD_DAYS', 14 );

// Latest CacheWarmer plugin/module version (for update checks).
define( 'CWLM_LATEST_PRODUCT_VERSION', '1.1.1' );

// Product URLs.
define( 'CWLM_PRODUCT_URL', 'https://cachewarmer.drossmedia.de' );
define( 'CWLM_PRODUCT_NAME', 'CacheWarmer' );

/**
 * Main plugin class.
 */
final class CacheWarmer_License_Manager {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void {
        // Core
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-activator.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-deactivator.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-database.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-license-manager.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-feature-flags.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-installation-tracker.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-audit-logger.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-jwt-handler.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-geoip.php';
        require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-email.php';

        // REST API
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-rest-controller.php';
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-validate-endpoint.php';
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-activate-endpoint.php';
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-deactivate-endpoint.php';
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-check-endpoint.php';
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-health-endpoint.php';
        require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-stripe-webhook.php';

        // Admin
        if ( is_admin() ) {
            require_once CWLM_PLUGIN_DIR . 'admin/class-cwlm-admin.php';
        }
    }

    private function init_hooks(): void {
        register_activation_hook( CWLM_PLUGIN_FILE, [ 'CWLM_Activator', 'activate' ] );
        register_deactivation_hook( CWLM_PLUGIN_FILE, [ 'CWLM_Deactivator', 'deactivate' ] );

        // REST API routes
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // Admin
        if ( is_admin() ) {
            $admin = new CWLM_Admin();
            $admin->init();
        }

        // Scheduled tasks
        add_action( 'cwlm_check_expired_licenses', [ $this, 'check_expired_licenses' ] );
        add_action( 'cwlm_cleanup_old_data', [ 'CWLM_Database', 'cleanup_old_data' ] );
        add_action( 'cwlm_send_expiry_warnings', [ 'CWLM_Email', 'send_expiry_warnings' ] );

        // Schedule hooks on init
        add_action( 'init', [ $this, 'schedule_hooks' ] );
    }

    /**
     * Register all REST API routes.
     */
    public function register_rest_routes(): void {
        $endpoints = [
            new CWLM_Validate_Endpoint(),
            new CWLM_Activate_Endpoint(),
            new CWLM_Deactivate_Endpoint(),
            new CWLM_Check_Endpoint(),
            new CWLM_Health_Endpoint(),
            new CWLM_Stripe_Webhook(),
        ];

        foreach ( $endpoints as $endpoint ) {
            $endpoint->register_routes();
        }
    }

    /**
     * Schedule recurring tasks.
     */
    public function schedule_hooks(): void {
        if ( ! wp_next_scheduled( 'cwlm_check_expired_licenses' ) ) {
            wp_schedule_event( time(), 'daily', 'cwlm_check_expired_licenses' );
        }
        if ( ! wp_next_scheduled( 'cwlm_cleanup_old_data' ) ) {
            wp_schedule_event( time(), 'daily', 'cwlm_cleanup_old_data' );
        }
        if ( ! wp_next_scheduled( 'cwlm_send_expiry_warnings' ) ) {
            wp_schedule_event( time(), 'daily', 'cwlm_send_expiry_warnings' );
        }
    }

    /**
     * Move active licenses past expiry into grace_period, and grace_period past 14 days into expired.
     */
    public function check_expired_licenses(): void {
        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        // Active -> grace_period (expired but within grace window)
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$prefix}licenses SET status = 'grace_period'
             WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= %s",
            gmdate( 'Y-m-d H:i:s' )
        ) );

        // Grace period -> expired (past grace window)
        $grace_cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . CWLM_GRACE_PERIOD_DAYS . ' days' ) );
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$prefix}licenses SET status = 'expired'
             WHERE status = 'grace_period' AND expires_at IS NOT NULL AND expires_at <= %s",
            $grace_cutoff
        ) );
    }
}

// Initialize.
add_action( 'plugins_loaded', function () {
    CacheWarmer_License_Manager::instance();
} );
