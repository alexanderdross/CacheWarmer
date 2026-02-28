# CacheWarmer Microservice – Konzept & Architekturplan

## 0. Repository

```
GitHub: https://github.com/alexanderdross/cachewarmer.git
```

## 1. Überblick

Ein selbst-gehosteter Microservice, der XML-Sitemaps entgegennimmt und automatisiert:
- Alle URLs per Headless Chrome aufruft (CDN/Edge-Cache aufwärmen)
- Social-Media-Caches aktualisiert (Facebook, LinkedIn, Twitter/X)
- URLs bei Suchmaschinen zur Indexierung einreicht (Google, Bing via IndexNow)

---

## 2. Architektur

```
┌─────────────────────────────────────────────────────┐
│                    Frontend / UI                     │
│  (Sitemap-URL eingeben, Status-Dashboard, Logs)     │
└──────────────────────┬──────────────────────────────┘
                       │ REST API
┌──────────────────────▼──────────────────────────────┐
│                  Backend (Node.js)                   │
│                                                      │
│  ┌────────────┐  ┌─────────────┐  ┌──────────────┐  │
│  │  Sitemap   │  │   Job Queue │  │   Dashboard  │  │
│  │  Parser    │──▶│  (BullMQ /  │  │   & Logs     │  │
│  │            │  │  Agenda.js) │  │   (SQLite)   │  │
│  └────────────┘  └──────┬──────┘  └──────────────┘  │
│                         │                            │
│         ┌───────────────┼───────────────┐            │
│         ▼               ▼               ▼            │
│  ┌─────────────┐ ┌────────────┐ ┌──────────────┐    │
│  │  CDN Warmer │ │  Social    │ │  Search      │    │
│  │  (Puppeteer)│ │  Cache     │ │  Indexing    │    │
│  │             │ │  Workers   │ │  Workers     │    │
│  └─────────────┘ └────────────┘ └──────────────┘    │
└─────────────────────────────────────────────────────┘
```

---

## 3. Tech-Stack

| Komponente         | Technologie                          | Begründung                                    |
|--------------------|--------------------------------------|-----------------------------------------------|
| Runtime            | Node.js (>=18 LTS)                   | Async-I/O, gutes Ecosystem                    |
| Framework          | Express.js oder Fastify              | Leichtgewichtig, Webspace-kompatibel          |
| Headless Browser   | Puppeteer (Chromium)                 | Full-Rendering für CDN + Social Previews      |
| Job Queue          | BullMQ (mit Redis) ODER Agenda.js    | Retry, Concurrency, Scheduling                |
| Datenbank          | SQLite (via better-sqlite3)          | Kein externer DB-Server nötig auf Webspace    |
| Frontend           | Einfaches SPA (React/Vue oder Vanilla JS) | Dashboard & Sitemap-Eingabe             |
| Auth               | API-Key + optional Basic Auth        | Schutz vor unautorisiertem Zugriff            |

### Alternative bei eingeschränktem Webspace (kein Root/Docker):
- **Ohne Redis**: Eigene File-basierte Queue oder `bee-queue` mit SQLite-Backend
- **Ohne Puppeteer**: Playwright mit leichterem Chromium oder `got`/`axios` für einfache HTTP-Requests (kein JS-Rendering)

---

## 4. Module & Workflows

### 4.1 Sitemap Parser
```
Input:  XML-Sitemap-URL (z.B. https://example.com/sitemap_index.xml)
Output: Liste aller URLs (rekursiv bei Sitemap-Index)
```
- Unterstützt `<sitemapindex>` (verschachtelte Sitemaps)
- Unterstützt `<urlset>` (direkte URL-Listen)
- Parst `<loc>`, `<lastmod>`, `<changefreq>`, `<priority>`
- Library: `fast-xml-parser` oder `xml2js`

### 4.2 CDN Cache Warmer (Puppeteer)
```
Für jede URL aus der Sitemap:
  1. Headless Chrome öffnet die URL
  2. Wartet auf `networkidle0` oder `load` Event
  3. Optional: Screenshot speichern (Debugging)
  4. Seite schließen
  5. Status loggen (HTTP-Code, Ladezeit, Fehler)
```
- **Concurrency**: Max 3–5 parallele Tabs (RAM-schonend auf Webspace)
- **Timeout**: 30s pro Seite, danach Abbruch + Retry
- **User-Agent**: Konfigurierbarer UA-String (z.B. `CacheWarmer/1.0`)

