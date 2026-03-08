<?php

namespace Drupal\cachewarmer\Service;

use GuzzleHttp\ClientInterface;

/**
 * Auto-detects sitemap URLs on the current site.
 */
class CacheWarmerSitemapDetector {

  /**
   * The HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * Constructs the CacheWarmerSitemapDetector service.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Detects available sitemap URLs on the current site.
   *
   * @return array
   *   Array of discovered sitemap URLs.
   */
  public function detect(): array {
    $found = [];
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    $candidates = [
      $base_url . '/sitemap.xml',
      $base_url . '/sitemap_index.xml',
      $base_url . '/default/sitemap.xml',
    ];

    foreach (array_unique($candidates) as $url) {
      try {
        $response = $this->httpClient->request('HEAD', $url, [
          'timeout' => 5,
          'http_errors' => FALSE,
          'verify' => FALSE,
        ]);
        if ($response->getStatusCode() === 200) {
          $contentType = $response->getHeaderLine('Content-Type');
          if (strpos($contentType, 'xml') !== FALSE || strpos($contentType, 'text') !== FALSE) {
            $found[] = $url;
          }
        }
      }
      catch (\Exception $e) {
        // Skip unreachable URLs.
      }
    }

    return $found;
  }

}
