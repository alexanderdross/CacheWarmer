<?php
/**
 * POST /activate – Installation registrieren und Lizenz aktivieren.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Activate_Endpoint extends CWLM_REST_Controller {

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/activate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $rate_check = $this->check_rate_limit( 'activate', $request );
        if ( $rate_check !== true ) {
            return $rate_check;
        }

        $license_key = sanitize_text_field( $request->get_param( 'license_key' ) ?? '' );
        $fingerprint = sanitize_text_field( $request->get_param( 'fingerprint' ) ?? '' );
        $platform    = sanitize_text_field( $request->get_param( 'platform' ) ?? '' );

        // Input-Validierung
        if ( ! CWLM_License_Manager::validate_key_format( $license_key ) ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_KEY', 'Ungültiges Lizenzschlüssel-Format.', 400 )
            );
        }

        if ( ! CWLM_Installation_Tracker::validate_fingerprint( $fingerprint ) ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_FINGERPRINT', 'Ungültiger Fingerprint (64 Hex-Zeichen erwartet).', 400 )
            );
        }

        if ( ! CWLM_Installation_Tracker::validate_platform( $platform ) ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_PLATFORM', 'Ungültige Plattform.', 400 )
            );
        }

        // Lizenz suchen
        $manager = new CWLM_License_Manager();
        $license = $manager->find_by_key( $license_key );

        if ( ! $license ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_KEY', 'Lizenzschlüssel nicht gefunden.', 404 )
            );
        }

        // Status prüfen
        if ( $license->status === 'revoked' ) {
            return $this->add_cors_headers(
                $this->error( 'LICENSE_REVOKED', 'Diese Lizenz wurde gesperrt.', 403 )
            );
        }

        if ( $license->status === 'expired' ) {
            return $this->add_cors_headers(
                $this->error( 'LICENSE_EXPIRED', 'Diese Lizenz ist abgelaufen.', 403 )
            );
        }

        // Development-Lizenz: Domain-Check
        if ( $license->tier === 'development' ) {
            $domain   = $request->get_param( 'domain' ) ?? $request->get_param( 'hostname' ) ?? '';
            $domain   = sanitize_text_field( $domain );
            if ( $domain && ! CWLM_Feature_Flags::is_development_domain( $domain ) ) {
                return $this->add_cors_headers(
                    $this->error( 'DEVELOPMENT_ONLY', 'Development-Lizenzen sind nur für lokale Domains gültig.', 403 )
                );
            }
        }

        // Installation registrieren
        $tracker = new CWLM_Installation_Tracker();
        $ip      = CWLM_Audit_Logger::get_anonymized_ip();

        $result = $tracker->activate( (int) $license->id, [
            'fingerprint'         => $fingerprint,
            'platform'            => $platform,
            'platform_version'    => sanitize_text_field( $request->get_param( 'platform_version' ) ?? '' ),
            'cachewarmer_version' => sanitize_text_field( $request->get_param( 'cachewarmer_version' ) ?? '' ),
            'domain'              => sanitize_text_field( $request->get_param( 'domain' ) ?? '' ),
            'hostname'            => sanitize_text_field( $request->get_param( 'hostname' ) ?? '' ),
            'os_platform'         => sanitize_text_field( $request->get_param( 'os_platform' ) ?? '' ),
            'os_version'          => sanitize_text_field( $request->get_param( 'os_version' ) ?? '' ),
            'ip_address'          => $ip,
        ] );

        if ( is_wp_error( $result ) ) {
            $data = $result->get_error_data();
            return $this->add_cors_headers(
                $this->error( $result->get_error_code(), $result->get_error_message(), $data['status'] ?? 409 )
            );
        }

        // Lizenzstatus auf active setzen falls noch inactive
        if ( $license->status === 'inactive' ) {
            global $wpdb;
            $prefix = $wpdb->prefix . CWLM_DB_PREFIX;
            $wpdb->update(
                $prefix . 'licenses',
                [ 'status' => 'active', 'activated_at' => gmdate( 'Y-m-d H:i:s' ) ],
                [ 'id' => $license->id ],
                [ '%s', '%s' ],
                [ '%d' ]
            );
        }

        // GeoIP-Daten speichern (asynchron, Fehler werden ignoriert)
        $geoip    = new CWLM_GeoIP();
        $real_ip  = CWLM_Audit_Logger::get_client_ip();
        $geoip->store_for_installation( $result['id'], $real_ip );

        // JWT Token generieren
        $jwt      = new CWLM_JWT_Handler();
        $features = ( new CWLM_Feature_Flags() )->get_features( $license );

        $token = $jwt->generate( [
            'license_id'      => (int) $license->id,
            'installation_id' => $result['id'],
            'tier'            => $license->tier,
            'features'        => $features,
        ] );

        // Audit-Log
        $audit = new CWLM_Audit_Logger();
        $audit->log(
            'license.activated',
            'api',
            null,
            (int) $license->id,
            $result['id'],
            [
                'platform'    => $platform,
                'fingerprint' => substr( $fingerprint, 0, 12 ) . '...',
                'is_new'      => $result['is_new'],
            ]
        );

        $heartbeat_hours = defined( 'CWLM_HEARTBEAT_INTERVAL_HOURS' ) ? (int) CWLM_HEARTBEAT_INTERVAL_HOURS : 24;

        return $this->add_cors_headers( $this->success( [
            'activated'        => true,
            'installation_id'  => $result['id'],
            'token'            => $token,
            'token_expires_at' => $jwt->get_expiry_date(),
            'features'         => $features,
            'next_check'       => gmdate( 'Y-m-d\TH:i:s\Z', time() + ( $heartbeat_hours * 3600 ) ),
        ] ) );
    }
}
