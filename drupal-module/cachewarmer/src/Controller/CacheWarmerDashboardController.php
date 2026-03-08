<?php

namespace Drupal\cachewarmer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cachewarmer\Service\CacheWarmerDatabase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for CacheWarmer admin pages.
 */
class CacheWarmerDashboardController extends ControllerBase {

  protected CacheWarmerDatabase $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('cachewarmer.database');
    return $instance;
  }

  /**
   * Dashboard page.
   */
  public function dashboard() {
    $counts = $this->database->getJobCounts();
    $jobs = $this->database->getJobs(20);

    return [
      '#theme' => 'cachewarmer_dashboard',
      '#status' => $counts,
      '#jobs' => $jobs,
      '#attached' => [
        'library' => ['cachewarmer/admin'],
        'drupalSettings' => [
          'cachewarmer' => [
            'ajaxUrls' => [
              'startWarm' => '/admin/cachewarmer/ajax/start-warm',
              'getJobs' => '/admin/cachewarmer/ajax/jobs',
              'getJob' => '/admin/cachewarmer/ajax/jobs/',
              'deleteJob' => '/admin/cachewarmer/ajax/jobs/',
              'status' => '/admin/cachewarmer/ajax/status',
              'exportResultsUrl' => '/admin/cachewarmer/ajax/export-results',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Sitemaps page.
   */
  public function sitemaps() {
    $sitemaps = $this->database->getAllSitemaps();

    return [
      '#theme' => 'cachewarmer_sitemaps',
      '#sitemaps' => $sitemaps,
      '#attached' => [
        'library' => ['cachewarmer/admin'],
        'drupalSettings' => [
          'cachewarmer' => [
            'ajaxUrls' => [
              'addSitemap' => '/admin/cachewarmer/ajax/sitemaps/add',
              'deleteSitemap' => '/admin/cachewarmer/ajax/sitemaps/',
              'warmSitemap' => '/admin/cachewarmer/ajax/sitemaps/',
              'bulkAddSitemapsUrl' => '/admin/cachewarmer/ajax/bulk-add-sitemaps',
              'detectSitemapsUrl' => '/admin/cachewarmer/ajax/detect-sitemaps',
              'exportResultsUrl' => '/admin/cachewarmer/ajax/export-results',
            ],
          ],
        ],
      ],
    ];
  }

}
