<?php
/**
 * SearchForge uninstall handler.
 * Removes all plugin data when the plugin is deleted via WP admin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables.
$tables = [
	'sf_snapshots',
	'sf_keywords',
	'sf_sync_log',
	'sf_briefs_cache',
	'sf_alerts',
	'sf_settings',
];

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// Remove options.
delete_option( 'searchforge_settings' );
delete_option( 'searchforge_db_version' );

// Remove scheduled events.
wp_clear_scheduled_hook( 'searchforge_daily_sync' );
wp_clear_scheduled_hook( 'searchforge_weekly_digest' );
