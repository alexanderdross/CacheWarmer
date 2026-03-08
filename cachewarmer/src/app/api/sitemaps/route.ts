import { NextRequest, NextResponse } from "next/server";
import { v4 as uuidv4 } from "uuid";
import { authenticateRequest } from "@/lib/auth";
import { getDb, normalizeUrl } from "@/lib/db/database";

export async function GET(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const db = getDb();
  const sitemaps = db.prepare("SELECT * FROM sitemaps ORDER BY created_at DESC").all();
  return NextResponse.json({ sitemaps });
}

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const body = await request.json();
    const { url: rawUrl, cronExpression } = body;

    if (!rawUrl || typeof rawUrl !== "string") {
      return NextResponse.json({ error: "url is required" }, { status: 400 });
    }

    let domain: string;
    try {
      domain = new URL(rawUrl).hostname;
    } catch {
      return NextResponse.json({ error: "Invalid URL" }, { status: 400 });
    }

    // Normalize so duplicate check and storage use the same form.
    const url = normalizeUrl(rawUrl);
    const db = getDb();

    // Check for duplicate URL
    const existing = db.prepare("SELECT id FROM sitemaps WHERE url = ?").get(url);
    if (existing) {
      return NextResponse.json({ error: "Diese Sitemap ist bereits registriert." }, { status: 409 });
    }

    const id = uuidv4();

    db.prepare(`
      INSERT INTO sitemaps (id, url, domain, cron_expression)
      VALUES (?, ?, ?, ?)
    `).run(id, url, domain, cronExpression || null);

    const sitemap = db.prepare("SELECT * FROM sitemaps WHERE id = ?").get(id);
    return NextResponse.json(sitemap, { status: 201 });
  } catch {
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
