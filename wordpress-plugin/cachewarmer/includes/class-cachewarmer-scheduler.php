<?php
/**
 * WP-Cron based scheduler for recurring sitemap warming.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Scheduler {

    private CacheWarmer_Job_Manager $job_manager;
    private CacheWarmer_Database $db;

    public function __construct( CacheWarmer_Job_Manager $job_manager, CacheWarmer_Database $db ) {
        $this->job_manager = $job_manager;
        $this->db          = $db;

        add_action( 'cachewarmer_cron_hook', array( $this, 'run_scheduled_warmings' ) );
        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
    }

    /**
     * Add custom cron schedules.
     */
    public function add_cron_schedules( array $schedules ): array {
        $schedules['every_6_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 6 Hours', 'cachewarmer' ),
        );
        $schedules['every_12_hours'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 12 Hours', 'cachewarmer' ),
        );
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display'  => __( 'Once Weekly', 'cachewarmer' ),
        );
        return $schedules;
    }

    /**
     * Run scheduled warmings for all registered sitemaps.
     */
    public function run_scheduled_warmings(): void {
        if ( ! get_option( 'cachewarmer_scheduler_enabled', '0' ) ) {
            return;
        }

        $sitemaps = $this->db->get_all_sitemaps();

        foreach ( $sitemaps as $sitemap ) {
            $targets = array( 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow' );
            $this->job_manager->create_job( $sitemap->url, $targets, $sitemap->id );
        }
    }

    /**
     * Run a scheduled warm for a specific sitemap.
     *
     * @param string $sitemap_id Sitemap ID.
     */
    public function run_scheduled_warm( string $sitemap_id ): void {
        $sitemap = $this->db->get_sitemap( $sitemap_id );
        if ( ! $sitemap ) {
            return;
        }

        $targets = array( 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow' );
        $this->job_manager->create_job( $sitemap->url, $targets, $sitemap->id );
    }

    /**
     * Update the cron schedule based on settings.
     */
    public static function update_schedule(): void {
        $hook = 'cachewarmer_cron_hook';

        // Clear existing schedule.
        $timestamp = wp_next_scheduled( $hook );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
        }

        if ( ! get_option( 'cachewarmer_scheduler_enabled', '0' ) ) {
            return;
        }

        $recurrence = get_option( 'cachewarmer_scheduler_cron', 'daily' );
        wp_schedule_event( time(), $recurrence, $hook );
    }
}
