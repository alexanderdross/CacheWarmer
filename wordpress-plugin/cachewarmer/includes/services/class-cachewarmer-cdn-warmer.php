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
     * The passes made for every URL, in order.
     *
     * Order matters: the desktop pass fills the cache and the ones after it
     * should find it warm. Passes are therefore run one round at a time, with
     * the URLs inside a round running in parallel — never the other way round.
     */
    private function passes(): array {
        $passes = array(
            array( 'ua' => $this->desktop_ua, 'viewport' => 'desktop' ),
            array( 'ua' => $this->mobile_ua, 'viewport' => 'mobile' ),
        );

        // Enterprise: custom viewports.
        foreach ( $this->custom_viewports as $vp ) {
            $passes[] = array( 'ua' => $this->desktop_ua, 'viewport' => $vp['label'] );
        }

        return $passes;
    }

    /**
     * Warm a batch of URLs.
     *
     * Previously this chunked the URLs by the concurrency setting and then
     * walked each chunk with a plain foreach, so the chunking had no effect
     * and every request was sequential — the "CDN Concurrency" setting did
     * nothing at all. Each round of URLs now goes out in parallel.
     *
     * The first pass fills the cache and every later pass acts as a probe, so
     * a warm can be shown to have worked without any extra requests.
     */
    public function warm( array $urls, string $job_id, ?callable $on_result = null ): array {
        $results     = array();
        $concurrency = max( 1, $this->concurrency );

        foreach ( array_chunk( $urls, $concurrency ) as $batch ) {
            $fills = array();

            foreach ( $this->passes() as $index => $pass ) {
                foreach ( $this->fetch_batch( $batch, $pass['ua'], $pass['viewport'] ) as $result ) {
                    $url = $result['url'];

                    if ( 0 === $index ) {
                        $fills[ $url ] = $result['cache_headers'] ?? array();
                    } elseif ( ! empty( $result['cache_headers'] ) ) {
                        $verdict = self::classify_cache_verdict(
                            $fills[ $url ] ?? array(),
                            $result['cache_headers'],
                            $result['cache_headers']['vary'] ?? null
                        );
                        $result['cache_headers']['verdict'] = $verdict['verdict'];
                        if ( isset( $verdict['reason'] ) ) {
                            $result['cache_headers']['verdictReason'] = $verdict['reason'];
                        }
                    }

                    $results[] = $result;
                    if ( $on_result ) {
                        $on_result( $result );
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Reduce a CDN cache header to a coarse state.
     */
    private static function cache_state( array $headers ): string {
        // Fastly reports both tiers comma-separated ("MISS, HIT"); only the
        // last segment describes the edge that answered us.
        $source = $headers['cfCacheStatus'] ?? $headers['xCache'] ?? '';
        $parts  = explode( ',', $source );
        $raw    = strtoupper( trim( (string) end( $parts ) ) );
        if ( '' === $raw ) {
            return (int) ( $headers['age'] ?? 0 ) > 0 ? 'hit' : 'unknown';
        }
        if ( str_contains( $raw, 'BYPASS' ) ) {
            return 'bypass';
        }
        if ( str_contains( $raw, 'DYNAMIC' ) ) {
            return 'dynamic';
        }
        // REVALIDATED, STALE and UPDATING are all served from cache.
        if ( str_contains( $raw, 'HIT' ) || str_contains( $raw, 'REVALIDATED' )
            || str_contains( $raw, 'STALE' ) || str_contains( $raw, 'UPDATING' ) ) {
            return 'hit';
        }
        if ( str_contains( $raw, 'MISS' ) || str_contains( $raw, 'EXPIRED' ) ) {
            return 'miss';
        }
        return 'unknown';
    }

    /**
     * Judge a fill/probe pair.
     *
     * For a warmer, MISS on the fill is the success signal — the request
     * populated the cache. HIT there means it was already warm and the run
     * changed nothing.
     *
     * @return array{verdict:string,reason?:string}
     */
    public static function classify_cache_verdict( array $fill, array $probe, ?string $probe_vary = null ): array {
        // The passes send different user agents. If the origin varies on that
        // header they address separate cache entries, so the pair proves
        // nothing either way.
        if ( ! empty( $probe_vary ) && false !== stripos( $probe_vary, 'user-agent' ) ) {
            return array(
                'verdict' => 'indeterminate',
                'reason'  => 'Origin sends Vary: User-Agent, so the two passes are separate cache entries',
            );
        }

        $fill_state  = self::cache_state( $fill );
        $probe_state = self::cache_state( $probe );

        if ( 'bypass' === $fill_state || 'bypass' === $probe_state ) {
            return array(
                'verdict' => 'bypassed',
                'reason'  => $probe['cacheControl'] ?? 'Cache bypassed',
            );
        }
        if ( 'dynamic' === $probe_state ) {
            return array(
                'verdict' => 'zone_not_caching',
                'reason'  => 'CDN reports the response as DYNAMIC',
            );
        }
        if ( 'hit' === $probe_state ) {
            return array( 'verdict' => 'hit' === $fill_state ? 'already_warm' : 'warmed' );
        }
        if ( 'miss' === $probe_state ) {
            $cache_control = strtolower( $probe['cacheControl'] ?? '' );
            if ( str_contains( $cache_control, 'no-store' ) ) {
                return array( 'verdict' => 'not_cacheable', 'reason' => 'Cache-Control: no-store' );
            }
            if ( str_contains( $cache_control, 'private' ) ) {
                return array( 'verdict' => 'not_cacheable', 'reason' => 'Cache-Control: private' );
            }
            return array( 'verdict' => 'not_cacheable', 'reason' => 'Still a miss after the fill request' );
        }

        return array( 'verdict' => 'unknown' );
    }

    /**
     * Fetch a set of URLs concurrently.
     *
     * Uses the Requests library WordPress ships with, since the WP HTTP API
     * exposes no parallel mode. Falls back to sequential wp_remote_get() when
     * only one URL is in flight or Requests cannot be resolved, so behaviour
     * degrades rather than breaks.
     */
    private function fetch_batch( array $urls, string $user_agent, string $viewport ): array {
        $requests_class = $this->requests_class();

        // Going through Requests directly bypasses WP_Http, and with it
        // WP_HTTP_BLOCK_EXTERNAL, pre_http_request, http_request_args and
        // https_ssl_verify. Where a site relies on any of those, correctness
        // matters more than parallelism — fall back to wp_remote_get.
        if ( $this->must_use_wp_http() ) {
            $requests_class = null;
        }

        if ( count( $urls ) < 2 || null === $requests_class ) {
            $results = array();
            foreach ( $urls as $url ) {
                $results[] = $this->fetch_url( $url, $user_agent, $viewport );
            }
            return $results;
        }

        $start   = microtime( true );
        $options = $this->request_options( $user_agent );

        // Records when each response lands, so a slow URL is not reported with
        // the same duration as a fast one that shared its round.
        $completed = array();
        $hooks     = $this->build_hooks( $start, $completed );
        if ( null !== $hooks ) {
            $options['hooks'] = $hooks;
        }

        $requests = array();
        foreach ( array_values( $urls ) as $index => $url ) {
            $requests[ $index ] = array(
                'url'     => $url,
                'headers' => $this->request_headers(),
                'type'    => 'GET',
                'options' => $options,
            );
        }

        try {
            $responses = $requests_class::request_multiple( $requests, $options );
        } catch ( \Throwable $e ) {
            // A failure here is about the batch, not any one URL; fall back so
            // the job still makes progress.
            $results = array();
            foreach ( $urls as $url ) {
                $results[] = $this->fetch_url( $url, $user_agent, $viewport );
            }
            return $results;
        }

        $batch_ms = (int) ( ( microtime( true ) - $start ) * 1000 );
        $results  = array();

        foreach ( array_values( $urls ) as $index => $url ) {
            $response    = $responses[ $index ] ?? null;
            $duration_ms = $completed[ $index ] ?? $batch_ms;

            if ( $response instanceof \Throwable ) {
                $results[] = array(
                    'url'         => $url,
                    'target'      => 'cdn',
                    'status'      => 'failed',
                    'http_status' => null,
                    'duration_ms' => $duration_ms,
                    'error'       => $response->getMessage(),
                    'viewport'    => $viewport,
                );
                continue;
            }

            if ( ! is_object( $response ) || ! isset( $response->status_code ) ) {
                $results[] = array(
                    'url'         => $url,
                    'target'      => 'cdn',
                    'status'      => 'failed',
                    'http_status' => null,
                    'duration_ms' => $duration_ms,
                    'error'       => 'No response returned for this URL',
                    'viewport'    => $viewport,
                );
                continue;
            }

            $http_status = (int) $response->status_code;

            $cache_headers = array_filter( array(
                'xCache'        => $this->header_from( $response, 'x-cache' ),
                'cfCacheStatus' => $this->header_from( $response, 'cf-cache-status' ),
                'age'           => $this->header_from( $response, 'age' ),
                'cacheControl'  => $this->header_from( $response, 'cache-control' ),
                'vary'          => $this->header_from( $response, 'vary' ),
            ) );

            $results[] = array(
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

        return $results;
    }

    /**
     * Whether this install depends on WP_Http behaviour the concurrent path
     * cannot honour.
     *
     * WP_HTTP_BLOCK_EXTERNAL is a hard rule — silently ignoring it would let
     * the warmer reach hosts the site owner has forbidden. The filters are
     * checked because a site that hooks them expects them to apply.
     */
    private function must_use_wp_http(): bool {
        if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
            return true;
        }
        foreach ( array( 'pre_http_request', 'http_request_args', 'https_ssl_verify' ) as $filter ) {
            if ( function_exists( 'has_filter' ) && has_filter( $filter ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * WordPress 6.2 moved Requests into a namespace and kept the old name as
     * a deprecated shim, so both spellings have to be tolerated.
     */
    private function requests_class(): ?string {
        if ( class_exists( '\WpOrg\Requests\Requests' ) ) {
            return '\WpOrg\Requests\Requests';
        }
        if ( class_exists( 'Requests' ) ) {
            return 'Requests';
        }
        return null;
    }

    /**
     * Hooks object used to timestamp each completed response.
     *
     * Optional: without it every URL in a round reports the round's duration.
     */
    private function build_hooks( float $start, array &$completed ) {
        $class = null;
        if ( class_exists( '\WpOrg\Requests\Hooks' ) ) {
            $class = '\WpOrg\Requests\Hooks';
        } elseif ( class_exists( 'Requests_Hooks' ) ) {
            $class = 'Requests_Hooks';
        }
        if ( null === $class ) {
            return null;
        }

        $hooks = new $class();
        $hooks->register(
            'multiple.request.complete',
            static function ( $response, $key ) use ( $start, &$completed ) {
                $completed[ $key ] = (int) ( ( microtime( true ) - $start ) * 1000 );
            }
        );

        return $hooks;
    }

    /**
     * Requests options mirroring what wp_remote_get() would have applied —
     * timeout, certificate bundle and any configured WordPress proxy.
     */
    private function request_options( string $user_agent ): array {
        $options = array(
            'timeout'          => $this->timeout,
            'connect_timeout'  => $this->timeout,
            'useragent'        => $user_agent,
            'follow_redirects' => true,
            'redirects'        => 5,
            'verify'           => true,
        );

        if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
            $ca_bundle = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
            if ( file_exists( $ca_bundle ) ) {
                $options['verify'] = $ca_bundle;
            }
        }

        // Requests bypasses the WP HTTP API, so a configured proxy has to be
        // passed through explicitly or proxied installs lose outbound access.
        if ( class_exists( 'WP_HTTP_Proxy' ) ) {
            $proxy = new WP_HTTP_Proxy();
            if ( $proxy->is_enabled() && $proxy->send_through_proxy( 'https://example.com' ) ) {
                $options['proxy'] = $proxy->authentication_enabled()
                    ? array( $proxy->host() . ':' . $proxy->port(), $proxy->username(), $proxy->password() )
                    : $proxy->host() . ':' . $proxy->port();
            }
        }

        return $options;
    }

    private function request_headers(): array {
        $headers = array(
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control'   => 'no-cache',
        );

        // Merge custom headers (Enterprise).
        if ( ! empty( $this->custom_headers ) ) {
            $headers = array_merge( $headers, $this->custom_headers );
        }

        // Add auth cookies (Enterprise).
        $cookie_header = $this->cookie_header();
        if ( null !== $cookie_header ) {
            $headers['Cookie'] = $cookie_header;
        }

        return $headers;
    }

    private function cookie_header(): ?string {
        if ( empty( $this->auth_cookies ) ) {
            return null;
        }

        $cookie_strings = array();
        foreach ( $this->auth_cookies as $cookie ) {
            if ( isset( $cookie['name'], $cookie['value'] ) ) {
                $cookie_strings[] = $cookie['name'] . '=' . $cookie['value'];
            }
        }

        return empty( $cookie_strings ) ? null : implode( '; ', $cookie_strings );
    }

    /**
     * Read a header off a Requests response. The headers object is
     * case-insensitive but returns null for anything absent.
     */
    private function header_from( object $response, string $name ): ?string {
        if ( ! isset( $response->headers ) ) {
            return null;
        }
        $value = $response->headers[ $name ] ?? null;
        return ( null === $value || '' === $value ) ? null : (string) $value;
    }

    /**
     * Sequential single-URL fetch, used when there is nothing to parallelise
     * or when Requests is unavailable. Shares its header construction with the
     * concurrent path so the two cannot drift apart.
     */
    private function fetch_url( string $url, string $user_agent, string $viewport ): array {
        $start = microtime( true );

        $args = array(
            'timeout'    => $this->timeout,
            'user-agent' => $user_agent,
            'sslverify'  => true,
            'headers'    => $this->request_headers(),
        );

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
            'vary'          => wp_remote_retrieve_header( $response, 'vary' ) ?: null,
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
