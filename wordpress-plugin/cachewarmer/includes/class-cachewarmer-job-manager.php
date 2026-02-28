<?php
/**
 * Job Manager — orchestrates warming jobs.
 *
 * Creates jobs, dispatches them to background processing via WP-Cron,
 * and coordinates all warming services.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Job_Manager {

    private CacheWarmer_Database $db;

    public function __construct( CacheWarmer_Database $db ) {
        $this->db = $db;
    }

    /**
     * Create a new warming job.
     *
     * @param string      $sitemap_url Sitemap URL to warm.
     * @param array       $targets     Warming targets (cdn, facebook, linkedin, twitter, google, bing, indexnow).
     * @param string|null $sitemap_id  Optional sitemap registration ID.
     * @return array Job data.
     */
    public function create_job( string $sitemap_url, array $targets, ?string $sitemap_id = null ): array {
        $job_id = wp_generate_uuid4();

        $all_targets = array( 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow' );
        if ( empty( $targets ) ) {
            $targets = $all_targets;
        }
        $targets = array_intersect( $targets, $all_targets );

        $job_data = array(
            'id'          => $job_id,
            'sitemap_id'  => $sitemap_id,
            'sitemap_url' => $sitemap_url,
            'targets'     => array_values( $targets ),
            'total_urls'  => 0,
        );

        $this->db->insert_job( $job_data );

        // Schedule background processing.
        wp_schedule_single_event( time(), 'cachewarmer_process_job', array( $job_id ) );
        spawn_cron();

        return array(
            'jobId'     => $job_id,
            'status'    => 'queued',
            'targets'   => array_values( $targets ),
            'createdAt' => current_time( 'mysql', true ),
        );
    }

    /**
     * Process a job in the background (called via WP-Cron).
     *
     * @param string $job_id Job ID.
     */
    public function process_job( string $job_id ): void {
        // Extend execution time for long-running jobs.
        if ( function_exists( 'set_time_limit' ) ) {
            set_time_limit( 0 );
        }
        wp_raise_memory_limit( 'cachewarmer' );

        $job = $this->db->get_job( $job_id );
        if ( ! $job ) {
            return;
        }

        if ( 'running' === $job->status ) {
            return; // Already processing.
        }

        // Mark as running.
        $this->db->update_job( $job_id, array(
            'status'     => 'running',
            'started_at' => current_time( 'mysql', true ),
        ) );

        try {
            // Parse sitemap.
            $parser = new CacheWarmer_Sitemap_Parser();
            $parsed = $parser->parse( $job->sitemap_url );

            if ( empty( $parsed ) ) {
                $this->db->update_job( $job_id, array(
                    'status'       => 'failed',
                    'completed_at' => current_time( 'mysql', true ),
                    'error'        => 'No URLs found in sitemap',
                ) );
                return;
            }

            $url_strings = array_map( function ( $entry ) {
                return $entry['loc'];
            }, $parsed );

            // Apply URL exclude patterns.
            $exclude_raw = get_option( 'cachewarmer_exclude_patterns', '' );
            if ( ! empty( trim( $exclude_raw ) ) ) {
                $patterns    = array_filter( array_map( 'trim', explode( "\n", $exclude_raw ) ) );
                $url_strings = array_values( array_filter( $url_strings, function ( $url ) use ( $patterns ) {
                    foreach ( $patterns as $pattern ) {
                        if ( false !== strpos( $url, $pattern ) ) {
                            return false;
                        }
                    }
                    return true;
                } ) );
            }

            $this->db->update_job( $job_id, array( 'total_urls' => count( $url_strings ) ) );

            $targets   = json_decode( $job->targets, true ) ?: array();
            $processed = 0;

            // Notify job started.
            CacheWarmer_Webhooks::notify( 'job.started', array(
                'jobId'      => $job_id,
                'sitemapUrl' => $job->sitemap_url,
                'urlCount'   => count( $url_strings ),
                'targets'    => $targets,
            ) );

            $on_result = function ( array $result ) use ( $job_id, &$processed ) {
                $this->db->insert_url_result( array(
                    'id'          => wp_generate_uuid4(),
                    'job_id'      => $job_id,
                    'url'         => $result['url'],
                    'target'      => $result['target'],
                    'status'      => $result['status'],
                    'http_status' => $result['http_status'] ?? null,
                    'duration_ms' => $result['duration_ms'] ?? null,
                    'error'       => $result['error'] ?? null,
                ) );
                $processed++;
                $this->db->update_job( $job_id, array( 'processed_urls' => $processed ) );
            };

            // Execute each enabled target sequentially.
            if ( in_array( 'cdn', $targets, true ) && get_option( 'cachewarmer_cdn_enabled', '1' ) ) {
                $warmer = new CacheWarmer_CDN_Warmer();
                $warmer->warm( $url_strings, $job_id, $on_result );
            }

            if ( in_array( 'facebook', $targets, true ) && get_option( 'cachewarmer_facebook_enabled', '0' ) ) {
                $warmer = new CacheWarmer_Facebook_Warmer();
                $warmer->warm( $url_strings, $job_id, $on_result );
            }

            if ( in_array( 'linkedin', $targets, true ) && get_option( 'cachewarmer_linkedin_enabled', '0' ) ) {
                $warmer = new CacheWarmer_LinkedIn_Warmer();
                $warmer->warm( $url_strings, $job_id, $on_result );
            }

            if ( in_array( 'twitter', $targets, true ) && get_option( 'cachewarmer_twitter_enabled', '0' ) ) {
                $warmer = new CacheWarmer_Twitter_Warmer();
                $warmer->warm( $url_strings, $job_id, $on_result );
            }

            if ( in_array( 'google', $targets, true ) && get_option( 'cachewarmer_google_enabled', '0' ) ) {
                $indexer = new CacheWarmer_Google_Indexer();
                $indexer->index( $url_strings, $job_id, $on_result );
            }

            if ( in_array( 'bing', $targets, true ) && get_option( 'cachewarmer_bing_enabled', '0' ) ) {
                $indexer = new CacheWarmer_Bing_Indexer();
                $indexer->index( $url_strings, $job_id, $on_result );
            }

            if ( in_array( 'indexnow', $targets, true ) && get_option( 'cachewarmer_indexnow_enabled', '0' ) ) {
                $indexnow = new CacheWarmer_IndexNow();
                $indexnow->submit( $url_strings, $job_id, $on_result );
            }

            // Update sitemap last_warmed_at if linked.
            if ( $job->sitemap_id ) {
                $this->db->update_sitemap_last_warmed( $job->sitemap_id );
            }

            $this->db->update_job( $job_id, array(
                'status'       => 'completed',
                'completed_at' => current_time( 'mysql', true ),
            ) );

            // Send completion notifications.
            $job_data = array(
                'id'             => $job_id,
                'status'         => 'completed',
                'sitemap_url'    => $job->sitemap_url,
                'total_urls'     => count( $url_strings ),
                'processed_urls' => $processed,
                'started_at'     => $job->started_at ?? null,
                'completed_at'   => current_time( 'mysql', true ),
            );
            CacheWarmer_Webhooks::notify( 'job.completed', $job_data );
            CacheWarmer_Email::send_job_completed( $job_data );

        } catch ( \Throwable $e ) {
            $this->db->update_job( $job_id, array(
                'status'       => 'failed',
                'completed_at' => current_time( 'mysql', true ),
                'error'        => $e->getMessage(),
            ) );

            // Send failure notifications.
            $job_data = array(
                'id'             => $job_id,
                'status'         => 'failed',
                'sitemap_url'    => $job->sitemap_url,
                'total_urls'     => 0,
                'processed_urls' => 0,
                'error'          => $e->getMessage(),
            );
            CacheWarmer_Webhooks::notify( 'job.failed', $job_data );
            CacheWarmer_Email::send_job_completed( $job_data );
        }
    }

    /**
     * Create a single-URL warming job.
     *
     * Used for auto-warm on publish and similar single-URL scenarios.
     *
     * @param string $url     The URL to warm.
     * @param array  $targets Warming targets.
     * @return array Job data.
     */
    public function create_single_url_job( string $url, array $targets ): array {
        $job_id = wp_generate_uuid4();

        $all_targets = array( 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow' );
        if ( empty( $targets ) ) {
            $targets = $all_targets;
        }
        $targets = array_intersect( $targets, $all_targets );

        $job_data = array(
            'id'          => $job_id,
            'sitemap_id'  => null,
            'sitemap_url' => $url,
            'targets'     => array_values( $targets ),
            'total_urls'  => 1,
        );

        $this->db->insert_job( $job_data );

        // Schedule background processing.
        wp_schedule_single_event( time(), 'cachewarmer_process_job', array( $job_id ) );
        spawn_cron();

        return array(
            'jobId'     => $job_id,
            'status'    => 'queued',
            'targets'   => array_values( $targets ),
            'createdAt' => current_time( 'mysql', true ),
        );
    }

    /**
     * Get job with stats.
     */
    public function get_job_with_stats( string $job_id ): ?array {
        $job = $this->db->get_job( $job_id );
        if ( ! $job ) {
            return null;
        }

        $stats = $this->db->get_job_stats( $job_id );

        return array(
            'job'   => $job,
            'stats' => $stats,
        );
    }
}
