<?php
/**
 * Facebook Sharing Debugger cache warming service.
 *
 * Uses the Facebook Graph API to trigger OG tag scraping/caching.
 * Endpoint: POST https://graph.facebook.com/v19.0/?scrape=true&id={URL}
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Facebook_Warmer {

    private string $app_id;
    private string $app_secret;
    private int $rate_limit_per_second;

    public function __construct() {
        $this->app_id               = get_option( 'cachewarmer_facebook_app_id', '' );
        $this->app_secret           = get_option( 'cachewarmer_facebook_app_secret', '' );
        $this->rate_limit_per_second = (int) get_option( 'cachewarmer_facebook_rate_limit', 10 );
    }

    public function is_configured(): bool {
        return ! empty( $this->app_id ) && ! empty( $this->app_secret );
    }

    /**
     * Warm Facebook caches for a list of URLs.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID.
     * @param callable|null $on_result Callback per result.
     * @return array Results.
     */
    public function warm( array $urls, string $job_id, ?callable $on_result = null ): array {
        if ( ! $this->is_configured() ) {
            return $this->skip_all( $urls, 'Facebook App ID/Secret not configured', $on_result );
        }

        $access_token = $this->app_id . '|' . $this->app_secret;
        $results      = array();
        $delay_us     = (int) ( 1000000 / max( 1, $this->rate_limit_per_second ) );

        foreach ( $urls as $url ) {
            $start = microtime( true );

            $response = wp_remote_post( 'https://graph.facebook.com/v19.0/', array(
                'timeout' => 30,
                'body'    => array(
                    'scrape'       => 'true',
                    'id'           => $url,
                    'access_token' => $access_token,
                ),
            ) );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'facebook',
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
                    'target'      => 'facebook',
                    'status'      => ( $http_status === 200 && ! isset( $body['error'] ) ) ? 'success' : 'failed',
                    'http_status' => $http_status,
                    'duration_ms' => $duration_ms,
                    'error'       => $body['error']['message'] ?? ( $http_status !== 200 ? "HTTP $http_status" : null ),
                );
            }

            $results[] = $result;
            if ( $on_result ) {
                $on_result( $result );
            }

            usleep( $delay_us );
        }

        return $results;
    }

    private function skip_all( array $urls, string $reason, ?callable $on_result ): array {
        $results = array();
        foreach ( $urls as $url ) {
            $result = array(
                'url'         => $url,
                'target'      => 'facebook',
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
