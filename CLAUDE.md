# CacheWarmer Microservice — Konzept & Architekturplan

## Ziel

Ein selbst gehosteter Microservice, der XML-Sitemaps entgegennimmt und sämtliche darin enthaltenen URLs systematisch aufwärmt — im CDN-Edge-Cache, in den Social-Media-Scraper-Caches (Facebook, LinkedIn, Twitter/X) sowie bei Suchmaschinen (Google, Bing via IndexNow).

---

## 1. Überblick & Architektur

```
┌─────────────────────────────────────────────────────────────────┐
│                     CacheWarmer Service                         │
│                        (Node.js / TypeScript)                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────────────────────────────┐   │
│  │  REST API     │───▶│  Job Queue (BullMQ / Redis)          │   │
│  │  POST /warm   │    │                                      │   │
│  └──────────────┘    │  ┌─────────┐ ┌─────────┐ ┌────────┐ │   │
│                       │  │CDN Warm │ │Social   │ │Search  │ │   │
│  ┌──────────────┐    │  │Worker   │ │Cache    │ │Index   │ │   │
│  │  Cron / CLI   │───▶│  │(Puppeteer│ │Worker   │ │Worker  │ │   │
│  │  Scheduler    │    │  │)        │ │(FB,LI,X)│ │(IndexN)│ │   │
│  └──────────────┘    │  └─────────┘ └─────────┘ └────────┘ │   │
│                       └──────────────────────────────────────┘   │
│                                                                 │
│  ┌──────────────┐    ┌──────────────┐                           │
│  │  Dashboard    │    │  SQLite DB   │                           │
│  │  (Web UI)     │    │  (Status/Log)│                           │
│  └──────────────┘    └──────────────┘                           │
└─────────────────────────────────────────────────────────────────┘
```

**Tech-Stack:**
- **Runtime:** Node.js 20+ mit TypeScript
- **Web-Framework:** Fastify (leichtgewichtig, schnell)
- **Headless Browser:** Puppeteer mit Chromium
- **Job Queue:** BullMQ + Redis (für asynchrone, rate-limited Job-Verarbeitung)
- **Datenbank:** SQLite (via better-sqlite3) — kein externer DB-Server nötig
- **Deployment:** Docker Container auf dem Webspace

---

## 2. Kernmodule

### 2.1 Sitemap Parser

- Akzeptiert XML-Sitemap-URLs via REST API oder CLI
- Parst `<urlset>` und `<sitemapindex>` (rekursiv für Sitemap-Indizes)
- Extrahiert alle `<loc>` URLs mit optionaler `<lastmod>` / `<priority>` Info
- Validiert URLs und dedupliziert

**Bibliothek:** `fast-xml-parser` oder `sitemapper`

### 2.2 CDN Edge Cache Warming (Puppeteer)

- Öffnet jede URL in einem headless Chromium-Browser
- Wartet auf `networkidle0` oder `load` Event
- Simuliert einen realen User-Agent (Desktop + Mobile)
- Unterstützt konfigurierbare Concurrency (z.B. 3-5 parallele Tabs)
- Optional: Screenshot als Nachweis speichern

**Konfiguration:**
```yaml
cdnWarming:
  enabled: true
  concurrency: 3
  waitUntil: "networkidle0"
  timeout: 30000          # ms
  userAgent: "Mozilla/5.0 (compatible; CacheWarmer/1.0)"
  viewports:
    - { width: 1920, height: 1080 }  # Desktop
    - { width: 375, height: 812 }    # Mobile
```

### 2.3 Facebook Sharing Debugger

- Nutzt die Facebook Graph API zum Scrapen/Cachen von OG-Tags
- Endpoint: `POST https://graph.facebook.com/v19.0/?scrape=true&id={URL}`
- Benötigt einen gültigen **Facebook App Access Token** (`app_id|app_secret`)
- Rate-Limit: max. 10 Requests/Sekunde (automatisches Throttling)

**Konfiguration:**
```yaml
facebook:
  enabled: true
  appId: "YOUR_FB_APP_ID"
  appSecret: "YOUR_FB_APP_SECRET"
  rateLimitPerSecond: 10
```

### 2.4 LinkedIn Post Inspector

