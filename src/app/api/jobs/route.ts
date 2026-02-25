import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { listJobs } from "@/lib/queue/job-manager";

export async function GET(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const searchParams = request.nextUrl.searchParams;
  const limit = Math.min(parseInt(searchParams.get("limit") || "50"), 100);
  const offset = parseInt(searchParams.get("offset") || "0");

  const jobs = listJobs(limit, offset);

  return NextResponse.json({
    jobs: jobs.map((job) => ({
      ...job,
      targets: JSON.parse(job.targets),
    })),
    limit,
    offset,
  });
}
