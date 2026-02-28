<?php
/**
 * Twitter/X Card cache warming service.
 *
 * Uses Twitter's Card Validator API to trigger card scraping.
 * Falls back to fetching the tweet composer endpoint which
 * triggers Twitter's card scraper automatically.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Twitter_Warmer {

    private int $concurrency;
    private int $delay_ms;
    private int $timeout;

    public function __construct() {
        $this->concurrency = (int) get_option( 'cachewarmer_twitter_concurrency', 2 );
        $this->delay_ms    = (int) get_option( 'cachewarmer_twitter_delay', 3000 );
        $this->timeout     = 15;
    }

    /**
     * Warm Twitter card caches.
     *
     * Requests the tweet composer URL for each page which triggers
     * Twitter's card scraper. No API key needed.
     *
     * @param array         $urls      Array of URL strings.
     * @param string        $job_id    Job ID.
     * @param callable|null $on_result Callback per result.
     * @return array Results.
     */
    public function warm( array $urls, string $job_id, ?callable $on_result = null ): array {
        $results = array();

        foreach ( array_chunk( $urls, $this->concurrency ) as $batch ) {
            foreach ( $batch as $url ) {
                $result = $this->warm_single( $url );
                $results[] = $result;
                if ( $on_result ) {
                    $on_result( $result );
                }
            }

            // Delay between batches.
            if ( $this->delay_ms > 0 ) {
                usleep( $this->delay_ms * 1000 );
            }
        }

        return $results;
    }

    private function warm_single( string $url ): array {
        $start = microtime( true );

        // The tweet composer endpoint triggers Twitter's card scraper.
        $composer_url = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url );

        $response = wp_remote_get( $composer_url, array(
            'timeout'    => $this->timeout,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'sslverify'  => false,
        ) );

        $duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

        if ( is_wp_error( $response ) ) {
            return array(
                'url'         => $url,
                'target'      => 'twitter',
                'status'      => 'failed',
                'http_status' => null,
                'duration_ms' => $duration_ms,
                'error'       => $response->get_error_message(),
            );
        }

        $http_status = wp_remote_retrieve_response_code( $response );

        return array(
            'url'         => $url,
            'target'      => 'twitter',
            'status'      => ( $http_status >= 200 && $http_status < 400 ) ? 'success' : 'failed',
            'http_status' => $http_status,
            'duration_ms' => $duration_ms,
            'error'       => ( $http_status >= 400 ) ? "HTTP $http_status" : null,
        );
    }
}
