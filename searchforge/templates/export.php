<?php
defined( 'ABSPATH' ) || exit;

$pages  = SearchForge\Admin\Dashboard::get_top_pages( 50 );
$is_pro = SearchForge\Admin\Settings::is_pro();
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge — Export', 'searchforge' ); ?></h1>

	<?php if ( ! $is_pro ) : ?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'Markdown export is a Pro feature. You can preview briefs in the dashboard, but export requires a Pro license.', 'searchforge' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="sf-export-actions">
		<h2><?php esc_html_e( 'Site Brief', 'searchforge' ); ?></h2>
		<p><?php esc_html_e( 'Export a complete site overview with all pages and aggregate metrics.', 'searchforge' ); ?></p>
		<button type="button" class="button button-primary" id="sf-export-site"
			<?php disabled( ! $is_pro ); ?>>
			<?php esc_html_e( 'Export Site Brief (.md)', 'searchforge' ); ?>
		</button>
	</div>

	<?php if ( ! empty( $pages ) ) : ?>
		<h2><?php esc_html_e( 'Per-Page Briefs', 'searchforge' ); ?></h2>
		<p><?php esc_html_e( 'Export a detailed brief for a specific page with keyword data and insights.', 'searchforge' ); ?></p>
		<table class="widefat sf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'searchforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pages as $page ) : ?>
					<tr>
						<td><code><?php echo esc_html( $page['page_path'] ); ?></code></td>
						<td><?php echo esc_html( number_format( (int) $page['clicks'] ) ); ?></td>
						<td>
							<button class="button button-small sf-export-btn"
								data-page="<?php echo esc_attr( $page['page_path'] ); ?>"
								<?php disabled( ! $is_pro ); ?>>
								<?php esc_html_e( 'Export .md', 'searchforge' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<!-- Modal for preview / download -->
	<div id="sf-export-modal" class="sf-modal" style="display:none;">
		<div class="sf-modal-content">
			<span class="sf-modal-close">&times;</span>
			<h2 id="sf-modal-title"></h2>
			<pre id="sf-modal-body"></pre>
			<button type="button" class="button button-primary" id="sf-modal-download">
				<?php esc_html_e( 'Download .md', 'searchforge' ); ?>
			</button>
		</div>
	</div>
</div>
