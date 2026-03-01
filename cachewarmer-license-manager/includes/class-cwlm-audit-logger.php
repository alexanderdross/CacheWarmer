<?php
/**
 * Audit-Trail Logger.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Audit_Logger {

    private string $prefix;

    public function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . CWLM_DB_PREFIX;
    }

    /**
     * Audit-Log-Eintrag schreiben.
     *
     * @param string               $action         Aktion (z.B. 'license.created').
     * @param string               $actor_type     Akteur-Typ: 'system', 'admin', 'api', 'stripe'.
     * @param string|null          $actor_id       WP User ID oder Identifier.
     * @param int|null             $license_id     Zugehörige Lizenz.
     * @param int|null             $installation_id Zugehörige Installation.
     * @param array<string, mixed> $details        Zusätzliche Daten.
     */
    public function log(
        string $action,
        string $actor_type,
        ?string $actor_id = null,
        ?int $license_id = null,
        ?int $installation_id = null,
        array $details = []
    ): void {
        global $wpdb;

        $wpdb->insert(
            $this->prefix . 'audit_logs',
            [
                'license_id'      => $license_id,
                'installation_id' => $installation_id,
                'action'          => $action,
                'actor_type'      => $actor_type,
                'actor_id'        => $actor_id ?? ( is_user_logged_in() ? (string) get_current_user_id() : null ),
                'ip_address'      => self::get_anonymized_ip(),
                'details_json'    => ! empty( $details ) ? wp_json_encode( $details ) : null,
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * IP-Adresse anonymisieren (letztes Oktett auf 0).
     */
    public static function get_anonymized_ip(): string {
        $ip = self::get_client_ip();

        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            return preg_replace( '/\.\d+$/', '.0', $ip );
        }

        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            $packed = inet_pton( $ip );
            // Letzte 10 Bytes (80 Bits) nullen
            for ( $i = 6; $i < 16; $i++ ) {
                $packed[ $i ] = "\0";
            }
            return inet_ntop( $packed );
        }

        return '0.0.0.0';
    }

    /**
     * Client-IP ermitteln.
     *
     * Vertraut Proxy-Headern nur wenn CWLM_TRUSTED_PROXIES konfiguriert ist.
     * Verhindert IP-Spoofing über X-Forwarded-For Header.
     */
    public static function get_client_ip(): string {
        $remote_addr = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

        // Proxy-Header nur auswerten wenn REMOTE_ADDR ein vertrauenswürdiger Proxy ist
        $trusted_proxies = defined( 'CWLM_TRUSTED_PROXIES' ) ? (array) CWLM_TRUSTED_PROXIES : [];

        if ( ! empty( $trusted_proxies ) && in_array( $remote_addr, $trusted_proxies, true ) ) {
            // Cloudflare-Header (einzelne IP, nicht fälschbar hinter CF)
            if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
                $ip = trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }

            // X-Real-IP (Nginx reverse proxy)
            if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
                $ip = trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ) );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }

            // X-Forwarded-For: erstes öffentliche IP
            if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
                foreach ( $ips as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                        return $ip;
                    }
                }
            }
        }

        // Fallback: REMOTE_ADDR (nicht manipulierbar)
        if ( filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
            return $remote_addr;
        }

        return '0.0.0.0';
    }

    /**
     * Audit-Logs abfragen (Admin).
     *
     * @param array<string, mixed> $filters Filter.
     * @return array{items: array<object>, total: int}
     */
    public function get_logs( array $filters = [], int $page = 1, int $per_page = 50 ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $filters['license_id'] ) ) {
            $where   .= ' AND license_id = %d';
            $params[] = (int) $filters['license_id'];
        }
        if ( ! empty( $filters['action'] ) ) {
            $where   .= ' AND action = %s';
            $params[] = $filters['action'];
        }
        if ( ! empty( $filters['actor_type'] ) ) {
            $where   .= ' AND actor_type = %s';
            $params[] = $filters['actor_type'];
        }

        $total_query = "SELECT COUNT(*) FROM {$this->prefix}audit_logs WHERE {$where}";
        $total       = $params
            ? (int) $wpdb->get_var( $wpdb->prepare( $total_query, ...$params ) )
            : (int) $wpdb->get_var( $total_query );

        $offset    = ( $page - 1 ) * $per_page;
        $query     = "SELECT * FROM {$this->prefix}audit_logs WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[]  = $per_page;
        $params[]  = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }
}
