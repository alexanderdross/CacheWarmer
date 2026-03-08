"use client";

interface Job {
  id: string;
  sitemap_url: string;
  status: string;
  total_urls: number;
  processed_urls: number;
  targets: string[];
  created_at: string;
  completed_at: string | null;
}

interface JobTableProps {
  jobs: Job[];
  onViewJob: (jobId: string) => void;
}

const statusColors: Record<string, string> = {
  queued: "bg-yellow-900 text-yellow-300",
  running: "bg-blue-900 text-blue-300",
  completed: "bg-green-900 text-green-300",
  failed: "bg-red-900 text-red-300",
};

export default function JobTable({ jobs, onViewJob }: JobTableProps) {
  if (jobs.length === 0) {
    return (
      <div className="bg-gray-900 border border-gray-800 rounded-lg p-8 text-center text-gray-500">
        Noch keine Jobs vorhanden. Starte einen neuen Warming-Job.
      </div>
    );
  }

  return (
    <>
      {/* Desktop table — hidden on mobile */}
      <div className="hidden md:block bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-800 text-left text-sm text-gray-400">
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Sitemap</th>
              <th className="px-4 py-3">Fortschritt</th>
              <th className="px-4 py-3">Targets</th>
              <th className="px-4 py-3">Erstellt</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            {jobs.map((job) => (
              <tr key={job.id} className="border-b border-gray-800/50 hover:bg-gray-800/30">
                <td className="px-4 py-3">
                  <span className={`px-2 py-1 rounded text-xs font-medium ${statusColors[job.status] || "bg-gray-800 text-gray-300"}`}>
                    {job.status}
                  </span>
                </td>
                <td className="px-4 py-3 text-sm font-mono truncate max-w-xs" title={job.sitemap_url}>
                  {job.sitemap_url}
                </td>
                <td className="px-4 py-3 text-sm">
                  <div className="flex items-center gap-2">
                    <div className="w-24 bg-gray-800 rounded-full h-2">
                      <div
                        className="bg-orange-500 h-2 rounded-full transition-all"
                        style={{ width: `${job.total_urls > 0 ? (job.processed_urls / job.total_urls) * 100 : 0}%` }}
                      />
                    </div>
                    <span className="text-gray-400 text-xs">
                      {job.processed_urls}/{job.total_urls}
                    </span>
                  </div>
                </td>
                <td className="px-4 py-3">
                  <div className="flex gap-1 flex-wrap">
                    {job.targets.map((t) => (
                      <span key={t} className="px-1.5 py-0.5 bg-gray-800 rounded text-xs text-gray-400">
                        {t}
                      </span>
                    ))}
                  </div>
                </td>
                <td className="px-4 py-3 text-sm text-gray-400">
                  {new Date(job.created_at).toLocaleString("de-DE")}
                </td>
                <td className="px-4 py-3">
                  <button
                    onClick={() => onViewJob(job.id)}
                    className="text-orange-500 hover:text-orange-400 text-sm"
                  >
                    Details
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Mobile card layout — visible only on small screens */}
      <div className="md:hidden space-y-3">
        {jobs.map((job) => (
          <div key={job.id} className="bg-gray-900 border border-gray-800 rounded-lg p-4 space-y-3">
            <div className="flex items-center justify-between">
              <span className={`px-2 py-1 rounded text-xs font-medium ${statusColors[job.status] || "bg-gray-800 text-gray-300"}`}>
                {job.status}
              </span>
              <span className="text-xs text-gray-500">
                {new Date(job.created_at).toLocaleString("de-DE")}
              </span>
            </div>
            <div>
              <span className="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Sitemap</span>
              <span className="text-sm font-mono text-gray-300 break-all">{job.sitemap_url}</span>
            </div>
            <div>
              <span className="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Fortschritt</span>
              <div className="flex items-center gap-2">
                <div className="flex-1 bg-gray-800 rounded-full h-2">
                  <div
                    className="bg-orange-500 h-2 rounded-full transition-all"
                    style={{ width: `${job.total_urls > 0 ? (job.processed_urls / job.total_urls) * 100 : 0}%` }}
                  />
                </div>
                <span className="text-gray-400 text-xs whitespace-nowrap">
                  {job.processed_urls}/{job.total_urls}
                </span>
              </div>
            </div>
            <div>
              <span className="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Targets</span>
              <div className="flex gap-1 flex-wrap">
                {job.targets.map((t) => (
                  <span key={t} className="px-1.5 py-0.5 bg-gray-800 rounded text-xs text-gray-400">
                    {t}
                  </span>
                ))}
              </div>
            </div>
            <div className="pt-2 border-t border-gray-800">
              <button
                onClick={() => onViewJob(job.id)}
                className="text-orange-500 hover:text-orange-400 text-sm w-full text-center py-1"
              >
                Details anzeigen
              </button>
            </div>
          </div>
        ))}
      </div>
    </>
  );
}
