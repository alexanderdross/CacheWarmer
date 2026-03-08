import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { createJob, processJob, DuplicateJobError, type WarmTarget } from "@/lib/queue/job-manager";
import logger from "@/lib/logger";

const VALID_TARGETS: WarmTarget[] = ["cdn", "facebook", "linkedin", "twitter", "google", "bing", "indexnow", "pinterest", "cdn-purge"];

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const body = await request.json();
    const { sitemapUrl, targets } = body;

    if (!sitemapUrl || typeof sitemapUrl !== "string") {
      return NextResponse.json({ error: "sitemapUrl is required" }, { status: 400 });
    }

    // Validate URL
    try {
      new URL(sitemapUrl);
    } catch {
      return NextResponse.json({ error: "Invalid sitemapUrl" }, { status: 400 });
    }

    // Validate targets
    const requestedTargets: WarmTarget[] = Array.isArray(targets) && targets.length > 0
      ? targets.filter((t: string) => VALID_TARGETS.includes(t as WarmTarget)) as WarmTarget[]
      : [...VALID_TARGETS];

    let job;
    try {
      job = createJob({ sitemapUrl, targets: requestedTargets });
    } catch (err) {
      if (err instanceof DuplicateJobError) {
        return NextResponse.json({
          error: "An active job already exists for this sitemap URL.",
          existingJobId: err.existingJobId,
        }, { status: 409 });
      }
      throw err;
    }

    // Start processing in background (non-blocking)
    processJob(job.id).catch((err) => {
      logger.error({ jobId: job.id, error: err }, "Background job processing failed");
    });

    return NextResponse.json({
      jobId: job.id,
      status: job.status,
      sitemapUrl,
      targets: requestedTargets,
      createdAt: job.created_at,
    }, { status: 202 });
  } catch (err) {
    logger.error({ error: err }, "Failed to create warming job");
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
