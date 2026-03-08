<?php
/**
 * CacheWarmer Comprehensive Test Suite
 *
 * Tests: QA, Unit, UAT, Accessibility, Performance, Security
 *
 * Run: php tests/CacheWarmerTestSuite.php
 */

require_once __DIR__ . '/bootstrap.php';

// Load plugin classes.
require_once CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-sitemap-parser.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-cdn-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-facebook-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-linkedin-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-twitter-warmer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-google-indexer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-bing-indexer.php';
require_once CACHEWARMER_PLUGIN_DIR . 'includes/services/class-cachewarmer-indexnow.php';

class TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private int $total  = 0;
    private string $current_suite = '';
    private array $failures = array();

    public function suite( string $name ): void {
        $this->current_suite = $name;
        echo "\n\033[1;36m━━━ $name ━━━\033[0m\n";
    }

    public function assert( string $description, bool $condition, string $details = '' ): void {
        $this->total++;
        if ( $condition ) {
            $this->passed++;
            echo "  \033[32m✓\033[0m $description\n";
        } else {
            $this->failed++;
            $msg = "  \033[31m✗\033[0m $description";
            if ( $details ) {
                $msg .= " — $details";
            }
            echo "$msg\n";
            $this->failures[] = "[$this->current_suite] $description" . ( $details ? " — $details" : '' );
        }
    }

    public function assertEqual( string $description, $expected, $actual ): void {
        $this->assert(
            $description,
            $expected === $actual,
            "expected " . var_export( $expected, true ) . ", got " . var_export( $actual, true )
        );
    }

    public function assertNotEmpty( string $description, $value ): void {
        $this->assert( $description, ! empty( $value ), 'value is empty' );
    }

    public function assertContains( string $description, string $haystack, string $needle ): void {
        $this->assert(
            $description,
            str_contains( $haystack, $needle ),
            "string does not contain '$needle'"
        );
    }

    public function assertGreaterThan( string $description, $value, $threshold ): void {
        $this->assert(
            $description,
            $value > $threshold,
            "$value is not > $threshold"
        );
    }

    public function summary(): int {
        echo "\n\033[1;35m" . str_repeat( '━', 60 ) . "\033[0m\n";
        echo "\033[1m  Results: $this->passed passed, $this->failed failed, $this->total total\033[0m\n";
        if ( $this->failed > 0 ) {
            echo "\033[31m  FAILURES:\033[0m\n";
            foreach ( $this->failures as $f ) {
                echo "    \033[31m• $f\033[0m\n";
            }
        } else {
            echo "\033[32m  All tests passed!\033[0m\n";
        }
        echo "\033[1;35m" . str_repeat( '━', 60 ) . "\033[0m\n\n";
        return $this->failed > 0 ? 1 : 0;
    }
}

// ══════════════════════════════════════════════
// Test Execution
// ══════════════════════════════════════════════

$t = new TestRunner();

// ──────────────────────────────────────────────
// 1. QA ASSESSMENT
// ──────────────────────────────────────────────
$t->suite( '1. QA Assessment — Code Quality' );

// Check all required files exist.
$required_files = array(
    'cachewarmer.php',
    'uninstall.php',
    'includes/class-cachewarmer.php',
    'includes/class-cachewarmer-database.php',
    'includes/class-cachewarmer-job-manager.php',
    'includes/class-cachewarmer-sitemap-parser.php',
    'includes/class-cachewarmer-rest-api.php',
    'includes/class-cachewarmer-scheduler.php',
    'includes/admin/class-cachewarmer-admin.php',
    'includes/services/class-cachewarmer-cdn-warmer.php',
    'includes/services/class-cachewarmer-facebook-warmer.php',
    'includes/services/class-cachewarmer-linkedin-warmer.php',
    'includes/services/class-cachewarmer-twitter-warmer.php',
    'includes/services/class-cachewarmer-google-indexer.php',
    'includes/services/class-cachewarmer-bing-indexer.php',
    'includes/services/class-cachewarmer-indexnow.php',
    'templates/dashboard.php',
    'templates/sitemaps.php',
    'templates/settings.php',
    'assets/css/admin.css',
    'assets/js/admin.js',
);

