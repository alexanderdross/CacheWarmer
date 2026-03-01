<?php
/**
 * Admin View: Stripe Produkt-Mapping.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . CWLM_DB_PREFIX;

// CRUD-Aktionen
if ( isset( $_POST['cwlm_product_action'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $_POST['_cwlm_product_nonce'] ?? '', 'cwlm_product_action' ) ) {
    $action = sanitize_text_field( $_POST['cwlm_product_action'] );

    if ( 'create' === $action ) {
        $wpdb->insert(
            $prefix . 'stripe_product_map',
            [
                'stripe_product_id' => sanitize_text_field( $_POST['stripe_product_id'] ?? '' ),
                'stripe_price_id'   => sanitize_text_field( $_POST['stripe_price_id'] ?? '' ) ?: null,
                'tier'              => in_array( $_POST['tier'] ?? '', [ 'free', 'professional', 'enterprise', 'development' ], true ) ? $_POST['tier'] : 'professional',
                'plan'              => sanitize_text_field( $_POST['plan'] ?? '' ),
                'max_sites'         => max( 1, (int) ( $_POST['max_sites'] ?? 1 ) ),
                'duration_days'     => max( 1, (int) ( $_POST['duration_days'] ?? 365 ) ),
                'description'       => sanitize_text_field( $_POST['description'] ?? '' ) ?: null,
                'is_active'         => 1,
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d' ]
        );

        if ( $wpdb->insert_id ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Produkt-Mapping erstellt.', 'cwlm' ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Fehler: Möglicherweise existiert dieses Mapping bereits.', 'cwlm' ) . '</p></div>';
        }
    }

    if ( 'toggle' === $action ) {
        $id = (int) ( $_POST['product_id'] ?? 0 );
        if ( $id ) {
            $current = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT is_active FROM {$prefix}stripe_product_map WHERE id = %d",
                $id
            ) );
            $wpdb->update(
                $prefix . 'stripe_product_map',
                [ 'is_active' => $current ? 0 : 1 ],
                [ 'id' => $id ],
                [ '%d' ],
                [ '%d' ]
            );
        }
    }

    if ( 'delete' === $action ) {
        $id = (int) ( $_POST['product_id'] ?? 0 );
        if ( $id ) {
            $wpdb->delete( $prefix . 'stripe_product_map', [ 'id' => $id ], [ '%d' ] );
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'Produkt-Mapping gelöscht.', 'cwlm' ) . '</p></div>';
        }
    }
}

$products = $wpdb->get_results( "SELECT * FROM {$prefix}stripe_product_map ORDER BY tier ASC, plan ASC" );
?>
<div class="wrap">
    <h1>
        <?php esc_html_e( 'Stripe Produkt-Mapping', 'cwlm' ); ?>
        <button class="page-title-action" id="cwlm-new-product-btn"><?php esc_html_e( 'Neues Mapping', 'cwlm' ); ?></button>
    </h1>

    <p class="description">
        <?php esc_html_e( 'Verknüpft Stripe-Produkte mit CacheWarmer-Lizenz-Tiers. Wird bei checkout.session.completed verwendet.', 'cwlm' ); ?>
    </p>

    <!-- Neues Mapping Formular -->
    <div id="cwlm-new-product-form" style="display:none;" class="cwlm-chart-container">
        <h3><?php esc_html_e( 'Neues Produkt-Mapping erstellen', 'cwlm' ); ?></h3>
        <form method="post">
            <?php wp_nonce_field( 'cwlm_product_action', '_cwlm_product_nonce' ); ?>
            <input type="hidden" name="cwlm_product_action" value="create">
            <table class="form-table">
                <tr>
                    <th><label for="stripe_product_id"><?php esc_html_e( 'Stripe Product ID', 'cwlm' ); ?> *</label></th>
                    <td><input type="text" name="stripe_product_id" id="stripe_product_id" class="regular-text" placeholder="prod_..." required></td>
                </tr>
                <tr>
                    <th><label for="stripe_price_id"><?php esc_html_e( 'Stripe Price ID', 'cwlm' ); ?></label></th>
                    <td><input type="text" name="stripe_price_id" id="stripe_price_id" class="regular-text" placeholder="price_..."></td>
                </tr>
                <tr>
                    <th><label for="pm_tier"><?php esc_html_e( 'Tier', 'cwlm' ); ?></label></th>
                    <td>
                        <select name="tier" id="pm_tier">
                            <option value="free">Free</option>
                            <option value="professional" selected>Professional</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="pm_plan"><?php esc_html_e( 'Plan', 'cwlm' ); ?></label></th>
                    <td><input type="text" name="plan" id="pm_plan" class="regular-text" placeholder="z.B. starter, business, unlimited"></td>
                </tr>
                <tr>
                    <th><label for="pm_max_sites"><?php esc_html_e( 'Max. Installationen', 'cwlm' ); ?></label></th>
                    <td><input type="number" name="max_sites" id="pm_max_sites" value="1" min="1" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="pm_duration"><?php esc_html_e( 'Laufzeit (Tage)', 'cwlm' ); ?></label></th>
                    <td><input type="number" name="duration_days" id="pm_duration" value="365" min="1" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="pm_desc"><?php esc_html_e( 'Beschreibung', 'cwlm' ); ?></label></th>
                    <td><input type="text" name="description" id="pm_desc" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button( __( 'Mapping erstellen', 'cwlm' ) ); ?>
        </form>
    </div>

    <table class="cwlm-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Stripe Product ID', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Stripe Price ID', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Tier', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Plan', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Max. Sites', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Laufzeit', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Status', 'cwlm' ); ?></th>
                <th><?php esc_html_e( 'Aktionen', 'cwlm' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $products ) ) : ?>
                <tr><td colspan="8"><?php esc_html_e( 'Keine Produkt-Mappings vorhanden.', 'cwlm' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $products as $product ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $product->stripe_product_id ); ?></code></td>
                        <td><code><?php echo esc_html( $product->stripe_price_id ?: '–' ); ?></code></td>
                        <td><span class="cwlm-badge cwlm-tier-<?php echo esc_attr( $product->tier ); ?>"><?php echo esc_html( ucfirst( $product->tier ) ); ?></span></td>
                        <td><?php echo esc_html( $product->plan ?: '–' ); ?></td>
                        <td><?php echo esc_html( $product->max_sites ); ?></td>
                        <td><?php printf( esc_html__( '%d Tage', 'cwlm' ), $product->duration_days ); ?></td>
                        <td>
                            <?php if ( $product->is_active ) : ?>
                                <span class="cwlm-badge cwlm-badge-active"><?php esc_html_e( 'Aktiv', 'cwlm' ); ?></span>
                            <?php else : ?>
                                <span class="cwlm-badge cwlm-badge-inactive"><?php esc_html_e( 'Inaktiv', 'cwlm' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'cwlm_product_action', '_cwlm_product_nonce' ); ?>
                                <input type="hidden" name="cwlm_product_action" value="toggle">
                                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>">
                                <button type="submit" class="button button-small" title="<?php echo $product->is_active ? esc_attr__( 'Deaktivieren', 'cwlm' ) : esc_attr__( 'Aktivieren', 'cwlm' ); ?>">
                                    <span class="dashicons dashicons-<?php echo $product->is_active ? 'hidden' : 'visibility'; ?>"></span>
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'cwlm_product_action', '_cwlm_product_nonce' ); ?>
                                <input type="hidden" name="cwlm_product_action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>">
                                <button type="submit" class="button button-small cwlm-confirm-action" data-confirm="<?php esc_attr_e( 'Mapping wirklich löschen?', 'cwlm' ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(function($) {
    $('#cwlm-new-product-btn').on('click', function() {
        $('#cwlm-new-product-form').slideToggle();
    });
});
</script>
