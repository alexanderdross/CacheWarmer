<?php
/**
 * API Rate Limiting via WordPress Transients.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Rate_Limiter {

    /** @var array<string, int> Standard-Limits pro Endpoint. */
    private const DEFAULTS = [
        'health'     => 120,
        'validate'   => 60,
        'activate'   => 10,
        'deactivate' => 10,
        'check'      => 30,
    ];

    /**
     * Prüfe ob Request erlaubt ist.
     *
     * @return true|WP_Error
     */
    public function check( string $endpoint, string $ip ): true|\WP_Error {
        $limit      = $this->get_limit( $endpoint );
        $key        = 'cwlm_rate_' . md5( $ip . '_' . $endpoint );
        $current    = (int) get_transient( $key );

        if ( $current >= $limit ) {
            $retry_after = $this->get_retry_after( $key );
            return new \WP_Error(
                'RATE_LIMITED',
                sprintf( 'Zu viele Anfragen. Bitte warten Sie %d Sekunden.', $retry_after ),
                [
                    'status'      => 429,
                    'retry_after' => $retry_after,
                ]
            );
        }

        if ( 0 === $current ) {
            set_transient( $key, 1, 60 ); // 1-Minuten-Fenster
        } else {
            set_transient( $key, $current + 1, 60 );
        }

        return true;
    }

    /**
     * Limit für Endpoint ermitteln.
     */
    private function get_limit( string $endpoint ): int {
        $configured = defined( 'CWLM_RATE_LIMIT_PER_MINUTE' ) ? (int) CWLM_RATE_LIMIT_PER_MINUTE : 60;

        return match ( $endpoint ) {
            'activate', 'deactivate' => defined( 'CWLM_RATE_LIMIT_ACTIVATE' )
                ? (int) CWLM_RATE_LIMIT_ACTIVATE
                : self::DEFAULTS[ $endpoint ],
            default => self::DEFAULTS[ $endpoint ] ?? $configured,
        };
    }

    /**
     * Retry-After Sekunden berechnen.
     */
    private function get_retry_after( string $key ): int {
        $timeout = get_option( '_transient_timeout_' . $key );
        if ( $timeout ) {
            return max( 1, (int) $timeout - time() );
        }
        return 60;
    }

    /**
     * Abgelaufene Rate-Limit-Einträge bereinigen.
     *
     * Rate limiting uses WordPress transients, so cleanup is handled
     * automatically by WordPress's transient expiration mechanism.
     * This method is kept for backwards compatibility and explicitly
     * triggers transient garbage collection.
     */
    public function cleanup_expired(): void {
        if ( function_exists( 'delete_expired_transients' ) ) {
            delete_expired_transients();
        }
    }
}
