<?php
/**
 * Unit Tests: License Manager – Key generation, validation, tier logic.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LicenseManagerTest extends TestCase {

    /**
     * Test: generate_license_key() erzeugt korrektes Format pro Tier.
     */
    public function test_generate_key_format_per_tier(): void {
        $manager = $this->create_manager_stub();

        $tiers = [
            'free'         => 'FREE',
            'professional' => 'PRO',
            'enterprise'   => 'ENT',
            'development'  => 'DEV',
        ];

        foreach ( $tiers as $tier => $expected_prefix ) {
            $key = $manager->generate_license_key( $tier );
            $this->assertMatchesRegularExpression(
                "/^CW-{$expected_prefix}-[A-F0-9]{16}$/",
                $key,
                "Key für Tier '$tier' hat falsches Format: $key"
            );
        }
    }

    /**
     * Test: generate_license_key() erzeugt einzigartige Keys.
     */
    public function test_generate_key_uniqueness(): void {
        $manager = $this->create_manager_stub();
        $keys = [];

        for ( $i = 0; $i < 100; $i++ ) {
            $keys[] = $manager->generate_license_key( 'professional' );
        }

        $unique_keys = array_unique( $keys );
        $this->assertCount( 100, $unique_keys, '100 generierte Keys sollten alle einzigartig sein' );
    }

    /**
     * Test: generate_license_key() mit unbekanntem Tier → FREE.
     */
    public function test_generate_key_unknown_tier_defaults_to_free(): void {
        $manager = $this->create_manager_stub();
        $key = $manager->generate_license_key( 'unknown' );

        $this->assertStringStartsWith( 'CW-FREE-', $key );
    }

    /**
     * Test: validate_key_format() – Boundary-Werte.
     */
    public function test_validate_key_format_boundaries(): void {
        // Genau 16 Hex-Zeichen (Minimum und Maximum)
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-FREE-0000000000000000' ) );
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-ENT-FFFFFFFFFFFFFFFF' ) );

        // 15 Zeichen (zu kurz)
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-PRO-000000000000000' ) );

        // 17 Zeichen (zu lang)
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-PRO-00000000000000000' ) );
    }

    /**
     * Test: validate_key_format() – SQL-Injection-Versuch.
     */
    public function test_validate_key_format_rejects_injection(): void {
        $this->assertFalse( CWLM_License_Manager::validate_key_format( "CW-PRO-'; DROP TABLE--" ) );
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-PRO-<script>alert(1)' ) );
        $this->assertFalse( CWLM_License_Manager::validate_key_format( "CW-PRO-\x00\x00\x00\x00" ) );
    }

    /**
     * Test: is_valid() – Status-basierte Validierung.
     */
    public function test_is_valid_status_checks(): void {
        $manager = $this->create_manager_stub();

        $this->assertTrue( $manager->is_valid( (object) [ 'status' => 'active' ] ) );
        $this->assertTrue( $manager->is_valid( (object) [ 'status' => 'grace_period' ] ) );
        $this->assertFalse( $manager->is_valid( (object) [ 'status' => 'inactive' ] ) );
        $this->assertFalse( $manager->is_valid( (object) [ 'status' => 'expired' ] ) );
        $this->assertFalse( $manager->is_valid( (object) [ 'status' => 'revoked' ] ) );
    }

    /**
     * Erstellt Manager-Stub ohne DB-Zugriff.
     */
    private function create_manager_stub(): CWLM_License_Manager {
        // Wir brauchen einen Mock für $wpdb
        $wpdb = new class {
            public string $prefix = 'wp_';
        };
        $GLOBALS['wpdb'] = $wpdb;

        return new CWLM_License_Manager();
    }
}
