import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import fs from "fs";
import path from "path";
import { stringify } from "yaml";

// Don't use the global logger mock for config tests - config doesn't use logger
vi.unmock("@/lib/logger");

describe("Config Module", () => {
  const originalCwd = process.cwd();
  let tmpDir: string;

  beforeEach(() => {
    tmpDir = fs.mkdtempSync(path.join("/tmp", "cachewarmer-test-"));
    // Reset module cache to get fresh config
    vi.resetModules();
  });

  afterEach(() => {
    fs.rmSync(tmpDir, { recursive: true, force: true });
    process.chdir(originalCwd);
  });

  it("should load config from config.yaml", async () => {
    const config = {
      server: { port: 3000, host: "0.0.0.0", apiKey: "test" },
      redis: { host: "localhost", port: 6379 },
      database: { path: "./data/test.db" },
      puppeteer: { executablePath: "/usr/bin/chromium", headless: true, args: [] },
      cdnWarming: {
        enabled: true,
        concurrency: 3,
        waitUntil: "networkidle0",
        timeout: 30000,
        userAgents: { desktop: "Desktop UA", mobile: "Mobile UA" },
      },
      facebook: { enabled: false, appId: "", appSecret: "", rateLimitPerSecond: 10 },
      linkedin: { enabled: false, sessionCookie: "", concurrency: 1, delayBetweenRequests: 5000 },
      twitter: { enabled: false, concurrency: 2, delayBetweenRequests: 3000, timeout: 15000 },
      google: { enabled: false, serviceAccountKeyFile: "", dailyQuota: 200 },
      bing: { enabled: false, apiKey: "", dailyQuota: 10000 },
      indexNow: { enabled: false, key: "", keyLocation: "" },
      scheduler: { enabled: false, defaultCron: "0 3 * * *" },
      logging: { level: "info", file: "./data/test.log" },
    };

    fs.writeFileSync(path.join(tmpDir, "config.yaml"), stringify(config));
    process.chdir(tmpDir);

    const { loadConfig } = await import("@/lib/config");
    const loaded = loadConfig();

    expect(loaded.server.port).toBe(3000);
    expect(loaded.server.apiKey).toBe("test");
    expect(loaded.cdnWarming.concurrency).toBe(3);
  });

  it("should prefer config.local.yaml over config.yaml", async () => {
    const mainConfig = {
      server: { port: 3000, host: "0.0.0.0", apiKey: "main-key" },
      redis: { host: "localhost", port: 6379 },
      database: { path: "./data/test.db" },
      puppeteer: { executablePath: "/usr/bin/chromium", headless: true, args: [] },
      cdnWarming: { enabled: true, concurrency: 3, waitUntil: "networkidle0", timeout: 30000, userAgents: { desktop: "D", mobile: "M" } },
      facebook: { enabled: false, appId: "", appSecret: "", rateLimitPerSecond: 10 },
      linkedin: { enabled: false, sessionCookie: "", concurrency: 1, delayBetweenRequests: 5000 },
      twitter: { enabled: false, concurrency: 2, delayBetweenRequests: 3000, timeout: 15000 },
      google: { enabled: false, serviceAccountKeyFile: "", dailyQuota: 200 },
      bing: { enabled: false, apiKey: "", dailyQuota: 10000 },
      indexNow: { enabled: false, key: "", keyLocation: "" },
      scheduler: { enabled: false, defaultCron: "0 3 * * *" },
      logging: { level: "info", file: "./data/test.log" },
    };

    const localConfig = { ...mainConfig, server: { ...mainConfig.server, apiKey: "local-key" } };

    fs.writeFileSync(path.join(tmpDir, "config.yaml"), stringify(mainConfig));
    fs.writeFileSync(path.join(tmpDir, "config.local.yaml"), stringify(localConfig));
    process.chdir(tmpDir);

    const { loadConfig } = await import("@/lib/config");
    const loaded = loadConfig();

    expect(loaded.server.apiKey).toBe("local-key");
  });

  it("should cache config after first load", async () => {
    const config = {
      server: { port: 3000, host: "0.0.0.0", apiKey: "test" },
      redis: { host: "localhost", port: 6379 },
      database: { path: "./data/test.db" },
      puppeteer: { executablePath: "/usr/bin/chromium", headless: true, args: [] },
      cdnWarming: { enabled: true, concurrency: 3, waitUntil: "networkidle0", timeout: 30000, userAgents: { desktop: "D", mobile: "M" } },
      facebook: { enabled: false, appId: "", appSecret: "", rateLimitPerSecond: 10 },
      linkedin: { enabled: false, sessionCookie: "", concurrency: 1, delayBetweenRequests: 5000 },
      twitter: { enabled: false, concurrency: 2, delayBetweenRequests: 3000, timeout: 15000 },
      google: { enabled: false, serviceAccountKeyFile: "", dailyQuota: 200 },
      bing: { enabled: false, apiKey: "", dailyQuota: 10000 },
      indexNow: { enabled: false, key: "", keyLocation: "" },
      scheduler: { enabled: false, defaultCron: "0 3 * * *" },
      logging: { level: "info", file: "./data/test.log" },
    };

    fs.writeFileSync(path.join(tmpDir, "config.yaml"), stringify(config));
    process.chdir(tmpDir);

    const { loadConfig, getConfig } = await import("@/lib/config");
    const first = loadConfig();
    const second = getConfig();

    expect(first).toBe(second); // Same reference = cached
  });
});
