<?php
/**
 * XML Sitemap parser.
 *
 * Parses <urlset> and <sitemapindex> formats recursively.
 * Extracts <loc>, <lastmod>, <priority>, <changefreq>.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Sitemap_Parser {

    private int $max_depth;
    private int $timeout;

    public function __construct( int $max_depth = 3, int $timeout = 30 ) {
        $this->max_depth = $max_depth;
        $this->timeout   = $timeout;
    }

    /**
     * Parse a sitemap URL and return all contained page URLs.
     *
     * @param string $url Sitemap URL.
     * @return array Array of [ 'loc' => string, 'lastmod' => ?string, 'priority' => ?string, 'changefreq' => ?string ].
     */
    public function parse( string $url ): array {
        $urls = array();
        $seen = array();
        $this->parse_recursive( $url, $urls, $seen, 0 );
        return $urls;
    }

    private function parse_recursive( string $url, array &$urls, array &$seen, int $depth ): void {
        if ( $depth >= $this->max_depth ) {
            return;
        }

        if ( isset( $seen[ $url ] ) ) {
            return;
        }
        $seen[ $url ] = true;

        $response = wp_remote_get( $url, array(
            'timeout'    => $this->timeout,
            'user-agent' => 'CacheWarmer/1.0 (WordPress)',
        ) );

        if ( is_wp_error( $response ) ) {
            return;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return;
        }

        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        if ( false === $xml ) {
            return;
        }

        // Register common namespaces.
        $namespaces = $xml->getNamespaces( true );
        $ns         = '';
        if ( isset( $namespaces[''] ) ) {
            $xml->registerXPathNamespace( 'sm', $namespaces[''] );
            $ns = 'sm:';
        }

        // Check for sitemapindex (recursive).
        $sitemaps = $xml->xpath( "//{$ns}sitemapindex/{$ns}sitemap/{$ns}loc" );
        if ( ! empty( $sitemaps ) ) {
            foreach ( $sitemaps as $sitemap_loc ) {
                $sitemap_url = trim( (string) $sitemap_loc );
                if ( filter_var( $sitemap_url, FILTER_VALIDATE_URL ) ) {
                    $this->parse_recursive( $sitemap_url, $urls, $seen, $depth + 1 );
                }
            }
            return;
        }

        // Parse urlset.
        $url_nodes = $xml->xpath( "//{$ns}urlset/{$ns}url" );
        if ( empty( $url_nodes ) ) {
            // Try without namespace prefix as fallback.
            $url_nodes = $xml->xpath( '//url' );
        }

        if ( ! empty( $url_nodes ) ) {
            foreach ( $url_nodes as $node ) {
                $loc = null;
                if ( $ns && isset( $node->children( $namespaces[''] )->loc ) ) {
                    $loc = trim( (string) $node->children( $namespaces[''] )->loc );
                } elseif ( isset( $node->loc ) ) {
                    $loc = trim( (string) $node->loc );
                }

                if ( ! $loc || ! filter_var( $loc, FILTER_VALIDATE_URL ) ) {
                    continue;
                }

                if ( isset( $seen[ $loc ] ) ) {
                    continue;
                }
                $seen[ $loc ] = true;

                $entry = array( 'loc' => $loc );

                // Optional fields.
                $lastmod = $ns ? ( $node->children( $namespaces[''] )->lastmod ?? null ) : ( $node->lastmod ?? null );
                if ( $lastmod ) {
                    $entry['lastmod'] = trim( (string) $lastmod );
                }

                $priority = $ns ? ( $node->children( $namespaces[''] )->priority ?? null ) : ( $node->priority ?? null );
                if ( $priority ) {
                    $entry['priority'] = trim( (string) $priority );
                }

                $changefreq = $ns ? ( $node->children( $namespaces[''] )->changefreq ?? null ) : ( $node->changefreq ?? null );
                if ( $changefreq ) {
                    $entry['changefreq'] = trim( (string) $changefreq );
                }

                $urls[] = $entry;
            }
        }
    }
}
