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

// KPIs berechnen
$total_licenses     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}licenses" );
$active_licenses    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}licenses WHERE status = %s", 'active' ) );
$grace_licenses     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}licenses WHERE status = %s", 'grace_period' ) );
$active_installs    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}installations WHERE is_active = 1" );
$today_activations  = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$prefix}installations WHERE activated_at >= %s",
    gmdate( 'Y-m-d 00:00:00' )
) );
$expiring_soon      = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$prefix}licenses WHERE status = 'active' AND expires_at BETWEEN %s AND %s",
    gmdate( 'Y-m-d H:i:s' ),
    gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) )
) );

// Chart-Daten: Tier-Verteilung
$tier_counts = $wpdb->get_results(
    "SELECT tier, COUNT(*) as cnt FROM {$prefix}licenses WHERE status IN ('active','grace_period') GROUP BY tier",
    OBJECT_K
);
$tier_data = [
    (int) ( $tier_counts['free']->cnt ?? 0 ),
    (int) ( $tier_counts['professional']->cnt ?? 0 ),
    (int) ( $tier_counts['enterprise']->cnt ?? 0 ),
    (int) ( $tier_counts['development']->cnt ?? 0 ),
];

// Chart-Daten: Plattform-Verteilung
$platform_counts = $wpdb->get_results(
    "SELECT platform, COUNT(*) as cnt FROM {$prefix}installations WHERE is_active = 1 GROUP BY platform",
    OBJECT_K
);
$platform_data = [
    (int) ( $platform_counts['nodejs']->cnt ?? 0 ),
    (int) ( $platform_counts['docker']->cnt ?? 0 ),
    (int) ( $platform_counts['wordpress']->cnt ?? 0 ),
    (int) ( $platform_counts['drupal']->cnt ?? 0 ),
];

// Chart-Daten: Aktivierungen der letzten 30 Tage
$timeline_labels = [];
$timeline_data   = [];
for ( $i = 29; $i >= 0; $i-- ) {
    $date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
    $timeline_labels[] = gmdate( 'd.m.', strtotime( "-{$i} days" ) );
    $timeline_data[]   = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$prefix}installations WHERE DATE(activated_at) = %s",
        $date
    ) );
}

// Letzte Audit-Einträge
$recent_logs = $wpdb->get_results(
    "SELECT * FROM {$prefix}audit_logs ORDER BY created_at DESC LIMIT 10"
);
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
