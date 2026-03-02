"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const links = [
  { href: "/", label: "Dashboard" },
  { href: "/sitemaps", label: "Sitemaps" },
  { href: "/settings", label: "Einstellungen" },
];

export default function NavBar() {
  const pathname = usePathname();

  return (
    <nav className="border-b border-gray-800 bg-gray-900">
      <div className="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div className="flex items-center gap-4 sm:gap-8 w-full sm:w-auto justify-between sm:justify-start">
          <Link href="/" className="text-xl font-bold tracking-tight">
            <span className="text-orange-500">Cache</span>Warmer
          </Link>
          <span className="text-xs text-gray-500 sm:hidden">v1.0.0</span>
        </div>
        <div className="flex gap-1 w-full sm:w-auto justify-center">
          {links.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className={`flex-1 sm:flex-none text-center px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                pathname === link.href
                  ? "bg-gray-800 text-white"
                  : "text-gray-400 hover:text-white hover:bg-gray-800/50"
              }`}
            >
              {link.label}
            </Link>
          ))}
        </div>
        <span className="text-xs text-gray-500 hidden sm:block">v1.0.0</span>
      </div>
    </nav>
  );
}
