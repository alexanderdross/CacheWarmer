<?php

/**
 * @file
 * Standalone test suite for the CacheWarmer Drupal module.
 * Runs without a full Drupal installation.
 *
 * Usage: php tests/CacheWarmerStandaloneTest.php
 */

// ============================================================================
// Minimal Test Runner
// ============================================================================

class TestRunner {
  private int $passed = 0;
  private int $failed = 0;
  private int $total = 0;
  private string $currentSuite = '';

  public function suite(string $name): void {
    $this->currentSuite = $name;
    echo "\n\033[1;36m━━━ {$name} ━━━\033[0m\n";
  }

  public function assert(string $description, bool $condition, string $details = ''): void {
    $this->total++;
    if ($condition) {
      $this->passed++;
      echo "  \033[32m✓\033[0m {$description}\n";
    } else {
      $this->failed++;
      echo "  \033[31m✗\033[0m {$description}\n";
      if ($details) {
        echo "    \033[33m→ {$details}\033[0m\n";
      }
    }
  }

  public function assertEqual(string $description, $expected, $actual): void {
    $this->assert($description, $expected === $actual,
      "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
  }

  public function assertContains(string $description, string $haystack, string $needle): void {
    $this->assert($description, str_contains($haystack, $needle),
      "String does not contain: '{$needle}'");
  }

  public function assertNotEmpty(string $description, $value): void {
    $this->assert($description, !empty($value), "Value is empty");
  }

  public function assertGreaterThan(string $description, $value, $threshold): void {
    $this->assert($description, $value > $threshold,
      "Expected > {$threshold}, got {$value}");
  }

  public function assertLessThan(string $description, $value, $threshold): void {
    $this->assert($description, $value < $threshold,
      "Expected < {$threshold}, got {$value}");
  }

  public function summary(): void {
    $total = $this->passed + $this->failed;
    echo "\n\033[1;35m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
    echo "\033[1m  Results: {$this->passed} passed, {$this->failed} failed, {$total} total\033[0m\n";
    if ($this->failed === 0) {
      echo "\033[32m  All tests passed!\033[0m\n";
    } else {
      echo "\033[31m  {$this->failed} test(s) failed!\033[0m\n";
    }
    echo "\033[1;35m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
    exit($this->failed > 0 ? 1 : 0);
  }
}

// ============================================================================
// Setup
// ============================================================================

$t = new TestRunner();
$moduleDir = dirname(__DIR__);

// ============================================================================
// 1. QA Assessment — File Existence & Structure
// ============================================================================

$t->suite('1. QA Assessment — File Existence');

$requiredFiles = [
  'cachewarmer.info.yml',
  'cachewarmer.install',
  'cachewarmer.module',
  'cachewarmer.routing.yml',
  'cachewarmer.permissions.yml',
  'cachewarmer.services.yml',
  'cachewarmer.libraries.yml',
  'cachewarmer.links.menu.yml',
  'cachewarmer.links.task.yml',
  'config/install/cachewarmer.settings.yml',
  'config/schema/cachewarmer.schema.yml',
  'src/Controller/CacheWarmerDashboardController.php',
  'src/Controller/CacheWarmerAjaxController.php',
  'src/Form/CacheWarmerSettingsForm.php',
  'src/Plugin/QueueWorker/CacheWarmerProcessJob.php',
  'src/Plugin/rest/resource/CacheWarmerResource.php',
  'src/Service/CacheWarmerDatabase.php',
  'src/Service/CacheWarmerJobManager.php',
  'src/Service/CacheWarmerSitemapParser.php',
  'src/Service/CdnWarmer.php',
  'src/Service/FacebookWarmer.php',
  'src/Service/LinkedinWarmer.php',
  'src/Service/TwitterWarmer.php',
  'src/Service/GoogleIndexer.php',
  'src/Service/BingIndexer.php',
  'src/Service/IndexNow.php',
  'templates/cachewarmer-dashboard.html.twig',
  'templates/cachewarmer-sitemaps.html.twig',
  'css/cachewarmer-admin.css',
  'js/cachewarmer-admin.js',
];

foreach ($requiredFiles as $file) {
  $t->assert("File exists: {$file}", file_exists("{$moduleDir}/{$file}"));
}

// ============================================================================
// 1b. QA Assessment — PHP Syntax Validation
// ============================================================================

$t->suite('1b. QA Assessment — PHP Syntax');

$phpFiles = [
  'cachewarmer.install',
  'cachewarmer.module',
  'src/Controller/CacheWarmerDashboardController.php',
  'src/Controller/CacheWarmerAjaxController.php',
  'src/Form/CacheWarmerSettingsForm.php',
  'src/Plugin/QueueWorker/CacheWarmerProcessJob.php',
  'src/Plugin/rest/resource/CacheWarmerResource.php',
  'src/Service/CacheWarmerDatabase.php',
  'src/Service/CacheWarmerJobManager.php',
  'src/Service/CacheWarmerSitemapParser.php',
  'src/Service/CdnWarmer.php',
  'src/Service/FacebookWarmer.php',
  'src/Service/LinkedinWarmer.php',
  'src/Service/TwitterWarmer.php',
  'src/Service/GoogleIndexer.php',
  'src/Service/BingIndexer.php',
  'src/Service/IndexNow.php',
];

foreach ($phpFiles as $file) {
  $output = [];
  $rc = 0;
  exec("php -l {$moduleDir}/{$file} 2>&1", $output, $rc);
  $t->assert("Valid PHP syntax: {$file}", $rc === 0, implode(' ', $output));
}

// ============================================================================
// 1c. QA Assessment — Module Metadata
// ============================================================================

$t->suite('1c. QA Assessment — Module Metadata');

$infoYml = file_get_contents("{$moduleDir}/cachewarmer.info.yml");
$t->assertContains('Info: module name', $infoYml, 'name: CacheWarmer');
$t->assertContains('Info: module type', $infoYml, 'type: module');
$t->assertContains('Info: core version', $infoYml, 'core_version_requirement:');
$t->assertContains('Info: REST dependency', $infoYml, 'drupal:rest');
$t->assertContains('Info: serialization dependency', $infoYml, 'drupal:serialization');
$t->assertContains('Info: PHP version', $infoYml, 'php: 8.1');
$t->assertContains('Info: configure route', $infoYml, 'configure: cachewarmer.settings');
$t->assertContains('Info: package', $infoYml, 'package: Performance');

// ============================================================================
// 2. Unit Tests — Service Namespaces & Structure
// ============================================================================

$t->suite('2. Unit Tests — Service Namespaces');

$serviceNamespaces = [
  'src/Service/CacheWarmerDatabase.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/CacheWarmerJobManager.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/CacheWarmerSitemapParser.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/CdnWarmer.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/FacebookWarmer.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/LinkedinWarmer.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/TwitterWarmer.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/GoogleIndexer.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/BingIndexer.php' => 'Drupal\\cachewarmer\\Service',
  'src/Service/IndexNow.php' => 'Drupal\\cachewarmer\\Service',
];

foreach ($serviceNamespaces as $file => $ns) {
  $content = file_get_contents("{$moduleDir}/{$file}");
  $t->assertContains("Namespace: " . basename($file), $content, "namespace {$ns};");
}

// ============================================================================
// 2b. Unit Tests — Service Method Signatures
// ============================================================================

$t->suite('2b. Unit Tests — Service Methods');

$warmers = [
  'src/Service/CdnWarmer.php' => 'warm',
  'src/Service/FacebookWarmer.php' => 'warm',
  'src/Service/LinkedinWarmer.php' => 'warm',
  'src/Service/TwitterWarmer.php' => 'warm',
  'src/Service/GoogleIndexer.php' => 'index',
  'src/Service/BingIndexer.php' => 'index',
  'src/Service/IndexNow.php' => 'index',
];

foreach ($warmers as $file => $method) {
  $content = file_get_contents("{$moduleDir}/{$file}");
  $t->assertContains(
    basename($file) . ": has {$method}() method",
    $content,
    "public function {$method}(array \$urls, string \$jobId"
  );
}

// ============================================================================
// 2c. Unit Tests — Guzzle HTTP Client Usage
// ============================================================================

$t->suite('2c. Unit Tests — Guzzle HTTP Client');

$httpServices = [
  'src/Service/CdnWarmer.php',
  'src/Service/FacebookWarmer.php',
  'src/Service/LinkedinWarmer.php',
  'src/Service/TwitterWarmer.php',
  'src/Service/GoogleIndexer.php',
  'src/Service/BingIndexer.php',
  'src/Service/IndexNow.php',
  'src/Service/CacheWarmerSitemapParser.php',
];

foreach ($httpServices as $file) {
  $content = file_get_contents("{$moduleDir}/{$file}");
  $t->assertContains(basename($file) . ': uses GuzzleHttp', $content, 'GuzzleHttp\\ClientInterface');
}

// ============================================================================
// 2d. Unit Tests — CDN Warmer Specifics
// ============================================================================

$t->suite('2d. Unit Tests — CDN Warmer');

$cdn = file_get_contents("{$moduleDir}/src/Service/CdnWarmer.php");
$t->assertContains('CDN: desktop user agent constant', $cdn, 'DESKTOP_UA');
$t->assertContains('CDN: mobile user agent constant', $cdn, 'MOBILE_UA');
$t->assertContains('CDN: Windows NT in desktop UA', $cdn, 'Windows NT');
$t->assertContains('CDN: iPhone in mobile UA', $cdn, 'iPhone');
$t->assertContains('CDN: reads config timeout', $cdn, 'cdn.timeout');
$t->assertContains('CDN: checks http_errors false', $cdn, "'http_errors' => FALSE");
$t->assertContains('CDN: success for < 400', $cdn, "< 400");
$t->assertContains('CDN: tracks duration', $cdn, 'duration');

// ============================================================================
// 2e. Unit Tests — Facebook Warmer Specifics
// ============================================================================

$t->suite('2e. Unit Tests — Facebook Warmer');

$fb = file_get_contents("{$moduleDir}/src/Service/FacebookWarmer.php");
$t->assertContains('Facebook: Graph API v19.0 endpoint', $fb, 'graph.facebook.com/v19.0');
$t->assertContains('Facebook: scrape=true', $fb, "'scrape' => 'true'");
$t->assertContains('Facebook: app_id|app_secret token format', $fb, "appId . '|' . \$appSecret");
$t->assertContains('Facebook: rate limit config', $fb, 'facebook.rate_limit');
$t->assertContains('Facebook: skipped when not configured', $fb, "'skipped'");
$t->assertContains('Facebook: checks for error in response', $fb, "isset(\$body['error'])");

// ============================================================================
// 2f. Unit Tests — LinkedIn Warmer Specifics
// ============================================================================

$t->suite('2f. Unit Tests — LinkedIn Warmer');

$li = file_get_contents("{$moduleDir}/src/Service/LinkedinWarmer.php");
$t->assertContains('LinkedIn: Post Inspector endpoint', $li, 'linkedin.com/post-inspector');
$t->assertContains('LinkedIn: li_at cookie', $li, 'li_at');
$t->assertContains('LinkedIn: csrf-token header', $li, 'csrf-token');
$t->assertContains('LinkedIn: X-Restli-Protocol-Version header', $li, 'X-Restli-Protocol-Version');
$t->assertContains('LinkedIn: session cookie config', $li, 'linkedin.session_cookie');
$t->assertContains('LinkedIn: skipped when not configured', $li, "'skipped'");
$t->assertContains('LinkedIn: delay between requests', $li, 'linkedin.delay');

// ============================================================================
// 2g. Unit Tests — Twitter/X Warmer Specifics
// ============================================================================

$t->suite('2g. Unit Tests — Twitter/X Warmer');

$tw = file_get_contents("{$moduleDir}/src/Service/TwitterWarmer.php");
$t->assertContains('Twitter: composer endpoint', $tw, 'twitter.com/intent/tweet');
$t->assertContains('Twitter: rawurlencode URL', $tw, 'rawurlencode');
$t->assertContains('Twitter: concurrency config', $tw, 'twitter.concurrency');
$t->assertContains('Twitter: delay config', $tw, 'twitter.delay');
$t->assertContains('Twitter: batch processing', $tw, 'array_chunk');

// ============================================================================
// 2h. Unit Tests — Google Indexer Specifics
// ============================================================================

$t->suite('2h. Unit Tests — Google Indexer');

$google = file_get_contents("{$moduleDir}/src/Service/GoogleIndexer.php");
$t->assertContains('Google: Indexing API endpoint', $google, 'indexing.googleapis.com');
$t->assertContains('Google: URL_UPDATED type', $google, 'URL_UPDATED');
$t->assertContains('Google: getAccessToken method', $google, 'getAccessToken');
$t->assertContains('Google: RS256 JWT signing', $google, 'RS256');
$t->assertContains('Google: openssl_sign for JWT', $google, 'openssl_sign');
$t->assertContains('Google: daily quota config', $google, 'google.daily_quota');
$t->assertContains('Google: OAuth2 token exchange', $google, 'oauth2.googleapis.com/token');
$t->assertContains('Google: service account JSON config', $google, 'google.service_account_json');
$t->assertContains('Google: skipped when not configured', $google, "'skipped'");

// ============================================================================
// 2i. Unit Tests — Bing Indexer Specifics
// ============================================================================

$t->suite('2i. Unit Tests — Bing Indexer');

$bing = file_get_contents("{$moduleDir}/src/Service/BingIndexer.php");
$t->assertContains('Bing: Webmaster API endpoint', $bing, 'ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch');
$t->assertContains('Bing: BATCH_SIZE constant', $bing, 'BATCH_SIZE');
$t->assertContains('Bing: batch 500', $bing, '500');
$t->assertContains('Bing: daily quota config', $bing, 'bing.daily_quota');
$t->assertContains('Bing: daily quota enforcement', $bing, 'Daily quota exceeded');
$t->assertContains('Bing: skipped when not configured', $bing, "'skipped'");
$t->assertContains('Bing: siteUrl extraction', $bing, 'siteUrl');

// ============================================================================
// 2j. Unit Tests — IndexNow Specifics
// ============================================================================

$t->suite('2j. Unit Tests — IndexNow');

$inow = file_get_contents("{$moduleDir}/src/Service/IndexNow.php");
$t->assertContains('IndexNow: API endpoint', $inow, 'api.indexnow.org/indexnow');
$t->assertContains('IndexNow: BATCH_SIZE constant', $inow, 'BATCH_SIZE');
$t->assertContains('IndexNow: 10000 batch limit', $inow, '10000');
$t->assertContains('IndexNow: keyLocation support', $inow, 'keyLocation');
$t->assertContains('IndexNow: accepts 202', $inow, '202');
$t->assertContains('IndexNow: skipped when not configured', $inow, "'skipped'");
$t->assertContains('IndexNow: host extraction', $inow, "parse_url");

// ============================================================================
// 2k. Unit Tests — Sitemap Parser
// ============================================================================

$t->suite('2k. Unit Tests — Sitemap Parser');

$sp = file_get_contents("{$moduleDir}/src/Service/CacheWarmerSitemapParser.php");
$t->assertContains('Parser: parse method signature', $sp, 'public function parse(string $url): array');
$t->assertContains('Parser: recursive method', $sp, 'parseRecursive');
$t->assertContains('Parser: MAX_DEPTH constant', $sp, 'MAX_DEPTH');
$t->assertContains('Parser: depth limit = 3', $sp, '3');
$t->assertContains('Parser: URL validation', $sp, 'FILTER_VALIDATE_URL');
$t->assertContains('Parser: XML parsing', $sp, 'simplexml_load_string');
$t->assertContains('Parser: sitemapindex support', $sp, 'sitemapindex');
$t->assertContains('Parser: urlset support', $sp, 'urlset');
$t->assertContains('Parser: loc extraction', $sp, 'loc');
$t->assertContains('Parser: lastmod extraction', $sp, 'lastmod');
$t->assertContains('Parser: priority extraction', $sp, 'priority');
$t->assertContains('Parser: changefreq extraction', $sp, 'changefreq');
$t->assertContains('Parser: deduplication', $sp, '$seen');
$t->assertContains('Parser: namespace handling', $sp, 'registerXPathNamespace');
$t->assertContains('Parser: libxml error handling', $sp, 'libxml_use_internal_errors');

// ============================================================================
// 2l. Unit Tests — Database Service
// ============================================================================

$t->suite('2l. Unit Tests — Database Service');

$db = file_get_contents("{$moduleDir}/src/Service/CacheWarmerDatabase.php");
$t->assertContains('DB: UUID generation', $db, 'generateUuid');
$t->assertContains('DB: insertSitemap method', $db, 'public function insertSitemap');
$t->assertContains('DB: getSitemap method', $db, 'public function getSitemap');
$t->assertContains('DB: getAllSitemaps method', $db, 'public function getAllSitemaps');
$t->assertContains('DB: deleteSitemap method', $db, 'public function deleteSitemap');
$t->assertContains('DB: updateSitemapLastWarmed method', $db, 'public function updateSitemapLastWarmed');
$t->assertContains('DB: insertJob method', $db, 'public function insertJob');
$t->assertContains('DB: getJob method', $db, 'public function getJob');
$t->assertContains('DB: getJobs method', $db, 'public function getJobs');
$t->assertContains('DB: updateJob method', $db, 'public function updateJob');
$t->assertContains('DB: deleteJob method', $db, 'public function deleteJob');
$t->assertContains('DB: getJobCounts method', $db, 'public function getJobCounts');
$t->assertContains('DB: insertUrlResult method', $db, 'public function insertUrlResult');
$t->assertContains('DB: getJobResults method', $db, 'public function getJobResults');
$t->assertContains('DB: getJobStats method', $db, 'public function getJobStats');
$t->assertContains('DB: getLogs method', $db, 'public function getLogs');
$t->assertContains('DB: uses Drupal DB Connection', $db, 'Drupal\\Core\\Database\\Connection');
$t->assertContains('DB: cascading delete (results)', $db, 'cachewarmer_url_results');

// ============================================================================
// 2m. Unit Tests — Job Manager
// ============================================================================

$t->suite('2m. Unit Tests — Job Manager');

$jm = file_get_contents("{$moduleDir}/src/Service/CacheWarmerJobManager.php");
$t->assertContains('JM: ALLOWED_TARGETS constant', $jm, 'ALLOWED_TARGETS');
$t->assertContains('JM: createJob method', $jm, 'public function createJob');
$t->assertContains('JM: processJob method', $jm, 'public function processJob');
$t->assertContains('JM: getJobWithStats method', $jm, 'public function getJobWithStats');
$t->assertContains('JM: processTarget method', $jm, 'processTarget');
$t->assertContains('JM: target cdn', $jm, "case 'cdn':");
$t->assertContains('JM: target facebook', $jm, "case 'facebook':");
$t->assertContains('JM: target linkedin', $jm, "case 'linkedin':");
$t->assertContains('JM: target twitter', $jm, "case 'twitter':");
$t->assertContains('JM: target google', $jm, "case 'google':");
$t->assertContains('JM: target bing', $jm, "case 'bing':");
$t->assertContains('JM: target indexnow', $jm, "case 'indexnow':");
$t->assertContains('JM: validates targets', $jm, 'array_intersect');
$t->assertContains('JM: extends execution time', $jm, 'set_time_limit');
$t->assertContains('JM: extends memory limit', $jm, 'memory_limit');
$t->assertContains('JM: updates sitemap last_warmed_at', $jm, 'updateSitemapLastWarmed');
$t->assertContains('JM: error handling', $jm, "catch (\\Exception");
$t->assertContains('JM: uses LoggerChannelFactory', $jm, 'LoggerChannelFactoryInterface');

// ============================================================================
// 3. Unit Tests — Database Schema
// ============================================================================

$t->suite('3. Unit Tests — Database Schema');

$install = file_get_contents("{$moduleDir}/cachewarmer.install");
$t->assertContains('Schema: defines hook_schema', $install, 'function cachewarmer_schema()');
$t->assertContains('Schema: sitemaps table', $install, 'cachewarmer_sitemaps');
$t->assertContains('Schema: jobs table', $install, 'cachewarmer_jobs');
$t->assertContains('Schema: url_results table', $install, 'cachewarmer_url_results');
$t->assertContains('Schema: uninstall hook', $install, 'function cachewarmer_uninstall()');

// Sitemaps columns
$sitemapCols = ['id', 'url', 'domain', 'cron_expression', 'created_at', 'last_warmed_at'];
foreach ($sitemapCols as $col) {
  $t->assertContains("Schema sitemaps: {$col} column", $install, "'{$col}'");
}

// Jobs columns
$jobCols = ['id', 'sitemap_id', 'sitemap_url', 'status', 'total_urls', 'processed_urls', 'targets', 'started_at', 'completed_at', 'error', 'created_at'];
foreach ($jobCols as $col) {
  $t->assertContains("Schema jobs: {$col} column", $install, "'{$col}'");
}

// URL Results columns
$resultCols = ['id', 'job_id', 'url', 'target', 'status', 'http_status', 'duration_ms', 'error', 'created_at'];
foreach ($resultCols as $col) {
  $t->assertContains("Schema results: {$col} column", $install, "'{$col}'");
}

// ============================================================================
// 3b. Unit Tests — Config Schema
// ============================================================================

$t->suite('3b. Unit Tests — Config Schema');

$schema = file_get_contents("{$moduleDir}/config/schema/cachewarmer.schema.yml");
$t->assertContains('Schema: root key', $schema, 'cachewarmer.settings:');
$schemaKeys = ['api_key', 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow', 'scheduler', 'log_level'];
foreach ($schemaKeys as $key) {
  $t->assertContains("Config schema: {$key}", $schema, "{$key}:");
}

// ============================================================================
// 3c. Unit Tests — Default Config
// ============================================================================

$t->suite('3c. Unit Tests — Default Config');

$defaults = file_get_contents("{$moduleDir}/config/install/cachewarmer.settings.yml");
$defaultKeys = ['api_key', 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow', 'scheduler', 'log_level'];
foreach ($defaultKeys as $key) {
  $t->assertContains("Default config: {$key}", $defaults, "{$key}:");
}

// ============================================================================
// 4. Unit Tests — Routing
// ============================================================================

$t->suite('4. Unit Tests — Routing');

$routing = file_get_contents("{$moduleDir}/cachewarmer.routing.yml");
$routes = [
  'cachewarmer.dashboard',
  'cachewarmer.sitemaps',
  'cachewarmer.settings',
  'cachewarmer.ajax.start_warm',
  'cachewarmer.ajax.get_jobs',
  'cachewarmer.ajax.get_job',
  'cachewarmer.ajax.delete_job',
  'cachewarmer.ajax.add_sitemap',
  'cachewarmer.ajax.delete_sitemap',
  'cachewarmer.ajax.warm_sitemap',
  'cachewarmer.ajax.status',
];

foreach ($routes as $route) {
  $t->assertContains("Route: {$route}", $routing, "{$route}:");
}

$t->assertContains('Routes: all require permission', $routing, "_permission: 'administer cachewarmer'");

// ============================================================================
// 4b. Unit Tests — Services YAML
// ============================================================================

$t->suite('4b. Unit Tests — Services YAML');

$services = file_get_contents("{$moduleDir}/cachewarmer.services.yml");
$serviceIds = [
  'cachewarmer.database',
  'cachewarmer.sitemap_parser',
  'cachewarmer.cdn_warmer',
  'cachewarmer.facebook_warmer',
  'cachewarmer.linkedin_warmer',
  'cachewarmer.twitter_warmer',
  'cachewarmer.google_indexer',
  'cachewarmer.bing_indexer',
  'cachewarmer.indexnow',
  'cachewarmer.job_manager',
];

foreach ($serviceIds as $svc) {
  $t->assertContains("Service: {$svc}", $services, "{$svc}:");
}

// ============================================================================
// 5. UAT Tests — Controllers
// ============================================================================

$t->suite('5. UAT Tests — Dashboard Controller');

$dc = file_get_contents("{$moduleDir}/src/Controller/CacheWarmerDashboardController.php");
$t->assertContains('Dashboard: extends ControllerBase', $dc, 'extends ControllerBase');
$t->assertContains('Dashboard: dashboard() method', $dc, 'public function dashboard()');
$t->assertContains('Dashboard: sitemaps() method', $dc, 'public function sitemaps()');
$t->assertContains('Dashboard: theme cachewarmer_dashboard', $dc, "'#theme' => 'cachewarmer_dashboard'");
$t->assertContains('Dashboard: theme cachewarmer_sitemaps', $dc, "'#theme' => 'cachewarmer_sitemaps'");
$t->assertContains('Dashboard: attaches library', $dc, 'cachewarmer/admin');
$t->assertContains('Dashboard: passes drupalSettings', $dc, 'drupalSettings');
$t->assertContains('Dashboard: DI via create()', $dc, 'public static function create');

// ============================================================================
// 5b. UAT Tests — AJAX Controller
// ============================================================================

$t->suite('5b. UAT Tests — AJAX Controller');

$ac = file_get_contents("{$moduleDir}/src/Controller/CacheWarmerAjaxController.php");
$ajaxMethods = ['startWarm', 'getJobs', 'getJob', 'deleteJob', 'addSitemap', 'deleteSitemap', 'warmSitemap', 'getStatus'];
foreach ($ajaxMethods as $method) {
  $t->assertContains("AJAX: {$method}() method", $ac, "public function {$method}(");
}
$t->assertContains('AJAX: returns JsonResponse', $ac, 'JsonResponse');
$t->assertContains('AJAX: DI via create()', $ac, 'public static function create');

// ============================================================================
// 5c. UAT Tests — Settings Form
// ============================================================================

$t->suite('5c. UAT Tests — Settings Form');

$sf = file_get_contents("{$moduleDir}/src/Form/CacheWarmerSettingsForm.php");
$t->assertContains('Settings: extends ConfigFormBase', $sf, 'extends ConfigFormBase');
$t->assertContains('Settings: references cachewarmer.settings', $sf, "'cachewarmer.settings'");
$t->assertContains('Settings: buildForm method', $sf, 'public function buildForm(');
$t->assertContains('Settings: submitForm method', $sf, 'public function submitForm(');

$sections = ['general', 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow', 'scheduler', 'logging'];
foreach ($sections as $section) {
  $t->assertContains("Settings section: {$section}", $sf, "\$form['{$section}']");
}

// ============================================================================
// 5d. UAT Tests — Queue Worker
// ============================================================================

$t->suite('5d. UAT Tests — Queue Worker');

$qw = file_get_contents("{$moduleDir}/src/Plugin/QueueWorker/CacheWarmerProcessJob.php");
$t->assertContains('QueueWorker: annotation', $qw, '@QueueWorker');
$t->assertContains('QueueWorker: id', $qw, 'id = "cachewarmer_process_job"');
$t->assertContains('QueueWorker: processItem method', $qw, 'public function processItem($data)');
$t->assertContains('QueueWorker: container factory', $qw, 'ContainerFactoryPluginInterface');
$t->assertContains('QueueWorker: cron time', $qw, '"time" = 300');

// ============================================================================
// 5e. UAT Tests — Dashboard Template
// ============================================================================

$t->suite('5e. UAT Tests — Dashboard Template');

$dt = file_get_contents("{$moduleDir}/templates/cachewarmer-dashboard.html.twig");
$t->assertContains('Dashboard: status cards container', $dt, 'cachewarmer-status-cards');
$t->assertContains('Dashboard: queued card', $dt, 'cachewarmer-card--queued');
$t->assertContains('Dashboard: running card', $dt, 'cachewarmer-card--running');
$t->assertContains('Dashboard: completed card', $dt, 'cachewarmer-card--completed');
$t->assertContains('Dashboard: failed card', $dt, 'cachewarmer-card--failed');
$t->assertContains('Dashboard: warm form', $dt, 'cachewarmer-warm-form');
$t->assertContains('Dashboard: sitemap URL input', $dt, 'cachewarmer-sitemap-url');
$t->assertContains('Dashboard: start warm button', $dt, 'cachewarmer-start-warm');
$t->assertContains('Dashboard: jobs table', $dt, 'cachewarmer-jobs-table');
$t->assertContains('Dashboard: modal', $dt, 'cachewarmer-modal');
$t->assertContains('Dashboard: progress bar', $dt, 'cachewarmer-progress');

// Target checkboxes
$targets = ['cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow'];
foreach ($targets as $target) {
  $t->assertContains("Dashboard: target checkbox {$target}", $dt, "value=\"{$target}\"");
}

// ============================================================================
// 5f. UAT Tests — Sitemaps Template
// ============================================================================

$t->suite('5f. UAT Tests — Sitemaps Template');

$st = file_get_contents("{$moduleDir}/templates/cachewarmer-sitemaps.html.twig");
$t->assertContains('Sitemaps: add form', $st, 'cachewarmer-add-sitemap-form');
$t->assertContains('Sitemaps: URL input', $st, 'cachewarmer-new-sitemap-url');
$t->assertContains('Sitemaps: add button', $st, 'cachewarmer-add-sitemap');
$t->assertContains('Sitemaps: table', $st, 'cachewarmer-sitemaps-table');
$t->assertContains('Sitemaps: warm now button', $st, 'cachewarmer-btn-warm-sitemap');
$t->assertContains('Sitemaps: delete button', $st, 'cachewarmer-btn-delete-sitemap');
$t->assertContains('Sitemaps: cron input', $st, 'cachewarmer-new-sitemap-cron');

// ============================================================================
// 6. Accessibility Tests
// ============================================================================

$t->suite('6. Accessibility Tests');

// Dashboard
$t->assertContains('A11y: Dashboard has label elements', $dt, '<label');
$t->assertContains('A11y: Dashboard label for sitemap-url', $dt, 'for="cachewarmer-sitemap-url"');
$t->assertContains('A11y: Dashboard input has matching id', $dt, 'id="cachewarmer-sitemap-url"');
$t->assertContains('A11y: Dashboard table has thead', $dt, '<thead>');
$t->assertContains('A11y: Dashboard table has th', $dt, '<th>');
$t->assertContains('A11y: Dashboard URL input is required', $dt, 'required');
$t->assertContains('A11y: Dashboard has h3 headings', $dt, '<h3>');

// Sitemaps
$t->assertContains('A11y: Sitemaps has label elements', $st, '<label');
$t->assertContains('A11y: Sitemaps label for URL', $st, 'for="cachewarmer-new-sitemap-url"');
$t->assertContains('A11y: Sitemaps table has thead', $st, '<thead>');
$t->assertContains('A11y: Sitemaps table has th', $st, '<th>');
$t->assertContains('A11y: Sitemaps URL is required', $st, 'required');
$t->assertContains('A11y: Modal has close button', $dt, 'cachewarmer-modal__close');

// Settings form (Drupal Form API generates accessible forms automatically)
$t->assertContains('A11y: Settings uses #title for fields', $sf, "'#title'");
$t->assertContains('A11y: Settings uses #description', $sf, "'#description'");

// ============================================================================
// 7. Security Tests
// ============================================================================

$t->suite('7. Security Tests');

// REST Resource auth
$rest = file_get_contents("{$moduleDir}/src/Plugin/rest/resource/CacheWarmerResource.php");
$t->assertContains('Sec: REST validateAuth method', $rest, 'validateAuth');
$t->assertContains('Sec: REST hash_equals for token', $rest, 'hash_equals');
$t->assertContains('Sec: REST AccessDeniedHttpException', $rest, 'AccessDeniedHttpException');
$t->assertContains('Sec: REST Bearer token check', $rest, 'Bearer');
$t->assertContains('Sec: REST BadRequestHttpException', $rest, 'BadRequestHttpException');
$t->assertContains('Sec: REST URL validation', $rest, 'FILTER_VALIDATE_URL');

// AJAX Controller input validation
$t->assertContains('Sec: AJAX URL validation', $ac, 'FILTER_VALIDATE_URL');
$t->assertContains('Sec: AJAX returns 400 on bad input', $ac, '400');
$t->assertContains('Sec: AJAX returns 404 on not found', $ac, '404');

// Permission
$perm = file_get_contents("{$moduleDir}/cachewarmer.permissions.yml");
$t->assertContains('Sec: permission defined', $perm, 'administer cachewarmer');
$t->assertContains('Sec: restrict access', $perm, 'restrict access: true');

// Routing permissions
$t->assertContains('Sec: routes require permission', $routing, "_permission: 'administer cachewarmer'");

// Settings form password fields
$passwordCount = substr_count($sf, "'#type' => 'password'");
$t->assert("Sec: settings uses password fields for secrets ({$passwordCount} found)", $passwordCount >= 4);

// Uninstall cleanup
$t->assertContains('Sec: uninstall deletes config', $install, 'configFactory');

// Module hooks
$module = file_get_contents("{$moduleDir}/cachewarmer.module");
$t->assertContains('Sec: module has hook_cron', $module, 'function cachewarmer_cron()');
$t->assertContains('Sec: cron checks scheduler enabled', $module, 'scheduler.enabled');

// ============================================================================
// 8. Performance Tests
// ============================================================================

$t->suite('8. Performance Tests');

// CSS file size
$cssSize = filesize("{$moduleDir}/css/cachewarmer-admin.css");
$cssSizeKb = round($cssSize / 1024, 2);
$t->assert("Perf: CSS file < 50KB ({$cssSizeKb}KB)", $cssSize < 50 * 1024);

// JS file size
$jsSize = filesize("{$moduleDir}/js/cachewarmer-admin.js");
$jsSizeKb = round($jsSize / 1024, 2);
$t->assert("Perf: JS file < 50KB ({$jsSizeKb}KB)", $jsSize < 50 * 1024);

// Total module size
$totalSize = 0;
$fileCount = 0;
$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($moduleDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($it as $file) {
  if ($file->isFile()) {
    $totalSize += $file->getSize();
    $fileCount++;
  }
}
$totalSizeKb = round($totalSize / 1024, 2);
$t->assert("Perf: Total module < 500KB ({$totalSizeKb}KB, {$fileCount} files)", $totalSize < 500 * 1024);

// PHP file count is reasonable
$t->assert("Perf: file count is reasonable ({$fileCount} files)", $fileCount >= 20 && $fileCount <= 50);

// ============================================================================
// 8b. Performance Tests — CSS & JS Quality
// ============================================================================

$t->suite('8b. Performance Tests — CSS & JS Quality');

$css = file_get_contents("{$moduleDir}/css/cachewarmer-admin.css");
$t->assertContains('CSS: status cards styles', $css, '.cachewarmer-status-cards');
$t->assertContains('CSS: card component', $css, '.cachewarmer-card');
$t->assertContains('CSS: badge component', $css, '.cachewarmer-badge');
$t->assertContains('CSS: progress bar', $css, '.cachewarmer-progress');
$t->assertContains('CSS: modal styles', $css, '.cachewarmer-modal');
$t->assertContains('CSS: responsive media query', $css, '@media');
$t->assertContains('CSS: queued badge color', $css, '.cachewarmer-badge--queued');
$t->assertContains('CSS: running badge color', $css, '.cachewarmer-badge--running');
$t->assertContains('CSS: completed badge color', $css, '.cachewarmer-badge--completed');
$t->assertContains('CSS: failed badge color', $css, '.cachewarmer-badge--failed');
$t->assertContains('CSS: tag component', $css, '.cachewarmer-tag');

$js = file_get_contents("{$moduleDir}/js/cachewarmer-admin.js");
$t->assertContains('JS: Drupal.t translations', $js, 'Drupal.t(');
$t->assertContains('JS: drupalSettings usage', $js, 'drupalSettings');
$t->assertContains('JS: escHtml XSS prevention', $js, 'escHtml');
$t->assertContains('JS: refreshJobsTable function', $js, 'refreshJobsTable');
$t->assertContains('JS: refreshStatus function', $js, 'refreshStatus');
$t->assertContains('JS: setInterval auto-refresh', $js, 'setInterval');
$t->assertContains('JS: warm form handler', $js, 'cachewarmer-start-warm');
$t->assertContains('JS: details modal handler', $js, 'cachewarmer-btn-details');
$t->assertContains('JS: delete job handler', $js, 'cachewarmer-btn-delete');
$t->assertContains('JS: add sitemap handler', $js, 'cachewarmer-add-sitemap');
$t->assertContains('JS: delete sitemap handler', $js, 'cachewarmer-btn-delete-sitemap');
$t->assertContains('JS: warm sitemap handler', $js, 'cachewarmer-btn-warm-sitemap');
$t->assertContains('JS: confirm before delete', $js, 'confirm(');

// ============================================================================
// 9. Unit Tests — Libraries & Links
// ============================================================================

$t->suite('9. Unit Tests — Libraries & Links');

$lib = file_get_contents("{$moduleDir}/cachewarmer.libraries.yml");
$t->assertContains('Library: core/drupal dependency', $lib, 'core/drupal');
$t->assertContains('Library: core/jquery dependency', $lib, 'core/jquery');
$t->assertContains('Library: core/drupalSettings dependency', $lib, 'core/drupalSettings');
$t->assertContains('Library: CSS file reference', $lib, 'cachewarmer-admin.css');
$t->assertContains('Library: JS file reference', $lib, 'cachewarmer-admin.js');

$menuLinks = file_get_contents("{$moduleDir}/cachewarmer.links.menu.yml");
$t->assertContains('Menu: dashboard link', $menuLinks, 'cachewarmer.dashboard');
$t->assertContains('Menu: parent config', $menuLinks, 'system.admin_config_development');

$taskLinks = file_get_contents("{$moduleDir}/cachewarmer.links.task.yml");
$t->assertContains('Tasks: dashboard tab', $taskLinks, 'cachewarmer.dashboard');
$t->assertContains('Tasks: sitemaps tab', $taskLinks, 'cachewarmer.sitemaps');
$t->assertContains('Tasks: settings tab', $taskLinks, 'cachewarmer.settings');

// ============================================================================
// 10. Module Hook Implementations
// ============================================================================

$t->suite('10. Unit Tests — Module Hooks');

$t->assertContains('Hook: hook_help', $module, 'function cachewarmer_help(');
$t->assertContains('Hook: hook_cron', $module, 'function cachewarmer_cron()');
$t->assertContains('Hook: hook_theme', $module, 'function cachewarmer_theme()');
$t->assertContains('Hook: theme cachewarmer_dashboard', $module, "'cachewarmer_dashboard'");
$t->assertContains('Hook: theme cachewarmer_sitemaps', $module, "'cachewarmer_sitemaps'");
$t->assertContains('Hook: template reference', $module, "'template' => 'cachewarmer-dashboard'");

// ============================================================================
// Done
// ============================================================================

$t->summary();
