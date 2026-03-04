<?php

namespace SearchForge\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
	}

	public function register_menus(): void {
		add_menu_page(
			__( 'SearchForge', 'searchforge' ),
			__( 'SearchForge', 'searchforge' ),
			'manage_options',
			'searchforge',
			[ $this, 'render_dashboard' ],
			'dashicons-search',
			30
		);

		add_submenu_page(
			'searchforge',
			__( 'Dashboard', 'searchforge' ),
			__( 'Dashboard', 'searchforge' ),
			'manage_options',
			'searchforge',
			[ $this, 'render_dashboard' ]
		);

		add_submenu_page(
			'searchforge',
			__( 'Pages', 'searchforge' ),
			__( 'Pages', 'searchforge' ),
			'manage_options',
			'searchforge-pages',
			[ $this, 'render_pages' ]
		);

		add_submenu_page(
			'searchforge',
			__( 'Keywords', 'searchforge' ),
			__( 'Keywords', 'searchforge' ),
			'manage_options',
			'searchforge-keywords',
			[ $this, 'render_keywords' ]
		);

		add_submenu_page(
			'searchforge',
			__( 'Export', 'searchforge' ),
			__( 'Export', 'searchforge' ),
			'manage_options',
			'searchforge-export',
			[ $this, 'render_export' ]
		);

		add_submenu_page(
			'searchforge',
			__( 'Settings', 'searchforge' ),
			__( 'Settings', 'searchforge' ),
			'manage_options',
			'searchforge-settings',
			[ $this, 'render_settings' ]
		);
	}

	public function render_dashboard(): void {
		include SEARCHFORGE_PATH . 'templates/dashboard.php';
	}

	public function render_pages(): void {
		include SEARCHFORGE_PATH . 'templates/pages.php';
	}

	public function render_keywords(): void {
		include SEARCHFORGE_PATH . 'templates/keywords.php';
	}

	public function render_export(): void {
		include SEARCHFORGE_PATH . 'templates/export.php';
	}

	public function render_settings(): void {
		include SEARCHFORGE_PATH . 'templates/settings.php';
	}
}