foreach ( $required_files as $file ) {
    $t->assert(
        "File exists: $file",
        file_exists( CACHEWARMER_PLUGIN_DIR . $file )
    );
}

// Check PHP syntax validity.
$php_files = array_filter( $required_files, fn( $f ) => str_ends_with( $f, '.php' ) );
foreach ( $php_files as $file ) {
    $path   = CACHEWARMER_PLUGIN_DIR . $file;
    $output = array();
    $result = 0;
    exec( "php -l " . escapeshellarg( $path ) . " 2>&1", $output, $result );
    $t->assert( "Valid PHP syntax: $file", $result === 0, implode( ' ', $output ) );
}

// Check plugin header.
$main_content = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'cachewarmer.php' );
$t->assertContains( 'Plugin header: Plugin Name', $main_content, 'Plugin Name:' );
$t->assertContains( 'Plugin header: Version', $main_content, 'Version:' );
$t->assertContains( 'Plugin header: Text Domain', $main_content, 'Text Domain: cachewarmer' );
$t->assertContains( 'Plugin uses ABSPATH guard', $main_content, "defined( 'ABSPATH' )" );

// Check ABSPATH guard in all PHP files.
foreach ( $php_files as $file ) {
    if ( $file === 'cachewarmer.php' ) continue;
    $content = file_get_contents( CACHEWARMER_PLUGIN_DIR . $file );
    // uninstall.php uses WP_UNINSTALL_PLUGIN guard instead of ABSPATH.
    if ( $file === 'uninstall.php' ) {
        $t->assert(
            "WP_UNINSTALL_PLUGIN guard: $file",
            str_contains( $content, "WP_UNINSTALL_PLUGIN" )
        );
    } else {
        $t->assert(
            "ABSPATH guard: $file",
            str_contains( $content, "defined( 'ABSPATH' )" ) || str_contains( $content, "defined('ABSPATH')" )
        );
    }
}

// Check CSS file is not empty and has expected classes.
$css = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'assets/css/admin.css' );
$t->assertContains( 'CSS has .cachewarmer-wrap', $css, '.cachewarmer-wrap' );
$t->assertContains( 'CSS has .cachewarmer-card', $css, '.cachewarmer-card' );
$t->assertContains( 'CSS has .cachewarmer-badge', $css, '.cachewarmer-badge' );
$t->assertContains( 'CSS has .cachewarmer-progress', $css, '.cachewarmer-progress' );
$t->assertContains( 'CSS has .cachewarmer-modal', $css, '.cachewarmer-modal' );
$t->assertContains( 'CSS has responsive media query', $css, '@media' );

// Check JS file structure.
$js = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'assets/js/admin.js' );
$t->assertContains( 'JS uses IIFE pattern', $js, '(function ($)' );
$t->assertContains( 'JS has warm form handler', $js, '#cw-warm-form' );
$t->assertContains( 'JS has refreshJobsTable', $js, 'refreshJobsTable' );
$t->assertContains( 'JS has escHtml function', $js, 'function escHtml' );
$t->assertContains( 'JS has modal close handler', $js, 'cachewarmer-modal-close' );

// ──────────────────────────────────────────────
// 2. UNIT TESTS
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — Sitemap Parser' );

// Test parsing a standard urlset sitemap.
$parser = new CacheWarmer_Sitemap_Parser();

// Mock HTTP response for sitemap.
$_wp_remote_responses = array();
$_wp_remote_responses['https://example.com/sitemap.xml'] = array(
    'body'     => '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            <url><loc>https://example.com/page1</loc><lastmod>2026-01-01</lastmod></url>
            <url><loc>https://example.com/page2</loc><priority>0.8</priority></url>
            <url><loc>https://example.com/page3</loc><changefreq>daily</changefreq></url>
        </urlset>',
    'response' => array( 'code' => 200 ),
);

