"use client";

import { useState, useEffect, useCallback } from "react";

interface Stat {
  target: string;
  status: string;
  count: number;
}

interface UrlResult {
  id: string;
  url: string;
  target: string;
  viewport: string | null;
  status: string;
  http_status: number | null;
  duration_ms: number | null;
  error: string | null;
  cache_headers: string | null;
  created_at: string;
}

interface JobDetailData {
  id: string;
  sitemap_url: string;
  status: string;
  total_urls: number;
  processed_urls: number;
  targets: string[];
  started_at: string | null;
  completed_at: string | null;
  error: string | null;
  created_at: string;
  stats: Stat[];
}

interface JobDetailProps {
  job: JobDetailData;
  onBack: () => void;
}

function CacheHeaderBadge({ cacheHeadersJson }: { cacheHeadersJson: string | null }) {
  if (!cacheHeadersJson) return <span className="text-gray-600">-</span>;
  try {
    const h = JSON.parse(cacheHeadersJson);
    const cacheStatus = h.cfCacheStatus || h.xCache;
    if (!cacheStatus) return <span className="text-gray-600">-</span>;
    const isHit = /HIT/i.test(cacheStatus);
    return (
      <span className={`px-1.5 py-0.5 rounded text-xs font-medium ${
        isHit ? "bg-green-900 text-green-300" : "bg-yellow-900 text-yellow-300"
      }`}>
        {cacheStatus}
        {h.age ? ` (${h.age}s)` : ""}
      </span>
    );
  } catch {
    return <span className="text-gray-600">-</span>;
  }
}

