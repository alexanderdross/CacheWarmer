<?php
/**
 * CDN Cache Purge + Warm service (Enterprise).
 *
 * Purges URL caches via the APIs of Cloudflare, Imperva (Incapsula),
 * and Akamai (Fast Purge v3). Uses wp_remote_post() for HTTP requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_CDN_Purge_Warmer {

    // Cloudflare settings.
    private bool   $cf_enabled;
    private string $cf_api_token;
    private string $cf_zone_id;

    // Imperva settings.
    private bool   $imp_enabled;
    private string $imp_api_id;
    private string $imp_api_key;
    private string $imp_site_id;

    // Akamai settings.
    private bool   $ak_enabled;
    private string $ak_host;
    private string $ak_client_token;
    private string $ak_client_secret;
    private string $ak_access_token;
    private string $ak_network;

    public function __construct() {
        $this->cf_enabled   = (bool) get_option( 'cachewarmer_cloudflare_enabled', '0' );
        $this->cf_api_token = (string) get_option( 'cachewarmer_cloudflare_api_token', '' );
        $this->cf_zone_id   = (string) get_option( 'cachewarmer_cloudflare_zone_id', '' );

        $this->imp_enabled = (bool) get_option( 'cachewarmer_imperva_enabled', '0' );
        $this->imp_api_id  = (string) get_option( 'cachewarmer_imperva_api_id', '' );
        $this->imp_api_key = (string) get_option( 'cachewarmer_imperva_api_key', '' );
        $this->imp_site_id = (string) get_option( 'cachewarmer_imperva_site_id', '' );

        $this->ak_enabled       = (bool) get_option( 'cachewarmer_akamai_enabled', '0' );
        $this->ak_host          = (string) get_option( 'cachewarmer_akamai_host', '' );
        $this->ak_client_token  = (string) get_option( 'cachewarmer_akamai_client_token', '' );
        $this->ak_client_secret = (string) get_option( 'cachewarmer_akamai_client_secret', '' );
        $this->ak_access_token  = (string) get_option( 'cachewarmer_akamai_access_token', '' );
        $this->ak_network       = (string) get_option( 'cachewarmer_akamai_network', 'production' );
    }

    /**
     * Purge URLs across all enabled CDN providers.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID for tracking.
     * @param callable|null $on_result Callback per URL result.
     * @return array Results.
     */
    public function purge( array $urls, string $job_id, ?callable $on_result = null ): array {
        $results = array();

        if ( $this->cf_enabled && $this->cf_api_token && $this->cf_zone_id ) {
            $results = array_merge( $results, $this->purge_cloudflare( $urls, $on_result ) );
        }

        if ( $this->imp_enabled && $this->imp_api_id && $this->imp_api_key && $this->imp_site_id ) {
            $results = array_merge( $results, $this->purge_imperva( $urls, $on_result ) );
        }

        if ( $this->ak_enabled && $this->ak_host && $this->ak_client_token && $this->ak_client_secret && $this->ak_access_token ) {
            $results = array_merge( $results, $this->purge_akamai( $urls, $on_result ) );
        }

        return $results;
    }

    // ─── Cloudflare ────────────────────────────────────────────────

    /**
     * Purge URLs via Cloudflare API v4.
     * Batch size: 30 URLs per request.
     */
    private function purge_cloudflare( array $urls, ?callable $on_result ): array {
        $results    = array();
        $batch_size = 30;

        foreach ( array_chunk( $urls, $batch_size ) as $idx => $batch ) {
            $start = microtime( true );

            $api_url  = 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $this->cf_zone_id ) . '/purge_cache';
            $response = wp_remote_post( $api_url, array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->cf_api_token,
                    'Content-Type'  => 'application/json',
                ),
                'body' => wp_json_encode( array( 'files' => array_values( $batch ) ) ),
            ) );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                foreach ( $batch as $url ) {
                    $result = array(
                        'url'         => $url,
                        'target'      => 'cdn-purge',
                        'status'      => 'failed',
                        'http_status' => null,
                        'duration_ms' => $duration_ms,
                        'error'       => 'Cloudflare: ' . $response->get_error_message(),
                    );
                    $results[] = $result;
                    if ( $on_result ) {
                        $on_result( $result );
                    }
                }
            } else {
                $http_status = wp_remote_retrieve_response_code( $response );
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );
                $success     = ! empty( $body['success'] );

                if ( $success ) {
                    foreach ( $batch as $url ) {
                        $result = array(
                            'url'         => $url,
                            'target'      => 'cdn-purge',
                            'status'      => 'success',
                            'http_status' => $http_status,
                            'duration_ms' => $duration_ms,
                            'error'       => null,
                        );
                        $results[] = $result;
                        if ( $on_result ) {
                            $on_result( $result );
                        }
                    }
                } else {
                    $errors  = array();
                    if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
                        foreach ( $body['errors'] as $err ) {
                            $errors[] = $err['message'] ?? '';
                        }
                    }
                    $err_msg = ! empty( $errors ) ? implode( '; ', $errors ) : "HTTP $http_status";

                    foreach ( $batch as $url ) {
                        $result = array(
                            'url'         => $url,
                            'target'      => 'cdn-purge',
                            'status'      => 'failed',
                            'http_status' => $http_status,
                            'duration_ms' => $duration_ms,
                            'error'       => 'Cloudflare: ' . $err_msg,
                        );
                        $results[] = $result;
                        if ( $on_result ) {
                            $on_result( $result );
                        }
                    }
                }
            }

            // Delay between batches.
            if ( $idx < count( array_chunk( $urls, $batch_size ) ) - 1 ) {
                usleep( 500000 ); // 500ms
            }
        }

        return $results;
    }

    // ─── Imperva (Incapsula) ───────────────────────────────────────

    /**
     * Purge URLs via Imperva Cloud WAF API v1.
     * One URL per request (purge_pattern parameter).
     */
    private function purge_imperva( array $urls, ?callable $on_result ): array {
        $results = array();

        foreach ( $urls as $url ) {
            $start = microtime( true );

            $response = wp_remote_post(
                'https://my.incapsula.com/api/prov/v1/sites/performance/purge',
                array(
                    'timeout' => 30,
                    'headers' => array(
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ),
                    'body' => array(
                        'api_id'        => $this->imp_api_id,
                        'api_key'       => $this->imp_api_key,
                        'site_id'       => $this->imp_site_id,
                        'purge_pattern' => $url,
                    ),
                )
            );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'cdn-purge',
                    'status'      => 'failed',
                    'http_status' => null,
                    'duration_ms' => $duration_ms,
                    'error'       => 'Imperva: ' . $response->get_error_message(),
                );
            } else {
                $http_status = wp_remote_retrieve_response_code( $response );
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );

                // Imperva returns res=0 for success.
                if ( isset( $body['res'] ) && 0 === (int) $body['res'] ) {
                    $result = array(
                        'url'         => $url,
                        'target'      => 'cdn-purge',
                        'status'      => 'success',
                        'http_status' => $http_status,
                        'duration_ms' => $duration_ms,
                        'error'       => null,
                    );
                } else {
                    $err_msg = $body['res_message'] ?? "Imperva error code " . ( $body['res'] ?? 'unknown' );
                    $result  = array(
                        'url'         => $url,
                        'target'      => 'cdn-purge',
                        'status'      => 'failed',
                        'http_status' => $http_status,
                        'duration_ms' => $duration_ms,
                        'error'       => 'Imperva: ' . $err_msg,
                    );
                }
            }

            $results[] = $result;
            if ( $on_result ) {
                $on_result( $result );
            }

            usleep( 200000 ); // 200ms between requests.
        }

        return $results;
    }

    // ─── Akamai (Fast Purge API v3) ────────────────────────────────

    /**
     * Purge URLs via Akamai Fast Purge API v3.
     * Batch size: 50 URLs per request.
     */
    private function purge_akamai( array $urls, ?callable $on_result ): array {
        $results    = array();
        $batch_size = 50;
        $network    = $this->ak_network ?: 'production';

        foreach ( array_chunk( $urls, $batch_size ) as $idx => $batch ) {
            $start = microtime( true );

            $api_url  = 'https://' . $this->ak_host . '/ccu/v3/invalidate/url/' . rawurlencode( $network );
            $body_str = wp_json_encode( array( 'objects' => array_values( $batch ) ) );

            $auth_header = $this->generate_edgegrid_auth( 'POST', $api_url, $body_str );

            $response = wp_remote_post( $api_url, array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => $auth_header,
                    'Content-Type'  => 'application/json',
                ),
                'body' => $body_str,
            ) );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                foreach ( $batch as $url ) {
                    $result = array(
                        'url'         => $url,
                        'target'      => 'cdn-purge',
                        'status'      => 'failed',
                        'http_status' => null,
                        'duration_ms' => $duration_ms,
                        'error'       => 'Akamai: ' . $response->get_error_message(),
                    );
                    $results[] = $result;
                    if ( $on_result ) {
                        $on_result( $result );
                    }
                }
            } else {
                $http_status = wp_remote_retrieve_response_code( $response );
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( $http_status >= 200 && $http_status < 300 ) {
                    foreach ( $batch as $url ) {
                        $result = array(
                            'url'         => $url,
                            'target'      => 'cdn-purge',
                            'status'      => 'success',
                            'http_status' => $http_status,
                            'duration_ms' => $duration_ms,
                            'error'       => null,
                        );
                        $results[] = $result;
                        if ( $on_result ) {
                            $on_result( $result );
                        }
                    }
                } else {
                    $err_msg = $body['detail'] ?? "HTTP $http_status";

                    foreach ( $batch as $url ) {
                        $result = array(
                            'url'         => $url,
                            'target'      => 'cdn-purge',
                            'status'      => 'failed',
                            'http_status' => $http_status,
                            'duration_ms' => $duration_ms,
                            'error'       => 'Akamai: ' . $err_msg,
                        );
                        $results[] = $result;
                        if ( $on_result ) {
                            $on_result( $result );
                        }
                    }
                }
            }

            // Delay between batches.
            if ( $idx < count( array_chunk( $urls, $batch_size ) ) - 1 ) {
                usleep( 1000000 ); // 1s
            }
        }

        return $results;
    }

    /**
     * Generate EdgeGrid Authorization header for Akamai API requests.
     *
     * Implements the EG1-HMAC-SHA256 signing algorithm.
     *
     * @param string $method   HTTP method (POST).
     * @param string $url      Full API URL.
     * @param string $body_str JSON request body.
     * @return string Authorization header value.
     */
    private function generate_edgegrid_auth( string $method, string $url, string $body_str ): string {
        $parsed = wp_parse_url( $url );

        $timestamp = gmdate( 'Ymd\TH:i:s+0000' );
        $nonce     = wp_generate_uuid4();

        $auth_data = sprintf(
            'EG1-HMAC-SHA256 client_token=%s;access_token=%s;timestamp=%s;nonce=%s;',
            $this->ak_client_token,
            $this->ak_access_token,
            $timestamp,
            $nonce
        );

        // Content hash: Base64(SHA-256(POST body)) — max 131072 bytes.
        $max_body     = substr( $body_str, 0, 131072 );
        $content_hash = base64_encode( hash( 'sha256', $max_body, true ) );

        $path_query = ( $parsed['path'] ?? '/' ) . ( ! empty( $parsed['query'] ) ? '?' . $parsed['query'] : '' );

        $data_to_sign = implode( "\t", array(
            strtoupper( $method ),
            'https',
            $parsed['host'] ?? '',
            $path_query,
            '',              // headers to sign (empty)
            $content_hash,
            $auth_data,
        ) );

        // Signing key = HMAC-SHA256(timestamp, client_secret).
        $signing_key = base64_encode(
            hash_hmac( 'sha256', $timestamp, $this->ak_client_secret, true )
        );

        // Signature = HMAC-SHA256(data_to_sign, signing_key).
        $signature = base64_encode(
            hash_hmac( 'sha256', $data_to_sign, base64_decode( $signing_key ), true )
        );

        return $auth_data . 'signature=' . $signature;
    }
}
