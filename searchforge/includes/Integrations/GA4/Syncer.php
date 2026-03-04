<?php

namespace SearchForge\Integrations\GA4;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Syncs GA4 behavior data and stores it for brief enrichment.
 */
class Syncer {

	/**
	 * Sync GA4 page-level behavior data.
	 *
	 * @return array|\WP_Error  [ 'pages_synced' => int ]
	 */
	public function sync(): array|\WP_Error {
		if ( ! Settings::is_pro() ) {
			return new \WP_Error( 'not_pro', __( 'GA4 integration requires a Pro license.', 'searchforge' ) );
		}

		$property_id = Settings::get( 'ga4_property_id', '' );
		if ( empty( $property_id ) ) {
			return new \WP_Error( 'no_ga4', __( 'GA4 property ID not configured.', 'searchforge' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'sf_ga4_metrics';
		$today = gmdate( 'Y-m-d' );

		// Fetch page metrics.
		$page_metrics = Client::get_page_metrics( 28, 500 );
		if ( is_wp_error( $page_metrics ) ) {
			return $page_metrics;
		}

		// Fetch organic landing page data.
		$landing_pages = Client::get_landing_pages( 28, 500 );
		$landing_data  = is_wp_error( $landing_pages ) ? [] : $landing_pages;

		$synced = 0;

		foreach ( $page_metrics as $path => $metrics ) {
			$organic = $landing_data[ $path ] ?? [];

			// Delete existing data for this date + path.
			$wpdb->delete( $table, [
				'page_path'     => $path,
				'snapshot_date' => $today,
			] );

			$wpdb->insert( $table, [
				'page_path'         => $path,
				'snapshot_date'     => $today,
				'sessions'          => $metrics['sessions'],
				'bounce_rate'       => $metrics['bounce_rate'],
				'avg_session_dur'   => $metrics['avg_session_dur'],
				'engaged_sessions'  => $metrics['engaged_sessions'],
				'conversions'       => $metrics['conversions'],
				'pageviews'         => $metrics['pageviews'],
				'organic_sessions'  => $organic['organic_sessions'] ?? 0,
				'organic_bounce'    => $organic['bounce_rate'] ?? null,
				'organic_conversions' => $organic['conversions'] ?? 0,
			] );

			$synced++;
		}

		// Log the sync.
		$wpdb->insert( $wpdb->prefix . 'sf_sync_log', [
			'source'          => 'ga4',
			'status'          => 'completed',
			'pages_synced'    => $synced,
			'keywords_synced' => 0,
			'started_at'      => current_time( 'mysql', true ),
			'completed_at'    => current_time( 'mysql', true ),
		] );

		return [ 'pages_synced' => $synced ];
	}

	/**
	 * Get GA4 behavior data for a specific page.
	 */
	public static function get_page_behavior( string $page_path ): ?array {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}sf_ga4_metrics
			WHERE page_path = %s
			ORDER BY snapshot_date DESC
			LIMIT 1",
			$page_path
		), ARRAY_A );
	}
}