$urls = $parser->parse( 'https://example.com/sitemap.xml' );
$t->assertEqual( 'Parses 3 URLs from urlset', 3, count( $urls ) );
$t->assertEqual( 'First URL loc', 'https://example.com/page1', $urls[0]['loc'] );
$t->assertEqual( 'First URL lastmod', '2026-01-01', $urls[0]['lastmod'] ?? null );
$t->assertEqual( 'Second URL priority', '0.8', $urls[1]['priority'] ?? null );
$t->assertEqual( 'Third URL changefreq', 'daily', $urls[2]['changefreq'] ?? null );

// Test sitemapindex parsing.
$_wp_remote_responses['https://example.com/sitemap-index.xml'] = array(
    'body'     => '<?xml version="1.0" encoding="UTF-8"?>
        <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            <sitemap><loc>https://example.com/sitemap.xml</loc></sitemap>
        </sitemapindex>',
    'response' => array( 'code' => 200 ),
);

$urls2 = $parser->parse( 'https://example.com/sitemap-index.xml' );
$t->assertEqual( 'Recursively parses sitemapindex', 3, count( $urls2 ) );

// Test deduplication.
$_wp_remote_responses['https://example.com/dup-sitemap.xml'] = array(
    'body'     => '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            <url><loc>https://example.com/dup</loc></url>
            <url><loc>https://example.com/dup</loc></url>
            <url><loc>https://example.com/unique</loc></url>
        </urlset>',
    'response' => array( 'code' => 200 ),
);

$urls3 = $parser->parse( 'https://example.com/dup-sitemap.xml' );
$t->assertEqual( 'Deduplicates URLs', 2, count( $urls3 ) );

// Test invalid URL handling.
$_wp_remote_responses['https://example.com/bad-sitemap.xml'] = array(
    'body'     => 'not xml',
    'response' => array( 'code' => 200 ),
);

$urls4 = $parser->parse( 'https://example.com/bad-sitemap.xml' );
$t->assertEqual( 'Handles invalid XML gracefully', 0, count( $urls4 ) );

// Test HTTP error.
$urls5 = $parser->parse( 'https://nonexistent.example.com/sitemap.xml' );
$t->assertEqual( 'Handles HTTP error gracefully', 0, count( $urls5 ) );

// ──────────────────────────────────────────────
// Unit Tests — CDN Warmer
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — CDN Warmer' );

update_option( 'cachewarmer_cdn_concurrency', 2 );
update_option( 'cachewarmer_cdn_timeout', 10 );

$_wp_remote_responses['https://example.com/page1'] = array(
    'body'     => '<html><body>Hello</body></html>',
    'response' => array( 'code' => 200 ),
);
$_wp_remote_responses['https://example.com/page2'] = array(
    'body'     => '<html><body>World</body></html>',
    'response' => array( 'code' => 200 ),
);

$cdn_warmer = new CacheWarmer_CDN_Warmer();
$cdn_results = $cdn_warmer->warm(
    array( 'https://example.com/page1', 'https://example.com/page2' ),
    'test-job-1'
);

// Each URL is fetched twice (desktop + mobile).
$t->assertEqual( 'CDN: 4 results for 2 URLs (desktop+mobile)', 4, count( $cdn_results ) );
$t->assertEqual( 'CDN: first result target is "cdn"', 'cdn', $cdn_results[0]['target'] );
$t->assertEqual( 'CDN: first result status', 'success', $cdn_results[0]['status'] );
$t->assertEqual( 'CDN: first result HTTP 200', 200, $cdn_results[0]['http_status'] );
$t->assert( 'CDN: duration >= 0', $cdn_results[0]['duration_ms'] >= 0 );

// Test CDN with failed response.
$_wp_remote_responses['https://example.com/error'] = array(
    'body'     => 'Server Error',
    'response' => array( 'code' => 500 ),
);

$cdn_err = $cdn_warmer->warm( array( 'https://example.com/error' ), 'test-job-err' );
$t->assertEqual( 'CDN: failed status on HTTP 500', 'failed', $cdn_err[0]['status'] );

// Test CDN with WP_Error.
$cdn_wp_err = $cdn_warmer->warm( array( 'https://unreachable.test/page' ), 'test-job-wp-err' );
$t->assertEqual( 'CDN: failed on WP_Error', 'failed', $cdn_wp_err[0]['status'] );
$t->assertNotEmpty( 'CDN: error message on WP_Error', $cdn_wp_err[0]['error'] );

