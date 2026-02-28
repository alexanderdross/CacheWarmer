<?php
/**
 * IndexNow protocol service.
 *
 * Submits URLs in batches to the IndexNow API.
 * Supports: Bing, Yandex, Seznam, Naver and other participating search engines.
 *
 * Endpoint: POST https://api.indexnow.org/indexnow
 * Batch size: up to 10,000 URLs per request.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_IndexNow {

    private const BATCH_SIZE   = 10000;
    private const API_ENDPOINT = 'https://api.indexnow.org/indexnow';

    private string $key;
    private string $key_location;

    public function __construct() {
        $this->key          = get_option( 'cachewarmer_indexnow_key', '' );
        $this->key_location = get_option( 'cachewarmer_indexnow_key_location', '' );
    }

    public function is_configured(): bool {
        return ! empty( $this->key );
    }

    /**
     * Submit URLs via IndexNow protocol.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID.
     * @param callable|null $on_result Callback per result.
     * @return array Results.
     */
    public function submit( array $urls, string $job_id, ?callable $on_result = null ): array {
        if ( ! $this->is_configured() ) {
            return $this->skip_all( $urls, 'IndexNow key not configured', $on_result );
        }

        if ( empty( $urls ) ) {
            return array();
        }

        // Extract host from first URL.
        $parsed = wp_parse_url( $urls[0] );
        $host   = $parsed['host'] ?? '';

        $results = array();

        foreach ( array_chunk( $urls, self::BATCH_SIZE ) as $batch ) {
            $start = microtime( true );

            $body = array(
                'host'    => $host,
                'key'     => $this->key,
                'urlList' => array_values( $batch ),
            );

            if ( ! empty( $this->key_location ) ) {
                $body['keyLocation'] = $this->key_location;
            }

            $response = wp_remote_post( self::API_ENDPOINT, array(
                'timeout' => 30,
                'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
                'body'    => wp_json_encode( $body ),
            ) );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                foreach ( $batch as $url ) {
                    $result = array(
                        'url'         => $url,
                        'target'      => 'indexnow',
                        'status'      => 'failed',
                        'http_status' => null,
                        'duration_ms' => $duration_ms,
                        'error'       => $response->get_error_message(),
                    );
                    $results[] = $result;
                    if ( $on_result ) {
                        $on_result( $result );
                    }
                }
                continue;
            }

            $http_status = wp_remote_retrieve_response_code( $response );
            // IndexNow returns 200 or 202 on success.
            $success = in_array( $http_status, array( 200, 202 ), true );

            foreach ( $batch as $url ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'indexnow',
                    'status'      => $success ? 'success' : 'failed',
                    'http_status' => $http_status,
                    'duration_ms' => $duration_ms,
                    'error'       => $success ? null : "HTTP $http_status",
                );
                $results[] = $result;
                if ( $on_result ) {
                    $on_result( $result );
                }
            }
        }

        return $results;
    }

    private function skip_all( array $urls, string $reason, ?callable $on_result ): array {
        $results = array();
        foreach ( $urls as $url ) {
            $result = array(
                'url'         => $url,
                'target'      => 'indexnow',
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
