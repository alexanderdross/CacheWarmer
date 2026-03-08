import SitemapManager from "@/components/SitemapManager";

export default function SitemapsPage() {
  return (
    <div className="space-y-6">
      <h2 className="text-xl font-bold">Sitemap-Verwaltung</h2>
      <p className="text-sm text-gray-400">
        Registriere Sitemaps fuer wiederkehrendes Cache-Warming. Optional mit Cron-Ausdruck fuer automatische Planung.
      </p>
      <SitemapManager />
    </div>
  );
}