### 4.3 Facebook Sharing Debugger
```
Endpoint: POST https://graph.facebook.com/
Query:    id={URL}&scrape=true&access_token={APP_TOKEN}
```
- Benötigt: Facebook App Access Token (`App-ID|App-Secret`)
- Rate Limit: ~50 Requests/Stunde (mit Throttling)
- Aktualisiert den Open Graph Cache bei Facebook
- **Setup**: Facebook Developer App erstellen → App Token generieren

### 4.4 LinkedIn Post Inspector
```
Endpoint: POST https://api.linkedin.com/v2/ugcPosts
          ODER manuell via URL-Aufruf
```
- LinkedIn hat **keine offizielle API** zum Cache-Invalidieren
- **Workaround A**: Puppeteer öffnet `https://www.linkedin.com/post-inspector/inspect/{encoded_url}`
- **Workaround B**: LinkedIn Share API mit OAuth2 Token
- **Hinweis**: LinkedIn Post Inspector erfordert Login → Puppeteer mit gespeicherter Session/Cookies

### 4.5 Twitter/X Card Cache (Tweet Composer Intent)
```
URL-Schema: https://twitter.com/intent/tweet?url={ENCODED_URL}
```
- Kein API-Zugang oder Login erforderlich
- Puppeteer öffnet die Intent-URL → Twitter lädt die Card-Preview → Twitterbot scrapt die Metadaten
- Der Tweet muss NICHT abgesendet werden – allein das Laden der Preview triggert den Cache-Refresh
- **Ablauf**:
  1. Puppeteer öffnet `https://twitter.com/intent/tweet?url={encodeURIComponent(url)}`
  2. Wartet auf das Laden der Card-Preview (Selector für Preview-Element)
  3. Seite schließen (kein Login nötig, kein Tweet wird gepostet)
  4. Status loggen
- **Hinweis**: Twitter Crawler re-indiziert Cards nur ~alle 7 Tage automatisch, daher ist aktives Warming sinnvoll
- **Rate Limiting**: Konservativ throttlen (~1 Req/5s), um IP-Blocks zu vermeiden

### 4.6 Search Engine Indexing

#### 4.6.1 IndexNow (Bing, Yandex, Seznam, Naver u.a.)
```
POST https://api.indexnow.org/indexnow
Content-Type: application/json

{
  "host": "example.com",
  "key": "{INDEXNOW_KEY}",
  "keyLocation": "https://example.com/{INDEXNOW_KEY}.txt",
  "urlList": ["https://example.com/page1", ...]
}
```
- **Setup**: Key-Datei auf dem Webserver hinterlegen
- Batch-Submissions möglich (bis 10.000 URLs pro Request)
- Automatisch an Bing, Yandex & Partner verteilt

#### 4.6.2 Google Search Console API
```
POST https://searchconsole.googleapis.com/v1/urlInspection/index:inspect
Authorization: Bearer {OAUTH_TOKEN}

{
  "inspectionUrl": "https://example.com/page1",
  "siteUrl": "https://example.com/"
}
```
- Benötigt: Google Cloud Projekt + OAuth2 Service Account
- **Achtung**: Google bietet kein Batch-Indexing über die API – nur Inspection
- Alternative: `Google Indexing API` (nur für `JobPosting` und `BroadcastEvent` Schemata)
- **Empfehlung**: Für allgemeine Seiten → Sitemap bei GSC einreichen + IndexNow nutzen

#### 4.6.3 Bing Webmaster Tools API
```
POST https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch
Content-Type: application/json
apikey: {BING_API_KEY}

{
  "siteUrl": "https://example.com",
  "urlList": ["https://example.com/page1", ...]
}
```
- Max 10.000 URLs/Tag (bei verifizierten Sites)
- API-Key über Bing Webmaster Portal

---

## 5. Datenmodell (SQLite)