- LinkedIn bietet keine offizielle API zum Invalidieren/Aufwärmen des Caches
- **Ansatz A (bevorzugt):** LinkedIn Post Inspector URL programmatisch via Puppeteer aufrufen:
  `https://www.linkedin.com/post-inspector/inspect/{encoded_url}`
  - Erfordert LinkedIn-Login (Session Cookie oder OAuth)
  - Puppeteer navigiert zur Inspector-Seite, gibt URL ein, klickt "Inspect"
- **Ansatz B (Fallback):** LinkedIn Share API aufrufen, was ebenfalls ein Scraping triggert

**Konfiguration:**
```yaml
linkedin:
  enabled: true
  sessionCookie: "YOUR_LI_AT_COOKIE"    # li_at Cookie
  concurrency: 1                         # konservativ wegen Rate-Limits
  delayBetweenRequests: 5000             # ms
```

### 2.5 Twitter/X Cache Warming (Tweet Composer)

- Nutzt den **Tweet Composer** um das Card-Scraping zu triggern
- Puppeteer öffnet `https://twitter.com/intent/tweet?url={encoded_url}` für jede URL
- Beim Laden der Composer-Seite ruft Twitter automatisch die OG-/Twitter-Card-Meta-Tags ab und cached sie
- Kein Twitter API-Key nötig — funktioniert rein über den öffentlichen Composer-Endpoint

**Konfiguration:**
```yaml
twitter:
  enabled: true
  concurrency: 2
  delayBetweenRequests: 3000  # ms — konservativ wegen Rate-Limits
  timeout: 15000              # ms
```

### 2.6 Suchmaschinen-Indexierung

#### 2.6.1 IndexNow (Bing, Yandex, Seznam, Naver u.a.)

- Einfacher HTTP POST an `https://api.indexnow.org/indexnow`
- Batch-Submission von bis zu 10.000 URLs pro Request
- Benötigt einen IndexNow-Key (wird als Textdatei auf der Website gehostet)

```json
POST https://api.indexnow.org/indexnow
{
  "host": "www.example.com",
  "key": "YOUR_INDEXNOW_KEY",
  "keyLocation": "https://www.example.com/YOUR_INDEXNOW_KEY.txt",
  "urlList": [
    "https://www.example.com/page1",
    "https://www.example.com/page2"
  ]
}
```

#### 2.6.2 Google Search Console (Indexing API)

- Nutzt die **Google Indexing API** (`https://indexing.googleapis.com/v3/urlNotifications:publish`)
- Erfordert ein **Google Service Account** mit Zugriff auf die Search Console Property
- Rate-Limit: 200 Requests/Tag pro Property
- Typ: `URL_UPDATED` oder `URL_DELETED`

**Konfiguration:**
```yaml
google:
  enabled: true
  serviceAccountKeyFile: "./credentials/google-sa-key.json"
  dailyQuota: 200
```

#### 2.6.3 Bing Webmaster Tools (URL Submission API)

- Zusätzlich zu IndexNow: direkte Submission via Bing API
- `POST https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch?apikey={API_KEY}`
- Tägliches Limit: 10.000 URLs (Standard), erweiterbar auf 100.000+

**Konfiguration:**
```yaml
bing:
  enabled: true
  apiKey: "YOUR_BING_WEBMASTER_API_KEY"
  dailyQuota: 10000
```

### 2.7 CDN Cache Purge + Warm (Enterprise)

Direkte Cache-Invalidierung über die APIs der CDN/WAF-Anbieter, ergänzend zum Puppeteer-basierten Edge-Warming. Unterstützt **Cloudflare**, **Imperva (Incapsula)** und **Akamai**.

#### 2.7.1 Cloudflare

- Nutzt die Cloudflare API v4 zum gezielten Purgen einzelner URLs
- Endpoint: `POST https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache`
- Batch-Verarbeitung: bis zu 30 URLs pro Request
- Authentifizierung: Bearer Token (API Token mit `Zone:Cache Purge` Berechtigung)

**Konfiguration:**
```yaml
cloudflare:
  enabled: true
  apiToken: "YOUR_CLOUDFLARE_API_TOKEN"
  zoneId: "YOUR_ZONE_ID"
```

