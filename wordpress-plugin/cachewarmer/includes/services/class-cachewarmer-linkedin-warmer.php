<?php
/**
 * LinkedIn Post Inspector cache warming service.
 *
 * Calls LinkedIn's Post Inspector endpoint to trigger OG tag scraping.
 * Requires a valid li_at session cookie.
 *
 * Note: WordPress cannot run Puppeteer, so this uses HTTP requests
 * to the Post Inspector API endpoint that the inspector page calls internally.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_LinkedIn_Warmer {

    private string $session_cookie;
    private int $delay_ms;

    public function __construct() {
        $this->session_cookie = get_option( 'cachewarmer_linkedin_session_cookie', '' );
        $this->delay_ms       = (int) get_option( 'cachewarmer_linkedin_delay', 5000 );
    }

    public function is_configured(): bool {
        return ! empty( $this->session_cookie );
    }

    /**
     * Warm LinkedIn caches.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID.
     * @param callable|null $on_result Callback per result.
     * @return array Results.
     */
    public function warm( array $urls, string $job_id, ?callable $on_result = null ): array {
        if ( ! $this->is_configured() ) {
            return $this->skip_all( $urls, 'LinkedIn session cookie not configured', $on_result );
        }

        $results = array();

        foreach ( $urls as $url ) {
            $start = microtime( true );

            // LinkedIn Post Inspector uses an internal API endpoint.
            $inspector_url = 'https://www.linkedin.com/post-inspector/api/inspect';

            $response = wp_remote_post( $inspector_url, array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Cookie'       => 'li_at=' . $this->session_cookie,
                    'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'csrf-token'   => 'ajax:0',
                    'X-Li-Lang'    => 'en_US',
                    'X-Restli-Protocol-Version' => '2.0.0',
                ),
                'body' => http_build_query( array( 'url' => $url ) ),
            ) );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'linkedin',
                    'status'      => 'failed',
                    'http_status' => null,
                    'duration_ms' => $duration_ms,
                    'error'       => $response->get_error_message(),
                );
            } else {
                $http_status = wp_remote_retrieve_response_code( $response );
                $result      = array(
                    'url'         => $url,
                    'target'      => 'linkedin',
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

            if ( $this->delay_ms > 0 ) {
                usleep( $this->delay_ms * 1000 );
            }
        }

        return $results;
    }

    private function skip_all( array $urls, string $reason, ?callable $on_result ): array {
        $results = array();
        foreach ( $urls as $url ) {
            $result = array(
                'url'         => $url,
                'target'      => 'linkedin',
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