```sql
-- Sitemaps die überwacht werden
CREATE TABLE sitemaps (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    url         TEXT NOT NULL UNIQUE,
    name        TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_run    DATETIME
);

-- Einzelne URLs aus den Sitemaps
CREATE TABLE urls (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    sitemap_id  INTEGER REFERENCES sitemaps(id),
    url         TEXT NOT NULL,
    lastmod     DATETIME,
    priority    REAL,
    UNIQUE(sitemap_id, url)
);

-- Job-Ausführungen / Runs
CREATE TABLE runs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    sitemap_id  INTEGER REFERENCES sitemaps(id),
    started_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME,
    status      TEXT DEFAULT 'running',  -- running, completed, failed
    total_urls  INTEGER,
    completed   INTEGER DEFAULT 0,
    failed      INTEGER DEFAULT 0
);

-- Ergebnisse pro URL und Service
CREATE TABLE results (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id      INTEGER REFERENCES runs(id),
    url_id      INTEGER REFERENCES urls(id),
    service     TEXT NOT NULL,  -- 'cdn', 'facebook', 'linkedin', 'twitter', 'indexnow', 'google', 'bing'
    status      TEXT,           -- 'success', 'error', 'timeout', 'skipped'
    http_code   INTEGER,
    duration_ms INTEGER,
    error_msg   TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 6. API-Endpunkte

```
POST   /api/sitemaps              → Neue Sitemap hinzufügen
GET    /api/sitemaps              → Alle Sitemaps auflisten
DELETE /api/sitemaps/:id          → Sitemap entfernen

POST   /api/sitemaps/:id/warm    → Cache-Warming starten (alle Services)
POST   /api/sitemaps/:id/warm    → Body: { services: ['cdn','facebook','indexnow'] }
GET    /api/runs                  → Alle Runs auflisten
GET    /api/runs/:id              → Run-Details mit Ergebnissen
GET    /api/runs/:id/live         → SSE-Stream für Live-Progress

POST   /api/warm-url              → Einzelne URL warmen
GET    /api/settings              → Konfiguration lesen
PUT    /api/settings              → Konfiguration aktualisieren

GET    /api/health                → Health-Check
```

---

## 7. Konfiguration (.env)

```env
# Server
PORT=3000
API_KEY=your-secret-api-key
NODE_ENV=production

# Puppeteer / Chrome
CHROME_EXECUTABLE_PATH=/usr/bin/chromium-browser
MAX_CONCURRENT_TABS=3
PAGE_TIMEOUT_MS=30000

# Facebook
FACEBOOK_APP_ID=123456789
FACEBOOK_APP_SECRET=abcdef123456

# LinkedIn (Session Cookies oder OAuth)
LINKEDIN_SESSION_COOKIE=li_at=XXXXXXX

# Twitter/X (kein API-Key nötig – Tweet Composer Intent)
# Nur Rate-Limiting konfigurierbar

# IndexNow
INDEXNOW_KEY=my-indexnow-key-12345

# Google Search Console
GOOGLE_SERVICE_ACCOUNT_JSON=./credentials/google-sa.json
GOOGLE_SITE_URL=https://example.com/

# Bing Webmaster
BING_API_KEY=your-bing-api-key

# Rate Limiting
RATE_LIMIT_CDN_PER_SECOND=2
RATE_LIMIT_FACEBOOK_PER_HOUR=50
RATE_LIMIT_INDEXNOW_BATCH_SIZE=100

# Lizenzierung (siehe Abschnitt 15)
LICENSE_KEY=CW-PRO-A1B2C3D4E5F6G7H8
LICENSE_DASHBOARD_URL=https://dashboard.cachewarmer.drossmedia.de
```

---

## 8. Projektstruktur

```
cachewarmer/
├── CLAUDE.md                    # Dieses Dokument
├── package.json
├── .env.example
├── .env
├── src/
│   ├── index.js                 # Einstiegspunkt / Server-Start
│   ├── config.js                # Env-Variablen laden & validieren
│   ├── server.js                # Express/Fastify Setup + Routes
│   ├── db/
│   │   ├── database.js          # SQLite-Verbindung
│   │   └── migrations.js        # Schema-Setup
│   ├── routes/
│   │   ├── sitemaps.js
│   │   ├── runs.js
│   │   ├── settings.js
│   │   └── health.js
│   ├── services/
│   │   ├── sitemap-parser.js    # XML-Sitemap laden & parsen
│   │   ├── cdn-warmer.js        # Puppeteer CDN-Warming
│   │   ├── facebook.js          # FB Sharing Debugger API
│   │   ├── linkedin.js          # LinkedIn Post Inspector
│   │   ├── twitter.js           # Twitter/X Card Cache
│   │   ├── indexnow.js          # IndexNow Submission
│   │   ├── google-search.js     # Google Search Console API
│   │   └── bing-webmaster.js    # Bing Webmaster Tools API
│   ├── queue/
│   │   ├── queue-manager.js     # Job-Queue Setup
│   │   └── workers.js           # Worker-Prozesse
│   ├── middleware/
│   │   ├── auth.js              # API-Key Validierung
│   │   └── rate-limiter.js
│   └── utils/
│       ├── logger.js            # Logging (pino / winston)
│       ├── browser-pool.js      # Puppeteer-Instanz-Management
│       └── retry.js             # Retry-Logik mit Backoff
├── public/
│   ├── index.html               # Dashboard SPA
│   ├── app.js
│   └── style.css
├── credentials/                  # Git-ignoriert
│   └── google-sa.json
├── data/
│   ├── cachewarmer.db           # SQLite-Datenbank
│   └── .instance-id             # Persistente UUID für Lizenz-Fingerprint
├── src/
│   └── license/
│       ├── client.js            # Lizenz-Aktivierung & Heartbeat
│       ├── fingerprint.js       # Installations-Fingerprint (SHA-256)
│       └── feature-gate.js      # Feature-Gating Middleware
├── LASTENHEFT-LICENSE-DASHBOARD.md  # Lastenheft License Dashboard
└── license-dashboard/
    └── README.md                # Technische Doku License Manager Plugin
