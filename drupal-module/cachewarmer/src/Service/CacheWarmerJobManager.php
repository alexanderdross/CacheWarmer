<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages cache warming jobs: creation, processing, and status tracking.
 */
class CacheWarmerJobManager {

  protected CacheWarmerDatabase $database;
  protected CacheWarmerSitemapParser $sitemapParser;
  protected CdnWarmer $cdnWarmer;
  protected FacebookWarmer $facebookWarmer;
  protected LinkedinWarmer $linkedinWarmer;
  protected TwitterWarmer $twitterWarmer;
  protected GoogleIndexer $googleIndexer;
  protected BingIndexer $bingIndexer;
  protected IndexNow $indexNow;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;
  protected CacheWarmerWebhooks $webhooks;
  protected CacheWarmerEmail $email;
  protected PinterestWarmer $pinterestWarmer;
  protected CdnPurgeWarmer $cdnPurgeWarmer;

  /**
   * Allowed warming targets.
   */
  /**
   * Upper bound for the purge propagation wait, in seconds.
   */
  protected const MAX_PROPAGATION_WAIT_SECONDS = 60;

  protected const ALLOWED_TARGETS = [
    'cdn', 'facebook', 'linkedin', 'twitter', 'google', 'bing', 'indexnow', 'pinterest', 'cdn-purge',
  ];

  public function __construct(
    CacheWarmerDatabase $database,
    CacheWarmerSitemapParser $sitemapParser,
    CdnWarmer $cdnWarmer,
    FacebookWarmer $facebookWarmer,
    LinkedinWarmer $linkedinWarmer,
    TwitterWarmer $twitterWarmer,
    GoogleIndexer $googleIndexer,
    BingIndexer $bingIndexer,
    IndexNow $indexNow,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
    CacheWarmerWebhooks $webhooks,
    CacheWarmerEmail $email,
    PinterestWarmer $pinterestWarmer,
    CdnPurgeWarmer $cdnPurgeWarmer,
  ) {
    $this->database = $database;
    $this->sitemapParser = $sitemapParser;
    $this->cdnWarmer = $cdnWarmer;
    $this->facebookWarmer = $facebookWarmer;
    $this->linkedinWarmer = $linkedinWarmer;
    $this->twitterWarmer = $twitterWarmer;
    $this->googleIndexer = $googleIndexer;
    $this->bingIndexer = $bingIndexer;
    $this->indexNow = $indexNow;
    $this->configFactory = $configFactory;
    $this->logger = $loggerFactory->get('cachewarmer');
    $this->webhooks = $webhooks;
    $this->email = $email;
    $this->pinterestWarmer = $pinterestWarmer;
    $this->cdnPurgeWarmer = $cdnPurgeWarmer;
  }

  /**
   * Creates a new warming job.
   */
  public function createJob(string $sitemapUrl, array $targets, ?string $sitemapId = NULL): array {
    // Check for an already active job for this URL.
    if ($this->database->hasActiveJobForUrl($sitemapUrl)) {
      return [
        'jobId' => NULL,
        'status' => 'rejected',
        'sitemapUrl' => $sitemapUrl,
        'targets' => $targets,
        'createdAt' => NULL,
        'error' => 'A warming job for this sitemap URL is already queued or running.',
      ];
    }

    // Validate targets.
    $targets = array_values(array_intersect($targets, self::ALLOWED_TARGETS));
    if (empty($targets)) {
      $targets = self::ALLOWED_TARGETS;
    }

    $job = $this->database->insertJob($sitemapUrl, $targets, $sitemapId);

    $this->logger->info('Created warming job @id for @url', [
      '@id' => $job->id,
      '@url' => $sitemapUrl,
    ]);

    return [
      'jobId' => $job->id,
      'status' => $job->status,
      'sitemapUrl' => $sitemapUrl,
      'targets' => $targets,
      'createdAt' => $job->created_at,
    ];
  }

