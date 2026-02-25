import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getDb } from "@/lib/db/database";

export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const { id } = await params;
  const db = getDb();
  const result = db.prepare("DELETE FROM sitemaps WHERE id = ?").run(id);

  if (result.changes === 0) {
    return NextResponse.json({ error: "Sitemap not found" }, { status: 404 });
  }

  return NextResponse.json({ message: "Sitemap deleted" });
}
