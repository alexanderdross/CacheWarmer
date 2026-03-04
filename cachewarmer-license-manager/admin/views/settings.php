<?php
/**
 * Admin View: Einstellungen – UI-basiertes Formular.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Einstellungen speichern
if ( isset( $_POST['cwlm_save_settings'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $_POST['_cwlm_settings_nonce'] ?? '', 'cwlm_save_settings' ) ) {
    $values = [];
    foreach ( CWLM_Settings::get_fields() as $key => $field ) {
        if ( isset( $_POST[ 'cwlm_' . $key ] ) ) {
            $values[ $key ] = wp_unslash( $_POST[ 'cwlm_' . $key ] );
        }
    }

    if ( CWLM_Settings::save( $values ) ) {
        // Transient-Caches leeren damit neue Werte sofort wirken
        delete_transient( 'cwlm_dashboard_data' );
        delete_transient( 'cwlm_widget_kpis' );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Einstellungen gespeichert.', 'cwlm' ) . '</p></div>';
    } else {
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Keine Änderungen vorgenommen.', 'cwlm' ) . '</p></div>';
    }
}

$fields   = CWLM_Settings::get_fields();
$sections = CWLM_Settings::get_sections();

// GeoIP-Status für Zusatzinfo
$geoip_path   = CWLM_Settings::get( 'maxmind_db_path' );
$geoip_exists = $geoip_path && file_exists( $geoip_path );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Einstellungen', 'cwlm' ); ?></h1>

    <p class="description" style="font-size: 14px; margin-bottom: 20px;">
        <?php esc_html_e( 'Konfigurieren Sie das License Dashboard direkt hier. Alle Einstellungen werden verschlüsselt in der Datenbank gespeichert.', 'cwlm' ); ?>
        <br>
        <span style="color: #646970;">
            <?php esc_html_e( 'Fortgeschrittene Nutzer können Werte auch als Konstanten in der wp-config.php definieren – diese haben immer Vorrang und sind hier als "überschrieben" markiert.', 'cwlm' ); ?>
        </span>
    </p>

    <form method="post" autocomplete="off">
        <?php wp_nonce_field( 'cwlm_save_settings', '_cwlm_settings_nonce' ); ?>

        <?php foreach ( $sections as $section_key => $section ) : ?>
            <div class="cwlm-chart-container" style="margin-bottom: 20px;">
                <h3>
                    <span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>" style="margin-right: 6px; color: #646970;"></span>
                    <?php echo esc_html( $section['title'] ); ?>
                </h3>
                <p class="description" style="margin: -5px 0 20px 0; font-size: 13px; line-height: 1.6; max-width: 800px;">
                    <?php echo esc_html( $section['description'] ); ?>
                </p>

                <table class="form-table" role="presentation">
                    <?php
                    foreach ( $fields as $key => $field ) :
                        if ( $field['section'] !== $section_key ) {
                            continue;
                        }

                        $is_constant    = CWLM_Settings::is_constant_defined( $key );
                        $current_value  = CWLM_Settings::get( $key );
                        $input_name     = 'cwlm_' . $key;
                        $display_value  = $current_value;

                        // Passwort-Felder: Wert nicht im Formular anzeigen, nur Platzhalter
                        if ( 'password' === $field['type'] && $current_value ) {
                            $display_value = '';
                        }
                    ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr( $input_name ); ?>">
                                    <?php echo esc_html( $field['label'] ); ?>
                                </label>
                            </th>
                            <td>
                                <?php if ( $is_constant ) : ?>
                                    <input type="text"
                                           class="regular-text"
                                           value="<?php echo 'password' === $field['type'] ? '••••••••••••••••' : esc_attr( $current_value ); ?>"
                                           disabled
                                           style="background: #f0f0f1; color: #646970;">
                                    <input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="">
                                    <p class="description">
                                        <span class="cwlm-badge cwlm-badge-grace_period" style="font-size: 10px; vertical-align: middle;">wp-config.php</span>
                                        <?php printf(
                                            esc_html__( 'Dieser Wert wird durch die Konstante %s in der wp-config.php überschrieben und kann hier nicht geändert werden.', 'cwlm' ),
                                            '<code>' . esc_html( $field['constant'] ) . '</code>'
                                        ); ?>
                                    </p>

                                <?php elseif ( 'password' === $field['type'] ) : ?>
                                    <input type="password"
                                           id="<?php echo esc_attr( $input_name ); ?>"
                                           name="<?php echo esc_attr( $input_name ); ?>"
                                           class="regular-text"
                                           value=""
                                           placeholder="<?php echo $current_value ? esc_attr__( '••••••••  (unverändert wenn leer)', 'cwlm' ) : ''; ?>"
                                           autocomplete="new-password">
                                    <button type="button" class="button button-small cwlm-toggle-password" data-target="<?php echo esc_attr( $input_name ); ?>">
                                        <span class="dashicons dashicons-visibility" style="vertical-align: middle; font-size: 16px;"></span>
                                    </button>
                                    <?php if ( 'jwt_secret' === $key ) : ?>
                                        <button type="button" class="button button-small cwlm-generate-secret" data-target="<?php echo esc_attr( $input_name ); ?>" data-length="64">
                                            <span class="dashicons dashicons-randomize" style="vertical-align: middle; font-size: 16px;"></span>
                                            <?php esc_html_e( 'Generieren', 'cwlm' ); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ( $current_value ) : ?>
                                        <span class="cwlm-badge cwlm-badge-active" style="font-size: 10px; vertical-align: middle; margin-left: 8px;"><?php esc_html_e( 'Gesetzt', 'cwlm' ); ?></span>
                                    <?php else : ?>
                                        <span class="cwlm-badge cwlm-badge-expired" style="font-size: 10px; vertical-align: middle; margin-left: 8px;"><?php esc_html_e( 'Fehlt', 'cwlm' ); ?></span>
                                    <?php endif; ?>

                                <?php elseif ( 'number' === $field['type'] ) : ?>
                                    <input type="number"
                                           id="<?php echo esc_attr( $input_name ); ?>"
                                           name="<?php echo esc_attr( $input_name ); ?>"
                                           class="small-text"
                                           value="<?php echo esc_attr( $display_value ); ?>"
                                           min="0">

                                <?php else : ?>
                                    <input type="text"
                                           id="<?php echo esc_attr( $input_name ); ?>"
                                           name="<?php echo esc_attr( $input_name ); ?>"
                                           class="regular-text"
                                           value="<?php echo esc_attr( $display_value ); ?>">
                                <?php endif; ?>

                                <?php if ( $field['help'] ) : ?>
                                    <p class="description"><?php echo esc_html( $field['help'] ); ?></p>
                                <?php endif; ?>

                                <?php
                                // GeoIP Zusatzinfo
                                if ( 'maxmind_db_path' === $key && $geoip_path ) :
                                    if ( $geoip_exists ) : ?>
                                        <p><span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'Datei gefunden', 'cwlm' ); ?></span>
                                        <small>(<?php echo esc_html( gmdate( 'd.m.Y', filemtime( $geoip_path ) ) ); ?>)</small></p>
                                    <?php else : ?>
                                        <p><span class="cwlm-badge cwlm-badge-expired"><?php esc_html_e( 'Datei nicht gefunden', 'cwlm' ); ?></span></p>
                                    <?php endif;
                                endif;
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

        <?php submit_button( __( 'Einstellungen speichern', 'cwlm' ), 'primary', 'cwlm_save_settings' ); ?>
    </form>

    <!-- System-Info -->
    <div class="cwlm-chart-container">
        <h3>
            <span class="dashicons dashicons-info-outline" style="margin-right: 6px; color: #646970;"></span>
            <?php esc_html_e( 'System-Informationen', 'cwlm' ); ?>
        </h3>
        <p class="description" style="margin: -5px 0 20px 0; font-size: 13px; line-height: 1.6; max-width: 800px;">
            <?php esc_html_e( 'Technische Details zur aktuellen Umgebung. Nützlich bei der Fehlersuche und für den Support.', 'cwlm' ); ?>
        </p>
        <div class="cwlm-detail-grid">
            <div class="cwlm-detail-label"><?php esc_html_e( 'Plugin-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( CWLM_VERSION ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'PHP-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( PHP_VERSION ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'WordPress-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( get_bloginfo( 'version' ) ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'MySQL-Version', 'cwlm' ); ?></div>
            <div><?php echo esc_html( $GLOBALS['wpdb']->db_version() ); ?></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'OpenSSL', 'cwlm' ); ?></div>
            <div>
                <?php if ( function_exists( 'openssl_encrypt' ) ) : ?>
                    <span class="cwlm-badge cwlm-badge-active"><?php echo esc_html( OPENSSL_VERSION_TEXT ); ?></span>
                <?php else : ?>
                    <span class="cwlm-badge cwlm-badge-expired"><?php esc_html_e( 'Nicht verfügbar', 'cwlm' ); ?></span>
                    <small><?php esc_html_e( 'Sensible Einstellungen werden unverschlüsselt gespeichert.', 'cwlm' ); ?></small>
                <?php endif; ?>
            </div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'REST API URL', 'cwlm' ); ?></div>
            <div><code><?php echo esc_html( rest_url( 'cwlm/v1/' ) ); ?></code></div>

            <div class="cwlm-detail-label"><?php esc_html_e( 'Stripe Webhook URL', 'cwlm' ); ?></div>
            <div><code><?php echo esc_html( rest_url( 'cwlm/v1/stripe/webhook' ) ); ?></code></div>

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

    <!-- wp-config.php Referenz (eingeklappt) -->
    <div class="cwlm-chart-container">
        <h3 style="cursor: pointer;" id="cwlm-wpconfig-toggle">
            <span class="dashicons dashicons-editor-code" style="margin-right: 6px; color: #646970;"></span>
            <?php esc_html_e( 'wp-config.php Referenz (für Fortgeschrittene)', 'cwlm' ); ?>
            <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 16px; color: #646970;" id="cwlm-wpconfig-arrow"></span>
        </h3>
        <div id="cwlm-wpconfig-content" style="display: none;">
            <p class="description" style="margin-bottom: 15px; line-height: 1.6; max-width: 800px;">
                <?php esc_html_e( 'Optional: Sie können alle oben genannten Einstellungen auch als PHP-Konstanten in Ihrer wp-config.php definieren. Konstanten haben immer Vorrang vor den hier gespeicherten Werten. Dies ist nützlich für Deployment-Automatisierung oder wenn Sie Secrets nicht in der Datenbank speichern möchten.', 'cwlm' ); ?>
            </p>
            <pre style="background:#1d2327; color: #c3c4c7; padding:15px; border-radius:4px; overflow-x:auto; font-size: 13px; line-height: 1.5;">
<span style="color:#9cdcfe;">// CacheWarmer License Manager – Konfiguration</span>
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_JWT_SECRET'</span>, <span style="color:#ce9178;">'<?php echo esc_html( wp_generate_password( 64, true, true ) ); ?>'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_JWT_EXPIRY_DAYS'</span>, <span style="color:#b5cea8;">30</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_STRIPE_WEBHOOK_SECRET'</span>, <span style="color:#ce9178;">'whsec_...'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_GRACE_PERIOD_DAYS'</span>, <span style="color:#b5cea8;">14</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_HEARTBEAT_INTERVAL_HOURS'</span>, <span style="color:#b5cea8;">24</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_MAXMIND_DB_PATH'</span>, <span style="color:#ce9178;">'<?php echo esc_html( CWLM_PLUGIN_DIR ); ?>data/GeoLite2-City.mmdb'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_RATE_LIMIT_PER_MINUTE'</span>, <span style="color:#b5cea8;">60</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_RATE_LIMIT_ACTIVATE'</span>, <span style="color:#b5cea8;">10</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_DEV_DOMAINS'</span>, <span style="color:#ce9178;">'localhost,*.local,*.dev,*.test,127.0.0.1'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'CWLM_CORS_ALLOWED_ORIGINS'</span>, <span style="color:#ce9178;">'*'</span> );</pre>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    // Toggle Password-Sichtbarkeit
    $(document).on('click', '.cwlm-toggle-password', function() {
        var target = $('#' + $(this).data('target'));
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            $(this).find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            target.attr('type', 'password');
            $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Secret generieren
    $(document).on('click', '.cwlm-generate-secret', function() {
        var target = $('#' + $(this).data('target'));
        var len = $(this).data('length') || 64;
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+';
        var secret = '';
        var values = new Uint32Array(len);
        crypto.getRandomValues(values);
        for (var i = 0; i < len; i++) {
            secret += chars[values[i] % chars.length];
        }
        target.val(secret).attr('type', 'text');
        $(this).closest('td').find('.cwlm-toggle-password .dashicons')
            .removeClass('dashicons-visibility').addClass('dashicons-hidden');
    });

    // wp-config.php Referenz einklappen/ausklappen
    $('#cwlm-wpconfig-toggle').on('click', function() {
        var content = $('#cwlm-wpconfig-content');
        var arrow = $('#cwlm-wpconfig-arrow');
        content.slideToggle(200);
        arrow.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });
});
</script>
