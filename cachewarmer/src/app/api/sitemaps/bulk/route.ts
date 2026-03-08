import { NextRequest, NextResponse } from "next/server";
import { v4 as uuidv4 } from "uuid";
import { authenticateRequest } from "@/lib/auth";
import { getDb, normalizeUrl } from "@/lib/db/database";

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const body = await request.json();
    const { urls } = body;

    if (!urls || typeof urls !== "string") {
      return NextResponse.json({ error: "urls is required (newline-separated string)" }, { status: 400 });
    }

    const lines = urls
      .split("\n")
      .map((line: string) => line.trim())
      .filter((line: string) => line.length > 0);

    const db = getDb();
    const stmt = db.prepare(
      "INSERT INTO sitemaps (id, url, domain, cron_expression) VALUES (?, ?, ?, ?)"
    );

    const added: Array<{ id: string; url: string; domain: string }> = [];
    const errors: string[] = [];
    const duplicates: string[] = [];
    const checkDuplicate = db.prepare("SELECT id FROM sitemaps WHERE url = ?");

    for (const line of lines) {
      try {
        const parsed = new URL(line);
        const normalized = normalizeUrl(line);
        if (checkDuplicate.get(normalized)) {
          duplicates.push(line);
          continue;
        }
        const id = uuidv4();
        stmt.run(id, normalized, parsed.hostname, null);
        added.push({ id, url: normalized, domain: parsed.hostname });
      } catch {
        errors.push(line);
      }
    }

    return NextResponse.json({ added, errors, duplicates }, { status: 201 });
  } catch {
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
