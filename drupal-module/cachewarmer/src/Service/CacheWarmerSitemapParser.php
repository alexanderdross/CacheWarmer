<?php

namespace Drupal\cachewarmer\Service;

use GuzzleHttp\ClientInterface;

/**
 * Parses XML sitemaps, including sitemap indexes.
 */
class CacheWarmerSitemapParser {

  /**
   * Maximum recursion depth for sitemap indexes.
   */
  protected const MAX_DEPTH = 3;

  /**
   * The HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * Constructs the CacheWarmerSitemapParser service.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * Parses a sitemap URL and returns all URLs found.
   *
   * @param string $url
   *   The sitemap URL to parse.
   *
   * @return array
   *   Array of associative arrays with 'loc', 'lastmod', 'priority', 'changefreq'.
   */
  public function parse(string $url): array {
    $urls = [];
    $this->parseRecursive($url, $urls, 0);

    // Deduplicate by loc.
    $seen = [];
    $unique = [];
    foreach ($urls as $entry) {
      if (!isset($seen[$entry['loc']])) {
        $seen[$entry['loc']] = TRUE;
        $unique[] = $entry;
      }
    }

    return $unique;
  }

  /**
   * Recursively parses sitemaps.
   */
  protected function parseRecursive(string $url, array &$urls, int $depth): void {
    if ($depth > self::MAX_DEPTH) {
      return;
    }

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 30,
        'headers' => [
          'User-Agent' => 'CacheWarmer/1.0 Sitemap Parser',
          'Accept' => 'application/xml, text/xml',
        ],
      ]);

      $body = (string) $response->getBody();
      if (empty($body)) {
        return;
      }

      $previousUseErrors = libxml_use_internal_errors(TRUE);
      $xml = simplexml_load_string($body);

      if ($xml === FALSE) {
        libxml_use_internal_errors($previousUseErrors);
        return;
      }

      // Register namespaces.
      $namespaces = $xml->getNamespaces(TRUE);
      $defaultNs = $namespaces[''] ?? 'http://www.sitemaps.org/schemas/sitemap/0.9';

      $xml->registerXPathNamespace('sm', $defaultNs);

      // Check if this is a sitemap index.
      $sitemaps = $xml->xpath('//sm:sitemapindex/sm:sitemap/sm:loc');
      if (!empty($sitemaps)) {
        foreach ($sitemaps as $sitemapLoc) {
          $childUrl = trim((string) $sitemapLoc);
          if (filter_var($childUrl, FILTER_VALIDATE_URL)) {
            $this->parseRecursive($childUrl, $urls, $depth + 1);
          }
        }
        libxml_use_internal_errors($previousUseErrors);
        return;
      }

      // Parse as urlset.
      $urlElements = $xml->xpath('//sm:urlset/sm:url');
      if (!empty($urlElements)) {
        foreach ($urlElements as $urlElement) {
          $loc = trim((string) ($urlElement->loc ?? ''));
          if (empty($loc) || !filter_var($loc, FILTER_VALIDATE_URL)) {
            continue;
          }

          $entry = ['loc' => $loc];

          $lastmod = (string) ($urlElement->lastmod ?? '');
          if (!empty($lastmod)) {
            $entry['lastmod'] = $lastmod;
          }

          $priority = (string) ($urlElement->priority ?? '');
          if (!empty($priority)) {
            $entry['priority'] = (float) $priority;
          }

          $changefreq = (string) ($urlElement->changefreq ?? '');
          if (!empty($changefreq)) {
            $entry['changefreq'] = $changefreq;
          }

          $urls[] = $entry;
        }
      }

      libxml_use_internal_errors($previousUseErrors);
    }
    catch (\Exception $e) {
      // Log error but don't break the entire parsing.
      \Drupal::logger('cachewarmer')->error('Failed to parse sitemap @url: @error', [
        '@url' => $url,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
