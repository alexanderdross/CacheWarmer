<?php
/**
 * Pinterest Rich Pin Validator warming service.
 *
 * Triggers Pinterest's rich pin scraper to refresh OG meta cache.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Pinterest_Warmer {

    private int $delay;

    public function __construct() {
        $this->delay = 2000; // 2s between requests
    }

    /**
     * Warm URLs via Pinterest Rich Pin Validator.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID for tracking.
     * @param callable|null $on_result Callback per URL result.
     * @return array Results.
     */
    public function warm( array $urls, string $job_id, ?callable $on_result = null ): array {
        $results = array();

        foreach ( $urls as $url ) {
            $start = microtime( true );

            $response = wp_remote_get(
                'https://developers.pinterest.com/tools/url-debugger/?link=' . rawurlencode( $url ),
                array(
                    'timeout'    => 30,
                    'user-agent' => 'Mozilla/5.0 (compatible; CacheWarmer/1.0)',
                    'sslverify'  => true,
                )
            );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'pinterest',
                    'status'      => 'failed',
                    'http_status' => null,
                    'duration_ms' => $duration_ms,
                    'error'       => $response->get_error_message(),
                );
            } else {
                $http_status = wp_remote_retrieve_response_code( $response );
                $result = array(
                    'url'         => $url,
                    'target'      => 'pinterest',
                    'status'      => ( $http_status >= 200 && $http_status < 400 ) ? 'success' : 'failed',
                    'http_status' => $http_status,
                    'duration_ms' => $duration_ms,
                    'error'       => ( $http_status >= 400 ) ? "HTTP $http_status" : null,
                );
            }

            $results[] = $result;
            if ( $on_result ) {
                $on_result( $result );
            }

            usleep( $this->delay * 1000 );
        }

        return $results;
    }
}
