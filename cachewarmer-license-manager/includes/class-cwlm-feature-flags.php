<?php
/**
 * Feature-Flags: Tier-basierte Feature-Zuordnung.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Feature_Flags {

    /**
     * Standard Feature-Flags pro Tier.
     *
     * @var array<string, array<string, mixed>>
     */
    private const TIER_FEATURES = [
        'free' => [
            'cdn_warming'           => true,
            'cdn_puppeteer'         => false,
            'social_facebook'       => false,
            'social_linkedin'       => false,
            'social_twitter'        => false,
            'indexnow'              => false,
            'google_search_console' => false,
            'bing_webmaster'        => false,
            'scheduling'            => false,
            'max_sitemaps'          => 1,
            'max_urls'              => 50,
            'max_workers'           => 1,
            'diff_detection'        => false,
            'multi_site'            => false,
            'screenshots'           => false,
            'lighthouse'            => false,
            'webhooks'              => false,
            'cloudflare'            => false,
            'imperva'               => false,
            'akamai'                => false,
            'whitelabel'            => false,
            'priority_support'      => false,
        ],
        'professional' => [
            'cdn_warming'           => true,
            'cdn_puppeteer'         => true,
            'social_facebook'       => true,
            'social_linkedin'       => true,
            'social_twitter'        => true,
            'indexnow'              => true,
            'google_search_console' => false,
            'bing_webmaster'        => false,
            'scheduling'            => true,
            'max_sitemaps'          => 5,
            'max_urls'              => 5000,
            'max_workers'           => 5,
            'diff_detection'        => true,
            'multi_site'            => false,
            'screenshots'           => false,
            'lighthouse'            => false,
            'webhooks'              => false,
            'cloudflare'            => false,
            'imperva'               => false,
            'akamai'                => false,
            'whitelabel'            => false,
            'priority_support'      => false,
        ],
        'enterprise' => [
            'cdn_warming'           => true,
            'cdn_puppeteer'         => true,
            'social_facebook'       => true,
            'social_linkedin'       => true,
            'social_twitter'        => true,
            'indexnow'              => true,
            'google_search_console' => true,
            'bing_webmaster'        => true,
            'scheduling'            => true,
            'max_sitemaps'          => -1, // unbegrenzt
            'max_urls'              => -1,
            'max_workers'           => 10,
            'diff_detection'        => true,
            'multi_site'            => true,
            'screenshots'           => true,
            'lighthouse'            => true,
            'webhooks'              => true,
            'cloudflare'            => true,
            'imperva'               => true,
            'akamai'                => true,
            'whitelabel'            => true,
            'priority_support'      => true,
        ],
        'development' => [
            'cdn_warming'           => true,
            'cdn_puppeteer'         => true,
            'social_facebook'       => true,
            'social_linkedin'       => true,
            'social_twitter'        => true,
            'indexnow'              => true,
            'google_search_console' => true,
            'bing_webmaster'        => true,
            'scheduling'            => true,
            'max_sitemaps'          => -1,
            'max_urls'              => -1,
            'max_workers'           => 10,
            'diff_detection'        => true,
            'multi_site'            => true,
            'screenshots'           => true,
            'lighthouse'            => true,
            'webhooks'              => true,
            'cloudflare'            => true,
            'imperva'               => true,
            'akamai'                => true,
            'whitelabel'            => true,
            'priority_support'      => false,
        ],
    ];

    /**
     * Features für eine Lizenz ermitteln (Tier + Override).
     *
     * @return array<string, mixed>
     */
    public function get_features( object $license ): array {
        $tier     = $license->tier ?? 'free';
        $defaults = self::TIER_FEATURES[ $tier ] ?? self::TIER_FEATURES['free'];

        // Individuelle Overrides anwenden
        if ( ! empty( $license->features_json ) ) {
            $overrides = json_decode( $license->features_json, true );
            if ( is_array( $overrides ) ) {
                $defaults = array_merge( $defaults, $overrides );
            }
        }

        return $defaults;
    }

    /**
     * Prüfe ob ein bestimmtes Feature freigeschaltet ist.
     */
    public function has_feature( object $license, string $feature ): bool {
        $features = $this->get_features( $license );
        return ! empty( $features[ $feature ] );
    }

    /**
     * Prüfe ob eine Domain als Entwicklungsdomäne gilt.
     */
    public static function is_development_domain( string $domain ): bool {
        $dev_domains = defined( 'CWLM_DEV_DOMAINS' )
            ? array_map( 'trim', explode( ',', CWLM_DEV_DOMAINS ) )
            : [ 'localhost', '*.local', '*.dev', '*.test', '127.0.0.1' ];

        foreach ( $dev_domains as $pattern ) {
            if ( $pattern === $domain ) {
                return true;
            }
            // Wildcard-Matching (*.local)
            if ( str_starts_with( $pattern, '*.' ) ) {
                $suffix = substr( $pattern, 1 ); // .local
                if ( str_ends_with( $domain, $suffix ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Standard-Features für einen Tier zurückgeben.
     *
     * @return array<string, mixed>
     */
    public static function get_tier_defaults( string $tier ): array {
        return self::TIER_FEATURES[ $tier ] ?? self::TIER_FEATURES['free'];
    }
}
