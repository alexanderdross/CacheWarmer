<?php

namespace Drupal\cachewarmer\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\cachewarmer\Service\CacheWarmerJobManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes cache warming jobs in the background.
 *
 * @QueueWorker(
 *   id = "cachewarmer_process_job",
 *   title = @Translation("CacheWarmer Job Processor"),
 *   cron = {"time" = 300}
 * )
 */
class CacheWarmerProcessJob extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected CacheWarmerJobManager $jobManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->jobManager = $container->get('cachewarmer.job_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['job_id'])) {
      return;
    }
    $this->jobManager->processJob($data['job_id']);
  }

}
