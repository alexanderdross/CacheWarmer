<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Submits URLs to Google via the Indexing API.
 */
class GoogleIndexer {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
  }

  /**
   * Indexes the given URLs.
   */
  public function index(array $urls, string $jobId, ?callable $onResult = NULL): void {
    $config = $this->configFactory->get('cachewarmer.settings');
    $serviceAccountJson = $config->get('google.service_account_json');
    $dailyQuota = (int) ($config->get('google.daily_quota') ?: 200);

    if (empty($serviceAccountJson)) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'Google not configured');
        }
      }
      return;
    }

    $credentials = json_decode($serviceAccountJson, TRUE);
    if (!$credentials || empty($credentials['client_email']) || empty($credentials['private_key'])) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'failed', NULL, 0, 'Invalid service account JSON');
        }
      }
      return;
    }

    $accessToken = $this->getAccessToken($credentials);
    if (!$accessToken) {
      foreach ($urls as $url) {
        if ($onResult) {
          $onResult($url, 'failed', NULL, 0, 'Failed to obtain access token');
        }
      }
      return;
    }

    $processed = 0;
    foreach ($urls as $url) {
      if ($processed >= $dailyQuota) {
        if ($onResult) {
          $onResult($url, 'skipped', NULL, 0, 'Daily quota exceeded');
        }
        continue;
      }

      $start = microtime(TRUE);
      try {
        $response = $this->httpClient->request('POST', 'https://indexing.googleapis.com/v3/urlNotifications:publish', [
          'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
          ],
          'json' => [
            'url' => $url,
            'type' => 'URL_UPDATED',
          ],
          'timeout' => 15,
          'http_errors' => FALSE,
        ]);

        $statusCode = $response->getStatusCode();
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $status = ($statusCode >= 200 && $statusCode < 300) ? 'success' : 'failed';

        if ($onResult) {
          $onResult($url, $status, $statusCode, $durationMs, $status === 'failed' ? "HTTP {$statusCode}" : NULL);
        }

        $processed++;
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        if ($onResult) {
          $onResult($url, 'failed', NULL, $durationMs, $e->getMessage());
        }
      }

      usleep(100000); // 100ms delay between requests.
    }
  }

  /**
   * Obtains an OAuth2 access token using the service account credentials.
   */
  protected function getAccessToken(array $credentials): ?string {
    $now = time();
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $claim = json_encode([
      'iss' => $credentials['client_email'],
      'scope' => 'https://www.googleapis.com/auth/indexing',
      'aud' => 'https://oauth2.googleapis.com/token',
      'exp' => $now + 3600,
      'iat' => $now,
    ]);

    $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $base64Claim = rtrim(strtr(base64_encode($claim), '+/', '-_'), '=');
    $signatureInput = $base64Header . '.' . $base64Claim;

    $privateKey = openssl_pkey_get_private($credentials['private_key']);
    if (!$privateKey) {
      return NULL;
    }

    $signature = '';
    if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
      return NULL;
    }

    $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $jwt = $signatureInput . '.' . $base64Signature;

    try {
      $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
        'form_params' => [
          'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
          'assertion' => $jwt,
        ],
        'timeout' => 10,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      return $body['access_token'] ?? NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