#### 2.7.2 Imperva (Incapsula)

- Nutzt die Imperva Cloud WAF API v1 zum Purgen des Site-Caches
- Endpoint: `POST https://my.incapsula.com/api/prov/v1/sites/performance/purge`
- Unterstützt Purge per URL-Pattern (`purge_pattern`) oder vollständigen Site-Purge
- Authentifizierung: `api_id` + `api_key` im Request-Body
- Purge-Zeiten typischerweise < 500ms über das gesamte Imperva-Netzwerk

**Konfiguration:**
```yaml
imperva:
  enabled: true
  apiId: "YOUR_IMPERVA_API_ID"
  apiKey: "YOUR_IMPERVA_API_KEY"
  siteId: "YOUR_SITE_ID"
```

#### 2.7.3 Akamai (Fast Purge API v3)

- Nutzt die Akamai Fast Purge API v3 für URL-basierte Cache-Invalidierung
- Endpoint: `POST https://{host}/ccu/v3/invalidate/url/{network}`
- Batch-Verarbeitung: bis zu 50 URLs pro Request
- Invalidierung in < 5 Sekunden über das gesamte Akamai-Netzwerk
- Authentifizierung: EdgeGrid (EG1-HMAC-SHA256) mit `client_token`, `client_secret`, `access_token`
- Unterstützt `production` und `staging` Networks

**Konfiguration:**
```yaml
akamai:
  enabled: true
  host: "akaa-xxxxx.luna.akamaiapis.net"
  clientToken: "YOUR_CLIENT_TOKEN"
  clientSecret: "YOUR_CLIENT_SECRET"
  accessToken: "YOUR_ACCESS_TOKEN"
  network: "production"    # production | staging
```

**Wichtig:** CDN-Purge erhöht kurzfristig die Origin-Last, da ungecachte Requests zum Origin durchschlagen. Bei großen Purge-Batches daher empfohlen, das Puppeteer-Warming direkt im Anschluss auszuführen (`targets: ["cdn-purge", "cdn"]`).

---

## 3. REST API Endpunkte

| Methode | Pfad | Beschreibung |
|---------|------|--------------|
| `POST` | `/api/warm` | Neue Sitemap zum Aufwärmen einreichen |
| `GET` | `/api/jobs` | Alle laufenden/abgeschlossenen Jobs auflisten |
| `GET` | `/api/jobs/:id` | Status eines einzelnen Jobs abrufen |
| `DELETE` | `/api/jobs/:id` | Einen Job abbrechen |
| `GET` | `/api/sitemaps` | Registrierte Sitemaps anzeigen |
| `POST` | `/api/sitemaps` | Sitemap registrieren (für wiederkehrendes Warming) |
| `DELETE` | `/api/sitemaps/:id` | Sitemap-Registrierung entfernen |
| `GET` | `/api/status` | Health-Check & Systemstatus |
| `GET` | `/api/logs` | Warming-Protokolle abrufen |

### Beispiel: Warming starten

```bash
curl -X POST http://localhost:3000/api/warm \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "sitemapUrl": "https://www.example.com/sitemap.xml",
    "targets": ["cdn", "facebook", "linkedin", "twitter", "google", "bing"],
    "priority": "normal"
  }'
```

### Beispiel: Antwort

```json
{
  "jobId": "warm-abc123",
  "status": "queued",
  "urlCount": 42,
  "targets": ["cdn", "facebook", "linkedin", "twitter", "google", "bing"],
  "createdAt": "2026-02-25T12:00:00Z"
}
```

---

## 4. Datenmodell (SQLite)

### Tabelle: `sitemaps`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | TEXT (UUID) | Primärschlüssel |
| url | TEXT | Sitemap-URL |
| domain | TEXT | Extrahierte Domain |
| cron_expression | TEXT | Cron-Ausdruck für wiederkehrendes Warming (optional) |
| created_at | DATETIME | Erstellungszeitpunkt |
| last_warmed_at | DATETIME | Letzter Warming-Durchlauf |

