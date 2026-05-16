"use client";

import { useEffect, useState } from "react";

interface SchemaRow {
  id: string;
  url: string;
  canonical_url: string;
  page_type: string;
  schema_type: string;
  status: "pass" | "warn" | "fail";
  error_count: number;
  warning_count: number;
  fingerprint: string;
  github_issue_number: number | null;
  github_pr_number: number | null;
  created_at: string;
}

interface SchemaValidationResponse {
  jobId: string;
  results: SchemaRow[];
}

export function SchemaValidationPanel({ jobId, apiKey }: { jobId: string; apiKey?: string }) {
  const [data, setData] = useState<SchemaValidationResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const headers: Record<string, string> = {};
    if (apiKey) headers["Authorization"] = `Bearer ${apiKey}`;
    fetch(`/api/jobs/${jobId}/schema-validation`, { headers })
      .then(async (r) => {
        if (!r.ok) throw new Error(await r.text());
        return r.json() as Promise<SchemaValidationResponse>;
      })
      .then((d) => setData(d))
      .catch((e) => setError(e instanceof Error ? e.message : String(e)))
      .finally(() => setLoading(false));
  }, [jobId, apiKey]);

  if (loading) return <div className="text-gray-400 text-sm">Loading schema validation results…</div>;
  if (error) return <div className="text-red-400 text-sm">Failed to load: {error}</div>;
  if (!data || data.results.length === 0) {
    return (
      <div className="text-gray-400 text-sm">
        No schema validation results recorded for this job. Either schema validation is disabled, or no URLs in the job
        matched the scoped detail-page patterns.
      </div>
    );
  }

  const byStatus = {
    pass: data.results.filter((r) => r.status === "pass").length,
    warn: data.results.filter((r) => r.status === "warn").length,
    fail: data.results.filter((r) => r.status === "fail").length,
  };

  return (
    <div className="space-y-3">
      <div className="flex gap-3 text-sm">
        <span className="px-2 py-1 rounded bg-green-900 text-green-300">{byStatus.pass} pass</span>
        <span className="px-2 py-1 rounded bg-yellow-900 text-yellow-300">{byStatus.warn} warn</span>
        <span className="px-2 py-1 rounded bg-red-900 text-red-300">{byStatus.fail} fail</span>
      </div>
      <table className="w-full text-xs">
        <thead>
          <tr className="text-left text-gray-400 border-b border-gray-700">
            <th className="py-2">URL</th>
            <th className="py-2">Page type</th>
            <th className="py-2">Schema</th>
            <th className="py-2">Status</th>
            <th className="py-2">Errors</th>
            <th className="py-2">Issue</th>
          </tr>
        </thead>
        <tbody>
          {data.results.map((r) => (
            <tr key={r.id} className="border-b border-gray-800">
              <td className="py-2 font-mono truncate max-w-md" title={r.url}>{r.canonical_url}</td>
              <td className="py-2">{r.page_type}</td>
              <td className="py-2">{r.schema_type}</td>
              <td className="py-2">
                <span
                  className={`px-1.5 py-0.5 rounded text-xs ${
                    r.status === "pass"
                      ? "bg-green-900 text-green-300"
                      : r.status === "warn"
                      ? "bg-yellow-900 text-yellow-300"
                      : "bg-red-900 text-red-300"
                  }`}
                >
                  {r.status}
                </span>
              </td>
              <td className="py-2">{r.error_count}</td>
              <td className="py-2">
                {r.github_issue_number ? `#${r.github_issue_number}` : "-"}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
