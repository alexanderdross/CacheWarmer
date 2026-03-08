<?php
/**
 * Auto-warm URLs when posts are published.
 *
 * Hooks into transition_post_status to automatically trigger
 * cache warming when a post or page is published (Premium+).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Publish_Hook {

    private CacheWarmer_Job_Manager $job_manager;

    /**
     * Constructor.
     *
     * @param CacheWarmer_Job_Manager $job_manager Job manager instance.
     */
    public function __construct( CacheWarmer_Job_Manager $job_manager ) {
        $this->job_manager = $job_manager;
        add_action( 'transition_post_status', array( $this, 'on_post_publish' ), 10, 3 );
    }

    /**
     * Trigger cache warming when a post transitions to 'publish'.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post object.
     */
    public function on_post_publish( string $new_status, string $old_status, WP_Post $post ): void {
        if ( ! CacheWarmer_License::is_premium_or_above() ) {
            return;
        }

        if ( ! get_option( 'cachewarmer_auto_warm_on_publish', false ) ) {
            return;
        }

        if ( 'publish' !== $new_status ) {
            return;
        }

        if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
            return;
        }

        $url = get_permalink( $post );
        if ( ! $url ) {
            return;
        }

        $targets = get_option( 'cachewarmer_auto_warm_targets', array( 'cdn', 'facebook', 'linkedin', 'twitter' ) );
        // Create a minimal warming job for this single URL.
        $this->job_manager->create_single_url_job( $url, $targets );
    }
}
