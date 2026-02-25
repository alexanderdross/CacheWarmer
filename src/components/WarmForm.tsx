"use client";

import { useState } from "react";

const ALL_TARGETS = [
  { id: "cdn", label: "CDN Edge Cache" },
  { id: "facebook", label: "Facebook" },
  { id: "linkedin", label: "LinkedIn" },
  { id: "twitter", label: "Twitter/X" },
  { id: "google", label: "Google" },
  { id: "bing", label: "Bing" },
  { id: "indexnow", label: "IndexNow" },
];

interface WarmFormProps {
  onSubmit: (sitemapUrl: string, targets: string[]) => Promise<void>;
}

export default function WarmForm({ onSubmit }: WarmFormProps) {
  const [sitemapUrl, setSitemapUrl] = useState("");
  const [targets, setTargets] = useState<string[]>(ALL_TARGETS.map((t) => t.id));
  const [loading, setLoading] = useState(false);

  const toggleTarget = (id: string) => {
    setTargets((prev) =>
      prev.includes(id) ? prev.filter((t) => t !== id) : [...prev, id]
    );
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!sitemapUrl || targets.length === 0) return;
    setLoading(true);
    try {
      await onSubmit(sitemapUrl, targets);
      setSitemapUrl("");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="bg-gray-900 border border-gray-800 rounded-lg p-6 space-y-4">
      <div>
        <label htmlFor="sitemapUrl" className="block text-sm font-medium text-gray-300 mb-1">
          Sitemap URL
        </label>
        <input
          id="sitemapUrl"
          type="url"
          value={sitemapUrl}
          onChange={(e) => setSitemapUrl(e.target.value)}
          placeholder="https://www.example.com/sitemap.xml"
          required
          className="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-300 mb-2">Warming Targets</label>
        <div className="flex flex-wrap gap-2">
          {ALL_TARGETS.map((target) => (
            <button
              key={target.id}
              type="button"
              onClick={() => toggleTarget(target.id)}
              className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                targets.includes(target.id)
                  ? "bg-orange-600 text-white"
                  : "bg-gray-800 text-gray-400 hover:bg-gray-700"
              }`}
            >
              {target.label}
            </button>
          ))}
        </div>
      </div>

      <button
        type="submit"
        disabled={loading || !sitemapUrl || targets.length === 0}
        className="w-full bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-md transition-colors"
      >
        {loading ? "Wird gestartet..." : "Cache Warming starten"}
      </button>
    </form>
  );
}
