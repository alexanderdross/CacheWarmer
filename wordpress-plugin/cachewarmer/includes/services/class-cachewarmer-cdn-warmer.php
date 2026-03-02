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
    private array $custom_headers;
    private array $custom_viewports;
    private array $auth_cookies;

    public function __construct() {
        $this->concurrency = (int) get_option( 'cachewarmer_cdn_concurrency', 3 );
        $this->timeout     = (int) get_option( 'cachewarmer_cdn_timeout', 30 );

        // Enterprise: custom user agent
        $default_desktop_ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        if ( CacheWarmer_License::can( 'custom_user_agent' ) ) {
            $this->desktop_ua = get_option( 'cachewarmer_custom_user_agent', '' ) ?: $default_desktop_ua;
        } else {
            $this->desktop_ua = get_option( 'cachewarmer_cdn_user_agent', $default_desktop_ua );
        }
        $this->mobile_ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

        // Enterprise: custom HTTP headers
        $this->custom_headers = array();
        if ( CacheWarmer_License::can( 'custom_headers' ) ) {
            $raw = get_option( 'cachewarmer_custom_headers', '' );
            if ( ! empty( $raw ) ) {
                foreach ( explode( "\n", $raw ) as $line ) {
                    $line = trim( $line );
                    if ( false !== strpos( $line, ':' ) ) {
                        list( $key, $value ) = explode( ':', $line, 2 );
                        $this->custom_headers[ trim( $key ) ] = trim( $value );
                    }
                }
            }
        }

        // Enterprise: custom viewports
        $this->custom_viewports = array();
        if ( CacheWarmer_License::can( 'custom_viewports' ) ) {
            $raw = get_option( 'cachewarmer_custom_viewports', '' );
            if ( ! empty( $raw ) ) {
                foreach ( explode( "\n", $raw ) as $line ) {
                    $line = trim( $line );
                    if ( preg_match( '/^(\d+)x(\d+)(?:\s+(.+))?$/', $line, $m ) ) {
                        $this->custom_viewports[] = array(
                            'width'  => (int) $m[1],
                            'height' => (int) $m[2],
                            'label'  => isset( $m[3] ) ? trim( $m[3] ) : "{$m[1]}x{$m[2]}",
                        );
                    }
                }
            }
        }

        // Enterprise: authenticated warming cookies
        $this->auth_cookies = array();
        if ( CacheWarmer_License::can( 'authenticated_warming' ) ) {
            $raw = get_option( 'cachewarmer_auth_cookies', '' );
            if ( ! empty( $raw ) ) {
                $decoded = json_decode( $raw, true );
                if ( is_array( $decoded ) ) {
                    $this->auth_cookies = $decoded;
                }
            }
        }
    }

    /**
     * Warm a batch of URLs.
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

                // Custom viewport requests (Enterprise).
                foreach ( $this->custom_viewports as $vp ) {
                    $result = $this->fetch_url( $url, $this->desktop_ua, $vp['label'] );
                    $results[] = $result;
                    if ( $on_result ) {
                        $on_result( $result );
                    }
                }
            }
        }

        return $results;
    }

    private function fetch_url( string $url, string $user_agent, string $viewport ): array {
        $start = microtime( true );

        $headers = array(
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control'   => 'no-cache',
        );

        // Merge custom headers (Enterprise).
        if ( ! empty( $this->custom_headers ) ) {
            $headers = array_merge( $headers, $this->custom_headers );
        }

        $args = array(
            'timeout'    => $this->timeout,
            'user-agent' => $user_agent,
            'sslverify'  => true,
            'headers'    => $headers,
        );

        // Add auth cookies (Enterprise).
        if ( ! empty( $this->auth_cookies ) ) {
            $cookie_strings = array();
            foreach ( $this->auth_cookies as $cookie ) {
                if ( isset( $cookie['name'], $cookie['value'] ) ) {
                    $cookie_strings[] = $cookie['name'] . '=' . $cookie['value'];
                }
            }
            if ( ! empty( $cookie_strings ) ) {
                $args['headers']['Cookie'] = implode( '; ', $cookie_strings );
            }
        }

        $response = wp_remote_get( $url, $args );

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

        $cache_headers = array_filter( array(
            'xCache'        => wp_remote_retrieve_header( $response, 'x-cache' ) ?: null,
            'cfCacheStatus' => wp_remote_retrieve_header( $response, 'cf-cache-status' ) ?: null,
            'age'           => wp_remote_retrieve_header( $response, 'age' ) ?: null,
            'cacheControl'  => wp_remote_retrieve_header( $response, 'cache-control' ) ?: null,
        ) );

        return array(
            'url'           => $url,
            'target'        => 'cdn',
            'status'        => ( $http_status >= 200 && $http_status < 400 ) ? 'success' : 'failed',
            'http_status'   => $http_status,
            'duration_ms'   => $duration_ms,
            'error'         => ( $http_status >= 400 ) ? "HTTP $http_status" : null,
            'viewport'      => $viewport,
            'cache_headers' => ! empty( $cache_headers ) ? $cache_headers : null,
        );
    }
}
