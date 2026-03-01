<?php
/**
 * PHPUnit Bootstrap – Minimales WordPress-Mock-Environment.
 *
 * Da das Plugin ohne vollständige WordPress-Installation nicht lauffähig ist,
 * stellen wir hier minimale Stubs bereit, die Unit-Tests der reinen
 * Geschäftslogik ermöglichen (Key-Generierung, Validierung, Feature-Flags, etc.).
 *
 * @package CacheWarmer_License_Manager\Tests
 */

// ABSPATH Stub
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wp/' );
}

// Plugin-Konstanten
define( 'CWLM_VERSION', '1.0.0-test' );
define( 'CWLM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'CWLM_PLUGIN_URL', 'http://localhost/wp-content/plugins/cachewarmer-license-manager/' );
define( 'CWLM_PLUGIN_BASENAME', 'cachewarmer-license-manager/cachewarmer-license-manager.php' );
define( 'CWLM_DB_PREFIX', 'cwlm_' );

// Konfiguration
define( 'CWLM_JWT_SECRET', 'test-secret-key-for-unit-tests-only-32chars!' );
define( 'CWLM_JWT_EXPIRY_DAYS', 30 );
define( 'CWLM_GRACE_PERIOD_DAYS', 14 );
define( 'CWLM_HEARTBEAT_INTERVAL_HOURS', 24 );
define( 'CWLM_DEV_DOMAINS', 'localhost,*.local,*.dev,*.test,127.0.0.1' );

// ── WordPress Function Stubs ───────────────────────────────────────────

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, $options = 0, $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = [] ) {
        return array_merge( $defaults, $args );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return trim( strip_tags( (string) $str ) );
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) {
        return filter_var( $email, FILTER_SANITIZE_EMAIL );
    }
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
    function is_user_logged_in() {
        return false;
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() {
        return 0;
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return stripslashes_deep( $value );
    }
}

if ( ! function_exists( 'stripslashes_deep' ) ) {
    function stripslashes_deep( $value ) {
        if ( is_array( $value ) ) {
            return array_map( 'stripslashes_deep', $value );
        }
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }
}

// ── Autoload testbare Klassen ──────────────────────────────────────────

// Nur Klassen die ohne DB/wpdb funktionieren
require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-feature-flags.php';
require_once CWLM_PLUGIN_DIR . 'includes/class-cwlm-jwt-handler.php';
