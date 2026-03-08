<?php
/**
 * Bing Webmaster Tools URL Submission API service.
 *
 * Submits URLs in batches to Bing for indexing.
 * Endpoint: POST https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch
 * Daily limit: 10,000 URLs (standard).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Bing_Indexer {

    private const BATCH_SIZE    = 500;
    private const API_ENDPOINT  = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch';

    private string $api_key;
    private int $daily_quota;

    public function __construct() {
        $this->api_key     = get_option( 'cachewarmer_bing_api_key', '' );
        $this->daily_quota = (int) get_option( 'cachewarmer_bing_daily_quota', 10000 );
    }

    public function is_configured(): bool {
        return ! empty( $this->api_key );
    }

    /**
     * Submit URLs to Bing Webmaster Tools.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID.
     * @param callable|null $on_result Callback per batch result.
     * @return array Results.
     */
    public function index( array $urls, string $job_id, ?callable $on_result = null ): array {
        if ( ! $this->is_configured() ) {
            return $this->skip_all( $urls, 'Bing API key not configured', $on_result );
        }

        if ( empty( $urls ) ) {
            return array();
        }

        // Respect daily quota.
        $urls_to_process = array_slice( $urls, 0, $this->daily_quota );
        $skipped_urls    = array_slice( $urls, $this->daily_quota );

        // Extract site URL from first URL.
        $parsed  = wp_parse_url( $urls_to_process[0] );
        $site_url = $parsed['scheme'] . '://' . $parsed['host'];

        $results = array();

        foreach ( array_chunk( $urls_to_process, self::BATCH_SIZE ) as $batch ) {
            $start = microtime( true );

            $response = wp_remote_post(
                self::API_ENDPOINT . '?apikey=' . rawurlencode( $this->api_key ),
                array(
                    'timeout' => 30,
                    'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
                    'body'    => wp_json_encode( array(
                        'siteUrl' => $site_url,
                        'urlList' => array_values( $batch ),
                    ) ),
                )
            );

            $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

            if ( is_wp_error( $response ) ) {
                foreach ( $batch as $url ) {
                    $result = array(
                        'url'         => $url,
                        'target'      => 'bing',
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
            $success     = ( $http_status >= 200 && $http_status < 300 );

            foreach ( $batch as $url ) {
                $result = array(
                    'url'         => $url,
                    'target'      => 'bing',
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

        // Mark quota-exceeded URLs as skipped.
        foreach ( $skipped_urls as $url ) {
            $result = array(
                'url'         => $url,
                'target'      => 'bing',
                'status'      => 'skipped',
                'http_status' => null,
                'duration_ms' => 0,
                'error'       => 'Daily quota exceeded',
            );
            $results[] = $result;
            if ( $on_result ) {
                $on_result( $result );
            }
        }

        return $results;
    }

    private function skip_all( array $urls, string $reason, ?callable $on_result ): array {
        $results = array();
        foreach ( $urls as $url ) {
            $result = array(
                'url'         => $url,
                'target'      => 'bing',
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
