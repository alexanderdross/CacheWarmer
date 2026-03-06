<?php
/**
 * Admin View: Audit Log.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logger = new SFLM_Audit_Logger();

// Filter
$filter_action = sanitize_text_field( $_GET['action_filter'] ?? '' );
$filter_actor  = sanitize_text_field( $_GET['actor_type'] ?? '' );
$filter_license = (int) ( $_GET['license_id'] ?? 0 );
$paged          = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page       = 50;

$filters = [];
if ( $filter_action ) {
    $filters['action'] = $filter_action;
}
if ( $filter_actor ) {
    $filters['actor_type'] = $filter_actor;
}
if ( $filter_license ) {
    $filters['license_id'] = $filter_license;
}

$result     = $logger->get_logs( $filters, $paged, $per_page );
$logs       = $result['items'];
$total      = $result['total'];
$total_pages = (int) ceil( $total / $per_page );

// Eindeutige Aktionen für Filter-Dropdown
global $wpdb;
$prefix  = $wpdb->prefix . SFLM_DB_PREFIX;
$actions = $wpdb->get_col( "SELECT DISTINCT action FROM {$prefix}audit_logs ORDER BY action ASC" );
?>
<div class="wrap" role="main">
    <h1><?php esc_html_e( 'Audit Log', 'sflm' ); ?></h1>

    <form method="get" class="sflm-filter-bar">
        <input type="hidden" name="page" value="sflm-audit">
        <select name="action_filter">
            <option value=""><?php esc_html_e( 'Alle Aktionen', 'sflm' ); ?></option>
            <?php foreach ( $actions as $action ) : ?>
                <option value="<?php echo esc_attr( $action ); ?>" <?php selected( $filter_action, $action ); ?>><?php echo esc_html( $action ); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="actor_type">
            <option value=""><?php esc_html_e( 'Alle Akteure', 'sflm' ); ?></option>
            <option value="system" <?php selected( $filter_actor, 'system' ); ?>>System</option>
            <option value="admin" <?php selected( $filter_actor, 'admin' ); ?>>Admin</option>
            <option value="api" <?php selected( $filter_actor, 'api' ); ?>>API</option>
            <option value="stripe" <?php selected( $filter_actor, 'stripe' ); ?>>Stripe</option>
        </select>
        <?php if ( $filter_license ) : ?>
            <span><?php printf( esc_html__( 'Lizenz: #%d', 'sflm' ), $filter_license ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=sflm-audit' ) ); ?>"><?php esc_html_e( 'Filter zurücksetzen', 'sflm' ); ?></a></span>
        <?php endif; ?>
        <?php submit_button( __( 'Filtern', 'sflm' ), 'secondary', 'filter', false ); ?>
    </form>

    <table class="sflm-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Zeitpunkt', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Aktion', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Akteur', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Lizenz', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Installation', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'IP', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Details', 'sflm' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $logs ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'Keine Audit-Einträge gefunden.', 'sflm' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td style="white-space:nowrap;"><?php echo esc_html( wp_date( 'd.m.Y H:i:s', strtotime( $log->created_at ) ) ); ?></td>
                        <td><code><?php echo esc_html( $log->action ); ?></code></td>
                        <td>
                            <span class="sflm-badge"><?php echo esc_html( $log->actor_type ); ?></span>
                            <?php if ( $log->actor_id ) : ?>
                                <br><small>#<?php echo esc_html( $log->actor_id ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $log->license_id ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sflm-audit&license_id=' . $log->license_id ) ); ?>">#<?php echo esc_html( $log->license_id ); ?></a>
                            <?php else : ?>
                                –
                            <?php endif; ?>
                        </td>
                        <td><?php echo $log->installation_id ? esc_html( '#' . $log->installation_id ) : '–'; ?></td>
                        <td><small><?php echo esc_html( $log->ip_address ?: '–' ); ?></small></td>
                        <td>
                            <?php
                            if ( $log->details_json ) {
                                $details = json_decode( $log->details_json, true );
                                if ( is_array( $details ) ) {
                                    echo '<small>';
                                    $parts = [];
                                    foreach ( $details as $k => $v ) {
                                        if ( is_bool( $v ) ) {
                                            $v = $v ? 'ja' : 'nein';
                                        } elseif ( is_array( $v ) ) {
                                            $v = wp_json_encode( $v );
                                        }
                                        $parts[] = '<strong>' . esc_html( $k ) . '</strong>: ' . esc_html( (string) $v );
                                    }
                                    echo implode( ', ', $parts );
                                    echo '</small>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf( esc_html__( '%d Einträge', 'sflm' ), $total ); ?></span>
                <?php
                echo paginate_links( [
                    'base'    => add_query_arg( 'paged', '%#%' ),
                    'format'  => '',
                    'current' => $paged,
                    'total'   => $total_pages,
                ] );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
