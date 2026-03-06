<?php
/**
 * Plugin-Aktivierung: Datenbank-Tabellen erstellen.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFLM_Activator {

    /**
     * Plugin-Aktivierung durchführen.
     */
    public static function activate(): void {
        self::create_tables();
        update_option( 'sflm_version', SFLM_VERSION );
        flush_rewrite_rules();
    }

    /**
     * Alle Datenbank-Tabellen erstellen.
     */
    private static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix          = $wpdb->prefix . SFLM_DB_PREFIX;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Licenses
        $sql = "CREATE TABLE {$prefix}licenses (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key     VARCHAR(30) NOT NULL,
            customer_email  VARCHAR(255) NOT NULL,
            customer_name   VARCHAR(255) DEFAULT NULL,
            tier            VARCHAR(20) NOT NULL DEFAULT 'free',
            plan            VARCHAR(50) DEFAULT NULL,
            status          VARCHAR(20) NOT NULL DEFAULT 'inactive',
            max_sites       INT UNSIGNED NOT NULL DEFAULT 1,
            active_sites    INT UNSIGNED NOT NULL DEFAULT 0,
            features_json   LONGTEXT DEFAULT NULL,
            stripe_customer_id     VARCHAR(255) DEFAULT NULL,
            stripe_subscription_id VARCHAR(255) DEFAULT NULL,
            expires_at      DATETIME DEFAULT NULL,
            activated_at    DATETIME DEFAULT NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes           TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_license_key (license_key),
            KEY idx_customer_email (customer_email),
            KEY idx_tier (tier),
            KEY idx_status (status),
            KEY idx_stripe_customer (stripe_customer_id),
            KEY idx_stripe_subscription (stripe_subscription_id),
            KEY idx_expires_at (expires_at),
            KEY idx_status_expires (status, expires_at)
        ) $charset_collate;";
        dbDelta( $sql );

        // 2. Installations
        $sql = "CREATE TABLE {$prefix}installations (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id      BIGINT UNSIGNED NOT NULL,
            domain          VARCHAR(255) DEFAULT NULL,
            hostname        VARCHAR(255) DEFAULT NULL,
            fingerprint     VARCHAR(64) NOT NULL,
            platform        VARCHAR(20) NOT NULL,
            platform_version VARCHAR(20) DEFAULT NULL,
            product_version     VARCHAR(20) DEFAULT NULL,
            os_platform     VARCHAR(50) DEFAULT NULL,
            os_version      VARCHAR(50) DEFAULT NULL,
            ip_address      VARCHAR(45) DEFAULT NULL,
            last_check      DATETIME DEFAULT NULL,
            is_active       TINYINT(1) NOT NULL DEFAULT 1,
            activated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deactivated_at  DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_license_fingerprint (license_id, fingerprint),
            KEY idx_domain (domain),
            KEY idx_platform (platform),
            KEY idx_last_check (last_check),
            KEY idx_is_active (is_active),
            KEY idx_activated_at (activated_at),
            KEY idx_active_lastcheck (is_active, last_check)
        ) $charset_collate;";
        dbDelta( $sql );

        // 3. Geo Data
        $sql = "CREATE TABLE {$prefix}geo_data (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            installation_id BIGINT UNSIGNED NOT NULL,
            country_code    CHAR(2) DEFAULT NULL,
            country_name    VARCHAR(100) DEFAULT NULL,
            region          VARCHAR(100) DEFAULT NULL,
            city            VARCHAR(100) DEFAULT NULL,
            latitude        DECIMAL(10,7) DEFAULT NULL,
            longitude       DECIMAL(10,7) DEFAULT NULL,
            timezone        VARCHAR(50) DEFAULT NULL,
            isp             VARCHAR(255) DEFAULT NULL,
            fetched_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_installation_id (installation_id),
            KEY idx_country (country_code),
            KEY idx_fetched_at (fetched_at)
        ) $charset_collate;";
        dbDelta( $sql );

        // 4. Audit Logs
        $sql = "CREATE TABLE {$prefix}audit_logs (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id      BIGINT UNSIGNED DEFAULT NULL,
            installation_id BIGINT UNSIGNED DEFAULT NULL,
            action          VARCHAR(50) NOT NULL,
            actor_type      VARCHAR(20) NOT NULL,
            actor_id        VARCHAR(255) DEFAULT NULL,
            ip_address      VARCHAR(45) DEFAULT NULL,
            details_json    LONGTEXT DEFAULT NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_license (license_id),
            KEY idx_action (action),
            KEY idx_actor (actor_type),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        dbDelta( $sql );

        // 5. Stripe Events
        $sql = "CREATE TABLE {$prefix}stripe_events (
            id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            stripe_event_id   VARCHAR(255) NOT NULL,
            event_type        VARCHAR(100) NOT NULL,
            payload_json      LONGTEXT NOT NULL,
            processing_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            license_id        BIGINT UNSIGNED DEFAULT NULL,
            error_message     TEXT DEFAULT NULL,
            received_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at      DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_stripe_event_id (stripe_event_id),
            KEY idx_event_type (event_type),
            KEY idx_processing_status (processing_status),
            KEY idx_received_at (received_at)
        ) $charset_collate;";
        dbDelta( $sql );

        // 6. Stripe Product Map
        $sql = "CREATE TABLE {$prefix}stripe_product_map (
            id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            stripe_product_id VARCHAR(255) NOT NULL,
            stripe_price_id   VARCHAR(255) DEFAULT NULL,
            tier              VARCHAR(20) NOT NULL,
            plan              VARCHAR(50) NOT NULL,
            max_sites         INT UNSIGNED NOT NULL DEFAULT 1,
            duration_days     INT UNSIGNED DEFAULT 365,
            description       VARCHAR(255) DEFAULT NULL,
            is_active         TINYINT(1) NOT NULL DEFAULT 1,
            created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_product_price (stripe_product_id, stripe_price_id),
            KEY idx_tier (tier)
        ) $charset_collate;";
        dbDelta( $sql );

    }
}
