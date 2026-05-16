export type PageType = "aircraft" | "job" | "event" | "parts-listing" | "parts-wanted";

const LOCALE_PREFIX = /^\/(?:[a-z]{2}\/)?/i;

const PATTERNS: Array<{ re: RegExp; type: PageType }> = [
  { re: /^\/(?:[a-z]{2}\/)?aircraft\/[^/]+\/?$/i, type: "aircraft" },
  { re: /^\/(?:[a-z]{2}\/)?jobs\/[^/]+\/?$/i, type: "job" },
  { re: /^\/(?:[a-z]{2}\/)?events\/[^/]+\/?$/i, type: "event" },
  { re: /^\/(?:[a-z]{2}\/)?parts\/listing\/[^/]+\/?$/i, type: "parts-listing" },
  { re: /^\/(?:[a-z]{2}\/)?parts\/wanted\/[^/]+\/?$/i, type: "parts-wanted" },
];

function parsePathname(url: string): string | null {
  try {
    const u = new URL(url);
    return u.pathname;
  } catch {
    if (url.startsWith("/")) return url;
    return null;
  }
}

export function isInSchemaScope(url: string): boolean {
  const path = parsePathname(url);
  if (!path) return false;
  return PATTERNS.some((p) => p.re.test(path));
}

export function classifyPageType(url: string): PageType | null {
  const path = parsePathname(url);
  if (!path) return null;
  for (const p of PATTERNS) {
    if (p.re.test(path)) return p.type;
  }
  return null;
}

export function canonicalizeForFingerprint(url: string): string {
  const path = parsePathname(url);
  if (!path) return url;
  const stripped = path.replace(LOCALE_PREFIX, "/").replace(/\/+$/, "");
  return stripped || "/";
}
