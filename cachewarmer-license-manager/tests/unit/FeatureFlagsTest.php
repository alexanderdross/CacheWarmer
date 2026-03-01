<?php
/**
 * Unit Tests: Feature-Flags und Tier-Zuordnung.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FeatureFlagsTest extends TestCase {

    private CWLM_Feature_Flags $flags;

    protected function setUp(): void {
        $this->flags = new CWLM_Feature_Flags();
    }

    /**
     * Test: Free-Tier hat nur CDN-Warming (HTTP).
     */
    public function test_free_tier_features(): void {
        $license = (object) [ 'tier' => 'free', 'features_json' => null ];
        $features = $this->flags->get_features( $license );

        $this->assertTrue( $features['cdn_warming'] );
        $this->assertFalse( $features['cdn_puppeteer'] );
        $this->assertFalse( $features['social_facebook'] );
        $this->assertFalse( $features['social_linkedin'] );
        $this->assertFalse( $features['social_twitter'] );
        $this->assertFalse( $features['indexnow'] );
        $this->assertFalse( $features['google_search_console'] );
        $this->assertFalse( $features['bing_webmaster'] );
        $this->assertFalse( $features['scheduling'] );
        $this->assertEquals( 1, $features['max_sitemaps'] );
        $this->assertEquals( 50, $features['max_urls'] );
        $this->assertEquals( 1, $features['max_workers'] );
    }

    /**
     * Test: Professional-Tier hat Social Media + IndexNow.
     */
    public function test_professional_tier_features(): void {
        $license = (object) [ 'tier' => 'professional', 'features_json' => null ];
        $features = $this->flags->get_features( $license );

        $this->assertTrue( $features['cdn_warming'] );
        $this->assertTrue( $features['cdn_puppeteer'] );
        $this->assertTrue( $features['social_facebook'] );
        $this->assertTrue( $features['social_linkedin'] );
        $this->assertTrue( $features['social_twitter'] );
        $this->assertTrue( $features['indexnow'] );
        $this->assertFalse( $features['google_search_console'] );
        $this->assertFalse( $features['bing_webmaster'] );
        $this->assertTrue( $features['scheduling'] );
        $this->assertEquals( 5, $features['max_sitemaps'] );
        $this->assertEquals( 5000, $features['max_urls'] );
        $this->assertEquals( 5, $features['max_workers'] );
    }

    /**
     * Test: Enterprise-Tier hat alle Features.
     */
    public function test_enterprise_tier_all_features_enabled(): void {
        $license = (object) [ 'tier' => 'enterprise', 'features_json' => null ];
        $features = $this->flags->get_features( $license );

        $this->assertTrue( $features['cdn_warming'] );
        $this->assertTrue( $features['cdn_puppeteer'] );
        $this->assertTrue( $features['google_search_console'] );
        $this->assertTrue( $features['bing_webmaster'] );
        $this->assertTrue( $features['multi_site'] );
        $this->assertTrue( $features['webhooks'] );
        $this->assertTrue( $features['cloudflare'] );
        $this->assertTrue( $features['whitelabel'] );
        $this->assertTrue( $features['priority_support'] );
        $this->assertEquals( -1, $features['max_sitemaps'] ); // unbegrenzt
        $this->assertEquals( -1, $features['max_urls'] );
        $this->assertEquals( 10, $features['max_workers'] );
    }

    /**
     * Test: Development-Tier = Enterprise ohne Priority Support.
     */
    public function test_development_tier_equals_enterprise_minus_support(): void {
        $license_dev = (object) [ 'tier' => 'development', 'features_json' => null ];
        $license_ent = (object) [ 'tier' => 'enterprise', 'features_json' => null ];

        $dev_features = $this->flags->get_features( $license_dev );
        $ent_features = $this->flags->get_features( $license_ent );

        $this->assertFalse( $dev_features['priority_support'] );
        $this->assertTrue( $ent_features['priority_support'] );

        // Alle anderen Features identisch
        unset( $dev_features['priority_support'], $ent_features['priority_support'] );
        $this->assertEquals( $ent_features, $dev_features );
    }

    /**
     * Test: Individuelle Feature-Overrides per JSON.
     */
    public function test_features_json_overrides(): void {
        $license = (object) [
            'tier'          => 'free',
            'features_json' => json_encode( [ 'max_urls' => 200, 'scheduling' => true ] ),
        ];

        $features = $this->flags->get_features( $license );

        $this->assertEquals( 200, $features['max_urls'] );
        $this->assertTrue( $features['scheduling'] );
        // Nicht-überschriebene bleiben auf Free-Defaults
        $this->assertFalse( $features['cdn_puppeteer'] );
    }

    /**
     * Test: Unbekannter Tier fällt auf Free zurück.
     */
    public function test_unknown_tier_falls_back_to_free(): void {
        $license = (object) [ 'tier' => 'unknown_tier', 'features_json' => null ];
        $features = $this->flags->get_features( $license );

        $free_defaults = CWLM_Feature_Flags::get_tier_defaults( 'free' );
        $this->assertEquals( $free_defaults, $features );
    }

    /**
     * Test: has_feature() prüft einzelne Features.
     */
    public function test_has_feature(): void {
        $license = (object) [ 'tier' => 'professional', 'features_json' => null ];

        $this->assertTrue( $this->flags->has_feature( $license, 'cdn_puppeteer' ) );
        $this->assertFalse( $this->flags->has_feature( $license, 'google_search_console' ) );
    }

    /**
     * Test: Development-Domain Erkennung.
     */
    public function test_is_development_domain(): void {
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( 'localhost' ) );
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( 'myapp.local' ) );
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( 'test.dev' ) );
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( 'site.test' ) );
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( '127.0.0.1' ) );
        $this->assertFalse( CWLM_Feature_Flags::is_development_domain( 'example.com' ) );
        $this->assertFalse( CWLM_Feature_Flags::is_development_domain( 'production.de' ) );
    }
}
