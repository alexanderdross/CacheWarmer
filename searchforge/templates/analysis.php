<?php
defined( 'ABSPATH' ) || exit;

$is_pro = SearchForge\Admin\Settings::is_pro();
$tab    = sanitize_text_field( $_GET['tab'] ?? 'cannibalization' );

$cannibalization = [];
$clusters        = [];

if ( $is_pro ) {
	if ( $tab === 'cannibalization' ) {
		$cannibalization = SearchForge\Analysis\Cannibalization::detect( 50 );
	} elseif ( $tab === 'clusters' ) {
		$clusters = SearchForge\Analysis\Clustering::cluster_keywords( 0.3, 500 );
	}
}
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge — Analysis', 'searchforge' ); ?>
		<?php if ( ! $is_pro ) : ?>
			<span class="sf-pro-badge">Pro</span>
		<?php endif; ?>
	</h1>

	<?php if ( ! $is_pro ) : ?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'Analysis features require a Pro license. Upgrade to unlock cannibalization detection, keyword clustering, and AI content briefs.', 'searchforge' ); ?></p>
		</div>
	<?php else : ?>

		<nav class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-analysis&tab=cannibalization' ) ); ?>"
				class="nav-tab <?php echo $tab === 'cannibalization' ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Cannibalization', 'searchforge' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-analysis&tab=clusters' ) ); ?>"
				class="nav-tab <?php echo $tab === 'clusters' ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Keyword Clusters', 'searchforge' ); ?>
			</a>
		</nav>

		<?php if ( $tab === 'cannibalization' ) : ?>
			<div class="sf-analysis-section">
				<p class="description">
					<?php esc_html_e( 'Keywords where multiple pages from your site compete for the same query, potentially splitting ranking signals.', 'searchforge' ); ?>
				</p>

				<?php if ( empty( $cannibalization ) ) : ?>
					<p><?php esc_html_e( 'No cannibalization detected. This is good! Each keyword maps to a single page.', 'searchforge' ); ?></p>
				<?php else : ?>
					<?php foreach ( $cannibalization as $item ) : ?>
						<div class="sf-cannibal-item sf-cannibal-<?php echo esc_attr( $item['severity'] ); ?>">
							<div class="sf-cannibal-header">
								<strong class="sf-cannibal-query"><?php echo esc_html( $item['query'] ); ?></strong>
								<span class="sf-severity-badge sf-severity-<?php echo esc_attr( $item['severity'] ); ?>">
									<?php echo esc_html( ucfirst( $item['severity'] ) ); ?>
								</span>
								<span class="sf-cannibal-meta">
									<?php echo esc_html( sprintf(
										__( '%d pages | %s clicks | %s impressions | spread: %s pos', 'searchforge' ),
										$item['page_count'],
										number_format( $item['total_clicks'] ),
										number_format( $item['total_impressions'] ),
										$item['position_spread']
									) ); ?>
								</span>
							</div>
							<table class="widefat sf-table sf-cannibal-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $item['pages'] as $page ) : ?>
										<tr>
											<td><code><?php echo esc_html( $page['page_path'] ); ?></code></td>
											<td><?php echo esc_html( round( (float) $page['position'], 1 ) ); ?></td>
											<td><?php echo esc_html( number_format( (int) $page['clicks'] ) ); ?></td>
											<td><?php echo esc_html( number_format( (int) $page['impressions'] ) ); ?></td>
											<td><?php echo esc_html( round( (float) $page['ctr'] * 100, 1 ) ); ?>%</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		<?php elseif ( $tab === 'clusters' ) : ?>
			<div class="sf-analysis-section">
				<p class="description">
					<?php esc_html_e( 'Keywords grouped by topical similarity. Use clusters to identify content themes and optimize internal linking.', 'searchforge' ); ?>
				</p>

				<?php if ( empty( $clusters ) ) : ?>
					<p><?php esc_html_e( 'Not enough keyword data to form clusters. Sync more data first.', 'searchforge' ); ?></p>
				<?php else : ?>
					<?php foreach ( $clusters as $i => $cluster ) : ?>
						<div class="sf-cluster-item">
							<div class="sf-cluster-header">
								<strong><?php echo esc_html( $cluster['name'] ); ?></strong>
								<span class="sf-cluster-meta">
									<?php echo esc_html( sprintf(
										__( '%d keywords | %s clicks | %s impressions', 'searchforge' ),
										count( $cluster['keywords'] ),
										number_format( $cluster['total_clicks'] ),
										number_format( $cluster['total_impressions'] )
									) ); ?>
								</span>
							</div>
							<table class="widefat sf-table sf-cluster-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Keyword', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
										<th><?php esc_html_e( 'Avg Position', 'searchforge' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $cluster['keywords'] as $kw ) : ?>
										<tr>
											<td><?php echo esc_html( $kw['query'] ); ?></td>
											<td><?php echo esc_html( number_format( (int) $kw['total_clicks'] ) ); ?></td>
											<td><?php echo esc_html( number_format( (int) $kw['total_impressions'] ) ); ?></td>
											<td><?php echo esc_html( round( (float) $kw['avg_position'], 1 ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</div>