// Callback test.
$callback_results = array();
$cdn_warmer->warm(
    array( 'https://example.com/page1' ),
    'test-job-cb',
    function ( $result ) use ( &$callback_results ) {
        $callback_results[] = $result;
    }
);
$t->assertEqual( 'CDN: callback invoked for each result', 2, count( $callback_results ) );

// ──────────────────────────────────────────────
// Unit Tests — Facebook Warmer
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — Facebook Warmer' );

// Not configured.
update_option( 'cachewarmer_facebook_app_id', '' );
update_option( 'cachewarmer_facebook_app_secret', '' );
$fb = new CacheWarmer_Facebook_Warmer();
$t->assert( 'Facebook: not configured without credentials', ! $fb->is_configured() );

$fb_results = $fb->warm( array( 'https://example.com/page1' ), 'test-fb' );
$t->assertEqual( 'Facebook: skipped when not configured', 'skipped', $fb_results[0]['status'] );

// Configured.
update_option( 'cachewarmer_facebook_app_id', 'test_id' );
update_option( 'cachewarmer_facebook_app_secret', 'test_secret' );
$fb2 = new CacheWarmer_Facebook_Warmer();
$t->assert( 'Facebook: configured with credentials', $fb2->is_configured() );

$_wp_remote_responses['https://graph.facebook.com/v19.0/'] = array(
    'body'     => '{"url":"https://example.com/page1","title":"Test"}',
    'response' => array( 'code' => 200 ),
);

$fb_warm = $fb2->warm( array( 'https://example.com/page1' ), 'test-fb-2' );
$t->assertEqual( 'Facebook: target is "facebook"', 'facebook', $fb_warm[0]['target'] );
$t->assertEqual( 'Facebook: success on 200', 'success', $fb_warm[0]['status'] );

// ──────────────────────────────────────────────
// Unit Tests — LinkedIn Warmer
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — LinkedIn Warmer' );

update_option( 'cachewarmer_linkedin_session_cookie', '' );
update_option( 'cachewarmer_linkedin_delay', 0 );
$li = new CacheWarmer_LinkedIn_Warmer();
$t->assert( 'LinkedIn: not configured without cookie', ! $li->is_configured() );

$li_results = $li->warm( array( 'https://example.com/page1' ), 'test-li' );
$t->assertEqual( 'LinkedIn: skipped when not configured', 'skipped', $li_results[0]['status'] );

update_option( 'cachewarmer_linkedin_session_cookie', 'test_cookie' );
$li2 = new CacheWarmer_LinkedIn_Warmer();
$t->assert( 'LinkedIn: configured with cookie', $li2->is_configured() );

// ──────────────────────────────────────────────
// Unit Tests — Twitter Warmer
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — Twitter Warmer' );

$tw_composer_url = 'https://twitter.com/intent/tweet?url=' . rawurlencode( 'https://example.com/page1' );
$_wp_remote_responses[ $tw_composer_url ] = array(
    'body'     => '<html>Compose Tweet</html>',
    'response' => array( 'code' => 200 ),
);

update_option( 'cachewarmer_twitter_concurrency', 1 );
update_option( 'cachewarmer_twitter_delay', 0 );

$tw = new CacheWarmer_Twitter_Warmer();
$tw_results = $tw->warm( array( 'https://example.com/page1' ), 'test-tw' );
$t->assertEqual( 'Twitter: target is "twitter"', 'twitter', $tw_results[0]['target'] );
$t->assertEqual( 'Twitter: success on 200', 'success', $tw_results[0]['status'] );

// ──────────────────────────────────────────────
// Unit Tests — Google Indexer
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — Google Indexer' );

update_option( 'cachewarmer_google_service_account', '' );
$gi = new CacheWarmer_Google_Indexer();
$t->assert( 'Google: not configured without SA', ! $gi->is_configured() );

$gi_results = $gi->index( array( 'https://example.com/page1' ), 'test-gi' );
$t->assertEqual( 'Google: skipped when not configured', 'skipped', $gi_results[0]['status'] );

// ──────────────────────────────────────────────
// Unit Tests — Bing Indexer
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — Bing Indexer' );

