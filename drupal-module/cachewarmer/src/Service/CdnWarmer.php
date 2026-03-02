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
  protected CacheWarmerLicense $licenseService;

  protected const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
  protected const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, CacheWarmerLicense $licenseService) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->licenseService = $licenseService;
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

    // Custom user agent (Enterprise).
    $desktopUa = self::DESKTOP_UA;
    $mobileUa = self::MOBILE_UA;
    if ($this->licenseService->can('custom_user_agent')) {
      $customUa = $config->get('cdn.custom_user_agent');
      if (!empty($customUa)) {
        $desktopUa = $customUa;
        $mobileUa = $customUa;
      }
    }

    // Build viewports list.
    $viewports = [
      ['ua' => $desktopUa, 'viewport' => 'desktop'],
      ['ua' => $mobileUa, 'viewport' => 'mobile'],
    ];

    // Custom viewports (Enterprise).
    if ($this->licenseService->can('custom_viewports')) {
      $customViewports = $config->get('cdn.custom_viewports');
      if (!empty($customViewports) && is_array($customViewports)) {
        foreach ($customViewports as $cvp) {
          $vpName = $cvp['name'] ?? 'custom';
          $vpUa = $cvp['user_agent'] ?? $desktopUa;
          $viewports[] = ['ua' => $vpUa, 'viewport' => $vpName];
        }
      }
    }

    // Custom headers (Enterprise).
    $customHeaders = [];
    if ($this->licenseService->can('custom_headers')) {
      $rawHeaders = $config->get('cdn.custom_headers');
      if (!empty($rawHeaders) && is_array($rawHeaders)) {
        $customHeaders = $rawHeaders;
      }
    }

    // Authenticated warming (Enterprise): Cookie header.
    $authCookies = '';
    if ($this->licenseService->can('authenticated_warming')) {
      $authCookies = $config->get('cdn.auth_cookies') ?: '';
    }

    foreach ($urls as $url) {
      foreach ($viewports as $vp) {
        $start = microtime(TRUE);
        try {
          $headers = array_merge(['User-Agent' => $vp['ua']], $customHeaders);
          if (!empty($authCookies)) {
            $headers['Cookie'] = $authCookies;
          }

          $response = $this->httpClient->request('GET', $url, [
            'timeout' => $timeout,
            'headers' => $headers,
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
