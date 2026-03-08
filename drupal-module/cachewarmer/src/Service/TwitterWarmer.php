<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Warms Twitter/X card cache via the Tweet Composer endpoint.
 */
class TwitterWarmer {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Warms the given URLs.
   */
  public function warm(array $urls, string $jobId, ?callable $onResult = NULL): void {
    $config = $this->configFactory->get('cachewarmer.settings');
    $delay = (int) ($config->get('twitter.delay') ?: 3000);
    $concurrency = (int) ($config->get('twitter.concurrency') ?: 2);

    $batches = array_chunk($urls, $concurrency);

    foreach ($batches as $batch) {
      foreach ($batch as $url) {
        $start = microtime(TRUE);
        try {
          $composerUrl = 'https://twitter.com/intent/tweet?url=' . rawurlencode($url);
          $response = $this->httpClient->request('GET', $composerUrl, [
            'timeout' => 15,
            'headers' => [
              'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'http_errors' => FALSE,
            'allow_redirects' => TRUE,
          ]);

          $statusCode = $response->getStatusCode();
          $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
          $status = ($statusCode >= 200 && $statusCode < 400) ? 'success' : 'failed';

          if ($onResult) {
            $onResult($url, $status, $statusCode, $durationMs, $status === 'failed' ? "HTTP {$statusCode}" : NULL);
          }
        }
        catch (\Exception $e) {
          $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
          if ($onResult) {
            $onResult($url, 'failed', NULL, $durationMs, $e->getMessage());
          }
        }
      }

      usleep($delay * 1000);
    }
  }

}
