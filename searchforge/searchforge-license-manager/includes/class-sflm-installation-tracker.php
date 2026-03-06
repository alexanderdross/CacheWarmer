<?php
/**
 * Installations-Tracking und -Verwaltung.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFLM_Installation_Tracker {

    private string $prefix;

    public function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . SFLM_DB_PREFIX;
    }

    /**
     * Fingerprint validieren.
     */
    public static function validate_fingerprint( string $fingerprint ): bool {
        return (bool) preg_match( '/^[a-f0-9]{64}$/', $fingerprint );
    }

    /**
     * Platform validieren.
     */
    public static function validate_platform( string $platform ): bool {
        return in_array( $platform, [ 'nodejs', 'docker', 'wordpress', 'drupal' ], true );
    }

    /**
     * Installation registrieren oder aktualisieren.
     *
     * @param array<string, mixed> $data Installationsdaten.
     * @return array{id: int, is_new: bool}|WP_Error
     */
    public function activate( int $license_id, array $data ): array|\WP_Error {
        global $wpdb;

        $fingerprint = $data['fingerprint'];

        // Prüfe ob Installation bereits existiert
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}installations
                 WHERE license_id = %d AND fingerprint = %s",
                $license_id,
                $fingerprint
            )
        );

        if ( $existing ) {
            // Re-Aktivierung: Bestehende Installation aktualisieren
            $wpdb->update(
                $this->prefix . 'installations',
                [
                    'domain'              => $data['domain'] ?? $existing->domain,
                    'hostname'            => $data['hostname'] ?? $existing->hostname,
                    'platform'            => $data['platform'],
                    'platform_version'    => $data['platform_version'] ?? null,
                    'product_version' => $data['product_version'] ?? null,
                    'os_platform'         => $data['os_platform'] ?? null,
                    'os_version'          => $data['os_version'] ?? null,
                    'ip_address'          => $data['ip_address'] ?? null,
                    'last_check'          => gmdate( 'Y-m-d H:i:s' ),
                    'is_active'           => 1,
                    'deactivated_at'      => null,
                ],
                [ 'id' => $existing->id ],
                [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', null ],
                [ '%d' ]
            );

            // Zähler nur aktualisieren wenn vorher inaktiv
            if ( ! $existing->is_active ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->prefix}licenses SET active_sites = active_sites + 1 WHERE id = %d",
                        $license_id
                    )
                );
            }

            return [ 'id' => (int) $existing->id, 'is_new' => false ];
        }

        // Neue Installation
        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT max_sites, active_sites FROM {$this->prefix}licenses WHERE id = %d",
                $license_id
            )
        );

        if ( $license && (int) $license->active_sites >= (int) $license->max_sites ) {
            return new \WP_Error(
                'SITE_LIMIT_REACHED',
                sprintf(
                    'Maximale Installationen erreicht (%d/%d).',
                    $license->active_sites,
                    $license->max_sites
                ),
                [ 'status' => 409 ]
            );
        }

        $wpdb->insert(
            $this->prefix . 'installations',
            [
                'license_id'          => $license_id,
                'domain'              => $data['domain'] ?? null,
                'hostname'            => $data['hostname'] ?? null,
                'fingerprint'         => $fingerprint,
                'platform'            => $data['platform'],
                'platform_version'    => $data['platform_version'] ?? null,
                'product_version' => $data['product_version'] ?? null,
                'os_platform'         => $data['os_platform'] ?? null,
                'os_version'          => $data['os_version'] ?? null,
                'ip_address'          => $data['ip_address'] ?? null,
                'last_check'          => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        $installation_id = (int) $wpdb->insert_id;

        // Aktive Sites hochzählen
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->prefix}licenses SET active_sites = active_sites + 1, activated_at = COALESCE(activated_at, %s) WHERE id = %d",
                gmdate( 'Y-m-d H:i:s' ),
                $license_id
            )
        );

        return [ 'id' => $installation_id, 'is_new' => true ];
    }

    /**
     * Installation deaktivieren.
     */
    public function deactivate( int $license_id, string $fingerprint ): bool {
        global $wpdb;

        $installation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->prefix}installations
                 WHERE license_id = %d AND fingerprint = %s AND is_active = 1",
                $license_id,
                $fingerprint
            )
        );

        if ( ! $installation ) {
            return false;
        }

        $wpdb->update(
            $this->prefix . 'installations',
            [
                'is_active'      => 0,
                'deactivated_at' => gmdate( 'Y-m-d H:i:s' ),
            ],
            [ 'id' => $installation->id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );

        // Aktive Sites runterzählen
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->prefix}licenses SET active_sites = GREATEST(active_sites - 1, 0) WHERE id = %d",
                $license_id
            )
        );

        return true;
    }

    /**
     * Stale Installationen deaktivieren (Cronjob).
     */
    public function deactivate_stale_installations( int $days = 7 ): int {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        $now    = gmdate( 'Y-m-d H:i:s' );

        // Batch-Update: Alle stale Installationen in einem Query deaktivieren
        $affected = (int) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->prefix}installations
                 SET is_active = 0, deactivated_at = %s
                 WHERE is_active = 1 AND last_check IS NOT NULL AND last_check < %s",
                $now,
                $cutoff
            )
        );

        if ( $affected > 0 ) {
            // Batch-Update: active_sites-Zähler pro Lizenz korrigieren
            $wpdb->query(
                "UPDATE {$this->prefix}licenses l
                 SET l.active_sites = (
                     SELECT COUNT(*) FROM {$this->prefix}installations i
                     WHERE i.license_id = l.id AND i.is_active = 1
                 )
                 WHERE l.active_sites > 0"
            );
        }

        return $affected;
    }

    /**
     * Heartbeat verarbeiten.
     */
    public function update_heartbeat( int $license_id, string $fingerprint, ?string $product_version = null ): bool {
        global $wpdb;

        $update_data = [
            'last_check' => gmdate( 'Y-m-d H:i:s' ),
        ];
        $formats = [ '%s' ];

        if ( $product_version ) {
            $update_data['product_version'] = $product_version;
            $formats[] = '%s';
        }

        $updated = $wpdb->update(
            $this->prefix . 'installations',
            $update_data,
            [
                'license_id'  => $license_id,
                'fingerprint' => $fingerprint,
                'is_active'   => 1,
            ],
            $formats,
            [ '%d', '%s', '%d' ]
        );

        return (bool) $updated;
    }
}
