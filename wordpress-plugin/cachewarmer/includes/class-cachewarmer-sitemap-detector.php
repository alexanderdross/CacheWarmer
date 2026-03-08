<?php
/**
 * Sitemap auto-detection.
 *
 * Probes common sitemap locations to discover available sitemaps.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Sitemap_Detector {

    /**
     * Detect available sitemaps on the current site.
     *
     * Checks common sitemap URLs (WordPress core, Yoast, Rank Math, etc.)
     * and returns those that respond with a 200 status and XML content.
     *
     * @return array List of discovered sitemap URLs.
     */
    public static function detect(): array {
        $found    = array();
        $site_url = get_site_url();

        $candidates = array(
            $site_url . '/sitemap.xml',
            $site_url . '/sitemap_index.xml',
            $site_url . '/wp-sitemap.xml',         // WordPress core sitemap.
            $site_url . '/sitemap-index.xml',       // Rank Math.
        );

        $candidates = array_unique( $candidates );

        foreach ( $candidates as $url ) {
            $response = wp_remote_head( $url, array(
                'timeout'   => 5,
                'sslverify' => false,
            ) );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
                continue;
            }

            $content_type = wp_remote_retrieve_header( $response, 'content-type' );
            if ( strpos( $content_type, 'xml' ) !== false || strpos( $content_type, 'text' ) !== false ) {
                $found[] = $url;
            }
        }

        return $found;
    }
}
