<?php
/**
 * Admin View: Installationen.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . SFLM_DB_PREFIX;

// Filter (mit Enum-Validierung)
$valid_platforms = [ '', 'nodejs', 'docker', 'wordpress', 'drupal' ];
$filter_platform = in_array( $_GET['platform'] ?? '', $valid_platforms, true ) ? $_GET['platform'] : '';
$filter_active   = in_array( $_GET['active'] ?? '', [ '', '0', '1' ], true ) ? $_GET['active'] : '';
$search          = sanitize_text_field( $_GET['s'] ?? '' );
$paged           = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page        = 25;

// Query
$where  = '1=1';
$params = [];

if ( $filter_platform ) {
    $where   .= ' AND i.platform = %s';
    $params[] = $filter_platform;
}
if ( '' !== $filter_active && is_numeric( $filter_active ) ) {
    $where   .= ' AND i.is_active = %d';
    $params[] = (int) $filter_active;
}
if ( $search ) {
    $like     = '%' . $wpdb->esc_like( $search ) . '%';
    $where   .= ' AND (i.domain LIKE %s OR i.hostname LIKE %s OR i.fingerprint LIKE %s OR l.license_key LIKE %s)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$total_query = "SELECT COUNT(*) FROM {$prefix}installations i
    LEFT JOIN {$prefix}licenses l ON i.license_id = l.id
    WHERE {$where}";
$total = $params
    ? (int) $wpdb->get_var( $wpdb->prepare( $total_query, ...$params ) )
    : (int) $wpdb->get_var( $total_query );

$offset    = ( $paged - 1 ) * $per_page;
$query     = "SELECT i.*, l.license_key, l.tier, l.customer_email
    FROM {$prefix}installations i
    LEFT JOIN {$prefix}licenses l ON i.license_id = l.id
    WHERE {$where}
    ORDER BY i.activated_at DESC
    LIMIT %d OFFSET %d";
$params[]  = $per_page;
$params[]  = $offset;

$installations = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );
$total_pages   = (int) ceil( $total / $per_page );
?>
<div class="wrap" role="main">
    <h1><?php esc_html_e( 'Installationen', 'sflm' ); ?></h1>

    <form method="get" class="sflm-filter-bar">
        <input type="hidden" name="page" value="sflm-installations">
        <select name="platform">
            <option value=""><?php esc_html_e( 'Alle Plattformen', 'sflm' ); ?></option>
            <option value="nodejs" <?php selected( $filter_platform, 'nodejs' ); ?>>Node.js</option>
            <option value="docker" <?php selected( $filter_platform, 'docker' ); ?>>Docker</option>
            <option value="wordpress" <?php selected( $filter_platform, 'wordpress' ); ?>>WordPress</option>
            <option value="drupal" <?php selected( $filter_platform, 'drupal' ); ?>>Drupal</option>
        </select>
        <select name="active">
            <option value=""><?php esc_html_e( 'Alle', 'sflm' ); ?></option>
            <option value="1" <?php selected( $filter_active, '1' ); ?>><?php esc_html_e( 'Aktiv', 'sflm' ); ?></option>
            <option value="0" <?php selected( $filter_active, '0' ); ?>><?php esc_html_e( 'Inaktiv', 'sflm' ); ?></option>
        </select>
        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Suche...', 'sflm' ); ?>">
        <?php submit_button( __( 'Filtern', 'sflm' ), 'secondary', 'filter', false ); ?>
    </form>

    <table class="sflm-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Plattform', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Domain / Hostname', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Lizenz', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Fingerprint', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Version', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Status', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Letzter Check', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Aktiviert am', 'sflm' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $installations ) ) : ?>
                <tr><td colspan="8"><?php esc_html_e( 'Keine Installationen gefunden.', 'sflm' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $installations as $inst ) : ?>
                    <tr>
                        <td>
                            <span class="sflm-platform-<?php echo esc_attr( $inst->platform ); ?>" aria-hidden="true"></span>
                            <?php echo esc_html( $inst->platform ); ?>
                            <?php if ( $inst->platform_version ) : ?>
                                <small><?php echo esc_html( $inst->platform_version ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html( $inst->domain ?: $inst->hostname ?: '–' ); ?>
                            <?php if ( $inst->os_platform ) : ?>
                                <br><small><?php echo esc_html( $inst->os_platform . ' ' . ( $inst->os_version ?: '' ) ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="sflm-license-key" style="font-size:11px;"><?php echo esc_html( $inst->license_key ?? '–' ); ?></span>
                            <?php if ( $inst->tier ) : ?>
                                <br><span class="sflm-badge sflm-tier-<?php echo esc_attr( $inst->tier ); ?>" style="font-size:10px;"><?php echo esc_html( ucfirst( $inst->tier ) ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size:10px;"><?php echo esc_html( substr( $inst->fingerprint, 0, 16 ) . '...' ); ?></code></td>
                        <td><?php echo esc_html( $inst->product_version ?: '–' ); ?></td>
                        <td>
                            <?php if ( $inst->is_active ) : ?>
                                <span class="sflm-badge sflm-badge-active"><?php esc_html_e( 'Aktiv', 'sflm' ); ?></span>
                            <?php else : ?>
                                <span class="sflm-badge sflm-badge-inactive"><?php esc_html_e( 'Inaktiv', 'sflm' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ( $inst->last_check ) {
                                $diff = time() - strtotime( $inst->last_check );
                                if ( $diff < 3600 ) {
                                    printf( esc_html__( 'vor %d Min.', 'sflm' ), (int) ( $diff / 60 ) );
                                } elseif ( $diff < 86400 ) {
                                    printf( esc_html__( 'vor %d Std.', 'sflm' ), (int) ( $diff / 3600 ) );
                                } else {
                                    printf( esc_html__( 'vor %d Tagen', 'sflm' ), (int) ( $diff / 86400 ) );
                                }
                            } else {
                                echo '–';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $inst->activated_at ) ) ); ?></td>
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
