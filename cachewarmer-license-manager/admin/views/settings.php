<?php
/**
 * Admin View: Einstellungen.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$geoip_path   = defined( 'CWLM_MAXMIND_DB_PATH' ) ? CWLM_MAXMIND_DB_PATH : '';
$geoip_exists = $geoip_path && file_exists( $geoip_path );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Einstellungen', 'cwlm' ); ?></h1>

    <p class="description">
        <?php esc_html_e( 'Die Plugin-Konfiguration erfolgt über Konstanten in der wp-config.php. Hier finden Sie eine Übersicht der aktuellen Werte.', 'cwlm' ); ?>
    </p>

    <!-- Aktuelle Konfiguration -->
    <div class="cwlm-chart-container">
        <h3><?php esc_html_e( 'Aktive Konfiguration', 'cwlm' ); ?></h3>
        <table class="cwlm-table widefat">
            <thead>
                <tr>
                    <th style="width:300px;"><?php esc_html_e( 'Konstante', 'cwlm' ); ?></th>
                    <th><?php esc_html_e( 'Wert', 'cwlm' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'cwlm' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>CWLM_JWT_SECRET</code></td>
                    <td><?php echo defined( 'CWLM_JWT_SECRET' ) && CWLM_JWT_SECRET ? '********' : '<em>' . esc_html__( 'Nicht gesetzt', 'cwlm' ) . '</em>'; ?></td>
                    <td>
                        <?php if ( defined( 'CWLM_JWT_SECRET' ) && CWLM_JWT_SECRET ) : ?>
                            <span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span>
                        <?php else : ?>
                            <span class="cwlm-badge cwlm-badge-expired"><?php esc_html_e( 'Fehlt', 'cwlm' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>CWLM_JWT_EXPIRY_DAYS</code></td>
                    <td><?php echo esc_html( defined( 'CWLM_JWT_EXPIRY_DAYS' ) ? CWLM_JWT_EXPIRY_DAYS : '30 (Standard)' ); ?></td>
                    <td><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span></td>
                </tr>
                <tr>
                    <td><code>CWLM_STRIPE_WEBHOOK_SECRET</code></td>
                    <td><?php echo defined( 'CWLM_STRIPE_WEBHOOK_SECRET' ) && CWLM_STRIPE_WEBHOOK_SECRET ? '********' : '<em>' . esc_html__( 'Nicht gesetzt', 'cwlm' ) . '</em>'; ?></td>
                    <td>
                        <?php if ( defined( 'CWLM_STRIPE_WEBHOOK_SECRET' ) && CWLM_STRIPE_WEBHOOK_SECRET ) : ?>
                            <span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span>
                        <?php else : ?>
                            <span class="cwlm-badge cwlm-badge-expired"><?php esc_html_e( 'Fehlt', 'cwlm' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>CWLM_GRACE_PERIOD_DAYS</code></td>
                    <td><?php echo esc_html( defined( 'CWLM_GRACE_PERIOD_DAYS' ) ? CWLM_GRACE_PERIOD_DAYS : '14 (Standard)' ); ?></td>
                    <td><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span></td>
                </tr>
                <tr>
                    <td><code>CWLM_HEARTBEAT_INTERVAL_HOURS</code></td>
                    <td><?php echo esc_html( defined( 'CWLM_HEARTBEAT_INTERVAL_HOURS' ) ? CWLM_HEARTBEAT_INTERVAL_HOURS : '24 (Standard)' ); ?></td>
                    <td><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span></td>
                </tr>
                <tr>
                    <td><code>CWLM_MAXMIND_DB_PATH</code></td>
                    <td>
                        <?php if ( $geoip_path ) : ?>
                            <code><?php echo esc_html( $geoip_path ); ?></code>
                        <?php else : ?>
                            <em><?php esc_html_e( 'Nicht gesetzt', 'cwlm' ); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( $geoip_exists ) : ?>
                            <span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'Gefunden', 'cwlm' ); ?></span>
                            <small>(<?php echo esc_html( gmdate( 'd.m.Y', filemtime( $geoip_path ) ) ); ?>)</small>
                        <?php elseif ( $geoip_path ) : ?>
                            <span class="cwlm-badge cwlm-badge-expired"><?php esc_html_e( 'Datei nicht gefunden', 'cwlm' ); ?></span>
                        <?php else : ?>
                            <span class="cwlm-badge cwlm-badge-inactive"><?php esc_html_e( 'Optional', 'cwlm' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>CWLM_RATE_LIMIT_PER_MINUTE</code></td>
                    <td><?php echo esc_html( defined( 'CWLM_RATE_LIMIT_PER_MINUTE' ) ? CWLM_RATE_LIMIT_PER_MINUTE : '60 (Standard)' ); ?></td>
                    <td><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span></td>
                </tr>
                <tr>
                    <td><code>CWLM_RATE_LIMIT_ACTIVATE</code></td>
                    <td><?php echo esc_html( defined( 'CWLM_RATE_LIMIT_ACTIVATE' ) ? CWLM_RATE_LIMIT_ACTIVATE : '10 (Standard)' ); ?></td>
                    <td><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span></td>
                </tr>
                <tr>
                    <td><code>CWLM_DEV_DOMAINS</code></td>
                    <td>
                        <?php if ( defined( 'CWLM_DEV_DOMAINS' ) ) : ?>
                            <code><?php echo esc_html( CWLM_DEV_DOMAINS ); ?></code>
                        <?php else : ?>
                            <code>localhost, *.local, *.dev, *.test, 127.0.0.1</code> <small>(Standard)</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'OK', 'cwlm' ); ?></span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- System-Info -->
    <div class="cwlm-chart-container">
        <h3><?php esc_html_e( 'System-Informationen', 'cwlm' ); ?></h3>
        <div class="cwlm-detail-grid">
            <div class="cwlm-detail-label"><?php esc_html_e( 'Plugin-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( CWLM_VERSION ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'PHP-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( PHP_VERSION ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'WordPress-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( get_bloginfo( 'version' ) ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'MySQL-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( $GLOBALS['wpdb']->db_version() ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'REST API URL', 'cwlm' ); ?></div>
            <div><code><?php echo esc_html( rest_url( 'cwlm/v1/' ) ); ?></code></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'Cronjobs', 'cwlm' ); ?></div>
            <div>
                <?php
                $crons = [
                    'cwlm_check_expired_licenses'   => __( 'Lizenz-Ablauf', 'cwlm' ),
                    'cwlm_cleanup_old_data'          => __( 'Datenbereinigung', 'cwlm' ),
                    'cwlm_cleanup_rate_limits'       => __( 'Rate-Limits', 'cwlm' ),
                    'cwlm_check_stale_installations' => __( 'Stale Installationen', 'cwlm' ),
                    'cwlm_send_expiry_warnings'      => __( 'Ablauf-Warnungen', 'cwlm' ),
                ];
                foreach ( $crons as $hook => $label ) {
                    $next = wp_next_scheduled( $hook );
                    echo esc_html( $label ) . ': ';
                    if ( $next ) {
                        echo '<span class="cwlm-badge cwlm-badge-active">' . esc_html( wp_date( 'd.m.Y H:i', $next ) ) . '</span>';
                    } else {
                        echo '<span class="cwlm-badge cwlm-badge-inactive">' . esc_html__( 'Nicht geplant', 'cwlm' ) . '</span>';
                    }
                    echo '<br>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- wp-config.php Vorlage -->
    <div class="cwlm-chart-container">
        <h3><?php esc_html_e( 'wp-config.php Konfigurationsvorlage', 'cwlm' ); ?></h3>
        <p class="description"><?php esc_html_e( 'Fügen Sie diese Konstanten in Ihre wp-config.php ein (vor "That\'s all, stop editing!").', 'cwlm' ); ?></p>
        <pre style="background:#f0f0f1; padding:15px; border:1px solid #c3c4c7; border-radius:4px; overflow-x:auto;">
// CacheWarmer License Manager
define( 'CWLM_JWT_SECRET', '<?php echo esc_html( wp_generate_password( 64, true, true ) ); ?>' );
define( 'CWLM_JWT_EXPIRY_DAYS', 30 );
define( 'CWLM_STRIPE_WEBHOOK_SECRET', 'whsec_...' );
define( 'CWLM_GRACE_PERIOD_DAYS', 14 );
define( 'CWLM_HEARTBEAT_INTERVAL_HOURS', 24 );
define( 'CWLM_MAXMIND_DB_PATH', '<?php echo esc_html( CWLM_PLUGIN_DIR ); ?>data/GeoLite2-City.mmdb' );
define( 'CWLM_RATE_LIMIT_PER_MINUTE', 60 );
define( 'CWLM_RATE_LIMIT_ACTIVATE', 10 );
define( 'CWLM_DEV_DOMAINS', 'localhost,*.local,*.dev,*.test,127.0.0.1' );</pre>
    </div>
</div>
