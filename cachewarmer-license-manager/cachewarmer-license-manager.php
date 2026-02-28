<?php
/**
 * Plugin Name:       CacheWarmer License Manager
 * Plugin URI:        https://dashboard.cachewarmer.drossmedia.de
 * Description:       Zentrales Lizenzverwaltungssystem für CacheWarmer – verwaltet Lizenzschlüssel, Installationen, Stripe-Zahlungen und Feature-Gating für Node.js, Docker, WordPress und Drupal Plattformen.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Alexander Dross / DrossMedia
 * Author URI:        https://drossmedia.de
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cwlm
 * Domain Path:       /languages
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin-Konstanten
define( 'CWLM_VERSION', '1.0.0' );
define( 'CWLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CWLM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CWLM_DB_PREFIX', 'cwlm_' );

/**
 * Überprüfe Mindestanforderungen.
 */
function cwlm_check_requirements() {
    if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'CacheWarmer License Manager benötigt PHP 8.0 oder höher.', 'cwlm' );
            echo '</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Plugin-Aktivierung.
 */
function cwlm_activate() {
    if ( ! cwlm_check_requirements() ) {
        return;
    }
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-activator.php';
    CWLM_Activator::activate();
}
register_activation_hook( __FILE__, 'cwlm_activate' );

/**
 * Plugin-Deaktivierung.
 */
function cwlm_deactivate() {
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-deactivator.php';
    CWLM_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'cwlm_deactivate' );

/**
 * Plugin initialisieren.
 */
function cwlm_init() {
    if ( ! cwlm_check_requirements() ) {
        return;
    }

    // Autoload-Klassen laden
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-loader.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-database.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-license-manager.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-installation-tracker.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-rate-limiter.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-jwt-handler.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-audit-logger.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-feature-flags.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-email.php';
    require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-geoip.php';

    // REST API laden
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-rest-controller.php';
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-health-endpoint.php';
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-validate-endpoint.php';
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-activate-endpoint.php';
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-deactivate-endpoint.php';
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-check-endpoint.php';
    require_once CWLM_PLUGIN_DIR . 'api/class-cwlm-stripe-webhook.php';

    // Admin-Bereich laden
    if ( is_admin() ) {
        require_once CWLM_PLUGIN_DIR . 'admin/class-cwlm-admin.php';
        $admin = new CWLM_Admin();
        $admin->init();
    }

    // REST-Routen registrieren
    add_action( 'rest_api_init', 'cwlm_register_rest_routes' );

    // Cronjobs registrieren
    cwlm_register_cron_events();
}
add_action( 'plugins_loaded', 'cwlm_init' );

/**
 * REST API Routen registrieren.
 */
function cwlm_register_rest_routes() {
    $health     = new CWLM_Health_Endpoint();
    $validate   = new CWLM_Validate_Endpoint();
    $activate   = new CWLM_Activate_Endpoint();
    $deactivate = new CWLM_Deactivate_Endpoint();
    $check      = new CWLM_Check_Endpoint();
    $stripe     = new CWLM_Stripe_Webhook();

    $health->register_routes();
    $validate->register_routes();
    $activate->register_routes();
    $deactivate->register_routes();
    $check->register_routes();
    $stripe->register_routes();
}

/**
 * Cronjob-Events registrieren.
 */
function cwlm_register_cron_events() {
    if ( ! wp_next_scheduled( 'cwlm_check_expired_licenses' ) ) {
        wp_schedule_event( time(), 'daily', 'cwlm_check_expired_licenses' );
    }
    if ( ! wp_next_scheduled( 'cwlm_cleanup_old_data' ) ) {
        wp_schedule_event( time(), 'weekly', 'cwlm_cleanup_old_data' );
    }
    if ( ! wp_next_scheduled( 'cwlm_cleanup_rate_limits' ) ) {
        wp_schedule_event( time(), 'hourly', 'cwlm_cleanup_rate_limits' );
    }
    if ( ! wp_next_scheduled( 'cwlm_check_stale_installations' ) ) {
        wp_schedule_event( time(), 'daily', 'cwlm_check_stale_installations' );
    }
    if ( ! wp_next_scheduled( 'cwlm_send_expiry_warnings' ) ) {
        wp_schedule_event( time(), 'daily', 'cwlm_send_expiry_warnings' );
    }
}

// Cronjob-Callbacks
add_action( 'cwlm_check_expired_licenses', function () {
    $manager = new CWLM_License_Manager();
    $manager->process_expired_licenses();
} );

add_action( 'cwlm_cleanup_old_data', function () {
    $db = new CWLM_Database();
    $db->cleanup_old_data( 24 ); // 24 Monate
} );

add_action( 'cwlm_cleanup_rate_limits', function () {
    $limiter = new CWLM_Rate_Limiter();
    $limiter->cleanup_expired();
} );

add_action( 'cwlm_check_stale_installations', function () {
    $tracker = new CWLM_Installation_Tracker();
    $tracker->deactivate_stale_installations( 7 ); // 7 Tage ohne Heartbeat
} );

add_action( 'cwlm_send_expiry_warnings', function () {
    $email = new CWLM_Email();
    $email->send_expiry_warnings( 7 ); // 7 Tage vor Ablauf
} );
