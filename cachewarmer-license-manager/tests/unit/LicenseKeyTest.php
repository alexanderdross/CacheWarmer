<?php
/**
 * Unit Tests: Lizenzschlüssel-Generierung und -Validierung.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LicenseKeyTest extends TestCase {

    /**
     * Test: Key-Format CW-{TIER}-{HEX16} Regex-Validierung.
     */
    public function test_validate_key_format_valid_keys(): void {
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-FREE-A1B2C3D4E5F60708' ) );
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-PRO-1234567890ABCDEF' ) );
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-ENT-ABCDEF1234567890' ) );
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-DEV-0000000000000000' ) );
        $this->assertTrue( CWLM_License_Manager::validate_key_format( 'CW-FREE-FFFFFFFFFFFFFFFF' ) );
    }

    /**
     * Test: Ungültige Key-Formate werden abgelehnt.
     */
    public function test_validate_key_format_invalid_keys(): void {
        // Falsches Prefix
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'XX-PRO-1234567890ABCDEF' ) );
        // Non-Hex (G, H sind keine Hex-Zeichen)
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-FREE-A1B2C3D4E5F6G7H8' ) );
        // Zu kurz
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-PRO-A1B2' ) );
        // Zu lang
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-PRO-A1B2C3D4E5F6G7H8Z' ) );
        // Lowercase nicht erlaubt
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-PRO-a1b2c3d4e5f6g7h8' ) );
        // Unbekannter Tier
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'CW-GOLD-A1B2C3D4E5F6G7H8' ) );
        // Leer
        $this->assertFalse( CWLM_License_Manager::validate_key_format( '' ) );
        // Kein Prefix
        $this->assertFalse( CWLM_License_Manager::validate_key_format( 'A1B2C3D4E5F6G7H8' ) );
    }
}
