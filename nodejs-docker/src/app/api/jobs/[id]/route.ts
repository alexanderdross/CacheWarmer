import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getJob, deleteJob, getJobStats, getJobResults } from "@/lib/queue/job-manager";

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const { id } = await params;
  const job = getJob(id);
  if (!job) {
    return NextResponse.json({ error: "Job not found" }, { status: 404 });
  }

  const stats = getJobStats(id);
  const includeResults = request.nextUrl.searchParams.get("results") === "true";
  const results = includeResults ? getJobResults(id) : undefined;

  return NextResponse.json({
    ...job,
    targets: JSON.parse(job.targets),
    stats,
    results,
  });
}

export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const { id } = await params;
  const deleted = deleteJob(id);
  if (!deleted) {
    return NextResponse.json({ error: "Job not found" }, { status: 404 });
  }

  return NextResponse.json({ message: "Job deleted" });
}
