import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "CacheWarmer Dashboard",
  description: "Self-hosted cache warming microservice",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="de">
      <body className="bg-gray-950 text-gray-100 min-h-screen">
        <nav className="border-b border-gray-800 bg-gray-900">
          <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 className="text-xl font-bold tracking-tight">
              <span className="text-orange-500">Cache</span>Warmer
            </h1>
            <span className="text-xs text-gray-500">v1.0.0</span>
          </div>
        </nav>
        <main className="max-w-7xl mx-auto px-4 py-8">{children}</main>
      </body>
    </html>
  );
}
