<?php

namespace SearchForge\Integrations\Bing;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Syncer {

	/**
	 * Run a full sync of Bing Webmaster data.
	 *
	 * @return array|\WP_Error
	 */
	public function sync_all(): array|\WP_Error {
		global $wpdb;

		if ( ! Settings::is_pro() ) {
			return new \WP_Error( 'not_pro', __( 'Bing integration requires a Pro license.', 'searchforge' ) );
		}

		$site_url = Settings::get( 'bing_site_url' );
		if ( empty( $site_url ) ) {
			return new \WP_Error( 'no_site', __( 'No Bing site URL configured.', 'searchforge' ) );
		}

		// Log sync start.
		$wpdb->insert( "{$wpdb->prefix}sf_sync_log", [
			'source' => 'bing',
			'status' => 'running',
		] );
		$log_id = $wpdb->insert_id;
		$today  = gmdate( 'Y-m-d' );

		try {
			// Sync page stats.
			$page_stats = Client::get_page_stats( $site_url );
			if ( is_wp_error( $page_stats ) ) {
				$this->log_failure( $log_id, $page_stats->get_error_message() );
				return $page_stats;
			}

			$pages_synced = $this->store_page_data( $page_stats, $today );

			// Sync query stats.
			$query_stats = Client::get_query_stats( $site_url );
			if ( is_wp_error( $query_stats ) ) {
				$this->log_failure( $log_id, $query_stats->get_error_message() );
				return $query_stats;
			}

			$keywords_synced = $this->store_keyword_data( $query_stats, $today );

			// Log success.
			$wpdb->update( "{$wpdb->prefix}sf_sync_log", [
				'status'          => 'completed',
				'pages_synced'    => $pages_synced,
				'keywords_synced' => $keywords_synced,
				'completed_at'    => current_time( 'mysql', true ),
			], [ 'id' => $log_id ] );

			return [
				'pages_synced'    => $pages_synced,
				'keywords_synced' => $keywords_synced,
				'source'          => 'bing',
			];

		} catch ( \Exception $e ) {
			$this->log_failure( $log_id, $e->getMessage() );
			return new \WP_Error( 'sync_error', $e->getMessage() );
		}
	}

	private function store_page_data( array $stats, string $snapshot_date ): int {
		global $wpdb;
		$table = "{$wpdb->prefix}sf_snapshots";
		$count = 0;

		// Bing returns an array of page stat objects.
		$pages = is_array( $stats ) ? $stats : [];

		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $pages as $entry ) {
				$page_url  = $entry['Query'] ?? $entry['Url'] ?? '';
				if ( empty( $page_url ) ) {
					continue;
				}

				$page_path = wp_parse_url( $page_url, PHP_URL_PATH ) ?: '/';

				$clicks      = (int) ( $entry['Clicks'] ?? 0 );
				$impressions = (int) ( $entry['Impressions'] ?? 0 );
				$ctr         = $impressions > 0 ? $clicks / $impressions : 0;
				$position    = (float) ( $entry['AvgImpressionPosition'] ?? $entry['Position'] ?? 0 );

				// Upsert.
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$table} WHERE page_path = %s AND snapshot_date = %s AND source = 'bing' AND device = 'all'",
					$page_path,
					$snapshot_date
				) );

				$result = $wpdb->insert( $table, [
					'page_url'      => $page_url,
					'page_path'     => $page_path,
					'snapshot_date' => $snapshot_date,
					'clicks'        => $clicks,
					'impressions'   => $impressions,
					'ctr'           => $ctr,
					'position'      => $position,
					'device'        => 'all',
					'source'        => 'bing',
				] );

				if ( false === $result ) {
					throw new \RuntimeException( "Failed to insert Bing page data for: {$page_path}" );
				}

				$count++;
			}

			$wpdb->query( 'COMMIT' );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}

		return $count;
	}

	private function store_keyword_data( array $stats, string $snapshot_date ): int {
		global $wpdb;
		$table = "{$wpdb->prefix}sf_keywords";
		$count = 0;

		$queries = is_array( $stats ) ? $stats : [];

		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete existing Bing keywords for this date.
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table} WHERE snapshot_date = %s AND source = 'bing'",
				$snapshot_date
			) );

			foreach ( $queries as $entry ) {
				$query = $entry['Query'] ?? '';
				if ( empty( $query ) ) {
					continue;
				}

				$clicks      = (int) ( $entry['Clicks'] ?? 0 );
				$impressions = (int) ( $entry['Impressions'] ?? 0 );
				$ctr         = $impressions > 0 ? $clicks / $impressions : 0;
				$position    = (float) ( $entry['AvgImpressionPosition'] ?? $entry['Position'] ?? 0 );

				// Bing query stats don't always include page info.
				$page_path = '/';
				if ( ! empty( $entry['Url'] ) ) {
					$page_path = wp_parse_url( $entry['Url'], PHP_URL_PATH ) ?: '/';
				}

				$result = $wpdb->insert( $table, [
					'page_path'     => $page_path,
					'query'         => $query,
					'snapshot_date' => $snapshot_date,
					'clicks'        => $clicks,
					'impressions'   => $impressions,
					'ctr'           => $ctr,
					'position'      => $position,
					'device'        => 'all',
					'source'        => 'bing',
				] );

				if ( false === $result ) {
					throw new \RuntimeException( "Failed to insert Bing keyword data for: {$query}" );
				}

				$count++;
			}

			$wpdb->query( 'COMMIT' );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}

		return $count;
	}

	private function log_failure( int $log_id, string $message ): void {
		global $wpdb;

		$wpdb->update( "{$wpdb->prefix}sf_sync_log", [
			'status'        => 'failed',
			'error_message' => $message,
			'completed_at'  => current_time( 'mysql', true ),
		], [ 'id' => $log_id ] );
	}
}