### Tabelle: `jobs`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | TEXT (UUID) | Primärschlüssel |
| sitemap_id | TEXT (FK) | Verweis auf Sitemap |
| status | TEXT | `queued` / `running` / `completed` / `failed` |
| total_urls | INTEGER | Gesamtzahl URLs |
| processed_urls | INTEGER | Bereits verarbeitete URLs |
| targets | TEXT (JSON) | Aktivierte Warming-Ziele |
| started_at | DATETIME | Startzeitpunkt |
| completed_at | DATETIME | Endzeitpunkt |
| error | TEXT | Fehlermeldung (optional) |

### Tabelle: `url_results`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| id | TEXT (UUID) | Primärschlüssel |
| job_id | TEXT (FK) | Verweis auf Job |
| url | TEXT | Die aufgewärmte URL |
| target | TEXT | `cdn` / `facebook` / `linkedin` / `twitter` / `google` / `bing` |
| status | TEXT | `success` / `failed` / `skipped` |
| http_status | INTEGER | HTTP-Statuscode (wenn relevant) |
| duration_ms | INTEGER | Dauer in Millisekunden |
| error | TEXT | Fehlermeldung (optional) |
| created_at | DATETIME | Zeitstempel |

---

## 5. Konfigurationsdatei

Zentrale Konfiguration via `config.yaml` im Projektroot:

```yaml
server:
  port: 3000
  host: "0.0.0.0"
  apiKey: "YOUR_SECRET_API_KEY"

redis:
  host: "localhost"
  port: 6379

database:
  path: "./data/cachewarmer.db"

puppeteer:
  executablePath: "/usr/bin/chromium-browser"
  headless: true
  args:
    - "--no-sandbox"
    - "--disable-setuid-sandbox"
    - "--disable-dev-shm-usage"

cdnWarming:
  enabled: true
  concurrency: 3
  waitUntil: "networkidle0"
  timeout: 30000
  userAgents:
    desktop: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    mobile: "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15"

facebook:
  enabled: true
  appId: ""
  appSecret: ""
  rateLimitPerSecond: 10

linkedin:
  enabled: true
  sessionCookie: ""
  concurrency: 1
  delayBetweenRequests: 5000

twitter:
  enabled: true
  method: "composer"
  concurrency: 2
  delayBetweenRequests: 3000

google:
  enabled: true
  serviceAccountKeyFile: "./credentials/google-sa-key.json"
  dailyQuota: 200

bing:
  enabled: true
  apiKey: ""
  dailyQuota: 10000

indexNow:
  enabled: true
  key: ""
  keyLocation: ""

cloudflare:
  enabled: false
  apiToken: ""                # Cloudflare API Token (Zone:Cache Purge permission)
  zoneId: ""                  # Cloudflare Zone ID

imperva:
  enabled: false
  apiId: ""                   # Imperva API ID
  apiKey: ""                  # Imperva API Key
  siteId: ""                  # Imperva Site ID (numeric)

akamai:
  enabled: false
  host: ""                    # e.g. akaa-xxxxx.luna.akamaiapis.net
  clientToken: ""             # EdgeGrid client_token
  clientSecret: ""            # EdgeGrid client_secret
  accessToken: ""             # EdgeGrid access_token
  network: "production"       # production | staging

scheduler:
  enabled: false
  defaultCron: "0 3 * * *"    # Standard: täglich um 03:00 Uhr

logging:
  level: "info"               # debug | info | warn | error
  file: "./data/cachewarmer.log"
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

# Cloudflare (Enterprise)
CLOUDFLARE_API_TOKEN=your-cloudflare-api-token
CLOUDFLARE_ZONE_ID=your-zone-id

# Imperva / Incapsula (Enterprise)
IMPERVA_API_ID=your-imperva-api-id
IMPERVA_API_KEY=your-imperva-api-key
IMPERVA_SITE_ID=your-imperva-site-id

# Akamai (Enterprise)
AKAMAI_HOST=akaa-xxxxx.luna.akamaiapis.net
AKAMAI_CLIENT_TOKEN=your-client-token
AKAMAI_CLIENT_SECRET=your-client-secret
AKAMAI_ACCESS_TOKEN=your-access-token

# Rate Limiting
RATE_LIMIT_CDN_PER_SECOND=2
RATE_LIMIT_FACEBOOK_PER_HOUR=50
RATE_LIMIT_INDEXNOW_BATCH_SIZE=100

# Lizenzierung (siehe Abschnitt 15)
LICENSE_KEY=CW-PRO-A1B2C3D4E5F6G7H8
LICENSE_DASHBOARD_URL=https://cachewarmer.drossmedia.de
```

