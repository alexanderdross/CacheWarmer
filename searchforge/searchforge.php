<?php
/**
 * Plugin Name: SearchForge
 * Plugin URI:  https://forge.drossmedia.de
 * Description: Unifies search data sources (GSC, Bing, Keyword Planner, Trends, GA4) into LLM-ready markdown briefs with AI content analysis.
 * Version:     1.8.0
 * Author:      Dross Media
 * Author URI:  https://drossmedia.de
 * License:     GPL-2.0-or-later
 * Text Domain: searchforge
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SEARCHFORGE_VERSION', '1.8.0' );
define( 'SEARCHFORGE_FILE', __FILE__ );
define( 'SEARCHFORGE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEARCHFORGE_URL', plugin_dir_url( __FILE__ ) );
define( 'SEARCHFORGE_SLUG', 'searchforge' );
define( 'SEARCHFORGE_DB_VERSION', '1.4.0' );

require_once SEARCHFORGE_PATH . 'includes/Autoloader.php';

SearchForge\Autoloader::register();

/**
 * Main plugin class.
 */
final class SearchForge {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->register_hooks();
	}

	private function register_hooks(): void {
		register_activation_hook( SEARCHFORGE_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( SEARCHFORGE_FILE, [ $this, 'deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function activate(): void {
		$installer = new SearchForge\Database\Installer();
		$installer->install();

		// Schedule recurring sync.
		if ( ! wp_next_scheduled( 'searchforge_daily_sync' ) ) {
			wp_schedule_event( time(), 'daily', 'searchforge_daily_sync' );
		}

		// Schedule weekly digest.
		if ( ! wp_next_scheduled( 'searchforge_weekly_digest' ) ) {
			wp_schedule_event( strtotime( 'next monday 08:00' ), 'weekly', 'searchforge_weekly_digest' );
		}

		flush_rewrite_rules();
	}

	public function deactivate(): void {
		wp_clear_scheduled_hook( 'searchforge_daily_sync' );
		wp_clear_scheduled_hook( 'searchforge_weekly_digest' );
		flush_rewrite_rules();
	}

	public function init(): void {
		load_plugin_textdomain( 'searchforge', false, dirname( plugin_basename( SEARCHFORGE_FILE ) ) . '/languages' );

		// Admin.
		if ( is_admin() ) {
			new SearchForge\Admin\Menu();
			new SearchForge\Admin\Settings();
			new SearchForge\Admin\Dashboard();
			new SearchForge\Admin\Assets();
			new SearchForge\Admin\DashboardWidget();
			new SearchForge\Admin\Onboarding();
			new SearchForge\Integrations\GSC\OAuth();
		}

		// WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'searchforge', SearchForge\Cli\Commands::class );
		}

		// REST API.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// llms.txt rewrite.
		new SearchForge\Export\LlmsTxt();

		// Sync hooks.
		add_action( 'searchforge_daily_sync', [ $this, 'run_daily_sync' ] );

		// Weekly digest email.
		add_action( 'searchforge_weekly_digest', [ SearchForge\Notifications\WeeklyDigest::class, 'send' ] );

		// Alert & monitoring system.
		new SearchForge\Alerts\Monitor();

		// Webhook notifications.
		new SearchForge\Notifications\Webhook();

		// Scheduler manager.
		new SearchForge\Scheduler\Manager();

		// Audit log (Pro).
		if ( SearchForge\Admin\Settings::is_pro() ) {
			new SearchForge\Monitoring\AuditLog();
		}

		// AJAX handlers.
		new SearchForge\Admin\Ajax();

		// DB upgrade check.
		$this->maybe_upgrade_db();
	}

	public function register_rest_routes(): void {
		$controller = new SearchForge\Api\RestController();
		$controller->register_routes();
	}

	public function run_daily_sync(): void {
		$settings = SearchForge\Admin\Settings::get_all();

		// GSC sync.
		if ( ! empty( $settings['gsc_access_token'] ) ) {
			$gsc_syncer = new SearchForge\Integrations\GSC\Syncer();
			$result = $gsc_syncer->sync_all();
			if ( is_wp_error( $result ) ) {
				do_action( 'searchforge_sync_failed', 'gsc', $result->get_error_message() );
			} else {
				do_action( 'searchforge_sync_completed', 'gsc', $result );
			}
		}

		// Bing sync (Pro only).
		if ( SearchForge\Admin\Settings::is_pro()
			&& ! empty( $settings['bing_enabled'] )
			&& ! empty( $settings['bing_api_key'] )
		) {
			$bing_syncer = new SearchForge\Integrations\Bing\Syncer();
			$result = $bing_syncer->sync_all();
			if ( is_wp_error( $result ) ) {
				do_action( 'searchforge_sync_failed', 'bing', $result->get_error_message() );
			} else {
				do_action( 'searchforge_sync_completed', 'bing', $result );
			}
		}

		// GA4 sync (Pro only).
		if ( SearchForge\Admin\Settings::is_pro()
			&& ! empty( $settings['ga4_enabled'] )
			&& ! empty( $settings['ga4_property_id'] )
		) {
			$ga4_syncer = new SearchForge\Integrations\GA4\Syncer();
			$result = $ga4_syncer->sync();
			if ( is_wp_error( $result ) ) {
				do_action( 'searchforge_sync_failed', 'ga4', $result->get_error_message() );
			} else {
				do_action( 'searchforge_sync_completed', 'ga4', $result );
			}
		}

		// Keyword Planner enrichment (Pro only).
		if ( SearchForge\Admin\Settings::is_pro()
			&& ! empty( $settings['kwp_enabled'] )
			&& ! empty( $settings['kwp_customer_id'] )
		) {
			$enricher = new SearchForge\Integrations\KeywordPlanner\Enricher();
			$enricher->enrich_keywords();
		}

		// Monitoring checks (Pro).
		if ( SearchForge\Admin\Settings::is_pro() ) {
			SearchForge\Monitoring\SslChecker::check_and_alert();
			SearchForge\Monitoring\QuotaTracker::check_and_alert();

			if ( $settings['broken_links_enabled'] ?? false ) {
				SearchForge\Monitoring\BrokenLinks::scan( 20 );
			}
		}

		// Data retention cleanup.
		SearchForge\Database\Cleanup::run();
	}

	/**
	 * Run DB upgrade if needed.
	 */
	private function maybe_upgrade_db(): void {
		$installed_version = get_option( 'searchforge_db_version', '0' );
		if ( version_compare( $installed_version, SEARCHFORGE_DB_VERSION, '<' ) ) {
			$installer = new SearchForge\Database\Installer();
			$installer->install();
		}
	}
}

SearchForge::instance();
