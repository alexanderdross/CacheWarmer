<?php
/**
 * Database management for CacheWarmer.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CacheWarmer_Database {

    /**
     * Create or update database tables.
     */
    public function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}cachewarmer_sitemaps (
            id VARCHAR(36) NOT NULL,
            url TEXT NOT NULL,
            domain VARCHAR(255) NOT NULL,
            cron_expression VARCHAR(100) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_warmed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_domain (domain)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}cachewarmer_jobs (
            id VARCHAR(36) NOT NULL,
            sitemap_id VARCHAR(36) DEFAULT NULL,
            sitemap_url TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            total_urls INT NOT NULL DEFAULT 0,
            processed_urls INT NOT NULL DEFAULT 0,
            targets TEXT,
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            error TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_sitemap_id (sitemap_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}cachewarmer_url_results (
            id VARCHAR(36) NOT NULL,
            job_id VARCHAR(36) NOT NULL,
            url TEXT NOT NULL,
            target VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            http_status INT DEFAULT NULL,
            duration_ms INT DEFAULT NULL,
            error TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_job_id (job_id),
            KEY idx_target (target),
            KEY idx_status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'cachewarmer_db_version', CACHEWARMER_DB_VERSION );
    }

    // ──────────────────────────────────────────────
    // Sitemaps
    // ──────────────────────────────────────────────

    public function insert_sitemap( array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->insert(
            "{$wpdb->prefix}cachewarmer_sitemaps",
            array(
                'id'              => $data['id'],
                'url'             => $data['url'],
                'domain'          => $data['domain'],
                'cron_expression' => $data['cron_expression'] ?? null,
                'created_at'      => current_time( 'mysql', true ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function get_sitemap( string $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cachewarmer_sitemaps WHERE id = %s",
                $id
            )
        );
    }

    public function get_all_sitemaps(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}cachewarmer_sitemaps ORDER BY created_at DESC"
        );
    }

    public function delete_sitemap( string $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete(
            "{$wpdb->prefix}cachewarmer_sitemaps",
            array( 'id' => $id ),
            array( '%s' )
        );
    }

    public function update_sitemap_last_warmed( string $id ): void {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}cachewarmer_sitemaps",
            array( 'last_warmed_at' => current_time( 'mysql', true ) ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%s' )
        );
    }

    // ──────────────────────────────────────────────
    // Jobs
    // ──────────────────────────────────────────────

    public function insert_job( array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->insert(
            "{$wpdb->prefix}cachewarmer_jobs",
            array(
                'id'          => $data['id'],
                'sitemap_id'  => $data['sitemap_id'] ?? null,
                'sitemap_url' => $data['sitemap_url'],
                'status'      => 'queued',
                'total_urls'  => $data['total_urls'] ?? 0,
                'targets'     => wp_json_encode( $data['targets'] ),
                'created_at'  => current_time( 'mysql', true ),
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    public function get_job( string $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cachewarmer_jobs WHERE id = %s",
                $id
            )
        );
    }

    public function get_jobs( int $limit = 50, int $offset = 0 ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cachewarmer_jobs ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    public function update_job( string $id, array $data ): bool {
        global $wpdb;
        $formats = array();
        foreach ( $data as $key => $value ) {
            if ( is_int( $value ) ) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }
        return (bool) $wpdb->update(
            "{$wpdb->prefix}cachewarmer_jobs",
            $data,
            array( 'id' => $id ),
            $formats,
            array( '%s' )
        );
    }

    public function delete_job( string $id ): bool {
        global $wpdb;
        $wpdb->delete(
            "{$wpdb->prefix}cachewarmer_url_results",
            array( 'job_id' => $id ),
            array( '%s' )
        );
        return (bool) $wpdb->delete(
            "{$wpdb->prefix}cachewarmer_jobs",
            array( 'id' => $id ),
            array( '%s' )
        );
    }

    // ──────────────────────────────────────────────
    // URL Results
    // ──────────────────────────────────────────────

    public function insert_url_result( array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->insert(
            "{$wpdb->prefix}cachewarmer_url_results",
            array(
                'id'          => $data['id'],
                'job_id'      => $data['job_id'],
                'url'         => $data['url'],
                'target'      => $data['target'],
                'status'      => $data['status'],
                'http_status' => $data['http_status'] ?? null,
                'duration_ms' => $data['duration_ms'] ?? null,
                'error'       => $data['error'] ?? null,
                'created_at'  => current_time( 'mysql', true ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
        );
    }

    /**
     * Get failed and skipped URL results for a job.
     *
     * @param string $job_id Job ID.
     * @return array
     */
    public function get_failed_skipped_results( string $job_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cachewarmer_url_results WHERE job_id = %s AND status IN ('failed', 'skipped') ORDER BY created_at DESC",
                $job_id
            ),
            ARRAY_A
        );
    }

    public function get_job_results( string $job_id, int $limit = 500, int $offset = 0 ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cachewarmer_url_results WHERE job_id = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $job_id,
                $limit,
                $offset
            )
        );
    }

    public function get_job_stats( string $job_id ): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT target, status, COUNT(*) as count FROM {$wpdb->prefix}cachewarmer_url_results WHERE job_id = %s GROUP BY target, status",
                $job_id
            ),
            ARRAY_A
        );

        $stats = array();
        foreach ( $rows as $row ) {
            if ( ! isset( $stats[ $row['target'] ] ) ) {
                $stats[ $row['target'] ] = array(
                    'success' => 0,
                    'failed'  => 0,
                    'skipped' => 0,
                    'pending' => 0,
                );
            }
            $stats[ $row['target'] ][ $row['status'] ] = (int) $row['count'];
        }
        return $stats;
    }

    public function get_total_urls_processed(): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cachewarmer_url_results WHERE status = 'success'"
        );
    }

    public function get_job_counts(): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}cachewarmer_jobs GROUP BY status",
            ARRAY_A
        );
        $counts = array(
            'queued'    => 0,
            'running'   => 0,
            'completed' => 0,
            'failed'    => 0,
        );
        foreach ( $rows as $row ) {
            $counts[ $row['status'] ] = (int) $row['count'];
        }
        return $counts;
    }

    public function get_logs( int $limit = 100, int $offset = 0, ?string $job_id = null ): array {
        global $wpdb;
        if ( $job_id ) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}cachewarmer_url_results WHERE job_id = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $job_id,
                    $limit,
                    $offset
                )
            );
        }
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cachewarmer_url_results ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }
}
