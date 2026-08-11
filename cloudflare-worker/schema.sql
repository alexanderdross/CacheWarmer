-- D1 schema for the hub deploy.
--
-- Only the hub needs this; satellites hold no database and POST their job
-- summaries to the hub's /report endpoint instead, because service bindings do
-- not cross Cloudflare account boundaries.
--
-- Apply with:
--   wrangler d1 execute cachewarmer-reports --remote \
--     --config deploy/drossmedia/wrangler.jsonc --file schema.sql

CREATE TABLE IF NOT EXISTS job_reports (
  job_id TEXT NOT NULL,
  site_id TEXT NOT NULL,
  region TEXT NOT NULL,
  status TEXT NOT NULL,
  total INTEGER NOT NULL DEFAULT 0,
  processed INTEGER NOT NULL DEFAULT 0,
  -- JSON array of the data centres that actually served the work, from cf-ray.
  colos TEXT,
  -- JSON object mapping each verdict to its count.
  verdicts TEXT,
  completed_at TEXT,
  -- One row per region per job; a re-report replaces rather than duplicates.
  PRIMARY KEY (job_id, region)
);

CREATE INDEX IF NOT EXISTS idx_job_reports_site ON job_reports(site_id);
CREATE INDEX IF NOT EXISTS idx_job_reports_completed ON job_reports(completed_at);