export default function JobDetail({ job, onBack }: JobDetailProps) {
  const [exporting, setExporting] = useState(false);
  const [results, setResults] = useState<UrlResult[]>([]);
  const [loadingResults, setLoadingResults] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const [filterTarget, setFilterTarget] = useState<string>("all");
  const [filterStatus, setFilterStatus] = useState<string>("all");
  const [page, setPage] = useState(0);
  const PAGE_SIZE = 50;

  const statsByTarget: Record<string, Record<string, number>> = {};
  for (const stat of job.stats) {
    if (!statsByTarget[stat.target]) statsByTarget[stat.target] = {};
    statsByTarget[stat.target][stat.status] = stat.count;
  }

  const fetchResults = useCallback(async () => {
    setLoadingResults(true);
    try {
      const res = await fetch(`/api/jobs/${job.id}?results=true`);
      if (res.ok) {
        const data = await res.json();
        setResults(data.results || []);
      }
    } finally {
      setLoadingResults(false);
    }
  }, [job.id]);

  useEffect(() => {
    if (showResults && results.length === 0) {
      fetchResults();
    }
  }, [showResults, results.length, fetchResults]);

  const filteredResults = results.filter((r) => {
    if (filterTarget !== "all" && r.target !== filterTarget) return false;
    if (filterStatus !== "all" && r.status !== filterStatus) return false;
    return true;
  });

  const totalPages = Math.ceil(filteredResults.length / PAGE_SIZE);
  const pagedResults = filteredResults.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE);

  // Reset page when filters change
  useEffect(() => { setPage(0); }, [filterTarget, filterStatus]);

  const handleExport = async (format: "csv" | "json") => {
    setExporting(true);
    try {
      const res = await fetch("/api/export", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ jobId: job.id, format }),
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

  const uniqueTargets = [...new Set(results.map((r) => r.target))];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <button onClick={onBack} className="text-orange-500 hover:text-orange-400 text-sm">
          &larr; Zurueck zur Uebersicht
        </button>
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

      <div className="bg-gray-900 border border-gray-800 rounded-lg p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold">Job Details</h2>
          <span className={`px-2 py-1 rounded text-xs font-medium ${
            job.status === "completed" ? "bg-green-900 text-green-300" :
            job.status === "running" ? "bg-blue-900 text-blue-300" :
            job.status === "failed" ? "bg-red-900 text-red-300" :
            "bg-yellow-900 text-yellow-300"
          }`}>
            {job.status}
          </span>
        </div>

        <dl className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt className="text-gray-400">Job ID</dt>
            <dd className="font-mono text-xs mt-1">{job.id}</dd>
          </div>
          <div>
            <dt className="text-gray-400">Sitemap</dt>
            <dd className="font-mono text-xs mt-1 truncate">{job.sitemap_url}</dd>
          </div>
          <div>
            <dt className="text-gray-400">Erstellt</dt>
            <dd className="mt-1">{new Date(job.created_at).toLocaleString("de-DE")}</dd>
          </div>
          <div>
            <dt className="text-gray-400">Abgeschlossen</dt>
            <dd className="mt-1">{job.completed_at ? new Date(job.completed_at).toLocaleString("de-DE") : "-"}</dd>
          </div>
          <div>
            <dt className="text-gray-400">Fortschritt</dt>
            <dd className="mt-1">{job.processed_urls} / {job.total_urls} URLs</dd>
          </div>
          <div>
            <dt className="text-gray-400">Targets</dt>
            <dd className="mt-1 flex gap-1 flex-wrap">
              {job.targets.map((t) => (
                <span key={t} className="px-1.5 py-0.5 bg-gray-800 rounded text-xs">{t}</span>
              ))}
            </dd>
          </div>
        </dl>

        {job.error && (
          <div className="bg-red-900/30 border border-red-800 rounded p-3 text-sm text-red-300">
            {job.error}
          </div>
        )}
      </div>

      {Object.keys(statsByTarget).length > 0 && (
        <div className="bg-gray-900 border border-gray-800 rounded-lg p-6">
          <h3 className="text-md font-semibold mb-4">Ergebnisse pro Target</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {Object.entries(statsByTarget).map(([target, stats]) => (
              <div key={target} className="bg-gray-800 rounded-lg p-4">
                <h4 className="text-sm font-medium text-orange-400 mb-2 capitalize">{target}</h4>
                <div className="space-y-1 text-sm">
                  {stats.success && (
                    <div className="flex justify-between">
                      <span className="text-green-400">Erfolgreich</span>
                      <span>{stats.success}</span>
                    </div>
                  )}
                  {stats.failed && (
                    <div className="flex justify-between">
                      <span className="text-red-400">Fehlgeschlagen</span>
                      <span>{stats.failed}</span>
                    </div>
                  )}
                  {stats.skipped && (
                    <div className="flex justify-between">
                      <span className="text-gray-400">Uebersprungen</span>
                      <span>{stats.skipped}</span>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Per-URL Results */}
      <div className="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-md font-semibold">Einzelergebnisse pro URL</h3>
          {!showResults ? (
            <button
              onClick={() => setShowResults(true)}
              className="bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-medium py-1.5 px-3 rounded-md transition-colors border border-gray-700"
            >
              Ergebnisse laden
            </button>
          ) : (
            <span className="text-xs text-gray-500">{filteredResults.length} Ergebnisse</span>
          )}
        </div>

        {loadingResults && (
          <p className="text-sm text-gray-500">Lade Ergebnisse...</p>
        )}

        {showResults && !loadingResults && results.length > 0 && (
          <>
            {/* Filters */}
            <div className="flex gap-3 mb-4">
              <select
                value={filterTarget}
                onChange={(e) => setFilterTarget(e.target.value)}
                className="bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-xs text-white focus:outline-none focus:ring-1 focus:ring-orange-500"
              >
                <option value="all">Alle Targets</option>
                {uniqueTargets.map((t) => (
                  <option key={t} value={t}>{t}</option>
                ))}
              </select>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="bg-gray-800 border border-gray-700 rounded-md px-2 py-1 text-xs text-white focus:outline-none focus:ring-1 focus:ring-orange-500"
              >
                <option value="all">Alle Status</option>
                <option value="success">Erfolgreich</option>
                <option value="failed">Fehlgeschlagen</option>
              </select>
            </div>

            {/* Results Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-xs">
                <thead>
                  <tr className="border-b border-gray-800 text-left text-gray-400">
                    <th className="pb-2 pr-3">URL</th>
                    <th className="pb-2 pr-3">Target</th>
                    <th className="pb-2 pr-3">Viewport</th>
                    <th className="pb-2 pr-3">Status</th>
                    <th className="pb-2 pr-3">HTTP</th>
                    <th className="pb-2 pr-3">Dauer</th>
                    <th className="pb-2 pr-3">Cache</th>
                    <th className="pb-2">Fehler</th>
                  </tr>
                </thead>
                <tbody>
                  {pagedResults.map((r) => (
                    <tr key={r.id} className="border-b border-gray-800/30 hover:bg-gray-800/20">
                      <td className="py-1.5 pr-3 font-mono text-gray-300 truncate max-w-xs" title={r.url}>
                        {r.url}
                      </td>
                      <td className="py-1.5 pr-3 capitalize">{r.target}</td>
                      <td className="py-1.5 pr-3 text-gray-400">{r.viewport || "-"}</td>
                      <td className="py-1.5 pr-3">
                        <span className={r.status === "success" ? "text-green-400" : "text-red-400"}>
                          {r.status === "success" ? "OK" : "FAIL"}
                        </span>
                      </td>
                      <td className="py-1.5 pr-3 text-gray-400">{r.http_status || "-"}</td>
                      <td className="py-1.5 pr-3 text-gray-400">{r.duration_ms ? `${r.duration_ms}ms` : "-"}</td>
                      <td className="py-1.5 pr-3"><CacheHeaderBadge cacheHeadersJson={r.cache_headers} /></td>
                      <td className="py-1.5 text-red-400 truncate max-w-xs" title={r.error || ""}>
                        {r.error || ""}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex items-center justify-between mt-3">
                <span className="text-xs text-gray-500">
                  Seite {page + 1} von {totalPages}
                </span>
                <div className="flex gap-2">
                  <button
                    onClick={() => setPage(Math.max(0, page - 1))}
                    disabled={page === 0}
                    className="bg-gray-800 hover:bg-gray-700 disabled:opacity-30 text-gray-300 text-xs py-1 px-2 rounded border border-gray-700"
                  >
                    Zurueck
                  </button>
                  <button
                    onClick={() => setPage(Math.min(totalPages - 1, page + 1))}
                    disabled={page >= totalPages - 1}
                    className="bg-gray-800 hover:bg-gray-700 disabled:opacity-30 text-gray-300 text-xs py-1 px-2 rounded border border-gray-700"
                  >
                    Weiter
                  </button>
                </div>
              </div>
            )}
          </>
        )}

        {showResults && !loadingResults && results.length === 0 && (
          <p className="text-sm text-gray-500">Noch keine Ergebnisse vorhanden.</p>
        )}
      </div>
    </div>
  );
}
