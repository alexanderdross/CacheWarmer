import fs from "fs";
import path from "path";
import { parse } from "yaml";

export interface AppConfig {
  server: {
    port: number;
    host: string;
    apiKey: string;
  };
  redis: {
    host: string;
    port: number;
  };
  database: {
    path: string;
  };
  puppeteer: {
    executablePath: string;
    headless: boolean;
    args: string[];
  };
  cdnWarming: {
    enabled: boolean;
    concurrency: number;
    waitUntil: string;
    timeout: number;
    userAgents: {
      desktop: string;
      mobile: string;
    };
    customUserAgent?: string;
    customHeaders?: Record<string, string>;
    customViewports?: Array<{ width: number; height: number; label: string }>;
    authCookies?: Array<{ name: string; value: string; domain?: string }>;
  };
  facebook: {
    enabled: boolean;
    appId: string;
    appSecret: string;
    rateLimitPerSecond: number;
  };
  linkedin: {
    enabled: boolean;
    sessionCookie: string;
    concurrency: number;
    delayBetweenRequests: number;
  };
  twitter: {
    enabled: boolean;
    concurrency: number;
    delayBetweenRequests: number;
    timeout: number;
  };
  google: {
    enabled: boolean;
    serviceAccountKeyFile: string;
    dailyQuota: number;
  };
  bing: {
    enabled: boolean;
    apiKey: string;
    dailyQuota: number;
  };
  indexNow: {
    enabled: boolean;
    key: string;
    keyLocation: string;
  };
  scheduler: {
    enabled: boolean;
    defaultCron: string;
  };
  notifications: {
    webhookUrl: string;
    emailEnabled: boolean;
    emailTo: string;
    emailFrom: string;
    smtpHost: string;
    smtpPort: number;
    smtpUser: string;
    smtpPass: string;
  };
  pinterest: {
    enabled: boolean;
    delay: number;
  };
  cloudflare: {
    enabled: boolean;
    apiToken: string;
    zoneId: string;
  };
  imperva: {
    enabled: boolean;
    apiId: string;
    apiKey: string;
    siteId: string;
  };
  akamai: {
    enabled: boolean;
    host: string;
    clientToken: string;
    clientSecret: string;
    accessToken: string;
    network: string;
  };
  excludePatterns: string;
  logging: {
    level: string;
    file: string;
  };
}

let cachedConfig: AppConfig | null = null;

export function loadConfig(): AppConfig {
  if (cachedConfig) return cachedConfig;

  const configPath = path.resolve(process.cwd(), "config.yaml");
  const localConfigPath = path.resolve(process.cwd(), "config.local.yaml");

  let raw: string;
  if (fs.existsSync(localConfigPath)) {
    raw = fs.readFileSync(localConfigPath, "utf-8");
  } else {
    raw = fs.readFileSync(configPath, "utf-8");
  }

  cachedConfig = parse(raw) as AppConfig;
  return cachedConfig;
}

export function getConfig(): AppConfig {
  return loadConfig();
}
