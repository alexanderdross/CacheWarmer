<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Warms LinkedIn cache via the Post Inspector API.
 */
class LinkedinWarmer {

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
    $sessionCookie = $config->get('linkedin.session_cookie');

    if (empty($sessionCookie)) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'LinkedIn not configured');
        }
      }
      return;
    }

    $delay = (int) ($config->get('linkedin.delay') ?: 5000);

    foreach ($urls as $url) {
      $start = microtime(TRUE);
      try {
        $response = $this->httpClient->request('POST', 'https://www.linkedin.com/post-inspector/api/inspect', [
          'headers' => [
            'Cookie' => 'li_at=' . $sessionCookie,
            'csrf-token' => 'ajax:0',
            'X-Li-Lang' => 'en_US',
            'X-Restli-Protocol-Version' => '2.0.0',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
          ],
          'form_params' => [
            'url' => $url,
          ],
          'timeout' => 15,
          'http_errors' => FALSE,
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

      usleep($delay * 1000);
    }
  }

}
