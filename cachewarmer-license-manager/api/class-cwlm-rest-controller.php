<?php
/**
 * REST API Base Controller.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class CWLM_REST_Controller {

    protected string $namespace = 'cwlm/v1';

    abstract public function register_routes(): void;

    /**
     * Erfolgs-Response senden.
     *
     * @param array<string, mixed> $data Response-Daten.
     */
    protected function success( array $data, int $status = 200 ): \WP_REST_Response {
        return new \WP_REST_Response( $data, $status );
    }

    /**
     * Fehler-Response senden.
     */
    protected function error( string $code, string $message, int $status = 400, array $details = [] ): \WP_REST_Response {
        $response = [
            'error'   => true,
            'code'    => $code,
            'message' => $message,
        ];

        if ( ! empty( $details ) ) {
            $response['details'] = $details;
        }

        return new \WP_REST_Response( $response, $status );
    }

    /**
     * Rate Limit prüfen.
     *
     * @return true|\WP_REST_Response
     */
    protected function check_rate_limit( string $endpoint, \WP_REST_Request $request ): true|\WP_REST_Response {
        $limiter = new CWLM_Rate_Limiter();
        $ip      = CWLM_Audit_Logger::get_client_ip();
        $result  = $limiter->check( $endpoint, $ip );

        if ( is_wp_error( $result ) ) {
            $data = $result->get_error_data();
            $response = $this->error(
                'RATE_LIMITED',
                $result->get_error_message(),
                429,
                [ 'retry_after' => $data['retry_after'] ?? 60 ]
            );
            $response->header( 'Retry-After', (string) ( $data['retry_after'] ?? 60 ) );
            return $response;
        }

        return true;
    }

    /**
     * CORS-Header setzen.
     *
     * Verwendet CWLM_CORS_ALLOWED_ORIGINS (kommaseparierte Liste) statt Wildcard.
     * Setze '*' als Wert um explizit alle Origins zuzulassen.
     */
    protected function add_cors_headers( \WP_REST_Response $response ): \WP_REST_Response {
        $allowed = defined( 'CWLM_CORS_ALLOWED_ORIGINS' ) ? CWLM_CORS_ALLOWED_ORIGINS : '';
        $origin  = isset( $_SERVER['HTTP_ORIGIN'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) )
            : '';

        if ( $allowed === '*' ) {
            $response->header( 'Access-Control-Allow-Origin', '*' );
        } elseif ( ! empty( $allowed ) && ! empty( $origin ) ) {
            $origins = array_map( 'trim', explode( ',', $allowed ) );
            if ( in_array( $origin, $origins, true ) ) {
                $response->header( 'Access-Control-Allow-Origin', $origin );
                $response->header( 'Vary', 'Origin' );
            }
        }

        $response->header( 'Access-Control-Allow-Methods', 'POST, GET, OPTIONS' );
        $response->header( 'Access-Control-Allow-Headers', 'Content-Type, Authorization' );
        return $response;
    }
}
