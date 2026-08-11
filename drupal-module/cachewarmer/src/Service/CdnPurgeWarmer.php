<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Purges CDN caches via provider APIs (Cloudflare, Imperva, Akamai).
 *
 * Runs before warming: purging afterwards would discard the cache the job just
 * built. Each provider is configured and licensed independently.
 */
class CdnPurgeWarmer {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected CacheWarmerLicense $licenseService;

  /**
   * Cloudflare accepts 100 operations per single-file purge request
   * (500 on Enterprise).
   */
  protected const CLOUDFLARE_BATCH_SIZE = 100;

  /**
   * Akamai Fast Purge accepts 50 objects per invalidation request.
   */
  protected const AKAMAI_BATCH_SIZE = 50;

  /**
   * Imperva purges one URL pattern per call; pace them to be polite.
   */
  protected const IMPERVA_DELAY_US = 200000;

  protected const TIMEOUT = 30;

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, CacheWarmerLicense $licenseService) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->licenseService = $licenseService;
  }

  /**
   * Purge the given URLs from every enabled and licensed provider.
   *
   * @param array $urls
   *   Array of URL strings.
   * @param string $jobId
   *   The job ID.
   * @param callable|null $onResult
   *   Callback: function(string $url, string $status, ?int $httpStatus, int $durationMs, ?string $error)
   *
   * @return array
   *   One entry per URL per provider, each with a 'provider' key and, where the
   *   provider reports one, an 'estimatedSeconds' propagation hint.
   */
  public function purge(array $urls, string $jobId, ?callable $onResult = NULL): array {
    $config = $this->configFactory->get('cachewarmer.settings');
    $results = [];
    $ran = FALSE;

    foreach (['cloudflare', 'imperva', 'akamai'] as $provider) {
      if (!$config->get("{$provider}.enabled")) {
        continue;
      }

      // Gating lives here, not in the settings form — there it is only a CSS
      // overlay and the fields still save.
      if (!$this->licenseService->can("{$provider}_integration")) {
        $results = array_merge($results, $this->skipAll(
          $urls,
          $provider,
          ucfirst($provider) . ' cache purge requires an Enterprise licence',
          $onResult
        ));
        $ran = TRUE;
        continue;
      }

      $method = 'purge' . ucfirst($provider);
      $results = array_merge($results, $this->{$method}($urls, $config, $onResult));
      $ran = TRUE;
    }

    // No provider enabled at all: still emit a row per URL, otherwise the job's
    // processed_urls counter drifts away from the URL count.
    if (!$ran) {
      $results = $this->skipAll($urls, 'cloudflare', 'No CDN purge provider is enabled', $onResult);
    }

    return $results;
  }

  /**
   * Emit a skipped result for every URL, with the reason.
   */
  protected function skipAll(array $urls, string $provider, string $reason, ?callable $onResult): array {
    $results = [];
    foreach ($urls as $url) {
      $results[] = [
        'url' => $url,
        'provider' => $provider,
        'status' => 'skipped',
        'httpStatus' => NULL,
        'durationMs' => 0,
        'error' => $reason,
      ];
      if ($onResult) {
        $onResult($url, 'skipped', NULL, 0, $reason);
      }
    }
    return $results;
  }

  /**
   * Record one outcome for every URL in a batch.
   */
  protected function recordBatch(array $batch, string $provider, string $status, ?int $httpStatus, int $durationMs, ?string $error, ?callable $onResult, array $extra = []): array {
    $results = [];
    foreach ($batch as $url) {
      $result = [
        'url' => $url,
        'provider' => $provider,
        'status' => $status,
        'httpStatus' => $httpStatus,
        'durationMs' => $durationMs,
        'error' => $error,
      ] + $extra;
      $results[] = $result;
      if ($onResult) {
        $onResult($url, $status, $httpStatus, $durationMs, $error);
      }
    }
    return $results;
  }

  // ─── Cloudflare ──────────────────────────────────────────────────

  protected function purgeCloudflare(array $urls, $config, ?callable $onResult): array {
    $apiToken = (string) ($config->get('cloudflare.api_token') ?: '');
    $zoneId = (string) ($config->get('cloudflare.zone_id') ?: '');

    if ($apiToken === '' || $zoneId === '') {
      return $this->skipAll($urls, 'cloudflare', 'Cloudflare purge not configured (API token or zone ID missing)', $onResult);
    }

    $endpoint = 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode($zoneId) . '/purge_cache';
    $results = [];

    foreach (array_chunk($urls, self::CLOUDFLARE_BATCH_SIZE) as $batch) {
      $start = microtime(TRUE);
      try {
        $response = $this->httpClient->request('POST', $endpoint, [
          'timeout' => self::TIMEOUT,
          'http_errors' => FALSE,
          'headers' => [
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type' => 'application/json',
          ],
          'body' => json_encode(['files' => array_values($batch)]),
        ]);

        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), TRUE);

        if (!empty($body['success'])) {
          $results = array_merge($results, $this->recordBatch($batch, 'cloudflare', 'success', $statusCode, $durationMs, NULL, $onResult));
        }
        else {
          $messages = array_filter(array_map(
            static fn($e) => $e['message'] ?? NULL,
            $body['errors'] ?? []
          ));
          $error = 'Cloudflare: ' . ($messages ? implode('; ', $messages) : "HTTP {$statusCode}");
          $results = array_merge($results, $this->recordBatch($batch, 'cloudflare', 'failed', $statusCode, $durationMs, $error, $onResult));
        }
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $results = array_merge($results, $this->recordBatch($batch, 'cloudflare', 'failed', NULL, $durationMs, 'Cloudflare: ' . $e->getMessage(), $onResult));
      }
    }

    return $results;
  }

  // ─── Imperva (Cloud WAF API v1) ──────────────────────────────────

  protected function purgeImperva(array $urls, $config, ?callable $onResult): array {
    $apiId = (string) ($config->get('imperva.api_id') ?: '');
    $apiKey = (string) ($config->get('imperva.api_key') ?: '');
    $siteId = (string) ($config->get('imperva.site_id') ?: '');

    if ($apiId === '' || $apiKey === '' || $siteId === '') {
      return $this->skipAll($urls, 'imperva', 'Imperva purge not configured (API ID, key or site ID missing)', $onResult);
    }

    $results = [];

    foreach ($urls as $index => $url) {
      $start = microtime(TRUE);
      try {
        // Imperva authenticates in the request body, not via headers.
        $response = $this->httpClient->request('POST', 'https://my.incapsula.com/api/prov/v1/sites/performance/purge', [
          'timeout' => self::TIMEOUT,
          'http_errors' => FALSE,
          'form_params' => [
            'api_id' => $apiId,
            'api_key' => $apiKey,
            'site_id' => $siteId,
            'purge_pattern' => $url,
          ],
        ]);

        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), TRUE);

        // Imperva signals success with res=0, not with the HTTP status.
        if (isset($body['res']) && 0 === (int) $body['res']) {
          $results = array_merge($results, $this->recordBatch([$url], 'imperva', 'success', $statusCode, $durationMs, NULL, $onResult));
        }
        else {
          $error = 'Imperva: ' . ($body['res_message'] ?? 'error code ' . ($body['res'] ?? 'unknown'));
          $results = array_merge($results, $this->recordBatch([$url], 'imperva', 'failed', $statusCode, $durationMs, $error, $onResult));
        }
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $results = array_merge($results, $this->recordBatch([$url], 'imperva', 'failed', NULL, $durationMs, 'Imperva: ' . $e->getMessage(), $onResult));
      }

      if ($index < count($urls) - 1) {
        usleep(self::IMPERVA_DELAY_US);
      }
    }

    return $results;
  }

  // ─── Akamai (Fast Purge API v3) ──────────────────────────────────

  protected function purgeAkamai(array $urls, $config, ?callable $onResult): array {
    $host = (string) ($config->get('akamai.host') ?: '');
    $clientToken = (string) ($config->get('akamai.client_token') ?: '');
    $clientSecret = (string) ($config->get('akamai.client_secret') ?: '');
    $accessToken = (string) ($config->get('akamai.access_token') ?: '');
    $network = (string) ($config->get('akamai.network') ?: 'production');

    if ($host === '' || $clientToken === '' || $clientSecret === '' || $accessToken === '') {
      return $this->skipAll($urls, 'akamai', 'Akamai purge not configured (EdgeGrid credentials incomplete)', $onResult);
    }

    $endpoint = 'https://' . $host . '/ccu/v3/invalidate/url/' . rawurlencode($network);
    $results = [];

    foreach (array_chunk($urls, self::AKAMAI_BATCH_SIZE) as $batch) {
      $start = microtime(TRUE);
      $bodyStr = json_encode(['objects' => array_values($batch)]);

      try {
        $response = $this->httpClient->request('POST', $endpoint, [
          'timeout' => self::TIMEOUT,
          'http_errors' => FALSE,
          'headers' => [
            'Authorization' => self::generateEdgeGridAuth('POST', $endpoint, $bodyStr, $clientToken, $clientSecret, $accessToken),
            'Content-Type' => 'application/json',
          ],
          'body' => $bodyStr,
        ]);

        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), TRUE);

        if ($statusCode >= 200 && $statusCode < 300) {
          // Akamai reports how long the invalidation takes to propagate. The
          // caller waits it out before warming, so the warm does not race the
          // purge.
          $extra = isset($body['estimatedSeconds'])
            ? ['estimatedSeconds' => (int) $body['estimatedSeconds']]
            : [];
          $results = array_merge($results, $this->recordBatch($batch, 'akamai', 'success', $statusCode, $durationMs, NULL, $onResult, $extra));
        }
        else {
          $error = 'Akamai: ' . ($body['detail'] ?? "HTTP {$statusCode}");
          $results = array_merge($results, $this->recordBatch($batch, 'akamai', 'failed', $statusCode, $durationMs, $error, $onResult));
        }
      }
      catch (\Exception $e) {
        $durationMs = (int) ((microtime(TRUE) - $start) * 1000);
        $results = array_merge($results, $this->recordBatch($batch, 'akamai', 'failed', NULL, $durationMs, 'Akamai: ' . $e->getMessage(), $onResult));
      }
    }

    return $results;
  }

  /**
   * Build an EG1-HMAC-SHA256 Authorization header.
   *
   * Two details in here have each broken one of the sibling implementations,
   * so both are spelled out:
   *
   * 1. The timestamp is yyyyMMddTHH:mm:ss+0000 — the date loses its hyphens
   *    but the time KEEPS its colons. The Node module stripped both and its
   *    signatures were rejected.
   * 2. The second HMAC is keyed on the base64 signing key AS A STRING, not on
   *    its decoded bytes. Akamai's reference clients pass the encoded string
   *    straight into HMAC. The WordPress plugin base64_decode()d it first and
   *    produced a different, invalid signature.
   *
   * Public and static so the test suite can compare it byte-for-byte against
   * the WordPress implementation.
   */
  public static function generateEdgeGridAuth(string $method, string $url, string $bodyStr, string $clientToken, string $clientSecret, string $accessToken, ?string $timestamp = NULL, ?string $nonce = NULL): string {
    $parsed = parse_url($url);

    $timestamp = $timestamp ?? gmdate('Ymd\TH:i:s+0000');
    $nonce = $nonce ?? self::uuid4();

    $authData = sprintf(
      'EG1-HMAC-SHA256 client_token=%s;access_token=%s;timestamp=%s;nonce=%s;',
      $clientToken,
      $accessToken,
      $timestamp,
      $nonce
    );

    // Content hash: Base64(SHA-256(POST body)), body truncated at 128 KiB.
    $contentHash = base64_encode(hash('sha256', substr($bodyStr, 0, 131072), TRUE));

    $pathQuery = ($parsed['path'] ?? '/') . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');

    $dataToSign = implode("\t", [
      strtoupper($method),
      'https',
      $parsed['host'] ?? '',
      $pathQuery,
      // Headers to sign — intentionally empty for Fast Purge.
      '',
      $contentHash,
      $authData,
    ]);

    $signingKey = base64_encode(hash_hmac('sha256', $timestamp, $clientSecret, TRUE));
    $signature = base64_encode(hash_hmac('sha256', $dataToSign, $signingKey, TRUE));

    return $authData . 'signature=' . $signature;
  }

  /**
   * RFC 4122 version 4 UUID, without depending on the Drupal container.
   */
  protected static function uuid4(): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
  }

}
