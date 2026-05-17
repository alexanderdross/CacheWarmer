"use client";

import { useState, useEffect, useCallback } from "react";

interface SchemaIssue {
  type: string;
  test: string;
  message: string;
  severity: "error" | "warning";
}

interface SchemaResult {
  url: string;
  status: string;
  schemas: string[];
  errors: SchemaIssue[];
  warnings: SchemaIssue[];
  duration_ms: number | null;
  error: string | null;
}

interface SchemaSummary {
  total: number;
  valid: number;
  withWarnings: number;
  withErrors: number;
  failed: number;
}

interface SchemaReportProps {
  jobId: string;
}

export default function SchemaReport({ jobId }: SchemaReportProps) {
  const [summary, setSummary] = useState<SchemaSummary | null>(null);
  const [results, setResults] = useState<SchemaResult[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<string>("all");
  const [expandedUrls, setExpandedUrls] = useState<Record<string, boolean>>({});
  const [exporting, setExporting] = useState(false);

  const fetchReport = useCallback(async () => {
    try {
      const res = await fetch(`/api/schema-report?jobId=${jobId}`);
      if (res.ok) {
        const data = await res.json();
        setSummary(data.summary);
        setResults(data.results || []);
      }
    } finally {
      setLoading(false);
    }
  }, [jobId]);

  useEffect(() => {
    fetchReport();
  }, [fetchReport]);

  const toggleUrl = (url: string) => {
    setExpandedUrls((prev) => ({ ...prev, [url]: !prev[url] }));
  };

  const handleExport = async (format: "csv" | "json") => {
    setExporting(true);
    try {
      const res = await fetch("/api/schema-report/export", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ jobId, format, filter }),
      });
      if (res.ok) {
        const data = await res.json();
        const blob = new Blob(
          [typeof data.content === "string" ? data.content : JSON.stringify(data.content, null, 2)],
          { type: format === "csv" ? "text/csv" : "application/json" }
        );
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = data.filename;
        a.click();
        URL.revokeObjectURL(url);
      }
    } finally {
      setExporting(false);
    }
  };

  const filteredResults = results.filter((r) => {
    if (filter === "errors") return r.status === "errors" || r.status === "failed";
    if (filter === "warnings") return r.status === "warnings" || r.status === "errors" || r.status === "failed";
    if (filter === "valid") return r.status === "valid";
    return true;
  });

  if (loading) {
    return (
      <div className="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <p className="text-sm text-gray-500">Lade Schema-Validierung...</p>
      </div>
    );
  }

  if (!summary || summary.total === 0) return null;

  return (
    <div className="bg-gray-900 border border-gray-800 rounded-lg p-6 space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-md font-semibold">Schema-Validierung (Structured Data)</h3>
        <div className="flex gap-2">
          <button
            onClick={() => handleExport("csv")}
            disabled={exporting}
            className="bg-gray-800 hover:bg-gray-700 disabled:opacity-50 text-gray-300 text-xs font-medium py-1.5 px-3 rounded-md transition-colors border border-gray-700"
          >
            Export CSV
          </button>
          <button
            onClick={() => handleExport("json")}
            disabled={exporting}
            className="bg-gray-800 hover:bg-gray-700 disabled:opacity-50 text-gray-300 text-xs font-medium py-1.5 px-3 rounded-md transition-colors border border-gray-700"
          >
            Export JSON
          </button>
        </div>
      </div>

      {/* Summary badges */}
      <div className="flex gap-3 flex-wrap">
        <span className="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-800 text-gray-300">
          {summary.total} Seiten
        </span>
        {summary.valid > 0 && (
          <span className="px-3 py-1.5 rounded-md text-sm font-medium bg-green-900/50 text-green-300">
            {summary.valid} Valid
          </span>
        )}
        {summary.withWarnings > 0 && (
          <span className="px-3 py-1.5 rounded-md text-sm font-medium bg-yellow-900/50 text-yellow-300">
            {summary.withWarnings} Warnungen
          </span>
        )}
        {summary.withErrors > 0 && (
          <span className="px-3 py-1.5 rounded-md text-sm font-medium bg-red-900/50 text-red-300">
            {summary.withErrors} Fehler
          </span>
        )}
        {summary.failed > 0 && (
          <span className="px-3 py-1.5 rounded-md text-sm font-medium bg-red-900/50 text-red-300">
            {summary.failed} Fehlgeschlagen
          </span>
        )}
      </div>

      {/* Filter */}
      <div className="flex gap-3">
        <select
          value={filter}
          onChange={(e) => setFilter(e.target.value)}
          className="bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-xs text-white focus:outline-none focus:ring-1 focus:ring-orange-500"
        >
          <option value="all">Alle ({summary.total})</option>
          <option value="errors">Nur Fehler ({summary.withErrors + summary.failed})</option>
          <option value="warnings">Fehler + Warnungen ({summary.withErrors + summary.withWarnings + summary.failed})</option>
          <option value="valid">Nur Valid ({summary.valid})</option>
        </select>
        <span className="text-xs text-gray-500 self-center">{filteredResults.length} Ergebnisse</span>
      </div>

      {/* Results list */}
      <div className="space-y-2 max-h-[600px] overflow-y-auto">
        {filteredResults.map((r, idx) => {
          const isExpanded = expandedUrls[r.url] || false;
          const issueCount = r.errors.length + r.warnings.length;

          return (
            <div key={idx} className="border border-gray-800 rounded-lg overflow-hidden">
              <div
                className="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-800/50 transition-colors"
                onClick={() => issueCount > 0 && toggleUrl(r.url)}
              >
                <span className={`px-2 py-0.5 rounded text-xs font-medium flex-shrink-0 ${
                  r.status === "valid" ? "bg-green-900/50 text-green-300" :
                  r.status === "warnings" ? "bg-yellow-900/50 text-yellow-300" :
                  r.status === "errors" ? "bg-red-900/50 text-red-300" :
                  "bg-red-900/50 text-red-300"
                }`}>
                  {r.status === "valid" ? "VALID" :
                   r.status === "warnings" ? "WARN" :
                   r.status === "errors" ? "ERROR" : "FAIL"}
                </span>

                <span className="font-mono text-xs text-gray-300 truncate flex-1" title={r.url}>
                  {r.url}
                </span>

                {r.schemas.length > 0 && (
                  <span className="text-xs text-gray-500 flex-shrink-0 hidden md:inline">
                    {r.schemas.join(", ")}
                  </span>
                )}

                {r.duration_ms && (
                  <span className="text-xs text-gray-600 flex-shrink-0">{r.duration_ms}ms</span>
                )}

                {issueCount > 0 && (
                  <span className={`text-gray-500 text-xs transition-transform duration-200 ${isExpanded ? "rotate-180" : ""}`}>
                    &#9660;
                  </span>
                )}
              </div>

              {isExpanded && issueCount > 0 && (
                <div className="border-t border-gray-800 p-3 bg-gray-950/50 space-y-2">
                  {r.errors.map((issue, i) => (
                    <div key={`err-${i}`} className="flex items-start gap-2 text-xs">
                      <span className="px-1.5 py-0.5 rounded bg-red-900/50 text-red-300 font-medium flex-shrink-0">ERROR</span>
                      <span className="text-gray-400 flex-shrink-0">[{issue.type}]</span>
                      <span className="text-gray-300">{issue.message}</span>
                    </div>
                  ))}
                  {r.warnings.map((issue, i) => (
                    <div key={`warn-${i}`} className="flex items-start gap-2 text-xs">
                      <span className="px-1.5 py-0.5 rounded bg-yellow-900/50 text-yellow-300 font-medium flex-shrink-0">WARN</span>
                      <span className="text-gray-400 flex-shrink-0">[{issue.type}]</span>
                      <span className="text-gray-300">{issue.message}</span>
                    </div>
                  ))}
                  {r.error && (
                    <div className="text-xs text-red-400">{r.error}</div>
                  )}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
