<?php
defined( 'ABSPATH' ) || exit;

$keywords = SearchForge\Admin\Dashboard::get_top_keywords( 100 );
$is_pro   = SearchForge\Admin\Settings::is_pro();
$is_free  = ! $is_pro;
?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge — Keywords', 'searchforge' ); ?></h1>

	<?php if ( $is_free && count( $keywords ) >= 100 ) : ?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'Free tier shows up to 100 keywords. Upgrade to Pro to see all keywords and unlock clustering.', 'searchforge' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $keywords ) ) : ?>
		<p><?php esc_html_e( 'No keyword data available. Run a GSC sync first.', 'searchforge' ); ?></p>
	<?php else : ?>
		<table class="widefat sf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Keyword', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Page', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Clicks', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Impressions', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'CTR', 'searchforge' ); ?></th>
					<th><?php esc_html_e( 'Position', 'searchforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $keywords as $kw ) : ?>
					<tr>
						<td><?php echo esc_html( $kw['query'] ); ?></td>
						<td><code><?php echo esc_html( $kw['page_path'] ); ?></code></td>
						<td><?php echo esc_html( number_format( (int) $kw['clicks'] ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) $kw['impressions'] ) ); ?></td>
						<td><?php echo esc_html( round( (float) $kw['ctr'] * 100, 1 ) ); ?>%</td>
						<td><?php echo esc_html( round( (float) $kw['position'], 1 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
