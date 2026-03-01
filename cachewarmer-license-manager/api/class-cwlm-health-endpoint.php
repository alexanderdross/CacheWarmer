<?php
/**
 * GET /health – Systemstatus.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Health_Endpoint extends CWLM_REST_Controller {

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/health', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $rate_check = $this->check_rate_limit( 'health', $request );
        if ( $rate_check !== true ) {
            return $rate_check;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        // DB-Verbindung prüfen
        $db_ok = (bool) $wpdb->get_var( "SELECT 1 FROM {$prefix}licenses LIMIT 1" ) !== false;

        // GeoIP-DB prüfen
        $geoip_path    = defined( 'CWLM_MAXMIND_DB_PATH' ) ? CWLM_MAXMIND_DB_PATH : '';
        $geoip_loaded  = file_exists( $geoip_path );
        $geoip_updated = $geoip_loaded ? gmdate( 'Y-m-d', filemtime( $geoip_path ) ) : null;

        // Öffentliche Response ohne sensible Details (Version, GeoIP-Status)
        return $this->add_cors_headers( $this->success( [
            'status'    => 'ok',
            'timestamp' => gmdate( 'Y-m-d\TH:i:s\Z' ),
            'database'  => $db_ok ? 'connected' : 'error',
        ] ) );
    }
}
