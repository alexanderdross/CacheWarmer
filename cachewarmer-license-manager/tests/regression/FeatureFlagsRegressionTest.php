<?php
/**
 * Regression Tests: Sicherstellen, dass Feature-Flag-Änderungen
 * keine bestehende Tier-Zuordnung brechen.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FeatureFlagsRegressionTest extends TestCase {

    /**
     * Regression: Free-Tier darf NIEMALS Puppeteer, Social oder Search haben.
     * (Verhindert versehentliches Freischalten von Premium-Features)
     */
    public function test_free_tier_never_has_premium_features(): void {
        $features = CWLM_Feature_Flags::get_tier_defaults( 'free' );

        $premium_features = [
            'cdn_puppeteer', 'social_facebook', 'social_linkedin', 'social_twitter',
            'indexnow', 'google_search_console', 'bing_webmaster', 'scheduling',
            'diff_detection', 'multi_site', 'screenshots', 'lighthouse',
            'webhooks', 'cloudflare', 'whitelabel', 'priority_support',
        ];

        foreach ( $premium_features as $feature ) {
            $this->assertFalse(
                $features[ $feature ],
                "REGRESSION: Free-Tier hat unerwarteterweise '$feature' aktiviert!"
            );
        }
    }

    /**
     * Regression: Professional-Tier darf NICHT Google SC oder Bing haben.
     */
    public function test_professional_tier_no_enterprise_features(): void {
        $features = CWLM_Feature_Flags::get_tier_defaults( 'professional' );

        $enterprise_only = [
            'google_search_console', 'bing_webmaster', 'multi_site',
            'screenshots', 'lighthouse', 'webhooks', 'cloudflare',
            'whitelabel', 'priority_support',
        ];

        foreach ( $enterprise_only as $feature ) {
            $this->assertFalse(
                $features[ $feature ],
                "REGRESSION: Professional-Tier hat unerwarteterweise '$feature' aktiviert!"
            );
        }
    }

    /**
     * Regression: Enterprise-Tier MUSS alle 21 Features haben.
     */
    public function test_enterprise_tier_has_all_features(): void {
        $features = CWLM_Feature_Flags::get_tier_defaults( 'enterprise' );

        foreach ( $features as $key => $value ) {
            if ( is_bool( $value ) ) {
                $this->assertTrue( $value, "REGRESSION: Enterprise-Tier hat '$key' deaktiviert!" );
            }
        }
    }

    /**
     * Regression: Tier-Limits dürfen nicht unter Minimum fallen.
     */
    public function test_tier_limits_minimums(): void {
        $free = CWLM_Feature_Flags::get_tier_defaults( 'free' );
        $pro  = CWLM_Feature_Flags::get_tier_defaults( 'professional' );
        $ent  = CWLM_Feature_Flags::get_tier_defaults( 'enterprise' );

        // Free Limits
        $this->assertEquals( 1, $free['max_sitemaps'] );
        $this->assertEquals( 50, $free['max_urls'] );
        $this->assertEquals( 1, $free['max_workers'] );

        // Pro Limits
        $this->assertEquals( 5, $pro['max_sitemaps'] );
        $this->assertEquals( 5000, $pro['max_urls'] );
        $this->assertEquals( 5, $pro['max_workers'] );

        // Enterprise = unbegrenzt
        $this->assertEquals( -1, $ent['max_sitemaps'] );
        $this->assertEquals( -1, $ent['max_urls'] );
        $this->assertEquals( 10, $ent['max_workers'] );
    }

    /**
     * Regression: Feature-JSON-Overrides dürfen Core-Features nicht löschen.
     */
    public function test_json_overrides_dont_remove_existing_features(): void {
        $flags   = new CWLM_Feature_Flags();
        $license = (object) [
            'tier'          => 'enterprise',
            'features_json' => json_encode( [ 'custom_feature' => true ] ),
        ];

        $features = $flags->get_features( $license );

        // Alle Enterprise-Features müssen noch da sein
        $this->assertTrue( $features['cdn_warming'] );
        $this->assertTrue( $features['google_search_console'] );
        $this->assertTrue( $features['cloudflare'] );
        // Custom Feature hinzugefügt
        $this->assertTrue( $features['custom_feature'] );
    }

    /**
     * Regression: Alle 4 bekannten Tiers müssen existieren.
     */
    public function test_all_known_tiers_exist(): void {
        $expected_tiers = [ 'free', 'professional', 'enterprise', 'development' ];

        foreach ( $expected_tiers as $tier ) {
            $features = CWLM_Feature_Flags::get_tier_defaults( $tier );
            $this->assertNotEmpty(
                $features,
                "REGRESSION: Tier '$tier' existiert nicht mehr!"
            );
            $this->assertArrayHasKey( 'cdn_warming', $features );
        }
    }

    /**
     * Regression: Jeder Tier hat genau 20 Feature-Flags (nicht mehr, nicht weniger).
     */
    public function test_each_tier_has_expected_feature_count(): void {
        $tiers = [ 'free', 'professional', 'enterprise', 'development' ];

        foreach ( $tiers as $tier ) {
            $features = CWLM_Feature_Flags::get_tier_defaults( $tier );
            $this->assertCount(
                20,
                $features,
                "REGRESSION: Tier '$tier' hat " . count( $features ) . " statt 20 Features!"
            );
        }
    }
}
