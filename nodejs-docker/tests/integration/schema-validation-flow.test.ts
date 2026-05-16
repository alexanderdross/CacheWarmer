import { describe, it, expect, vi, beforeEach } from "vitest";
import { v4 as uuidv4 } from "uuid";
import { createTestDb, resetTestConfig, testConfig } from "../helpers";
import type Database from "better-sqlite3";

let testDb: Database.Database;

const upsertResultMock = vi.fn();
const supabaseFromMock = vi.fn();

vi.mock("@/lib/db/database", () => ({
  getDb: () => testDb,
  closeDb: vi.fn(),
  getActiveJobForSitemapUrl: vi.fn().mockReturnValue(null),
  normalizeUrl: (u: string) => u,
}));

vi.mock("@/lib/config", async () => {
  const helpers = await import("../helpers");
  return { getConfig: () => helpers.testConfig, loadConfig: () => helpers.testConfig };
});

vi.mock("@/lib/services/sitemap-parser", () => ({
  parseSitemap: vi.fn().mockResolvedValue([
    { loc: "https://trade.aero/aircraft/cessna-c172-101" },
    { loc: "https://trade.aero/de/aircraft/cessna-c172-101" },
    { loc: "https://trade.aero/about" },
  ]),
}));

// Mock cdn-warmer to emit __html on the in-scope desktop result.
vi.mock("@/lib/services/cdn-warmer", () => ({
  warmUrls: vi.fn(async (urls: string[], onProgress: (r: unknown) => void) => {
    const html = `<html><head>
      <script type="application/ld+json">{"@context":"http://schema.org","@type":"Product","name":"x"}</script>
    </head></html>`;
    for (const url of urls) {
      const inScope = url.includes("/aircraft/");
      onProgress({
        url,
        viewport: "desktop",
        status: "success",
        httpStatus: 200,
        durationMs: 100,
        __html: inScope ? html : undefined,
      });
    }
    return [];
  }),
  closeBrowser: vi.fn(),
}));

vi.mock("@/lib/services/facebook-warmer", () => ({ warmFacebook: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/linkedin-warmer", () => ({ warmLinkedIn: vi.fn().mockResolvedValue([]), closeBrowser: vi.fn() }));
vi.mock("@/lib/services/twitter-warmer", () => ({ warmTwitter: vi.fn().mockResolvedValue([]), closeBrowser: vi.fn() }));
vi.mock("@/lib/services/indexnow", () => ({ submitIndexNow: vi.fn().mockResolvedValue({ status: "success", urlCount: 0 }) }));
vi.mock("@/lib/services/google-indexer", () => ({ submitToGoogle: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/bing-indexer", () => ({ submitToBing: vi.fn().mockResolvedValue({ status: "success", urlCount: 0 }) }));
vi.mock("@/lib/services/pinterest-warmer", () => ({ warmPinterest: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/cdn-purge-warm", () => ({ purgeCdnCache: vi.fn().mockResolvedValue([]) }));
vi.mock("@/lib/services/webhooks", () => ({ sendWebhook: vi.fn().mockResolvedValue(undefined) }));
vi.mock("@/lib/services/email-notifications", () => ({ sendJobCompletedEmail: vi.fn().mockResolvedValue(undefined) }));

vi.mock("@supabase/supabase-js", () => ({
  createClient: () => ({
    from: supabaseFromMock.mockImplementation(() => ({
      upsert: (row: unknown) => ({
        select: () => ({
          single: async () => {
            upsertResultMock(row);
            return { data: { id: uuidv4() }, error: null };
          },
        }),
      }),
      update: () => ({ eq: async () => ({ error: null }) }),
      select: () => ({
        eq: () => ({
          eq: () => ({
            gte: async () => ({ data: [], error: null }),
          }),
        }),
      }),
    })),
  }),
}));

describe("schema-validation flow (processJob integration)", () => {
  beforeEach(() => {
    testDb = createTestDb();
    resetTestConfig();
    upsertResultMock.mockReset();
    supabaseFromMock.mockReset();

    // Enable schemaValidation with github disabled (issue/PR creation skipped).
    (testConfig as unknown as Record<string, unknown>).schemaValidation = {
      enabled: true,
      validationEngine: "sdtt",
      presets: ["Google"],
      scopedPaths: [],
      concurrency: 2,
      severityThreshold: "error",
      supabase: {
        urlEnv: "TEST_SUPABASE_URL",
        serviceRoleKeyEnv: "TEST_SUPABASE_KEY",
        table: "schema_validation_results",
      },
      github: { enabled: false, tokenEnv: "TEST_TOKEN", repoOwner: "x", repoName: "y", labels: [], issueAssignees: [], maxIssuesPerJob: 5, reopenClosedWithinDays: 30 },
      autoFix: { enabled: false, branch: "x", baseBranch: "main", draftPr: true, allowedFixes: [] },
    };
    process.env.TEST_SUPABASE_URL = "https://test.supabase.co";
    process.env.TEST_SUPABASE_KEY = "test-key";
  });

  it("validates in-scope aircraft URLs and skips out-of-scope routes", async () => {
    const { createJob, processJob } = await import("@/lib/queue/job-manager");
    const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
    await processJob(job.id);

    const updated = testDb.prepare("SELECT status, total_urls, processed_urls FROM jobs WHERE id = ?").get(job.id) as {
      status: string;
      total_urls: number;
      processed_urls: number;
    };
    expect(updated.status).toBe("completed");
    expect(updated.total_urls).toBe(3);

    // Two in-scope aircraft URLs (de + en) should have triggered Supabase upserts.
    // /about is out of scope and must NOT have been validated.
    expect(upsertResultMock).toHaveBeenCalled();
    const urls = upsertResultMock.mock.calls.map((c) => (c[0] as { url: string }).url);
    for (const u of urls) {
      expect(u).toContain("/aircraft/");
    }
    // Both calls should share the same canonical_url + fingerprint (locale dedup).
    const canonicals = new Set(upsertResultMock.mock.calls.map((c) => (c[0] as { canonical_url: string }).canonical_url));
    const fingerprints = new Set(upsertResultMock.mock.calls.map((c) => (c[0] as { fingerprint: string }).fingerprint));
    expect(canonicals.size).toBe(1);
    expect(fingerprints.size).toBe(1);
  });

  it("strips transient __html before persisting url_results", async () => {
    const { createJob, processJob } = await import("@/lib/queue/job-manager");
    const job = createJob({ sitemapUrl: "https://example.com/sitemap.xml", targets: ["cdn"] });
    await processJob(job.id);

    const rows = testDb.prepare("SELECT * FROM url_results WHERE job_id = ?").all(job.id) as Array<Record<string, unknown>>;
    expect(rows.length).toBeGreaterThan(0);
    for (const row of rows) {
      // url_results table has no __html column; verify nothing leaked into another field.
      expect(JSON.stringify(row)).not.toContain("<script");
      expect(JSON.stringify(row)).not.toContain("ld+json");
    }
  });
});
