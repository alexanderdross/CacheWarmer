<?php

namespace Drupal\Tests\cachewarmer\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for the CacheWarmer module.
 *
 * @group cachewarmer
 */
class CacheWarmerTestSuite extends UnitTestCase {

  /**
   * Module base path.
   */
  protected string $modulePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->modulePath = dirname(__DIR__, 3);
  }

  // ==========================================================================
  // QA: File existence and structure
  // ==========================================================================

  /**
   * Tests that all required module files exist.
   */
  public function testModuleFilesExist(): void {
    $files = [
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

    foreach ($files as $file) {
      $this->assertFileExists(
        $this->modulePath . '/' . $file,
        "File {$file} should exist."
      );
    }
  }

  /**
   * Tests that PHP files have valid syntax.
   */
  public function testPhpSyntax(): void {
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
      $path = $this->modulePath . '/' . $file;
      $output = [];
      $returnCode = 0;
      exec("php -l {$path} 2>&1", $output, $returnCode);
      $this->assertEquals(
        0,
        $returnCode,
        "File {$file} should have valid PHP syntax: " . implode("\n", $output)
      );
    }
  }

  /**
   * Tests that the info.yml file has correct metadata.
   */
  public function testInfoYml(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.info.yml');
    $this->assertStringContainsString("name: CacheWarmer", $content);
    $this->assertStringContainsString("type: module", $content);
    $this->assertStringContainsString("core_version_requirement:", $content);
    $this->assertStringContainsString("drupal:rest", $content);
    $this->assertStringContainsString("drupal:serialization", $content);
    $this->assertStringContainsString("php: 8.1", $content);
  }

  /**
   * Tests that the config schema covers all settings.
   */
  public function testConfigSchema(): void {
    $content = file_get_contents($this->modulePath . '/config/schema/cachewarmer.schema.yml');
    $this->assertStringContainsString('cachewarmer.settings:', $content);
    $this->assertStringContainsString('api_key:', $content);
    $this->assertStringContainsString('cdn:', $content);
    $this->assertStringContainsString('facebook:', $content);
    $this->assertStringContainsString('linkedin:', $content);
    $this->assertStringContainsString('twitter:', $content);
    $this->assertStringContainsString('google:', $content);
    $this->assertStringContainsString('bing:', $content);
    $this->assertStringContainsString('indexnow:', $content);
    $this->assertStringContainsString('scheduler:', $content);
    $this->assertStringContainsString('log_level:', $content);
  }

  /**
   * Tests default config has all required keys.
   */
  public function testDefaultConfig(): void {
    $content = file_get_contents($this->modulePath . '/config/install/cachewarmer.settings.yml');
    $this->assertStringContainsString('api_key:', $content);
    $this->assertStringContainsString('cdn:', $content);
    $this->assertStringContainsString('facebook:', $content);
    $this->assertStringContainsString('linkedin:', $content);
    $this->assertStringContainsString('twitter:', $content);
    $this->assertStringContainsString('google:', $content);
    $this->assertStringContainsString('bing:', $content);
    $this->assertStringContainsString('indexnow:', $content);
    $this->assertStringContainsString('scheduler:', $content);
    $this->assertStringContainsString('log_level:', $content);
  }

  // ==========================================================================
  // Service Classes: Namespace and structure
  // ==========================================================================

  /**
   * Tests that all service classes declare correct namespaces.
   */
  public function testServiceNamespaces(): void {
    $services = [
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

    foreach ($services as $file => $expectedNs) {
      $content = file_get_contents($this->modulePath . '/' . $file);
      $this->assertStringContainsString(
        "namespace {$expectedNs};",
        $content,
        "File {$file} should declare namespace {$expectedNs}"
      );
    }
  }

  /**
   * Tests that warming services have warm() or index() methods.
   */
  public function testServiceMethodSignatures(): void {
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
      $content = file_get_contents($this->modulePath . '/' . $file);
      $this->assertStringContainsString(
        "public function {$method}(array \$urls, string \$jobId",
        $content,
        "File {$file} should have public {$method}() method"
      );
    }
  }

  /**
   * Tests that services use Guzzle HTTP client.
   */
  public function testServicesUseGuzzle(): void {
    $services = [
      'src/Service/CdnWarmer.php',
      'src/Service/FacebookWarmer.php',
      'src/Service/LinkedinWarmer.php',
      'src/Service/TwitterWarmer.php',
      'src/Service/GoogleIndexer.php',
      'src/Service/BingIndexer.php',
      'src/Service/IndexNow.php',
      'src/Service/CacheWarmerSitemapParser.php',
    ];

    foreach ($services as $file) {
      $content = file_get_contents($this->modulePath . '/' . $file);
      $this->assertStringContainsString(
        'GuzzleHttp\\ClientInterface',
        $content,
        "File {$file} should use GuzzleHttp\\ClientInterface"
      );
    }
  }

  // ==========================================================================
  // Database: Schema validation
  // ==========================================================================

  /**
   * Tests the install file defines all 3 tables.
   */
  public function testDatabaseSchema(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.install');
    $this->assertStringContainsString('cachewarmer_sitemaps', $content);
    $this->assertStringContainsString('cachewarmer_jobs', $content);
    $this->assertStringContainsString('cachewarmer_url_results', $content);
    $this->assertStringContainsString('function cachewarmer_schema()', $content);
    $this->assertStringContainsString('function cachewarmer_uninstall()', $content);
  }

  /**
   * Tests schema has required columns for sitemaps table.
   */
  public function testSitemapsSchemaColumns(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.install');
    $requiredColumns = ['id', 'url', 'domain', 'cron_expression', 'created_at', 'last_warmed_at'];
    foreach ($requiredColumns as $col) {
      $this->assertStringContainsString(
        "'{$col}'",
        $content,
        "Sitemaps schema should include column '{$col}'"
      );
    }
  }

  /**
   * Tests schema has required columns for jobs table.
   */
  public function testJobsSchemaColumns(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.install');
    $requiredColumns = ['id', 'sitemap_id', 'sitemap_url', 'status', 'total_urls', 'processed_urls', 'targets', 'started_at', 'completed_at', 'error', 'created_at'];
    foreach ($requiredColumns as $col) {
      $this->assertStringContainsString(
        "'{$col}'",
        $content,
        "Jobs schema should include column '{$col}'"
      );
    }
  }

  /**
   * Tests schema has required columns for url_results table.
   */
  public function testUrlResultsSchemaColumns(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.install');
    $requiredColumns = ['id', 'job_id', 'url', 'target', 'status', 'http_status', 'duration_ms', 'error', 'created_at'];
    foreach ($requiredColumns as $col) {
      $this->assertStringContainsString(
        "'{$col}'",
        $content,
        "URL results schema should include column '{$col}'"
      );
    }
  }

  // ==========================================================================
  // Routing: Route definitions
  // ==========================================================================

  /**
   * Tests that routing file defines all required routes.
   */
  public function testRoutingDefinitions(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.routing.yml');
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
      $this->assertStringContainsString(
        $route . ':',
        $content,
        "Route {$route} should be defined in routing.yml"
      );
    }
  }

  /**
   * Tests that all routes require administer cachewarmer permission.
   */
  public function testRoutePermissions(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.routing.yml');
    $this->assertStringContainsString("_permission: 'administer cachewarmer'", $content);
  }

  // ==========================================================================
  // Services YAML
  // ==========================================================================

  /**
   * Tests that all services are registered.
   */
  public function testServicesYaml(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.services.yml');
    $services = [
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

    foreach ($services as $service) {
      $this->assertStringContainsString(
        $service . ':',
        $content,
        "Service {$service} should be registered in services.yml"
      );
    }
  }

  // ==========================================================================
  // Controller validation
  // ==========================================================================

  /**
   * Tests dashboard controller extends ControllerBase.
   */
  public function testDashboardControllerStructure(): void {
    $content = file_get_contents($this->modulePath . '/src/Controller/CacheWarmerDashboardController.php');
    $this->assertStringContainsString('extends ControllerBase', $content);
    $this->assertStringContainsString('public function dashboard()', $content);
    $this->assertStringContainsString('public function sitemaps()', $content);
    $this->assertStringContainsString("'#theme' => 'cachewarmer_dashboard'", $content);
    $this->assertStringContainsString("'#theme' => 'cachewarmer_sitemaps'", $content);
  }

  /**
   * Tests AJAX controller has all required methods.
   */
  public function testAjaxControllerMethods(): void {
    $content = file_get_contents($this->modulePath . '/src/Controller/CacheWarmerAjaxController.php');
    $methods = ['startWarm', 'getJobs', 'getJob', 'deleteJob', 'addSitemap', 'deleteSitemap', 'warmSitemap', 'getStatus'];
    foreach ($methods as $method) {
      $this->assertStringContainsString(
        "public function {$method}(",
        $content,
        "AjaxController should have method {$method}()"
      );
    }
  }

  /**
   * Tests AJAX controller returns JsonResponse.
   */
  public function testAjaxControllerReturnsJson(): void {
    $content = file_get_contents($this->modulePath . '/src/Controller/CacheWarmerAjaxController.php');
    $this->assertStringContainsString('JsonResponse', $content);
    $this->assertStringContainsString('use Symfony\\Component\\HttpFoundation\\JsonResponse', $content);
  }

  // ==========================================================================
  // Settings form
  // ==========================================================================

  /**
   * Tests settings form extends ConfigFormBase.
   */
  public function testSettingsFormStructure(): void {
    $content = file_get_contents($this->modulePath . '/src/Form/CacheWarmerSettingsForm.php');
    $this->assertStringContainsString('extends ConfigFormBase', $content);
    $this->assertStringContainsString("'cachewarmer.settings'", $content);
    $this->assertStringContainsString('public function buildForm(', $content);
    $this->assertStringContainsString('public function submitForm(', $content);
  }

  /**
   * Tests settings form has all service sections.
   */
  public function testSettingsFormSections(): void {
    $content = file_get_contents($this->modulePath . '/src/Form/CacheWarmerSettingsForm.php');
    $sections = ['general', 'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow', 'scheduler', 'logging'];
    foreach ($sections as $section) {
      $this->assertStringContainsString(
        "\$form['{$section}']",
        $content,
        "Settings form should have section '{$section}'"
      );
    }
  }

  /**
   * Tests settings form uses password fields for secrets.
   */
  public function testSettingsFormPasswordFields(): void {
    $content = file_get_contents($this->modulePath . '/src/Form/CacheWarmerSettingsForm.php');
    // API key, app secret, session cookie, bing api key should use password type
    $this->assertGreaterThanOrEqual(
      4,
      substr_count($content, "'#type' => 'password'"),
      "Settings form should use password type for sensitive fields"
    );
  }

  // ==========================================================================
  // Queue Worker
  // ==========================================================================

  /**
   * Tests queue worker plugin annotation.
   */
  public function testQueueWorkerPlugin(): void {
    $content = file_get_contents($this->modulePath . '/src/Plugin/QueueWorker/CacheWarmerProcessJob.php');
    $this->assertStringContainsString('@QueueWorker', $content);
    $this->assertStringContainsString('id = "cachewarmer_process_job"', $content);
    $this->assertStringContainsString('public function processItem($data)', $content);
    $this->assertStringContainsString('ContainerFactoryPluginInterface', $content);
  }

  // ==========================================================================
  // Templates
  // ==========================================================================

  /**
   * Tests dashboard template has required elements.
   */
  public function testDashboardTemplate(): void {
    $content = file_get_contents($this->modulePath . '/templates/cachewarmer-dashboard.html.twig');
    $this->assertStringContainsString('cachewarmer-status-cards', $content);
    $this->assertStringContainsString('cachewarmer-warm-form', $content);
    $this->assertStringContainsString('cachewarmer-sitemap-url', $content);
    $this->assertStringContainsString('cachewarmer-jobs-table', $content);
    $this->assertStringContainsString('cachewarmer-modal', $content);
    $this->assertStringContainsString('cachewarmer-start-warm', $content);
    // Target checkboxes
    $this->assertStringContainsString('value="cdn"', $content);
    $this->assertStringContainsString('value="facebook"', $content);
    $this->assertStringContainsString('value="linkedin"', $content);
    $this->assertStringContainsString('value="twitter"', $content);
    $this->assertStringContainsString('value="google"', $content);
    $this->assertStringContainsString('value="bing"', $content);
    $this->assertStringContainsString('value="indexnow"', $content);
  }

  /**
   * Tests sitemaps template has required elements.
   */
  public function testSitemapsTemplate(): void {
    $content = file_get_contents($this->modulePath . '/templates/cachewarmer-sitemaps.html.twig');
    $this->assertStringContainsString('cachewarmer-add-sitemap-form', $content);
    $this->assertStringContainsString('cachewarmer-new-sitemap-url', $content);
    $this->assertStringContainsString('cachewarmer-sitemaps-table', $content);
    $this->assertStringContainsString('cachewarmer-add-sitemap', $content);
    $this->assertStringContainsString('cachewarmer-btn-warm-sitemap', $content);
    $this->assertStringContainsString('cachewarmer-btn-delete-sitemap', $content);
  }

  /**
   * Tests templates have proper table headers for accessibility.
   */
  public function testTemplateAccessibility(): void {
    $dashboard = file_get_contents($this->modulePath . '/templates/cachewarmer-dashboard.html.twig');
    $this->assertStringContainsString('<thead>', $dashboard);
    $this->assertStringContainsString('<th>', $dashboard);
    $this->assertStringContainsString('label', $dashboard);

    $sitemaps = file_get_contents($this->modulePath . '/templates/cachewarmer-sitemaps.html.twig');
    $this->assertStringContainsString('<thead>', $sitemaps);
    $this->assertStringContainsString('<th>', $sitemaps);
    $this->assertStringContainsString('label', $sitemaps);
  }

  // ==========================================================================
  // CSS & JS
  // ==========================================================================

  /**
   * Tests CSS file has styles for all key components.
   */
  public function testCssComponents(): void {
    $content = file_get_contents($this->modulePath . '/css/cachewarmer-admin.css');
    $selectors = [
      '.cachewarmer-status-cards',
      '.cachewarmer-card',
      '.cachewarmer-badge',
      '.cachewarmer-progress',
      '.cachewarmer-modal',
      '.cachewarmer-tag',
      '.cachewarmer-badge--queued',
      '.cachewarmer-badge--running',
      '.cachewarmer-badge--completed',
      '.cachewarmer-badge--failed',
      '@media',
    ];

    foreach ($selectors as $selector) {
      $this->assertStringContainsString(
        $selector,
        $content,
        "CSS should contain selector/rule: {$selector}"
      );
    }
  }

  /**
   * Tests JS file has all required event handlers.
   */
  public function testJsEventHandlers(): void {
    $content = file_get_contents($this->modulePath . '/js/cachewarmer-admin.js');
    $handlers = [
      'cachewarmer-start-warm',
      'cachewarmer-btn-details',
      'cachewarmer-btn-delete',
      'cachewarmer-add-sitemap',
      'cachewarmer-btn-delete-sitemap',
      'cachewarmer-btn-warm-sitemap',
      'refreshJobsTable',
      'refreshStatus',
      'setInterval',
    ];

    foreach ($handlers as $handler) {
      $this->assertStringContainsString(
        $handler,
        $content,
        "JS should contain handler/function: {$handler}"
      );
    }
  }

  /**
   * Tests JS uses Drupal.t for translations.
   */
  public function testJsTranslation(): void {
    $content = file_get_contents($this->modulePath . '/js/cachewarmer-admin.js');
    $this->assertStringContainsString('Drupal.t(', $content);
  }

  /**
   * Tests JS uses drupalSettings for AJAX URLs.
   */
  public function testJsDrupalSettings(): void {
    $content = file_get_contents($this->modulePath . '/js/cachewarmer-admin.js');
    $this->assertStringContainsString('drupalSettings', $content);
    $this->assertStringContainsString('cachewarmer', $content);
  }

  // ==========================================================================
  // Security
  // ==========================================================================

  /**
   * Tests that REST resource validates authentication.
   */
  public function testRestResourceAuth(): void {
    $content = file_get_contents($this->modulePath . '/src/Plugin/rest/resource/CacheWarmerResource.php');
    $this->assertStringContainsString('validateAuth', $content);
    $this->assertStringContainsString('hash_equals', $content);
    $this->assertStringContainsString('AccessDeniedHttpException', $content);
    $this->assertStringContainsString('Bearer', $content);
  }

  /**
   * Tests that AJAX controller validates URL input.
   */
  public function testAjaxInputValidation(): void {
    $content = file_get_contents($this->modulePath . '/src/Controller/CacheWarmerAjaxController.php');
    $this->assertStringContainsString('FILTER_VALIDATE_URL', $content);
  }

  /**
   * Tests permission definition.
   */
  public function testPermissionDefinition(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.permissions.yml');
    $this->assertStringContainsString('administer cachewarmer', $content);
    $this->assertStringContainsString('restrict access: true', $content);
  }

  /**
   * Tests that module files have proper Drupal patterns.
   */
  public function testModuleHookImplementations(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.module');
    $this->assertStringContainsString('function cachewarmer_help(', $content);
    $this->assertStringContainsString('function cachewarmer_cron()', $content);
    $this->assertStringContainsString('function cachewarmer_theme()', $content);
  }

  // ==========================================================================
  // Performance
  // ==========================================================================

  /**
   * Tests that CSS file is within size limits.
   */
  public function testCssFileSize(): void {
    $size = filesize($this->modulePath . '/css/cachewarmer-admin.css');
    $this->assertLessThan(
      50 * 1024,
      $size,
      'CSS file should be less than 50KB'
    );
  }

  /**
   * Tests that JS file is within size limits.
   */
  public function testJsFileSize(): void {
    $size = filesize($this->modulePath . '/js/cachewarmer-admin.js');
    $this->assertLessThan(
      50 * 1024,
      $size,
      'JS file should be less than 50KB'
    );
  }

  /**
   * Tests that total module size is reasonable.
   */
  public function testModuleSize(): void {
    $totalSize = 0;
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->modulePath, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $totalSize += $file->getSize();
      }
    }

    $this->assertLessThan(
      500 * 1024,
      $totalSize,
      'Total module size should be less than 500KB'
    );
  }

  // ==========================================================================
  // Sitemap Parser
  // ==========================================================================

  /**
   * Tests sitemap parser class structure.
   */
  public function testSitemapParserStructure(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/CacheWarmerSitemapParser.php');
    $this->assertStringContainsString('public function parse(string $url): array', $content);
    $this->assertStringContainsString('parseRecursive', $content);
    $this->assertStringContainsString('MAX_DEPTH', $content);
    $this->assertStringContainsString('FILTER_VALIDATE_URL', $content);
    $this->assertStringContainsString('simplexml_load_string', $content);
    $this->assertStringContainsString('sitemapindex', $content);
    $this->assertStringContainsString('urlset', $content);
  }

  // ==========================================================================
  // Job Manager
  // ==========================================================================

  /**
   * Tests job manager class structure.
   */
  public function testJobManagerStructure(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/CacheWarmerJobManager.php');
    $this->assertStringContainsString('ALLOWED_TARGETS', $content);
    $this->assertStringContainsString('public function createJob(', $content);
    $this->assertStringContainsString('public function processJob(', $content);
    $this->assertStringContainsString('public function getJobWithStats(', $content);
    $this->assertStringContainsString('processTarget', $content);
    // Verify all 7 targets are handled
    $this->assertStringContainsString("case 'cdn':", $content);
    $this->assertStringContainsString("case 'facebook':", $content);
    $this->assertStringContainsString("case 'linkedin':", $content);
    $this->assertStringContainsString("case 'twitter':", $content);
    $this->assertStringContainsString("case 'google':", $content);
    $this->assertStringContainsString("case 'bing':", $content);
    $this->assertStringContainsString("case 'indexnow':", $content);
  }

  // ==========================================================================
  // Facebook warmer specifics
  // ==========================================================================

  /**
   * Tests Facebook warmer uses correct API endpoint.
   */
  public function testFacebookEndpoint(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/FacebookWarmer.php');
    $this->assertStringContainsString('graph.facebook.com/v19.0', $content);
    $this->assertStringContainsString("'scrape' => 'true'", $content);
  }

  // ==========================================================================
  // LinkedIn warmer specifics
  // ==========================================================================

  /**
   * Tests LinkedIn warmer uses correct API endpoint.
   */
  public function testLinkedinEndpoint(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/LinkedinWarmer.php');
    $this->assertStringContainsString('linkedin.com/post-inspector', $content);
    $this->assertStringContainsString('li_at', $content);
    $this->assertStringContainsString('csrf-token', $content);
  }

  // ==========================================================================
  // Twitter warmer specifics
  // ==========================================================================

  /**
   * Tests Twitter warmer uses composer endpoint.
   */
  public function testTwitterEndpoint(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/TwitterWarmer.php');
    $this->assertStringContainsString('twitter.com/intent/tweet', $content);
    $this->assertStringContainsString('rawurlencode', $content);
  }

  // ==========================================================================
  // Google indexer specifics
  // ==========================================================================

  /**
   * Tests Google indexer uses correct API and JWT.
   */
  public function testGoogleIndexerDetails(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/GoogleIndexer.php');
    $this->assertStringContainsString('indexing.googleapis.com', $content);
    $this->assertStringContainsString('URL_UPDATED', $content);
    $this->assertStringContainsString('getAccessToken', $content);
    $this->assertStringContainsString('RS256', $content);
    $this->assertStringContainsString('openssl_sign', $content);
    $this->assertStringContainsString('daily_quota', $content);
  }

  // ==========================================================================
  // Bing indexer specifics
  // ==========================================================================

  /**
   * Tests Bing indexer uses correct API endpoint and batching.
   */
  public function testBingIndexerDetails(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/BingIndexer.php');
    $this->assertStringContainsString('ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch', $content);
    $this->assertStringContainsString('BATCH_SIZE', $content);
    $this->assertStringContainsString('daily_quota', $content);
  }

  // ==========================================================================
  // IndexNow specifics
  // ==========================================================================

  /**
   * Tests IndexNow uses correct API endpoint and batching.
   */
  public function testIndexNowDetails(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/IndexNow.php');
    $this->assertStringContainsString('api.indexnow.org/indexnow', $content);
    $this->assertStringContainsString('BATCH_SIZE', $content);
    $this->assertStringContainsString('10000', $content);
    $this->assertStringContainsString('keyLocation', $content);
  }

  // ==========================================================================
  // CDN warmer specifics
  // ==========================================================================

  /**
   * Tests CDN warmer uses desktop and mobile user agents.
   */
  public function testCdnWarmerUserAgents(): void {
    $content = file_get_contents($this->modulePath . '/src/Service/CdnWarmer.php');
    $this->assertStringContainsString('DESKTOP_UA', $content);
    $this->assertStringContainsString('MOBILE_UA', $content);
    $this->assertStringContainsString('iPhone', $content);
    $this->assertStringContainsString('Windows NT', $content);
  }

  // ==========================================================================
  // Libraries
  // ==========================================================================

  /**
   * Tests library definition includes correct dependencies.
   */
  public function testLibraryDefinition(): void {
    $content = file_get_contents($this->modulePath . '/cachewarmer.libraries.yml');
    $this->assertStringContainsString('core/drupal', $content);
    $this->assertStringContainsString('core/jquery', $content);
    $this->assertStringContainsString('core/drupalSettings', $content);
    $this->assertStringContainsString('cachewarmer-admin.css', $content);
    $this->assertStringContainsString('cachewarmer-admin.js', $content);
  }

  // ==========================================================================
  // Links
  // ==========================================================================

  /**
   * Tests menu and task link definitions.
   */
  public function testLinkDefinitions(): void {
    $menuLinks = file_get_contents($this->modulePath . '/cachewarmer.links.menu.yml');
    $this->assertStringContainsString('cachewarmer.dashboard', $menuLinks);
    $this->assertStringContainsString('system.admin_config_development', $menuLinks);

    $taskLinks = file_get_contents($this->modulePath . '/cachewarmer.links.task.yml');
    $this->assertStringContainsString('cachewarmer.dashboard', $taskLinks);
    $this->assertStringContainsString('cachewarmer.sitemaps', $taskLinks);
    $this->assertStringContainsString('cachewarmer.settings', $taskLinks);
  }

}
