<?php
/**
 * Sitemaps management template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$db       = CacheWarmer::get_instance()->get_database();
$sitemaps = $db->get_all_sitemaps();
?>
<div class="wrap cachewarmer-wrap">
    <h1>
        <span class="dashicons dashicons-admin-site-alt3"></span>
        <?php esc_html_e( 'Sitemap Management', 'cachewarmer' ); ?>
    </h1>

    <!-- Add Sitemap Form -->
    <div class="cachewarmer-section">
        <h2><?php esc_html_e( 'Register Sitemap', 'cachewarmer' ); ?></h2>
        <form id="cw-add-sitemap-form" class="cachewarmer-form">
            <div class="cachewarmer-form-row cachewarmer-form-inline">
                <div>
                    <label for="cw-new-sitemap-url"><?php esc_html_e( 'Sitemap URL', 'cachewarmer' ); ?></label>
                    <input type="url" id="cw-new-sitemap-url" name="url" class="regular-text"
                           placeholder="https://example.com/sitemap.xml" required>
                </div>
                <div>
                    <label for="cw-new-sitemap-cron"><?php esc_html_e( 'Cron Expression (optional)', 'cachewarmer' ); ?></label>
                    <input type="text" id="cw-new-sitemap-cron" name="cronExpression" class="regular-text"
                           placeholder="0 3 * * *">
                </div>
                <div>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Add Sitemap', 'cachewarmer' ); ?>
                    </button>
                </div>
            </div>
            <span id="cw-sitemap-spinner" class="spinner" style="float: none;"></span>
            <div id="cw-sitemap-message" class="cachewarmer-message" style="display:none;"></div>
        </form>
    </div>

    <!-- Bulk Import -->
    <div class="cachewarmer-section">
        <h2><?php esc_html_e( 'Bulk Import Sitemaps', 'cachewarmer' ); ?></h2>
        <form id="cw-bulk-import-form" class="cachewarmer-form">
            <div class="cachewarmer-form-row">
                <label for="cw-bulk-urls"><?php esc_html_e( 'Sitemap URLs (one per line)', 'cachewarmer' ); ?></label>
                <textarea id="cw-bulk-urls" name="urls" rows="5" class="large-text"
                          placeholder="https://example.com/sitemap.xml&#10;https://another-site.com/sitemap.xml&#10;https://third-site.com/sitemap_index.xml"></textarea>
            </div>
            <div class="cachewarmer-form-row">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'Import All', 'cachewarmer' ); ?>
                </button>
                <button type="button" id="cw-detect-sitemaps" class="button">
                    <?php esc_html_e( 'Auto-Detect Local Sitemaps', 'cachewarmer' ); ?>
                </button>
                <span id="cw-bulk-spinner" class="spinner" style="float: none;"></span>
            </div>
            <div id="cw-bulk-message" class="cachewarmer-message" style="display:none;"></div>
        </form>
    </div>

    <!-- Sitemaps Table -->
    <div class="cachewarmer-section">
        <h2><?php esc_html_e( 'Registered Sitemaps', 'cachewarmer' ); ?></h2>
        <table class="wp-list-table widefat fixed striped" id="cw-sitemaps-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Domain', 'cachewarmer' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'cachewarmer' ); ?></th>
                    <th><?php esc_html_e( 'Cron', 'cachewarmer' ); ?></th>
                    <th><?php esc_html_e( 'Last Warmed', 'cachewarmer' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'cachewarmer' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $sitemaps ) ) : ?>
                    <tr id="cw-no-sitemaps-row">
                        <td colspan="5"><?php esc_html_e( 'No sitemaps registered yet.', 'cachewarmer' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $sitemaps as $sitemap ) : ?>
                        <tr data-sitemap-id="<?php echo esc_attr( $sitemap->id ); ?>">
                            <td><?php echo esc_html( $sitemap->domain ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $sitemap->url ); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html( $sitemap->url ); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo $sitemap->cron_expression ? esc_html( $sitemap->cron_expression ) : '<em>' . esc_html__( 'None', 'cachewarmer' ) . '</em>'; ?>
                            </td>
                            <td>
                                <?php echo $sitemap->last_warmed_at ? esc_html( $sitemap->last_warmed_at ) : '<em>' . esc_html__( 'Never', 'cachewarmer' ) . '</em>'; ?>
                            </td>
                            <td>
                                <button class="button button-small button-primary cw-warm-sitemap" data-sitemap-id="<?php echo esc_attr( $sitemap->id ); ?>">
                                    <?php esc_html_e( 'Warm Now', 'cachewarmer' ); ?>
                                </button>
                                <button class="button button-small button-link-delete cw-delete-sitemap" data-sitemap-id="<?php echo esc_attr( $sitemap->id ); ?>">
                                    <?php esc_html_e( 'Delete', 'cachewarmer' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
