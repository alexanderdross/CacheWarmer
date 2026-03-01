<?php
/**
 * Regression Tests: Lizenzschlüssel-Format und Geschäftslogik.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LicenseKeyRegressionTest extends TestCase {

    /**
     * Regression: Key-Format muss CW-{TIER}-{16 Hex} bleiben.
     */
    public function test_key_format_regex_unchanged(): void {
        // Bekannte gültige Keys aus der Dokumentation
        $valid_keys = [
            'CW-FREE-A1B2C3D4E5F60708',
            'CW-PRO-1234567890ABCDEF',
            'CW-ENT-ABCDEF1234567890',
            'CW-DEV-0000000000000000',
        ];

        foreach ( $valid_keys as $key ) {
            $this->assertTrue(
                CWLM_License_Manager::validate_key_format( $key ),
                "REGRESSION: Bisher gültiger Key wird abgelehnt: $key"
            );
        }
    }

    /**
     * Regression: Generierte Keys müssen das dokumentierte Format haben.
     */
    public function test_generated_key_matches_documented_format(): void {
        $wpdb = new class { public string $prefix = 'wp_'; };
        $GLOBALS['wpdb'] = $wpdb;

        $manager = new CWLM_License_Manager();

        foreach ( [ 'free', 'professional', 'enterprise', 'development' ] as $tier ) {
            $key = $manager->generate_license_key( $tier );
            $this->assertTrue(
                CWLM_License_Manager::validate_key_format( $key ),
                "REGRESSION: Generierter Key für '$tier' ist ungültig: $key"
            );
        }
    }

    /**
     * Regression: is_valid() akzeptiert exakt 'active' und 'grace_period'.
     */
    public function test_is_valid_accepted_statuses(): void {
        $wpdb = new class { public string $prefix = 'wp_'; };
        $GLOBALS['wpdb'] = $wpdb;

        $manager = new CWLM_License_Manager();

        // Muss als gültig gelten
        $valid_statuses = [ 'active', 'grace_period' ];
        foreach ( $valid_statuses as $status ) {
            $this->assertTrue(
                $manager->is_valid( (object) [ 'status' => $status ] ),
                "REGRESSION: Status '$status' wird als ungültig behandelt"
            );
        }

        // Muss als ungültig gelten
        $invalid_statuses = [ 'inactive', 'expired', 'revoked', 'pending', '', 'unknown' ];
        foreach ( $invalid_statuses as $status ) {
            $this->assertFalse(
                $manager->is_valid( (object) [ 'status' => $status ] ),
                "REGRESSION: Status '$status' wird als gültig behandelt"
            );
        }
    }

    /**
     * Regression: Unterstützte Plattformen bleiben stabil.
     */
    public function test_supported_platforms_unchanged(): void {
        $expected = [ 'nodejs', 'docker', 'wordpress', 'drupal' ];

        foreach ( $expected as $platform ) {
            $this->assertTrue(
                CWLM_Installation_Tracker::validate_platform( $platform ),
                "REGRESSION: Plattform '$platform' wird nicht mehr unterstützt"
            );
        }
    }

    /**
     * Regression: Fingerprint bleibt SHA-256 (64 lowercase hex).
     */
    public function test_fingerprint_format_unchanged(): void {
        // SHA-256 Hash
        $valid = hash( 'sha256', 'test-data-for-fingerprint' );
        $this->assertTrue(
            CWLM_Installation_Tracker::validate_fingerprint( $valid ),
            "REGRESSION: SHA-256-Hash wird als Fingerprint abgelehnt"
        );
        $this->assertEquals( 64, strlen( $valid ) );
    }

    /**
     * Regression: JWT hat_secret() erfordert mindestens 32 Zeichen.
     */
    public function test_jwt_minimum_secret_length(): void {
        $jwt = new CWLM_JWT_Handler();

        // CWLM_JWT_SECRET ist im Bootstrap auf > 32 Zeichen gesetzt
        $this->assertTrue( $jwt->has_secret(), 'REGRESSION: JWT Secret-Validierung hat sich geändert' );
    }
}
