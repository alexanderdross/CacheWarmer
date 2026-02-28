<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends webhook notifications for CacheWarmer events.
 */
class CacheWarmerWebhooks {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * The license service.
   */
  protected CacheWarmerLicense $license;

  /**
   * Constructs the CacheWarmerWebhooks service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\cachewarmer\Service\CacheWarmerLicense $license
   *   The license service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, LoggerInterface $logger, CacheWarmerLicense $license) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->license = $license;
  }

  /**
   * Checks if a hostname resolves to a private/internal IP.
   */
  protected function isPrivateHost(string $host): bool {
    $blocked = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
    if (in_array(strtolower($host), $blocked, TRUE)) {
      return TRUE;
    }
    $ip = gethostbyname($host);
    if ($ip === $host) {
      return FALSE; // Could not resolve.
    }
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
  }

  /**
   * Sends a webhook notification.
   *
   * @param string $event
   *   The event name.
   * @param array $data
   *   The event data.
   */
  public function notify(string $event, array $data): void {
    if (!$this->license->isEnterprise()) {
      return;
    }

    $webhook_url = $this->configFactory->get('cachewarmer.settings')->get('webhook_url');
    if (empty($webhook_url) || !filter_var($webhook_url, FILTER_VALIDATE_URL)) {
      return;
    }

    // SSRF protection: block internal/private IPs.
    $host = parse_url($webhook_url, PHP_URL_HOST);
    if ($host && $this->isPrivateHost($host)) {
      $this->logger->warning('Webhook URL blocked (private/internal): @url', ['@url' => $webhook_url]);
      return;
    }

    try {
      $this->httpClient->request('POST', $webhook_url, [
        'json' => [
          'event' => $event,
          'timestamp' => gmdate('c'),
          'data' => $data,
        ],
        'timeout' => 10,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->warning('Webhook notification failed: @error', ['@error' => $e->getMessage()]);
    }
  }

}
