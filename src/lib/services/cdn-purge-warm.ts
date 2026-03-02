import { createHmac, createHash, randomUUID } from "crypto";
import { getConfig } from "@/lib/config";
import logger from "@/lib/logger";

export interface CdnPurgeResult {
  url: string;
  provider: "cloudflare" | "imperva" | "akamai";
  status: "success" | "failed" | "skipped";
  httpStatus?: number;
  durationMs: number;
  error?: string;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

// ─── Cloudflare ────────────────────────────────────────────────

async function purgeCloudflare(urls: string[]): Promise<CdnPurgeResult[]> {
  const config = getConfig();
  const { apiToken, zoneId } = config.cloudflare;

  if (!apiToken || !zoneId) {
    logger.warn("Cloudflare purge skipped: missing apiToken or zoneId");
    return urls.map((url) => ({
      url,
      provider: "cloudflare" as const,
      status: "skipped" as const,
      durationMs: 0,
      error: "Missing apiToken or zoneId",
    }));
  }

  const results: CdnPurgeResult[] = [];

  // Cloudflare allows up to 30 URLs per purge request
  const batchSize = 30;
  for (let i = 0; i < urls.length; i += batchSize) {
    const batch = urls.slice(i, i + batchSize);
    const start = Date.now();

    try {
      const response = await fetch(
        `https://api.cloudflare.com/client/v4/zones/${encodeURIComponent(zoneId)}/purge_cache`,
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${apiToken}`,
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ files: batch }),
          signal: AbortSignal.timeout(30000),
        }
      );

      const durationMs = Date.now() - start;
      const body = await response.json() as { success: boolean; errors?: Array<{ message: string }> };

      if (body.success) {
        for (const url of batch) {
          const result: CdnPurgeResult = {
            url,
            provider: "cloudflare",
            status: "success",
            httpStatus: response.status,
            durationMs,
          };
          results.push(result);
          logger.info({ url, provider: "cloudflare", durationMs }, "Cloudflare purge success");
        }
      } else {
        const errMsg = body.errors?.map((e) => e.message).join("; ") || `HTTP ${response.status}`;
        for (const url of batch) {
          results.push({
            url,
            provider: "cloudflare",
            status: "failed",
            httpStatus: response.status,
            durationMs,
            error: errMsg,
          });
        }
        logger.error({ error: errMsg, batchSize: batch.length }, "Cloudflare purge failed");
      }
    } catch (err) {
      const durationMs = Date.now() - start;
      const error = err instanceof Error ? err.message : String(err);
      for (const url of batch) {
        results.push({ url, provider: "cloudflare", status: "failed", durationMs, error });
      }
      logger.error({ error, batchSize: batch.length }, "Cloudflare purge error");
    }

    if (i + batchSize < urls.length) {
      await delay(500);
    }
  }

  return results;
}

// ─── Imperva (Incapsula) ───────────────────────────────────────

async function purgeImperva(urls: string[]): Promise<CdnPurgeResult[]> {
  const config = getConfig();
  const { apiId, apiKey, siteId } = config.imperva;

  if (!apiId || !apiKey || !siteId) {
    logger.warn("Imperva purge skipped: missing apiId, apiKey, or siteId");
    return urls.map((url) => ({
      url,
      provider: "imperva" as const,
      status: "skipped" as const,
      durationMs: 0,
      error: "Missing apiId, apiKey, or siteId",
    }));
  }

  const results: CdnPurgeResult[] = [];

  // Imperva purges one URL pattern at a time via the purge_pattern parameter
  for (const url of urls) {
    const start = Date.now();

    try {
      const body = new URLSearchParams({
        api_id: apiId,
        api_key: apiKey,
        site_id: siteId,
        purge_pattern: url,
      });

      const response = await fetch(
        "https://my.incapsula.com/api/prov/v1/sites/performance/purge",
        {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: body.toString(),
          signal: AbortSignal.timeout(30000),
        }
      );

      const durationMs = Date.now() - start;
      const json = await response.json() as { res: number; res_message: string };

      // Imperva returns res=0 for success
      if (json.res === 0) {
        results.push({
          url,
          provider: "imperva",
          status: "success",
          httpStatus: response.status,
          durationMs,
        });
        logger.info({ url, provider: "imperva", durationMs }, "Imperva purge success");
      } else {
        const error = json.res_message || `Imperva error code ${json.res}`;
        results.push({
          url,
          provider: "imperva",
          status: "failed",
          httpStatus: response.status,
          durationMs,
          error,
        });
        logger.error({ url, error }, "Imperva purge failed");
      }
    } catch (err) {
      const durationMs = Date.now() - start;
      const error = err instanceof Error ? err.message : String(err);
      results.push({ url, provider: "imperva", status: "failed", durationMs, error });
      logger.error({ url, error }, "Imperva purge error");
    }

    await delay(200);
  }

  return results;
}

// ─── Akamai (Fast Purge API v3) ────────────────────────────────

/**
 * Generate EdgeGrid Authorization header for Akamai API requests.
 * Implements the EG1-HMAC-SHA256 signing algorithm.
 */
function generateEdgeGridAuth(
  method: string,
  url: string,
  body: string,
  clientToken: string,
  clientSecret: string,
  accessToken: string
): string {
  const parsedUrl = new URL(url);
  const timestamp = new Date()
    .toISOString()
    .replace(/[-:]/g, "")
    .replace(/\.\d+Z$/, "+0000");
  const nonce = randomUUID();

  const authData =
    `EG1-HMAC-SHA256 client_token=${clientToken};` +
    `access_token=${accessToken};` +
    `timestamp=${timestamp};` +
    `nonce=${nonce};`;

  // Content hash: Base64(SHA-256(POST body)) — max 131072 bytes
  const maxBody = body.slice(0, 131072);
  const contentHashReal = createHash("sha256").update(maxBody).digest("base64");

  const dataToSign = [
    method.toUpperCase(),
    "https",
    parsedUrl.hostname,
    parsedUrl.pathname + (parsedUrl.search || ""),
    "", // headers to sign (empty for our use case)
    contentHashReal,
    authData,
  ].join("\t");

  // Signing key = HMAC-SHA256(timestamp, client_secret)
  const signingKey = createHmac("sha256", clientSecret)
    .update(timestamp)
    .digest("base64");

  // Signature = HMAC-SHA256(data_to_sign, signing_key)
  const signature = createHmac("sha256", signingKey)
    .update(dataToSign)
    .digest("base64");

  return `${authData}signature=${signature}`;
}

async function purgeAkamai(urls: string[]): Promise<CdnPurgeResult[]> {
  const config = getConfig();
  const { host, clientToken, clientSecret, accessToken, network } = config.akamai;

  if (!host || !clientToken || !clientSecret || !accessToken) {
    logger.warn("Akamai purge skipped: missing credentials");
    return urls.map((url) => ({
      url,
      provider: "akamai" as const,
      status: "skipped" as const,
      durationMs: 0,
      error: "Missing Akamai credentials (host, clientToken, clientSecret, or accessToken)",
    }));
  }

  const results: CdnPurgeResult[] = [];
  const purgeNetwork = network || "production";

  // Akamai allows up to 50 URLs per invalidation request
  const batchSize = 50;
  for (let i = 0; i < urls.length; i += batchSize) {
    const batch = urls.slice(i, i + batchSize);
    const start = Date.now();

    try {
      const apiUrl = `https://${host}/ccu/v3/invalidate/url/${purgeNetwork}`;
      const bodyStr = JSON.stringify({ objects: batch });

      const authHeader = generateEdgeGridAuth(
        "POST",
        apiUrl,
        bodyStr,
        clientToken,
        clientSecret,
        accessToken
      );

      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          Authorization: authHeader,
          "Content-Type": "application/json",
        },
        body: bodyStr,
        signal: AbortSignal.timeout(30000),
      });

      const durationMs = Date.now() - start;
      const json = await response.json() as {
        httpStatus: number;
        detail: string;
        estimatedSeconds: number;
        purgeId: string;
      };

      if (response.status >= 200 && response.status < 300) {
        for (const url of batch) {
          results.push({
            url,
            provider: "akamai",
            status: "success",
            httpStatus: response.status,
            durationMs,
          });
          logger.info(
            { url, provider: "akamai", purgeId: json.purgeId, estimatedSeconds: json.estimatedSeconds, durationMs },
            "Akamai purge success"
          );
        }
      } else {
        const error = json.detail || `HTTP ${response.status}`;
        for (const url of batch) {
          results.push({
            url,
            provider: "akamai",
            status: "failed",
            httpStatus: response.status,
            durationMs,
            error,
          });
        }
        logger.error({ error, batchSize: batch.length }, "Akamai purge failed");
      }
    } catch (err) {
      const durationMs = Date.now() - start;
      const error = err instanceof Error ? err.message : String(err);
      for (const url of batch) {
        results.push({ url, provider: "akamai", status: "failed", durationMs, error });
      }
      logger.error({ error, batchSize: batch.length }, "Akamai purge error");
    }

