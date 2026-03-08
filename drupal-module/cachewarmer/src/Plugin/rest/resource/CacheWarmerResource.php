<?php

namespace Drupal\cachewarmer\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cachewarmer\Service\CacheWarmerDatabase;
use Drupal\cachewarmer\Service\CacheWarmerJobManager;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides CacheWarmer REST API.
 *
 * @RestResource(
 *   id = "cachewarmer",
 *   label = @Translation("CacheWarmer API"),
 *   uri_paths = {
 *     "canonical" = "/api/cachewarmer/{action}",
 *     "create" = "/api/cachewarmer/{action}"
 *   }
 * )
 */
class CacheWarmerResource extends ResourceBase {

  protected CacheWarmerDatabase $database;
  protected CacheWarmerJobManager $jobManager;
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('cachewarmer.database');
    $instance->jobManager = $container->get('cachewarmer.job_manager');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Validates Bearer token authentication.
   */
  protected function validateAuth(Request $request): void {
    $config = $this->configFactory->get('cachewarmer.settings');
    $apiKey = $config->get('api_key');

    if (empty($apiKey)) {
      return;
    }

    $authHeader = $request->headers->get('Authorization', '');
    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
      throw new AccessDeniedHttpException('Missing or invalid Authorization header.');
    }

    if (!hash_equals($apiKey, $matches[1])) {
      throw new AccessDeniedHttpException('Invalid API key.');
    }
  }

  /**
   * Responds to POST requests.
   */
  public function post($action, Request $request) {
    $this->validateAuth($request);
    $data = json_decode($request->getContent(), TRUE) ?: [];

    switch ($action) {
      case 'warm':
        return $this->startWarm($data);

      case 'sitemaps':
        return $this->registerSitemap($data);

      default:
        throw new NotFoundHttpException('Unknown action.');
    }
  }

  /**
   * Responds to GET requests.
   */
  public function get($action, Request $request) {
    $this->validateAuth($request);

    switch ($action) {
      case 'status':
        return $this->getStatus();

      case 'jobs':
        return $this->listJobs($request);

      case 'sitemaps':
        return $this->listSitemaps();

      case 'logs':
        return $this->getLogs($request);

      default:
        // Check if action is a job ID lookup (jobs/{id}).
        if (preg_match('/^jobs\/(.+)$/', $action, $matches)) {
          return $this->getJob($matches[1]);
        }
        throw new NotFoundHttpException('Unknown action.');
    }
  }

  /**
   * Responds to DELETE requests.
   */
  public function delete($action, Request $request) {
    $this->validateAuth($request);

    if (preg_match('/^jobs\/(.+)$/', $action, $matches)) {
      return $this->deleteJob($matches[1]);
    }
    if (preg_match('/^sitemaps\/(.+)$/', $action, $matches)) {
      return $this->deleteSitemap($matches[1]);
    }

    throw new NotFoundHttpException('Unknown action.');
  }

  /**
   * Starts a warming job.
   */
  protected function startWarm(array $data): ResourceResponse {
    $sitemapUrl = $data['sitemapUrl'] ?? '';
    if (empty($sitemapUrl) || !filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
      throw new BadRequestHttpException('Valid sitemapUrl is required.');
    }

    $targets = $data['targets'] ?? ['cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow'];
    $result = $this->jobManager->createJob($sitemapUrl, $targets);

    if ($result['status'] === 'rejected') {
      throw new ConflictHttpException($result['error']);
    }

    // Trigger processing via queue.
    \Drupal::queue('cachewarmer_process_job')->createItem(['job_id' => $result['jobId']]);

    return new ResourceResponse($result, 201);
  }

  /**
   * Gets system status.
   */
  protected function getStatus(): ResourceResponse {
    $counts = $this->database->getJobCounts();
    return new ResourceResponse([
      'status' => 'ok',
      'jobs' => $counts,
    ]);
  }

  /**
   * Lists jobs.
   */
  protected function listJobs(Request $request): ResourceResponse {
    $limit = (int) ($request->query->get('limit', 20));
    $offset = (int) ($request->query->get('offset', 0));
    $jobs = $this->database->getJobs($limit, $offset);
    return new ResourceResponse($jobs);
  }

  /**
   * Gets a single job with stats.
   */
  protected function getJob(string $id): ResourceResponse {
    $job = $this->jobManager->getJobWithStats($id);
    if (!$job) {
      throw new NotFoundHttpException('Job not found.');
    }
    return new ResourceResponse($job);
  }

  /**
   * Deletes a job.
   */
  protected function deleteJob(string $id): ResourceResponse {
    $deleted = $this->database->deleteJob($id);
    if (!$deleted) {
      throw new NotFoundHttpException('Job not found.');
    }
    return new ResourceResponse(['deleted' => TRUE]);
  }

  /**
   * Lists sitemaps.
   */
  protected function listSitemaps(): ResourceResponse {
    $sitemaps = $this->database->getAllSitemaps();
    return new ResourceResponse($sitemaps);
  }

  /**
   * Registers a sitemap.
   */
  protected function registerSitemap(array $data): ResourceResponse {
    $url = $data['url'] ?? '';
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
      throw new BadRequestHttpException('Valid url is required.');
    }

    // Check for duplicate sitemap URL.
    $existing = $this->database->getSitemapByUrl($url);
    if ($existing) {
      throw new ConflictHttpException('A sitemap with this URL already exists.');
    }

    $parsed = parse_url($url);
    $domain = $parsed['host'] ?? '';
    $cronExpression = $data['cronExpression'] ?? NULL;

    $sitemap = $this->database->insertSitemap($url, $domain, $cronExpression);
    return new ResourceResponse($sitemap, 201);
  }

  /**
   * Deletes a sitemap.
   */
  protected function deleteSitemap(string $id): ResourceResponse {
    $deleted = $this->database->deleteSitemap($id);
    if (!$deleted) {
      throw new NotFoundHttpException('Sitemap not found.');
    }
    return new ResourceResponse(['deleted' => TRUE]);
  }

  /**
   * Gets logs.
   */
  protected function getLogs(Request $request): ResourceResponse {
    $limit = (int) ($request->query->get('limit', 100));
    $offset = (int) ($request->query->get('offset', 0));
    $logs = $this->database->getLogs($limit, $offset);
    return new ResourceResponse($logs);
  }

}
