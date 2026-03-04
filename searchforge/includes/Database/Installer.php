<?php

namespace SearchForge\Database;

defined( 'ABSPATH' ) || exit;

class Installer {

	public function install(): void {
		$this->create_tables();
		update_option( 'searchforge_db_version', SEARCHFORGE_DB_VERSION );
	}

	private function create_tables(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE {$wpdb->prefix}sf_snapshots (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				page_url VARCHAR(2048) NOT NULL,
				page_path VARCHAR(512) NOT NULL,
				snapshot_date DATE NOT NULL,
				clicks INT UNSIGNED NOT NULL DEFAULT 0,
				impressions INT UNSIGNED NOT NULL DEFAULT 0,
				ctr DECIMAL(5,4) NOT NULL DEFAULT 0,
				position DECIMAL(6,2) NOT NULL DEFAULT 0,
				device VARCHAR(10) NOT NULL DEFAULT 'all',
				source VARCHAR(20) NOT NULL DEFAULT 'gsc',
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_page_date (page_path, snapshot_date),
				KEY idx_source_date (source, snapshot_date),
				KEY idx_snapshot_date (snapshot_date)
			) {$charset};

			CREATE TABLE {$wpdb->prefix}sf_keywords (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				page_path VARCHAR(512) NOT NULL,
				query VARCHAR(512) NOT NULL,
				snapshot_date DATE NOT NULL,
				clicks INT UNSIGNED NOT NULL DEFAULT 0,
				impressions INT UNSIGNED NOT NULL DEFAULT 0,
				ctr DECIMAL(5,4) NOT NULL DEFAULT 0,
				position DECIMAL(6,2) NOT NULL DEFAULT 0,
				device VARCHAR(10) NOT NULL DEFAULT 'all',
				source VARCHAR(20) NOT NULL DEFAULT 'gsc',
				search_volume INT UNSIGNED DEFAULT NULL,
				competition VARCHAR(10) DEFAULT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_page_query (page_path, query(100)),
				KEY idx_query (query(100)),
				KEY idx_source_date (source, snapshot_date)
			) {$charset};

			CREATE TABLE {$wpdb->prefix}sf_sync_log (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				source VARCHAR(20) NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'running',
				pages_synced INT UNSIGNED NOT NULL DEFAULT 0,
				keywords_synced INT UNSIGNED NOT NULL DEFAULT 0,
				error_message TEXT DEFAULT NULL,
				started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				completed_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				KEY idx_source_status (source, status)
			) {$charset};

			CREATE TABLE {$wpdb->prefix}sf_briefs_cache (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				page_path VARCHAR(512) NOT NULL,
				brief_type VARCHAR(30) NOT NULL DEFAULT 'page',
				content LONGTEXT NOT NULL,
				generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				expires_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY idx_page_type (page_path, brief_type),
				KEY idx_expires (expires_at)
			) {$charset};

			CREATE TABLE {$wpdb->prefix}sf_alerts (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				alert_type VARCHAR(30) NOT NULL,
				title VARCHAR(255) NOT NULL,
				severity VARCHAR(10) NOT NULL DEFAULT 'info',
				data LONGTEXT DEFAULT NULL,
				is_read TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_type_read (alert_type, is_read),
				KEY idx_created (created_at)
			) {$charset};

			CREATE TABLE {$wpdb->prefix}sf_settings (
				setting_name VARCHAR(100) NOT NULL,
				setting_value LONGTEXT DEFAULT NULL,
				PRIMARY KEY (setting_name)
			) {$charset};
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
