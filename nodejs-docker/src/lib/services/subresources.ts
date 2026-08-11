/**
 * Subresource extraction for the fetch-based CDN warmer.
 *
 * A headless browser warms a page's stylesheets, scripts and images as a side
 * effect of rendering it. A plain fetch() does not, so those requests have to
 * be issued deliberately — otherwise dropping the browser would quietly stop
 * warming everything except the HTML document.
 *
 * This is a scanner, not an HTML parser. It reads start tags and their
 * attributes and ignores everything else, which is all that is needed to find
 * href/src/srcset. It respects quoting, skips comments, and skips the raw-text
 * content of <script>, <style> and <textarea> so a URL inside a JavaScript
 * string is not mistaken for a real subresource.
 */

/** Tags worth looking at. Anything else cannot carry a subresource URL. */
const INTERESTING = new Set(["base", "link", "script", "img", "source", "video"]);

/** Elements whose content is raw text, not markup. */
const RAW_TEXT = new Set(["script", "style", "textarea"]);

/** rel values that make a <link> a real request the browser would issue. */
const FETCHING_REL = new Set([
  "stylesheet",
  "preload",
  "modulepreload",
  "prefetch",
  "icon",
  "shortcut icon",
  "apple-touch-icon",
  "manifest",
]);

const UNFETCHABLE_SCHEME = /^(data|blob|javascript|mailto|tel|about):/i;

export interface ScannedTag {
  name: string;
  attrs: Record<string, string>;
}

/**
 * Yield every start tag of interest, in document order.
 *
 * Exported for testing: the quoting and raw-text rules are the part most
 * likely to be wrong, and they are far easier to check directly.
 */
export function* scanTags(html: string): Generator<ScannedTag> {
  let i = 0;

  while (i < html.length) {
    const lt = html.indexOf("<", i);
    if (lt === -1) return;

    if (html.startsWith("<!--", lt)) {
      const end = html.indexOf("-->", lt + 4);
      i = end === -1 ? html.length : end + 3;
      continue;
    }

    // <!doctype …>, <?xml …>, </closing>
    const afterLt = html[lt + 1];
    if (afterLt === "!" || afterLt === "?" || afterLt === "/") {
      const end = html.indexOf(">", lt + 1);
      i = end === -1 ? html.length : end + 1;
      continue;
    }

    const nameMatch = /^[a-zA-Z][a-zA-Z0-9-]*/.exec(html.slice(lt + 1, lt + 32));
    if (!nameMatch) {
      i = lt + 1;
      continue;
    }

    const name = nameMatch[0].toLowerCase();
    const { attrs, end } = readAttributes(html, lt + 1 + name.length);

    if (INTERESTING.has(name)) yield { name, attrs };

    i = end;

    // Jump past raw-text content so its body is never scanned as markup.
    if (RAW_TEXT.has(name)) {
      const close = html.toLowerCase().indexOf(`</${name}`, i);
      if (close !== -1) i = close;
    }
  }
}

/** Read attributes up to the closing '>', honouring quotes. */
function readAttributes(html: string, from: number): { attrs: Record<string, string>; end: number } {
  const attrs: Record<string, string> = {};
  let i = from;

  while (i < html.length) {
    while (i < html.length && /\s/.test(html[i])) i++;
    if (i >= html.length) break;

    if (html[i] === ">") return { attrs, end: i + 1 };
    if (html[i] === "/" && html[i + 1] === ">") return { attrs, end: i + 2 };

    const nameStart = i;
    while (i < html.length && !/[\s=>/]/.test(html[i])) i++;
    const attrName = html.slice(nameStart, i).toLowerCase();
    if (!attrName) {
      i++;
      continue;
    }

    while (i < html.length && /\s/.test(html[i])) i++;

    if (html[i] !== "=") {
      attrs[attrName] = "";
      continue;
    }

    i++;
    while (i < html.length && /\s/.test(html[i])) i++;

    const quote = html[i];
    if (quote === '"' || quote === "'") {
      const close = html.indexOf(quote, i + 1);
      if (close === -1) return { attrs, end: html.length };
      attrs[attrName] = html.slice(i + 1, close);
      i = close + 1;
    } else {
      const valueStart = i;
      while (i < html.length && !/[\s>]/.test(html[i])) i++;
      attrs[attrName] = html.slice(valueStart, i);
    }
  }

  return { attrs, end: i };
}

/** Pull the URLs out of a srcset, dropping the descriptors. */
function fromSrcset(value: string): string[] {
  return value
    .split(",")
    .map((candidate) => candidate.trim().split(/\s+/)[0])
    .filter(Boolean);
}

export interface ExtractOptions {
  /**
   * Hosts whose assets may be warmed, beyond the document's own. Third-party
   * scripts live in someone else's CDN, where warming achieves nothing and
   * costs a request; a separate asset host of your own is the case this exists
   * for.
   */
  assetHosts?: string[];
  /** Upper bound per document. */
  max?: number;
}

/**
 * Extract the subresource URLs a browser would have fetched for this document.
 *
 * Returns absolute URLs, deduplicated, in document order.
 */
export function extractSubresources(
  html: string,
  documentUrl: string,
  options: ExtractOptions = {}
): string[] {
  const { assetHosts = [], max = 20 } = options;

  let base: URL;
  try {
    base = new URL(documentUrl);
  } catch {
    return [];
  }

  const allowedHosts = new Set([base.hostname.toLowerCase(), ...assetHosts.map((h) => h.toLowerCase())]);
  const found: string[] = [];
  const seen = new Set<string>();

  const add = (raw: string | undefined) => {
    if (!raw || found.length >= max) return;
    const value = raw.trim();
    if (!value || value.startsWith("#") || UNFETCHABLE_SCHEME.test(value)) return;

    let resolved: URL;
    try {
      resolved = new URL(value, base);
    } catch {
      return;
    }
    if (resolved.protocol !== "http:" && resolved.protocol !== "https:") return;
    if (!allowedHosts.has(resolved.hostname.toLowerCase())) return;

    const href = resolved.href;
    if (seen.has(href)) return;
    seen.add(href);
    found.push(href);
  };

  for (const tag of scanTags(html)) {
    switch (tag.name) {
      case "base":
        // A <base href> rebases every relative URL after it, including ones
        // already collected in principle — but browsers apply it document-wide
        // and it appears in <head>, so rebasing from here on is correct enough.
        if (tag.attrs.href) {
          try {
            base = new URL(tag.attrs.href, base);
          } catch {
            /* keep the document URL */
          }
        }
        break;

      case "link": {
        const rel = (tag.attrs.rel || "").trim().toLowerCase();
        if (FETCHING_REL.has(rel)) add(tag.attrs.href);
        break;
      }

      case "script":
        add(tag.attrs.src);
        break;

      case "img":
        add(tag.attrs.src);
        if (tag.attrs.srcset) fromSrcset(tag.attrs.srcset).forEach(add);
        break;

      case "source":
        add(tag.attrs.src);
        if (tag.attrs.srcset) fromSrcset(tag.attrs.srcset).forEach(add);
        break;

      case "video":
        add(tag.attrs.poster);
        break;
    }

    if (found.length >= max) break;
  }

  return found;
}
