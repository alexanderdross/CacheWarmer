import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getDb } from "@/lib/db/database";

export async function GET(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const searchParams = request.nextUrl.searchParams;
  const limit = Math.min(parseInt(searchParams.get("limit") || "100") || 100, 500);
  const offset = parseInt(searchParams.get("offset") || "0") || 0;
  const jobId = searchParams.get("jobId");

  const db = getDb();

  let results;
  if (jobId) {
    results = db.prepare(
      "SELECT * FROM url_results WHERE job_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
    ).all(jobId, limit, offset);
  } else {
    results = db.prepare(
      "SELECT * FROM url_results ORDER BY created_at DESC LIMIT ? OFFSET ?"
    ).all(limit, offset);
  }

  return NextResponse.json({ logs: results, limit, offset });
}
