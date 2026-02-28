"use client";

import { useState, useEffect, useCallback } from "react";

interface Sitemap {
  id: string;
  url: string;
  domain: string;
  cron_expression: string | null;
  created_at: string;
  last_warmed_at: string | null;
}

export default function SitemapManager() {
  const [sitemaps, setSitemaps] = useState<Sitemap[]>([]);
  const [newUrl, setNewUrl] = useState("");
  const [cronFrequency, setCronFrequency] = useState("none");
  const [cronHour, setCronHour] = useState(3);
  const [adding, setAdding] = useState(false);

  // Bulk import state
  const [showBulk, setShowBulk] = useState(false);
  const [bulkUrls, setBulkUrls] = useState("");
  const [bulkImporting, setBulkImporting] = useState(false);
  const [bulkResult, setBulkResult] = useState<{ added: number; errors: string[] } | null>(null);

  // Auto-detect state
  const [detectDomain, setDetectDomain] = useState("");
  const [detecting, setDetecting] = useState(false);
  const [detectedSitemaps, setDetectedSitemaps] = useState<string[]>([]);

  function buildCronExpression(frequency: string, hour: number): string | undefined {
    switch (frequency) {
      case "hourly":
        return "0 * * * *";
      case "every_6_hours": {
        const hours = [hour, (hour + 6) % 24, (hour + 12) % 24, (hour + 18) % 24].sort((a, b) => a - b);
        return `0 ${hours.join(",")} * * *`;
      }
      case "every_12_hours": {
        const hours = [hour, (hour + 12) % 24].sort((a, b) => a - b);
        return `0 ${hours.join(",")} * * *`;
      }
      case "daily":
        return `0 ${hour} * * *`;
      default:
        return undefined;
    }
  }

  function formatCronLabel(cron: string | null): string {
    if (!cron) return "-";
    if (cron === "0 * * * *") return "Stuendlich";
    const match = cron.match(/^0 (\S+) \* \* \*$/);
    if (match) {
      const hours = match[1].split(",").map(Number);
      if (hours.length === 1) return `Taeglich um ${hours[0].toString().padStart(2, "0")}:00`;
      if (hours.length === 2) return `Alle 12 Std. (ab ${Math.min(...hours).toString().padStart(2, "0")}:00)`;
      if (hours.length === 4) return `Alle 6 Std. (ab ${Math.min(...hours).toString().padStart(2, "0")}:00)`;
    }
    return cron;
  }

  const fetchSitemaps = useCallback(async () => {
    try {
      const res = await fetch("/api/sitemaps");
      if (res.ok) {
        const data = await res.json();
        setSitemaps(data.sitemaps);
      }
    } catch { /* ignore */ }
  }, []);

  useEffect(() => {
    fetchSitemaps();
  }, [fetchSitemaps]);

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newUrl) return;
    setAdding(true);

    try {
      const res = await fetch("/api/sitemaps", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          url: newUrl,
          cronExpression: buildCronExpression(cronFrequency, cronHour),
        }),
      });

      if (res.ok) {
        setNewUrl("");
        setCronFrequency("none");
        setCronHour(3);
        await fetchSitemaps();
      }
    } finally {
      setAdding(false);
    }
  };

  const handleDelete = async (id: string) => {
    const res = await fetch(`/api/sitemaps/${id}`, { method: "DELETE" });
    if (res.ok) await fetchSitemaps();
  };

  const handleWarm = async (sitemapUrl: string) => {
    await fetch("/api/warm", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        sitemapUrl,
        targets: ["cdn", "facebook", "linkedin", "twitter", "google", "bing", "indexnow"],
      }),
    });
  };

  const handleBulkImport = async () => {
    if (!bulkUrls.trim()) return;
    setBulkImporting(true);
    setBulkResult(null);

    try {
      const res = await fetch("/api/sitemaps/bulk", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ urls: bulkUrls }),
      });

      if (res.ok) {
        const data = await res.json();
        setBulkResult({ added: data.added.length, errors: data.errors });
        setBulkUrls("");
        await fetchSitemaps();
      }
    } finally {
      setBulkImporting(false);
    }
  };

  const handleDetect = async () => {
    if (!detectDomain.trim()) return;
    setDetecting(true);
    setDetectedSitemaps([]);

    try {
      const res = await fetch("/api/sitemaps/detect", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ domain: detectDomain }),
      });

      if (res.ok) {
        const data = await res.json();
        setDetectedSitemaps(data.sitemaps || []);
      }
    } finally {
      setDetecting(false);
    }
  };

  const handleAddDetected = async (url: string) => {
    const res = await fetch("/api/sitemaps", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ url }),
    });
    if (res.ok) {
      setDetectedSitemaps((prev) => prev.filter((s) => s !== url));
      await fetchSitemaps();
    }
  };

  return (
    <div className="space-y-4">
      {/* Add Form */}
      <form onSubmit={handleAdd} className="bg-gray-900 border border-gray-800 rounded-lg p-4">
        <div className="flex gap-3 items-end">
          <div className="flex-1">
            <label className="block text-sm font-medium text-gray-300 mb-1">Sitemap URL</label>
            <input
              type="url"
              value={newUrl}
              onChange={(e) => setNewUrl(e.target.value)}
              placeholder="https://www.example.com/sitemap.xml"
              required
              className="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>
          <div className="w-48">
            <label className="block text-sm font-medium text-gray-300 mb-1">
              Intervall <span className="text-gray-500">(optional)</span>
            </label>
            <select
              value={cronFrequency}
              onChange={(e) => setCronFrequency(e.target.value)}
              className="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
            >
              <option value="none">Kein Zeitplan</option>
              <option value="hourly">Stuendlich</option>
              <option value="every_6_hours">Alle 6 Stunden</option>
              <option value="every_12_hours">Alle 12 Stunden</option>
              <option value="daily">Taeglich</option>
            </select>
          </div>
          {cronFrequency !== "none" && cronFrequency !== "hourly" && (
            <div className="w-32">
              <label className="block text-sm font-medium text-gray-300 mb-1">
                Startzeit
              </label>
              <select
                value={cronHour}
                onChange={(e) => setCronHour(Number(e.target.value))}
                className="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
              >
                {Array.from({ length: 24 }, (_, i) => (
                  <option key={i} value={i}>
                    {i.toString().padStart(2, "0")}:00
                  </option>
                ))}
              </select>
            </div>
          )}
          <button
            type="submit"
            disabled={adding || !newUrl}
            className="bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md transition-colors whitespace-nowrap"
          >
            {adding ? "..." : "Hinzufuegen"}
          </button>
        </div>
      </form>

      {/* Action Buttons */}
      <div className="flex gap-3">
        <button
          onClick={() => { setShowBulk(!showBulk); setDetectedSitemaps([]); }}
          className="bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium py-2 px-4 rounded-md transition-colors border border-gray-700"
        >
          Bulk Import
        </button>
        <div className="flex gap-2 items-center flex-1">
          <input
            type="text"
            value={detectDomain}
            onChange={(e) => setDetectDomain(e.target.value)}
            placeholder="example.com"
            className="bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 w-64"
          />
          <button
            onClick={handleDetect}
            disabled={detecting || !detectDomain.trim()}
            className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors whitespace-nowrap"
          >
            {detecting ? "Suche..." : "Sitemaps erkennen"}
          </button>
        </div>
      </div>

      {/* Detected Sitemaps */}
      {detectedSitemaps.length > 0 && (
        <div className="bg-gray-900 border border-blue-800 rounded-lg p-4">
          <h3 className="text-sm font-medium text-blue-400 mb-2">Erkannte Sitemaps</h3>
          <div className="space-y-2">
            {detectedSitemaps.map((url) => (
              <div key={url} className="flex items-center justify-between gap-2">
                <span className="text-sm font-mono text-gray-300 truncate">{url}</span>
                <button
                  onClick={() => handleAddDetected(url)}
                  className="text-green-500 hover:text-green-400 text-sm whitespace-nowrap"
                >
                  Hinzufuegen
                </button>
              </div>
            ))}
          </div>
        </div>
      )}
      {detectedSitemaps.length === 0 && detecting === false && detectDomain && (
        <div className="bg-gray-900 border border-gray-800 rounded-lg p-4 text-sm text-gray-500">
          {/* Only show after a search has been performed */}
        </div>
      )}

      {/* Bulk Import */}
      {showBulk && (
        <div className="bg-gray-900 border border-gray-800 rounded-lg p-4 space-y-3">
          <h3 className="text-sm font-medium text-gray-300">Bulk Import (eine URL pro Zeile)</h3>
          <textarea
            value={bulkUrls}
            onChange={(e) => setBulkUrls(e.target.value)}
            placeholder={"https://www.example.com/sitemap.xml\nhttps://www.example.com/sitemap-news.xml\nhttps://www.example.com/sitemap-products.xml"}
            rows={6}
            className="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono"
          />
          <div className="flex items-center gap-3">
            <button
              onClick={handleBulkImport}
              disabled={bulkImporting || !bulkUrls.trim()}
              className="bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md transition-colors text-sm"
            >
              {bulkImporting ? "Importiere..." : "Importieren"}
            </button>
            {bulkResult && (
              <span className="text-sm text-gray-400">
                {bulkResult.added} hinzugefuegt
                {bulkResult.errors.length > 0 && (
                  <span className="text-red-400"> | {bulkResult.errors.length} Fehler</span>
                )}
              </span>
            )}
          </div>
        </div>
      )}

      {/* Sitemap List */}
      {sitemaps.length === 0 ? (
        <div className="bg-gray-900 border border-gray-800 rounded-lg p-8 text-center text-gray-500">
          Noch keine Sitemaps registriert.
        </div>
      ) : (
        <div className="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-800 text-left text-sm text-gray-400">
                <th className="px-4 py-3">Domain</th>
                <th className="px-4 py-3">URL</th>
                <th className="px-4 py-3">Cron</th>
                <th className="px-4 py-3">Letztes Warming</th>
                <th className="px-4 py-3">Aktionen</th>
              </tr>
            </thead>
            <tbody>
              {sitemaps.map((sm) => (
                <tr key={sm.id} className="border-b border-gray-800/50 hover:bg-gray-800/30">
                  <td className="px-4 py-3 text-sm font-medium">{sm.domain}</td>
                  <td className="px-4 py-3 text-sm font-mono text-gray-400 truncate max-w-xs" title={sm.url}>
                    {sm.url}
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-400">
                    {formatCronLabel(sm.cron_expression)}
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-400">
                    {sm.last_warmed_at ? new Date(sm.last_warmed_at).toLocaleString("de-DE") : "-"}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex gap-2">
                      <button
                        onClick={() => handleWarm(sm.url)}
                        className="text-orange-500 hover:text-orange-400 text-sm"
                      >
                        Jetzt aufwaermen
                      </button>
                      <button
                        onClick={() => handleDelete(sm.id)}
                        className="text-red-500 hover:text-red-400 text-sm"
                      >
                        Loeschen
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