    if (i + batchSize < urls.length) {
      await delay(1000);
    }
  }

  return results;
}

// ─── Public API ────────────────────────────────────────────────

export type CdnProvider = "cloudflare" | "imperva" | "akamai";

/**
 * Purge and warm URLs across all enabled CDN providers.
 * This runs purge requests against Cloudflare, Imperva, and/or Akamai
 * based on which providers are enabled in config.
 */
export async function purgeCdnCache(
  urls: string[],
  onProgress?: (result: CdnPurgeResult) => void
): Promise<CdnPurgeResult[]> {
  const config = getConfig();
  const allResults: CdnPurgeResult[] = [];

  const providers: Array<{
    name: CdnProvider;
    enabled: boolean;
    fn: (urls: string[]) => Promise<CdnPurgeResult[]>;
  }> = [
    { name: "cloudflare", enabled: config.cloudflare?.enabled ?? false, fn: purgeCloudflare },
    { name: "imperva", enabled: config.imperva?.enabled ?? false, fn: purgeImperva },
    { name: "akamai", enabled: config.akamai?.enabled ?? false, fn: purgeAkamai },
  ];

  const enabledProviders = providers.filter((p) => p.enabled);

  if (enabledProviders.length === 0) {
    logger.info("CDN purge skipped: no CDN providers enabled");
    return urls.map((url) => ({
      url,
      provider: "cloudflare" as const,
      status: "skipped" as const,
      durationMs: 0,
      error: "No CDN providers enabled",
    }));
  }

  for (const provider of enabledProviders) {
    logger.info({ provider: provider.name, urlCount: urls.length }, "Starting CDN purge");
    const results = await provider.fn(urls);
    for (const result of results) {
      allResults.push(result);
      onProgress?.(result);
    }
  }

  return allResults;
}