update_option( 'cachewarmer_bing_api_key', '' );
$bi = new CacheWarmer_Bing_Indexer();
$t->assert( 'Bing: not configured without API key', ! $bi->is_configured() );

$bi_results = $bi->index( array( 'https://example.com/page1' ), 'test-bi' );
$t->assertEqual( 'Bing: skipped when not configured', 'skipped', $bi_results[0]['status'] );

update_option( 'cachewarmer_bing_api_key', 'test-key' );
update_option( 'cachewarmer_bing_daily_quota', 5 );
$bi2 = new CacheWarmer_Bing_Indexer();
$t->assert( 'Bing: configured with API key', $bi2->is_configured() );

$_wp_remote_responses['https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch?apikey=test-key'] = array(
    'body'     => '{"d":null}',
    'response' => array( 'code' => 200 ),
);

$bi_warm = $bi2->index( array( 'https://example.com/page1' ), 'test-bi-2' );
$t->assertEqual( 'Bing: target is "bing"', 'bing', $bi_warm[0]['target'] );
$t->assertEqual( 'Bing: success on 200', 'success', $bi_warm[0]['status'] );

// Test quota.
$bi3_urls = array();
for ( $i = 0; $i < 8; $i++ ) {
    $bi3_urls[] = "https://example.com/q$i";
    $_wp_remote_responses["https://example.com/q$i"] = array( 'body' => '', 'response' => array( 'code' => 200 ) );
}
$bi3_results = $bi2->index( $bi3_urls, 'test-bi-quota' );
$skipped_count = count( array_filter( $bi3_results, fn( $r ) => $r['status'] === 'skipped' ) );
$t->assertEqual( 'Bing: skips URLs beyond daily quota (5)', 3, $skipped_count );

// ──────────────────────────────────────────────
// Unit Tests — IndexNow
// ──────────────────────────────────────────────
$t->suite( '2. Unit Tests — IndexNow' );

update_option( 'cachewarmer_indexnow_key', '' );
$inow = new CacheWarmer_IndexNow();
$t->assert( 'IndexNow: not configured without key', ! $inow->is_configured() );

$inow_results = $inow->submit( array( 'https://example.com/page1' ), 'test-inow' );
$t->assertEqual( 'IndexNow: skipped when not configured', 'skipped', $inow_results[0]['status'] );

update_option( 'cachewarmer_indexnow_key', 'test-inow-key' );
$inow2 = new CacheWarmer_IndexNow();
$t->assert( 'IndexNow: configured with key', $inow2->is_configured() );

$_wp_remote_responses['https://api.indexnow.org/indexnow'] = array(
    'body'     => '',
    'response' => array( 'code' => 202 ),
);

$inow_warm = $inow2->submit( array( 'https://example.com/page1' ), 'test-inow-2' );
$t->assertEqual( 'IndexNow: target is "indexnow"', 'indexnow', $inow_warm[0]['target'] );
$t->assertEqual( 'IndexNow: success on 202', 'success', $inow_warm[0]['status'] );

// ──────────────────────────────────────────────
// 3. UAT TESTS (User Acceptance)
// ──────────────────────────────────────────────
$t->suite( '3. UAT Tests — User Acceptance' );

// Test that settings page has all expected sections.
$settings_html = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'templates/settings.php' );
$t->assertContains( 'Settings: CDN section', $settings_html, 'CDN Cache Warming' );
$t->assertContains( 'Settings: Facebook section', $settings_html, 'Facebook Sharing Debugger' );
$t->assertContains( 'Settings: LinkedIn section', $settings_html, 'LinkedIn Post Inspector' );
$t->assertContains( 'Settings: Twitter section', $settings_html, 'Twitter/X Card Validator' );
$t->assertContains( 'Settings: Google section', $settings_html, 'Google Indexing API' );
$t->assertContains( 'Settings: Bing section', $settings_html, 'Bing Webmaster Tools' );
$t->assertContains( 'Settings: IndexNow section', $settings_html, 'IndexNow Protocol' );
$t->assertContains( 'Settings: Scheduler section', $settings_html, 'Scheduled Warming' );
$t->assertContains( 'Settings: Save button', $settings_html, 'submit_button' );