  /**
   * Processes a warming job.
   */
  public function processJob(string $jobId): void {
    $job = $this->database->getJob($jobId);
    if (!$job) {
      $this->logger->error('Job @id not found', ['@id' => $jobId]);
      return;
    }

    if ($job->status !== 'queued') {
      $this->logger->warning('Job @id is not queued (status: @status)', [
        '@id' => $jobId,
        '@status' => $job->status,
      ]);
      return;
    }

    // Extend execution limits.
    if (function_exists('set_time_limit')) {
      @set_time_limit(0);
    }
    if (function_exists('ini_set')) {
      @ini_set('memory_limit', '512M');
    }

    $this->database->updateJob($jobId, [
      'status' => 'running',
      'started_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ]);

    try {
      // Parse sitemap.
      $entries = $this->sitemapParser->parse($job->sitemap_url);

      // Priority-based warming (Premium+).
      $licenseService = \Drupal::service('cachewarmer.license');
      if ($licenseService->can('priority_warming')) {
        usort($entries, function ($a, $b) {
          $pa = isset($a['priority']) ? (float) $a['priority'] : 0.5;
          $pb = isset($b['priority']) ? (float) $b['priority'] : 0.5;
          return $pb <=> $pa;
        });
      }

      $urls = array_map(fn($e) => $e['loc'], $entries);

      // Apply URL exclude patterns (Enterprise only).
      $config = $this->configFactory->get('cachewarmer.settings');
      /** @var \Drupal\cachewarmer\Service\CacheWarmerLicense $licenseService */
      $licenseService = \Drupal::service('cachewarmer.license');
      $excludeRaw = $licenseService->isEnterprise() ? ($config->get('exclude_patterns') ?? '') : '';
      if (!empty(trim($excludeRaw))) {
        $patterns = array_filter(array_map('trim', explode("\n", $excludeRaw)));
        $beforeCount = count($urls);
        $urls = array_values(array_filter($urls, function (string $url) use ($patterns) {
          foreach ($patterns as $pattern) {
            if (str_contains($url, $pattern)) {
              return FALSE;
            }
          }
          return TRUE;
        }));
        if (count($urls) < $beforeCount) {
          $this->logger->info('Excluded @count URLs by patterns for job @id', [
            '@count' => $beforeCount - count($urls),
            '@id' => $jobId,
          ]);
        }
      }

      $this->database->updateJob($jobId, [
        'total_urls' => count($urls),
      ]);

      if (empty($urls)) {
        $this->database->updateJob($jobId, [
          'status' => 'completed',
          'completed_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
        return;
      }

      $targets = json_decode($job->targets, TRUE) ?: [];
      $processedCount = 0;

      $this->webhooks->notify('job.started', [
        'jobId' => $jobId,
        'sitemapUrl' => $job->sitemap_url,
        'urlCount' => count($urls),
        'targets' => $targets,
      ]);

      // CDN purge runs before every warming phase, regardless of where the
      // caller put it in the targets array. Purging afterwards would discard
      // the cache the job just built.
      if (in_array('cdn-purge', $targets, TRUE) && $this->isTargetEnabled('cdn-purge', $config)) {
        $purgeOnResult = function (string $url, string $status, ?int $httpStatus, int $durationMs, ?string $error) use ($jobId, &$processedCount) {
          $this->database->insertUrlResult($jobId, $url, 'cdn-purge', $status, $httpStatus, $durationMs, $error);
          $processedCount++;
          $this->database->updateJob($jobId, ['processed_urls' => $processedCount]);
        };

        $purgeResults = $this->cdnPurgeWarmer->purge($urls, $jobId, $purgeOnResult);

        // Akamai reports how long invalidation takes to propagate. Clamped,
        // because the value comes from a third party and an implausible one
        // would otherwise stall the job.
        $propagation = 0;
        foreach ($purgeResults as $purgeResult) {
          $propagation = max($propagation, (int) ($purgeResult['estimatedSeconds'] ?? 0));
        }
        $propagation = min($propagation, self::MAX_PROPAGATION_WAIT_SECONDS);
        if ($propagation > 0) {
          $this->logger->info('Waiting @seconds s for CDN purge to propagate before warming job @id', [
            '@seconds' => $propagation,
            '@id' => $jobId,
          ]);
          sleep($propagation);
        }
      }

      // Process the remaining targets.
      foreach ($targets as $target) {
        if ($target === 'cdn-purge') {
          // Already handled above.
          continue;
        }
        if (!$this->isTargetEnabled($target, $config)) {
          continue;
        }

        // The CDN warmer supplies a viewport and cache headers; the other
        // targets do not. Both were previously absent from this signature, so
        // PHP discarded them and the cache headers never reached the database.
        $targetOnResult = function (string $url, string $status, ?int $httpStatus, int $durationMs, ?string $error, ?string $viewport = NULL, ?array $cacheHeaders = NULL) use ($jobId, $target, &$processedCount) {
          $this->database->insertUrlResult($jobId, $url, $target, $status, $httpStatus, $durationMs, $error, $viewport, $cacheHeaders);
          $processedCount++;
          $this->database->updateJob($jobId, [
            'processed_urls' => $processedCount,
          ]);
        };

        $this->processTarget($target, $urls, $jobId, $targetOnResult);
      }

      $this->database->updateJob($jobId, [
        'status' => 'completed',
        'completed_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'processed_urls' => $processedCount,
      ]);

      // Update sitemap last_warmed_at if linked.
      if (!empty($job->sitemap_id)) {
        $this->database->updateSitemapLastWarmed($job->sitemap_id);
      }

      $this->logger->info('Completed warming job @id: @count results', [
        '@id' => $jobId,
        '@count' => $processedCount,
      ]);

      // Send completion notifications.
      $jobData = [
        'id' => $jobId,
        'status' => 'completed',
        'sitemap_url' => $job->sitemap_url,
        'total_urls' => count($urls),
        'processed_urls' => $processedCount,
      ];
      $this->webhooks->notify('job.completed', $jobData);
      $this->email->sendJobCompleted($jobData);
    }
    catch (\Exception $e) {
      $this->database->updateJob($jobId, [
        'status' => 'failed',
        'completed_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'error' => $e->getMessage(),
      ]);
      $this->logger->error('Job @id failed: @error', [
        '@id' => $jobId,
        '@error' => $e->getMessage(),
      ]);

      // Send failure notifications.
      $jobData = [
        'id' => $jobId,
        'status' => 'failed',
        'sitemap_url' => $job->sitemap_url,
        'total_urls' => 0,
        'processed_urls' => 0,
        'error' => $e->getMessage(),
      ];
      $this->webhooks->notify('job.failed', $jobData);
      $this->email->sendJobCompleted($jobData);
    }
  }

  /**
   * Checks if a target is enabled in config.
   */
  protected function isTargetEnabled(string $target, $config): bool {
    if ($target === 'cdn-purge') {
      // Active as soon as one provider is switched on; the generic
      // "<target>.enabled" lookup cannot express that.
      $anyProvider = (bool) $config->get('cloudflare.enabled')
        || (bool) $config->get('imperva.enabled')
        || (bool) $config->get('akamai.enabled');
      if (!$anyProvider) {
        return FALSE;
      }
      // isTargetAllowed() is otherwise never called in this module, so without
      // this check "Enterprise only" would be a claim rather than a rule.
      return \Drupal::service('cachewarmer.license')->isTargetAllowed('cdn-purge');
    }

    return (bool) $config->get("{$target}.enabled");
  }

  /**
   * Dispatches warming to the appropriate service.
   */
  protected function processTarget(string $target, array $urls, string $jobId, callable $onResult): void {
    switch ($target) {
      case 'cdn':
        $this->cdnWarmer->warm($urls, $jobId, $onResult);
        break;

      case 'facebook':
        $this->facebookWarmer->warm($urls, $jobId, $onResult);
        break;

      case 'linkedin':
        $this->linkedinWarmer->warm($urls, $jobId, $onResult);
        break;

      case 'twitter':
        $this->twitterWarmer->warm($urls, $jobId, $onResult);
        break;

      case 'google':
        $this->googleIndexer->index($urls, $jobId, $onResult);
        break;

      case 'bing':
        $this->bingIndexer->index($urls, $jobId, $onResult);
        break;

      case 'indexnow':
        $this->indexNow->index($urls, $jobId, $onResult);
        break;

      case 'pinterest':
        $this->pinterestWarmer->warm($urls, $jobId, $onResult);
        break;

      case 'cdn-purge':
        $this->cdnPurgeWarmer->purge($urls, $jobId, $onResult);
        break;
    }
  }

  /**
   * Gets a job with aggregated stats.
   */
  public function getJobWithStats(string $jobId): ?array {
    $job = $this->database->getJob($jobId);
    if (!$job) {
      return NULL;
    }

    $stats = $this->database->getJobStats($jobId);

    return [
      'id' => $job->id,
      'sitemap_id' => $job->sitemap_id,
      'sitemap_url' => $job->sitemap_url,
      'status' => $job->status,
      'total_urls' => (int) $job->total_urls,
      'processed_urls' => (int) $job->processed_urls,
      'targets' => json_decode($job->targets, TRUE),
      'started_at' => $job->started_at,
      'completed_at' => $job->completed_at,
      'error' => $job->error,
      'created_at' => $job->created_at,
      'stats' => $stats,
    ];
  }

}
