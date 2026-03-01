<?php
/**
 * POST /check – Heartbeat (alle 24h).
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Check_Endpoint extends CWLM_REST_Controller {

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/check', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $rate_check = $this->check_rate_limit( 'check', $request );
        if ( $rate_check !== true ) {
            return $rate_check;
        }

        $license_key = sanitize_text_field( $request->get_param( 'license_key' ) ?? '' );
        $fingerprint = sanitize_text_field( $request->get_param( 'fingerprint' ) ?? '' );
        $token       = sanitize_text_field( $request->get_param( 'token' ) ?? '' );
        $cw_version  = sanitize_text_field( $request->get_param( 'cachewarmer_version' ) ?? '' );

        // Validierung
        if ( ! CWLM_License_Manager::validate_key_format( $license_key ) ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_KEY', 'Ungültiges Lizenzschlüssel-Format.', 400 )
            );
        }

        if ( ! CWLM_Installation_Tracker::validate_fingerprint( $fingerprint ) ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_FINGERPRINT', 'Ungültiger Fingerprint.', 400 )
            );
        }

        // Token validieren
        $jwt          = new CWLM_JWT_Handler();
        $token_data   = $jwt->validate( $token );

        if ( false === $token_data ) {
            return $this->add_cors_headers(
                $this->error( 'INVALID_TOKEN', 'JWT Token ungültig oder abgelaufen.', 401 )
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

        // Cross-Validierung: Token-license_id muss zur Lizenz passen
        if ( isset( $token_data['license_id'] ) && (int) $token_data['license_id'] !== (int) $license->id ) {
            return $this->add_cors_headers(
                $this->error( 'TOKEN_MISMATCH', 'JWT Token gehört nicht zu diesem Lizenzschlüssel.', 403 )
            );
        }

        // Heartbeat aktualisieren
        $tracker = new CWLM_Installation_Tracker();
        $tracker->update_heartbeat( (int) $license->id, $fingerprint, $cw_version ?: null );

        // Features und neuen Token ermitteln
        $features  = ( new CWLM_Feature_Flags() )->get_features( $license );
        $new_token = $jwt->generate( [
            'license_id'      => (int) $license->id,
            'installation_id' => $token_data['installation_id'] ?? 0,
            'tier'            => $license->tier,
            'features'        => $features,
        ] );

        $heartbeat_hours = defined( 'CWLM_HEARTBEAT_INTERVAL_HOURS' ) ? (int) CWLM_HEARTBEAT_INTERVAL_HOURS : 24;

        // Messages (z.B. Update-Hinweis)
        $messages = [];

        return $this->add_cors_headers( $this->success( [
            'valid'            => $manager->is_valid( $license ),
            'status'           => $license->status,
            'features'         => $features,
            'token'            => $new_token,
            'token_expires_at' => $jwt->get_expiry_date(),
            'next_check'       => gmdate( 'Y-m-d\TH:i:s\Z', time() + ( $heartbeat_hours * 3600 ) ),
            'update_available' => null,
            'messages'         => $messages,
        ] ) );
    }
}
