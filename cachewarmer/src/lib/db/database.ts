import Database from "better-sqlite3";
import path from "path";
import fs from "fs";

let db: Database.Database | null = null;

export function getDb(): Database.Database {
  if (db) return db;

  const dbPath = path.resolve(process.cwd(), "data", "cachewarmer.db");
  const dir = path.dirname(dbPath);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  db = new Database(dbPath);
  db.pragma("journal_mode = WAL");
  db.pragma("foreign_keys = ON");

  runMigrations(db);

  return db;
}

function runMigrations(db: Database.Database) {
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
      viewport TEXT,
      status TEXT NOT NULL DEFAULT 'pending',
      http_status INTEGER,
      duration_ms INTEGER,
      error TEXT,
      cache_headers TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE INDEX IF NOT EXISTS idx_url_results_job_id ON url_results(job_id);
    CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status);
    CREATE UNIQUE INDEX IF NOT EXISTS idx_sitemaps_url ON sitemaps(url);
  `);

  // Add columns if upgrading from older schema
  const cols = db.prepare("PRAGMA table_info(url_results)").all() as { name: string }[];
  const colNames = cols.map((c) => c.name);
  if (!colNames.includes("viewport")) {
    db.exec("ALTER TABLE url_results ADD COLUMN viewport TEXT");
  }
  if (!colNames.includes("cache_headers")) {
    db.exec("ALTER TABLE url_results ADD COLUMN cache_headers TEXT");
  }
}

export function closeDb() {
  if (db) {
    db.close();
    db = null;
  }
}
