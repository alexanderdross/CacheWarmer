<?php
/**
 * Admin View: Installationen.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . CWLM_DB_PREFIX;

// Filter
$filter_platform = sanitize_text_field( $_GET['platform'] ?? '' );
$filter_active   = $_GET['active'] ?? '';
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
<div class="wrap">
    <h1><?php esc_html_e( 'Installationen', 'cwlm' ); ?></h1>

    <form method="get" class="cwlm-filter-bar">
        <input type="hidden" name="page" value="cwlm-installations">
        <select name="platform">
            <option value=""><?php esc_html_e( 'Alle Plattformen', 'cwlm' ); ?></option>
            <option value="nodejs" <?php selected( $filter_platform, 'nodejs' ); ?>>Node.js</option>
            <option value="docker" <?php selected( $filter_platform, 'docker' ); ?>>Docker</option>
            <option value="wordpress" <?php selected( $filter_platform, 'wordpress' ); ?>>WordPress</option>
            <option value="drupal" <?php selected( $filter_platform, 'drupal' ); ?>>Drupal</option>
        </select>
        <select name="active">
            <option value=""><?php esc_html_e( 'Alle', 'cwlm' ); ?></option>
            <option value="1" <?php selected( $filter_active, '1' ); ?>><?php esc_html_e( 'Aktiv', 'cwlm' ); ?></option>
            <option value="0" <?php selected( $filter_active, '0' ); ?>><?php esc_html_e( 'Inaktiv', 'cwlm' ); ?></option>
        </select>
        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Suche...', 'cwlm' ); ?>">
        <?php submit_button( __( 'Filtern', 'cwlm' ), 'secondary', 'filter', false ); ?>
    </form>

    <table class="cwlm-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Plattform', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Domain / Hostname', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Lizenz', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Fingerprint', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Version', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Status', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Letzter Check', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Aktiviert am', 'cwlm' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $installations ) ) : ?>
                <tr><td colspan="8"><?php esc_html_e( 'Keine Installationen gefunden.', 'cwlm' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $installations as $inst ) : ?>
                    <tr>
                        <td>
                            <span class="cwlm-platform-<?php echo esc_attr( $inst->platform ); ?>">
                                <?php echo esc_html( $inst->platform ); ?>
                            </span>
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
                            <span class="cwlm-license-key" style="font-size:11px;"><?php echo esc_html( $inst->license_key ?? '–' ); ?></span>
                            <?php if ( $inst->tier ) : ?>
                                <br><span class="cwlm-badge cwlm-tier-<?php echo esc_attr( $inst->tier ); ?>" style="font-size:10px;"><?php echo esc_html( ucfirst( $inst->tier ) ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size:10px;"><?php echo esc_html( substr( $inst->fingerprint, 0, 16 ) . '...' ); ?></code></td>
                        <td><?php echo esc_html( $inst->cachewarmer_version ?: '–' ); ?></td>
                        <td>
                            <?php if ( $inst->is_active ) : ?>
                                <span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'Aktiv', 'cwlm' ); ?></span>
                            <?php else : ?>
                                <span class="cwlm-badge cwlm-badge-inactive"><?php esc_html_e( 'Inaktiv', 'cwlm' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ( $inst->last_check ) {
                                $diff = time() - strtotime( $inst->last_check );
                                if ( $diff < 3600 ) {
                                    printf( esc_html__( 'vor %d Min.', 'cwlm' ), (int) ( $diff / 60 ) );
                                } elseif ( $diff < 86400 ) {
                                    printf( esc_html__( 'vor %d Std.', 'cwlm' ), (int) ( $diff / 3600 ) );
                                } else {
                                    printf( esc_html__( 'vor %d Tagen', 'cwlm' ), (int) ( $diff / 86400 ) );
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
