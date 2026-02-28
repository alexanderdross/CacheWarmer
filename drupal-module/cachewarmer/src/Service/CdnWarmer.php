<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Warms CDN edge caches by requesting URLs with desktop and mobile user agents.
 */
class CdnWarmer {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  protected const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
  protected const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Warms the given URLs.
   *
   * @param array $urls
   *   Array of URL strings.
   * @param string $jobId
   *   The job ID.
   * @param callable|null $onResult
   *   Callback: function(string $url, string $status, ?int $httpStatus, int $durationMs, ?string $error, string $viewport, ?array $cacheHeaders)
   */
  public function warm(array $urls, string $jobId, ?callable $onResult = NULL): void {
    $config = $this->configFactory->get('cachewarmer.settings');
    $timeout = (int) ($config->get('cdn.timeout') ?: 30);
    $viewports = [
      ['ua' => self::DESKTOP_UA, 'viewport' => 'desktop'],
      ['ua' => self::MOBILE_UA, 'viewport' => 'mobile'],
    ];

    foreach ($urls as $url) {
      foreach ($viewports as $vp) {
        $start = microtime(TRUE);
        try {
          $response = $this->httpClient->request('GET', $url, [
            'timeout' => $timeout,
            'headers' => ['User-Agent' => $vp['ua']],
            'http_errors' => FALSE,
          ]);

          $statusCode = $response->getStatusCode();
          $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
          $status = $statusCode < 400 ? 'success' : 'failed';

          $cacheHeaders = array_filter([
            'xCache' => $response->getHeaderLine('x-cache') ?: NULL,
            'cfCacheStatus' => $response->getHeaderLine('cf-cache-status') ?: NULL,
            'age' => $response->getHeaderLine('age') ?: NULL,
            'cacheControl' => $response->getHeaderLine('cache-control') ?: NULL,
          ]);

          if ($onResult) {
            $onResult($url, $status, $statusCode, $durationMs, $status === 'failed' ? "HTTP {$statusCode}" : NULL, $vp['viewport'], !empty($cacheHeaders) ? $cacheHeaders : NULL);
          }
        }
        catch (\Exception $e) {
          $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
          if ($onResult) {
            $onResult($url, 'failed', NULL, $durationMs, $e->getMessage(), $vp['viewport'], NULL);
          }
        }
      }
    }
  }

}
