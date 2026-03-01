<?php
/**
 * Unit Tests: JWT Token Generierung und Validierung.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class JwtHandlerTest extends TestCase {

    private CWLM_JWT_Handler $jwt;

    protected function setUp(): void {
        $this->jwt = new CWLM_JWT_Handler();
    }

    /**
     * Test: Token generieren und validieren (Round-Trip).
     */
    public function test_generate_and_validate_roundtrip(): void {
        $payload = [
            'license_id'      => 42,
            'installation_id' => 7,
            'tier'            => 'professional',
        ];

        $token = $this->jwt->generate( $payload );

        $this->assertIsString( $token );
        $this->assertStringContainsString( '.', $token );

        // 3 Teile: Header.Payload.Signature
        $parts = explode( '.', $token );
        $this->assertCount( 3, $parts );

        // Validierung
        $decoded = $this->jwt->validate( $token );
        $this->assertIsArray( $decoded );
        $this->assertEquals( 42, $decoded['license_id'] );
        $this->assertEquals( 7, $decoded['installation_id'] );
        $this->assertEquals( 'professional', $decoded['tier'] );
    }

    /**
     * Test: Token enthält iat und exp Claims.
     */
    public function test_token_contains_iat_and_exp(): void {
        $token   = $this->jwt->generate( [ 'test' => true ] );
        $decoded = $this->jwt->validate( $token );

        $this->assertArrayHasKey( 'iat', $decoded );
        $this->assertArrayHasKey( 'exp', $decoded );
        $this->assertGreaterThan( time() - 5, $decoded['iat'] );
        $this->assertGreaterThan( time(), $decoded['exp'] );

        // exp = iat + 30 Tage (default)
        $expected_exp = $decoded['iat'] + ( 30 * 86400 );
        $this->assertEquals( $expected_exp, $decoded['exp'] );
    }

    /**
     * Test: Manipulierter Token wird abgelehnt.
     */
    public function test_tampered_token_rejected(): void {
        $token = $this->jwt->generate( [ 'license_id' => 1 ] );

        // Payload manipulieren
        $parts    = explode( '.', $token );
        $payload  = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );
        $payload['license_id'] = 999; // Manipulation
        $parts[1] = rtrim( strtr( base64_encode( json_encode( $payload ) ), '+/', '-_' ), '=' );
        $tampered = implode( '.', $parts );

        $this->assertFalse( $this->jwt->validate( $tampered ) );
    }

    /**
     * Test: Leerer/ungültiger Token wird abgelehnt.
     */
    public function test_invalid_tokens_rejected(): void {
        $this->assertFalse( $this->jwt->validate( '' ) );
        $this->assertFalse( $this->jwt->validate( 'not-a-jwt' ) );
        $this->assertFalse( $this->jwt->validate( 'a.b' ) );
        $this->assertFalse( $this->jwt->validate( 'a.b.c.d' ) );
    }

    /**
     * Test: get_expiry_date() liefert ISO-8601 Datum.
     */
    public function test_get_expiry_date_format(): void {
        $date = $this->jwt->get_expiry_date();

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            $date
        );

        // ~30 Tage in der Zukunft
        $ts = strtotime( $date );
        $this->assertGreaterThan( time() + 29 * 86400, $ts );
        $this->assertLessThan( time() + 31 * 86400, $ts );
    }
}