// Test dashboard has key elements.
$dashboard_html = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'templates/dashboard.php' );
$t->assertContains( 'Dashboard: status cards', $dashboard_html, 'cachewarmer-status-cards' );
$t->assertContains( 'Dashboard: warm form', $dashboard_html, 'cw-warm-form' );
$t->assertContains( 'Dashboard: jobs table', $dashboard_html, 'cw-jobs-table' );
$t->assertContains( 'Dashboard: job modal', $dashboard_html, 'cw-job-modal' );
$t->assertContains( 'Dashboard: sitemap URL input', $dashboard_html, 'cw-sitemap-url' );
$t->assertContains( 'Dashboard: target checkboxes', $dashboard_html, 'targets[]' );
$t->assertContains( 'Dashboard: progress bar', $dashboard_html, 'cachewarmer-progress-bar' );

// Test sitemaps page.
$sitemaps_html = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'templates/sitemaps.php' );
$t->assertContains( 'Sitemaps: add form', $sitemaps_html, 'cw-add-sitemap-form' );
$t->assertContains( 'Sitemaps: table', $sitemaps_html, 'cw-sitemaps-table' );
$t->assertContains( 'Sitemaps: warm now button class', $sitemaps_html, 'cw-warm-sitemap' );
$t->assertContains( 'Sitemaps: delete button class', $sitemaps_html, 'cw-delete-sitemap' );

// Test all 7 warming targets are defined in dashboard PHP source.
// The template uses esc_attr($key) dynamically, so we check for the key strings in the PHP array.
$targets_in_dashboard = array( 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow' );
foreach ( $targets_in_dashboard as $target ) {
    $t->assertContains( "Dashboard defines target: $target", $dashboard_html, "'$target'" );
}

// Test admin class has all AJAX handlers.
$admin_content = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'includes/admin/class-cachewarmer-admin.php' );
$ajax_actions = array(
    'cachewarmer_start_warm',
    'cachewarmer_get_jobs',
    'cachewarmer_get_job',
    'cachewarmer_delete_job',
    'cachewarmer_add_sitemap',
    'cachewarmer_delete_sitemap',
    'cachewarmer_warm_sitemap',
    'cachewarmer_get_status',
);
foreach ( $ajax_actions as $action ) {
    $t->assertContains( "Admin has AJAX handler: $action", $admin_content, $action );
}

// ──────────────────────────────────────────────
// 4. ACCESSIBILITY TESTS
// ──────────────────────────────────────────────
$t->suite( '4. Accessibility Tests' );

// Check form labels are associated with inputs.
$all_templates = $dashboard_html . $sitemaps_html . $settings_html;

$t->assertContains( 'A11y: Labels use "for" attribute (sitemap url)', $dashboard_html, 'for="cw-sitemap-url"' );
$t->assertContains( 'A11y: Labels use "for" attribute (new sitemap)', $sitemaps_html, 'for="cw-new-sitemap-url"' );

// Check form inputs have IDs matching labels.
$t->assertContains( 'A11y: Input has matching id (sitemap url)', $dashboard_html, 'id="cw-sitemap-url"' );
$t->assertContains( 'A11y: Input has matching id (new sitemap)', $sitemaps_html, 'id="cw-new-sitemap-url"' );

// Check tables have thead.
$t->assertContains( 'A11y: Jobs table has thead', $dashboard_html, '<thead>' );
$t->assertContains( 'A11y: Sitemaps table has thead', $sitemaps_html, '<thead>' );

// Check tables use th for headers.
$t->assertContains( 'A11y: Jobs table uses th elements', $dashboard_html, '<th' );

// Check settings table uses scope="row" on th.
$t->assertContains( 'A11y: Settings uses scope="row"', $settings_html, 'scope="row"' );

// Check form inputs have required attribute where needed.
$t->assertContains( 'A11y: Sitemap URL input is required', $dashboard_html, 'required' );
$t->assertContains( 'A11y: New sitemap URL is required', $sitemaps_html, 'required' );

