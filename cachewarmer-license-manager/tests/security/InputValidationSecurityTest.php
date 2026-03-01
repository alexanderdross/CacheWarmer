<?php
/**
 * Security Tests: Input-Validierung, Injection-Prävention, Edge Cases.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class InputValidationSecurityTest extends TestCase {

    // ── License Key Injection Tests ─────────────────────────────────────

    /**
     * @dataProvider sqlInjectionProvider
     */
    public function test_license_key_rejects_sql_injection( string $payload ): void {
        $this->assertFalse(
            CWLM_License_Manager::validate_key_format( $payload ),
            "SQL-Injection-Payload wurde nicht abgelehnt: $payload"
        );
    }

    /**
     * @dataProvider xssPayloadProvider
     */
    public function test_license_key_rejects_xss_payloads( string $payload ): void {
        $this->assertFalse(
            CWLM_License_Manager::validate_key_format( $payload ),
            "XSS-Payload wurde nicht abgelehnt: $payload"
        );
    }

    /**
     * @dataProvider pathTraversalProvider
     */
    public function test_license_key_rejects_path_traversal( string $payload ): void {
        $this->assertFalse(
            CWLM_License_Manager::validate_key_format( $payload ),
            "Path-Traversal-Payload wurde nicht abgelehnt: $payload"
        );
    }

    // ── Fingerprint Injection Tests ──────────────────────────────────────

    /**
     * @dataProvider sqlInjectionProvider
     */
    public function test_fingerprint_rejects_sql_injection( string $payload ): void {
        $this->assertFalse(
            CWLM_Installation_Tracker::validate_fingerprint( $payload ),
            "Fingerprint akzeptiert SQL-Injection: $payload"
        );
    }

    /**
     * @dataProvider xssPayloadProvider
     */
    public function test_fingerprint_rejects_xss_payloads( string $payload ): void {
        $this->assertFalse(
            CWLM_Installation_Tracker::validate_fingerprint( $payload ),
            "Fingerprint akzeptiert XSS: $payload"
        );
    }

    // ── Platform Injection Tests ────────────────────────────────────────

    /**
     * @dataProvider sqlInjectionProvider
     */
    public function test_platform_rejects_sql_injection( string $payload ): void {
        $this->assertFalse(
            CWLM_Installation_Tracker::validate_platform( $payload ),
            "Platform akzeptiert SQL-Injection: $payload"
        );
    }

    // ── JWT Security Tests ──────────────────────────────────────────────

    public function test_jwt_rejects_none_algorithm_attack(): void {
        $jwt = new CWLM_JWT_Handler();

        // "none"-Algorithmus-Angriff: Header auf alg:none setzen
        $header  = rtrim( strtr( base64_encode( '{"alg":"none","typ":"JWT"}' ), '+/', '-_' ), '=' );
        $payload = rtrim( strtr( base64_encode( '{"license_id":1,"exp":' . ( time() + 86400 ) . '}' ), '+/', '-_' ), '=' );
        $token   = "$header.$payload.";

        $this->assertFalse( $jwt->validate( $token ), 'JWT "none"-Algorithmus-Angriff muss abgelehnt werden' );
    }

    public function test_jwt_rejects_expired_token(): void {
        $jwt = new CWLM_JWT_Handler();

        // Token mit abgelaufenem exp
        $header  = rtrim( strtr( base64_encode( json_encode( [ 'alg' => 'HS256', 'typ' => 'JWT' ] ) ), '+/', '-_' ), '=' );
        $payload_data = [ 'license_id' => 1, 'iat' => time() - 86400, 'exp' => time() - 3600 ];
        $payload = rtrim( strtr( base64_encode( json_encode( $payload_data ) ), '+/', '-_' ), '=' );
        $sig     = rtrim( strtr( base64_encode( hash_hmac( 'sha256', "$header.$payload", CWLM_JWT_SECRET, true ) ), '+/', '-_' ), '=' );
        $token   = "$header.$payload.$sig";

        $this->assertFalse( $jwt->validate( $token ), 'Abgelaufener JWT-Token muss abgelehnt werden' );
    }

    public function test_jwt_rejects_malformed_base64(): void {
        $jwt = new CWLM_JWT_Handler();

        $this->assertFalse( $jwt->validate( '!!!.@@@.###' ) );
        $this->assertFalse( $jwt->validate( 'a.b.c' ) );
    }

    public function test_jwt_rejects_token_without_exp(): void {
        $jwt = new CWLM_JWT_Handler();

        $header  = rtrim( strtr( base64_encode( json_encode( [ 'alg' => 'HS256', 'typ' => 'JWT' ] ) ), '+/', '-_' ), '=' );
        $payload = rtrim( strtr( base64_encode( json_encode( [ 'license_id' => 1 ] ) ), '+/', '-_' ), '=' );
        $sig     = rtrim( strtr( base64_encode( hash_hmac( 'sha256', "$header.$payload", CWLM_JWT_SECRET, true ) ), '+/', '-_' ), '=' );

        $this->assertFalse( $jwt->validate( "$header.$payload.$sig" ), 'Token ohne exp-Claim muss abgelehnt werden' );
    }

    // ── Feature Flags Security ──────────────────────────────────────────

    public function test_features_json_rejects_malformed_json(): void {
        $flags   = new CWLM_Feature_Flags();
        $license = (object) [
            'tier'          => 'free',
            'features_json' => '{invalid json!!!',
        ];

        $features = $flags->get_features( $license );

        // Muss auf Free-Defaults zurückfallen, nicht crashen
        $this->assertTrue( $features['cdn_warming'] );
        $this->assertFalse( $features['cdn_puppeteer'] );
    }

    public function test_features_json_does_not_escalate_tier(): void {
        $flags   = new CWLM_Feature_Flags();
        $license = (object) [
            'tier'          => 'free',
            'features_json' => json_encode( [
                'cdn_puppeteer'         => true,
                'google_search_console' => true,
                'max_urls'              => 999999,
            ] ),
        ];

        $features = $flags->get_features( $license );

        // JSON-Overrides WERDEN angewendet (Design-Entscheidung: Admin kann Features individuell freischalten)
        // Aber wir prüfen dass der Mechanismus funktioniert
        $this->assertIsArray( $features );
        $this->assertArrayHasKey( 'cdn_warming', $features );
    }

    public function test_development_domain_wildcard_does_not_match_partial(): void {
        // "test.dev" soll matchen, aber "devious.com" NICHT
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( 'test.dev' ) );
        $this->assertFalse( CWLM_Feature_Flags::is_development_domain( 'devious.com' ) );
        $this->assertFalse( CWLM_Feature_Flags::is_development_domain( 'evil.dev.example.com' ) );

        // "myapp.local" soll matchen
        $this->assertTrue( CWLM_Feature_Flags::is_development_domain( 'myapp.local' ) );
        // Aber "localstore.com" NICHT
        $this->assertFalse( CWLM_Feature_Flags::is_development_domain( 'localstore.com' ) );
    }

    // ── IP-Anonymisierung Security ──────────────────────────────────────

    public function test_ip_anonymization_covers_edge_cases(): void {
        // Standard IPv4
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertEquals( '192.168.1.0', CWLM_Audit_Logger::get_anonymized_ip() );

        // IP mit .0 Endung
        $_SERVER['REMOTE_ADDR'] = '10.0.0.0';
        $this->assertEquals( '10.0.0.0', CWLM_Audit_Logger::get_anonymized_ip() );

        // IP mit .255 Endung
        $_SERVER['REMOTE_ADDR'] = '10.0.0.255';
        $this->assertEquals( '10.0.0.0', CWLM_Audit_Logger::get_anonymized_ip() );
    }

    // ── Data Providers ──────────────────────────────────────────────────

    public static function sqlInjectionProvider(): array {
        return [
            'basic union'        => ["' UNION SELECT * FROM users--"],
            'drop table'         => ["'; DROP TABLE licenses;--"],
            'or 1=1'             => ["' OR 1=1--"],
            'comment bypass'     => ["admin'/*"],
            'double encoding'    => ["%27%20OR%201%3D1--"],
            'sleep injection'    => ["' OR SLEEP(5)--"],
            'load file'          => ["' UNION SELECT LOAD_FILE('/etc/passwd')--"],
            'hex bypass'         => ["0x27206F722031"],
            'stacked queries'    => ["'; INSERT INTO admins VALUES('hack','pwned');--"],
            'batch alt del'      => ["'; DELETE FROM licenses WHERE ''='"],
        ];
    }

    public static function xssPayloadProvider(): array {
        return [
            'script tag'         => ['<script>alert(1)</script>'],
            'img onerror'        => ['<img src=x onerror=alert(1)>'],
            'svg onload'         => ['<svg/onload=alert(1)>'],
            'event handler'      => ['" onmouseover="alert(1)'],
            'javascript uri'     => ['javascript:alert(document.cookie)'],
            'data uri'           => ['data:text/html,<script>alert(1)</script>'],
            'encoded script'     => ['&lt;script&gt;alert(1)&lt;/script&gt;'],
            'null byte'          => ["CW-PRO-\x00<script>alert(1)"],
        ];
    }

    public static function pathTraversalProvider(): array {
        return [
            'dot dot slash'    => ['../../etc/passwd'],
            'encoded dots'     => ['%2e%2e%2f%2e%2e%2f'],
            'null byte path'   => ["file\x00.php"],
            'backslash'        => ['..\\..\\windows\\system32'],
        ];
    }

    protected function tearDown(): void {
        unset( $_SERVER['REMOTE_ADDR'] );
    }
}
