<?php
/**
 * Dashboard template — main overview with warm form, status cards, and job table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$db     = CacheWarmer::get_instance()->get_database();
$counts = $db->get_job_counts();
$total  = $db->get_total_urls_processed();
$jobs   = $db->get_jobs( 20 );
?>
<div class="wrap cachewarmer-wrap">
    <h1>
        <span class="dashicons dashicons-performance"></span>
        <?php esc_html_e( 'CacheWarmer Dashboard', 'cachewarmer' ); ?>
    </h1>

    <!-- Status Cards -->
    <div class="cachewarmer-status-cards">
        <div class="cachewarmer-card">
            <h3><?php esc_html_e( 'Queued', 'cachewarmer' ); ?></h3>
            <span class="cachewarmer-card-value status-queued" id="cw-status-queued"><?php echo esc_html( $counts['queued'] ); ?></span>
        </div>
        <div class="cachewarmer-card">
            <h3><?php esc_html_e( 'Running', 'cachewarmer' ); ?></h3>
            <span class="cachewarmer-card-value status-running" id="cw-status-running"><?php echo esc_html( $counts['running'] ); ?></span>
        </div>
        <div class="cachewarmer-card">
            <h3><?php esc_html_e( 'Completed', 'cachewarmer' ); ?></h3>
            <span class="cachewarmer-card-value status-completed" id="cw-status-completed"><?php echo esc_html( $counts['completed'] ); ?></span>
        </div>
        <div class="cachewarmer-card">
            <h3><?php esc_html_e( 'Failed', 'cachewarmer' ); ?></h3>
            <span class="cachewarmer-card-value status-failed" id="cw-status-failed"><?php echo esc_html( $counts['failed'] ); ?></span>
        </div>
        <div class="cachewarmer-card">
            <h3><?php esc_html_e( 'URLs Processed', 'cachewarmer' ); ?></h3>
            <span class="cachewarmer-card-value" id="cw-status-total"><?php echo esc_html( $total ); ?></span>
        </div>
    </div>

    <!-- Warm Form -->
    <div class="cachewarmer-section">
        <h2><?php esc_html_e( 'Start Cache Warming', 'cachewarmer' ); ?></h2>
        <form id="cw-warm-form" class="cachewarmer-form">
            <div class="cachewarmer-form-row">
                <label for="cw-sitemap-url"><?php esc_html_e( 'Sitemap URL', 'cachewarmer' ); ?></label>
                <input type="url" id="cw-sitemap-url" name="sitemapUrl" class="regular-text"
                       placeholder="https://example.com/sitemap.xml" required>
            </div>

            <div class="cachewarmer-form-row">
                <label><?php esc_html_e( 'Targets', 'cachewarmer' ); ?></label>
                <div class="cachewarmer-targets">
                    <?php
                    $targets = array(
                        'cdn'      => 'CDN',
                        'facebook' => 'Facebook',
                        'linkedin' => 'LinkedIn',
                        'twitter'  => 'Twitter/X',
                        'google'   => 'Google',
                        'bing'     => 'Bing',
                        'indexnow' => 'IndexNow',
                    );
                    foreach ( $targets as $key => $label ) :
                        ?>
                        <label class="cachewarmer-target-label">
                            <input type="checkbox" name="targets[]" value="<?php echo esc_attr( $key ); ?>" checked>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="button button-primary button-hero" id="cw-warm-submit">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Start Warming', 'cachewarmer' ); ?>
            </button>
            <span id="cw-warm-spinner" class="spinner" style="float: none;"></span>
            <div id="cw-warm-message" class="cachewarmer-message" style="display:none;"></div>
        </form>
    </div>

    <!-- Jobs Table -->
    <div class="cachewarmer-section">
        <h2><?php esc_html_e( 'Recent Jobs', 'cachewarmer' ); ?></h2>
        <table class="wp-list-table widefat fixed striped" id="cw-jobs-table">
            <thead>
                <tr>
                    <th class="column-status"><?php esc_html_e( 'Status', 'cachewarmer' ); ?></th>
                    <th class="column-sitemap"><?php esc_html_e( 'Sitemap', 'cachewarmer' ); ?></th>
                    <th class="column-progress">
                        <?php esc_html_e( 'Progress', 'cachewarmer' ); ?>
                        <span class="dashicons dashicons-editor-help cachewarmer-help-tip"
                              title="<?php esc_attr_e( 'Total tasks = URLs × active services. Each URL is processed once per enabled target (CDN, Facebook, etc.).', 'cachewarmer' ); ?>"></span>
                    </th>
                    <th class="column-targets"><?php esc_html_e( 'Targets', 'cachewarmer' ); ?></th>
                    <th class="column-created"><?php esc_html_e( 'Created', 'cachewarmer' ); ?></th>
                    <th class="column-actions"><?php esc_html_e( 'Actions', 'cachewarmer' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $jobs ) ) : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No jobs yet. Start a warming job above.', 'cachewarmer' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $jobs as $job ) :
                        $job_targets      = json_decode( $job->targets, true ) ?: array();
                        $active_count     = count( $job_targets );
                        $urls_in_sitemap  = $active_count > 0 ? intval( $job->total_urls / $active_count ) : $job->total_urls;
                        $progress         = $job->total_urls > 0 ? round( ( $job->processed_urls / $job->total_urls ) * 100 ) : 0;
                        $progress_tooltip = sprintf(
                            /* translators: 1: processed tasks, 2: total tasks, 3: URL count, 4: target count */
                            __( '%1$d / %2$d tasks (%3$d URLs × %4$d services)', 'cachewarmer' ),
                            $job->processed_urls,
                            $job->total_urls,
                            $urls_in_sitemap,
                            $active_count
                        );
                    ?>
                        <tr data-job-id="<?php echo esc_attr( $job->id ); ?>">
                            <td>
                                <span class="cachewarmer-badge badge-<?php echo esc_attr( $job->status ); ?>">
                                    <?php echo esc_html( ucfirst( $job->status ) ); ?>
                                </span>
                            </td>
                            <td class="column-sitemap" title="<?php echo esc_attr( $job->sitemap_url ); ?>">
                                <?php echo esc_html( wp_parse_url( $job->sitemap_url, PHP_URL_HOST ) ); ?>
                            </td>
                            <td>
                                <div class="cachewarmer-progress" title="<?php echo esc_attr( $progress_tooltip ); ?>">
                                    <div class="cachewarmer-progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
                                    <span class="cachewarmer-progress-text">
                                        <?php echo esc_html( $job->processed_urls . '/' . $job->total_urls ); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php foreach ( $job_targets as $t ) : ?>
                                    <span class="cachewarmer-tag"><?php echo esc_html( $t ); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td><?php echo esc_html( $job->created_at ); ?></td>
                            <td>
                                <button class="button button-small cw-job-details" data-job-id="<?php echo esc_attr( $job->id ); ?>">
                                    <?php esc_html_e( 'Details', 'cachewarmer' ); ?>
                                </button>
                                <button class="button button-small button-link-delete cw-job-delete" data-job-id="<?php echo esc_attr( $job->id ); ?>">
                                    <?php esc_html_e( 'Delete', 'cachewarmer' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Job Detail Modal -->
    <div id="cw-job-modal" class="cachewarmer-modal" style="display:none;">
        <div class="cachewarmer-modal-content">
            <span class="cachewarmer-modal-close">&times;</span>
            <h2><?php esc_html_e( 'Job Details', 'cachewarmer' ); ?></h2>
            <div id="cw-job-modal-body">
                <div class="spinner is-active" style="float:none;"></div>
            </div>
        </div>
    </div>

    <div class="cachewarmer-footer">
        <?php
        printf(
            'made with %s by <a href="https://dross.net/media/?ref=cachewarmer" target="_blank" rel="noopener">Dross:Media</a>',
            '<span class="cachewarmer-heart">&hearts;</span>'
        );
        ?>
    </div>
</div>
