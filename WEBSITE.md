# CacheWarmer Website — Konzept, Informationsarchitektur & Inhalte

**Domain:** `https://cachewarmer.drossmedia.de/`
**Referenz-Design:** `https://pdfviewer.drossmedia.de/`
**Technologie:** Next.js / Astro (SSG) auf gleichem Hosting-Stack wie pdfviewer

---

## Inhaltsverzeichnis

1. [Informationsarchitektur (Sitemap)](#1-informationsarchitektur)
2. [Design-System & Branding](#2-design-system--branding)
3. [Globale Elemente (Header, Footer, SEO)](#3-globale-elemente)
4. [Startseite (Homepage)](#4-startseite-homepage)
5. [/wordpress/ — WordPress Plugin](#5-wordpress--wordpress-plugin)
6. [/drupal/ — Drupal Modul](#6-drupal--drupal-modul)
7. [/self-hosted/ — Node.js Microservice](#7-self-hosted--nodejs-microservice)
8. [/pro/ — Pricing & Pro Features](#8-pro--pricing--pro-features)
9. [/enterprise/ — Enterprise](#9-enterprise--enterprise)
10. [/features/ — Alle Features](#10-features--alle-features)
11. [/documentation/ — Dokumentation](#11-documentation--dokumentation)
12. [/changelog/ — Changelog](#12-changelog--changelog)
13. [/contact/ — Kontakt](#13-contact--kontakt)
14. [/imprint/ — Impressum](#14-imprint--impressum)
15. [/privacy/ — Datenschutz](#15-privacy--datenschutz)
16. [Schema.org / Structured Data](#16-schemaorg--structured-data)

---

## 1. Informationsarchitektur

```
cachewarmer.drossmedia.de/
│
├── /                          ← Startseite (Landingpage)
│
├── /wordpress/                ← WordPress Plugin-Seite
├── /drupal/                   ← Drupal Modul-Seite
├── /self-hosted/              ← Node.js Microservice-Seite
│
├── /pro/                      ← Pricing (Free / Premium / Enterprise)
├── /enterprise/               ← Enterprise-Details & Kontakt
│
├── /features/                 ← Alle Features im Detail
├── /documentation/            ← Technische Doku
├── /changelog/                ← Versionshistorie
│
├── /contact/                  ← Kontakt & Support
├── /imprint/                  ← Impressum
└── /privacy/                  ← Datenschutzerklärung
```

### Navigation (Header)

```
[ Logo: CacheWarmer ]   Features   Platforms ▼   Docs   Changelog   Pro   [Get Started →]

                         Dropdown "Platforms":
                         ├── WordPress Plugin
                         ├── Drupal Module
                         └── Self-Hosted (Node.js)
```

### Breadcrumb-Beispiel

```
Home → WordPress → Installation
Home → Pro → Feature Comparison
```

---

## 2. Design-System & Branding

### Farbpalette

| Rolle | Farbe | Hex | Verwendung |
|-------|-------|-----|------------|
| Primary | Dunkelblau | `#0f2b46` | Hintergründe, Header, Footer |
| Accent | Orange | `#e86b2e` | CTAs, Highlights, Badges |
| Secondary | Hellblau | `#2b7de9` | Links, sekundäre Aktionen |
| Success | Grün | `#00a854` | Erfolg, aktive States |
| Danger | Rot | `#d63638` | Fehler, Delete-Aktionen |
| Surface | Hellgrau | `#f4f6f9` | Seitenhintergrund |
| Card | Weiß | `#ffffff` | Karten, Sektionen |
| Text | Dunkelgrau | `#1a1d24` | Fließtext |
| Muted | Grau | `#6b7280` | Beschreibungen, Labels |

### Typografie

| Rolle | Font | Gewicht | Größe |
|-------|------|---------|-------|
| H1 | Outfit | 700 | clamp(32px, 5vw, 56px) |
| H2 | Outfit | 700 | clamp(24px, 3vw, 40px) |
| H3 | Outfit | 600 | clamp(18px, 2.5vw, 28px) |
| Body | Inter | 400 | 16px / 1.6 |
| Small | Inter | 400 | 14px / 1.5 |
| Code | JetBrains Mono | 400 | 14px / 1.4 |

### Icon-System

Lucide Icons (konsistent mit pdfviewer.drossmedia.de):
- `Flame` — Warming / Hauptaktion
- `Globe` — CDN / Web
- `Share2` — Social Media
- `Search` — Suchmaschinen
- `Clock` — Scheduler / Cron
- `Shield` — Sicherheit
- `BarChart3` — Dashboard / Analytics
- `Zap` — Performance / Speed
- `Server` — Self-Hosted
- `Puzzle` — Plugin / Modul

---

## 3. Globale Elemente

### 3.1 Header (Fixed, alle Seiten)

```html
<header>
  <nav>
    <a href="/" class="logo">
      <FlameIcon />
      <span>CacheWarmer</span>
    </a>

    <ul class="nav-links">
      <li><a href="/features/">Features</a></li>
      <li class="dropdown">
        <span>Platforms ▾</span>
        <ul>
          <li><a href="/wordpress/">WordPress Plugin</a></li>
          <li><a href="/drupal/">Drupal Module</a></li>
          <li><a href="/self-hosted/">Self-Hosted (Node.js)</a></li>
        </ul>
      </li>
      <li><a href="/documentation/">Docs</a></li>
      <li><a href="/changelog/">Changelog</a></li>
      <li><a href="/enterprise/">Enterprise</a></li>
    </ul>

    <div class="nav-actions">
      <a href="/pro/" class="btn btn-accent">Pro</a>
      <a href="#download" class="btn btn-primary">Get Started</a>
    </div>
  </nav>
</header>
```

### 3.2 Footer (alle Seiten)

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                  │
│  [Flame Logo] CacheWarmer                                        │
│  Warm your caches. Boost your SEO.                               │
│                                                                  │
│  Download:                                                       │
│  → WordPress Plugin (wordpress.org)                              │
│  → Drupal Module (drupal.org)                                    │
│  → npm install @cachewarmer/core                                 │
│                                                                  │
│  ─────────────────────────────────────────────                   │
│                                                                  │
│  Product            Platforms            Resources               │
│  Features           WordPress Plugin     Documentation           │
│  Pro / Pricing      Drupal Module        Changelog               │
│  Enterprise         Self-Hosted          Contact                 │
│                                                                  │
│  ─────────────────────────────────────────────                   │
│                                                                  │
│  © 2026 Dross:Media · Imprint · Privacy · Contact               │
│  Made with ♥ by Dross:Media                                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.3 SEO Meta-Tags (Global Template)

```html
<!-- Basis (jede Seite individuell) -->
<title>{Seitentitel} — CacheWarmer | CDN & Social Cache Warming</title>
<meta name="description" content="{Seitenbeschreibung}" />
<link rel="canonical" href="https://cachewarmer.drossmedia.de/{slug}/" />

<!-- Open Graph -->
<meta property="og:site_name" content="CacheWarmer by Dross:Media" />
<meta property="og:type" content="website" />
<meta property="og:title" content="{Seitentitel}" />
<meta property="og:description" content="{Seitenbeschreibung}" />
<meta property="og:image" content="https://cachewarmer.drossmedia.de/og/{slug}.png" />
<meta property="og:url" content="https://cachewarmer.drossmedia.de/{slug}/" />

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{Seitentitel}" />
<meta name="twitter:description" content="{Seitenbeschreibung}" />
<meta name="twitter:image" content="https://cachewarmer.drossmedia.de/og/{slug}.png" />

<!-- Alternate Language (falls mehrsprachig) -->
<link rel="alternate" hreflang="en" href="https://cachewarmer.drossmedia.de/{slug}/" />
<link rel="alternate" hreflang="de" href="https://cachewarmer.drossmedia.de/de/{slug}/" />
```

---

## 4. Startseite (Homepage)

**URL:** `https://cachewarmer.drossmedia.de/`
**Title:** `CacheWarmer — Warm Your CDN, Social & Search Engine Caches Automatically`
**Description:** `Automatically warm CDN edge caches, refresh Facebook, LinkedIn & Twitter previews, and notify Google & Bing about new content. Free WordPress plugin, Drupal module & self-hosted Node.js microservice.`

---

### Sektion 1: Hero

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                  │
│              Warm Your Caches.                                   │
│              Boost Your SEO.                                     │
│                                                                  │
│  Automatically warm CDN edge caches, refresh social media        │
│  previews on Facebook, LinkedIn & Twitter, and notify Google     │
│  & Bing about new content — all from one dashboard.              │
│                                                                  │
│  [Download for WordPress]  [Get Drupal Module]                   │
│                                                                  │
│  npm install @cachewarmer/core                                   │
│                                                                  │
│  ───── or ─────                                                  │
│                                                                  │
│  [View Features →]                                               │
│                                                                  │
│  ┌────────────────────────────────────────────────┐              │
│  │  [Dashboard Preview Image]                      │              │
│  │  Status: 3 Queued · 1 Running · 47 Completed   │              │
│  │  ████████████████████░░░░ 82% · 164/200 URLs    │              │
│  └────────────────────────────────────────────────┘              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### Sektion 2: The Problem

**Headline:** Your Caches Are Working Against You

**Subheadline:** Every time you publish or update content, outdated caches show the wrong version to your visitors, social media followers, and search engines.

| Problem | Icon | Beschreibung |
|---------|------|-------------|
| Stale CDN Cache | `Globe` | Your CDN still serves yesterday's version. Visitors see outdated content, broken layouts, or missing images. |
| Wrong Social Previews | `Share2` | You share your new blog post on Facebook — but it shows the old title, old image, or no preview at all. |
| Slow Search Indexing | `Search` | Google doesn't know about your new pages. It can take days or weeks for fresh content to appear in search results. |
| LinkedIn Shows Old Data | `Linkedin` | Your LinkedIn post preview shows a completely wrong description — because LinkedIn cached an old version. |
| Twitter Cards Are Broken | `Twitter` | The Twitter Card Validator shows the wrong image. Your tweet looks unprofessional. |
| No Visibility Into Caches | `EyeOff` | You have no idea which caches are stale and which are fresh. You're flying blind. |

```
┌─────────────────────────────────────────────────┐
│  ✗  Facebook shows: "Welcome to our website"     │
│     You wanted:     "New Product Launch 2026!"   │
│                                                   │
│  ✗  Google last crawled: 12 days ago              │
│     Your update was:     2 hours ago              │
│                                                   │
│  ✗  CDN serves: version from Feb 15              │
│     Current version: Feb 28                      │
└─────────────────────────────────────────────────┘
```

---

### Sektion 3: The Solution

**Headline:** One Click. All Caches Warm.

**Subheadline:** CacheWarmer systematically warms all your cache layers — CDN, social media, search engines, and even purges CDN caches directly via API — so your content is always fresh, everywhere.

| Lösung | Icon | Beschreibung |
|--------|------|-------------|
| CDN Edge Cache | `Globe` | Visits every URL with desktop & mobile user-agents so your CDN serves the latest version from edge locations worldwide. |
| Facebook Debugger | `Facebook` | Tells Facebook's Graph API to re-scrape your pages. Your OG tags (title, image, description) update instantly. |
| LinkedIn Inspector | `Linkedin` | Triggers LinkedIn's Post Inspector for every URL. Your shared links show the correct preview immediately. |
| Twitter/X Cards | `Twitter` | Loads the Tweet Composer endpoint to force Twitter's card scraper to refresh your Twitter Card meta tags. |
| Google Indexing | `Search` | Notifies Google directly via the Indexing API that your pages have changed. Faster indexing, better rankings. |
| Bing & IndexNow | `Zap` | Submits URLs to Bing Webmaster Tools and the IndexNow protocol — reaching Bing, Yandex, Seznam, and Naver. |

```
┌─────────────────────────────────────────────────┐
│  ✓  CDN:      200 URLs warmed (desktop+mobile)  │
│  ✓  Facebook: 200 OG tags refreshed             │
│  ✓  LinkedIn: 200 previews updated              │
│  ✓  Twitter:  200 cards validated                │
│  ✓  Google:   200 URLs submitted to Indexing API │
│  ✓  Bing:     200 URLs submitted via API         │
│  ✓  IndexNow: 200 URLs notified (4 engines)     │
│  ✓  CDN Purge: Cloudflare/Imperva/Akamai purged │
│                                                   │
│  Total: 1,400+ cache operations in 4 minutes     │
└─────────────────────────────────────────────────┘
```

---

### Sektion 4: Comparison Table

**Headline:** Why Use CacheWarmer Instead of Doing It Manually?

| Aspekt | Manuell | CacheWarmer |
|--------|---------|-------------|
| CDN aufwärmen | Seite für Seite im Browser öffnen | Automatisch alle URLs mit Desktop + Mobile |
| Facebook-Cache aktualisieren | Jede URL einzeln im Sharing Debugger eingeben | Batch-Update aller URLs via Graph API |
| LinkedIn-Vorschau erneuern | Post Inspector manuell aufrufen | Automatisch für alle registrierten URLs |
| Twitter Cards prüfen | Card Validator manuell pro URL | Bulk-Validation via Tweet Composer |
| Google benachrichtigen | Search Console → URL-Prüfung → einzeln einreichen | Batch-Submission via Indexing API (200/Tag) |
| Bing benachrichtigen | Bing Webmaster → URL übermitteln | Batch von 10.000 URLs via IndexNow |
| **Zeitaufwand für 100 URLs** | **~3 Stunden** | **~2 Minuten** |
| **Automatisch nach Zeitplan** | Nein | Ja (stündlich bis wöchentlich) |

---

### Sektion 5: 3-Step Process

**Headline:** Up and Running in 3 Easy Steps

```
    ①                    ②                    ③
 Install             Add Sitemap          Start Warming

 One-click install   Paste your XML       Click "Start" or
 from wordpress.org  sitemap URL or       set up automatic
 or drupal.org.      add external ones.   scheduled warming.

 [2 minutes]         [30 seconds]         [Automatic ∞]
```

---

### Sektion 6: Platform Cards

**Headline:** Available for Your Platform

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  [WordPress Logo] │  │  [Drupal Logo]    │  │  [Node.js Logo]   │
│                    │  │                    │  │                    │
│  WordPress Plugin  │  │  Drupal Module    │  │  Self-Hosted       │
│                    │  │                    │  │                    │
│  WordPress 5.8+    │  │  Drupal 10/11     │  │  Node.js 20+       │
│  PHP 7.4+          │  │  PHP 8.1+         │  │  TypeScript        │
│  One-click install │  │  Composer install  │  │  Docker ready      │
│                    │  │                    │  │                    │
│  [Get Plugin →]    │  │  [Get Module →]    │  │  [View on GitHub →]│
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

---

### Sektion 7: Pricing Preview

**Headline:** Simple, Transparent Pricing

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│                    │  │  ★ MOST POPULAR   │  │                    │
│  FREE              │  │                    │  │  ENTERPRISE        │
│                    │  │  PREMIUM           │  │                    │
│  €0                │  │                    │  │  ab €499/yr        │
│  forever           │  │  €79/yr            │  │                    │
│                    │  │                    │  │  Unlimited sites   │
│  · CDN Warming     │  │  · All 7 targets   │  │  · Multi-Site      │
│  · IndexNow        │  │  · 10,000 URLs     │  │  · Webhooks        │
│  · 50 URLs/job     │  │  · 25 Sitemaps     │  │  · White-Label     │
│  · 2 Sitemaps      │  │  · Scheduler       │  │  · Custom Cron     │
│  · Manual only     │  │  · REST API        │  │  · Priority Support│
│                    │  │  · CSV Export       │  │  · SLA available   │
│  [Get Started]     │  │                    │  │                    │
│                    │  │  [Get Premium →]    │  │  [Contact Sales →] │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

---

### Sektion 8: FAQ (Homepage)

**Headline:** Common Questions

**Q: Is CacheWarmer really free?**
A: Yes. The free version includes CDN cache warming and IndexNow support for up to 50 URLs. It's open-source and available on wordpress.org and drupal.org. Premium adds social media warming, search engine APIs, scheduling, and higher limits.

**Q: What is cache warming?**
A: When you update your website, CDNs and social media platforms still serve the old cached version. Cache warming proactively visits your URLs so the caches are refreshed — before your visitors or followers see the stale content.

**Q: Why do I need social media cache warming?**
A: When you share a link on Facebook, LinkedIn, or Twitter, these platforms cache the OG/meta tags (title, description, image). If you've updated your page, the old preview will still appear. CacheWarmer forces these platforms to re-scrape your pages so the preview is always current.

**Q: Does this work with any CDN?**
A: Yes. CacheWarmer works with Cloudflare, Fastly, AWS CloudFront, Varnish, Nginx cache, and any other reverse-proxy or CDN that caches based on HTTP requests. It simply visits your URLs — the CDN caches the fresh response. Enterprise users also get direct CDN API integration for **Cloudflare**, **Imperva (Incapsula)**, and **Akamai** — purge and re-warm caches in seconds via their native APIs.

**Q: Can I warm external sitemaps (other domains)?**
A: Yes. CacheWarmer supports adding multiple external XML sitemaps from any domain. The free version allows 1 external sitemap, Premium allows 10, and Enterprise is unlimited.

**Q: How is this different from IndexNow plugins?**
A: Most IndexNow plugins only notify search engines. CacheWarmer goes far beyond that — it also warms your CDN edge cache (desktop + mobile), refreshes Facebook/LinkedIn/Twitter previews, and submits to Google's Indexing API. It's a complete cache management solution, not just a search engine notifier.

**Q: Does CacheWarmer slow down my website?**
A: No. Warming runs in the background via WP-Cron (WordPress), Drupal Queue (Drupal), or BullMQ (Node.js). It makes outbound requests — it doesn't affect your site's frontend performance.

**Q: What data do you collect?**
A: CacheWarmer stores job and URL result data locally in your own database. No data is sent to our servers. The only external requests are to the warming targets (CDN, Facebook, etc.) on your behalf.

---

### Sektion 9: CTA Footer-Banner

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                  │
│         Ready to Warm Your Caches?                               │
│                                                                  │
│  Get started in 2 minutes. Free forever for small sites.         │
│                                                                  │
│  [Download for WordPress]  [Get Drupal Module]  [View on GitHub] │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. /wordpress/ — WordPress Plugin

**URL:** `https://cachewarmer.drossmedia.de/wordpress/`
**Title:** `CacheWarmer for WordPress — CDN, Social & Search Cache Warming Plugin`
**Description:** `Free WordPress plugin to automatically warm CDN edge caches, refresh Facebook/LinkedIn/Twitter previews, and notify Google & Bing. Install in 2 minutes.`

---

### Sektion 1: Hero

**Headline:** CacheWarmer for WordPress
**Subheadline:** Warm all 7 cache layers directly from your WordPress admin dashboard. Free plugin with one-click installation from wordpress.org.

**CTAs:**
- `[Download from wordpress.org]` (primary)
- `[View on GitHub]` (secondary)

**Requirements Box:**
```
Requirements:
· WordPress 5.8+
· PHP 7.4+
· Optional: Yoast SEO (for enhanced XML sitemap detection)
```

---

### Sektion 2: Installation

**Headline:** Installation in 3 Steps

**Step 1: Install**
```
WordPress Dashboard → Plugins → Add New → Search "CacheWarmer"
→ Click "Install Now" → Click "Activate"
```

**Step 2: Configure**
```
CacheWarmer → Settings
→ Add your API credentials (Facebook, Google, etc.)
→ Enable the warming targets you need
```

**Step 3: Add Sitemaps**
```
CacheWarmer → Sitemaps
→ Your local sitemap is auto-detected
→ Add external sitemaps from other domains
→ Click "Start Warming" or enable the scheduler
```

---

### Sektion 3: Screenshots

| Screenshot | Beschreibung |
|-----------|-------------|
| Dashboard | Status cards showing queued/running/completed/failed jobs, warming form with target selection, jobs table with progress bars |
| Sitemaps | Registered sitemaps table with domain, URL, cron schedule, last warmed timestamp, warm now / delete buttons |
| Settings | Collapsible sections for each service — CDN, Facebook, LinkedIn, Twitter, Google, Bing, IndexNow, Scheduler |
| Job Detail | Modal showing per-target breakdown — success/failed/skipped counts for each warming target |

---

### Sektion 4: WordPress-Specific Features

| Feature | Beschreibung |
|---------|-------------|
| WP-Cron Integration | Scheduled warming runs via WordPress Cron. No external cron job needed. |
| Yoast SEO Compatible | Auto-detects Yoast XML sitemaps. Works with Rank Math and other SEO plugins too. |
| WordPress REST API | Full REST API at `/wp-json/cachewarmer/v1/` for programmatic control. |
| Admin Dashboard | Native WordPress admin pages — Dashboard, Sitemaps, Settings — fully integrated. |
| Nonce Protection | All AJAX actions protected by WordPress nonces. CSRF-safe by default. |
| Capability Check | Only users with `manage_options` capability can access CacheWarmer. |
| Multisite Ready | Enterprise license supports WordPress Multisite networks. |
| Translation Ready | Fully internationalized with `__()` and `esc_html_e()`. Translation-ready .pot file included. |

---

### Sektion 5: Feature Comparison Table (Free vs Premium vs Enterprise)

(Gleiche Tabelle wie unter /pro/, aber mit WordPress-spezifischen Details)

---

### Sektion 6: CTA

```
Ready to warm your WordPress caches?

[Download Free from wordpress.org]    [Get Premium for €79/year →]
```

---

## 6. /drupal/ — Drupal Modul

**URL:** `https://cachewarmer.drossmedia.de/drupal/`
**Title:** `CacheWarmer for Drupal — CDN, Social & Search Cache Warming Module`
**Description:** `Free Drupal 10/11 module to warm CDN edge caches, refresh social media previews, and submit URLs to Google & Bing. Queue API integration for background processing.`

---

### Sektion 1: Hero

**Headline:** CacheWarmer for Drupal
**Subheadline:** Full cache warming solution for Drupal 10 and 11. Uses Drupal's Queue API for reliable background processing and Config API for seamless settings management.

**CTAs:**
- `[Download from drupal.org]` (primary)
- `[View on GitHub]` (secondary)

**Requirements Box:**
```
Requirements:
· Drupal 10.0+ or 11.x
· PHP 8.1+
· Modules: REST, Serialization (core)
```

---

### Sektion 2: Installation

**Step 1: Install via Composer**
```bash
composer require drupal/cachewarmer
drush en cachewarmer -y
```

**Step 2: Configure**
```
Configuration → Development → CacheWarmer → Settings
→ Add API credentials for each service
→ Enable warming targets
```

**Step 3: Add Sitemaps & Warm**
```
CacheWarmer → Sitemaps tab
→ Your local sitemap is auto-detected
→ Add external sitemaps
→ Dashboard tab → Start Warming
```

---

### Sektion 3: Drupal-Specific Features

| Feature | Beschreibung |
|---------|-------------|
| Queue API | Jobs processed via Drupal's Queue API. Reliable, crash-resistant background execution. |
| Config API | All settings managed via Drupal's Config system. Exportable with `drush config:export`. |
| Service Container | All services registered in `cachewarmer.services.yml`. Fully injectable, testable, overridable. |
| Permissions | `administer cachewarmer` permission with `restrict access: true`. |
| Twig Templates | Dashboard and Sitemaps use Twig templates — fully theme-overridable. |
| hook_cron | Scheduled warming integrates with Drupal's cron system. |
| REST Resource | Full REST API via Drupal's REST module at `/api/cachewarmer/`. |
| Schema & Migrations | Database schema via `hook_schema()`. Clean uninstall removes all tables and config. |

---

### Sektion 4: CTA

```
Ready to warm your Drupal caches?

[Download from drupal.org]    [Get Premium for €99/year →]
```

---

## 7. /self-hosted/ — Node.js Microservice

**URL:** `https://cachewarmer.drossmedia.de/self-hosted/`
**Title:** `CacheWarmer Self-Hosted — Node.js Microservice for CDN & Cache Warming`
**Description:** `Self-hosted Node.js/TypeScript microservice for cache warming. Docker-ready with Redis job queue, SQLite database, REST API, and Puppeteer-based CDN warming.`

---

### Sektion 1: Hero

**Headline:** CacheWarmer Self-Hosted
**Subheadline:** A standalone Node.js microservice that warms caches for any website — regardless of CMS. Deploy with Docker, manage via REST API, and integrate into your CI/CD pipeline.

**CTAs:**
- `npm install @cachewarmer/core` (code block)
- `[View on GitHub]` (primary)
- `[Docker Quickstart →]` (secondary)

---

### Sektion 2: Quickstart

```bash
# Clone & start with Docker
git clone https://github.com/drossmedia/cachewarmer.git
cd cachewarmer
docker-compose up -d

# Warm a sitemap via API
curl -X POST http://localhost:3000/api/warm \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "sitemapUrl": "https://www.example.com/sitemap.xml",
    "targets": ["cdn", "facebook", "linkedin", "twitter", "google", "bing", "indexnow"]
  }'
```

---

### Sektion 3: Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     CacheWarmer Service                         │
│                     (Node.js / TypeScript)                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  REST API ──→ BullMQ Job Queue (Redis) ──→ Workers              │
│  POST /warm    │                            │                    │
│  GET  /jobs    ├─ CDN Worker (Puppeteer)    ├─ Desktop + Mobile │
│  GET  /status  ├─ Social Worker (FB/LI/X)   ├─ Graph API        │
│                └─ Search Worker (Google/Bing)└─ Indexing API     │
│                                                                  │
│  SQLite DB ←── Status Tracking & Logging                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### Sektion 4: Tech Stack

| Technologie | Rolle |
|-------------|-------|
| Node.js 20+ | Runtime |
| TypeScript | Type-safe codebase |
| Fastify | Web framework (lightweight, fast) |
| Puppeteer + Chromium | Headless browser for CDN warming |
| BullMQ + Redis | Async job queue with rate limiting |
| SQLite (better-sqlite3) | Local database — no external DB needed |
| Docker | Containerized deployment |

---

### Sektion 5: REST API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/warm` | Submit a sitemap for warming |
| `GET` | `/api/jobs` | List all jobs |
| `GET` | `/api/jobs/:id` | Get job details with per-target stats |
| `DELETE` | `/api/jobs/:id` | Cancel/delete a job |
| `GET` | `/api/sitemaps` | List registered sitemaps |
| `POST` | `/api/sitemaps` | Register a sitemap for recurring warming |
| `DELETE` | `/api/sitemaps/:id` | Remove a sitemap |
| `GET` | `/api/status` | Health check & system status |
| `GET` | `/api/logs` | Warming logs with pagination |

---

### Sektion 6: CTA

```
Deploy your own CacheWarmer instance.

[View on GitHub]    [Docker Quickstart →]    [Read the Docs →]
```

---

## 8. /pro/ — Pricing & Pro Features

**URL:** `https://cachewarmer.drossmedia.de/pro/`
**Title:** `CacheWarmer Pro — Pricing & Plans (Free / Premium / Enterprise)`
**Description:** `Compare CacheWarmer plans. Free CDN warming for up to 50 URLs. Premium adds all 7 warming targets, scheduling, REST API, and 10,000 URLs. Enterprise: unlimited.`

---

### Sektion 1: Hero

**Headline:** Simple, Transparent Pricing
**Subheadline:** From personal blogs to enterprise deployments. Start free, upgrade when you grow.

---

### Sektion 2: Pricing Cards

```
┌──────────────────┐  ┌───────────────────────┐  ┌───────────────────────┐
│                    │  │  ★ MOST POPULAR        │  │                        │
│  FREE              │  │                        │  │  ENTERPRISE            │
│                    │  │  PREMIUM               │  │                        │
│  €0                │  │                        │  │  from €599/yr          │
│  forever           │  │  €99/yr (WordPress)    │  │                        │
│                    │  │  €129/yr (Drupal)      │  │  Unlimited sites       │
│                    │  │                        │  │                        │
│  2 warming targets │  │  All 9 targets         │  │  All 9 targets         │
│  · CDN Edge Cache  │  │  · CDN Edge Cache      │  │  · Everything in       │
│  · IndexNow        │  │  · IndexNow            │  │    Premium, plus:      │
│                    │  │  · Facebook Debugger   │  │                        │
│  50 URLs per job   │  │  · LinkedIn Inspector  │  │  Unlimited URLs        │
│  2 sitemaps        │  │  · Twitter/X Cards     │  │  Unlimited sitemaps    │
│  3 jobs per day    │  │  · Google Indexing API  │  │  Unlimited jobs        │
│  Manual warming    │  │  · Bing Webmaster API  │  │                        │
│  7-day log history │  │  · Pinterest Rich Pins │  │  Multi-Site management │
│                    │  │                        │  │  CDN Purge: Cloudflare │
│                    │  │                        │  │   Imperva & Akamai     │
│  Basic dashboard   │  │  10,000 URLs per job   │  │  Custom UA & Headers   │
│                    │  │  25 sitemaps           │  │  Custom viewports      │
│                    │  │  50 jobs per day       │  │  Authenticated warming │
│                    │  │  Scheduled warming     │  │  Conditional warming   │
│                    │  │  REST API access       │  │  Audit log             │
│                    │  │  CSV/JSON export       │  │  PDF/HTML reports      │
│                    │  │  Smart warming (diff)  │  │  Performance alerts    │
│                    │  │  Priority URL warming  │  │  Quota alerts          │
│                    │  │  Cache analytics       │  │  IP whitelist          │
│                    │  │  Broken link detection │  │  Zapier/n8n/Make       │
│                    │  │  SSL expiry warnings   │  │  365-day logs          │
│                    │  │  Performance trending  │  │  Priority support      │
│                    │  │  Quota usage tracker   │  │  SLA available         │
│                    │  │  Failed URL CSV export │  │                        │
│                    │  │  90-day log history    │  │  Email + Live Chat     │
│                    │  │  Email support         │  │                        │
│                    │  │                        │  │                        │
│  [Get Started]     │  │  [Get Premium →]       │  │  [Contact Sales →]     │
│                    │  │                        │  │                        │
│                    │  │  30-day money-back     │  │                        │
│                    │  │  guarantee             │  │                        │
└──────────────────┘  └───────────────────────┘  └───────────────────────┘
```

**Unterhalb der Cards:**

```
Also available:
· Lifetime Premium: €249 (WordPress) / €329 (Drupal) / €379 (Node.js) — pay once, own forever
· Lifetime Enterprise Starter: €1,499 (WordPress) / €1,999 (Drupal) / €2,499 (Node.js)
· Lifetime Enterprise Professional: €4,499 (WordPress) / €5,999 (Drupal) / €7,499 (Node.js)
```

---

### Sektion 3: Feature Comparison Table (vollständig)

**Headline:** Compare Every Feature

#### Warming Targets

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| CDN Edge Cache (Desktop + Mobile) | ✓ | ✓ | ✓ |
| IndexNow (Bing, Yandex, Seznam, Naver) | ✓ | ✓ | ✓ |
| Facebook Sharing Debugger | — | ✓ | ✓ |
| LinkedIn Post Inspector | — | ✓ | ✓ |
| Twitter/X Card Validator | — | ✓ | ✓ |
| Google Indexing API | — | ✓ | ✓ |
| Bing Webmaster URL Submission | — | ✓ | ✓ |
| **Pinterest Rich Pin Validator** | — | ✓ | ✓ |
| **Cloudflare Cache Purge + Warm** | — | — | ✓ |
| **Imperva (Incapsula) Cache Purge + Warm** | — | — | ✓ |
| **Akamai Fast Purge + Warm** | — | — | ✓ |

#### Limits

| Limit | Free | Premium | Enterprise |
|-------|:----:|:-------:|:----------:|
| URLs per warming job | 50 | 10,000 | Unlimited |
| Registered sitemaps | 2 | 25 | Unlimited |
| External sitemaps (other domains) | 1 | 10 | Unlimited |
| Jobs per day | 3 | 50 | Unlimited |
| Log retention | 7 days | 90 days | 365 days |
| **Managed sites** | 1 | 1 | Unlimited |

#### Scheduling & Automation

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Manual warming (dashboard) | ✓ | ✓ | ✓ |
| Scheduled warming (cron) | — | ✓ | ✓ |
| Frequency options | — | Daily / 12h / 6h | Hourly + Custom Cron |
| Auto-warm on publish | — | ✓ | ✓ |
| Multi-sitemap batch warming | — | — | ✓ |
| **Smart Warming (diff-detection)** | — | ✓ | ✓ |
| **Priority-based URL warming** | — | ✓ | ✓ |
| **Sitemap change polling** | — | — | ✓ |
| **Conditional warming (skip fresh cache)** | — | — | ✓ |
| **Custom warm sequence** | — | — | ✓ |

#### Dashboard & Reporting

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Status dashboard | ✓ | ✓ | ✓ |
| Job progress table | ✓ | ✓ | ✓ |
| Job detail modal | ✓ | ✓ | ✓ |
| Per-target statistics | — | ✓ | ✓ |
| CSV/JSON export | — | ✓ | ✓ |
| **Export failed/skipped URLs as CSV** | — | ✓ | ✓ |
| **Cache hit/miss analysis** | — | ✓ | ✓ |
| **Service success rate dashboard** | — | ✓ | ✓ |
| **Quota usage tracker** | — | ✓ | ✓ |
| **Performance trending** | — | ✓ | ✓ |
| Historical analytics | — | — | ✓ |
| Trend charts | — | — | ✓ |
| **Automated PDF/HTML reports** | — | — | ✓ |
| **Audit log** | — | — | ✓ |

#### Monitoring & Alerting

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **Broken link detection** | — | ✓ | ✓ |
| **SSL certificate expiry warnings** | — | ✓ | ✓ |
| **Performance regression alerts** | — | — | ✓ |
| **Quota exhaustion alerts** | — | — | ✓ |

#### API & Integration

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| REST API access | — | ✓ | ✓ |
| Bearer token auth | — | ✓ | ✓ |
| API rate limit | — | 60 req/min | Unlimited |
| Webhook notifications | — | — | ✓ |
| CI/CD integration | — | — | ✓ |
| **Zapier/n8n/Make webhook compatibility** | — | — | ✓ |
| **IP whitelist for API access** | — | — | ✓ |

#### Configuration & Customization

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| CDN concurrency | Fixed: 2 | 1–10 | 1–20 |
| Custom user-agent | — | — | ✓ |
| Custom timeout per service | — | ✓ | ✓ |
| Per-service rate limits | — | ✓ | ✓ |
| Log level | Fixed: info | Selectable | Selectable |
| **Custom HTTP headers** | — | — | ✓ |
| **Custom viewports** | — | — | ✓ |
| **Authenticated page warming (cookies)** | — | — | ✓ |

#### Multi-Site & Agency

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Single-site license | ✓ | ✓ | — |
| **Multi-site management (single dashboard)** | — | — | ✓ |
| White-label branding | — | — | ✓ |
| Central management | — | — | ✓ |

#### Support

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Community support | ✓ | ✓ | ✓ |
| Email support | — | ✓ | ✓ |
| Priority support | — | — | ✓ |
| Live chat | — | — | ✓ |
| SLA agreement | — | — | ✓ |

---

### Sektion 4: FAQ (Pricing)

**Q: Can I switch between plans?**
A: Yes. You can upgrade at any time and we'll prorate the difference. Downgrading takes effect at the end of your current billing period.

**Q: What happens when my license expires?**
A: The plugin continues to work with the free feature set. No data is lost. You just lose access to premium features until you renew.

**Q: Is there a refund policy?**
A: Yes — 30-day money-back guarantee, no questions asked.

**Q: Does the license cover staging sites?**
A: Yes. Staging and development environments don't count toward your site limit.

**Q: Which payment methods do you accept?**
A: Credit cards and PayPal via Stripe. Invoices available for Enterprise plans.

**Q: Is the Lifetime license really forever?**
A: Yes. Pay once, receive all updates and new features for the lifetime of the product. Support included.

**Q: Is there a non-profit discount?**
A: Yes — 40% off for registered non-profits and educational institutions. Contact us with proof of status.

**Q: One license for WordPress and Drupal?**
A: Yes. Your license key works across WordPress and Drupal. One purchase covers both platforms.

---

### Sektion 5: CTA

```
Start free. Upgrade when you're ready.

[Download Free]    [Get Premium →]    [Contact Enterprise →]
```

---

## 9. /enterprise/ — Enterprise

**URL:** `https://cachewarmer.drossmedia.de/enterprise/`
**Title:** `CacheWarmer Enterprise — Multi-Site, CDN Purge (Cloudflare, Imperva, Akamai), Webhooks & Priority Support`
**Description:** `CacheWarmer Enterprise for agencies and large organizations. Unlimited sites, unlimited URLs, direct CDN cache purge via Cloudflare, Imperva & Akamai APIs, webhook notifications, white-label branding, and SLA-backed priority support.`

---

### Sektion 1: Hero

**Headline:** CacheWarmer for Teams & Agencies
**Subheadline:** Unlimited sites, unlimited URLs, unlimited warming — with the integrations, automation, and support that enterprise teams need.

**CTAs:**
- `[Request a Demo]` (primary)
- `[Download Pricing Overview (PDF)]` (secondary)

---

### Sektion 2: Enterprise Features

| Feature | Detail |
|---------|--------|
| **Unlimited Everything** | No limits on URLs, sitemaps, jobs, or sites. Scale without worrying about quotas. |
| **Multi-Site Management** | Manage warming for multiple domains from a single dashboard. Per-domain sitemap groups, per-domain stats, cross-site overview. |
| **Webhook Notifications** | Get notified via webhook when jobs complete, fail, or encounter errors. Zapier/n8n/Make compatible payloads for no-code automation. |
| **White-Label Branding** | Remove "CacheWarmer" branding and replace with your agency's name and logo. |
| **Custom Cron Schedules** | Define warming schedules down to the minute with custom cron expressions. |
| **REST API (Unlimited)** | No rate limits on API access. IP whitelist for security. Integrate into CI/CD pipelines, custom dashboards, or automation workflows. |
| **Custom User Agent & Headers** | Define custom UA strings and HTTP headers for CDN warming. Useful for CDN rules, bot detection bypass, and internal caching layers. |
| **Custom Viewports** | Test beyond desktop and mobile — add tablet, 4K, or any custom viewport size to your warming runs. |
| **Authenticated Warming** | Warm pages behind login walls by injecting cookies or session tokens. Essential for intranets, paywalls, and staging environments. |
| **CDN Cache Purge (Cloudflare)** | Purge and re-warm via Cloudflare Zone API v4. Batch up to 30 URLs per request. Auto-detect CF-proxied domains. |
| **CDN Cache Purge (Imperva)** | Purge Imperva (Incapsula) CDN cache via Cloud WAF API v1 with URL-pattern support. Sub-500ms purge propagation across the Imperva network. |
| **CDN Cache Purge (Akamai)** | Invalidate URLs via Akamai Fast Purge API v3 with EdgeGrid authentication. Batch up to 50 URLs per request. Supports production and staging networks. |
| **Conditional Warming** | Skip URLs where cache is still fresh (checks CDN headers before warming). Saves time and bandwidth. |
| **Sitemap Change Polling** | Automatically detect sitemap changes and trigger warming without manual intervention. |
| **Performance Regression Alerts** | Get alerted when response times spike >50% compared to previous runs. |
| **Quota Exhaustion Alerts** | Receive warnings when Google/Bing daily quotas reach 80% and 100%. |
| **Audit Log** | Full trail of all API calls, job triggers, and config changes. Who did what, when. |
| **Automated PDF/HTML Reports** | Generate and download professional warming reports per job. |
| **Priority Support** | Email and live chat support with guaranteed response times. |
| **SLA Agreement** | Service Level Agreement available for teams that need uptime and response guarantees. |

---

### Sektion 3: Use Cases

**For Agencies:**
> Manage cache warming for all your client websites from one dashboard. White-label the plugin, configure each site individually, and get notified when warming jobs complete.

**For E-Commerce:**
> Product pages, category pages, blog posts — keep all caches fresh so customers always see the latest prices, images, and availability. Integrate with your deployment pipeline to warm caches after every release.

**For Publishing & Media:**
> Breaking news and time-sensitive articles need fresh caches immediately. Auto-warm on publish ensures Facebook, LinkedIn, and Twitter show the correct headline and image the moment you share.

**For Multi-Site Networks:**
> One Enterprise license covers your entire network. Warm all sites from a central dashboard with unified reporting and webhook notifications.

---

### Sektion 4: Enterprise Pricing

| Plan | Price | Sites | Support |
|------|------:|------:|---------|
| **Enterprise Starter** | €599/year (WP) / €799/year (Drupal) / €999/year (Node.js) | Up to 5 sites | Email + Live Chat |
| **Enterprise Professional** | €1,799/year (WP) / €2,499/year (Drupal) / €2,999/year (Node.js) | Up to 25 sites | Priority + SLA, Webhooks, White-Label, Multi-Site, Cloudflare/Imperva/Akamai |
| **Enterprise Corporate** | from €5,999/year (WP) / €6,999/year (Drupal) | Unlimited | Dedicated Account Manager, Custom Dev |
| **Enterprise Starter Lifetime** | €1,499 (WP) / €1,999 (Drupal) / €2,499 (Node.js) | Up to 5 sites | Lifetime updates + support |
| **Enterprise Professional Lifetime** | €4,499 (WP) / €5,999 (Drupal) / €7,499 (Node.js) | Up to 25 sites | Lifetime updates + support |

---

### Sektion 5: CTA

```
Let's find the right plan for your team.

[Request a Demo]    [Contact Sales]    [Download Pricing PDF]
```

---

## 10. /features/ — Alle Features

**URL:** `https://cachewarmer.drossmedia.de/features/`
**Title:** `CacheWarmer Features — CDN, Facebook, LinkedIn, Twitter, Google, Bing, IndexNow, Pinterest & CDN Purge`
**Description:** `Complete feature overview of CacheWarmer. 11 warming targets incl. direct CDN cache purge via Cloudflare, Imperva & Akamai. Scheduled automation, REST API, multi-sitemap support, and a real-time dashboard.`

---

### Sektion 1: Hero

**Headline:** Everything You Need to Keep Caches Fresh
**Subheadline:** 11 warming targets including direct CDN cache purge, scheduled automation, REST API, and a real-time dashboard.

---

### Sektion 2: Feature Blocks (eines pro Target)

#### CDN Edge Cache Warming
- Visits every URL with both desktop and mobile user-agents
- Configurable concurrency (2–20 parallel requests)
- Custom user-agent strings
- Configurable timeout (5–120 seconds)
- HTTP status tracking per URL
- **Available in:** Free, Premium, Enterprise

#### Facebook Sharing Debugger
- Calls Facebook Graph API v19.0 with `scrape=true`
- Forces refresh of og:title, og:description, og:image
- Configurable rate limiting (1–50 requests/second)
- Requires Facebook App ID and App Secret
- **Available in:** Premium, Enterprise

#### LinkedIn Post Inspector
- Triggers LinkedIn's Post Inspector for each URL
- Refreshes link preview data (title, description, image)
- Uses `li_at` session cookie for authentication
- Configurable delay between requests (1–30 seconds)
- **Available in:** Premium, Enterprise

#### Twitter/X Card Validator
- Loads Tweet Composer endpoint to trigger card scraping
- No API key required (uses public endpoint)
- Configurable batch size and delay
- Validates Twitter Card meta tags
- **Available in:** Premium, Enterprise

#### Google Indexing API
- Submits `URL_UPDATED` notifications to Google
- OAuth2 JWT authentication with Service Account
- Configurable daily quota (default: 200 URLs/day)
- Tracks quota usage to prevent overages
- **Available in:** Premium, Enterprise

#### Bing Webmaster URL Submission
- Batch submission of up to 500 URLs per request
- Configurable daily quota (up to 100,000 URLs)
- Direct submission to Bing's index
- **Available in:** Premium, Enterprise

#### IndexNow Protocol
- Batch submission of up to 10,000 URLs per request
- Supports Bing, Yandex, Seznam, and Naver
- Requires hosted key file on your domain
- **Available in:** Free, Premium, Enterprise

#### Pinterest Rich Pin Validator (NEW)
- Triggers Pinterest's rich pin scraper for OG meta refresh
- Automatically refreshes preview images and metadata
- Configurable delay between requests
- **Available in:** Premium, Enterprise

#### Cloudflare Cache Purge + Warm (NEW)
- Purge Cloudflare cache via Zone API v4 before warming
- Batch up to 30 URLs per purge request
- Requires Cloudflare API token with Zone:Cache Purge permission + Zone ID
- **Available in:** Enterprise

#### Imperva (Incapsula) Cache Purge + Warm (NEW)
- Purge Imperva CDN cache via Cloud WAF API v1
- URL-pattern based purge or full site purge
- Sub-500ms purge propagation across the entire Imperva network
- Requires Imperva API ID, API Key, and Site ID
- **Available in:** Enterprise

#### Akamai Fast Purge + Warm (NEW)
- Invalidate URLs via Akamai Fast Purge API v3
- Batch up to 50 URLs per invalidation request
- Cache invalidation in < 5 seconds globally
- EdgeGrid (EG1-HMAC-SHA256) authentication
- Supports production and staging networks
- **Available in:** Enterprise

---

### Sektion 3: Smart Warming Features (NEW)

| Feature | Description | Tier |
|---------|-------------|------|
| **Smart Warming (Diff-Detection)** | Only warm URLs where `lastmod` in sitemap changed since the last run. Saves time and API quota. | Premium+ |
| **Priority-Based Warming** | Process high-priority URLs first based on sitemap `<priority>` field. Critical pages get warmed before low-priority ones. | Premium+ |
| **Conditional Warming** | Send HEAD request before warming — skip URLs where CDN cache is still fresh (Age < max-age). | Enterprise |
| **Sitemap Change Polling** | Background polling: fetch sitemap every N hours, compare with previous state, auto-trigger warming on changes. | Enterprise |
| **Custom Warm Sequence** | Define the order in which warming services execute (e.g., CDN first, then social, then search engines). | Enterprise |

---

### Sektion 4: Analytics & Reporting Features (NEW)

| Feature | Description | Tier |
|---------|-------------|------|
| **Cache Hit/Miss Analysis** | Parse CDN cache headers (X-Cache, CF-Cache-Status, Age) and display hit/miss/expired ratios per job. | Premium+ |
| **Performance Trending** | Track average response time per URL over multiple warming runs. Visualize performance improvements. | Premium+ |
| **Service Success Rate Dashboard** | Per-target success/failure/skipped rates with historical comparison charts. | Premium+ |
| **Quota Usage Tracker** | Visual progress bar for Google and Bing daily API quota consumption. Alerts at 80% and 100%. | Premium+ |
| **Export Failed/Skipped URLs** | Download a CSV of all URLs that failed or were skipped during a warming job. Filter by target service. | Premium+ |
| **Automated PDF/HTML Reports** | Generate downloadable warming reports per job with summary stats, per-target results, and cache analysis. | Enterprise |
| **Audit Log** | Log all API calls, job triggers, and config changes with timestamps, actor, and details. | Enterprise |

---

### Sektion 5: Monitoring & Alerting Features (NEW)

| Feature | Description | Tier |
|---------|-------------|------|
| **Broken Link Detection** | Flag HTTP 404/5xx responses during warming. Exportable broken link report per job. | Premium+ |
| **SSL Certificate Expiry Warnings** | Check SSL certificate validity during CDN warming. Warn if certificate expires within 30 days. | Premium+ |
| **Performance Regression Alerts** | Alert (via webhook/email) when average response time increases >50% compared to the previous run. | Enterprise |
| **Quota Exhaustion Alerts** | Notify when Google/Bing daily API quota reaches 80% or 100%. | Enterprise |

---

### Sektion 6: Enterprise Configuration Features (NEW)

| Feature | Description |
|---------|-------------|
| **Custom User Agent** | Define a custom UA string for CDN warming requests. Useful for CDN rules that treat bots differently. |
| **Custom HTTP Headers** | Inject custom headers (e.g., `X-Warm: true`, auth tokens) into CDN warming requests. |
| **Custom Viewports** | Define additional viewport sizes beyond default desktop (1920x1080) and mobile (375x812). |
| **Authenticated Page Warming** | Inject cookies or session tokens to warm pages behind login walls or paywalls. |
| **Multi-Site Management** | Manage warming for multiple domains from a single dashboard. Per-domain sitemap groups and statistics. |
| **Cloudflare Cache Purge** | Purge and re-warm via Cloudflare Zone API v4. Batch up to 30 URLs per request. |
| **Imperva Cache Purge** | Purge Imperva (Incapsula) CDN cache via Cloud WAF API v1 with URL-pattern support. Sub-500ms propagation. |
| **Akamai Fast Purge** | Invalidate URLs via Akamai Fast Purge API v3 with EdgeGrid auth. Batch up to 50 URLs. Supports production + staging. |
| **IP Whitelist** | Restrict REST API access to configured IP ranges for additional security. |
| **Zapier/n8n/Make Compatibility** | Structured webhook payloads with documented JSON event schema for no-code automation platforms. |

---

### Sektion 7: Dashboard & Management Features

| Feature | Description |
|---------|-------------|
| Real-time Dashboard | Status cards showing queued, running, completed, and failed jobs at a glance. |
| Job Progress | Visual progress bars with URL count (e.g., "164/200 URLs") and percentage. |
| Job Details | Modal with per-target breakdown — success, failed, and skipped counts for each warming target. |
| Multi-Sitemap Management | Register multiple local and external sitemaps. Auto-detect local sitemaps on WordPress and Drupal. |
| Scheduled Warming | Set it and forget it. Configure hourly, daily, or weekly warming schedules. |
| REST API | Full programmatic control. Start jobs, manage sitemaps, check status — all via API. |
| CSV/JSON Export | Export warming results and logs for reporting and analysis. |

---

### Sektion 4: CTA

```
See it in action.

[Get Started Free]    [Compare Plans →]
```

---

## 11. /documentation/ — Dokumentation

**URL:** `https://cachewarmer.drossmedia.de/documentation/`
**Title:** `CacheWarmer Documentation — Installation, Configuration & API Reference`
**Description:** `Complete documentation for CacheWarmer. Installation guides for WordPress, Drupal, and Node.js. Configuration reference, REST API docs, and troubleshooting.`

---

### Inhalt (Sidebar-Navigation)

```
Documentation
├── Getting Started
│   ├── Installation (WordPress)
│   ├── Installation (Drupal)
│   ├── Installation (Self-Hosted)
│   └── Quick Start Guide
│
├── Configuration
│   ├── CDN Warming
│   ├── Facebook Debugger
│   ├── LinkedIn Inspector
│   ├── Twitter/X Cards
│   ├── Google Indexing API
│   ├── Bing Webmaster API
│   ├── IndexNow Protocol
│   ├── Cloudflare Cache Purge (Enterprise)
│   ├── Imperva Cache Purge (Enterprise)
│   ├── Akamai Fast Purge (Enterprise)
│   └── Scheduling
│
├── Sitemap Management
│   ├── Adding Local Sitemaps
│   ├── Adding External Sitemaps
│   ├── Bulk Import
│   └── Auto-Detection
│
├── REST API Reference
│   ├── Authentication
│   ├── POST /api/warm
│   ├── GET /api/jobs
│   ├── GET /api/jobs/:id
│   ├── DELETE /api/jobs/:id
│   ├── GET /api/sitemaps
│   ├── POST /api/sitemaps
│   ├── DELETE /api/sitemaps/:id
│   ├── GET /api/status
│   └── GET /api/logs
│
├── Licensing
│   ├── Free vs Premium vs Enterprise
│   ├── Activating Your License
│   └── Managing Sites
│
├── Troubleshooting
│   ├── Common Errors
│   ├── Facebook Rate Limits
│   ├── Google Quota Exceeded
│   └── LinkedIn Cookie Expired
│
└── Developer
    ├── WordPress Hooks & Filters
    ├── Drupal Services & Events
    ├── Custom Warming Targets
    └── Contributing
```

---

## 12. /changelog/ — Changelog

**URL:** `https://cachewarmer.drossmedia.de/changelog/`
**Title:** `CacheWarmer Changelog — Version History & Release Notes`
**Description:** `Complete version history of CacheWarmer. See what's new in each release for WordPress, Drupal, and Node.js.`

---

### Format

```
## v1.2.0 — 2026-03-02

### New Warming Targets (Enterprise)
- **Imperva (Incapsula) Cache Purge + Warm** — purge site cache via
  Imperva Cloud WAF API v1 with URL-pattern support; sub-500ms purge
  propagation across the Imperva network
- **Akamai Fast Purge + Warm** — invalidate URLs via Akamai Fast Purge
  API v3 with EdgeGrid (EG1-HMAC-SHA256) authentication; batch up to
  50 URLs per request; supports production and staging networks

### Enhanced
- CDN Cache Purge feature now supports 3 providers (Cloudflare +
  Imperva + Akamai) — all configurable independently
- New `cdn-purge` warming target for API usage: purges all enabled
  CDN providers before optional Puppeteer-based re-warming
- Updated feature flags and license tiers across all platforms

### Platforms
- WordPress 5.8+ / PHP 7.4+
- Drupal 10+ / PHP 8.1+
- Node.js 20+ / TypeScript

---

## v1.1.0 — 2026-03-02

### New Warming Target
- Pinterest Rich Pin Validator — triggers Pinterest's rich pin scraper
  to refresh OG meta cache (Premium+)
- Cloudflare API Integration — purge + warm via Cloudflare Zone API;
  auto-detect CF domains (Enterprise)

### New Features (Premium)
- Smart Warming (diff-detection) — only warm URLs where `lastmod`
  changed since last run
- Priority-based URL warming — process high-priority URLs first
  based on sitemap `<priority>` field
- Cache hit/miss analysis — parse CDN cache headers and display
  hit/miss ratios per job
- Performance trending — track average response times per URL across
  multiple runs
- Service success rate dashboard — per-target success/failure rates
  with historical comparison
- Quota usage tracker — visual progress bar for Google/Bing daily
  API quota consumption
- Broken link detection — flag HTTP 404/5xx responses during warming
  with exportable report
- SSL certificate expiry warnings — detect certificates expiring
  within 30 days during CDN warming
- Export failed/skipped URLs as CSV — download a CSV of all URLs
  that failed or were skipped during a warming job
- Custom timeout per service — override timeout individually per
  warming target

### New Features (Enterprise)
- Custom User Agent strings — define a custom UA for CDN warming
- Custom HTTP headers — inject custom headers (e.g., `X-Warm: true`,
  auth headers) into CDN requests
- Custom viewports — define additional viewport sizes beyond default
  desktop/mobile
- Authenticated page warming — inject cookies/session tokens to warm
  pages behind login
- Sitemap change polling — periodically poll sitemaps for changes
  and auto-trigger warming
- Conditional warming — skip URLs if CDN cache headers indicate
  cache is still fresh
- Custom warm sequence — user-defined order of warming services
- Multi-site management — single dashboard managing warming for
  multiple domains with per-domain stats
- Audit log — log all API calls, job triggers, config changes with
  timestamps and actor
- IP whitelist for API access — restrict REST API to configured
  IP ranges
- Performance regression alerts — alert when average response time
  increases >50% vs previous run
- Quota exhaustion alerts — notify when Google/Bing daily quota
  reaches 80% or 100%
- Automated PDF/HTML reports — generate downloadable warming reports
  per job
- Zapier/n8n/Make webhook compatibility — structured webhook payloads
  with documented event schema

### Platforms
- WordPress 5.8+ / PHP 7.4+
- Drupal 10+ / PHP 8.1+
- Node.js 20+ / TypeScript

---

## v1.0.0 — 2026-03-15

### Added
- Initial release with 7 warming targets (CDN, Facebook, LinkedIn,
  Twitter, Google, Bing, IndexNow)
- WordPress plugin with admin dashboard, sitemaps management, and settings
- Drupal module with Queue API, Config API, and Twig templates
- Self-hosted Node.js microservice with Docker, Redis, and SQLite
- REST API for all three platforms
- Multi-sitemap support (local + external)
- Scheduled warming via WP-Cron, Drupal Cron, and BullMQ

### Platforms
- WordPress 5.8+ / PHP 7.4+
- Drupal 10+ / PHP 8.1+
- Node.js 20+ / TypeScript
```

---

## 13. /contact/ — Kontakt

**URL:** `https://cachewarmer.drossmedia.de/contact/`
**Title:** `Contact CacheWarmer — Support, Sales & Enterprise Inquiries`
**Description:** `Get in touch with the CacheWarmer team. Support for free and premium users, enterprise sales, and partnership inquiries.`

---

### Inhalt

**Headline:** Get in Touch

**Kontaktformular:**

| Feld | Typ | Required |
|------|-----|----------|
| Name | Text | Yes |
| Email | Email | Yes |
| Subject | Dropdown: Support / Sales / Enterprise / Partnership / Other | Yes |
| Platform | Dropdown: WordPress / Drupal / Self-Hosted / Not yet using | No |
| License Key | Text | No |
| Message | Textarea | Yes |

**Weitere Kontaktdaten:**

```
Dross:Media
Alexander Dross

Email: hello@drossmedia.de
Web: https://drossmedia.de

Enterprise & Sales: enterprise@drossmedia.de
Support: support@drossmedia.de
```

---

## 14. /imprint/ — Impressum

**URL:** `https://cachewarmer.drossmedia.de/imprint/`
**Title:** `Imprint — CacheWarmer by Dross:Media`

### Inhalt

```
Impressum

Angaben gemäß § 5 TMG:

Dross:Media
Alexander Dross
[Adresse]
[PLZ Ort]

Kontakt:
E-Mail: hello@drossmedia.de
Web: https://drossmedia.de

Umsatzsteuer-ID:
Umsatzsteuer-Identifikationsnummer gemäß § 27a Umsatzsteuergesetz:
[USt-IdNr.]

Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:
Alexander Dross
[Adresse]

Streitschlichtung:
Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung
(OS) bereit: https://ec.europa.eu/consumers/odr

Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor
einer Verbraucherschlichtungsstelle teilzunehmen.
```

---

## 15. /privacy/ — Datenschutz

**URL:** `https://cachewarmer.drossmedia.de/privacy/`
**Title:** `Privacy Policy — CacheWarmer by Dross:Media`

### Inhalt (Kurzfassung der Sektionen)

```
Datenschutzerklärung

1. Verantwortlicher
   Dross:Media, Alexander Dross, [Adresse]

2. Hosting
   Diese Website wird bei [Hoster] gehostet. Server-Logfiles werden
   gespeichert (IP-Adresse, Zeitstempel, Seite, Browser).
   Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO.

3. Kontaktformular
   Wenn Sie uns über das Kontaktformular schreiben, werden Ihre Angaben
   zur Bearbeitung der Anfrage gespeichert. Rechtsgrundlage: Art. 6 Abs. 1
   lit. b DSGVO.

4. Lizenzverwaltung
   Für Premium- und Enterprise-Kunden speichern wir: Name, E-Mail,
   Lizenzschlüssel, Domain(s). Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO
   (Vertragserfüllung).

5. Zahlungsabwicklung
   Zahlungen werden über Stripe abgewickelt. Wir speichern keine
   Kreditkartendaten. Stripes Datenschutzerklärung: https://stripe.com/privacy

6. CacheWarmer Plugin/Modul
   Das CacheWarmer Plugin/Modul speichert ALLE Daten lokal in Ihrer eigenen
   Datenbank. Es werden KEINE Daten an unsere Server übermittelt. Die einzigen
   externen Verbindungen sind zu den Warming-Zielen (CDN, Facebook, LinkedIn,
   Twitter, Google, Bing, IndexNow) — auf Ihren Befehl.

7. Cookies
   Diese Website verwendet keine Tracking-Cookies. Für die Lizenzverwaltung
   wird ein technisch notwendiger Session-Cookie verwendet.

8. Ihre Rechte
   Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit,
   Widerspruch (Art. 15-21 DSGVO). Beschwerderecht bei der zuständigen
   Aufsichtsbehörde.

9. Aktualität
   Stand: März 2026
```

---

## 16. Schema.org / Structured Data

### Product Schema (Startseite)

```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "CacheWarmer",
  "applicationCategory": "WebApplication",
  "operatingSystem": "WordPress, Drupal, Node.js",
  "description": "Automatically warm CDN edge caches, refresh social media previews, and notify search engines about new content.",
  "url": "https://cachewarmer.drossmedia.de/",
  "author": {
    "@type": "Organization",
    "name": "Dross:Media",
    "url": "https://drossmedia.de"
  },
  "offers": [
    {
      "@type": "Offer",
      "name": "Free",
      "price": "0",
      "priceCurrency": "EUR",
      "description": "CDN warming + IndexNow, 50 URLs per job"
    },
    {
      "@type": "Offer",
      "name": "Premium",
      "price": "79",
      "priceCurrency": "EUR",
      "billingIncrement": "P1Y",
      "description": "All 7 warming targets, 10,000 URLs, scheduler, REST API"
    },
    {
      "@type": "Offer",
      "name": "Enterprise",
      "price": "499",
      "priceCurrency": "EUR",
      "billingIncrement": "P1Y",
      "description": "Up to 5 sites, priority support, all warming targets"
    }
  ],
  "screenshot": "https://cachewarmer.drossmedia.de/images/dashboard-preview.png",
  "featureList": [
    "CDN Edge Cache Warming (Desktop + Mobile)",
    "Facebook Sharing Debugger Integration",
    "LinkedIn Post Inspector Integration",
    "Twitter/X Card Validator",
    "Google Indexing API Submission",
    "Bing Webmaster URL Submission",
    "IndexNow Protocol (Bing, Yandex, Seznam, Naver)",
    "Scheduled Warming (Hourly to Weekly)",
    "REST API with Bearer Token Authentication",
    "Multi-Sitemap Support (Local + External)",
    "Real-time Dashboard with Job Progress",
    "CSV/JSON Export"
  ]
}
```

### Organization Schema

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Dross:Media",
  "url": "https://drossmedia.de",
  "logo": "https://drossmedia.de/logo.png",
  "sameAs": [
    "https://github.com/drossmedia"
  ]
}
```

### FAQ Schema (pro FAQ-Sektion)

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is CacheWarmer really free?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. The free version includes CDN cache warming and IndexNow..."
      }
    }
  ]
}
```

### BreadcrumbList Schema

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://cachewarmer.drossmedia.de/" },
    { "@type": "ListItem", "position": 2, "name": "WordPress", "item": "https://cachewarmer.drossmedia.de/wordpress/" }
  ]
}
```

---

## Zusammenfassung: Alle Seiten auf einen Blick

| Seite | URL | Zweck | Primäre CTA |
|-------|-----|-------|-------------|
| **Startseite** | `/` | Landingpage, Problem/Lösung, Pricing-Preview | Download / Get Started |
| **WordPress** | `/wordpress/` | Plugin-Details, Installation, Screenshots | Download from wordpress.org |
| **Drupal** | `/drupal/` | Modul-Details, Composer-Install, Features | Download from drupal.org |
| **Self-Hosted** | `/self-hosted/` | Node.js Microservice, Docker, REST API | View on GitHub |
| **Pro / Pricing** | `/pro/` | Pricing-Tabelle, Feature-Vergleich, FAQ | Get Premium |
| **Enterprise** | `/enterprise/` | Enterprise-Features, Use Cases, Pricing | Request Demo |
| **Features** | `/features/` | Alle 7 Targets im Detail, Dashboard | Get Started Free |
| **Documentation** | `/documentation/` | Technische Doku, API-Referenz | — |
| **Changelog** | `/changelog/` | Versionshistorie | — |
| **Contact** | `/contact/` | Kontaktformular, Support | Send Message |
| **Imprint** | `/imprint/` | Impressum (§ 5 TMG) | — |
| **Privacy** | `/privacy/` | Datenschutzerklärung (DSGVO) | — |
