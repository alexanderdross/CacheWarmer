<?php
/**
 * Admin View: Lizenzverwaltung.
 *
 * @package SearchForge_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$manager = new SFLM_License_Manager();

// Filter-Parameter (mit Enum-Validierung)
$valid_tiers    = [ '', 'free', 'professional', 'enterprise', 'development' ];
$valid_statuses = [ '', 'active', 'inactive', 'grace_period', 'expired', 'revoked' ];
$filter_tier   = in_array( $_GET['tier'] ?? '', $valid_tiers, true ) ? $_GET['tier'] : '';
$filter_status = in_array( $_GET['status'] ?? '', $valid_statuses, true ) ? $_GET['status'] : '';
$search        = sanitize_text_field( $_GET['s'] ?? '' );
$paged         = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page      = 20;

// CRUD-Aktionen verarbeiten
if ( isset( $_POST['sflm_action'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $_POST['_sflm_nonce'] ?? '', 'sflm_license_action' ) ) {
    $action = sanitize_text_field( $_POST['sflm_action'] );

    if ( 'create' === $action ) {
        $valid_create_tiers = [ 'free', 'professional', 'enterprise', 'development' ];
        $tier      = in_array( $_POST['tier'] ?? '', $valid_create_tiers, true ) ? $_POST['tier'] : 'free';
        $email     = sanitize_email( $_POST['customer_email'] ?? '' );
        $name      = sanitize_text_field( $_POST['customer_name'] ?? '' );
        $plan      = sanitize_text_field( $_POST['plan'] ?? '' );
        $max_sites = max( 1, (int) ( $_POST['max_sites'] ?? 1 ) );
        $expires   = sanitize_text_field( $_POST['expires_at'] ?? '' );

        if ( $email ) {
            $license_id = $manager->create_license( [
                'customer_email' => $email,
                'customer_name'  => $name,
                'tier'           => $tier,
                'plan'           => $plan ?: null,
                'max_sites'      => $max_sites,
                'expires_at'     => ( $expires && false !== strtotime( $expires ) ) ? gmdate( 'Y-m-d H:i:s', strtotime( $expires ) ) : null,
            ] );

            if ( $license_id ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Lizenz erfolgreich erstellt.', 'sflm' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Fehler beim Erstellen der Lizenz.', 'sflm' ) . '</p></div>';
            }
        }
    }

    if ( 'revoke' === $action ) {
        $license_id = (int) ( $_POST['license_id'] ?? 0 );
        $reason     = sanitize_text_field( $_POST['reason'] ?? 'Admin-Aktion' );
        if ( $license_id ) {
            $manager->revoke_license( $license_id, $reason );
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'Lizenz wurde gesperrt.', 'sflm' ) . '</p></div>';
        }
    }

    if ( 'extend' === $action ) {
        $license_id = (int) ( $_POST['license_id'] ?? 0 );
        $days       = max( 1, (int) ( $_POST['days'] ?? 365 ) );
        if ( $license_id ) {
            $manager->extend_license( $license_id, $days );
            echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( 'Lizenz um %d Tage verlängert.', 'sflm' ), $days ) . '</p></div>';
        }
    }
}

// Lizenzen laden
$filters = [];
if ( $filter_tier ) {
    $filters['tier'] = $filter_tier;
}
if ( $filter_status ) {
    $filters['status'] = $filter_status;
}
if ( $search ) {
    $filters['search'] = $search;
}

$result     = $manager->list_licenses( $filters, $paged, $per_page );
$licenses   = $result['items'];
$total      = $result['total'];
$total_pages = (int) ceil( $total / $per_page );
?>
<div class="wrap" role="main">
    <h1>
        <?php esc_html_e( 'Lizenzen', 'sflm' ); ?>
        <button class="page-title-action" id="sflm-new-license-btn"><?php esc_html_e( 'Neue Lizenz', 'sflm' ); ?></button>
    </h1>

    <!-- Neue Lizenz Formular (versteckt) -->
    <div id="sflm-new-license-form" style="display:none;" class="sflm-chart-container">
        <h3><?php esc_html_e( 'Neue Lizenz erstellen', 'sflm' ); ?></h3>
        <form method="post">
            <?php wp_nonce_field( 'sflm_license_action', '_sflm_nonce' ); ?>
            <input type="hidden" name="sflm_action" value="create">
            <table class="form-table">
                <tr>
                    <th><label for="customer_email"><?php esc_html_e( 'E-Mail', 'sflm' ); ?> *</label></th>
                    <td><input type="email" name="customer_email" id="customer_email" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="customer_name"><?php esc_html_e( 'Name', 'sflm' ); ?></label></th>
                    <td><input type="text" name="customer_name" id="customer_name" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="tier"><?php esc_html_e( 'Tier', 'sflm' ); ?></label></th>
                    <td>
                        <select name="tier" id="tier">
                            <option value="free">Free</option>
                            <option value="professional">Professional</option>
                            <option value="enterprise">Enterprise</option>
                            <option value="development">Development</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="plan"><?php esc_html_e( 'Plan', 'sflm' ); ?></label></th>
                    <td><input type="text" name="plan" id="plan" class="regular-text" placeholder="z.B. starter, business"></td>
                </tr>
                <tr>
                    <th><label for="max_sites"><?php esc_html_e( 'Max. Installationen', 'sflm' ); ?></label></th>
                    <td><input type="number" name="max_sites" id="max_sites" value="1" min="1" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="expires_at"><?php esc_html_e( 'Ablaufdatum', 'sflm' ); ?></label></th>
                    <td><input type="date" name="expires_at" id="expires_at"></td>
                </tr>
            </table>
            <?php submit_button( __( 'Lizenz erstellen', 'sflm' ) ); ?>
        </form>
    </div>

    <!-- Filter -->
    <form method="get" class="sflm-filter-bar">
        <input type="hidden" name="page" value="sflm-licenses">
        <select name="tier">
            <option value=""><?php esc_html_e( 'Alle Tiers', 'sflm' ); ?></option>
            <option value="free" <?php selected( $filter_tier, 'free' ); ?>>Free</option>
            <option value="professional" <?php selected( $filter_tier, 'professional' ); ?>>Professional</option>
            <option value="enterprise" <?php selected( $filter_tier, 'enterprise' ); ?>>Enterprise</option>
            <option value="development" <?php selected( $filter_tier, 'development' ); ?>>Development</option>
        </select>
        <select name="status">
            <option value=""><?php esc_html_e( 'Alle Status', 'sflm' ); ?></option>
            <option value="active" <?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Aktiv', 'sflm' ); ?></option>
            <option value="inactive" <?php selected( $filter_status, 'inactive' ); ?>><?php esc_html_e( 'Inaktiv', 'sflm' ); ?></option>
            <option value="grace_period" <?php selected( $filter_status, 'grace_period' ); ?>><?php esc_html_e( 'Karenzzeit', 'sflm' ); ?></option>
            <option value="expired" <?php selected( $filter_status, 'expired' ); ?>><?php esc_html_e( 'Abgelaufen', 'sflm' ); ?></option>
            <option value="revoked" <?php selected( $filter_status, 'revoked' ); ?>><?php esc_html_e( 'Gesperrt', 'sflm' ); ?></option>
        </select>
        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Suche...', 'sflm' ); ?>">
        <?php submit_button( __( 'Filtern', 'sflm' ), 'secondary', 'filter', false ); ?>
    </form>

    <!-- Tabelle -->
    <table class="sflm-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Lizenzschlüssel', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Kunde', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Tier', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Status', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Sites', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Ablaufdatum', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Erstellt', 'sflm' ); ?></th>
                <th><?php esc_html_e( 'Aktionen', 'sflm' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $licenses ) ) : ?>
                <tr><td colspan="8"><?php esc_html_e( 'Keine Lizenzen gefunden.', 'sflm' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $licenses as $license ) : ?>
                    <tr>
                        <td>
                            <span class="sflm-license-key"><?php echo esc_html( $license->license_key ); ?></span>
                            <button class="button-link sflm-copy-key" data-key="<?php echo esc_attr( $license->license_key ); ?>" title="<?php esc_attr_e( 'Kopieren', 'sflm' ); ?>" aria-label="<?php esc_attr_e( 'Lizenzschlüssel kopieren', 'sflm' ); ?>">
                                <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                            </button>
                        </td>
                        <td>
                            <?php echo esc_html( $license->customer_name ?: '–' ); ?><br>
                            <small><?php echo esc_html( $license->customer_email ); ?></small>
                        </td>
                        <td><span class="sflm-badge sflm-tier-<?php echo esc_attr( $license->tier ); ?>"><?php echo esc_html( ucfirst( $license->tier ) ); ?></span></td>
                        <td><span class="sflm-badge sflm-badge-<?php echo esc_attr( $license->status ); ?>"><?php echo esc_html( $license->status ); ?></span></td>
                        <td><?php echo esc_html( $license->active_sites . '/' . $license->max_sites ); ?></td>
                        <td><?php echo $license->expires_at ? esc_html( wp_date( 'd.m.Y', strtotime( $license->expires_at ) ) ) : '–'; ?></td>
                        <td><?php echo esc_html( wp_date( 'd.m.Y', strtotime( $license->created_at ) ) ); ?></td>
                        <td>
                            <?php if ( $license->status !== 'revoked' ) : ?>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'sflm_license_action', '_sflm_nonce' ); ?>
                                    <input type="hidden" name="sflm_action" value="extend">
                                    <input type="hidden" name="license_id" value="<?php echo esc_attr( $license->id ); ?>">
                                    <input type="hidden" name="days" value="365">
                                    <button type="submit" class="button button-small" title="<?php esc_attr_e( '+1 Jahr', 'sflm' ); ?>" aria-label="<?php esc_attr_e( 'Lizenz um 1 Jahr verlängern', 'sflm' ); ?>">
                                        <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                                    </button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'sflm_license_action', '_sflm_nonce' ); ?>
                                    <input type="hidden" name="sflm_action" value="revoke">
                                    <input type="hidden" name="license_id" value="<?php echo esc_attr( $license->id ); ?>">
                                    <input type="hidden" name="reason" value="Admin-Aktion">
                                    <button type="submit" class="button button-small sflm-confirm-action" data-confirm="<?php esc_attr_e( 'Lizenz wirklich sperren?', 'sflm' ); ?>" title="<?php esc_attr_e( 'Sperren', 'sflm' ); ?>" aria-label="<?php esc_attr_e( 'Lizenz sperren', 'sflm' ); ?>">
                                        <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf( esc_html__( '%d Einträge', 'sflm' ), $total ); ?></span>
                <?php
                $base_url = add_query_arg( [
                    'page'   => 'sflm-licenses',
                    'tier'   => $filter_tier,
                    'status' => $filter_status,
                    's'      => $search,
                ], admin_url( 'admin.php' ) );

                echo paginate_links( [
                    'base'    => $base_url . '%_%',
                    'format'  => '&paged=%#%',
                    'current' => $paged,
                    'total'   => $total_pages,
                ] );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

