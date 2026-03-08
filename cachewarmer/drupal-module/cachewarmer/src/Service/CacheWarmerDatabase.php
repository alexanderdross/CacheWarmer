<?php

namespace Drupal\cachewarmer\Service;

use Drupal\Core\Database\Connection;

/**
 * Database operations for CacheWarmer.
 */
class CacheWarmerDatabase {

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * Constructs the CacheWarmerDatabase service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Generates a UUID v4.
   */
  public function generateUuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  /**
   * Returns the current timestamp in ISO 8601 format.
   */
  protected function now(): string {
    return gmdate('Y-m-d\TH:i:s\Z');
  }

  // --- Sitemaps ---

  /**
   * Inserts a new sitemap.
   */
  public function insertSitemap(string $url, string $domain, ?string $cronExpression = NULL): object {
    $id = $this->generateUuid();
    $now = $this->now();

    $this->database->insert('cachewarmer_sitemaps')
      ->fields([
        'id' => $id,
        'url' => $url,
        'domain' => $domain,
        'cron_expression' => $cronExpression,
        'created_at' => $now,
      ])
      ->execute();

    return $this->getSitemap($id);
  }

  /**
   * Gets a sitemap by ID.
   */
  public function getSitemap(string $id): ?object {
    return $this->database->select('cachewarmer_sitemaps', 's')
      ->fields('s')
      ->condition('id', $id)
      ->execute()
      ->fetchObject() ?: NULL;
  }

  /**
   * Gets all sitemaps.
   */
  public function getAllSitemaps(): array {
    return $this->database->select('cachewarmer_sitemaps', 's')
      ->fields('s')
      ->orderBy('created_at', 'DESC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Deletes a sitemap.
   */
  public function deleteSitemap(string $id): bool {
    $deleted = $this->database->delete('cachewarmer_sitemaps')
      ->condition('id', $id)
      ->execute();
    return $deleted > 0;
  }

  /**
   * Updates the last warmed timestamp.
   */
  public function updateSitemapLastWarmed(string $id): void {
    $this->database->update('cachewarmer_sitemaps')
      ->fields(['last_warmed_at' => $this->now()])
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Gets a sitemap by URL.
   */
  public function getSitemapByUrl(string $url): ?object {
    return $this->database->select('cachewarmer_sitemaps', 's')
      ->fields('s')
      ->condition('url', $url)
      ->range(0, 1)
      ->execute()
      ->fetchObject() ?: NULL;
  }

  // --- Jobs ---

  /**
   * Checks if an active job exists for a given sitemap URL.
   */
  public function hasActiveJobForUrl(string $url): bool {
    $count = $this->database->select('cachewarmer_jobs', 'j')
      ->condition('sitemap_url', $url)
      ->condition('status', ['queued', 'running'], 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();
    return ((int) $count) > 0;
  }

  /**
   * Inserts a new job.
   */
  public function insertJob(string $sitemapUrl, array $targets, ?string $sitemapId = NULL): object {
    $id = $this->generateUuid();
    $now = $this->now();

    $this->database->insert('cachewarmer_jobs')
      ->fields([
        'id' => $id,
        'sitemap_id' => $sitemapId,
        'sitemap_url' => $sitemapUrl,
        'status' => 'queued',
        'total_urls' => 0,
        'processed_urls' => 0,
        'targets' => json_encode($targets),
        'created_at' => $now,
      ])
      ->execute();

    return $this->getJob($id);
  }

  /**
   * Gets a job by ID.
   */
  public function getJob(string $id): ?object {
    return $this->database->select('cachewarmer_jobs', 'j')
      ->fields('j')
      ->condition('id', $id)
      ->execute()
      ->fetchObject() ?: NULL;
  }

  /**
   * Gets jobs with optional limit.
   */
  public function getJobs(int $limit = 20, int $offset = 0): array {
    return $this->database->select('cachewarmer_jobs', 'j')
      ->fields('j')
      ->orderBy('created_at', 'DESC')
      ->range($offset, $limit)
      ->execute()
      ->fetchAll();
  }

  /**
   * Updates a job.
   */
  public function updateJob(string $id, array $fields): void {
    $this->database->update('cachewarmer_jobs')
      ->fields($fields)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Deletes a job and its results.
   */
  public function deleteJob(string $id): bool {
    $this->database->delete('cachewarmer_url_results')
      ->condition('job_id', $id)
      ->execute();

    $deleted = $this->database->delete('cachewarmer_jobs')
      ->condition('id', $id)
      ->execute();

    return $deleted > 0;
  }

  /**
   * Gets job counts by status.
   */
  public function getJobCounts(): array {
    $results = $this->database->select('cachewarmer_jobs', 'j')
      ->fields('j', ['status'])
      ->execute()
      ->fetchAll();

    $counts = [
      'queued' => 0,
      'running' => 0,
      'completed' => 0,
      'failed' => 0,
      'total_processed' => 0,
    ];

    foreach ($results as $row) {
      if (isset($counts[$row->status])) {
        $counts[$row->status]++;
      }
    }

    // Sum processed URLs.
    $total = $this->database->select('cachewarmer_jobs', 'j')
      ->addExpression('SUM(processed_urls)', 'total')
      ->execute()
      ->fetchField();
    $counts['total_processed'] = (int) ($total ?? 0);

    return $counts;
  }

  // --- URL Results ---

  /**
   * Inserts a URL result.
   */
  public function insertUrlResult(string $jobId, string $url, string $target, string $status, ?int $httpStatus = NULL, ?int $durationMs = NULL, ?string $error = NULL): void {
    $this->database->insert('cachewarmer_url_results')
      ->fields([
        'id' => $this->generateUuid(),
        'job_id' => $jobId,
        'url' => $url,
        'target' => $target,
        'status' => $status,
        'http_status' => $httpStatus,
        'duration_ms' => $durationMs,
        'error' => $error,
        'created_at' => $this->now(),
      ])
      ->execute();
  }

  /**
   * Gets job results.
   */
  public function getJobResults(string $jobId): array {
    return $this->database->select('cachewarmer_url_results', 'r')
      ->fields('r')
      ->condition('job_id', $jobId)
      ->orderBy('created_at', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets aggregated job stats by target.
   */
  public function getJobStats(string $jobId): array {
    $results = $this->database->select('cachewarmer_url_results', 'r')
      ->fields('r', ['target', 'status'])
      ->condition('job_id', $jobId)
      ->execute()
      ->fetchAll();

    $stats = [];
    foreach ($results as $row) {
      if (!isset($stats[$row->target])) {
        $stats[$row->target] = [
          'success' => 0,
          'failed' => 0,
          'skipped' => 0,
          'pending' => 0,
        ];
      }
      if (isset($stats[$row->target][$row->status])) {
        $stats[$row->target][$row->status]++;
      }
    }

    return $stats;
  }

  /**
   * Gets failed and skipped URL results for a job.
   */
  public function getFailedSkippedResults(string $jobId): array {
    $query = $this->database->select('cachewarmer_url_results', 'r')
      ->fields('r')
      ->condition('r.job_id', $jobId)
      ->condition('r.status', ['failed', 'skipped'], 'IN')
      ->orderBy('r.created_at', 'DESC');
    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Gets recent logs.
   */
  public function getLogs(int $limit = 100, int $offset = 0): array {
    return $this->database->select('cachewarmer_url_results', 'r')
      ->fields('r')
      ->orderBy('created_at', 'DESC')
      ->range($offset, $limit)
      ->execute()
      ->fetchAll();
  }

}
