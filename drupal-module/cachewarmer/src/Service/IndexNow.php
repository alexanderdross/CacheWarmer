<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Submits URLs via the IndexNow protocol (Bing, Yandex, Seznam, Naver).
 */
class IndexNow {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  protected const BATCH_SIZE = 10000;

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Indexes the given URLs.
   */
  public function index(array $urls, string $jobId, ?callable $onResult = NULL): void {
    $config = $this->configFactory->get('cachewarmer.settings');
    $key = $config->get('indexnow.key');

    if (empty($key)) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'IndexNow not configured');
        }
      }
      return;
    }

    $keyLocation = $config->get('indexnow.key_location');
    $batches = array_chunk($urls, self::BATCH_SIZE);

    foreach ($batches as $batch) {
      // Extract host from first URL.
      $parsed = parse_url($batch[0]);
      $host = $parsed['host'] ?? '';

      $payload = [
        'host' => $host,
        'key' => $key,
        'urlList' => $batch,
      ];

      if (!empty($keyLocation)) {
        $payload['keyLocation'] = $keyLocation;
      }

      $start = microtime(TRUE);
      try {
        $response = $this->httpClient->request('POST', 'https://api.indexnow.org/indexnow', [
          'json' => $payload,
          'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
          'timeout' => 30,
          'http_errors' => FALSE,
        ]);

        $statusCode = $response->getStatusCode();
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $status = ($statusCode === 200 || $statusCode === 202) ? 'success' : 'failed';
        $error = $status === 'failed' ? "HTTP {$statusCode}" : NULL;

        foreach ($batch as $url) {
          if ($onResult) {
            $onResult($url, $status, $statusCode, $durationMs, $error);
          }
        }
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        foreach ($batch as $url) {
          if ($onResult) {
            $onResult($url, 'failed', NULL, $durationMs, $e->getMessage());
          }
        }
      }
    }
  }

}
