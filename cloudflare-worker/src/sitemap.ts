/**
 * Sitemap parsing.
 *
 * Uses HTMLRewriter rather than an XML library: it is built into the runtime,
 * streams, and costs almost no CPU time — which matters because Workers Free
 * allows only 10 ms of CPU per invocation.
 */

export interface SitemapEntry {
  loc: string;
  lastmod?: string;
  priority?: number;
}

export interface ParsedSitemap {
  /** Page URLs found in a <urlset>. */
  entries: SitemapEntry[];
  /** Nested sitemap URLs found in a <sitemapindex>. */
  children: string[];
}

class Collector {
  entries: SitemapEntry[] = [];
  children: string[] = [];
  isIndex = false;

  private current: Partial<SitemapEntry> = {};
  private field: "loc" | "lastmod" | "priority" | null = null;
  private buffer = "";

  openIndex() {
    this.isIndex = true;
  }

  openField(field: "loc" | "lastmod" | "priority") {
    this.field = field;
    this.buffer = "";
  }

  /** Text arrives in chunks; accumulate until the element closes. */
  appendText(text: string, last: boolean) {
    if (!this.field) return;
    this.buffer += text;
    if (!last) return;

    const value = this.buffer.trim();
    if (this.field === "loc") this.current.loc = value;
    else if (this.field === "lastmod") this.current.lastmod = value;
    else if (this.field === "priority") {
      const n = Number.parseFloat(value);
      if (Number.isFinite(n)) this.current.priority = n;
    }
    this.field = null;
    this.buffer = "";
  }

  closeEntry() {
    const loc = this.current.loc;
    if (loc) {
      if (this.isIndex) this.children.push(loc);
      else this.entries.push({ ...this.current, loc } as SitemapEntry);
    }
    this.current = {};
  }
}

export async function parseSitemap(xml: Response): Promise<ParsedSitemap> {
  const collector = new Collector();

  const rewriter = new HTMLRewriter()
    .on("sitemapindex", { element: () => collector.openIndex() })
    .on("loc", {
      element: () => collector.openField("loc"),
      text: (t) => collector.appendText(t.text, t.lastInTextNode),
    })
    .on("lastmod", {
      element: () => collector.openField("lastmod"),
      text: (t) => collector.appendText(t.text, t.lastInTextNode),
    })
    .on("priority", {
      element: () => collector.openField("priority"),
      text: (t) => collector.appendText(t.text, t.lastInTextNode),
    })
    .on("url", { element: (el) => el.onEndTag(() => collector.closeEntry()) })
    .on("sitemap", { element: (el) => el.onEndTag(() => collector.closeEntry()) });

  await rewriter.transform(xml).arrayBuffer();

  return { entries: collector.entries, children: collector.children };
}

export interface FetchSitemapOptions {
  userAgent: string;
  timeoutMs: number;
  /** Guard against a sitemap index that fans out further than expected. */
  maxDepth: number;
  /**
   * Safety ceiling on how many entries to collect, NOT the per-run limit.
   *
   * Collect generously and let the caller sort by priority before slicing —
   * truncating here would drop high-priority pages simply because they appear
   * late in the document.
   */
  maxUrls: number;
}

/**
 * Fetch a sitemap and follow nested indexes breadth-first.
 *
 * Every fetch is a subrequest, so the caller must budget for the sitemaps
 * themselves on top of the URLs they yield.
 */
export async function fetchSitemapUrls(
  sitemapUrl: string,
  options: FetchSitemapOptions,
): Promise<{ entries: SitemapEntry[]; sitemapsFetched: number; truncated: boolean }> {
  const seen = new Set<string>();
  let queue = [sitemapUrl];
  const entries: SitemapEntry[] = [];
  let sitemapsFetched = 0;
  let truncated = false;

  for (let depth = 0; depth <= options.maxDepth && queue.length > 0; depth++) {
    const next: string[] = [];

    for (const url of queue) {
      if (seen.has(url)) continue;
      seen.add(url);

      const response = await fetch(url, {
        headers: { "user-agent": options.userAgent },
        signal: AbortSignal.timeout(options.timeoutMs),
      });
      sitemapsFetched++;

      if (!response.ok) {
        await response.body?.cancel();
        throw new Error(`Sitemap ${url} returned HTTP ${response.status}`);
      }

      const parsed = await parseSitemap(response);
      next.push(...parsed.children);

      for (const entry of parsed.entries) {
        if (entries.length >= options.maxUrls) {
          truncated = true;
          break;
        }
        entries.push(entry);
      }
      if (truncated) break;
    }

    if (truncated) break;
    queue = next;
  }

  return { entries, sitemapsFetched, truncated };
}

/**
 * Highest sitemap priority first, so the pages that matter are warm even if a
 * run is cut short.
 *
 * The Node module has this same feature but sorts a copy that is never used —
 * sorting here returns a new array so the result cannot be discarded by
 * accident.
 */
export function sortByPriority(entries: readonly SitemapEntry[]): SitemapEntry[] {
  return [...entries].sort((a, b) => (b.priority ?? 0.5) - (a.priority ?? 0.5));
}

/** Only URLs whose lastmod moved since the previous run. */
export function changedSince(
  entries: readonly SitemapEntry[],
  previous: ReadonlyMap<string, string>,
): SitemapEntry[] {
  return entries.filter((entry) => {
    if (!entry.lastmod) return true; // No lastmod: assume it may have changed.
    return previous.get(entry.loc) !== entry.lastmod;
  });
}

/** Split into chunks that fit one invocation's subrequest budget. */
export function chunk<T>(items: readonly T[], size: number): T[][] {
  if (size < 1) throw new Error("chunk size must be at least 1");
  const out: T[][] = [];
  for (let i = 0; i < items.length; i += size) out.push(items.slice(i, i + size));
  return out;
}
