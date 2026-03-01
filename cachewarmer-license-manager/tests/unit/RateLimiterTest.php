<?php
/**
 * Unit Tests: Rate Limiter – Limit-Konfiguration und Defaults.
 *
 * @package CacheWarmer_License_Manager\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase {

    /**
     * Test: Default-Limits sind definiert für bekannte Endpoints.
     */
    public function test_default_limits_exist(): void {
        $reflection = new ReflectionClass( CWLM_Rate_Limiter::class );
        $defaults   = $reflection->getConstant( 'DEFAULTS' );

        $this->assertIsArray( $defaults );
        $this->assertArrayHasKey( 'health', $defaults );
        $this->assertArrayHasKey( 'validate', $defaults );
        $this->assertArrayHasKey( 'activate', $defaults );
        $this->assertArrayHasKey( 'deactivate', $defaults );
        $this->assertArrayHasKey( 'check', $defaults );
    }

    /**
     * Test: Activate/Deactivate haben niedrigere Limits als Health/Validate.
     */
    public function test_activate_limits_are_stricter(): void {
        $reflection = new ReflectionClass( CWLM_Rate_Limiter::class );
        $defaults   = $reflection->getConstant( 'DEFAULTS' );

        $this->assertLessThan(
            $defaults['health'],
            $defaults['activate'],
            'Activate Limit sollte strikter sein als Health'
        );

        $this->assertLessThan(
            $defaults['validate'],
            $defaults['activate'],
            'Activate Limit sollte strikter sein als Validate'
        );
    }

    /**
     * Test: All limits are positive integers.
     */
    public function test_all_limits_are_positive(): void {
        $reflection = new ReflectionClass( CWLM_Rate_Limiter::class );
        $defaults   = $reflection->getConstant( 'DEFAULTS' );

        foreach ( $defaults as $endpoint => $limit ) {
            $this->assertGreaterThan( 0, $limit, "Limit für '$endpoint' muss positiv sein" );
        }
    }
}
