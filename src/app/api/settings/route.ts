import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import fs from "fs";
import path from "path";
import { parse, stringify } from "yaml";

function getConfigPath(): string {
  const localPath = path.resolve(process.cwd(), "config.local.yaml");
  const mainPath = path.resolve(process.cwd(), "config.yaml");
  return fs.existsSync(localPath) ? localPath : mainPath;
}

function getWriteConfigPath(): string {
  return path.resolve(process.cwd(), "config.local.yaml");
}

export async function GET(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const configPath = getConfigPath();
    const raw = fs.readFileSync(configPath, "utf-8");
    const config = parse(raw);

    // Mask sensitive values for display
    const masked = {
      ...config,
      server: {
        ...config.server,
        apiKey: config.server?.apiKey ? "***configured***" : "",
      },
      facebook: {
        ...config.facebook,
        appId: config.facebook?.appId || "",
        appSecret: config.facebook?.appSecret ? "***configured***" : "",
      },
      linkedin: {
        ...config.linkedin,
        sessionCookie: config.linkedin?.sessionCookie ? "***configured***" : "",
      },
      google: {
        ...config.google,
        serviceAccountKeyFile: config.google?.serviceAccountKeyFile || "",
      },
      bing: {
        ...config.bing,
        apiKey: config.bing?.apiKey ? "***configured***" : "",
      },
      indexNow: {
        ...config.indexNow,
        key: config.indexNow?.key || "",
        keyLocation: config.indexNow?.keyLocation || "",
      },
      notifications: {
        ...config.notifications,
        webhookUrl: config.notifications?.webhookUrl || "",
        emailEnabled: config.notifications?.emailEnabled || false,
        emailTo: config.notifications?.emailTo || "",
        emailFrom: config.notifications?.emailFrom || "cachewarmer@localhost",
        smtpHost: config.notifications?.smtpHost || "",
        smtpPort: config.notifications?.smtpPort || 587,
        smtpUser: config.notifications?.smtpUser || "",
        smtpPass: config.notifications?.smtpPass ? "***configured***" : "",
      },
      excludePatterns: config.excludePatterns || "",
    };

    return NextResponse.json(masked);
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    return NextResponse.json({ error }, { status: 500 });
  }
}

export async function PUT(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const updates = await request.json();

    // Read current config
    const configPath = getConfigPath();
    const raw = fs.readFileSync(configPath, "utf-8");
    const config = parse(raw);

    // Merge updates — only update fields that are not masked placeholders
    if (updates.facebook) {
      if (updates.facebook.appId !== undefined) config.facebook.appId = updates.facebook.appId;
      if (updates.facebook.appSecret && updates.facebook.appSecret !== "***configured***") {
        config.facebook.appSecret = updates.facebook.appSecret;
      }
      if (updates.facebook.enabled !== undefined) config.facebook.enabled = updates.facebook.enabled;
      if (updates.facebook.rateLimitPerSecond !== undefined) config.facebook.rateLimitPerSecond = updates.facebook.rateLimitPerSecond;
    }

    if (updates.linkedin) {
      if (updates.linkedin.sessionCookie && updates.linkedin.sessionCookie !== "***configured***") {
        config.linkedin.sessionCookie = updates.linkedin.sessionCookie;
      }
      if (updates.linkedin.enabled !== undefined) config.linkedin.enabled = updates.linkedin.enabled;
      if (updates.linkedin.concurrency !== undefined) config.linkedin.concurrency = updates.linkedin.concurrency;
      if (updates.linkedin.delayBetweenRequests !== undefined) config.linkedin.delayBetweenRequests = updates.linkedin.delayBetweenRequests;
    }

    if (updates.twitter) {
      if (updates.twitter.enabled !== undefined) config.twitter.enabled = updates.twitter.enabled;
      if (updates.twitter.concurrency !== undefined) config.twitter.concurrency = updates.twitter.concurrency;
      if (updates.twitter.delayBetweenRequests !== undefined) config.twitter.delayBetweenRequests = updates.twitter.delayBetweenRequests;
    }

    if (updates.google) {
      if (updates.google.enabled !== undefined) config.google.enabled = updates.google.enabled;
      if (updates.google.serviceAccountKeyFile !== undefined) config.google.serviceAccountKeyFile = updates.google.serviceAccountKeyFile;
      if (updates.google.dailyQuota !== undefined) config.google.dailyQuota = updates.google.dailyQuota;
    }

    if (updates.bing) {
      if (updates.bing.apiKey && updates.bing.apiKey !== "***configured***") {
        config.bing.apiKey = updates.bing.apiKey;
      }
      if (updates.bing.enabled !== undefined) config.bing.enabled = updates.bing.enabled;
      if (updates.bing.dailyQuota !== undefined) config.bing.dailyQuota = updates.bing.dailyQuota;
    }

    if (updates.indexNow) {
      if (updates.indexNow.key !== undefined) config.indexNow.key = updates.indexNow.key;
      if (updates.indexNow.keyLocation !== undefined) config.indexNow.keyLocation = updates.indexNow.keyLocation;
      if (updates.indexNow.enabled !== undefined) config.indexNow.enabled = updates.indexNow.enabled;
    }

    if (updates.cdnWarming) {
      if (updates.cdnWarming.enabled !== undefined) config.cdnWarming.enabled = updates.cdnWarming.enabled;
      if (updates.cdnWarming.concurrency !== undefined) config.cdnWarming.concurrency = updates.cdnWarming.concurrency;
      if (updates.cdnWarming.timeout !== undefined) config.cdnWarming.timeout = updates.cdnWarming.timeout;
    }

    if (updates.server) {
      if (updates.server.apiKey && updates.server.apiKey !== "***configured***") {
        config.server.apiKey = updates.server.apiKey;
      }
    }

    if (updates.notifications) {
      if (!config.notifications) config.notifications = {};
      if (updates.notifications.webhookUrl !== undefined) config.notifications.webhookUrl = updates.notifications.webhookUrl;
      if (updates.notifications.emailEnabled !== undefined) config.notifications.emailEnabled = updates.notifications.emailEnabled;
      if (updates.notifications.emailTo !== undefined) config.notifications.emailTo = updates.notifications.emailTo;
      if (updates.notifications.emailFrom !== undefined) config.notifications.emailFrom = updates.notifications.emailFrom;
      if (updates.notifications.smtpHost !== undefined) config.notifications.smtpHost = updates.notifications.smtpHost;
      if (updates.notifications.smtpPort !== undefined) config.notifications.smtpPort = updates.notifications.smtpPort;
      if (updates.notifications.smtpUser !== undefined) config.notifications.smtpUser = updates.notifications.smtpUser;
      if (updates.notifications.smtpPass && updates.notifications.smtpPass !== "***configured***") {
        config.notifications.smtpPass = updates.notifications.smtpPass;
      }
    }

    if (updates.excludePatterns !== undefined) {
      config.excludePatterns = updates.excludePatterns;
    }

    // Write to config.local.yaml
    const writePath = getWriteConfigPath();
    fs.writeFileSync(writePath, stringify(config), "utf-8");

    return NextResponse.json({ message: "Konfiguration gespeichert", path: writePath });
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    return NextResponse.json({ error }, { status: 500 });
  }
}
