<?php
/**
 * CacheWarmer Uninstall — cleans up all plugin data.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cachewarmer_url_results" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cachewarmer_jobs" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cachewarmer_sitemaps" );

// Delete all options.
$options = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        'cachewarmer_%'
    )
);
foreach ( $options as $option ) {
    delete_option( $option );
}

// Clear scheduled events.
wp_clear_scheduled_hook( 'cachewarmer_cron_hook' );
wp_clear_scheduled_hook( 'cachewarmer_process_job' );
wp_clear_scheduled_hook( 'cachewarmer_scheduled_warm' );
