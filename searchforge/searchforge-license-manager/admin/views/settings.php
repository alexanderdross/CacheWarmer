<?php
/**
 * Admin View: Einstellungen – UI-basiertes Formular.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Einstellungen speichern
if ( isset( $_POST['sflm_save_settings'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $_POST['_sflm_settings_nonce'] ?? '', 'sflm_save_settings' ) ) {
    $values = [];
    foreach ( SFLM_Settings::get_fields() as $key => $field ) {
        if ( isset( $_POST[ 'sflm_' . $key ] ) ) {
            $values[ $key ] = wp_unslash( $_POST[ 'sflm_' . $key ] );
        }
    }

    if ( SFLM_Settings::save( $values ) ) {
        // Transient-Caches leeren damit neue Werte sofort wirken
        delete_transient( 'sflm_dashboard_data' );
        delete_transient( 'sflm_widget_kpis' );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Einstellungen gespeichert.', 'sflm' ) . '</p></div>';
    } else {
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Keine Änderungen vorgenommen.', 'sflm' ) . '</p></div>';
    }
}

$fields   = SFLM_Settings::get_fields();
$sections = SFLM_Settings::get_sections();

// GeoIP-Status für Zusatzinfo
$geoip_path   = SFLM_Settings::get( 'maxmind_db_path' );
$geoip_exists = $geoip_path && file_exists( $geoip_path );
?>
<div class="wrap" role="main">
    <h1><?php esc_html_e( 'Einstellungen', 'sflm' ); ?></h1>

    <p class="description" style="font-size: 14px; margin-bottom: 20px;">
        <?php esc_html_e( 'Konfigurieren Sie das License Dashboard direkt hier. Alle Einstellungen werden verschlüsselt in der Datenbank gespeichert.', 'sflm' ); ?>
        <br>
        <span style="color: #646970;">
            <?php esc_html_e( 'Fortgeschrittene Nutzer können Werte auch als Konstanten in der wp-config.php definieren – diese haben immer Vorrang und sind hier als "überschrieben" markiert.', 'sflm' ); ?>
        </span>
    </p>

    <form method="post" autocomplete="off">
        <?php wp_nonce_field( 'sflm_save_settings', '_sflm_settings_nonce' ); ?>

        <?php foreach ( $sections as $section_key => $section ) : ?>
            <div class="sflm-chart-container" style="margin-bottom: 20px;">
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

                        $is_constant    = SFLM_Settings::is_constant_defined( $key );
                        $current_value  = SFLM_Settings::get( $key );
                        $input_name     = 'sflm_' . $key;
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
                                        <span class="sflm-badge sflm-badge-grace_period" style="font-size: 10px; vertical-align: middle;">wp-config.php</span>
                                        <?php printf(
                                            esc_html__( 'Dieser Wert wird durch die Konstante %s in der wp-config.php überschrieben und kann hier nicht geändert werden.', 'sflm' ),
                                            '<code>' . esc_html( $field['constant'] ) . '</code>'
                                        ); ?>
                                    </p>

                                <?php elseif ( 'password' === $field['type'] ) : ?>
                                    <input type="password"
                                           id="<?php echo esc_attr( $input_name ); ?>"
                                           name="<?php echo esc_attr( $input_name ); ?>"
                                           class="regular-text"
                                           value=""
                                           placeholder="<?php echo $current_value ? esc_attr__( '••••••••  (unverändert wenn leer)', 'sflm' ) : ''; ?>"
                                           autocomplete="new-password">
                                    <button type="button" class="button button-small sflm-toggle-password" data-target="<?php echo esc_attr( $input_name ); ?>" aria-label="<?php esc_attr_e( 'Passwort anzeigen/verbergen', 'sflm' ); ?>">
                                        <span class="dashicons dashicons-visibility" style="vertical-align: middle; font-size: 16px;" aria-hidden="true"></span>
                                    </button>
                                    <?php if ( 'jwt_secret' === $key ) : ?>
                                        <button type="button" class="button button-small sflm-generate-secret" data-target="<?php echo esc_attr( $input_name ); ?>" data-length="64">
                                            <span class="dashicons dashicons-randomize" style="vertical-align: middle; font-size: 16px;"></span>
                                            <?php esc_html_e( 'Generieren', 'sflm' ); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ( $current_value ) : ?>
                                        <span class="sflm-badge sflm-badge-active" style="font-size: 10px; vertical-align: middle; margin-left: 8px;"><?php esc_html_e( 'Gesetzt', 'sflm' ); ?></span>
                                    <?php else : ?>
                                        <span class="sflm-badge sflm-badge-expired" style="font-size: 10px; vertical-align: middle; margin-left: 8px;"><?php esc_html_e( 'Fehlt', 'sflm' ); ?></span>
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
                                        <p><span class="sflm-badge sflm-badge-active"><?php esc_html_e( 'Datei gefunden', 'sflm' ); ?></span>
                                        <small>(<?php echo esc_html( gmdate( 'd.m.Y', filemtime( $geoip_path ) ) ); ?>)</small></p>
                                    <?php else : ?>
                                        <p><span class="sflm-badge sflm-badge-expired"><?php esc_html_e( 'Datei nicht gefunden', 'sflm' ); ?></span></p>
                                    <?php endif;
                                endif;
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

        <?php submit_button( __( 'Einstellungen speichern', 'sflm' ), 'primary', 'sflm_save_settings' ); ?>
    </form>

    <!-- System-Info -->
    <div class="sflm-chart-container">
        <h3>
            <span class="dashicons dashicons-info-outline" style="margin-right: 6px; color: #646970;"></span>
            <?php esc_html_e( 'System-Informationen', 'sflm' ); ?>
        </h3>
        <p class="description" style="margin: -5px 0 20px 0; font-size: 13px; line-height: 1.6; max-width: 800px;">
            <?php esc_html_e( 'Technische Details zur aktuellen Umgebung. Nützlich bei der Fehlersuche und für den Support.', 'sflm' ); ?>
        </p>
        <div class="sflm-detail-grid">
            <div class="sflm-detail-label"><?php esc_html_e( 'Plugin-Version', 'sflm' ); ?></div>
            <div><?php echo esc_html( SFLM_VERSION ); ?></div>

            <div class="sflm-detail-label"><?php esc_html_e( 'PHP-Version', 'sflm' ); ?></div>
            <div><?php echo esc_html( PHP_VERSION ); ?></div>

            <div class="sflm-detail-label"><?php esc_html_e( 'WordPress-Version', 'sflm' ); ?></div>
            <div><?php echo esc_html( get_bloginfo( 'version' ) ); ?></div>

            <div class="sflm-detail-label"><?php esc_html_e( 'MySQL-Version', 'sflm' ); ?></div>
            <div><?php echo esc_html( $GLOBALS['wpdb']->db_version() ); ?></div>

            <div class="sflm-detail-label"><?php esc_html_e( 'OpenSSL', 'sflm' ); ?></div>
            <div>
                <?php if ( function_exists( 'openssl_encrypt' ) ) : ?>
                    <span class="sflm-badge sflm-badge-active"><?php echo esc_html( OPENSSL_VERSION_TEXT ); ?></span>
                <?php else : ?>
                    <span class="sflm-badge sflm-badge-expired"><?php esc_html_e( 'Nicht verfügbar', 'sflm' ); ?></span>
                    <small><?php esc_html_e( 'Sensible Einstellungen werden unverschlüsselt gespeichert.', 'sflm' ); ?></small>
                <?php endif; ?>
            </div>

            <div class="sflm-detail-label"><?php esc_html_e( 'REST API URL', 'sflm' ); ?></div>
            <div><code><?php echo esc_html( rest_url( 'sflm/v1/' ) ); ?></code></div>

            <div class="sflm-detail-label"><?php esc_html_e( 'Stripe Webhook URL', 'sflm' ); ?></div>
            <div><code><?php echo esc_html( rest_url( 'sflm/v1/stripe/webhook' ) ); ?></code></div>

            <div class="sflm-detail-label"><?php esc_html_e( 'Cronjobs', 'sflm' ); ?></div>
            <div>
                <?php
                $crons = [
                    'sflm_check_expired_licenses'   => __( 'Lizenz-Ablauf', 'sflm' ),
                    'sflm_cleanup_old_data'          => __( 'Datenbereinigung', 'sflm' ),
                    'sflm_cleanup_rate_limits'       => __( 'Rate-Limits', 'sflm' ),
                    'sflm_check_stale_installations' => __( 'Stale Installationen', 'sflm' ),
                    'sflm_send_expiry_warnings'      => __( 'Ablauf-Warnungen', 'sflm' ),
                ];
                foreach ( $crons as $hook => $label ) {
                    $next = wp_next_scheduled( $hook );
                    echo esc_html( $label ) . ': ';
                    if ( $next ) {
                        echo '<span class="sflm-badge sflm-badge-active">' . esc_html( wp_date( 'd.m.Y H:i', $next ) ) . '</span>';
                    } else {
                        echo '<span class="sflm-badge sflm-badge-inactive">' . esc_html__( 'Nicht geplant', 'sflm' ) . '</span>';
                    }
                    echo '<br>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- wp-config.php Referenz (eingeklappt) -->
    <div class="sflm-chart-container">
        <h3 style="cursor: pointer;" id="sflm-wpconfig-toggle">
            <span class="dashicons dashicons-editor-code" style="margin-right: 6px; color: #646970;"></span>
            <?php esc_html_e( 'wp-config.php Referenz (für Fortgeschrittene)', 'sflm' ); ?>
            <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 16px; color: #646970;" id="sflm-wpconfig-arrow"></span>
        </h3>
        <div id="sflm-wpconfig-content" style="display: none;">
            <p class="description" style="margin-bottom: 15px; line-height: 1.6; max-width: 800px;">
                <?php esc_html_e( 'Optional: Sie können alle oben genannten Einstellungen auch als PHP-Konstanten in Ihrer wp-config.php definieren. Konstanten haben immer Vorrang vor den hier gespeicherten Werten. Dies ist nützlich für Deployment-Automatisierung oder wenn Sie Secrets nicht in der Datenbank speichern möchten.', 'sflm' ); ?>
            </p>
            <pre style="background:#1d2327; color: #c3c4c7; padding:15px; border-radius:4px; overflow-x:auto; font-size: 13px; line-height: 1.5;">
<span style="color:#9cdcfe;">// SearchForge License Manager – Konfiguration</span>
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_JWT_SECRET'</span>, <span style="color:#ce9178;">'<?php echo esc_html( wp_generate_password( 64, true, true ) ); ?>'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_JWT_EXPIRY_DAYS'</span>, <span style="color:#b5cea8;">30</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_STRIPE_WEBHOOK_SECRET'</span>, <span style="color:#ce9178;">'whsec_...'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_GRACE_PERIOD_DAYS'</span>, <span style="color:#b5cea8;">14</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_HEARTBEAT_INTERVAL_HOURS'</span>, <span style="color:#b5cea8;">24</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_MAXMIND_DB_PATH'</span>, <span style="color:#ce9178;">'<?php echo esc_html( SFLM_PLUGIN_DIR ); ?>data/GeoLite2-City.mmdb'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_RATE_LIMIT_PER_MINUTE'</span>, <span style="color:#b5cea8;">60</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_RATE_LIMIT_ACTIVATE'</span>, <span style="color:#b5cea8;">10</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_DEV_DOMAINS'</span>, <span style="color:#ce9178;">'localhost,*.local,*.dev,*.test,127.0.0.1'</span> );
<span style="color:#c586c0;">define</span>( <span style="color:#ce9178;">'SFLM_CORS_ALLOWED_ORIGINS'</span>, <span style="color:#ce9178;">'*'</span> );</pre>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    // Toggle Password-Sichtbarkeit
    $(document).on('click', '.sflm-toggle-password', function() {
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
    $(document).on('click', '.sflm-generate-secret', function() {
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
        $(this).closest('td').find('.sflm-toggle-password .dashicons')
            .removeClass('dashicons-visibility').addClass('dashicons-hidden');
    });

    // wp-config.php Referenz einklappen/ausklappen
    $('#sflm-wpconfig-toggle').on('click', function() {
        var content = $('#sflm-wpconfig-content');
        var arrow = $('#sflm-wpconfig-arrow');
        content.slideToggle(200);
        arrow.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });
});
</script>
