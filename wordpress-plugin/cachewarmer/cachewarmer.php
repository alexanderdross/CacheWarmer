<?php
/**
 * Plugin Name: CacheWarmer
 * Plugin URI: https://github.com/alexanderdross/cachewarmer
 * Description: Systematically warms CDN edge caches, social media scraper caches (Facebook, LinkedIn, Twitter/X), and submits URLs to search engines (Google, Bing, IndexNow).
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Alexander Dross
 * Author URI: https://github.com/alexanderdross
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cachewarmer
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CACHEWARMER_VERSION', '1.0.0' );
define( 'CACHEWARMER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CACHEWARMER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CACHEWARMER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CACHEWARMER_DB_VERSION', '1.0.0' );

require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer.php';

/**
 * Plugin activation.
 */
function cachewarmer_activate(): void {
    CacheWarmer::get_instance()->activate();
}

/**
 * Plugin deactivation.
 */
function cachewarmer_deactivate(): void {
    CacheWarmer::get_instance()->deactivate();
}

register_activation_hook( __FILE__, 'cachewarmer_activate' );
register_deactivation_hook( __FILE__, 'cachewarmer_deactivate' );

/**
 * Initialize the plugin.
 */
function cachewarmer_init(): void {
    CacheWarmer::get_instance();
}
add_action( 'plugins_loaded', 'cachewarmer_init' );
