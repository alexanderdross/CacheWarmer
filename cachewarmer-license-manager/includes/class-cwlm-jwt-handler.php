<?php
/**
 * JWT Token Management via firebase/php-jwt.
 *
 * Nutzt die Firebase JWT Library (Composer-Dependency) für standardkonforme
 * Token-Generierung und -Validierung. Fällt auf manuelle Implementation zurück
 * wenn die Library nicht verfügbar ist.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_JWT_Handler {

    private string $secret;
    private int $expiry_days;
    private bool $use_firebase;

    public function __construct() {
        $this->secret      = defined( 'CWLM_JWT_SECRET' ) ? CWLM_JWT_SECRET : '';
        $this->expiry_days = defined( 'CWLM_JWT_EXPIRY_DAYS' ) ? (int) CWLM_JWT_EXPIRY_DAYS : 30;

        // Composer-Autoloader laden falls vorhanden
        $autoload = CWLM_PLUGIN_DIR . 'vendor/autoload.php';
        if ( file_exists( $autoload ) ) {
            require_once $autoload;
        }

        $this->use_firebase = class_exists( '\Firebase\JWT\JWT' ) && class_exists( '\Firebase\JWT\Key' );
    }

    /**
     * JWT Token generieren.
     *
     * @param array<string, mixed> $payload Payload-Daten.
     */
    public function generate( array $payload ): string {
        $payload['iat'] = time();
        $payload['exp'] = time() + ( $this->expiry_days * 86400 );

        if ( $this->use_firebase ) {
            return \Firebase\JWT\JWT::encode( $payload, $this->secret, 'HS256' );
        }

        return $this->manual_encode( $payload );
    }

    /**
     * JWT Token validieren und Payload zurückgeben.
     *
     * @return array<string, mixed>|false
     */
    public function validate( string $token ): array|false {
        if ( $this->use_firebase ) {
            try {
                $decoded = \Firebase\JWT\JWT::decode( $token, new \Firebase\JWT\Key( $this->secret, 'HS256' ) );
                return (array) $decoded;
            } catch ( \Exception $e ) {
                return false;
            }
        }

        return $this->manual_decode( $token );
    }

    /**
     * Token-Ablaufdatum zurückgeben.
     */
    public function get_expiry_date(): string {
        return gmdate( 'Y-m-d\TH:i:s\Z', time() + ( $this->expiry_days * 86400 ) );
    }

    /**
     * Manuelles JWT-Encoding (Fallback ohne Composer-Library).
     */
    private function manual_encode( array $payload ): string {
        $header = $this->base64url_encode( wp_json_encode( [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ] ) );

        $payload_encoded = $this->base64url_encode( wp_json_encode( $payload ) );

        $signature = $this->base64url_encode(
            hash_hmac( 'sha256', "{$header}.{$payload_encoded}", $this->secret, true )
        );

        return "{$header}.{$payload_encoded}.{$signature}";
    }

    /**
     * Manuelles JWT-Decoding (Fallback ohne Composer-Library).
     *
     * @return array<string, mixed>|false
     */
    private function manual_decode( string $token ): array|false {
        $parts = explode( '.', $token );
        if ( count( $parts ) !== 3 ) {
            return false;
        }

        [ $header, $payload, $signature ] = $parts;

        $expected_sig = $this->base64url_encode(
            hash_hmac( 'sha256', "{$header}.{$payload}", $this->secret, true )
        );

        if ( ! hash_equals( $expected_sig, $signature ) ) {
            return false;
        }

        $payload_data = json_decode( $this->base64url_decode( $payload ), true );

        if ( ! $payload_data || ! isset( $payload_data['exp'] ) ) {
            return false;
        }

        if ( $payload_data['exp'] < time() ) {
            return false;
        }

        return $payload_data;
    }

    private function base64url_encode( string $data ): string {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    private function base64url_decode( string $data ): string {
        return base64_decode( strtr( $data, '-_', '+/' ) );
    }
}
