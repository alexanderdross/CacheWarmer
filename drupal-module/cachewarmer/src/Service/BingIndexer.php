<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Submits URLs to Bing via the Webmaster Tools API.
 */
class BingIndexer {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  protected const BATCH_SIZE = 500;

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Indexes the given URLs.
   */
  public function index(array $urls, string $jobId, ?callable $onResult = NULL): void {
    $config = $this->configFactory->get('cachewarmer.settings');
    $apiKey = $config->get('bing.api_key');
    $dailyQuota = (int) ($config->get('bing.daily_quota') ?: 10000);

    if (empty($apiKey)) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'Bing not configured');
        }
      }
      return;
    }

    $batches = array_chunk($urls, self::BATCH_SIZE);
    $submitted = 0;

    foreach ($batches as $batch) {
      $remainingQuota = $dailyQuota - $submitted;
      if ($remainingQuota <= 0) {
        foreach ($batch as $url) {
          if ($onResult) {
            $onResult($url, 'skipped', NULL, 0, 'Daily quota exceeded');
          }
        }
        continue;
      }

      $batchToSubmit = array_slice($batch, 0, $remainingQuota);
      $skipped = array_slice($batch, $remainingQuota);

      // Extract site URL from first URL.
      $parsed = parse_url($batchToSubmit[0]);
      $siteUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

      $start = microtime(TRUE);
      try {
        $response = $this->httpClient->request('POST', 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch', [
          'query' => ['apikey' => $apiKey],
          'json' => [
            'siteUrl' => $siteUrl,
            'urlList' => $batchToSubmit,
          ],
          'timeout' => 30,
          'http_errors' => FALSE,
        ]);

        $statusCode = $response->getStatusCode();
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $status = ($statusCode >= 200 && $statusCode < 300) ? 'success' : 'failed';
        $error = $status === 'failed' ? "HTTP {$statusCode}" : NULL;

        foreach ($batchToSubmit as $url) {
          if ($onResult) {
            $onResult($url, $status, $statusCode, $durationMs, $error);
          }
        }

        $submitted += count($batchToSubmit);
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        foreach ($batchToSubmit as $url) {
          if ($onResult) {
            $onResult($url, 'failed', NULL, $durationMs, $e->getMessage());
          }
        }
      }

      foreach ($skipped as $url) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'Daily quota exceeded');
        }
      }
    }
  }

}
