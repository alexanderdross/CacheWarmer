<?php
/**
 * Email notifications for CacheWarmer.
 *
 * Sends email notifications when jobs complete (Enterprise only).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Email {

    /**
     * Send a job-completed email notification.
     *
     * Only fires for Enterprise tier when email notifications are enabled.
     *
     * @param array $job_data Job data array with id, status, sitemap_url, etc.
     */
    public static function send_job_completed( array $job_data ): void {
        if ( ! CacheWarmer_License::is_enterprise() ) {
            return;
        }

        if ( ! get_option( 'cachewarmer_email_notifications', false ) ) {
            return;
        }

        $to      = get_option( 'cachewarmer_notification_email', get_option( 'admin_email' ) );
        $subject = sprintf(
            '[CacheWarmer] Job %s: %s',
            $job_data['status'] ?? '',
            $job_data['sitemap_url'] ?? 'Unknown'
        );

        $body = sprintf(
            "Job ID: %s\nStatus: %s\nSitemap: %s\nURLs: %d/%d\nDuration: %s\n\nView details in your WordPress dashboard.",
            $job_data['id'] ?? '',
            $job_data['status'] ?? '',
            $job_data['sitemap_url'] ?? '',
            $job_data['processed_urls'] ?? 0,
            $job_data['total_urls'] ?? 0,
            isset( $job_data['started_at'], $job_data['completed_at'] )
                ? $job_data['completed_at'] . ' (started: ' . $job_data['started_at'] . ')'
                : 'N/A'
        );

        wp_mail( $to, $subject, $body );
    }
}