---

## 8. Projektstruktur

```
cachewarmer/
├── CLAUDE.md                    # Dieses Dokument (Konzept & Entwicklungsnotizen)
├── package.json
├── tsconfig.json
├── Dockerfile
├── docker-compose.yml
├── config.yaml                  # Hauptkonfiguration
├── src/
│   ├── index.ts                 # Einstiegspunkt
│   ├── server.ts                # Fastify Server Setup
│   ├── config.ts                # Konfigurationsloader
│   ├── db/
│   │   ├── database.ts          # SQLite-Verbindung & Migrationen
│   │   └── migrations/          # SQL-Migrationsdateien
│   ├── api/
│   │   ├── routes.ts            # API-Routen
│   │   ├── warm.controller.ts   # Warming-Endpoints
│   │   ├── jobs.controller.ts   # Job-Verwaltung
│   │   └── sitemaps.controller.ts
│   ├── services/
│   │   ├── sitemap-parser.ts    # XML-Sitemap parsen
│   │   ├── cdn-warmer.ts        # Puppeteer CDN-Warming
│   │   ├── cdn-purge-warm.ts   # CDN Cache Purge (Cloudflare, Imperva, Akamai)
│   │   ├── facebook-warmer.ts   # Facebook Debugger API
│   │   ├── linkedin-warmer.ts   # LinkedIn Post Inspector
│   │   ├── twitter-warmer.ts    # Twitter/X Card Validator
│   │   ├── google-indexer.ts    # Google Indexing API
│   │   ├── bing-indexer.ts      # Bing Webmaster API
│   │   └── indexnow.ts          # IndexNow Protokoll
│   ├── queue/
│   │   ├── queue.ts             # BullMQ Queue Setup
│   │   └── workers/
│   │       ├── cdn.worker.ts
│   │       ├── social.worker.ts
│   │       └── search.worker.ts
│   ├── scheduler/
│   │   └── cron.ts              # Cron-basiertes Scheduling
│   └── utils/
│       ├── logger.ts            # Logging (pino)
│       ├── browser-pool.ts      # Puppeteer-Instanz-Management
│       ├── rate-limiter.ts      # Rate-Limiting Utility
│       └── retry.ts             # Retry-Logik mit Backoff
├── public/
│   ├── index.html               # Dashboard SPA
│   ├── app.js
│   └── style.css
├── credentials/                 # Git-ignoriert
│   └── google-sa-key.json
├── data/                        # Git-ignoriert
│   ├── cachewarmer.db           # SQLite-Datenbank
│   ├── cachewarmer.log
│   └── .instance-id             # Persistente UUID für Lizenz-Fingerprint
├── src/
│   └── license/
│       ├── client.js            # Lizenz-Aktivierung & Heartbeat
│       ├── fingerprint.js       # Installations-Fingerprint (SHA-256)
│       └── feature-gate.js      # Feature-Gating Middleware
├── LASTENHEFT-LICENSE-DASHBOARD.md  # Lastenheft License Dashboard
├── cachewarmer-license-manager/  # WordPress License Manager Plugin
└── tests/
    ├── sitemap-parser.test.ts
    ├── cdn-warmer.test.ts
    └── ...
```

---

## 7. Docker Deployment

### Dockerfile

```dockerfile
FROM node:20-slim

RUN apt-get update && apt-get install -y \
    chromium \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

WORKDIR /app
COPY package*.json ./
RUN npm ci --production
COPY dist/ ./dist/
COPY config.yaml ./

EXPOSE 3000
CMD ["node", "dist/index.js"]
```

### docker-compose.yml

```yaml
version: "3.8"
services:
  cachewarmer:
    build: .
    ports:
      - "3000:3000"
    volumes:
      - ./data:/app/data
      - ./credentials:/app/credentials:ro
      - ./config.yaml:/app/config.yaml:ro
    depends_on:
      - redis
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    volumes:
      - redis-data:/data
    restart: unless-stopped

volumes:
  redis-data:
```

