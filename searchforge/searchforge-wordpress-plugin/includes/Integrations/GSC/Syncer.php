<?php

namespace SearchForge\Integrations\GSC;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Syncer {

	/**
	 * Run a full sync: pages + keywords from GSC.
	 *
	 * @return array|\WP_Error
	 */
	public function sync_all(): array|\WP_Error {
		global $wpdb;

		$settings = Settings::get_all();
		$property = $settings['gsc_property'];

		if ( empty( $property ) ) {
			return new \WP_Error( 'no_property', __( 'No GSC property selected.', 'searchforge' ) );
		}

		// Log sync start.
		$wpdb->insert( "{$wpdb->prefix}sf_sync_log", [
			'source' => 'gsc',
			'status' => 'running',
		] );
		$log_id = $wpdb->insert_id;

		$end_date   = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
		$start_date = gmdate( 'Y-m-d', strtotime( '-28 days' ) );
		$today      = gmdate( 'Y-m-d' );

		try {
			// Sync page data.
			$page_limit = Settings::get_page_limit();
			$limit      = $page_limit > 0 ? $page_limit : 25000;

			$pages = Client::get_page_data( $property, $start_date, $end_date, $limit );
			if ( is_wp_error( $pages ) ) {
				$this->log_failure( $log_id, $pages->get_error_message() );
				return $pages;
			}

			$pages_synced = $this->store_page_data( $pages, $today );

			// Sync keyword data.
			$keywords = Client::get_keyword_data( $property, $start_date, $end_date, '', $limit * 5 );
			if ( is_wp_error( $keywords ) ) {
				$this->log_failure( $log_id, $keywords->get_error_message() );
				return $keywords;
			}

			$keywords_synced = $this->store_keyword_data( $keywords, $today );

			// Clean old data beyond retention period.
			$this->cleanup_old_data();

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
				'date_range'      => "$start_date to $end_date",
			];

		} catch ( \Exception $e ) {
			$this->log_failure( $log_id, $e->getMessage() );
			return new \WP_Error( 'sync_error', $e->getMessage() );
		}
	}

	private function store_page_data( array $pages, string $snapshot_date ): int {
		global $wpdb;
		$table = "{$wpdb->prefix}sf_snapshots";
		$count = 0;

		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $pages as $page ) {
				$page_url  = $page['page'];
				$page_path = wp_parse_url( $page_url, PHP_URL_PATH ) ?: '/';

				// Upsert: delete existing for this page+date+source, then insert.
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$table} WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc' AND device = 'all'",
					$page_path,
					$snapshot_date
				) );

				$result = $wpdb->insert( $table, [
					'page_url'      => $page_url,
					'page_path'     => $page_path,
					'snapshot_date' => $snapshot_date,
					'clicks'        => $page['clicks'],
					'impressions'   => $page['impressions'],
					'ctr'           => $page['ctr'],
					'position'      => $page['position'],
					'device'        => 'all',
					'source'        => 'gsc',
				] );

				if ( false === $result ) {
					throw new \RuntimeException( "Failed to insert page data for: {$page_path}" );
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

	private function store_keyword_data( array $keywords, string $snapshot_date ): int {
		global $wpdb;
		$table = "{$wpdb->prefix}sf_keywords";
		$count = 0;

		$wpdb->query( 'START TRANSACTION' );

		try {
			// Delete existing keywords for this snapshot date.
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table} WHERE snapshot_date = %s AND source = 'gsc'",
				$snapshot_date
			) );

			foreach ( $keywords as $kw ) {
				$page_path = wp_parse_url( $kw['page'], PHP_URL_PATH ) ?: '/';

				$result = $wpdb->insert( $table, [
					'page_path'     => $page_path,
					'query'         => $kw['query'],
					'snapshot_date' => $snapshot_date,
					'clicks'        => $kw['clicks'],
					'impressions'   => $kw['impressions'],
					'ctr'           => $kw['ctr'],
					'position'      => $kw['position'],
					'device'        => 'all',
					'source'        => 'gsc',
				] );

				if ( false === $result ) {
					throw new \RuntimeException( "Failed to insert keyword data for: {$kw['query']}" );
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

	private function cleanup_old_data(): void {
		global $wpdb;

		$retention_days = Settings::get_retention_days();
		$cutoff         = gmdate( 'Y-m-d', strtotime( "-{$retention_days} days" ) );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}sf_snapshots WHERE snapshot_date < %s",
			$cutoff
		) );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}sf_keywords WHERE snapshot_date < %s",
			$cutoff
		) );
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
