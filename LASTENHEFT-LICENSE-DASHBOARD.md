# Lastenheft: License Dashboard & Management System

## CacheWarmer – Zentrales Lizenzverwaltungssystem

**Projekt:** CacheWarmer License Management Dashboard
**Version:** 1.0
**Datum:** 28.02.2026
**Autor:** Alexander Dross
**Status:** Entwurf

---

## Inhaltsverzeichnis

1. [Projektbeschreibung](#1-projektbeschreibung)
2. [Zielsetzung](#2-zielsetzung)
3. [Systemarchitektur](#3-systemarchitektur)
4. [Produktdefinition & Lizenzmodelle](#4-produktdefinition--lizenzmodelle)
5. [Plattform-Support](#5-plattform-support)
6. [Datenmodell (MySQL)](#6-datenmodell-mysql)
7. [Public License API](#7-public-license-api)
8. [Admin Dashboard (WordPress)](#8-admin-dashboard-wordpress)
9. [Stripe Payment Integration](#9-stripe-payment-integration)
10. [Sicherheit](#10-sicherheit)
11. [DSGVO / Datenschutz](#11-dsgvo--datenschutz)
12. [Rate Limiting](#12-rate-limiting)
13. [Nicht-funktionale Anforderungen](#13-nicht-funktionale-anforderungen)
14. [Implementierungsplan](#14-implementierungsplan)

---

## 1. Projektbeschreibung

### 1.1 Ausgangslage

Der CacheWarmer ist ein Microservice zur automatisierten Cache-Erwärmung von Websites. Er ruft URLs per Headless Chrome auf (CDN/Edge-Cache), aktualisiert Social-Media-Caches (Facebook, LinkedIn, Twitter/X) und reicht URLs bei Suchmaschinen ein (Google, Bing via IndexNow).

Der CacheWarmer wird als kommerzielle Software auf vier Plattformen vertrieben:
- **Node.js** (Standalone Microservice)
- **Docker** (Containerisierte Variante)
- **WordPress** (Plugin)
- **Drupal** (Modul)

### 1.2 Problemstellung

Aktuell existiert keine zentrale Lizenzverwaltung. Um den kommerziellen Vertrieb zu ermöglichen, wird ein zentrales License Management Dashboard benötigt, das:
- Lizenzschlüssel erstellt, aktiviert, deaktiviert und verwaltet
- Zahlungen über Stripe automatisiert abwickelt
- Installationen überwacht und geografisch erfasst
- Feature-Zugriff je nach Lizenzstufe kontrolliert

### 1.3 Dashboard-URL

```
https://dashboard.cachewarmer.drossmedia.de
```

### 1.4 Technische Basis

Das Dashboard wird als **WordPress-Plugin** (`cachewarmer-license-manager`) auf einer eigenständigen WordPress-Installation betrieben.

---

## 2. Zielsetzung

| Nr. | Ziel | Priorität |
|-----|------|-----------|
| Z1 | Zentrale Lizenzverwaltung (Erstellen, Aktivieren, Deaktivieren, Verlängern, Sperren) | Muss |
| Z2 | Öffentliche REST-API zur Lizenzvalidierung für alle CacheWarmer-Installationen | Muss |
| Z3 | Installations-Tracking mit Geolocation (IP-basiert) | Muss |
| Z4 | Admin-Dashboard mit KPIs, Statistiken und Audit-Logs | Muss |
| Z5 | Stripe Payment Integration mit automatischer Lizenzgenerierung | Muss |
| Z6 | Plattformübergreifende Lizenzvalidierung (Node.js, Docker, WordPress, Drupal) | Muss |
| Z7 | Feature-Flags je Lizenzstufe (Free/Professional/Enterprise) | Muss |
| Z8 | DSGVO-konforme Datenverarbeitung | Muss |

---

## 3. Systemarchitektur

Das System besteht aus vier Hauptkomponenten:

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Stripe Payment                              │
│                    (Webhooks & Checkout)                             │
└───────────────────────────┬─────────────────────────────────────────┘
                            │ Webhook Events
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│           WordPress Dashboard (License Manager Plugin)              │
│           dashboard.cachewarmer.drossmedia.de                       │
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────────────────┐  │
│  │ Admin UI     │  │ REST API     │  │ Stripe Webhook Handler    │  │
│  │ (Dashboard,  │  │ /cwlm/v1/   │  │ (Signaturprüfung,         │  │
│  │  Lizenzen,   │  │              │  │  Auto-Lizenzgenerierung)  │  │
│  │  Statistik)  │  │              │  │                           │  │
│  └──────┬───────┘  └──────┬───────┘  └───────────┬───────────────┘  │
│         │                 │                       │                  │
│         └─────────────────┼───────────────────────┘                  │
│                           ▼                                          │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │              MySQL Datenbank (7 Tabellen)                      │  │
│  │   wp_cwlm_licenses | wp_cwlm_installations | wp_cwlm_geo_data │  │
│  │   wp_cwlm_audit_logs | wp_cwlm_stripe_events | ...            │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │              MaxMind GeoLite2-City (Geolocation)               │  │
│  └────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
          │                    │                     │
          │ REST API           │ REST API             │ REST API
          ▼                    ▼                     ▼
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────────────┐
│  CacheWarmer     │ │  CacheWarmer     │ │  CacheWarmer             │
│  Node.js /       │ │  WordPress       │ │  Drupal                  │
│  Docker          │ │  Plugin          │ │  Modul                   │
│                  │ │                  │ │                          │
│  Fingerprint:    │ │  Fingerprint:    │ │  Fingerprint:            │
│  Hostname+MAC+OS │ │  Domain+WP-Ver.  │ │  Domain+Drupal-Ver.      │
└──────────────────┘ └──────────────────┘ └──────────────────────────┘
```

### Kommunikationsfluss

1. CacheWarmer-Installation sendet Lizenzschlüssel + Fingerprint an Dashboard-API
2. API validiert Schlüssel, prüft Installationslimit, loggt Aktivierung
3. Erfolgreiche Aktivierung: JWT-Token zurück + Feature-Flags
4. Periodischer Heartbeat (alle 24h) zur Statusprüfung
5. Stripe Webhooks erstellen/verlängern Lizenzen automatisch

---

## 4. Produktdefinition & Lizenzmodelle

### 4.1 Produkt: CacheWarmer

Ein selbst-gehosteter Microservice / Plugin / Modul zur automatisierten Cache-Erwärmung von Websites über XML-Sitemaps.

### 4.2 Lizenz-Tiers

#### Free (Kostenlos)

| Feature | Verfügbar |
|---------|-----------|
| CDN Cache Warming (HTTP-Request, kein Puppeteer) | Ja |
| Sitemaps | 1 |
| Max. URLs | 50 |
| Parallele Worker | 1 |
| Social Media Cache (Facebook, LinkedIn, Twitter/X) | Nein |
| Search Engine Indexing (IndexNow, Google, Bing) | Nein |
| Scheduling / Cron | Nein |
| SSE Live-Progress | Ja |
| API-Key Auth | Ja |
| Priority Support | Nein |

#### Professional (Kostenpflichtig)

| Feature | Verfügbar |
|---------|-----------|
| CDN Cache Warming (Puppeteer, Full Rendering) | Ja |
| Sitemaps | 5 |
| Max. URLs | 5.000 |
| Parallele Worker | 5 |
| Facebook Sharing Debugger | Ja |
| LinkedIn Post Inspector | Ja |
| Twitter/X Card Cache Refresh | Ja |
| IndexNow Submission | Ja |
| Google Search Console API | Nein |
| Bing Webmaster Tools API | Nein |
| Scheduling / Cron | Ja |
| SSE Live-Progress | Ja |
| Diff-Detection (nur geänderte URLs) | Ja |
| Priority Support | Nein |

**Sub-Pläne:**
- **Starter** – 1 Installation, 1 Sitemap, 1.000 URLs
- **Professional** – 3 Installationen, 5 Sitemaps, 5.000 URLs
- **Agency** – 10 Installationen, unbegrenzte Sitemaps, 5.000 URLs pro Sitemap

#### Enterprise (Kostenpflichtig)

| Feature | Verfügbar |
|---------|-----------|
| Alle Professional Features | Ja |
| Google Search Console API | Ja |
| Bing Webmaster Tools API | Ja |
| Sitemaps | Unbegrenzt |
| Max. URLs | Unbegrenzt |
| Parallele Worker | 10+ (konfigurierbar) |
| Multi-Site Management | Ja |
| Screenshot-Archiv (Vor/Nach-Vergleich) | Ja |
| Lighthouse Audit Integration | Ja |
| Webhooks (Slack, E-Mail bei Completion) | Ja |
| Cloudflare API Integration | Ja |
| White-Label Option | Ja |
| Priority Support (< 24h Antwortzeit) | Ja |

**Installationen:** Unbegrenzt (Fair-Use)

#### Development (Kostenlos)

| Eigenschaft | Wert |
|-------------|------|
| Alle Features | Ja (Enterprise-Umfang) |
| Gültige Domains | `localhost`, `*.local`, `*.dev`, `*.test`, `127.0.0.1` |
| Produktionseinsatz | Nein |
| Automatische Erkennung | Ja (anhand Hostname/Domain) |

### 4.3 License Key Format

```
CW-{TIER}-{HEX16}

Beispiele:
  CW-FREE-A1B2C3D4E5F6G7H8
  CW-PRO-9F8E7D6C5B4A3210
  CW-ENT-1234567890ABCDEF
  CW-DEV-FEDCBA0987654321
```

- **Prefix**: `CW` (CacheWarmer)
- **Tier**: `FREE`, `PRO`, `ENT`, `DEV`
- **Key**: 16 Zeichen Hexadezimal (kryptografisch sicher, `random_bytes(8)`)

### 4.4 Lizenz-Lifecycle

```
                    ┌─────────┐
                    │ INACTIVE │ ← Lizenz erstellt (nach Kauf / manuell)
                    └────┬────┘
                         │ Erste Aktivierung
                         ▼
                    ┌─────────┐
              ┌─────│  ACTIVE  │◄────────────────────┐
              │     └────┬────┘                      │
              │          │                           │
              │          │ Ablaufdatum erreicht      │ Verlängerung (Stripe)
              │          ▼                           │
              │     ┌──────────────┐                 │
              │     │ GRACE_PERIOD │─────────────────┘
              │     │  (14 Tage)   │
              │     └──────┬───────┘
              │            │ Keine Verlängerung
              │            ▼
              │     ┌─────────┐
              │     │ EXPIRED  │
              │     └─────────┘
              │
              │ Admin-Aktion / Missbrauch
              ▼
         ┌──────────┐
         │ REVOKED   │
         └──────────┘
```

**Status-Definitionen:**
- `inactive` – Lizenz erstellt, noch nicht aktiviert
- `active` – Lizenz aktiv, Features freigeschaltet
- `grace_period` – Ablaufdatum überschritten, 14 Tage Karenzzeit (volle Funktion)
- `expired` – Karenzzeit abgelaufen, Features gesperrt (nur Free-Funktionen)
- `revoked` – Administrativ gesperrt (Missbrauch, Rückbuchung)

---

## 5. Plattform-Support

### 5.1 Feature-Matrix nach Plattform

| Feature | Node.js Standalone | Docker | WordPress Plugin | Drupal Modul |
|---------|-------------------|--------|-----------------|--------------|
| CDN Warming (HTTP) | Ja | Ja | Ja | Ja |
| CDN Warming (Puppeteer) | Ja | Ja | Nein¹ | Nein¹ |
| Social Media Cache | Ja | Ja | Ja | Ja |
| Search Engine Indexing | Ja | Ja | Ja | Ja |
| Scheduling | Ja (node-cron) | Ja (cron) | Ja (WP-Cron) | Ja (Drupal Cron) |
| SSE Live-Progress | Ja | Ja | Ja (Admin-AJAX) | Ja |
| SQLite Datenbank | Ja | Ja | Nein (WP DB) | Nein (Drupal DB) |
| REST API | Ja (Express) | Ja (Express) | Ja (WP REST) | Ja (Drupal REST) |

¹ WordPress und Drupal nutzen HTTP-basiertes CDN Warming; für Puppeteer-Support wird die Node.js/Docker-Variante empfohlen.

### 5.2 Lizenzaktivierung je Plattform

| Plattform | Aktivierungsart | Fingerprint-Bestandteile |
|-----------|----------------|--------------------------|
| Node.js Standalone | `LICENSE_KEY` in `.env` + API-Call beim Start | Hostname + MAC-Hash + OS + UUID-Datei (`data/.instance-id`) |
| Docker | Environment Variable `LICENSE_KEY` + API-Call | Container-Host-UUID (Volume-mounted `data/.instance-id`) |
| WordPress Plugin | Admin-Seite → Lizenzschlüssel eingeben | Site-URL + WordPress-Version + Domain |
| Drupal Modul | Admin-Konfiguration → Lizenzschlüssel eingeben | Site-URL + Drupal-Version + Domain |

### 5.3 Versionsbezeichnungen

| Plattform | Paketname | Repository/Vertrieb |
|-----------|-----------|---------------------|
| Node.js | `@drossmedia/cachewarmer` | NPM (private) / GitHub Releases |
| Docker | `drossmedia/cachewarmer` | Docker Hub / GitHub Container Registry |
| WordPress | `cachewarmer` | wordpress.org (Free) / dashboard.cachewarmer.drossmedia.de (Pro/Ent) |
| Drupal | `cachewarmer` | drupal.org (Free) / dashboard.cachewarmer.drossmedia.de (Pro/Ent) |

---

## 6. Datenmodell (MySQL)

Alle Tabellen verwenden das Prefix `wp_cwlm_` (CacheWarmer License Manager).

### 6.1 Tabelle: `wp_cwlm_licenses`

Kern-Tabelle für alle Lizenzschlüssel.

```sql
CREATE TABLE wp_cwlm_licenses (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_key     VARCHAR(30) NOT NULL UNIQUE,
    customer_email  VARCHAR(255) NOT NULL,
    customer_name   VARCHAR(255) DEFAULT NULL,
    tier            ENUM('free','professional','enterprise','development') NOT NULL DEFAULT 'free',
    plan            VARCHAR(50) DEFAULT NULL,          -- starter, professional, agency, enterprise, dev
    status          ENUM('inactive','active','grace_period','expired','revoked') NOT NULL DEFAULT 'inactive',
    max_sites       INT UNSIGNED NOT NULL DEFAULT 1,
    active_sites    INT UNSIGNED NOT NULL DEFAULT 0,
    features_json   JSON DEFAULT NULL,                 -- Überschreibung der Tier-Defaults
    stripe_customer_id   VARCHAR(255) DEFAULT NULL,
    stripe_subscription_id VARCHAR(255) DEFAULT NULL,
    expires_at      DATETIME DEFAULT NULL,             -- NULL = unbegrenzt
    activated_at    DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes           TEXT DEFAULT NULL,

    INDEX idx_customer_email (customer_email),
    INDEX idx_tier (tier),
    INDEX idx_status (status),
    INDEX idx_stripe_customer (stripe_customer_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.2 Tabelle: `wp_cwlm_installations`

Tracking aktiver Installationen pro Lizenz.

```sql
CREATE TABLE wp_cwlm_installations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id      BIGINT UNSIGNED NOT NULL,
    domain          VARCHAR(255) DEFAULT NULL,          -- Für WP/Drupal
    hostname        VARCHAR(255) DEFAULT NULL,          -- Für Node.js/Docker
    fingerprint     VARCHAR(64) NOT NULL,               -- SHA-256 Hash
    platform        ENUM('nodejs','docker','wordpress','drupal') NOT NULL,
    platform_version VARCHAR(20) DEFAULT NULL,          -- z.B. '18.19.0', '6.4.2'
    cachewarmer_version VARCHAR(20) DEFAULT NULL,       -- z.B. '1.0.0'
    os_platform     VARCHAR(50) DEFAULT NULL,           -- z.B. 'linux', 'darwin'
    os_version      VARCHAR(50) DEFAULT NULL,
    ip_address      VARCHAR(45) DEFAULT NULL,           -- IPv4/IPv6 (anonymisiert)
    last_check      DATETIME DEFAULT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    activated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deactivated_at  DATETIME DEFAULT NULL,

    FOREIGN KEY (license_id) REFERENCES wp_cwlm_licenses(id) ON DELETE CASCADE,
    UNIQUE KEY uk_license_fingerprint (license_id, fingerprint),
    INDEX idx_domain (domain),
    INDEX idx_platform (platform),
    INDEX idx_last_check (last_check),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.3 Tabelle: `wp_cwlm_geo_data`

Geolocation-Daten der Installationen (MaxMind GeoLite2).

```sql
CREATE TABLE wp_cwlm_geo_data (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_id BIGINT UNSIGNED NOT NULL,
    country_code    CHAR(2) DEFAULT NULL,               -- ISO 3166-1 alpha-2
    country_name    VARCHAR(100) DEFAULT NULL,
    region          VARCHAR(100) DEFAULT NULL,
    city            VARCHAR(100) DEFAULT NULL,
    latitude        DECIMAL(10,7) DEFAULT NULL,
    longitude       DECIMAL(10,7) DEFAULT NULL,
    timezone        VARCHAR(50) DEFAULT NULL,
    isp             VARCHAR(255) DEFAULT NULL,
    fetched_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (installation_id) REFERENCES wp_cwlm_installations(id) ON DELETE CASCADE,
    INDEX idx_country (country_code),
    INDEX idx_fetched_at (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.4 Tabelle: `wp_cwlm_audit_logs`

Audit-Trail aller administrativen und API-Aktionen.

```sql
CREATE TABLE wp_cwlm_audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id      BIGINT UNSIGNED DEFAULT NULL,
    installation_id BIGINT UNSIGNED DEFAULT NULL,
    action          VARCHAR(50) NOT NULL,               -- z.B. 'license.created', 'license.activated'
    actor_type      ENUM('system','admin','api','stripe') NOT NULL,
    actor_id        VARCHAR(255) DEFAULT NULL,           -- WP User ID oder 'stripe_webhook'
    ip_address      VARCHAR(45) DEFAULT NULL,
    details_json    JSON DEFAULT NULL,                   -- Zusätzliche Kontextdaten
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_license (license_id),
    INDEX idx_action (action),
    INDEX idx_actor (actor_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.5 Tabelle: `wp_cwlm_stripe_events`

Protokollierung aller eingehenden Stripe Webhook Events.

```sql
CREATE TABLE wp_cwlm_stripe_events (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stripe_event_id VARCHAR(255) NOT NULL UNIQUE,       -- evt_xxx (Idempotenz)
    event_type      VARCHAR(100) NOT NULL,              -- z.B. 'checkout.session.completed'
    payload_json    JSON NOT NULL,
    processing_status ENUM('pending','processed','failed','ignored') NOT NULL DEFAULT 'pending',
    license_id      BIGINT UNSIGNED DEFAULT NULL,
    error_message   TEXT DEFAULT NULL,
    received_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at    DATETIME DEFAULT NULL,

    INDEX idx_event_type (event_type),
    INDEX idx_processing_status (processing_status),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.6 Tabelle: `wp_cwlm_stripe_product_map`

Zuordnung von Stripe-Produkten zu Lizenz-Tiers und Plänen.

```sql
CREATE TABLE wp_cwlm_stripe_product_map (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stripe_product_id VARCHAR(255) NOT NULL,
    stripe_price_id   VARCHAR(255) DEFAULT NULL,
    tier            ENUM('free','professional','enterprise') NOT NULL,
    plan            VARCHAR(50) NOT NULL,               -- starter, professional, agency, enterprise
    max_sites       INT UNSIGNED NOT NULL DEFAULT 1,
    duration_days   INT UNSIGNED DEFAULT 365,           -- Lizenzlaufzeit in Tagen
    description     VARCHAR(255) DEFAULT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_product_price (stripe_product_id, stripe_price_id),
    INDEX idx_tier (tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.7 Tabelle: `wp_cwlm_rate_limits`

Tracking für API Rate Limiting.

```sql
CREATE TABLE wp_cwlm_rate_limits (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address      VARCHAR(45) NOT NULL,
    endpoint        VARCHAR(100) NOT NULL,
    request_count   INT UNSIGNED NOT NULL DEFAULT 1,
    window_start    DATETIME NOT NULL,
    window_end      DATETIME NOT NULL,

    UNIQUE KEY uk_ip_endpoint_window (ip_address, endpoint, window_start),
    INDEX idx_window_end (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 7. Public License API

### 7.1 Base URL

```
https://dashboard.cachewarmer.drossmedia.de/wp-json/cwlm/v1/
```

### 7.2 Endpunkte

#### `GET /health`

Systemstatus prüfen.

**Response (200):**
```json
{
    "status": "ok",
    "version": "1.0.0",
    "timestamp": "2026-02-28T12:00:00Z"
}
```

#### `POST /validate`

Lizenz prüfen ohne Aktivierung. Für Pre-Check vor Kauf/Upgrade.

**Request:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "platform": "nodejs"
}
```

**Response (200):**
```json
{
    "valid": true,
    "tier": "professional",
    "plan": "professional",
    "status": "active",
    "expires_at": "2027-02-28T00:00:00Z",
    "max_sites": 3,
    "active_sites": 1,
    "features": {
        "cdn_warming": true,
        "cdn_puppeteer": true,
        "social_facebook": true,
        "social_linkedin": true,
        "social_twitter": true,
        "indexnow": true,
        "google_search_console": false,
        "bing_webmaster": false,
        "scheduling": true,
        "max_sitemaps": 5,
        "max_urls": 5000,
        "max_workers": 5,
        "diff_detection": true,
        "multi_site": false,
        "screenshots": false,
        "lighthouse": false,
        "webhooks": false,
        "cloudflare": false,
        "whitelabel": false
    }
}
```

#### `POST /activate`

Installation registrieren und Lizenz aktivieren.

**Request:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6...sha256hash",
    "platform": "nodejs",
    "platform_version": "20.11.0",
    "cachewarmer_version": "1.0.0",
    "domain": null,
    "hostname": "web-server-01",
    "os_platform": "linux",
    "os_version": "Ubuntu 22.04"
}
```

**Response (200):**
```json
{
    "activated": true,
    "installation_id": 42,
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "token_expires_at": "2026-03-30T12:00:00Z",
    "features": { ... },
    "next_check": "2026-03-01T12:00:00Z"
}
```

**Fehler-Responses:**
- `400` – Ungültige Parameter
- `403` – Lizenz gesperrt/abgelaufen
- `409` – Installationslimit erreicht
- `429` – Rate Limit überschritten

#### `POST /deactivate`

Installation freigeben (z.B. bei Server-Wechsel).

**Request:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6...sha256hash"
}
```

**Response (200):**
```json
{
    "deactivated": true,
    "active_sites": 0,
    "max_sites": 3
}
```

#### `POST /check`

Heartbeat – wird alle 24 Stunden von der Installation gesendet.

**Request:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6...sha256hash",
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "cachewarmer_version": "1.0.0"
}
```

**Response (200):**
```json
{
    "valid": true,
    "status": "active",
    "features": { ... },
    "next_check": "2026-03-02T12:00:00Z",
    "update_available": "1.1.0",
    "messages": []
}
```

### 7.3 Fehler-Format

Alle Fehler-Responses folgen einem einheitlichen Format:

```json
{
    "error": true,
    "code": "LICENSE_EXPIRED",
    "message": "Die Lizenz ist am 2026-01-15 abgelaufen.",
    "details": {}
}
```

**Fehlercodes:**
- `INVALID_KEY` – Lizenzschlüssel nicht gefunden
- `LICENSE_EXPIRED` – Lizenz abgelaufen (nach Grace Period)
- `LICENSE_REVOKED` – Lizenz administrativ gesperrt
- `SITE_LIMIT_REACHED` – Maximale Installationen erreicht
- `INVALID_FINGERPRINT` – Fingerprint stimmt nicht überein
- `INVALID_PLATFORM` – Ungültige Plattform
- `RATE_LIMITED` – Zu viele Anfragen
- `DEVELOPMENT_ONLY` – Dev-Lizenz auf Produktions-Domain
- `INTERNAL_ERROR` – Serverfehler

---

## 8. Admin Dashboard (WordPress)

### 8.1 Hauptmenü-Struktur

Das Plugin registriert ein eigenes Top-Level-Menü im WordPress-Admin:

```
CacheWarmer LM
├── Dashboard        → KPI-Übersicht, Diagramme
├── Lizenzen         → CRUD, Suche, Filter, Bulk-Aktionen
├── Installationen   → Aktive Instanzen, Plattform-Statistik
├── Audit Log        → Chronologisches Aktivitätsprotokoll
├── Stripe Events    → Webhook-Protokoll, Reprocessing
├── Produkte         → Stripe Produkt-Mapping
└── Einstellungen    → API-Keys, MaxMind, Stripe Config
```

### 8.2 Dashboard (KPI-Seite)

**KPI-Karten (oben):**
- Aktive Lizenzen (Gesamt)
- Aktive Installationen (Gesamt)
- Lizenzen in Grace Period
- Neue Lizenzen (letzte 30 Tage)

**Diagramme:**
- Lizenzen nach Tier (Kreisdiagramm)
- Installationen nach Plattform (Balkendiagramm: Node.js / Docker / WordPress / Drupal)
- Neue Aktivierungen über Zeit (Liniendiagramm, 12 Monate)
- Geografische Verteilung (Weltkarte)

**Tabellen:**
- Letzte 10 Aktivierungen
- Letzte 10 Audit-Log-Einträge
- Lizenzen die bald ablaufen (nächste 30 Tage)

### 8.3 Lizenzverwaltung

**Funktionen:**
- Lizenz erstellen (manuell oder via Stripe)
- Lizenz bearbeiten (Tier, Plan, Status, max_sites, Ablaufdatum)
- Lizenz sperren (revoke) mit Begründung
- Lizenz verlängern (manuell + Dauer)
- Bulk-Aktionen: Sperren, Verlängern, Exportieren (CSV)
- Suche nach Key, E-Mail, Domain, Status
- Filter nach Tier, Status, Plattform

**Detailansicht pro Lizenz:**
- Stammdaten (Key, Kunde, Tier, Status)
- Aktive Installationen (mit Deaktivieren-Button)
- Stripe-Verlauf (Zahlungen, Subscription-Status)
- Audit-Log (nur für diese Lizenz)
- Feature-Overrides (JSON-Editor)

### 8.4 Installationsübersicht

Pro Installation werden erfasst:
1. Domain / Hostname
2. Plattform (Node.js / Docker / WordPress / Drupal)
3. Plattform-Version
4. CacheWarmer-Version
5. Betriebssystem
6. IP-Adresse (anonymisiert)
7. Geolocation (Land, Stadt)
8. Letzter Heartbeat
9. Aktivierungsdatum

### 8.5 Einstellungen

| Einstellung | Beschreibung |
|-------------|-------------|
| Stripe Secret Key | `sk_live_...` oder `sk_test_...` |
| Stripe Webhook Secret | `whsec_...` für HMAC-Verifizierung |
| Stripe Publishable Key | Für Frontend-Checkout |
| JWT Secret | Geheimschlüssel für Token-Signierung |
| MaxMind License Key | Für GeoLite2-Datenbank-Download |
| MaxMind DB Path | Pfad zur `.mmdb` Datei |
| Grace Period (Tage) | Standard: 14 |
| Heartbeat Intervall (Std.) | Standard: 24 |
| Rate Limit (req/min) | Standard: 60 |
| Dev-Domains | Komma-separiert: `localhost,*.local,*.dev,*.test` |

---

## 9. Stripe Payment Integration

### 9.1 Unterstützte Webhook Events

| Event | Aktion |
|-------|--------|
| `checkout.session.completed` | Neue Lizenz erstellen, E-Mail senden |
| `invoice.payment_succeeded` | Lizenz verlängern |
| `invoice.payment_failed` | Grace Period starten, Warnung loggen |
| `customer.subscription.deleted` | Lizenz auf `expired` setzen |
| `customer.subscription.updated` | Plan-Änderung übernehmen (Up/Downgrade) |
| `charge.refunded` | Lizenz sperren (revoke) |
| `charge.dispute.created` | Lizenz sperren (revoke), Audit-Log |

### 9.2 Checkout-Flow

```
1. Kunde wählt Plan auf Website
2. Stripe Checkout Session wird erstellt (serverseitig)
3. Kunde zahlt über Stripe Checkout
4. Stripe sendet checkout.session.completed Webhook
5. Plugin empfängt Webhook:
   a. HMAC-SHA256 Signatur prüfen
   b. Event-Idempotenz prüfen (stripe_event_id)
   c. Stripe Product → Tier/Plan mappen (wp_cwlm_stripe_product_map)
   d. Lizenzschlüssel generieren (CW-{TIER}-{HEX16})
   e. Lizenz in DB speichern (Status: inactive)
   f. Audit-Log schreiben
   g. E-Mail mit Lizenzschlüssel an Kunden senden
6. Kunde aktiviert Lizenz in CacheWarmer-Installation
```

### 9.3 Subscription-Verlängerung

```
1. Stripe belastet Kunden automatisch (Recurring)
2. invoice.payment_succeeded Webhook
3. Plugin verlängert Ablaufdatum um duration_days
4. Falls Status = grace_period → zurück auf active
5. Audit-Log + optionale Benachrichtigung
```

### 9.4 Stripe Produkt-Mapping (Admin UI)

Über die Admin-Seite "Produkte" werden Stripe-Produkte den CacheWarmer-Tiers zugeordnet:

| Stripe Produkt | Stripe Preis | CW Tier | CW Plan | Max Sites | Laufzeit |
|----------------|-------------|---------|---------|-----------|----------|
| prod_CW_starter | price_monthly | professional | starter | 1 | 30 Tage |
| prod_CW_starter | price_yearly | professional | starter | 1 | 365 Tage |
| prod_CW_pro | price_monthly | professional | professional | 3 | 30 Tage |
| prod_CW_pro | price_yearly | professional | professional | 3 | 365 Tage |
| prod_CW_agency | price_monthly | professional | agency | 10 | 30 Tage |
| prod_CW_agency | price_yearly | professional | agency | 10 | 365 Tage |
| prod_CW_enterprise | price_monthly | enterprise | enterprise | 999 | 30 Tage |
| prod_CW_enterprise | price_yearly | enterprise | enterprise | 999 | 365 Tage |

---

## 10. Sicherheit

### 10.1 Transport

- **HTTPS erzwingen**: Alle API-Calls nur über TLS 1.2+
- HTTP-Requests werden mit `301` auf HTTPS umgeleitet

### 10.2 SQL Injection Prevention

- Alle Datenbankabfragen nutzen `$wpdb->prepare()` mit parametrisierten Queries
- Kein direktes Einsetzen von User-Input in SQL-Strings

### 10.3 Input Validation

- Lizenzschlüssel: Regex `/^CW-(FREE|PRO|ENT|DEV)-[A-F0-9]{16}$/`
- Fingerprint: Exakt 64 Zeichen hexadezimal (SHA-256)
- Platform: Enum-Validierung (`nodejs`, `docker`, `wordpress`, `drupal`)
- Domains: Filter mit `filter_var()` und URL-Validierung
- Alle JSON-Payloads werden gegen Schema validiert

### 10.4 Stripe Webhook Security

- HMAC-SHA256 Signaturprüfung für jeden Webhook
- Webhook-Secret wird in `wp-config.php` als Konstante gespeichert (nicht in DB)
- Event-Idempotenz über `stripe_event_id` (Duplikaterkennung)
- Replay-Schutz: Events älter als 5 Minuten werden abgelehnt

### 10.5 JWT Token

- Signiert mit HMAC-SHA256 und eigenem Secret
- Ablauf: 30 Tage (wird bei jedem Check erneuert)
- Payload: `license_id`, `installation_id`, `tier`, `features`, `exp`

### 10.6 Passwort & Key Management

- Lizenzschlüssel werden mit `bcrypt` gehasht in der DB gespeichert (Lookup über separaten Index)
- Stripe Keys als `wp-config.php` Konstanten (nie in der Datenbank)
- JWT Secret als `wp-config.php` Konstante

---

## 11. DSGVO / Datenschutz

### 11.1 Datenminimierung

Es werden nur technisch notwendige Daten erfasst:
- E-Mail-Adresse (für Lizenz-Zuordnung und Kommunikation)
- Domain/Hostname (für Installations-Tracking)
- IP-Adresse (für Geolocation, wird anonymisiert)
- Plattform- und Versionsinformationen (für Support und Kompatibilität)

**Nicht erfasst:**
- Endnutzer-Daten der CacheWarmer-Installation
- Inhalte der gecachten Websites
- Persönliche Nutzungsstatistiken

### 11.2 IP-Anonymisierung

- IP-Adressen werden **nach** der Geolocation-Abfrage anonymisiert
- IPv4: Letztes Oktett wird auf `0` gesetzt (`192.168.1.42` → `192.168.1.0`)
- IPv6: Letzte 80 Bits werden genullt
- Anonymisierte IP wird in `wp_cwlm_installations.ip_address` gespeichert

### 11.3 Aufbewahrungsfristen

| Daten | Aufbewahrung | Löschung |
|-------|-------------|----------|
| Lizenzdaten | Unbegrenzt (solange aktiv) | Auf Anfrage (Recht auf Löschung) |
| Installationsdaten | 24 Monate nach Deaktivierung | Automatischer Cronjob |
| Geolocation-Daten | 24 Monate | Automatischer Cronjob |
| Audit-Logs | 24 Monate | Automatischer Cronjob |
| Stripe Events | 24 Monate | Automatischer Cronjob |
| Rate-Limit-Daten | 1 Stunde | Automatische Bereinigung |

### 11.4 Recht auf Löschung

- Admin kann über Dashboard alle Daten eines Kunden (per E-Mail) löschen
- Löschung umfasst: Lizenz, Installationen, Geodaten, Audit-Logs
- Stripe-Daten müssen separat über Stripe Dashboard gelöscht werden
- Löschvorgang wird im Audit-Log dokumentiert (anonymisiert)

### 11.5 Geolocation

- MaxMind GeoLite2-City Datenbank wird **lokal** gespeichert
- Keine Übermittlung von IP-Adressen an externe Dienste
- Monatliches Update der GeoIP-Datenbank via Cronjob

---

## 12. Rate Limiting

### 12.1 Limits

| Endpoint | Limit | Zeitfenster |
|----------|-------|-------------|
| `GET /health` | 120 req | 1 Minute |
| `POST /validate` | 60 req | 1 Minute |
| `POST /activate` | 10 req | 1 Minute |
| `POST /deactivate` | 10 req | 1 Minute |
| `POST /check` | 30 req | 1 Minute |
| Stripe Webhook | Unbegrenzt | – |

### 12.2 Implementierung

- Rate Limiting über WordPress Transients API
- Key: `cwlm_rate_{ip_hash}_{endpoint}`
- Sliding Window Counter
- Bei Überschreitung: `429 Too Many Requests` mit `Retry-After` Header

### 12.3 Response bei Rate Limit

```json
{
    "error": true,
    "code": "RATE_LIMITED",
    "message": "Zu viele Anfragen. Bitte warten Sie 42 Sekunden.",
    "retry_after": 42
}
```

Header: `Retry-After: 42`

---

## 13. Nicht-funktionale Anforderungen

| Anforderung | Zielwert |
|-------------|----------|
| API Response Time (Validate/Check) | < 200ms (P95) |
| API Response Time (Activate) | < 500ms (P95) |
| Verfügbarkeit | 99.5% (monatlich) |
| Max. gleichzeitige API-Verbindungen | 100 |
| Datenbank-Größe (1 Jahr, 10.000 Lizenzen) | < 500 MB |
| WordPress-Version | >= 6.0 |
| PHP-Version | >= 8.0 |
| MySQL-Version | >= 5.7 / MariaDB >= 10.3 |
| MaxMind GeoLite2 Update-Intervall | Monatlich |
| Backup-Strategie | Tägliches DB-Backup (wp-config.php basiert) |

---

## 14. Implementierungsplan

### Phase 1: Foundation (Woche 1–2)
- [ ] WordPress-Plugin-Scaffolding (`cachewarmer-license-manager`)
- [ ] Datenbank-Schema erstellen (7 Tabellen, Migrations-System)
- [ ] Basis-Adminmenü und Einstellungsseite
- [ ] Konfigurationssystem (`wp-config.php` Konstanten)

### Phase 2: Core API (Woche 3–4)
- [ ] REST API Framework (Namespace `cwlm/v1`)
- [ ] `GET /health` Endpoint
- [ ] `POST /validate` Endpoint mit Lizenz-Lookup
- [ ] `POST /activate` Endpoint mit Fingerprint-Tracking
- [ ] `POST /deactivate` Endpoint
- [ ] `POST /check` Heartbeat-Endpoint
- [ ] Rate Limiting Middleware
- [ ] Fehlerbehandlung und einheitliches Response-Format

### Phase 3: Dashboard UI (Woche 5–7)
- [ ] KPI Dashboard mit Karten und Diagrammen
- [ ] Lizenzverwaltung (CRUD, Suche, Filter, Bulk)
- [ ] Installationsübersicht mit Plattform-Statistiken
- [ ] Audit-Log-Ansicht (filterbar, paginiert)
- [ ] Lizenz-Detailansicht mit allen verknüpften Daten

### Phase 4: Stripe Integration (Woche 8–9)
- [ ] Stripe Webhook Endpoint (`/cwlm/v1/stripe/webhook`)
- [ ] HMAC-SHA256 Signaturprüfung
- [ ] Event-Verarbeitung: `checkout.session.completed`
- [ ] Event-Verarbeitung: `invoice.payment_succeeded/failed`
- [ ] Event-Verarbeitung: `customer.subscription.deleted/updated`
- [ ] Event-Verarbeitung: `charge.refunded`, `charge.dispute.created`
- [ ] Stripe Produkt-Mapping UI
- [ ] Stripe Events Admin-Seite (Log + Reprocessing)
- [ ] Automatische Lizenzgenerierung nach Kauf

### Phase 5: Geolocation & Analytics (Woche 10–11)
- [ ] MaxMind GeoLite2 Integration
- [ ] Automatisches DB-Update (monatlich via Cronjob)
- [ ] IP-Anonymisierung nach Geolocation-Lookup
- [ ] Geografische Verteilungskarte im Dashboard
- [ ] Erweiterte Statistiken (Trends, Plattform-Verteilung)

### Phase 6: Client-Integration (Woche 12–13)
- [ ] License-Client-Modul für Node.js (`src/license/client.js`)
- [ ] Docker-spezifische Fingerprint-Generierung
- [ ] WordPress CacheWarmer Plugin: License-Tab im Admin
- [ ] Drupal CacheWarmer Modul: License-Konfigurationsseite
- [ ] Middleware für Feature-Gating basierend auf Tier
- [ ] Heartbeat-Service (24h Intervall)

### Phase 7: Security & Production (Woche 14)
- [ ] Security Audit (SQL Injection, XSS, CSRF)
- [ ] DSGVO-Compliance-Prüfung
- [ ] Daten-Aufräum-Cronjob (24 Monate)
- [ ] Performance-Tests (Load Testing)
- [ ] Deployment auf `dashboard.cachewarmer.drossmedia.de`
- [ ] SSL/TLS-Konfiguration
- [ ] Monitoring & Alerting Setup
- [ ] Dokumentation finalisieren

---

## Anhang

### A. Glossar

| Begriff | Bedeutung |
|---------|-----------|
| CWLM | CacheWarmer License Manager (Plugin-Slug) |
| Tier | Lizenzstufe (Free, Professional, Enterprise) |
| Plan | Sub-Stufe innerhalb eines Tiers (Starter, Professional, Agency) |
| Fingerprint | SHA-256 Hash aus Installations-spezifischen Daten |
| Grace Period | Karenzzeit nach Lizenzablauf (14 Tage, volle Funktion) |
| Heartbeat | Periodischer Statuscheck einer Installation (alle 24h) |
| Feature Flags | JSON-basierte Feature-Freischaltung pro Tier |

### B. Referenzen

- Stripe API Dokumentation: https://stripe.com/docs/api
- MaxMind GeoLite2: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
- WordPress REST API Handbook: https://developer.wordpress.org/rest-api/
- IndexNow Protokoll: https://www.indexnow.org/documentation
