import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getSchemaResults } from "@/lib/db/database";

export async function POST(request: NextRequest) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  try {
    const body = await request.json();
    const { jobId, format = "json", filter = "all" } = body;

    if (!jobId || typeof jobId !== "string") {
      return NextResponse.json({ error: "jobId is required" }, { status: 400 });
    }

    let results = (getSchemaResults(jobId) as Record<string, unknown>[]).map((r) => ({
      url: r.url as string,
      status: r.status as string,
      schemas: r.schemas ? JSON.parse(r.schemas as string) : [],
      errors: r.errors ? JSON.parse(r.errors as string) : [],
      warnings: r.warnings ? JSON.parse(r.warnings as string) : [],
      duration_ms: r.duration_ms as number | null,
      error: r.error as string | null,
    }));

    if (filter === "errors") {
      results = results.filter((r) => r.status === "errors" || r.status === "failed");
    } else if (filter === "warnings") {
      results = results.filter((r) => r.status === "warnings" || r.status === "errors");
    }

    if (format === "csv") {
      let csv = "url,status,schemas,severity,schema_type,test,message\n";
      for (const r of results) {
        const schemasStr = r.schemas.join("; ");
        const issues = [
          ...r.errors.map((e: { type: string; test: string; message: string }) => ({ ...e, severity: "error" })),
          ...r.warnings.map((w: { type: string; test: string; message: string }) => ({ ...w, severity: "warning" })),
        ];

        if (issues.length === 0) {
          csv += `"${r.url}","${r.status}","${schemasStr}","","","",""\n`;
        } else {
          for (const issue of issues) {
            csv += `"${r.url}","${r.status}","${schemasStr}","${issue.severity}","${(issue.type || "").replace(/"/g, '""')}","${(issue.test || "").replace(/"/g, '""')}","${(issue.message || "").replace(/"/g, '""')}"\n`;
          }
        }
      }

      return NextResponse.json({
        format: "csv",
        content: csv,
        filename: `schema-report-${jobId}.csv`,
      });
    }

    return NextResponse.json({
      format: "json",
      content: results,
      filename: `schema-report-${jobId}.json`,
    });
  } catch {
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
