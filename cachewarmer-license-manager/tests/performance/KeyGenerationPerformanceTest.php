<?php
/**
 * Performance Tests: Key-Generierung, JWT-Overhead, Feature-Flag-Lookup.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class KeyGenerationPerformanceTest extends TestCase {

    /**
     * Test: 1000 License-Keys können in < 500ms generiert werden.
     */
    public function test_key_generation_throughput(): void {
        $wpdb = new class { public string $prefix = 'wp_'; };
        $GLOBALS['wpdb'] = $wpdb;

        $manager = new CWLM_License_Manager();
        $tiers   = [ 'free', 'professional', 'enterprise', 'development' ];

        $start = microtime( true );
        for ( $i = 0; $i < 1000; $i++ ) {
            $manager->generate_license_key( $tiers[ $i % 4 ] );
        }
        $elapsed = ( microtime( true ) - $start ) * 1000;

        $this->assertLessThan(
            500,
            $elapsed,
            sprintf( '1000 Keys in %.1f ms generiert (Limit: 500ms)', $elapsed )
        );
    }

    /**
     * Test: JWT-Generierung + Validierung unter 50ms pro Token.
     */
    public function test_jwt_roundtrip_performance(): void {
        $jwt     = new CWLM_JWT_Handler();
        $payload = [
            'license_id'      => 42,
            'installation_id' => 7,
            'tier'            => 'enterprise',
            'features'        => CWLM_Feature_Flags::get_tier_defaults( 'enterprise' ),
        ];

        $start = microtime( true );
        for ( $i = 0; $i < 100; $i++ ) {
            $token = $jwt->generate( $payload );
            $jwt->validate( $token );
        }
        $elapsed = ( microtime( true ) - $start ) * 1000;

        $per_op = $elapsed / 100;
        $this->assertLessThan(
            50,
            $per_op,
            sprintf( 'JWT Round-Trip: %.2f ms/op (Limit: 50ms)', $per_op )
        );
    }

    /**
     * Test: Feature-Flag-Lookup ist O(1) – 10000 Lookups in < 100ms.
     */
    public function test_feature_lookup_performance(): void {
        $flags   = new CWLM_Feature_Flags();
        $license = (object) [
            'tier'          => 'professional',
            'features_json' => json_encode( [ 'max_urls' => 10000 ] ),
        ];

        $start = microtime( true );
        for ( $i = 0; $i < 10000; $i++ ) {
            $flags->get_features( $license );
        }
        $elapsed = ( microtime( true ) - $start ) * 1000;

        $this->assertLessThan(
            100,
            $elapsed,
            sprintf( '10000 Feature-Lookups in %.1f ms (Limit: 100ms)', $elapsed )
        );
    }

    /**
     * Test: Fingerprint-Validierung ist O(1) – 10000 Validierungen in < 50ms.
     */
    public function test_fingerprint_validation_performance(): void {
        $fp = str_repeat( 'a', 64 );

        $start = microtime( true );
        for ( $i = 0; $i < 10000; $i++ ) {
            CWLM_Installation_Tracker::validate_fingerprint( $fp );
        }
        $elapsed = ( microtime( true ) - $start ) * 1000;

        $this->assertLessThan(
            50,
            $elapsed,
            sprintf( '10000 Fingerprint-Validierungen in %.1f ms (Limit: 50ms)', $elapsed )
        );
    }

    /**
     * Test: Key-Format-Validierung ist O(1) – 10000 Validierungen in < 50ms.
     */
    public function test_key_validation_performance(): void {
        $key = 'CW-PRO-1234567890ABCDEF';

        $start = microtime( true );
        for ( $i = 0; $i < 10000; $i++ ) {
            CWLM_License_Manager::validate_key_format( $key );
        }
        $elapsed = ( microtime( true ) - $start ) * 1000;

        $this->assertLessThan(
            50,
            $elapsed,
            sprintf( '10000 Key-Validierungen in %.1f ms (Limit: 50ms)', $elapsed )
        );
    }

    /**
     * Test: Development-Domain-Check-Performance.
     */
    public function test_dev_domain_check_performance(): void {
        $domains = [
            'localhost', 'myapp.local', 'test.dev', 'site.test', '127.0.0.1',
            'example.com', 'production.de', 'shop.example.com',
        ];

        $start = microtime( true );
        for ( $i = 0; $i < 10000; $i++ ) {
            CWLM_Feature_Flags::is_development_domain( $domains[ $i % 8 ] );
        }
        $elapsed = ( microtime( true ) - $start ) * 1000;

        $this->assertLessThan(
            100,
            $elapsed,
            sprintf( '10000 Domain-Checks in %.1f ms (Limit: 100ms)', $elapsed )
        );
    }
}
