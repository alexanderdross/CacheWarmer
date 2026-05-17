import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getSchemaResults, getSchemaSummary } from "@/lib/db/database";

export async function GET(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const { searchParams } = new URL(request.url);
  const jobId = searchParams.get("jobId");

  if (!jobId) {
    return NextResponse.json({ error: "jobId is required" }, { status: 400 });
  }

  try {
    const summary = getSchemaSummary(jobId);
    const results = (getSchemaResults(jobId) as Record<string, unknown>[]).map((r) => ({
      ...r,
      schemas: r.schemas ? JSON.parse(r.schemas as string) : [],
      errors: r.errors ? JSON.parse(r.errors as string) : [],
      warnings: r.warnings ? JSON.parse(r.warnings as string) : [],
    }));

    return NextResponse.json({ jobId, summary, results });
  } catch {
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
