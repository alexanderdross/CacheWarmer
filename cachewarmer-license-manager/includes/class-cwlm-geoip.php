<?php
/**
 * GeoIP-Integration via MaxMind GeoLite2.
 *
 * Nutzt die geoip2/geoip2 Composer-Dependency für IP-basierte Geolokalisierung.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_GeoIP {

    private ?object $reader = null;
    private string $prefix;

    public function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . CWLM_DB_PREFIX;
        $this->init_reader();
    }

    /**
     * MaxMind Reader initialisieren.
     */
    private function init_reader(): void {
        $db_path = defined( 'CWLM_MAXMIND_DB_PATH' ) ? CWLM_MAXMIND_DB_PATH : '';

        if ( ! $db_path || ! file_exists( $db_path ) ) {
            return;
        }

        // Prüfe ob Composer-Autoloader vorhanden
        $autoload = CWLM_PLUGIN_DIR . 'vendor/autoload.php';
        if ( file_exists( $autoload ) ) {
            require_once $autoload;
        }

        if ( ! class_exists( '\GeoIp2\Database\Reader' ) ) {
            return;
        }

        try {
            $this->reader = new \GeoIp2\Database\Reader( $db_path );
        } catch ( \Exception $e ) {
            // Reader konnte nicht initialisiert werden
            $this->reader = null;
        }
    }

    /**
     * Prüfe ob GeoIP verfügbar ist.
     */
    public function is_available(): bool {
        return null !== $this->reader;
    }

    /**
     * Geo-Daten für eine IP-Adresse abrufen.
     *
     * @return array<string, mixed>|null
     */
    public function lookup( string $ip ): ?array {
        if ( ! $this->reader ) {
            return null;
        }

        // Anonymisierte IPs nicht lookupbar
        if ( str_ends_with( $ip, '.0' ) || '0.0.0.0' === $ip ) {
            return null;
        }

        try {
            $record = $this->reader->city( $ip );

            return [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
                'region'       => $record->mostSpecificSubdivision->name,
                'city'         => $record->city->name,
                'latitude'     => $record->location->latitude,
                'longitude'    => $record->location->longitude,
                'timezone'     => $record->location->timeZone,
            ];
        } catch ( \GeoIp2\Exception\AddressNotFoundException $e ) {
            return null;
        } catch ( \Exception $e ) {
            return null;
        }
    }

    /**
     * Geo-Daten für eine Installation speichern.
     */
    public function store_for_installation( int $installation_id, string $ip ): bool {
        $data = $this->lookup( $ip );
        if ( ! $data ) {
            return false;
        }

        global $wpdb;

        // Vorhandenen Eintrag aktualisieren oder neuen erstellen
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$this->prefix}geo_data WHERE installation_id = %d",
            $installation_id
        ) );

        if ( $existing ) {
            $wpdb->update(
                $this->prefix . 'geo_data',
                array_merge( $data, [ 'fetched_at' => gmdate( 'Y-m-d H:i:s' ) ] ),
                [ 'installation_id' => $installation_id ],
                [ '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s' ],
                [ '%d' ]
            );
        } else {
            $wpdb->insert(
                $this->prefix . 'geo_data',
                array_merge( $data, [
                    'installation_id' => $installation_id,
                    'fetched_at'      => gmdate( 'Y-m-d H:i:s' ),
                ] ),
                [ '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s' ]
            );
        }

        return true;
    }

    /**
     * Geo-Daten für eine Installation abrufen.
     *
     * @return object|null
     */
    public function get_for_installation( int $installation_id ): ?object {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->prefix}geo_data WHERE installation_id = %d ORDER BY fetched_at DESC LIMIT 1",
            $installation_id
        ) );
    }
}