```

---

## 9. Ablauf eines Warming-Runs

```
1. User submittiert Sitemap-URL über UI/API
2. Sitemap Parser lädt XML, extrahiert alle URLs
3. URLs werden in DB gespeichert, neuer Run wird erstellt
4. Für jede URL werden Jobs in die Queue eingestellt:
   ├── Job: CDN Warming (Puppeteer)
   ├── Job: Facebook Cache Refresh
   ├── Job: LinkedIn Inspector
   ├── Job: Twitter/X Card Refresh
   ├── Job: IndexNow Submission (Batch)
   ├── Job: Google Search Console
   └── Job: Bing Webmaster Submission (Batch)
5. Workers verarbeiten Jobs parallel (mit Concurrency-Limits)
6. Ergebnisse werden in results-Tabelle geschrieben
7. Dashboard zeigt Live-Fortschritt via SSE
8. Nach Abschluss: Zusammenfassung & Benachrichtigung
```

---

## 10. Rate-Limiting & Throttling-Strategie

| Service          | Limit                     | Strategie                            |
|------------------|---------------------------|--------------------------------------|
| CDN Warming      | 2–5 Requests/Sekunde      | Concurrency-Pool + Delay             |
| Facebook API     | ~50/Stunde                | Token-Bucket, Pause bei 429          |
| LinkedIn         | ~30/Stunde (geschätzt)    | Conservative Throttling              |
| Twitter/X        | ~1 Req/5s (konservativ) | Festes Delay, IP-Schutz              |
| IndexNow         | 10.000 URLs/Batch         | Batch-Requests, 1 Req/10s           |
| Google SC API    | 600 Req/Min               | Standard Throttling                  |
| Bing API         | 10.000/Tag                | Daily Counter, Batch-Submissions     |

---

## 11. Webspace-Kompatibilität

### Anforderungen an den Webspace:
- **Node.js** ≥ 18 (oder Docker-Support)
- **Chromium/Chrome** installierbar (für Puppeteer)
- **Persistent Storage** für SQLite-DB
- **Mindestens 512 MB RAM** (Chromium braucht ~200-300 MB)
- **Cron-Job Unterstützung** (für scheduled Runs)

### Falls kein Chromium möglich:
- Fallback auf reine HTTP-Requests (`got`/`axios`) für CDN-Warming
- Social-Media-APIs direkt ansprechen (ohne Browser-Automation)
- Cloud-basierter Headless Chrome Service (z.B. Browserless.io)

### Deployment-Optionen:
1. **Node.js Webspace** (z.B. Netcup, Hetzner vServer): Volle Funktionalität
2. **Shared Hosting mit Node.js**: Eingeschränkt (kein Puppeteer)
3. **Docker**: `docker-compose.yml` mit Node + Chromium Container

---

## 12. Sicherheit

- API-Key-Authentifizierung für alle Endpunkte
- Rate-Limiting auf API-Ebene (Schutz vor Missbrauch)
- Input-Validierung (nur gültige URLs/Sitemaps akzeptieren)
- Credentials in `.env` (nie in Git)
- HTTPS erzwingen
- Optional: IP-Whitelist

---

## 13. Erweiterungsideen (Phase 2)

- **Scheduling**: Automatische Runs per Cron (täglich/wöchentlich)
- **Webhooks**: Benachrichtigung bei Completion (Slack, E-Mail)
- **Diff-Detection**: Nur geänderte URLs warmen (basierend auf `lastmod`)
- **Multi-Tenant**: Mehrere Nutzer/Projekte
- **Lighthouse Audit**: Performance-Score pro URL mitspeichern
- **Screenshot-Archiv**: Vor/Nach-Vergleich
- **Pinterest Rich Pin Validator**: Zusätzlicher Social-Cache
- **Cloudflare API Integration**: Direkte Cache-Purge + Warm Befehle

---

## 14. Nächste Schritte (Implementierungsreihenfolge)

1. ☐ Projekt-Scaffolding (package.json, Ordnerstruktur, .env)
2. ☐ SQLite-Datenbank + Migrationen
3. ☐ Sitemap-Parser (XML → URL-Liste)
4. ☐ Express Server + API-Routes + Auth-Middleware
5. ☐ CDN Cache Warmer (Puppeteer)
6. ☐ Facebook Sharing Debugger Integration
7. ☐ IndexNow + Bing Webmaster Integration
8. ☐ Google Search Console Integration
9. ☐ LinkedIn Post Inspector (Puppeteer-basiert)
10. ☐ Twitter/X Card Refresh
11. ☐ Job-Queue mit Retry-Logik
12. ☐ Frontend Dashboard (Progress, Logs, History)
13. ☐ Scheduling / Cron-Integration
14. ☐ Testing & Deployment

---

## 15. Lizenzierung & Commercial Distribution

### 15.1 Überblick

CacheWarmer wird kommerziell vertrieben über ein zentrales **License Management Dashboard** basierend auf WordPress.

| Eigenschaft | Wert |
|------------|------|
| Dashboard URL | `https://dashboard.cachewarmer.drossmedia.de` |
| WordPress Plugin | `cachewarmer-license-manager` (CWLM) |
| API Namespace | `cwlm/v1` |
| Datenbank | MySQL (7 Tabellen, Prefix `wp_cwlm_`) |
| Payment | Stripe (native Webhook-Integration) |

