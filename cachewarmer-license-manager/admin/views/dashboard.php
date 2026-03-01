<?php
/**
 * Admin Dashboard View – KPIs, Charts, letzte Aktivitäten.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . CWLM_DB_PREFIX;

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
    $cache_key  = 'cwlm_dashboard_data';
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
<div class="wrap">
    <h1><?php esc_html_e( 'CacheWarmer License Manager – Dashboard', 'cwlm' ); ?></h1>

    <!-- KPI Cards -->
    <div class="cwlm-kpi-grid">
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $total_licenses ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Lizenzen gesamt', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $active_licenses ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Aktive Lizenzen', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $active_installs ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Aktive Installationen', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $today_activations ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Aktivierungen heute', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card <?php echo $grace_licenses > 0 ? 'cwlm-kpi-warning' : ''; ?>">
            <div class="cwlm-kpi-value"><?php echo esc_html( $grace_licenses ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'In Karenzzeit', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card <?php echo $expiring_soon > 0 ? 'cwlm-kpi-warning' : ''; ?>">
            <div class="cwlm-kpi-value"><?php echo esc_html( $expiring_soon ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Laufen in 7 Tagen ab', 'cwlm' ); ?></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="cwlm-chart-grid">
        <div class="cwlm-chart-container">
            <h3><?php esc_html_e( 'Lizenz-Tiers', 'cwlm' ); ?></h3>
            <canvas id="cwlm-chart-tiers" height="200"></canvas>
        </div>
        <div class="cwlm-chart-container">
            <h3><?php esc_html_e( 'Plattformen', 'cwlm' ); ?></h3>
            <canvas id="cwlm-chart-platforms" height="200"></canvas>
        </div>
    </div>
    <div class="cwlm-chart-container">
        <h3><?php esc_html_e( 'Aktivierungen (letzte 30 Tage)', 'cwlm' ); ?></h3>
        <canvas id="cwlm-chart-timeline" height="100"></canvas>
    </div>

    <!-- Letzte Aktivitäten -->
    <div class="cwlm-chart-container">
        <h3><?php esc_html_e( 'Letzte Aktivitäten', 'cwlm' ); ?></h3>
        <table class="cwlm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Zeitpunkt', 'cwlm' ); ?></th>
                    <th><?php esc_html_e( 'Aktion', 'cwlm' ); ?></th>
                    <th><?php esc_html_e( 'Akteur', 'cwlm' ); ?></th>
                    <th><?php esc_html_e( 'Lizenz-ID', 'cwlm' ); ?></th>
                    <th><?php esc_html_e( 'Details', 'cwlm' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $recent_logs ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'Keine Einträge vorhanden.', 'cwlm' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $recent_logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $log->created_at ) ) ); ?></td>
                            <td><code><?php echo esc_html( $log->action ); ?></code></td>
                            <td><span class="cwlm-badge"><?php echo esc_html( $log->actor_type ); ?></span></td>
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
window.cwlmChartData = {
    tiers: <?php echo wp_json_encode( $tier_data ); ?>,
    platforms: <?php echo wp_json_encode( $platform_data ); ?>,
    timeline: {
        labels: <?php echo wp_json_encode( $timeline_labels ); ?>,
        data: <?php echo wp_json_encode( $timeline_data ); ?>
    }
};
</script>
