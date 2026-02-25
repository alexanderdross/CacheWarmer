"use client";

import { useState, useEffect, useCallback } from "react";
import StatusCard from "@/components/StatusCard";
import JobTable from "@/components/JobTable";
import WarmForm from "@/components/WarmForm";
import JobDetail from "@/components/JobDetail";

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

interface StatusData {
  status: string;
  uptime: number;
  jobs: Record<string, number>;
  totalUrlsProcessed: number;
}

export default function Dashboard() {
  const [statusData, setStatusData] = useState<StatusData | null>(null);
  const [jobs, setJobs] = useState<Job[]>([]);
  const [selectedJobId, setSelectedJobId] = useState<string | null>(null);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [jobDetail, setJobDetail] = useState<any>(null);

  const fetchStatus = useCallback(async () => {
    try {
      const res = await fetch("/api/status");
      if (res.ok) setStatusData(await res.json());
    } catch { /* ignore */ }
  }, []);

  const fetchJobs = useCallback(async () => {
    try {
      const res = await fetch("/api/jobs");
      if (res.ok) {
        const data = await res.json();
        setJobs(data.jobs);
      }
    } catch { /* ignore */ }
  }, []);

  const fetchJobDetail = useCallback(async (jobId: string) => {
    try {
      const res = await fetch(`/api/jobs/${jobId}`);
      if (res.ok) setJobDetail(await res.json());
    } catch { /* ignore */ }
  }, []);

  useEffect(() => {
    fetchStatus();
    fetchJobs();
    const interval = setInterval(() => {
      fetchStatus();
      fetchJobs();
      if (selectedJobId) fetchJobDetail(selectedJobId);
    }, 5000);
    return () => clearInterval(interval);
  }, [fetchStatus, fetchJobs, fetchJobDetail, selectedJobId]);

  useEffect(() => {
    if (selectedJobId) fetchJobDetail(selectedJobId);
  }, [selectedJobId, fetchJobDetail]);

  const handleSubmitWarm = async (sitemapUrl: string, targets: string[]) => {
    const res = await fetch("/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sitemapUrl, targets }),
    });

    if (res.ok) {
      await fetchJobs();
      await fetchStatus();
    }
  };

  if (selectedJobId && jobDetail) {
    return (
      <JobDetail
        job={jobDetail}
        onBack={() => {
          setSelectedJobId(null);
          setJobDetail(null);
        }}
      />
    );
  }

  return (
    <div className="space-y-8">
      {/* Status Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatusCard label="Status" value={statusData?.status || "..."} color="text-green-400" />
        <StatusCard label="Laufende Jobs" value={statusData?.jobs?.running || 0} color="text-blue-400" />
        <StatusCard label="Abgeschlossen" value={statusData?.jobs?.completed || 0} color="text-green-400" />
        <StatusCard label="URLs verarbeitet" value={statusData?.totalUrlsProcessed || 0} color="text-orange-400" />
      </div>

      {/* Warm Form */}
      <div>
        <h2 className="text-lg font-semibold mb-3">Neuen Warming-Job starten</h2>
        <WarmForm onSubmit={handleSubmitWarm} />
      </div>

      {/* Jobs Table */}
      <div>
        <h2 className="text-lg font-semibold mb-3">Jobs</h2>
        <JobTable jobs={jobs} onViewJob={setSelectedJobId} />
      </div>
    </div>
  );
}
