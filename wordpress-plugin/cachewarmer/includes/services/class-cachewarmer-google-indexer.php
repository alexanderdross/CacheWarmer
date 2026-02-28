<?php
/**
 * Google Indexing API service.
 *
 * Uses the Google Indexing API to notify Google about URL updates.
 * Requires a Google Service Account with Indexing API access.
 *
 * Endpoint: POST https://indexing.googleapis.com/v3/urlNotifications:publish
 * Rate limit: 200 requests/day per property.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Google_Indexer {

    private string $service_account_json;
    private int $daily_quota;

    public function __construct() {
        $this->service_account_json = get_option( 'cachewarmer_google_service_account', '' );
        $this->daily_quota          = (int) get_option( 'cachewarmer_google_daily_quota', 200 );
    }

    public function is_configured(): bool {
        return ! empty( $this->service_account_json );
    }

    /**
     * Submit URLs to Google Indexing API.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID.
     * @param callable|null $on_result Callback per result.
     * @return array Results.
     */
    public function index( array $urls, string $job_id, ?callable $on_result = null ): array {
        if ( ! $this->is_configured() ) {
            return $this->skip_all( $urls, 'Google Service Account not configured', $on_result );
        }

        $access_token = $this->get_access_token();
        if ( ! $access_token ) {
            return $this->skip_all( $urls, 'Failed to obtain Google access token', $on_result );
        }

        $results = array();
        $count   = 0;

        foreach ( $urls as $url ) {
            if ( $count >= $this->daily_quota ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'google',
                    'status'      => 'skipped',
                    'http_status' => null,
                    'duration_ms' => 0,
                    'error'       => 'Daily quota exceeded',
                );
                $results[] = $result;
                if ( $on_result ) {
                    $on_result( $result );
                }
                continue;
            }

            $start = microtime( true );

            $response = wp_remote_post(
                'https://indexing.googleapis.com/v3/urlNotifications:publish',
                array(
                    'timeout' => 30,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $access_token,
                    ),
                    'body' => wp_json_encode( array(
                        'url'  => $url,
                        'type' => 'URL_UPDATED',
                    ) ),
                )
            );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'google',
                    'status'      => 'failed',
                    'http_status' => null,
                    'duration_ms' => $duration_ms,
                    'error'       => $response->get_error_message(),
                );
            } else {
                $http_status = wp_remote_retrieve_response_code( $response );
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );

                $result = array(
                    'url'         => $url,
                    'target'      => 'google',
                    'status'      => ( $http_status === 200 ) ? 'success' : 'failed',
                    'http_status' => $http_status,
                    'duration_ms' => $duration_ms,
                    'error'       => $body['error']['message'] ?? ( $http_status !== 200 ? "HTTP $http_status" : null ),
                );
            }

            $results[] = $result;
            if ( $on_result ) {
                $on_result( $result );
            }

            $count++;
            usleep( 100000 ); // 100ms between requests.
        }

        return $results;
    }

    /**
     * Get a Google OAuth2 access token using the service account.
     */
    private function get_access_token(): ?string {
        $sa = json_decode( $this->service_account_json, true );
        if ( ! $sa || ! isset( $sa['client_email'], $sa['private_key'] ) ) {
            return null;
        }

        $now    = time();
        $header = wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) );
        $claim  = wp_json_encode( array(
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ) );

        $base64_header = rtrim( strtr( base64_encode( $header ), '+/', '-_' ), '=' );
        $base64_claim  = rtrim( strtr( base64_encode( $claim ), '+/', '-_' ), '=' );
        $signing_input = $base64_header . '.' . $base64_claim;

        $private_key = openssl_pkey_get_private( $sa['private_key'] );
        if ( ! $private_key ) {
            return null;
        }

        $signature = '';
        if ( ! openssl_sign( $signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256 ) ) {
            return null;
        }

        $base64_signature = rtrim( strtr( base64_encode( $signature ), '+/', '-_' ), '=' );
        $jwt              = $signing_input . '.' . $base64_signature;

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'timeout' => 15,
            'body'    => array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['access_token'] ?? null;
    }

    private function skip_all( array $urls, string $reason, ?callable $on_result ): array {
        $results = array();
        foreach ( $urls as $url ) {
            $result = array(
                'url'         => $url,
                'target'      => 'google',
                'status'      => 'skipped',
                'http_status' => null,
                'duration_ms' => 0,
                'error'       => $reason,
            );
            $results[] = $result;
            if ( $on_result ) {
                $on_result( $result );
            }
        }
        return $results;
    }
}
