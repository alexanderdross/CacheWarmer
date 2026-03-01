<?php
/**
 * Unit Tests: Audit Logger – IP-Anonymisierung und Client-IP-Erkennung.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AuditLoggerTest extends TestCase {

    /**
     * Test: IPv4-Anonymisierung – letztes Oktett wird auf 0 gesetzt.
     */
    public function test_anonymize_ipv4(): void {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.42';
        $anon = CWLM_Audit_Logger::get_anonymized_ip();

        $this->assertEquals( '192.168.1.0', $anon );
    }

    /**
     * Test: IPv4-Anonymisierung – verschiedene IPs.
     */
    public function test_anonymize_ipv4_various(): void {
        $cases = [
            '10.0.0.1'     => '10.0.0.0',
            '172.16.0.255' => '172.16.0.0',
            '8.8.8.8'      => '8.8.8.0',
            '1.2.3.4'      => '1.2.3.0',
        ];

        foreach ( $cases as $input => $expected ) {
            $_SERVER['REMOTE_ADDR'] = $input;
            $this->assertEquals( $expected, CWLM_Audit_Logger::get_anonymized_ip(), "Anonymisierung von $input fehlerhaft" );
        }
    }

    /**
     * Test: get_client_ip() – Fallback auf REMOTE_ADDR wenn keine Proxies konfiguriert.
     */
    public function test_get_client_ip_without_proxies(): void {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
        unset( $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_REAL_IP'] );

        $ip = CWLM_Audit_Logger::get_client_ip();
        $this->assertEquals( '203.0.113.50', $ip );
    }

    /**
     * Test: get_client_ip() – X-Forwarded-For wird NICHT vertraut ohne CWLM_TRUSTED_PROXIES.
     */
    public function test_get_client_ip_ignores_xff_without_trusted_proxies(): void {
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.100';

        $ip = CWLM_Audit_Logger::get_client_ip();
        $this->assertEquals( '10.0.0.1', $ip, 'Ohne TRUSTED_PROXIES darf X-Forwarded-For nicht vertraut werden' );
    }

    /**
     * Test: get_client_ip() – Fehlende REMOTE_ADDR → Fallback 0.0.0.0.
     */
    public function test_get_client_ip_missing_remote_addr(): void {
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_REAL_IP']
        );

        $ip = CWLM_Audit_Logger::get_client_ip();
        $this->assertEquals( '0.0.0.0', $ip );
    }

    /**
     * Test: get_client_ip() – Ungültige IP in REMOTE_ADDR.
     */
    public function test_get_client_ip_invalid_remote_addr(): void {
        $_SERVER['REMOTE_ADDR'] = 'not-an-ip';
        $ip = CWLM_Audit_Logger::get_client_ip();
        $this->assertEquals( '0.0.0.0', $ip );
    }

    protected function tearDown(): void {
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_REAL_IP']
        );
    }
}
