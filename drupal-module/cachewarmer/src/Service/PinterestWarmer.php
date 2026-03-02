<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Pinterest Rich Pin Validator warming service.
 *
 * Triggers Pinterest's rich pin scraper to refresh OG meta cache.
 */
class PinterestWarmer {

  protected ConfigFactoryInterface $configFactory;
  protected ClientInterface $httpClient;

  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * Warms URLs via Pinterest Rich Pin Validator.
   *
   * @param array $urls
   *   Array of URL strings.
   * @param string $jobId
   *   Job ID for tracking.
   * @param callable|null $onResult
   *   Callback per URL result: function(string $url, string $status, ?int $httpStatus, int $durationMs, ?string $error).
   */
  public function warm(array $urls, string $jobId, ?callable $onResult = NULL): void {
    foreach ($urls as $url) {
      $start = microtime(TRUE);

      try {
        $response = $this->httpClient->request('GET', 'https://developers.pinterest.com/tools/url-debugger/', [
          'query' => ['link' => $url],
          'timeout' => 30,
          'headers' => [
            'User-Agent' => 'Mozilla/5.0 (compatible; CacheWarmer/1.0)',
          ],
        ]);

        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $httpStatus = $response->getStatusCode();
        $status = ($httpStatus >= 200 && $httpStatus < 400) ? 'success' : 'failed';
        $error = ($httpStatus >= 400) ? "HTTP {$httpStatus}" : NULL;

        if ($onResult) {
          $onResult($url, $status, $httpStatus, $durationMs, $error);
        }
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        if ($onResult) {
          $onResult($url, 'failed', NULL, $durationMs, $e->getMessage());
        }
      }

      usleep(2000000); // 2s delay
    }
  }

}
