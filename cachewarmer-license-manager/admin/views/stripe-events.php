<?php
/**
 * Admin View: Stripe Webhook Events.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . CWLM_DB_PREFIX;

// Filter
$filter_type   = sanitize_text_field( $_GET['event_type'] ?? '' );
$filter_status = sanitize_text_field( $_GET['processing_status'] ?? '' );
$paged         = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page      = 30;

$where  = '1=1';
$params = [];

if ( $filter_type ) {
    $where   .= ' AND event_type = %s';
    $params[] = $filter_type;
}
if ( $filter_status ) {
    $where   .= ' AND processing_status = %s';
    $params[] = $filter_status;
}

$total_query = "SELECT COUNT(*) FROM {$prefix}stripe_events WHERE {$where}";
$total       = $params
    ? (int) $wpdb->get_var( $wpdb->prepare( $total_query, ...$params ) )
    : (int) $wpdb->get_var( $total_query );

$offset    = ( $paged - 1 ) * $per_page;
$query     = "SELECT * FROM {$prefix}stripe_events WHERE {$where} ORDER BY received_at DESC LIMIT %d OFFSET %d";
$params[]  = $per_page;
$params[]  = $offset;

$events     = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );
$total_pages = (int) ceil( $total / $per_page );

// Event-Typen für Dropdown
$event_types = $wpdb->get_col( "SELECT DISTINCT event_type FROM {$prefix}stripe_events ORDER BY event_type ASC" );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Stripe Webhook Events', 'cwlm' ); ?></h1>

    <form method="get" class="cwlm-filter-bar">
        <input type="hidden" name="page" value="cwlm-stripe">
        <select name="event_type">
            <option value=""><?php esc_html_e( 'Alle Event-Typen', 'cwlm' ); ?></option>
            <?php foreach ( $event_types as $type ) : ?>
                <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $filter_type, $type ); ?>><?php echo esc_html( $type ); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="processing_status">
            <option value=""><?php esc_html_e( 'Alle Status', 'cwlm' ); ?></option>
            <option value="pending" <?php selected( $filter_status, 'pending' ); ?>><?php esc_html_e( 'Ausstehend', 'cwlm' ); ?></option>
            <option value="processed" <?php selected( $filter_status, 'processed' ); ?>><?php esc_html_e( 'Verarbeitet', 'cwlm' ); ?></option>
            <option value="failed" <?php selected( $filter_status, 'failed' ); ?>><?php esc_html_e( 'Fehlgeschlagen', 'cwlm' ); ?></option>
            <option value="ignored" <?php selected( $filter_status, 'ignored' ); ?>><?php esc_html_e( 'Ignoriert', 'cwlm' ); ?></option>
        </select>
        <?php submit_button( __( 'Filtern', 'cwlm' ), 'secondary', 'filter', false ); ?>
    </form>

    <!-- KPI Mini-Cards -->
    <?php
    $stats = $wpdb->get_results(
        "SELECT processing_status, COUNT(*) as cnt FROM {$prefix}stripe_events GROUP BY processing_status",
        OBJECT_K
    );
    ?>
    <div class="cwlm-kpi-grid" style="margin-bottom:10px;">
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $stats['processed']->cnt ?? 0 ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Verarbeitet', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card <?php echo ( $stats['failed']->cnt ?? 0 ) > 0 ? 'cwlm-kpi-warning' : ''; ?>">
            <div class="cwlm-kpi-value"><?php echo esc_html( $stats['failed']->cnt ?? 0 ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Fehlgeschlagen', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $stats['pending']->cnt ?? 0 ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Ausstehend', 'cwlm' ); ?></div>
        </div>
        <div class="cwlm-kpi-card">
            <div class="cwlm-kpi-value"><?php echo esc_html( $stats['ignored']->cnt ?? 0 ); ?></div>
            <div class="cwlm-kpi-label"><?php esc_html_e( 'Ignoriert', 'cwlm' ); ?></div>
        </div>
    </div>

    <table class="cwlm-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Stripe Event ID', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Typ', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Status', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Lizenz', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Empfangen', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Verarbeitet', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Fehler', 'cwlm' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $events ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'Keine Stripe Events gefunden.', 'cwlm' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $events as $event ) : ?>
                    <tr>
                        <td><code style="font-size:11px;"><?php echo esc_html( $event->stripe_event_id ); ?></code></td>
                        <td><code><?php echo esc_html( $event->event_type ); ?></code></td>
                        <td>
                            <?php
                            $status_class = match( $event->processing_status ) {
                                'processed' => 'cwlm-badge-active',
                                'failed'    => 'cwlm-badge-expired',
                                'ignored'   => 'cwlm-badge-inactive',
                                default     => 'cwlm-badge-grace_period',
                            };
                            ?>
                            <span class="cwlm-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $event->processing_status ); ?></span>
                        </td>
                        <td><?php echo $event->license_id ? esc_html( '#' . $event->license_id ) : '–'; ?></td>
                        <td style="white-space:nowrap;"><?php echo esc_html( wp_date( 'd.m.Y H:i:s', strtotime( $event->received_at ) ) ); ?></td>
                        <td style="white-space:nowrap;"><?php echo $event->processed_at ? esc_html( wp_date( 'd.m.Y H:i:s', strtotime( $event->processed_at ) ) ) : '–'; ?></td>
                        <td>
                            <?php if ( $event->error_message ) : ?>
                                <small style="color:#d63638;"><?php echo esc_html( $event->error_message ); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf( esc_html__( '%d Einträge', 'cwlm' ), $total ); ?></span>
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
