<?php

namespace Drupal\cachewarmer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cachewarmer\Service\CacheWarmerDatabase;
use Drupal\cachewarmer\Service\CacheWarmerJobManager;
use Drupal\cachewarmer\Service\CacheWarmerLicense;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * AJAX controller for CacheWarmer admin operations.
 */
class CacheWarmerAjaxController extends ControllerBase {

  protected CacheWarmerDatabase $database;
  protected CacheWarmerJobManager $jobManager;
  protected CacheWarmerLicense $license;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('cachewarmer.database');
    $instance->jobManager = $container->get('cachewarmer.job_manager');
    $instance->license = $container->get('cachewarmer.license');
    return $instance;
  }

  /**
   * Starts a warming job.
   */
  public function startWarm(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?: $request->request->all();

    $sitemapUrl = $data['sitemap_url'] ?? '';
    if (empty($sitemapUrl) || !filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Valid sitemap URL is required.'], 400);
    }

    $targets = $data['targets'] ?? [];
    if (!is_array($targets) || empty($targets)) {
      $targets = ['cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow'];
    }

    $result = $this->jobManager->createJob($sitemapUrl, $targets);

    // Queue the job for background processing.
    \Drupal::queue('cachewarmer_process_job')->createItem(['job_id' => $result['jobId']]);

    return new JsonResponse(['success' => TRUE, 'data' => $result]);
  }

  /**
   * Gets all jobs.
   */
  public function getJobs(): JsonResponse {
    $jobs = $this->database->getJobs(20);
    $formatted = [];
    foreach ($jobs as $job) {
      $formatted[] = [
        'id' => $job->id,
        'sitemap_url' => $job->sitemap_url,
        'status' => $job->status,
        'total_urls' => (int) $job->total_urls,
        'processed_urls' => (int) $job->processed_urls,
        'targets' => json_decode($job->targets, TRUE),
        'created_at' => $job->created_at,
        'error' => $job->error,
      ];
    }
    return new JsonResponse(['success' => TRUE, 'data' => $formatted]);
  }

  /**
   * Gets a single job with stats.
   */
  public function getJob(string $job_id): JsonResponse {
    $job = $this->jobManager->getJobWithStats($job_id);
    if (!$job) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Job not found.'], 404);
    }
    return new JsonResponse(['success' => TRUE, 'data' => $job]);
  }

  /**
   * Deletes a job.
   */
  public function deleteJob(string $job_id): JsonResponse {
    $deleted = $this->database->deleteJob($job_id);
    if (!$deleted) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Job not found.'], 404);
    }
    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * Adds a sitemap.
   */
  public function addSitemap(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?: $request->request->all();

    $url = $data['url'] ?? '';
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Valid sitemap URL is required.'], 400);
    }

    $parsed = parse_url($url);
    $domain = $parsed['host'] ?? '';
    $cronExpression = $data['cron_expression'] ?? NULL;

    $sitemap = $this->database->insertSitemap($url, $domain, $cronExpression);
    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => $sitemap->id,
        'url' => $sitemap->url,
        'domain' => $sitemap->domain,
        'cron_expression' => $sitemap->cron_expression,
        'created_at' => $sitemap->created_at,
        'last_warmed_at' => $sitemap->last_warmed_at,
      ],
    ]);
  }

  /**
   * Deletes a sitemap.
   */
  public function deleteSitemap(string $sitemap_id): JsonResponse {
    $deleted = $this->database->deleteSitemap($sitemap_id);
    if (!$deleted) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sitemap not found.'], 404);
    }
    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * Warms a registered sitemap.
   */
  public function warmSitemap(string $sitemap_id): JsonResponse {
    $sitemap = $this->database->getSitemap($sitemap_id);
    if (!$sitemap) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Sitemap not found.'], 404);
    }

    $targets = ['cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow'];
    $result = $this->jobManager->createJob($sitemap->url, $targets, $sitemap->id);

    \Drupal::queue('cachewarmer_process_job')->createItem(['job_id' => $result['jobId']]);

    return new JsonResponse(['success' => TRUE, 'data' => $result]);
  }

  /**
   * Gets system status.
   */
  public function getStatus(): JsonResponse {
    $counts = $this->database->getJobCounts();
    return new JsonResponse(['success' => TRUE, 'data' => $counts]);
  }

  /**
   * Bulk adds sitemaps from a list of URLs.
   */
  public function bulkAddSitemaps(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $urlsRaw = $content['urls'] ?? '';
    $lines = array_filter(array_map('trim', explode("\n", $urlsRaw)));
    $added = [];
    $errors = [];

    foreach ($lines as $url) {
      if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = $url;
        continue;
      }
      $domain = parse_url($url, PHP_URL_HOST) ?: '';
      $sitemap = $this->database->insertSitemap($url, $domain);
      $added[] = [
        'id' => $sitemap->id,
        'url' => $sitemap->url,
        'domain' => $sitemap->domain,
        'created_at' => $sitemap->created_at,
      ];
    }

    return new JsonResponse(['added' => $added, 'errors' => $errors]);
  }

  /**
   * Auto-detects sitemaps on the current site.
   */
  public function detectSitemaps(): JsonResponse {
    /** @var \Drupal\cachewarmer\Service\CacheWarmerSitemapDetector $detector */
    $detector = \Drupal::service('cachewarmer.sitemap_detector');
    $found = $detector->detect();
    return new JsonResponse(['sitemaps' => $found]);
  }

  /**
   * Exports job results in CSV or JSON format.
   */
  public function exportResults(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $jobId = $content['job_id'] ?? '';
    $format = $content['format'] ?? 'json';
    $results = $this->database->getJobResults($jobId);

    if ($format === 'csv') {
      $csv = "url,target,status,http_status,duration_ms,error,created_at\n";
      foreach ($results as $r) {
        $csv .= sprintf(
          '"%s","%s","%s",%d,%d,"%s","%s"' . "\n",
          $r->url,
          $r->target,
          $r->status,
          $r->http_status ?? 0,
          $r->duration_ms ?? 0,
          str_replace('"', '""', $r->error ?? ''),
          $r->created_at
        );
      }
      return new JsonResponse([
        'format' => 'csv',
        'content' => $csv,
        'filename' => 'cachewarmer-' . $jobId . '.csv',
      ]);
    }

    $jsonResults = [];
    foreach ($results as $r) {
      $jsonResults[] = [
        'url' => $r->url,
        'target' => $r->target,
        'status' => $r->status,
        'http_status' => $r->http_status,
        'duration_ms' => $r->duration_ms,
        'error' => $r->error,
        'created_at' => $r->created_at,
      ];
    }

    return new JsonResponse([
      'format' => 'json',
      'content' => $jsonResults,
      'filename' => 'cachewarmer-' . $jobId . '.json',
    ]);
  }

}