// Check modal has close button.
$t->assertContains( 'A11y: Modal has close button', $dashboard_html, 'cachewarmer-modal-close' );

// Check buttons have visible text.
$t->assertContains( 'A11y: Start Warming button has text', $dashboard_html, 'Start Warming' );
$t->assertContains( 'A11y: Details button has text', $dashboard_html, 'Details' );
$t->assertContains( 'A11y: Delete button has text', $dashboard_html, 'Delete' );

// Check form descriptions exist for help text.
$t->assertContains( 'A11y: API key has description', $settings_html, 'class="description"' );

// Check headings hierarchy (h1 -> h2).
$t->assertContains( 'A11y: Dashboard has h1', $dashboard_html, '<h1>' );
$t->assertContains( 'A11y: Dashboard has h2 sections', $dashboard_html, '<h2>' );

// Check that color is not the only indicator (badges use text too).
$t->assertContains( 'A11y: Badge shows text status', $dashboard_html, 'ucfirst' );

// ──────────────────────────────────────────────
// 5. PERFORMANCE TESTS
// ──────────────────────────────────────────────
$t->suite( '5. Performance Tests' );

// Test sitemap parsing speed.
$perf_xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
for ( $i = 0; $i < 1000; $i++ ) {
    $perf_xml .= "<url><loc>https://example.com/page$i</loc></url>";
}
$perf_xml .= '</urlset>';

$_wp_remote_responses['https://example.com/large-sitemap.xml'] = array(
    'body'     => $perf_xml,
    'response' => array( 'code' => 200 ),
);

$start = microtime( true );
$perf_urls = $parser->parse( 'https://example.com/large-sitemap.xml' );
$parse_time = ( microtime( true ) - $start ) * 1000;

$t->assertEqual( 'Perf: parses 1000 URLs correctly', 1000, count( $perf_urls ) );
$t->assert( "Perf: 1000 URLs parsed in < 500ms (took {$parse_time}ms)", $parse_time < 500 );

// Test CDN warmer memory usage.
$mem_before = memory_get_usage( true );
$cdn_warmer2 = new CacheWarmer_CDN_Warmer();
$mem_after = memory_get_usage( true );
$mem_diff = ( $mem_after - $mem_before ) / 1024 / 1024;
$t->assert( "Perf: CDN Warmer uses < 2MB RAM ({$mem_diff}MB)", $mem_diff < 2 );

// Test service instantiation speed.
$start = microtime( true );
for ( $i = 0; $i < 100; $i++ ) {
    new CacheWarmer_CDN_Warmer();
    new CacheWarmer_Facebook_Warmer();
    new CacheWarmer_Twitter_Warmer();
    new CacheWarmer_IndexNow();
}
$inst_time = ( microtime( true ) - $start ) * 1000;
$t->assert( "Perf: 400 service instantiations in < 100ms (took {$inst_time}ms)", $inst_time < 100 );

// Test CSS file size is reasonable.
$css_size = filesize( CACHEWARMER_PLUGIN_DIR . 'assets/css/admin.css' ) / 1024;
$t->assert( "Perf: CSS file < 50KB ({$css_size}KB)", $css_size < 50 );

// Test JS file size is reasonable.
$js_size = filesize( CACHEWARMER_PLUGIN_DIR . 'assets/js/admin.js' ) / 1024;
$t->assert( "Perf: JS file < 50KB ({$js_size}KB)", $js_size < 50 );

// Test total plugin size.
$total_size = 0;
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( CACHEWARMER_PLUGIN_DIR, RecursiveDirectoryIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) {
    if ( $file->isFile() && strpos( $file->getPathname(), '/tests/' ) === false ) {
        $total_size += $file->getSize();
    }
}
$total_kb = $total_size / 1024;
$t->assert( "Perf: Total plugin size < 500KB ({$total_kb}KB)", $total_kb < 500 );

// ──────────────────────────────────────────────
// 6. SECURITY TESTS
// ──────────────────────────────────────────────
$t->suite( '6. Security Tests' );

// Check nonce usage in admin.
$t->assertContains( 'Sec: Admin uses nonce creation', $admin_content, 'wp_create_nonce' );
$t->assertContains( 'Sec: Admin verifies nonce', $admin_content, 'check_ajax_referer' );

