<?php
/**
 * Lizenz-CRUD und Lifecycle-Management.
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
     * Lizenzschlüssel generieren.
     */
    public function generate_license_key( string $tier ): string {
        $tier_map = [
            'free'         => 'FREE',
            'professional' => 'PRO',
            'enterprise'   => 'ENT',
            'development'  => 'DEV',
        ];

        $prefix = $tier_map[ $tier ] ?? 'FREE';
        $key    = strtoupper( bin2hex( random_bytes( 8 ) ) );

        return "CW-{$prefix}-{$key}";
    }

    /**
     * Lizenzschlüssel validieren (Format).
     */
    public static function validate_key_format( string $key ): bool {
        return (bool) preg_match( '/^CW-(FREE|PRO|ENT|DEV)-[A-F0-9]{16}$/', $key );
    }

    /**
     * Lizenz per Key finden.
     */
    public function find_by_key( string $license_key ): ?object {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}licenses WHERE license_key = %s",
                $license_key
            )
        );
    }

    /**
     * Lizenz per Stripe Subscription ID finden.
     */
    public function find_by_subscription( string $subscription_id ): ?object {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}licenses WHERE stripe_subscription_id = %s",
                $subscription_id
            )
        );
    }

    /**
     * Neue Lizenz erstellen.
     *
     * @param array<string, mixed> $data Lizenzdaten.
     * @return int|false Lizenz-ID oder false.
     */
    public function create_license( array $data ): int|false {
        global $wpdb;

        // Tier validieren
        $valid_tiers = [ 'free', 'professional', 'enterprise', 'development' ];
        if ( isset( $data['tier'] ) && ! in_array( $data['tier'], $valid_tiers, true ) ) {
            return false;
        }

        $defaults = [
            'license_key'    => $this->generate_license_key( $data['tier'] ?? 'free' ),
            'customer_email' => '',
            'customer_name'  => null,
            'tier'           => 'free',
            'plan'           => null,
            'status'         => 'inactive',
            'max_sites'      => 1,
            'features_json'  => null,
            'expires_at'     => null,
        ];

        $data = wp_parse_args( $data, $defaults );

        $inserted = $wpdb->insert(
            $this->prefix . 'licenses',
            [
                'license_key'            => $data['license_key'],
                'customer_email'         => sanitize_email( $data['customer_email'] ),
                'customer_name'          => sanitize_text_field( $data['customer_name'] ?? '' ),
                'tier'                   => $data['tier'],
                'plan'                   => $data['plan'],
                'status'                 => $data['status'],
                'max_sites'              => (int) $data['max_sites'],
                'features_json'          => $data['features_json'] ? wp_json_encode( $data['features_json'] ) : null,
                'stripe_customer_id'     => $data['stripe_customer_id'] ?? null,
                'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
                'expires_at'             => $data['expires_at'],
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            return false;
        }

        $license_id = (int) $wpdb->insert_id;

        $audit = new CWLM_Audit_Logger();
        $audit->log( 'license.created', 'system', null, $license_id, null, [
            'tier' => $data['tier'],
            'plan' => $data['plan'],
        ] );

        return $license_id;
    }

    /**
     * Lizenz verlängern.
     */
    public function extend_license( int $license_id, int $days ): bool {
        global $wpdb;

        $license = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->prefix}licenses WHERE id = %d", $license_id )
        );

        if ( ! $license ) {
            return false;
        }

        $base_date = ( $license->expires_at && strtotime( $license->expires_at ) > time() )
            ? $license->expires_at
            : gmdate( 'Y-m-d H:i:s' );

        $new_expiry = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days} days", strtotime( $base_date ) ) );
        $new_status = 'active';

        $wpdb->update(
            $this->prefix . 'licenses',
            [
                'expires_at' => $new_expiry,
                'status'     => $new_status,
                'updated_at' => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ 'id' => $license_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        $audit = new CWLM_Audit_Logger();
        $audit->log( 'license.renewed', 'system', null, $license_id, null, [
            'days'        => $days,
            'new_expiry'  => $new_expiry,
            'old_status'  => $license->status,
        ] );

        return true;
    }

    /**
     * Lizenz sperren.
     */
    public function revoke_license( int $license_id, string $reason = '' ): bool {
        global $wpdb;

        $updated = $wpdb->update(
            $this->prefix . 'licenses',
            [
                'status'     => 'revoked',
                'updated_at' => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ 'id' => $license_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        if ( $updated ) {
            // Alle Installationen deaktivieren
            $wpdb->update(
                $this->prefix . 'installations',
                [
                    'is_active'      => 0,
                    'deactivated_at' => gmdate( 'Y-m-d H:i:s' ),
                ],
                [ 'license_id' => $license_id, 'is_active' => 1 ],
                [ '%d', '%s' ],
                [ '%d', '%d' ]
            );

            $wpdb->update(
                $this->prefix . 'licenses',
                [ 'active_sites' => 0 ],
                [ 'id' => $license_id ],
                [ '%d' ],
                [ '%d' ]
            );

            $audit = new CWLM_Audit_Logger();
            $audit->log( 'license.revoked', 'admin', null, $license_id, null, [
                'reason' => $reason,
            ] );
        }

        return (bool) $updated;
    }

    /**
     * Abgelaufene Lizenzen verarbeiten (Cronjob).
     */
    public function process_expired_licenses(): void {
        global $wpdb;
        $now = gmdate( 'Y-m-d H:i:s' );
        $grace_days = defined( 'CWLM_GRACE_PERIOD_DAYS' ) ? CWLM_GRACE_PERIOD_DAYS : 14;

        // Active → Grace Period (Ablaufdatum überschritten)
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->prefix}licenses
                 SET status = 'grace_period', updated_at = %s
                 WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < %s",
                $now,
                $now
            )
        );

        // Grace Period → Expired (Karenzzeit abgelaufen)
        $grace_cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$grace_days} days" ) );
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->prefix}licenses
                 SET status = 'expired', updated_at = %s
                 WHERE status = 'grace_period' AND expires_at IS NOT NULL AND expires_at < %s",
                $now,
                $grace_cutoff
            )
        );

        // Installationen abgelaufener Lizenzen deaktivieren
        $wpdb->query(
            "UPDATE {$this->prefix}installations i
             INNER JOIN {$this->prefix}licenses l ON i.license_id = l.id
             SET i.is_active = 0, i.deactivated_at = NOW()
             WHERE l.status = 'expired' AND i.is_active = 1"
        );
    }

    /**
     * Prüfe ob Lizenz gültig ist (für API-Responses).
     */
    public function is_valid( object $license ): bool {
        return in_array( $license->status, [ 'active', 'grace_period' ], true );
    }

    /**
     * Lizenzen auflisten (Admin).
     *
     * @param array<string, mixed> $filters Filter.
     * @return array{items: array<object>, total: int}
     */
    public function list_licenses( array $filters = [], int $page = 1, int $per_page = 20 ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $filters['tier'] ) ) {
            $where   .= ' AND tier = %s';
            $params[] = $filters['tier'];
        }
        if ( ! empty( $filters['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $filters['status'];
        }
        if ( ! empty( $filters['search'] ) ) {
            $search   = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where   .= ' AND (license_key LIKE %s OR customer_email LIKE %s OR customer_name LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $total_query = "SELECT COUNT(*) FROM {$this->prefix}licenses WHERE {$where}";
        $total       = $params
            ? (int) $wpdb->get_var( $wpdb->prepare( $total_query, ...$params ) )
            : (int) $wpdb->get_var( $total_query );

        $offset    = ( $page - 1 ) * $per_page;
        $query     = "SELECT * FROM {$this->prefix}licenses WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[]  = $per_page;
        $params[]  = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }
}
