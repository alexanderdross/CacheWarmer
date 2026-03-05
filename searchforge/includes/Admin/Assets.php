<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue( string $hook ): void {
		if ( false === strpos( $hook, 'searchforge' ) ) {
			return;
		}

		wp_enqueue_style(
			'searchforge-admin',
			SEARCHFORGE_URL . 'assets/css/admin.css',
			[],
			SEARCHFORGE_VERSION
		);

		wp_enqueue_script(
			'searchforge-admin',
			SEARCHFORGE_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			SEARCHFORGE_VERSION,
			true
		);

		wp_localize_script( 'searchforge-admin', 'searchforge', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'searchforge_nonce' ),
			'rest_url' => rest_url( 'searchforge/v1/' ),
		] );

		// Chart.js on dashboard and page detail pages.
		$needs_charts = false !== strpos( $hook, 'searchforge-page-detail' )
			|| 'toplevel_page_searchforge' === $hook;

		if ( $needs_charts ) {
			wp_enqueue_script(
				'chartjs',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
				[],
				'4.4.7',
				true
			);

			wp_enqueue_script(
				'searchforge-charts',
				SEARCHFORGE_URL . 'assets/js/charts.js',
				[ 'jquery', 'chartjs' ],
				SEARCHFORGE_VERSION,
				true
			);
		}
	}
}
