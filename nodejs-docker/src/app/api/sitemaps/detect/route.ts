import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import logger from "@/lib/logger";

const COMMON_SITEMAP_PATHS = [
  "/sitemap.xml",
  "/sitemap_index.xml",
  "/sitemap-index.xml",
  "/wp-sitemap.xml",
  "/sitemap/sitemap-index.xml",
];

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const body = await request.json().catch(() => ({}));
    const { domain } = body as { domain?: string };

    if (!domain) {
      return NextResponse.json({ error: "domain is required" }, { status: 400 });
    }

    const baseUrl = domain.startsWith("http") ? domain : `https://${domain}`;
    const found: string[] = [];

    for (const sitemapPath of COMMON_SITEMAP_PATHS) {
      const url = `${baseUrl.replace(/\/$/, "")}${sitemapPath}`;
      try {
        const res = await fetch(url, {
          method: "HEAD",
          signal: AbortSignal.timeout(5000),
        });

        if (res.ok) {
          const contentType = res.headers.get("content-type") || "";
          if (
            contentType.includes("xml") ||
            contentType.includes("text/plain") ||
            contentType.includes("text/html")
          ) {
            found.push(url);
            logger.info({ url }, "Auto-detected sitemap");
          }
        }
      } catch {
        // Ignore network errors for individual probes
      }
    }

    return NextResponse.json({ sitemaps: found, domain: baseUrl });
  } catch {
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
