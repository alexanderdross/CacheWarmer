<?php
/**
 * Lizenzverwaltung: CRUD-Operationen und Validierung.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_License_Manager {

    private string $prefix;

    public function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . CWLM_DB_PREFIX;
    }

    /**
     * Lizenzschlüssel-Format validieren.
     *
     * Erwartetes Format: CW-{TIER}-{16 Hex-Zeichen}
     * Beispiel: CW-PRO-A1B2C3D4E5F6G7H8
     */
    public static function validate_key_format( string $key ): bool {
        return (bool) preg_match( '/^CW-(FREE|PRO|ENT|DEV)-[A-F0-9]{16}$/i', $key );
    }

    /**
     * Lizenz anhand des Schlüssels finden.
     */
    public function find_by_key( string $license_key ): ?object {
        global $wpdb;

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}licenses WHERE license_key = %s LIMIT 1",
                $license_key
            )
        );

        return $license ?: null;
    }

    /**
     * Lizenz anhand der Stripe Subscription-ID finden.
     */
    public function find_by_subscription( string $subscription_id ): ?object {
        global $wpdb;

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}licenses WHERE stripe_subscription_id = %s LIMIT 1",
                $subscription_id
            )
        );

        return $license ?: null;
    }

    /**
     * Prüfen ob eine Lizenz gültig ist (aktiv oder in Karenzzeit und nicht abgelaufen).
     */
    public function is_valid( object $license ): bool {
        if ( in_array( $license->status, [ 'revoked', 'expired' ], true ) ) {
            return false;
        }

        if ( $license->status === 'active' || $license->status === 'grace_period' ) {
            return true;
        }

        return false;
    }

    /**
     * Neue Lizenz erstellen.
     *
     * @param array $data {
     *     @type string $customer_email         Pflichtfeld.
     *     @type string $customer_name          Optional.
     *     @type string $tier                   free|professional|enterprise|development
     *     @type string $plan                   Optional (z.B. starter, business).
     *     @type int    $max_sites              Standard: 1.
     *     @type string $expires_at             MySQL datetime oder null.
     *     @type string $stripe_customer_id     Optional.
     *     @type string $stripe_subscription_id Optional.
     * }
     * @return int|null Lizenz-ID oder null bei Fehler.
     */
    public function create_license( array $data ): ?int {
        global $wpdb;

        $email = sanitize_email( $data['customer_email'] ?? '' );
        if ( empty( $email ) ) {
            return null;
        }

        $tier      = $data['tier'] ?? 'free';
        $tier_map  = [
            'free'         => 'FREE',
            'professional' => 'PRO',
            'enterprise'   => 'ENT',
            'development'  => 'DEV',
        ];
        $prefix_code = $tier_map[ $tier ] ?? 'PRO';
        $license_key = 'CW-' . $prefix_code . '-' . strtoupper( bin2hex( random_bytes( 8 ) ) );

        $inserted = $wpdb->insert(
            $this->prefix . 'licenses',
            [
                'license_key'            => $license_key,
                'customer_email'         => $email,
                'customer_name'          => sanitize_text_field( $data['customer_name'] ?? '' ) ?: null,
                'tier'                   => $tier,
                'plan'                   => sanitize_text_field( $data['plan'] ?? '' ) ?: null,
                'status'                 => 'inactive',
                'max_sites'              => max( 1, (int) ( $data['max_sites'] ?? 1 ) ),
                'stripe_customer_id'     => sanitize_text_field( $data['stripe_customer_id'] ?? '' ) ?: null,
                'stripe_subscription_id' => sanitize_text_field( $data['stripe_subscription_id'] ?? '' ) ?: null,
                'expires_at'             => $data['expires_at'] ?? null,
                'created_at'             => gmdate( 'Y-m-d H:i:s' ),
                'updated_at'             => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            return null;
        }

        $license_id = (int) $wpdb->insert_id;

        // Audit-Log
        if ( class_exists( 'CWLM_Audit_Logger' ) ) {
            $audit = new CWLM_Audit_Logger();
            $audit->log( 'license.created', is_admin() ? 'admin' : 'system', null, $license_id, null, [
                'tier'  => $tier,
                'email' => $email,
            ] );
        }

        return $license_id;
    }

    /**
     * Lizenz um eine bestimmte Anzahl Tage verlängern.
     */
    public function extend_license( int $license_id, int $days ): void {
        global $wpdb;

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}licenses WHERE id = %d",
                $license_id
            )
        );

        if ( ! $license ) {
            return;
        }

        // Basis: aktuelles Ablaufdatum oder jetzt
        $base = ( $license->expires_at && strtotime( $license->expires_at ) > time() )
            ? strtotime( $license->expires_at )
            : time();

        $new_expires = gmdate( 'Y-m-d H:i:s', $base + ( $days * DAY_IN_SECONDS ) );

        $wpdb->update(
            $this->prefix . 'licenses',
            [
                'status'     => 'active',
                'expires_at' => $new_expires,
                'updated_at' => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ 'id' => $license_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( class_exists( 'CWLM_Audit_Logger' ) ) {
            $audit = new CWLM_Audit_Logger();
            $audit->log( 'license.extended', is_admin() ? 'admin' : 'system', null, $license_id, null, [
                'days'        => $days,
                'new_expires' => $new_expires,
            ] );
        }
    }

    /**
     * Lizenz sperren (revoke).
     */
    public function revoke_license( int $license_id, string $reason = '' ): void {
        global $wpdb;

        $wpdb->update(
            $this->prefix . 'licenses',
            [
                'status'     => 'revoked',
                'updated_at' => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ 'id' => $license_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        // Alle aktiven Installationen deaktivieren
        $wpdb->update(
            $this->prefix . 'installations',
            [
                'is_active'      => 0,
                'deactivated_at' => gmdate( 'Y-m-d H:i:s' ),
            ],
            [
                'license_id' => $license_id,
                'is_active'  => 1,
            ],
            [ '%d', '%s' ],
            [ '%d', '%d' ]
        );

        // active_sites auf 0 setzen
        $wpdb->update(
            $this->prefix . 'licenses',
            [ 'active_sites' => 0 ],
            [ 'id' => $license_id ],
            [ '%d' ],
            [ '%d' ]
        );

        if ( class_exists( 'CWLM_Audit_Logger' ) ) {
            $audit = new CWLM_Audit_Logger();
            $audit->log( 'license.revoked', is_admin() ? 'admin' : 'system', null, $license_id, null, [
                'reason' => $reason,
            ] );
        }
    }

    /**
     * Lizenzen auflisten mit Filtern und Paginierung.
     *
     * @param array $filters { tier?: string, status?: string, search?: string }
     * @param int   $page     Aktuelle Seite (1-basiert).
     * @param int   $per_page Einträge pro Seite.
     * @return array{ items: object[], total: int }
     */
    public function list_licenses( array $filters = [], int $page = 1, int $per_page = 20 ): array {
        global $wpdb;

        $where  = [];
        $values = [];

        if ( ! empty( $filters['tier'] ) ) {
            $where[]  = 'tier = %s';
            $values[] = $filters['tier'];
        }

        if ( ! empty( $filters['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $filters['status'];
        }

        if ( ! empty( $filters['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where[]  = '(license_key LIKE %s OR customer_email LIKE %s OR customer_name LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $offset    = max( 0, ( $page - 1 ) * $per_page );

        // Gesamtanzahl
        $count_sql = "SELECT COUNT(*) FROM {$this->prefix}licenses {$where_sql}";
        if ( ! empty( $values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, ...$values );
        }
        $total = (int) $wpdb->get_var( $count_sql );

        // Daten
        $data_sql = "SELECT * FROM {$this->prefix}licenses {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $all_values   = array_merge( $values, [ $per_page, $offset ] );
        $data_sql     = $wpdb->prepare( $data_sql, ...$all_values );
        $items        = $wpdb->get_results( $data_sql );

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }
}