// Check capability checks.
$t->assertContains( 'Sec: Admin checks manage_options', $admin_content, 'manage_options' );

// Check input sanitization.
$t->assertContains( 'Sec: sanitize_text_field used', $admin_content, 'sanitize_text_field' );
$t->assertContains( 'Sec: esc_url_raw used', $admin_content, 'esc_url_raw' );
$t->assertContains( 'Sec: wp_unslash used', $admin_content, 'wp_unslash' );

// Check output escaping in templates.
$t->assertContains( 'Sec: Dashboard uses esc_html()', $dashboard_html, 'esc_html(' );
$t->assertContains( 'Sec: Dashboard uses esc_attr()', $dashboard_html, 'esc_attr(' );
$t->assertContains( 'Sec: Settings uses esc_attr()', $settings_html, 'esc_attr(' );
$t->assertContains( 'Sec: Sitemaps uses esc_url()', $sitemaps_html, 'esc_url(' );

// Check REST API auth.
$rest_content = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'includes/class-cachewarmer-rest-api.php' );
$t->assertContains( 'Sec: REST uses permission_callback', $rest_content, 'permission_callback' );
$t->assertContains( 'Sec: REST checks manage_options', $rest_content, 'manage_options' );
$t->assertContains( 'Sec: REST uses hash_equals for token', $rest_content, 'hash_equals' );

// Check URL validation.
$t->assertContains( 'Sec: REST validates URLs', $rest_content, 'FILTER_VALIDATE_URL' );
$t->assertContains( 'Sec: Admin validates URLs', $admin_content, 'FILTER_VALIDATE_URL' );

// Check sensitive fields use password type.
$t->assertContains( 'Sec: API key uses password input', $settings_html, 'type="password" id="cachewarmer_api_key"' );
$t->assertContains( 'Sec: FB secret uses password input', $settings_html, 'type="password" id="cachewarmer_facebook_app_secret"' );
$t->assertContains( 'Sec: Bing key uses password input', $settings_html, 'type="password" id="cachewarmer_bing_api_key"' );
$t->assertContains( 'Sec: LinkedIn cookie uses password input', $settings_html, 'type="password" id="cachewarmer_linkedin_session_cookie"' );

// Check uninstall cleans up.
$uninstall = file_get_contents( CACHEWARMER_PLUGIN_DIR . 'uninstall.php' );
$t->assertContains( 'Sec: Uninstall checks WP_UNINSTALL_PLUGIN', $uninstall, 'WP_UNINSTALL_PLUGIN' );
$t->assertContains( 'Sec: Uninstall drops tables', $uninstall, 'DROP TABLE' );
$t->assertContains( 'Sec: Uninstall deletes options', $uninstall, 'delete_option' );
$t->assertContains( 'Sec: Uninstall clears cron', $uninstall, 'wp_clear_scheduled_hook' );

// Check no direct file access in PHP files.
$direct_access_files = array(
    'includes/class-cachewarmer.php',
    'includes/class-cachewarmer-database.php',
    'includes/class-cachewarmer-job-manager.php',
    'includes/class-cachewarmer-rest-api.php',
    'includes/admin/class-cachewarmer-admin.php',
    'templates/dashboard.php',
    'templates/settings.php',
    'templates/sitemaps.php',
);
foreach ( $direct_access_files as $file ) {
    $content = file_get_contents( CACHEWARMER_PLUGIN_DIR . $file );
    $t->assert(
        "Sec: $file blocks direct access",
        str_contains( $content, "defined( 'ABSPATH' )" ) || str_contains( $content, "defined('ABSPATH')" )
    );
}

// Check JS XSS protection.
$t->assertContains( 'Sec: JS escapes HTML output', $js, 'escHtml' );
$t->assertContains( 'Sec: JS escapes attributes', $js, 'escAttr' );

// Check register_setting has sanitize callback.
$t->assertContains( 'Sec: Settings registered with sanitize_callback', $admin_content, 'sanitize_callback' );

// ──────────────────────────────────────────────
// SUMMARY
// ──────────────────────────────────────────────
exit( $t->summary() );
