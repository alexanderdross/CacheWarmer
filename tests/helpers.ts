import Database from "better-sqlite3";

/**
 * Shared mutable config reference for tests.
 * Mock config modules read from this directly.
 * Tests modify this to change config behavior.
 */
export let testConfig: ReturnType<typeof createMockConfig>;

export function setTestConfig(config: ReturnType<typeof createMockConfig>) {
  testConfig = config;
}

export function resetTestConfig() {
  testConfig = createMockConfig();
}

/**
 * Create an in-memory SQLite database with the same schema as production.
 */
export function createTestDb(): Database.Database {
  const db = new Database(":memory:");
  db.pragma("journal_mode = WAL");
  db.pragma("foreign_keys = ON");

  db.exec(`
    CREATE TABLE IF NOT EXISTS sitemaps (
      id TEXT PRIMARY KEY,
      url TEXT NOT NULL,
      domain TEXT NOT NULL,
      cron_expression TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      last_warmed_at TEXT
    );

    CREATE TABLE IF NOT EXISTS jobs (
      id TEXT PRIMARY KEY,
      sitemap_id TEXT REFERENCES sitemaps(id),
      sitemap_url TEXT,
      status TEXT NOT NULL DEFAULT 'queued',
      total_urls INTEGER NOT NULL DEFAULT 0,
      processed_urls INTEGER NOT NULL DEFAULT 0,
      targets TEXT NOT NULL DEFAULT '[]',
      started_at TEXT,
      completed_at TEXT,
      error TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS url_results (
      id TEXT PRIMARY KEY,
      job_id TEXT NOT NULL REFERENCES jobs(id),
      url TEXT NOT NULL,
      target TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      http_status INTEGER,
      duration_ms INTEGER,
      error TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE INDEX IF NOT EXISTS idx_url_results_job_id ON url_results(job_id);
    CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status);
  `);

  return db;
}

/**
 * Create a mock config for testing
 */
export function createMockConfig() {
  return {
    server: {
      port: 3000,
      host: "0.0.0.0",
      apiKey: "test-api-key-12345",
    },
    redis: { host: "localhost", port: 6379 },
    database: { path: ":memory:" },
    puppeteer: {
      executablePath: "/usr/bin/chromium-browser",
      headless: true,
      args: ["--no-sandbox", "--disable-setuid-sandbox"],
    },
    cdnWarming: {
      enabled: true,
      concurrency: 2,
      waitUntil: "networkidle0",
      timeout: 10000,
      userAgents: {
        desktop: "Mozilla/5.0 Desktop",
        mobile: "Mozilla/5.0 Mobile",
      },
    },
    facebook: {
      enabled: true,
      appId: "test-app-id",
      appSecret: "test-app-secret",
      rateLimitPerSecond: 10,
    },
    linkedin: {
      enabled: true,
      sessionCookie: "test-session-cookie",
      concurrency: 1,
      delayBetweenRequests: 100,
    },
    twitter: {
      enabled: true,
      concurrency: 2,
      delayBetweenRequests: 100,
      timeout: 5000,
    },
    google: {
      enabled: true,
      serviceAccountKeyFile: "./credentials/google-sa-key.json",
      dailyQuota: 200,
    },
    bing: {
      enabled: true,
      apiKey: "test-bing-key",
      dailyQuota: 10000,
    },
    indexNow: {
      enabled: true,
      key: "test-indexnow-key",
      keyLocation: "https://example.com/test-indexnow-key.txt",
    },
    scheduler: {
      enabled: false,
      defaultCron: "0 3 * * *",
    },
    logging: {
      level: "info",
      file: "./data/cachewarmer.log",
    },
  };
}

/**
 * Generate a valid sitemap XML
 */
export function generateSitemapXml(urls: string[]): string {
  const entries = urls
    .map(
      (url) => `  <url>
    <loc>${url}</loc>
    <lastmod>2026-01-01</lastmod>
    <priority>0.8</priority>
  </url>`
    )
    .join("\n");

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${entries}
</urlset>`;
}

/**
 * Generate a sitemap index XML
 */
export function generateSitemapIndexXml(sitemapUrls: string[]): string {
  const entries = sitemapUrls
    .map(
      (url) => `  <sitemap>
    <loc>${url}</loc>
    <lastmod>2026-01-01</lastmod>
  </sitemap>`
    )
    .join("\n");

  return `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${entries}
</sitemapindex>`;
}

// Initialize default config
testConfig = createMockConfig();
