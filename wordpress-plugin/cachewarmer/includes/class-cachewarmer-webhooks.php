<?php
/**
 * Webhook notifications for CacheWarmer.
 *
 * Sends event payloads to a configured webhook URL (Enterprise only).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Webhooks {

    /**
     * Send a webhook notification for an event.
     *
     * Only fires for Enterprise tier. Sends a non-blocking POST request
     * with JSON payload to the configured webhook URL.
     *
     * @param string $event Event name (e.g. 'job.completed', 'job.failed').
     * @param array  $data  Event data.
     */
    public static function notify( string $event, array $data ): void {
        if ( ! CacheWarmer_License::is_enterprise() ) {
            return;
        }

        $webhook_url = get_option( 'cachewarmer_webhook_url', '' );
        if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            return;
        }

        // SSRF protection: block requests to private/internal hosts.
        $parsed = wp_parse_url( $webhook_url );
        $host   = $parsed['host'] ?? '';
        if ( empty( $host ) || self::is_private_host( $host ) ) {
            return;
        }

        $payload = wp_json_encode( array(
            'event'     => $event,
            'timestamp' => gmdate( 'c' ),
            'data'      => $data,
        ) );

        wp_remote_post( $webhook_url, array(
            'headers'  => array( 'Content-Type' => 'application/json' ),
            'body'     => $payload,
            'timeout'  => 10,
            'blocking' => false,
        ) );
    }

    /**
     * Check if a host resolves to a private or reserved IP address.
     *
     * @param string $host Hostname to check.
     * @return bool TRUE if the host is private/internal.
     */
    protected static function is_private_host( string $host ): bool {
        $blocked = array( 'localhost', '127.0.0.1', '::1', '0.0.0.0' );
        if ( in_array( strtolower( $host ), $blocked, true ) ) {
            return true;
        }

        $ip = gethostbyname( $host );
        if ( $ip === $host ) {
            // Could not resolve — allow (DNS may be unavailable).
            return false;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
