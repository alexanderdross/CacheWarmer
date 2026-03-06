<?php
/**
 * Admin Dashboard View – KPIs, Charts, letzte Aktivitäten.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . SFLM_DB_PREFIX;

// Prüfe ob Tabellen existieren (Schutz falls Plugin nicht korrekt aktiviert)
$table_licenses      = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $prefix . 'licenses' ) . "'" );
$table_installations = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $prefix . 'installations' ) . "'" );
$table_audit_logs    = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $prefix . 'audit_logs' ) . "'" );
$table_exists        = $table_licenses;

$total_licenses    = 0;
$active_licenses   = 0;
$grace_licenses    = 0;
$expiring_soon     = 0;
$active_installs   = 0;
$today_activations = 0;
$tier_data         = [ 0, 0, 0, 0 ];
$platform_data     = [ 0, 0, 0, 0 ];
$timeline_labels   = [];
$timeline_data     = [];
$recent_logs       = [];

if ( $table_exists ) {
    // Transient-Cache für Dashboard-Daten (5 Minuten)
    $cache_key  = 'sflm_dashboard_data';
    $cache_data = get_transient( $cache_key );

    if ( false === $cache_data ) {
        // KPIs: Kombinierte Single-Query statt 6 einzelne COUNT(*)
        $kpi_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COUNT(*) AS total_licenses,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_licenses,
                SUM(CASE WHEN status = 'grace_period' THEN 1 ELSE 0 END) AS grace_licenses,
                SUM(CASE WHEN status = 'active' AND expires_at BETWEEN %s AND %s THEN 1 ELSE 0 END) AS expiring_soon
             FROM {$prefix}licenses",
            gmdate( 'Y-m-d H:i:s' ),
            gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) )
        ) );

        $install_kpi     = null;
        $platform_counts = [];
        $timeline_raw    = [];

        if ( $table_installations ) {
            $install_kpi = $wpdb->get_row( $wpdb->prepare(
                "SELECT
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_installs,
                    SUM(CASE WHEN activated_at >= %s THEN 1 ELSE 0 END) AS today_activations
                 FROM {$prefix}installations",
                gmdate( 'Y-m-d 00:00:00' )
            ) );

            // Chart-Daten: Plattform-Verteilung
            $platform_counts = $wpdb->get_results(
                "SELECT platform, COUNT(*) as cnt FROM {$prefix}installations WHERE is_active = 1 GROUP BY platform",
                OBJECT_K
            );

            // Chart-Daten: Aktivierungen letzte 30 Tage
            $timeline_raw = $wpdb->get_results( $wpdb->prepare(
                "SELECT DATE(activated_at) AS act_date, COUNT(*) AS cnt
                 FROM {$prefix}installations
                 WHERE activated_at >= %s
                 GROUP BY DATE(activated_at)",
                gmdate( 'Y-m-d', strtotime( '-29 days' ) )
            ), OBJECT_K );
        }

        // Chart-Daten: Tier-Verteilung
        $tier_counts = $wpdb->get_results(
            "SELECT tier, COUNT(*) as cnt FROM {$prefix}licenses WHERE status IN ('active','grace_period') GROUP BY tier",
            OBJECT_K
        );

        // Nur cachen wenn Queries erfolgreich waren
        if ( null !== $kpi_row ) {
            $cache_data = [
                'kpi_row'         => $kpi_row,
                'install_kpi'     => $install_kpi,
                'tier_counts'     => is_array( $tier_counts ) ? $tier_counts : [],
                'platform_counts' => is_array( $platform_counts ) ? $platform_counts : [],
                'timeline_raw'    => is_array( $timeline_raw ) ? $timeline_raw : [],
            ];
            set_transient( $cache_key, $cache_data, 300 ); // 5 Minuten Cache
        }
    }

    if ( is_array( $cache_data ) ) {
        // Cache auspacken
        $kpi_row         = $cache_data['kpi_row'] ?? null;
        $install_kpi     = $cache_data['install_kpi'] ?? null;
        $tier_counts     = $cache_data['tier_counts'] ?? [];
        $platform_counts = $cache_data['platform_counts'] ?? [];
        $timeline_raw    = $cache_data['timeline_raw'] ?? [];

        $total_licenses    = $kpi_row ? (int) $kpi_row->total_licenses : 0;
        $active_licenses   = $kpi_row ? (int) $kpi_row->active_licenses : 0;
        $grace_licenses    = $kpi_row ? (int) $kpi_row->grace_licenses : 0;
        $expiring_soon     = $kpi_row ? (int) $kpi_row->expiring_soon : 0;
        $active_installs   = $install_kpi ? (int) $install_kpi->active_installs : 0;
        $today_activations = $install_kpi ? (int) $install_kpi->today_activations : 0;

        $tier_data = [
            isset( $tier_counts['free'] ) ? (int) $tier_counts['free']->cnt : 0,
            isset( $tier_counts['professional'] ) ? (int) $tier_counts['professional']->cnt : 0,
            isset( $tier_counts['enterprise'] ) ? (int) $tier_counts['enterprise']->cnt : 0,
            isset( $tier_counts['development'] ) ? (int) $tier_counts['development']->cnt : 0,
        ];

        $platform_data = [
            isset( $platform_counts['nodejs'] ) ? (int) $platform_counts['nodejs']->cnt : 0,
            isset( $platform_counts['docker'] ) ? (int) $platform_counts['docker']->cnt : 0,
            isset( $platform_counts['wordpress'] ) ? (int) $platform_counts['wordpress']->cnt : 0,
            isset( $platform_counts['drupal'] ) ? (int) $platform_counts['drupal']->cnt : 0,
        ];
    }

    // Timeline: Batch-Ergebnis auf 30-Tage-Array mappen
    for ( $i = 29; $i >= 0; $i-- ) {
        $date              = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
        $timeline_labels[] = gmdate( 'd.m.', strtotime( "-{$i} days" ) );
        $timeline_data[]   = isset( $timeline_raw[ $date ] ) ? (int) $timeline_raw[ $date ]->cnt : 0;
    }

    // Geo-Daten: Installations-Verteilung nach Land/Stadt
    $geo_countries    = [];
    $geo_cities       = [];
    $geo_map_points   = [];
    $table_geo        = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $prefix . 'geo_data' ) . "'" );

    if ( $table_geo ) {
        $geo_cache_key  = 'sflm_dashboard_geo';
        $geo_cache_data = get_transient( $geo_cache_key );

        if ( false === $geo_cache_data ) {
            // Top Länder nach Anzahl aktiver Installationen
            $geo_countries_raw = $wpdb->get_results(
                "SELECT g.country_code, g.country_name, COUNT(DISTINCT g.installation_id) AS install_count
                 FROM {$prefix}geo_data g
                 INNER JOIN {$prefix}installations i ON g.installation_id = i.id AND i.is_active = 1
                 WHERE g.country_code IS NOT NULL
                 GROUP BY g.country_code, g.country_name
                 ORDER BY install_count DESC
                 LIMIT 50"
            );

            // Top Städte nach Anzahl aktiver Installationen
            $geo_cities_raw = $wpdb->get_results(
                "SELECT g.country_code, g.country_name, g.city, COUNT(DISTINCT g.installation_id) AS install_count
                 FROM {$prefix}geo_data g
                 INNER JOIN {$prefix}installations i ON g.installation_id = i.id AND i.is_active = 1
                 WHERE g.city IS NOT NULL AND g.city != ''
                 GROUP BY g.country_code, g.country_name, g.city
                 ORDER BY install_count DESC
                 LIMIT 30"
            );

            // Kartenpunkte: Durchschnittliche Koordinaten pro Land
            $geo_map_raw = $wpdb->get_results(
                "SELECT g.country_code, g.country_name,
                        AVG(g.latitude) AS lat, AVG(g.longitude) AS lng,
                        COUNT(DISTINCT g.installation_id) AS install_count
                 FROM {$prefix}geo_data g
                 INNER JOIN {$prefix}installations i ON g.installation_id = i.id AND i.is_active = 1
                 WHERE g.latitude IS NOT NULL AND g.longitude IS NOT NULL
                 GROUP BY g.country_code, g.country_name"
            );

            $geo_cache_data = [
                'countries' => is_array( $geo_countries_raw ) ? $geo_countries_raw : [],
                'cities'    => is_array( $geo_cities_raw ) ? $geo_cities_raw : [],
                'map'       => is_array( $geo_map_raw ) ? $geo_map_raw : [],
            ];
            set_transient( $geo_cache_key, $geo_cache_data, 300 );
        }

        $geo_countries  = $geo_cache_data['countries'] ?? [];
        $geo_cities     = $geo_cache_data['cities'] ?? [];
        $geo_map_points = $geo_cache_data['map'] ?? [];
    }

    // Letzte Audit-Einträge (nicht gecacht – soll stets aktuell sein)
    if ( $table_audit_logs ) {
        $recent_logs = $wpdb->get_results(
            "SELECT * FROM {$prefix}audit_logs ORDER BY created_at DESC LIMIT 10"
        );
        if ( ! is_array( $recent_logs ) ) {
            $recent_logs = [];
        }
    }
} else {
    // Tabellen existieren nicht – leere Timeline füllen
    for ( $i = 29; $i >= 0; $i-- ) {
        $timeline_labels[] = gmdate( 'd.m.', strtotime( "-{$i} days" ) );
        $timeline_data[]   = 0;
    }
}
?>
<div class="wrap" role="main">
    <h1><?php esc_html_e( 'SearchForge License Manager – Dashboard', 'sflm' ); ?></h1>

    <!-- KPI Cards -->
    <div class="sflm-kpi-grid" aria-live="polite">
        <div class="sflm-kpi-card">
            <div class="sflm-kpi-value"><?php echo esc_html( $total_licenses ); ?></div>
            <div class="sflm-kpi-label"><?php esc_html_e( 'Lizenzen gesamt', 'sflm' ); ?></div>
        </div>
        <div class="sflm-kpi-card">
            <div class="sflm-kpi-value"><?php echo esc_html( $active_licenses ); ?></div>
            <div class="sflm-kpi-label"><?php esc_html_e( 'Aktive Lizenzen', 'sflm' ); ?></div>
        </div>
        <div class="sflm-kpi-card">
            <div class="sflm-kpi-value"><?php echo esc_html( $active_installs ); ?></div>
            <div class="sflm-kpi-label"><?php esc_html_e( 'Aktive Installationen', 'sflm' ); ?></div>
        </div>
        <div class="sflm-kpi-card">
            <div class="sflm-kpi-value"><?php echo esc_html( $today_activations ); ?></div>
            <div class="sflm-kpi-label"><?php esc_html_e( 'Aktivierungen heute', 'sflm' ); ?></div>
        </div>
        <div class="sflm-kpi-card <?php echo $grace_licenses > 0 ? 'sflm-kpi-warning' : ''; ?>">
            <div class="sflm-kpi-value"><?php echo esc_html( $grace_licenses ); ?></div>
            <div class="sflm-kpi-label"><?php esc_html_e( 'In Karenzzeit', 'sflm' ); ?></div>
        </div>
        <div class="sflm-kpi-card <?php echo $expiring_soon > 0 ? 'sflm-kpi-warning' : ''; ?>">
            <div class="sflm-kpi-value"><?php echo esc_html( $expiring_soon ); ?></div>
            <div class="sflm-kpi-label"><?php esc_html_e( 'Laufen in 7 Tagen ab', 'sflm' ); ?></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="sflm-chart-grid">
        <div class="sflm-chart-container">
            <h3><?php esc_html_e( 'Lizenz-Tiers', 'sflm' ); ?></h3>
            <canvas id="sflm-chart-tiers" height="200"></canvas>
        </div>
        <div class="sflm-chart-container">
            <h3><?php esc_html_e( 'Plattformen', 'sflm' ); ?></h3>
            <canvas id="sflm-chart-platforms" height="200"></canvas>
        </div>
    </div>
    <div class="sflm-chart-container">
        <h3><?php esc_html_e( 'Aktivierungen (letzte 30 Tage)', 'sflm' ); ?></h3>
        <canvas id="sflm-chart-timeline" height="100"></canvas>
    </div>

    <!-- Geo-Verteilung: Weltkarte + Tabelle -->
    <div class="sflm-chart-container">
        <h3><?php esc_html_e( 'Installationen weltweit', 'sflm' ); ?></h3>
        <div class="sflm-geo-section">
            <div class="sflm-geo-map-wrap">
                <div id="sflm-world-map" class="sflm-world-map"></div>
                <div class="sflm-map-legend">
                    <span class="sflm-map-legend-item">
                        <span class="sflm-map-dot sflm-map-dot-sm"></span> 1–5
                    </span>
                    <span class="sflm-map-legend-item">
                        <span class="sflm-map-dot sflm-map-dot-md"></span> 6–20
                    </span>
                    <span class="sflm-map-legend-item">
                        <span class="sflm-map-dot sflm-map-dot-lg"></span> 21+
                    </span>
                </div>
            </div>
            <div class="sflm-geo-tables">
                <!-- Länder-Tabelle -->
                <div class="sflm-geo-table-wrap">
                    <h4><?php esc_html_e( 'Nach Land', 'sflm' ); ?></h4>
                    <table class="sflm-table sflm-geo-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Land', 'sflm' ); ?></th>
                                <th class="sflm-geo-col-count"><?php esc_html_e( 'Installationen', 'sflm' ); ?></th>
                                <th class="sflm-geo-col-bar"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $geo_countries ) ) : ?>
                                <tr><td colspan="3"><?php esc_html_e( 'Keine Geodaten vorhanden.', 'sflm' ); ?></td></tr>
                            <?php else : ?>
                                <?php
                                $geo_max_country = max( array_column( $geo_countries, 'install_count' ) );
                                foreach ( $geo_countries as $gc ) :
                                    $pct = $geo_max_country > 0 ? round( ( (int) $gc->install_count / $geo_max_country ) * 100 ) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="sflm-country-flag"><?php echo esc_html( $gc->country_code ); ?></span>
                                            <?php echo esc_html( $gc->country_name ); ?>
                                        </td>
                                        <td class="sflm-geo-col-count"><?php echo esc_html( $gc->install_count ); ?></td>
                                        <td class="sflm-geo-col-bar">
                                            <div class="sflm-geo-bar" style="width: <?php echo esc_attr( $pct ); ?>%"></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Städte-Tabelle -->
                <div class="sflm-geo-table-wrap">
                    <h4><?php esc_html_e( 'Nach Stadt', 'sflm' ); ?></h4>
                    <table class="sflm-table sflm-geo-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Stadt', 'sflm' ); ?></th>
                                <th><?php esc_html_e( 'Land', 'sflm' ); ?></th>
                                <th class="sflm-geo-col-count"><?php esc_html_e( 'Installationen', 'sflm' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $geo_cities ) ) : ?>
                                <tr><td colspan="3"><?php esc_html_e( 'Keine Geodaten vorhanden.', 'sflm' ); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ( $geo_cities as $gcity ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $gcity->city ); ?></td>
                                        <td>
                                            <span class="sflm-country-flag"><?php echo esc_html( $gcity->country_code ); ?></span>
                                            <?php echo esc_html( $gcity->country_name ); ?>
                                        </td>
                                        <td class="sflm-geo-col-count"><?php echo esc_html( $gcity->install_count ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Letzte Aktivitäten -->
    <div class="sflm-chart-container">
        <h3><?php esc_html_e( 'Letzte Aktivitäten', 'sflm' ); ?></h3>
        <table class="sflm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Zeitpunkt', 'sflm' ); ?></th>
                    <th><?php esc_html_e( 'Aktion', 'sflm' ); ?></th>
                    <th><?php esc_html_e( 'Akteur', 'sflm' ); ?></th>
                    <th><?php esc_html_e( 'Lizenz-ID', 'sflm' ); ?></th>
                    <th><?php esc_html_e( 'Details', 'sflm' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $recent_logs ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'Keine Einträge vorhanden.', 'sflm' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $recent_logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $log->created_at ) ) ); ?></td>
                            <td><code><?php echo esc_html( $log->action ); ?></code></td>
                            <td><span class="sflm-badge"><?php echo esc_html( $log->actor_type ); ?></span></td>
                            <td><?php echo $log->license_id ? esc_html( $log->license_id ) : '–'; ?></td>
                            <td>
                                <?php
                                if ( $log->details_json ) {
                                    $details = json_decode( $log->details_json, true );
                                    if ( is_array( $details ) ) {
                                        $parts = [];
                                        foreach ( $details as $k => $v ) {
                                            $parts[] = esc_html( $k ) . ': ' . esc_html( is_bool( $v ) ? ( $v ? 'ja' : 'nein' ) : $v );
                                        }
                                        echo implode( ', ', $parts );
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
window.sflmChartData = {
    tiers: <?php echo wp_json_encode( $tier_data ); ?>,
    platforms: <?php echo wp_json_encode( $platform_data ); ?>,
    timeline: {
        labels: <?php echo wp_json_encode( $timeline_labels ); ?>,
        data: <?php echo wp_json_encode( $timeline_data ); ?>
    },
    geoMap: <?php echo wp_json_encode( array_map( function( $p ) {
        return [
            'cc'    => $p->country_code,
            'name'  => $p->country_name,
            'lat'   => (float) $p->lat,
            'lng'   => (float) $p->lng,
            'count' => (int) $p->install_count,
        ];
    }, $geo_map_points ) ); ?>
};
</script>
