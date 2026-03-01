<?php
/**
 * E-Mail-Versand für Lizenz-Events.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Email {

    /**
     * Lizenzschlüssel per E-Mail senden.
     */
    public function send_license_created( string $email, string $license_key, string $tier, string $plan ): bool {
        $subject = 'Ihr CacheWarmer Lizenzschlüssel';

        $template = CWLM_PLUGIN_DIR . 'email-templates/license-created.php';
        if ( ! file_exists( $template ) ) {
            return false;
        }
        ob_start();
        $data = compact( 'license_key', 'tier', 'plan' );
        include $template;
        $body = ob_get_clean();

        return wp_mail( $email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    /**
     * Ablauf-Warnung senden.
     */
    public function send_expiry_warning( string $email, string $license_key, string $expires_at ): bool {
        $subject = 'CacheWarmer: Ihre Lizenz läuft bald ab';

        $template = CWLM_PLUGIN_DIR . 'email-templates/license-expiring.php';
        if ( ! file_exists( $template ) ) {
            return false;
        }
        ob_start();
        $data = compact( 'license_key', 'expires_at' );
        include $template;
        $body = ob_get_clean();

        return wp_mail( $email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    /**
     * Ablauf-Warnungen für bald ablaufende Lizenzen versenden (Cronjob).
     */
    public function send_expiry_warnings( int $days_before = 7 ): int {
        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        $warning_date = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days_before} days" ) );
        $now          = gmdate( 'Y-m-d H:i:s' );

        $licenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT license_key, customer_email, expires_at
                 FROM {$prefix}licenses
                 WHERE status = 'active'
                   AND expires_at IS NOT NULL
                   AND expires_at BETWEEN %s AND %s",
                $now,
                $warning_date
            )
        );

        $sent = 0;
        foreach ( $licenses as $license ) {
            if ( $this->send_expiry_warning( $license->customer_email, $license->license_key, $license->expires_at ) ) {
                $sent++;
            }
        }

        return $sent;
    }
}