### 15.2 Produkt-Tiers

| Feature | Free | Professional | Enterprise |
|---------|------|-------------|-----------|
| CDN Warming (HTTP) | ✓ | ✓ | ✓ |
| CDN Warming (Puppeteer) | – | ✓ | ✓ |
| Social Media (FB, LI, X) | – | ✓ | ✓ |
| IndexNow | – | ✓ | ✓ |
| Google Search Console | – | – | ✓ |
| Bing Webmaster Tools | – | – | ✓ |
| Scheduling | – | ✓ | ✓ |
| Max Sitemaps | 1 | 5 | Unbegrenzt |
| Max URLs | 50 | 5.000 | Unbegrenzt |
| Workers | 1 | 5 | 10+ |
| Multi-Site / Webhooks / Cloudflare | – | – | ✓ |
| Priority Support | – | – | ✓ |

Zusätzlich: **Development**-Lizenz (Enterprise-Features, nur localhost/\*.local/\*.dev/\*.test).

### 15.3 Plattform-Support

CacheWarmer wird auf 4 Plattformen angeboten:

| Plattform | Paket | Fingerprint |
|-----------|-------|-------------|
| Node.js Standalone | `@drossmedia/cachewarmer` (NPM) | Hostname + UUID + OS |
| Docker | `drossmedia/cachewarmer` (Docker Hub) | Host-UUID (Volume) |
| WordPress Plugin | `cachewarmer` (wordpress.org / Dashboard) | Domain + WP-Version |
| Drupal Modul | `cachewarmer` (drupal.org / Dashboard) | Domain + Drupal-Version |

### 15.4 License Key Format

```
CW-{TIER}-{HEX16}
Beispiel: CW-PRO-A1B2C3D4E5F6G7H8
```

### 15.5 Integration in CacheWarmer

Die Lizenzvalidierung wird beim Start durchgeführt und per Heartbeat alle 24h erneuert:

1. `LICENSE_KEY` und `LICENSE_DASHBOARD_URL` in `.env` konfigurieren
2. Beim Start: `src/license/client.js` ruft `/cwlm/v1/activate` auf
3. Features werden gecacht und über `src/license/feature-gate.js` geprüft
4. Alle 24h: Heartbeat an `/cwlm/v1/check`
5. Bei Feature-Zugriff: Middleware prüft ob Tier berechtigt

### 15.6 Dokumentation

| Dokument | Beschreibung |
|----------|-------------|
| `LASTENHEFT-LICENSE-DASHBOARD.md` | Formales Lastenheft mit Anforderungen, Datenmodell, API-Spec, Implementierungsplan |
| `license-dashboard/README.md` | Technische Doku des WordPress-Plugins (Schema, Endpoints, Stripe, Admin UI) |
