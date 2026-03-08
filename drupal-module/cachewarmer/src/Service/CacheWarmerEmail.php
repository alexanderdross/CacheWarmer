<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Sends email notifications for CacheWarmer events.
 */
class CacheWarmerEmail {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The license service.
   */
  protected CacheWarmerLicense $license;

  /**
   * Constructs the CacheWarmerEmail service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\cachewarmer\Service\CacheWarmerLicense $license
   *   The license service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, CacheWarmerLicense $license) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->license = $license;
  }

  /**
   * Sends a job completed email notification.
   *
   * @param array $jobData
   *   The job data array.
   */
  public function sendJobCompleted(array $jobData): void {
    if (!$this->license->isEnterprise()) {
      return;
    }

    $config = $this->configFactory->get('cachewarmer.settings');
    if (!$config->get('email_notifications')) {
      return;
    }

    $to = $config->get('notification_email') ?: \Drupal::config('system.site')->get('mail');
    if (empty($to)) {
      return;
    }

    $params = [
      'subject' => sprintf('[CacheWarmer] Job %s: %s', $jobData['status'] ?? '', $jobData['sitemap_url'] ?? 'Unknown'),
      'body' => sprintf(
        "Job ID: %s\nStatus: %s\nSitemap: %s\nURLs: %d/%d\n\nView details in your Drupal dashboard.",
        $jobData['id'] ?? '',
        $jobData['status'] ?? '',
        $jobData['sitemap_url'] ?? '',
        $jobData['processed_urls'] ?? 0,
        $jobData['total_urls'] ?? 0
      ),
    ];

    $this->mailManager->mail('cachewarmer', 'job_notification', $to, 'en', $params);
  }

}
