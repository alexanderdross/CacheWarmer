<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manages license tiers and feature access for CacheWarmer.
 *
 * License key format: CW-{TIER}-{HEX16}
 *   TIER = PRO | ENT
 *   HEX16 = 4-char duration (days, hex, 0000 = never) + 12-char HMAC signature.
 */
class CacheWarmerLicense {

  const TIER_FREE = 'free';
  const TIER_PREMIUM = 'premium';
  const TIER_ENTERPRISE = 'enterprise';

  /**
   * HMAC signing secret for key validation.
   */
  const SIGN_SECRET = 'cw-drossmedia-lic-2026-s3cr3t';

  const LIMITS = [
    'free' => [
      'max_urls_per_job' => 50,
      'max_sitemaps' => 2,
      'max_external_sitemaps' => 1,
      'max_jobs_per_day' => 3,
      'log_retention_days' => 7,
      'cdn_concurrency' => 2,
      'allowed_targets' => ['cdn', 'indexnow'],
      'scheduler_enabled' => FALSE,
      'api_enabled' => FALSE,
      'export_enabled' => FALSE,
      'webhooks_enabled' => FALSE,
      'email_notifications' => FALSE,
    ],
    'premium' => [
      'max_urls_per_job' => 10000,
      'max_sitemaps' => 25,
      'max_external_sitemaps' => 10,
      'max_jobs_per_day' => 50,
      'log_retention_days' => 90,
      'cdn_concurrency' => 10,
      'allowed_targets' => ['cdn', 'indexnow', 'facebook', 'linkedin', 'twitter', 'google', 'bing'],
      'scheduler_enabled' => TRUE,
      'api_enabled' => TRUE,
      'export_enabled' => TRUE,
      'webhooks_enabled' => FALSE,
      'email_notifications' => TRUE,
    ],
    'enterprise' => [
      'max_urls_per_job' => PHP_INT_MAX,
      'max_sitemaps' => PHP_INT_MAX,
      'max_external_sitemaps' => PHP_INT_MAX,
      'max_jobs_per_day' => PHP_INT_MAX,
      'log_retention_days' => 365,
      'cdn_concurrency' => 20,
      'allowed_targets' => ['cdn', 'indexnow', 'facebook', 'linkedin', 'twitter', 'google', 'bing'],
      'scheduler_enabled' => TRUE,
      'api_enabled' => TRUE,
      'export_enabled' => TRUE,
      'webhooks_enabled' => TRUE,
      'email_notifications' => TRUE,
    ],
  ];

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the CacheWarmerLicense service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the current license tier, auto-downgrading if expired.
   *
   * @return string
   *   The license tier (free, premium, or enterprise).
   */
  public function getTier(): string {
    $config = $this->configFactory->get('cachewarmer.settings');
    $tier = $config->get('license_tier') ?? self::TIER_FREE;

    if ($tier !== self::TIER_FREE) {
      $expiresAt = (int) ($config->get('license_expires_at') ?? 0);
      if ($expiresAt > 0 && time() > $expiresAt) {
        $editable = $this->configFactory->getEditable('cachewarmer.settings');
        $editable->set('license_tier', self::TIER_FREE)->save();
        return self::TIER_FREE;
      }
    }

    return $tier;
  }

  /**
   * Gets a specific limit value for the current tier.
   *
   * @param string $key
   *   The limit key.
   *
   * @return mixed
   *   The limit value, or NULL if not found.
   */
  public function getLimit(string $key) {
    $tier = $this->getTier();
    return self::LIMITS[$tier][$key] ?? NULL;
  }

  /**
   * Checks if a feature is enabled for the current tier.
   *
   * @param string $feature
   *   The feature key.
   *
   * @return bool
   *   TRUE if the feature is enabled.
   */
  public function can(string $feature): bool {
    return (bool) $this->getLimit($feature);
  }

  /**
   * Checks if a warming target is allowed for the current tier.
   *
   * @param string $target
   *   The target name.
   *
   * @return bool
   *   TRUE if the target is allowed.
   */
  public function isTargetAllowed(string $target): bool {
    $allowed = $this->getLimit('allowed_targets');
    return in_array($target, $allowed, TRUE);
  }

  /**
   * Checks if the current tier is Premium or above.
   *
   * @return bool
   *   TRUE if Premium or Enterprise.
   */
  public function isPremiumOrAbove(): bool {
    return in_array($this->getTier(), [self::TIER_PREMIUM, self::TIER_ENTERPRISE], TRUE);
  }

  /**
   * Checks if the current tier is Enterprise.
   *
   * @return bool
   *   TRUE if Enterprise.
   */
  public function isEnterprise(): bool {
    return $this->getTier() === self::TIER_ENTERPRISE;
  }

  /**
   * Validates a license key via HMAC signature.
   *
   * @param string $key
   *   The license key.
   *
   * @return array|false
   *   Array with 'tier' and 'duration_days', or FALSE if invalid.
   */
  public static function validateKey(string $key) {
    $key = strtoupper(trim($key));

    if (!preg_match('/^CW-(PRO|ENT)-([0-9A-F]{16})$/', $key, $m)) {
      return FALSE;
    }

    $tierCode = $m[1];
    $hex = $m[2];
    $durationHex = substr($hex, 0, 4);
    $providedSig = strtolower(substr($hex, 4, 12));

    $payload = $tierCode . $durationHex;
    $expectedSig = substr(hash_hmac('sha256', $payload, self::SIGN_SECRET), 0, 12);

    if (!hash_equals($expectedSig, $providedSig)) {
      return FALSE;
    }

    return [
      'tier' => $tierCode === 'ENT' ? self::TIER_ENTERPRISE : self::TIER_PREMIUM,
      'duration_days' => hexdec($durationHex),
    ];
  }

  /**
   * Activates a license key and sets the tier accordingly.
   *
   * @param string $licenseKey
   *   The license key to activate.
   *
   * @return array
   *   Array with 'tier', 'activated', and optionally 'expires_at' keys.
   */
  public function activate(string $licenseKey): array {
    $licenseKey = strtoupper(trim($licenseKey));

    $parsed = self::validateKey($licenseKey);

    if ($parsed === FALSE) {
      return [
        'tier' => self::TIER_FREE,
        'activated' => FALSE,
        'error' => 'Invalid license key.',
      ];
    }

    $expiresAt = 0;
    if ($parsed['duration_days'] > 0) {
      $expiresAt = time() + ($parsed['duration_days'] * 86400);
    }

    return [
      'tier' => $parsed['tier'],
      'activated' => TRUE,
      'expires_at' => $expiresAt,
    ];
  }

}
