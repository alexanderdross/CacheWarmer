<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Warms CDN edge caches by requesting URLs with desktop and mobile user agents.
 *
 * Requests within a pass run concurrently, and the desktop pass doubles as the
 * fill while the mobile pass acts as the probe that proves the fill landed.
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

    // Base user agents: the plain setting applies to every tier.
    $desktopUa = $config->get('cdn.user_agent') ?: self::DESKTOP_UA;
    $mobileUa = self::MOBILE_UA;

    // Custom user agent (Enterprise) overrides both passes.
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

    // Custom viewports (Enterprise): one "Label|User-Agent" per line.
    if ($this->licenseService->can('custom_viewports')) {
      foreach (self::parseLines($config->get('cdn.custom_viewports')) as $line) {
        [$vpName, $vpUa] = array_pad(explode('|', $line, 2), 2, NULL);
        $vpName = trim((string) $vpName);
        if ($vpName === '') {
          continue;
        }
        $viewports[] = [
          'ua' => trim((string) $vpUa) ?: $desktopUa,
          'viewport' => $vpName,
        ];
      }
    }

    // Custom headers (Enterprise): one "Header: Value" per line.
    $customHeaders = [];
    if ($this->licenseService->can('custom_headers')) {
      foreach (self::parseLines($config->get('cdn.custom_headers')) as $line) {
        if (!str_contains($line, ':')) {
          continue;
        }
        [$name, $value] = explode(':', $line, 2);
        $name = trim($name);
        if ($name !== '') {
          $customHeaders[$name] = trim($value);
        }
      }
    }

    // Authenticated warming (Enterprise): Cookie header.
    $authCookies = '';
    if ($this->licenseService->can('authenticated_warming')) {
      $authCookies = self::cookieHeader($config->get('cdn.auth_cookies'));
    }

    $concurrency = max(1, (int) ($config->get('cdn.concurrency') ?: 3));

    // One round per pass, URLs inside a round running together. Parallelising
    // across passes instead would race the fill against the probe.
    foreach (array_chunk($urls, $concurrency) as $batch) {
      $fills = [];

      foreach ($viewports as $index => $vp) {
        $headers = array_merge(['User-Agent' => $vp['ua']], $customHeaders);
        if (!empty($authCookies)) {
          $headers['Cookie'] = $authCookies;
        }

        $observations = $this->requestBatch($batch, $headers, $timeout, $concurrency);

        foreach ($batch as $url) {
          $observation = $observations[$url];

          // The first pass fills the cache; every later pass can be compared
          // against it to see whether the fill actually landed.
          if ($index === 0) {
            $fills[$url] = $observation['cacheHeaders'];
          }
          elseif (!empty($observation['cacheHeaders'])) {
            $verdict = self::classifyVerdict(
              $fills[$url] ?? [],
              $observation['cacheHeaders'],
              $observation['cacheHeaders']['vary'] ?? NULL
            );
            $observation['cacheHeaders']['verdict'] = $verdict['verdict'];
            if (isset($verdict['reason'])) {
              $observation['cacheHeaders']['verdictReason'] = $verdict['reason'];
            }
          }

          if ($onResult) {
            $onResult(
              $url,
              $observation['status'],
              $observation['httpStatus'],
              $observation['durationMs'],
              $observation['error'],
              $vp['viewport'],
              !empty($observation['cacheHeaders']) ? $observation['cacheHeaders'] : NULL
            );
          }
        }
      }
    }
  }

  /**
   * Split a multi-line setting into trimmed, non-empty lines.
   */
  protected static function parseLines($raw): array {
    if (!is_string($raw) || trim($raw) === '') {
      return [];
    }
    return array_values(array_filter(array_map('trim', explode("\n", $raw)), static fn($l) => $l !== ''));
  }

  /**
   * Build a Cookie header from the authenticated-warming setting.
   *
   * Accepts the WordPress edition's JSON form — [{"name":…,"value":…}] — so a
   * value can be moved between the two without editing, and falls back to
   * treating the setting as a ready-made Cookie header.
   */
  public static function cookieHeader($raw): string {
    if (!is_string($raw) || trim($raw) === '') {
      return '';
    }
    $raw = trim($raw);

    $decoded = json_decode($raw, TRUE);
    if (is_array($decoded)) {
      $pairs = [];
      foreach ($decoded as $cookie) {
        if (is_array($cookie) && isset($cookie['name'], $cookie['value'])) {
          $pairs[] = $cookie['name'] . '=' . $cookie['value'];
        }
      }
      return implode('; ', $pairs);
    }

    return $raw;
  }

  /**
   * Issue one request per URL concurrently and collect the observations.
   *
   * @return array
   *   Keyed by URL: status, httpStatus, durationMs, error, cacheHeaders.
   */
  protected function requestBatch(array $urls, array $headers, int $timeout, int $concurrency): array {
    $start = microtime(TRUE);
    $results = [];

    $requests = function () use ($urls, $headers) {
      foreach ($urls as $url) {
        yield $url => new Request('GET', $url, $headers);
      }
    };

    $pool = new Pool($this->httpClient, $requests(), [
      'concurrency' => $concurrency,
      'options' => [
        'timeout' => $timeout,
        'http_errors' => FALSE,
      ],
      'fulfilled' => function (ResponseInterface $response, string $url) use (&$results, $start) {
        $statusCode = $response->getStatusCode();
        $results[$url] = [
          'status' => $statusCode < 400 ? 'success' : 'failed',
          'httpStatus' => $statusCode,
          'durationMs' => (int) ((microtime(TRUE) - $start) * 1000),
          'error' => $statusCode >= 400 ? "HTTP {$statusCode}" : NULL,
          'cacheHeaders' => array_filter([
            'xCache' => $response->getHeaderLine('x-cache') ?: NULL,
            'cfCacheStatus' => $response->getHeaderLine('cf-cache-status') ?: NULL,
            'age' => $response->getHeaderLine('age') ?: NULL,
            'cacheControl' => $response->getHeaderLine('cache-control') ?: NULL,
            'vary' => $response->getHeaderLine('vary') ?: NULL,
          ]),
        ];
      },
      'rejected' => function ($reason, string $url) use (&$results, $start) {
        $results[$url] = [
          'status' => 'failed',
          'httpStatus' => NULL,
          'durationMs' => (int) ((microtime(TRUE) - $start) * 1000),
          'error' => $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason,
          'cacheHeaders' => [],
        ];
      },
    ]);

    $pool->promise()->wait();

    // A URL the pool never reported on must still produce a row, or the job's
    // progress count silently drifts from the URL count.
    foreach ($urls as $url) {
      if (!isset($results[$url])) {
        $results[$url] = [
          'status' => 'failed',
          'httpStatus' => NULL,
          'durationMs' => (int) ((microtime(TRUE) - $start) * 1000),
          'error' => 'No response recorded for this URL',
          'cacheHeaders' => [],
        ];
      }
    }

    return $results;
  }

  /**
   * Reduce a CDN cache header to a coarse state.
   */
  protected static function cacheState(array $headers): string {
    // Fastly reports both tiers comma-separated ("MISS, HIT"); only the last
    // segment describes the edge that answered us.
    $source = $headers['cfCacheStatus'] ?? $headers['xCache'] ?? '';
    $parts = explode(',', $source);
    $raw = strtoupper(trim((string) end($parts)));
    if ($raw === '') {
      return (int) ($headers['age'] ?? 0) > 0 ? 'hit' : 'unknown';
    }
    if (str_contains($raw, 'BYPASS')) {
      return 'bypass';
    }
    if (str_contains($raw, 'DYNAMIC')) {
      return 'dynamic';
    }
    // REVALIDATED, STALE and UPDATING are all served from cache.
    if (str_contains($raw, 'HIT') || str_contains($raw, 'REVALIDATED')
      || str_contains($raw, 'STALE') || str_contains($raw, 'UPDATING')) {
      return 'hit';
    }
    if (str_contains($raw, 'MISS') || str_contains($raw, 'EXPIRED')) {
      return 'miss';
    }
    return 'unknown';
  }

  /**
   * Judge a fill/probe pair.
   *
   * For a warmer a MISS on the fill is the success signal — it means the
   * request populated the cache. A HIT there means the cache was already warm
   * and the run changed nothing.
   *
   * @return array
   *   'verdict' and optionally 'reason'.
   */
  public static function classifyVerdict(array $fill, array $probe, ?string $probeVary = NULL): array {
    // The passes send different user agents. If the origin varies on that
    // header they address separate cache entries, so the pair proves nothing.
    if (!empty($probeVary) && stripos($probeVary, 'user-agent') !== FALSE) {
      return [
        'verdict' => 'indeterminate',
        'reason' => 'Origin sends Vary: User-Agent, so the two passes are separate cache entries',
      ];
    }

    $fillState = self::cacheState($fill);
    $probeState = self::cacheState($probe);

    if ($fillState === 'bypass' || $probeState === 'bypass') {
      return [
        'verdict' => 'bypassed',
        'reason' => $probe['cacheControl'] ?? 'Cache bypassed',
      ];
    }
    if ($probeState === 'dynamic') {
      return [
        'verdict' => 'zone_not_caching',
        'reason' => 'CDN reports the response as DYNAMIC',
      ];
    }
    if ($probeState === 'hit') {
      return ['verdict' => $fillState === 'hit' ? 'already_warm' : 'warmed'];
    }
    if ($probeState === 'miss') {
      $cacheControl = strtolower($probe['cacheControl'] ?? '');
      if (str_contains($cacheControl, 'no-store')) {
        return ['verdict' => 'not_cacheable', 'reason' => 'Cache-Control: no-store'];
      }
      if (str_contains($cacheControl, 'private')) {
        return ['verdict' => 'not_cacheable', 'reason' => 'Cache-Control: private'];
      }
      return ['verdict' => 'not_cacheable', 'reason' => 'Still a miss after the fill request'];
    }

    return ['verdict' => 'unknown'];
  }

}