---

## 8. Benötigte API-Zugänge & Credentials

| Dienst | Benötigt | Wie erhalten |
|--------|----------|--------------|
| **Facebook** | App ID + App Secret | [developers.facebook.com](https://developers.facebook.com) — App erstellen |
| **LinkedIn** | `li_at` Session Cookie | Aus Browser DevTools nach Login extrahieren |
| **Twitter/X** | API Key + Secret (optional) | [developer.twitter.com](https://developer.twitter.com) |
| **Google** | Service Account JSON | [Google Cloud Console](https://console.cloud.google.com) — Indexing API aktivieren |
| **Bing** | Webmaster API Key | [Bing Webmaster Tools](https://www.bing.com/webmasters) |
| **IndexNow** | API Key | Selbst generieren + als `.txt` auf der Website hosten |
| **Cloudflare** | API Token + Zone ID | [Cloudflare Dashboard](https://dash.cloudflare.com) — API Token mit Zone:Cache Purge Berechtigung |
| **Imperva** | API ID + API Key + Site ID | [Imperva Cloud Security Console](https://my.imperva.com) — Account Settings → API |
| **Akamai** | EdgeGrid Credentials (Host, Client Token, Client Secret, Access Token) | [Akamai Control Center](https://control.akamai.com) — Identity & Access → API Clients |

---

## 9. Implementierungsreihenfolge (Phasen)

### Phase 1 — Grundgerüst (MVP)
1. Projekt-Setup (TypeScript, Fastify, Docker)
2. Sitemap Parser (XML parsen, URLs extrahieren)
3. CDN Cache Warming via Puppeteer
4. REST API (`POST /api/warm`, `GET /api/jobs`)
5. SQLite Datenbank & Logging

### Phase 2 — Social Media Caches
6. Facebook Sharing Debugger Integration
7. LinkedIn Post Inspector Integration
8. Twitter/X Card Validator Integration

### Phase 3 — Suchmaschinen-Indexierung
9. IndexNow Integration (Bing, Yandex etc.)
10. Google Indexing API Integration
11. Bing Webmaster Tools API Integration

### Phase 4 — Automatisierung & Dashboard
12. Cron-basiertes Scheduling
13. Web-Dashboard (Statusübersicht, Logs, manuelle Trigger)
14. Webhook-Notifications (optional, z.B. bei Fehlern)

---

## 10. Entwicklungshinweise

- **Rate-Limiting ist kritisch:** Alle externen APIs haben Limits. Jeder Worker muss diese respektieren.
- **Fehlertoleranz:** Einzelne URL-Fehler dürfen nicht den gesamten Job abbrechen. Fehler loggen und weitermachen.
- **Idempotenz:** Ein erneutes Warming derselben URLs sollte keine Probleme verursachen.
- **Sicherheit:** API-Key-Auth für alle Endpoints. Credentials niemals im Git. HTTPS erzwingen. Optional: IP-Whitelist.
- **Monitoring:** Structured Logging mit Pino. Metriken über die `/api/status` Route.
- **Input-Validierung:** Nur gültige URLs/Sitemaps akzeptieren.

---

## 13. Erweiterungsideen (Phase 2)

- **Scheduling**: Automatische Runs per Cron (täglich/wöchentlich)
- **Webhooks**: Benachrichtigung bei Completion (Slack, E-Mail)
- **Diff-Detection**: Nur geänderte URLs warmen (basierend auf `lastmod`)
- **Multi-Tenant**: Mehrere Nutzer/Projekte
- **Lighthouse Audit**: Performance-Score pro URL mitspeichern
- **Screenshot-Archiv**: Vor/Nach-Vergleich
- **Pinterest Rich Pin Validator**: Zusätzlicher Social-Cache
- **CDN Cache Purge + Warm**: Direkte Cache-Purge via Cloudflare, Imperva (Incapsula) und Akamai APIs

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
| Dashboard URL | `https://cachewarmer.drossmedia.de` |
| WordPress Plugin | `cachewarmer-license-manager` (CWLM) |
| API Namespace | `cwlm/v1` |
| Datenbank | MySQL (7 Tabellen, Prefix `wp_cwlm_`) |
| Payment | Stripe (native Webhook-Integration) |

### 15.2 Produkt-Tiers

#### Warming-Targets

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| CDN Edge Cache (Desktop + Mobile) | ✓ | ✓ | ✓ |
| IndexNow (Bing, Yandex, Seznam, Naver) | ✓ | ✓ | ✓ |
| Facebook Sharing Debugger | – | ✓ | ✓ |
| LinkedIn Post Inspector | – | ✓ | ✓ |
| Twitter/X Card Validator | – | ✓ | ✓ |
| Google Indexing API | – | ✓ | ✓ |
| Bing Webmaster URL Submission | – | ✓ | ✓ |
| Pinterest Rich Pin Validator | – | ✓ | ✓ |
| Cloudflare Cache Purge + Warm | – | – | ✓ |
| Imperva (Incapsula) Cache Purge + Warm | – | – | ✓ |
| Akamai Fast Purge + Warm | – | – | ✓ |

#### Mengen-Limits

| Limit | Free | Premium | Enterprise |
|-------|:----:|:-------:|:----------:|
| URLs pro Warming-Job | 50 | 10.000 | Unbegrenzt |
| Registrierte Sitemaps | 2 | 25 | Unbegrenzt |
| Externe Sitemaps | 1 | 10 | Unbegrenzt |
| Jobs pro Tag | 3 | 50 | Unbegrenzt |
| CDN Concurrency | 2 | 10 | 20 |
| Log-Aufbewahrung | 7 Tage | 90 Tage | 365 Tage |
| Verwaltete Sites | 1 | 1 | Unbegrenzt |

#### Scheduling & Automatisierung

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Manuelles Warming | ✓ | ✓ | ✓ |
| Geplantes Warming (Scheduler) | – | ✓ | ✓ |
| Auto-Warm bei Veröffentlichung | – | ✓ | ✓ |
| Smart Warming (Diff-Detection) | – | ✓ | ✓ |
| Prioritätsbasiertes URL-Warming | – | ✓ | ✓ |
| Sitemap-Änderungsüberwachung | – | – | ✓ |
| Bedingtes Warming | – | – | ✓ |
| Benutzerdefinierte Warm-Reihenfolge | – | – | ✓ |

#### Dashboard & Reporting

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Status-Dashboard | ✓ | ✓ | ✓ |
| CSV/JSON-Export | – | ✓ | ✓ |
| Export fehlgeschlagener URLs (CSV) | – | ✓ | ✓ |
| Cache Hit/Miss Analyse | – | ✓ | ✓ |
| Performance-Trending | – | ✓ | ✓ |
| Quota-Nutzungs-Tracker | – | ✓ | ✓ |
| Automatische PDF/HTML-Reports | – | – | ✓ |
| Audit-Log | – | – | ✓ |

#### Monitoring & Alerting

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Broken-Link-Erkennung | – | ✓ | ✓ |
| SSL-Zertifikat-Ablauf-Warnung | – | ✓ | ✓ |
| Performance-Regressions-Alerts | – | – | ✓ |
| Quota-Erschöpfungs-Alerts | – | – | ✓ |

#### Konfiguration & Customization

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Custom Timeout pro Service | – | ✓ | ✓ |
| Custom User-Agent | – | – | ✓ |
| Custom HTTP-Headers | – | – | ✓ |
| Custom Viewports | – | – | ✓ |
| Authentifiziertes Warming | – | – | ✓ |

#### API & Integration

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| REST API Zugang | – | ✓ | ✓ |
| Webhook-Benachrichtigungen | – | – | ✓ |
| Zapier/n8n/Make Kompatibilität | – | – | ✓ |
| IP-Whitelist für API | – | – | ✓ |

#### Multi-Site & Agentur

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| Single-Site | ✓ | ✓ | – |
| Multi-Site-Verwaltung | – | – | ✓ |
| White-Label | – | – | ✓ |
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
| `cachewarmer-license-manager/` | WordPress License Manager Plugin (Schema, Endpoints, Stripe, Admin UI) |
