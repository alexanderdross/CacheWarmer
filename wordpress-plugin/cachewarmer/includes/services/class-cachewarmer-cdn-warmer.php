<?php
/**
 * CDN Edge Cache Warming service.
 *
 * Fetches each URL with desktop and mobile user-agents to warm CDN caches.
 * Uses wp_remote_get() instead of Puppeteer for WordPress compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_CDN_Warmer {

    private int $concurrency;
    private int $timeout;
    private string $desktop_ua;
    private string $mobile_ua;

    public function __construct() {
        $this->concurrency = (int) get_option( 'cachewarmer_cdn_concurrency', 3 );
        $this->timeout     = (int) get_option( 'cachewarmer_cdn_timeout', 30 );
        $this->desktop_ua  = get_option(
            'cachewarmer_cdn_user_agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );
        $this->mobile_ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
    }

    /**
     * Warm a batch of URLs.
     *
     * @param array  $urls     Array of URL strings.
     * @param string $job_id   Job ID for tracking.
     * @param callable|null $on_result Callback invoked per URL result.
     * @return array Results.
     */
    public function warm( array $urls, string $job_id, ?callable $on_result = null ): array {
        $results = array();

        foreach ( array_chunk( $urls, $this->concurrency ) as $batch ) {
            foreach ( $batch as $url ) {
                // Desktop request.
                $result = $this->fetch_url( $url, $this->desktop_ua, 'desktop' );
                $results[] = $result;
                if ( $on_result ) {
                    $on_result( $result );
                }

                // Mobile request.
                $result = $this->fetch_url( $url, $this->mobile_ua, 'mobile' );
                $results[] = $result;
                if ( $on_result ) {
                    $on_result( $result );
                }
            }
        }

        return $results;
    }

    private function fetch_url( string $url, string $user_agent, string $viewport ): array {
        $start = microtime( true );

        $response = wp_remote_get( $url, array(
            'timeout'    => $this->timeout,
            'user-agent' => $user_agent,
            'sslverify'  => false,
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Cache-Control'   => 'no-cache',
            ),
        ) );

        $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

        if ( is_wp_error( $response ) ) {
            return array(
                'url'         => $url,
                'target'      => 'cdn',
                'status'      => 'failed',
                'http_status' => null,
                'duration_ms' => $duration_ms,
                'error'       => $response->get_error_message(),
                'viewport'    => $viewport,
            );
        }

        $http_status = wp_remote_retrieve_response_code( $response );

        return array(
            'url'         => $url,
            'target'      => 'cdn',
            'status'      => ( $http_status >= 200 && $http_status < 400 ) ? 'success' : 'failed',
            'http_status' => $http_status,
            'duration_ms' => $duration_ms,
            'error'       => ( $http_status >= 400 ) ? "HTTP $http_status" : null,
            'viewport'    => $viewport,
        );
    }
}
