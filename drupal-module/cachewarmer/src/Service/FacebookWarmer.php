<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Warms Facebook OG cache via the Graph API scrape endpoint.
 */
class FacebookWarmer {

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
    $appId = $config->get('facebook.app_id');
    $appSecret = $config->get('facebook.app_secret');

    if (empty($appId) || empty($appSecret)) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'Facebook not configured');
        }
      }
      return;
    }

    $accessToken = $appId . '|' . $appSecret;
    $rateLimit = (int) ($config->get('facebook.rate_limit') ?: 10);
    $delayUs = $rateLimit > 0 ? (int) (1000000 / $rateLimit) : 100000;

    foreach ($urls as $url) {
      $start = microtime(TRUE);
      try {
        $response = $this->httpClient->request('POST', 'https://graph.facebook.com/v19.0/', [
          'form_params' => [
            'scrape' => 'true',
            'id' => $url,
            'access_token' => $accessToken,
          ],
          'timeout' => 15,
          'http_errors' => FALSE,
        ]);

        $statusCode = $response->getStatusCode();
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $body = json_decode((string) $response->getBody(), TRUE);

        if ($statusCode === 200 && !isset($body['error'])) {
          if ($onResult) {
            $onResult($url, 'success', $statusCode, $durationMs, NULL);
          }
        }
        else {
          $error = $body['error']['message'] ?? "HTTP {$statusCode}";
          if ($onResult) {
            $onResult($url, 'failed', $statusCode, $durationMs, $error);
          }
        }
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        if ($onResult) {
          $onResult($url, 'failed', NULL, $durationMs, $e->getMessage());
        }
      }

      usleep($delayUs);
    }
  }

}
