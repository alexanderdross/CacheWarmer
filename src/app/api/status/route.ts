import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getDb } from "@/lib/db/database";

export async function GET(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const db = getDb();

  const jobCounts = db.prepare(`
    SELECT status, COUNT(*) as count FROM jobs GROUP BY status
  `).all() as Array<{ status: string; count: number }>;

  const totalUrls = db.prepare("SELECT COUNT(*) as count FROM url_results").get() as { count: number };
  const recentJobs = db.prepare("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5").all();

  return NextResponse.json({
    status: "healthy",
    uptime: process.uptime(),
    jobs: Object.fromEntries(jobCounts.map((r) => [r.status, r.count])),
    totalUrlsProcessed: totalUrls.count,
    recentJobs,
  });
}
