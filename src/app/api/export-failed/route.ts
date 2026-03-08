import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getFailedSkippedResults } from "@/lib/queue/job-manager";

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const body = await request.json();
    const { jobId, format = "csv" } = body;

    if (!jobId || typeof jobId !== "string") {
      return NextResponse.json({ error: "jobId is required" }, { status: 400 });
    }

    const results = getFailedSkippedResults(jobId) as Array<{
      url: string;
      target: string;
      status: string;
      http_status: number | null;
      duration_ms: number | null;
      error: string | null;
      created_at: string;
    }>;

    if (format === "json") {
      return NextResponse.json({
        format: "json",
        content: results,
        count: results.length,
        filename: `cachewarmer-failed-${jobId}.json`,
      });
    }

    // Default: CSV
    let csv = "url,target,status,http_status,duration_ms,error,created_at\n";
    for (const r of results) {
      csv += `"${(r.url || "").replace(/"/g, '""')}","${r.target}","${r.status}",${r.http_status ?? ""},${r.duration_ms ?? ""},"${(r.error || "").replace(/"/g, '""')}","${r.created_at}"\n`;
    }

    return NextResponse.json({
      format: "csv",
      content: csv,
      count: results.length,
      filename: `cachewarmer-failed-${jobId}.csv`,
    });
  } catch {
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
