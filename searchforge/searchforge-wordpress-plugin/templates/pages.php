<?php
defined( 'ABSPATH' ) || exit;

$per_page = 50;
$paged    = max( 1, absint( $_GET['paged'] ?? 1 ) );
$search   = sanitize_text_field( $_GET['s'] ?? '' );
$offset   = ( $paged - 1 ) * $per_page;

$pages    = SearchForge\Admin\Dashboard::get_top_pages( $per_page, '', $offset, $search );
$total    = SearchForge\Admin\Dashboard::count_pages( $search );
$settings = SearchForge\Admin\Settings::get_all();
$is_pro   = SearchForge\Admin\Settings::is_pro();
$limit    = SearchForge\Admin\Settings::get_page_limit();

$total_pages = ceil( $total / $per_page );
$base_url    = admin_url( 'admin.php?page=searchforge-pages' );
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge — Pages', 'searchforge' ); ?>
		<span class="title-count">(<?php echo esc_html( number_format( $total ) ); ?>)</span>
	</h1>

	<?php if ( $limit > 0 && $total >= $limit ) : ?>
		<div class="notice notice-info">
			<p>
				<?php echo esc_html( sprintf(
					__( 'Free tier is limited to %d pages. Upgrade to Pro for unlimited pages.', 'searchforge' ),
					$limit
				) ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Search -->
	<form method="get" class="sf-search-form">
		<input type="hidden" name="page" value="searchforge-pages" />
		<p class="search-box">
			<label class="screen-reader-text" for="sf-search-input">
				<?php esc_html_e( 'Search pages:', 'searchforge' ); ?>
			</label>
			<input type="search" id="sf-search-input" name="s"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Search pages...', 'searchforge' ); ?>" />
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'searchforge' ); ?>" />
			<?php if ( $search ) : ?>
				<a href="<?php echo esc_url( $base_url ); ?>" class="button">
					<?php esc_html_e( 'Clear', 'searchforge' ); ?>
				</a>
			<?php endif; ?>
		</p>
	</form>

	<?php if ( empty( $pages ) ) : ?>
		<p><?php esc_html_e( 'No page data available. Run a GSC sync first.', 'searchforge' ); ?></p>
	<?php else : ?>

		<!-- Bulk Actions -->
		<?php if ( $is_pro ) : ?>
			<div class="sf-bulk-actions">
				<label class="sf-bulk-select-label">
					<input type="checkbox" id="sf-select-all" />
					<?php esc_html_e( 'Select all', 'searchforge' ); ?>
				</label>
				<button type="button" class="button" id="sf-bulk-export" disabled>
					<?php esc_html_e( 'Export Selected Briefs', 'searchforge' ); ?>
				</button>
				<button type="button" class="button" id="sf-bulk-ai-brief" disabled>
					<?php esc_html_e( 'Bulk AI Brief', 'searchforge' ); ?>
				</button>
				<span id="sf-bulk-count" class="sf-bulk-count"></span>
			</div>
		<?php endif; ?>

		<table class="widefat sf-table">
			<thead>
				<tr>
					<?php if ( $is_pro ) : ?>
						<th class="sf-check-col">
							<input type="checkbox" class="sf-select-all-th" />
						</th>
					<?php endif; ?>
					<th scope="col"><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'searchforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pages as $page ) : ?>
					<tr>
						<?php if ( $is_pro ) : ?>
							<td class="sf-check-col">
								<input type="checkbox" class="sf-page-check"
									value="<?php echo esc_attr( $page['page_path'] ); ?>" />
							</td>
						<?php endif; ?>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-page-detail&path=' . urlencode( $page['page_path'] ) ) ); ?>">
								<?php echo esc_html( $page['page_path'] ); ?>
							</a>
							<a href="<?php echo esc_url( home_url( $page['page_path'] ) ); ?>" target="_blank" class="sf-external-link" title="<?php esc_attr_e( 'View page', 'searchforge' ); ?>" aria-label="<?php esc_attr_e( 'View page in new tab', 'searchforge' ); ?>">&#8599;</a>
						</td>
						<td><?php echo esc_html( number_format( (int) $page['clicks'] ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) $page['impressions'] ) ); ?></td>
						<td><?php echo esc_html( round( (float) $page['ctr'] * 100, 1 ) ); ?>%</td>
						<td><?php echo esc_html( round( (float) $page['position'], 1 ) ); ?></td>
						<td>
							<?php if ( $is_pro ) : ?>
								<button class="button button-small sf-export-btn"
									data-page="<?php echo esc_attr( $page['page_path'] ); ?>">
									<?php esc_html_e( 'Export Brief', 'searchforge' ); ?>
								</button>
								<button class="button button-small sf-ai-brief-btn"
									data-page="<?php echo esc_attr( $page['page_path'] ); ?>">
									<?php esc_html_e( 'AI Brief', 'searchforge' ); ?>
								</button>
							<?php else : ?>
								<span class="sf-pro-badge" title="<?php esc_attr_e( 'Pro feature', 'searchforge' ); ?>">Pro</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php echo esc_html( sprintf(
							/* translators: %s: total items */
							__( '%s items', 'searchforge' ),
							number_format( $total )
						) ); ?>
					</span>
					<span class="pagination-links">
						<?php if ( $paged > 1 ) : ?>
							<a class="prev-page button" href="<?php echo esc_url( add_query_arg( [ 'paged' => $paged - 1, 's' => $search ], $base_url ) ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'searchforge' ); ?>">
								&lsaquo;
							</a>
						<?php endif; ?>
						<span class="paging-input">
							<?php echo esc_html( $paged ); ?> / <?php echo esc_html( $total_pages ); ?>
						</span>
						<?php if ( $paged < $total_pages ) : ?>
							<a class="next-page button" href="<?php echo esc_url( add_query_arg( [ 'paged' => $paged + 1, 's' => $search ], $base_url ) ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'searchforge' ); ?>">
								&rsaquo;
							</a>
						<?php endif; ?>
					</span>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<!-- Bulk progress modal -->
<div id="sf-bulk-modal" class="sf-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sf-bulk-modal-title">
	<div class="sf-modal-content">
		<span class="sf-modal-close" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Close modal', 'searchforge' ); ?>">&times;</span>
		<h2 id="sf-bulk-modal-title"></h2>
		<div id="sf-bulk-progress">
			<div class="sf-bulk-progress-bar">
				<div class="sf-bulk-progress-fill" id="sf-bulk-fill"></div>
			</div>
			<p id="sf-bulk-status"></p>
		</div>
		<pre id="sf-bulk-output" style="display:none;"></pre>
		<button class="button button-primary" id="sf-bulk-download" style="display:none;">
			<?php esc_html_e( 'Download All', 'searchforge' ); ?>
		</button>
	</div>
</div>

<!-- Export Modal -->
<div id="sf-export-modal" class="sf-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sf-modal-title">
	<div class="sf-modal-content">
		<span class="sf-modal-close" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Close modal', 'searchforge' ); ?>">&times;</span>
		<h2 id="sf-modal-title"></h2>
		<pre id="sf-modal-body"></pre>
		<button class="button button-primary" id="sf-modal-download">
			<?php esc_html_e( 'Download', 'searchforge' ); ?>
		</button>
	</div>
</div>
