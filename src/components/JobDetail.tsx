"use client";

interface Stat {
  target: string;
  status: string;
  count: number;
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

export default function JobDetail({ job, onBack }: JobDetailProps) {
  const statsByTarget: Record<string, Record<string, number>> = {};
  for (const stat of job.stats) {
    if (!statsByTarget[stat.target]) statsByTarget[stat.target] = {};
    statsByTarget[stat.target][stat.status] = stat.count;
  }

  return (
    <div className="space-y-6">
      <button onClick={onBack} className="text-orange-500 hover:text-orange-400 text-sm">
        &larr; Zurueck zur Uebersicht
      </button>

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
    </div>
  );
}
