# CacheWarmer License Manager (CWLM)

WordPress-Plugin zur zentralen Lizenzverwaltung für CacheWarmer-Installationen.

**Plugin Name:** CacheWarmer License Manager
**Plugin Slug:** `cachewarmer-license-manager`
**Kurzform:** CWLM
**Dashboard URL:** `https://cachewarmer.drossmedia.de`
**Namespace:** `cwlm/v1`
**PHP:** >= 8.0
**WordPress:** >= 6.0
**MySQL:** >= 5.7 / MariaDB >= 10.3

---

## Inhaltsverzeichnis

1. [Architektur](#1-architektur)
2. [Installation & Setup](#2-installation--setup)
3. [Datenbank-Schema](#3-datenbank-schema)
4. [REST API Referenz](#4-rest-api-referenz)
5. [Feature Flags nach Tier](#5-feature-flags-nach-tier)
6. [Plattform-spezifische Lizenzierung](#6-plattform-spezifische-lizenzierung)
7. [Stripe Integration](#7-stripe-integration)
8. [Admin Dashboard Pages](#8-admin-dashboard-pages)
9. [Geolocation (MaxMind)](#9-geolocation-maxmind)
10. [License Key Format](#10-license-key-format)
11. [Instance Fingerprint](#11-instance-fingerprint)
12. [Dateistruktur](#12-dateistruktur)
13. [Sicherheit](#13-sicherheit)
14. [Cronjobs & Wartung](#14-cronjobs--wartung)
15. [Development & Testing](#15-development--testing)

---

## 1. Architektur

### Komponentendiagramm

```
┌─────────────────────────────────────────────────────────────┐
│              WordPress (cachewarmer.drossmedia.de) │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │         CacheWarmer License Manager Plugin             │  │
│  │                                                        │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │  │
│  │  │  Admin Pages  │  │  REST API    │  │  Stripe     │  │  │
│  │  │  (7 Seiten)   │  │  Controller  │  │  Webhook    │  │  │
│  │  │              │  │  (5 Endpunkte)│  │  Handler    │  │  │
│  │  └──────┬───────┘  └──────┬───────┘  └──────┬──────┘  │  │
│  │         │                 │                  │         │  │
│  │         └─────────────────┼──────────────────┘         │  │
│  │                           ▼                            │  │
│  │  ┌────────────────────────────────────────────────────┐│  │
│  │  │              Service Layer                         ││  │
│  │  │  License Manager | Installation Tracker |          ││  │
│  │  │  GeoIP Service  | Audit Logger | Rate Limiter     ││  │
│  │  └────────────────────────┬───────────────────────────┘│  │
│  │                           ▼                            │  │
│  │  ┌────────────────────────────────────────────────────┐│  │
│  │  │          MySQL (7 Tabellen, Prefix: wp_cwlm_)      ││  │
│  │  └────────────────────────────────────────────────────┘│  │
│  └────────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │          MaxMind GeoLite2-City (.mmdb lokal)            │  │
│  └────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
         │                    │                     │
         ▼                    ▼                     ▼
   CacheWarmer          CacheWarmer           CacheWarmer
   Node.js/Docker       WordPress Plugin      Drupal Modul
```

### Request-Flow (Lizenzvalidierung)

```
CacheWarmer Installation                    CWLM Dashboard
        │                                        │
        │  POST /wp-json/cwlm/v1/activate        │
        │  { license_key, fingerprint, ... }      │
        │ ──────────────────────────────────────► │
        │                                        │
        │                            ┌───────────┤
        │                            │ 1. Rate Limit prüfen
        │                            │ 2. Input validieren
        │                            │ 3. Lizenz suchen
        │                            │ 4. Status prüfen
        │                            │ 5. Site-Limit prüfen
        │                            │ 6. Installation anlegen
        │                            │ 7. GeoIP auflösen
        │                            │ 8. Audit-Log schreiben
        │                            │ 9. JWT generieren
        │                            └───────────┤
        │                                        │
        │  200 { activated, token, features }     │
        │ ◄────────────────────────────────────── │
        │                                        │
```

---

## 2. Installation & Setup

### 2.1 Plugin Installation

```bash
# Plugin-Verzeichnis erstellen
cd /var/www/html/wp-content/plugins/
git clone [repo-url] cachewarmer-license-manager

# Oder als ZIP hochladen über WordPress Admin → Plugins → Installieren
```

### 2.2 Abhängigkeiten

```bash
cd cachewarmer-license-manager
composer install --no-dev
```

**Composer-Abhängigkeiten:**
- `geoip2/geoip2` – MaxMind GeoLite2 PHP Reader
- `firebase/php-jwt` – JWT Token Generierung/Validierung
- `stripe/stripe-php` – Stripe API Client (für Webhook-Signatur)

### 2.3 WordPress Konfiguration (wp-config.php)

Folgende Konstanten **müssen** in `wp-config.php` definiert werden:

```php
// Stripe Integration
define('CWLM_STRIPE_SECRET_KEY',     'sk_live_...');          // Stripe Secret Key
define('CWLM_STRIPE_WEBHOOK_SECRET', 'whsec_...');            // Webhook Signing Secret
define('CWLM_STRIPE_PUBLISHABLE_KEY','pk_live_...');           // Publishable Key (für Checkout)

// JWT Token
define('CWLM_JWT_SECRET',           'ein-langes-zufälliges-secret-min-32-zeichen');
define('CWLM_JWT_EXPIRY_DAYS',       30);                     // Token-Gültigkeit

// MaxMind GeoLite2
define('CWLM_MAXMIND_LICENSE_KEY',   'dein-maxmind-key');     // Für DB-Downloads
define('CWLM_MAXMIND_DB_PATH',       '/path/to/GeoLite2-City.mmdb');

// Rate Limiting
define('CWLM_RATE_LIMIT_PER_MINUTE', 60);                    // Requests pro Minute/IP
define('CWLM_RATE_LIMIT_ACTIVATE',   10);                    // Aktivierungen pro Minute/IP

// Grace Period
define('CWLM_GRACE_PERIOD_DAYS',     14);                    // Tage nach Ablauf

// Heartbeat
define('CWLM_HEARTBEAT_INTERVAL_HOURS', 24);                 // Check-Intervall

// Development Domains (komma-separiert)
define('CWLM_DEV_DOMAINS', 'localhost,*.local,*.dev,*.test,127.0.0.1');
```

### 2.4 Ersteinrichtung

Nach Aktivierung des Plugins:
1. Plugin aktivieren unter WordPress Admin → Plugins
2. Tabellen werden automatisch erstellt (7 Tabellen mit `wp_cwlm_` Prefix)
3. Unter **CacheWarmer LM → Einstellungen** die Stripe-Keys verifizieren
4. MaxMind GeoLite2-Datenbank herunterladen (Button in Einstellungen)
5. Stripe Webhook-URL konfigurieren: `https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/stripe/webhook`
6. Stripe Produkte anlegen und im **Produkte**-Tab mappen

---

## 3. Datenbank-Schema

Das Plugin erstellt 7 Tabellen bei Aktivierung über `dbDelta()`.

### 3.1 Tabellenübersicht

| Tabelle | Beschreibung | Geschätzte Größe (10k Lizenzen/Jahr) |
|---------|-------------|--------------------------------------|
| `wp_cwlm_licenses` | Kern-Lizenzdaten | ~5 MB |
| `wp_cwlm_installations` | Aktive Instanzen | ~10 MB |
| `wp_cwlm_geo_data` | IP-Geolocation | ~8 MB |
| `wp_cwlm_audit_logs` | Aktivitätsprotokoll | ~50 MB |
| `wp_cwlm_stripe_events` | Webhook-Protokoll | ~30 MB |
| `wp_cwlm_stripe_product_map` | Produkt-Zuordnung | < 1 MB |
| `wp_cwlm_rate_limits` | Rate Limiting | < 1 MB (selbstreinigend) |

### 3.2 Detailschema

#### `wp_cwlm_licenses`

| Spalte | Typ | Null | Default | Beschreibung |
|--------|-----|------|---------|-------------|
| `id` | BIGINT UNSIGNED | Nein | AUTO_INCREMENT | Primärschlüssel |
| `license_key` | VARCHAR(30) | Nein | – | Eindeutiger Lizenzschlüssel (Format: `CW-{TIER}-{HEX16}`) |
| `customer_email` | VARCHAR(255) | Nein | – | Kunden-E-Mail |
| `customer_name` | VARCHAR(255) | Ja | NULL | Kundenname |
| `tier` | ENUM | Nein | 'free' | `free`, `professional`, `enterprise`, `development` |
| `plan` | VARCHAR(50) | Ja | NULL | Sub-Plan: `starter`, `professional`, `agency`, `enterprise`, `dev` |
| `status` | ENUM | Nein | 'inactive' | `inactive`, `active`, `grace_period`, `expired`, `revoked` |
| `max_sites` | INT UNSIGNED | Nein | 1 | Maximale gleichzeitige Installationen |
| `active_sites` | INT UNSIGNED | Nein | 0 | Aktuell aktive Installationen (Cache-Counter) |
| `features_json` | JSON | Ja | NULL | Feature-Override (überschreibt Tier-Defaults) |
| `stripe_customer_id` | VARCHAR(255) | Ja | NULL | Stripe Customer ID (`cus_xxx`) |
| `stripe_subscription_id` | VARCHAR(255) | Ja | NULL | Stripe Subscription ID (`sub_xxx`) |
| `expires_at` | DATETIME | Ja | NULL | Ablaufdatum (NULL = unbegrenzt) |
| `activated_at` | DATETIME | Ja | NULL | Erstaktivierung |
| `created_at` | DATETIME | Nein | CURRENT_TIMESTAMP | Erstellungsdatum |
| `updated_at` | DATETIME | Nein | CURRENT_TIMESTAMP | Letzte Änderung |
| `notes` | TEXT | Ja | NULL | Admin-Notizen |

**Indizes:** `license_key` (UNIQUE), `customer_email`, `tier`, `status`, `stripe_customer_id`, `expires_at`

#### `wp_cwlm_installations`

| Spalte | Typ | Null | Default | Beschreibung |
|--------|-----|------|---------|-------------|
| `id` | BIGINT UNSIGNED | Nein | AUTO_INCREMENT | Primärschlüssel |
| `license_id` | BIGINT UNSIGNED | Nein | – | FK → licenses.id |
| `domain` | VARCHAR(255) | Ja | NULL | Für WordPress/Drupal-Installationen |
| `hostname` | VARCHAR(255) | Ja | NULL | Für Node.js/Docker-Installationen |
| `fingerprint` | VARCHAR(64) | Nein | – | SHA-256 Installations-Fingerprint |
| `platform` | ENUM | Nein | – | `nodejs`, `docker`, `wordpress`, `drupal` |
| `platform_version` | VARCHAR(20) | Ja | NULL | z.B. `20.11.0`, `6.4.2` |
| `cachewarmer_version` | VARCHAR(20) | Ja | NULL | CacheWarmer-Version |
| `os_platform` | VARCHAR(50) | Ja | NULL | z.B. `linux`, `darwin`, `win32` |
| `os_version` | VARCHAR(50) | Ja | NULL | z.B. `Ubuntu 22.04` |
| `ip_address` | VARCHAR(45) | Ja | NULL | Anonymisierte IP (letztes Oktett = 0) |
| `last_check` | DATETIME | Ja | NULL | Letzter Heartbeat |
| `is_active` | TINYINT(1) | Nein | 1 | Aktiv-Flag |
| `activated_at` | DATETIME | Nein | CURRENT_TIMESTAMP | Aktivierungszeitpunkt |
| `deactivated_at` | DATETIME | Ja | NULL | Deaktivierungszeitpunkt |

**Indizes:** `(license_id, fingerprint)` (UNIQUE), `domain`, `platform`, `last_check`, `is_active`

#### `wp_cwlm_geo_data`

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | BIGINT UNSIGNED | Primärschlüssel |
| `installation_id` | BIGINT UNSIGNED | FK → installations.id |
| `country_code` | CHAR(2) | ISO 3166-1 alpha-2 |
| `country_name` | VARCHAR(100) | Ländername |
| `region` | VARCHAR(100) | Bundesland/Region |
| `city` | VARCHAR(100) | Stadt |
| `latitude` | DECIMAL(10,7) | Breitengrad |
| `longitude` | DECIMAL(10,7) | Längengrad |
| `timezone` | VARCHAR(50) | Zeitzone |
| `isp` | VARCHAR(255) | Internet Service Provider |
| `fetched_at` | DATETIME | Abfragezeitpunkt |

#### `wp_cwlm_audit_logs`

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | BIGINT UNSIGNED | Primärschlüssel |
| `license_id` | BIGINT UNSIGNED | Zugehörige Lizenz (optional) |
| `installation_id` | BIGINT UNSIGNED | Zugehörige Installation (optional) |
| `action` | VARCHAR(50) | Aktion (siehe unten) |
| `actor_type` | ENUM | `system`, `admin`, `api`, `stripe` |
| `actor_id` | VARCHAR(255) | WP User ID oder System-Identifier |
| `ip_address` | VARCHAR(45) | Anfrage-IP (anonymisiert) |
| `details_json` | JSON | Kontextdaten |
| `created_at` | DATETIME | Zeitstempel |

**Audit-Aktionen:**
- `license.created` – Lizenz erstellt (manuell oder via Stripe)
- `license.activated` – Erste Aktivierung einer Installation
- `license.deactivated` – Installation deaktiviert
- `license.renewed` – Lizenz verlängert
- `license.expired` – Lizenz abgelaufen
- `license.revoked` – Lizenz gesperrt
- `license.updated` – Lizenzdaten geändert (Admin)
- `license.deleted` – Lizenz gelöscht (DSGVO)
- `installation.check` – Heartbeat empfangen
- `stripe.webhook.received` – Stripe Event eingegangen
- `stripe.webhook.processed` – Stripe Event verarbeitet
- `stripe.webhook.failed` – Stripe Event Verarbeitung fehlgeschlagen
- `settings.updated` – Einstellungen geändert

#### `wp_cwlm_stripe_events`

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | BIGINT UNSIGNED | Primärschlüssel |
| `stripe_event_id` | VARCHAR(255) | Stripe Event ID (UNIQUE, Idempotenz) |
| `event_type` | VARCHAR(100) | z.B. `checkout.session.completed` |
| `payload_json` | JSON | Vollständiger Event-Payload |
| `processing_status` | ENUM | `pending`, `processed`, `failed`, `ignored` |
| `license_id` | BIGINT UNSIGNED | Erzeugte/betroffene Lizenz |
| `error_message` | TEXT | Fehlermeldung bei `failed` |
| `received_at` | DATETIME | Empfangszeitpunkt |
| `processed_at` | DATETIME | Verarbeitungszeitpunkt |

#### `wp_cwlm_stripe_product_map`

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | BIGINT UNSIGNED | Primärschlüssel |
| `stripe_product_id` | VARCHAR(255) | Stripe Product ID (`prod_xxx`) |
| `stripe_price_id` | VARCHAR(255) | Stripe Price ID (`price_xxx`) |
| `tier` | ENUM | `free`, `professional`, `enterprise` |
| `plan` | VARCHAR(50) | Sub-Plan-Name |
| `max_sites` | INT UNSIGNED | Installationslimit |
| `duration_days` | INT UNSIGNED | Lizenzlaufzeit (Standard: 365) |
| `description` | VARCHAR(255) | Beschreibung für Admin |
| `is_active` | TINYINT(1) | Aktiv-Flag |
| `created_at` | DATETIME | Erstellungsdatum |

#### `wp_cwlm_rate_limits`

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | BIGINT UNSIGNED | Primärschlüssel |
| `ip_address` | VARCHAR(45) | Anfrage-IP |
| `endpoint` | VARCHAR(100) | API-Endpoint |
| `request_count` | INT UNSIGNED | Zähler im Zeitfenster |
| `window_start` | DATETIME | Fenster-Start |
| `window_end` | DATETIME | Fenster-Ende |

---

## 4. REST API Referenz

**Base URL:** `https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/`

Alle Endpunkte sind öffentlich (keine WordPress-Authentifizierung erforderlich). Rate Limiting wird per IP angewendet.

### 4.1 Health Check

```
GET /health
```

Prüft ob das License-Dashboard erreichbar und funktionsfähig ist.

**Response: `200 OK`**
```json
{
    "status": "ok",
    "version": "1.0.0",
    "timestamp": "2026-02-28T12:00:00Z",
    "database": "connected",
    "geoip_db": "loaded",
    "geoip_updated": "2026-02-01"
}
```

### 4.2 Validate

```
POST /validate
Content-Type: application/json
```

Prüft eine Lizenz ohne sie zu aktivieren. Nützlich für Pre-Check in Installer/Setup-Wizard.

**Request Body:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "platform": "nodejs"
}
```

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|-------------|
| `license_key` | string | Ja | Lizenzschlüssel (Format: `CW-{TIER}-{HEX16}`) |
| `platform` | string | Ja | `nodejs`, `docker`, `wordpress`, `drupal` |

**Response: `200 OK`**
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

**Fehler:**
- `400 Bad Request` – Fehlende/ungültige Parameter
- `404 Not Found` – Lizenzschlüssel unbekannt (`INVALID_KEY`)
- `429 Too Many Requests` – Rate Limit

### 4.3 Activate

```
POST /activate
Content-Type: application/json
```

Registriert eine neue Installation und aktiviert die Lizenz.

**Request Body:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567890123456789012345678901234abcd",
    "platform": "nodejs",
    "platform_version": "20.11.0",
    "cachewarmer_version": "1.0.0",
    "domain": null,
    "hostname": "web-server-01",
    "os_platform": "linux",
    "os_version": "Ubuntu 22.04"
}
```

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|-------------|
| `license_key` | string | Ja | Lizenzschlüssel |
| `fingerprint` | string | Ja | SHA-256 Hash (64 Hex-Zeichen) |
| `platform` | string | Ja | `nodejs`, `docker`, `wordpress`, `drupal` |
| `platform_version` | string | Nein | Version der Plattform |
| `cachewarmer_version` | string | Nein | CacheWarmer-Version |
| `domain` | string | Nein | Für WP/Drupal: Website-Domain |
| `hostname` | string | Nein | Für Node.js/Docker: Servername |
| `os_platform` | string | Nein | Betriebssystem |
| `os_version` | string | Nein | OS-Version |

**Response: `200 OK`**
```json
{
    "activated": true,
    "installation_id": 42,
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_expires_at": "2026-03-30T12:00:00Z",
    "features": { ... },
    "next_check": "2026-03-01T12:00:00Z"
}
```

**Fehler:**
- `400 Bad Request` – Ungültige Parameter
- `403 Forbidden` – Lizenz gesperrt (`LICENSE_REVOKED`) oder abgelaufen (`LICENSE_EXPIRED`)
- `409 Conflict` – Installationslimit erreicht (`SITE_LIMIT_REACHED`)
- `429 Too Many Requests` – Rate Limit

**Sonderfälle:**
- Bereits aktivierter Fingerprint → Bestehende Installation wird aktualisiert (kein neuer Slot)
- Development-Lizenz auf Produktions-Domain → `403` mit Code `DEVELOPMENT_ONLY`
- Bereits existierende Installation mit gleichem Fingerprint → Re-Aktivierung (kein neuer Counter)

### 4.4 Deactivate

```
POST /deactivate
Content-Type: application/json
```

Gibt einen Installations-Slot frei (z.B. bei Server-Migration).

**Request Body:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567890123456789012345678901234abcd"
}
```

**Response: `200 OK`**
```json
{
    "deactivated": true,
    "active_sites": 0,
    "max_sites": 3
}
```

**Fehler:**
- `400 Bad Request` – Fehlende Parameter
- `404 Not Found` – Lizenz oder Installation nicht gefunden

### 4.5 Check (Heartbeat)

```
POST /check
Content-Type: application/json
```

Periodischer Statuscheck. Wird alle 24 Stunden von der CacheWarmer-Installation gesendet.

**Request Body:**
```json
{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567890123456789012345678901234abcd",
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "cachewarmer_version": "1.0.0"
}
```

**Response: `200 OK`**
```json
{
    "valid": true,
    "status": "active",
    "features": { ... },
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "token_expires_at": "2026-03-31T12:00:00Z",
    "next_check": "2026-03-02T12:00:00Z",
    "update_available": "1.1.0",
    "messages": [
        {
            "type": "info",
            "text": "CacheWarmer 1.1.0 ist verfügbar mit verbesserter Facebook-Integration."
        }
    ]
}
```

**Aktionen bei Check:**
1. JWT-Token validieren und erneuern
2. `last_check` in Installation aktualisieren
3. Geolocation aktualisieren (falls > 30 Tage alt)
4. CacheWarmer-Version aktualisieren
5. Lizenzstatus prüfen (Active → Grace → Expired Übergang)
6. Verfügbare Updates prüfen und melden

### 4.6 Stripe Webhook

```
POST /stripe/webhook
Content-Type: application/json
Stripe-Signature: t=...,v1=...
```

Empfängt und verarbeitet Stripe Webhook Events. Nicht für manuelle Nutzung.

**Verarbeitung:**
1. HMAC-SHA256 Signatur prüfen (`CWLM_STRIPE_WEBHOOK_SECRET`)
2. Idempotenz prüfen (`stripe_event_id`)
3. Event in `wp_cwlm_stripe_events` loggen
4. Event verarbeiten (siehe Stripe Integration)
5. Audit-Log schreiben

**Response: `200 OK`** (immer, um Stripe-Retries zu vermeiden)
```json
{
    "received": true,
    "processed": true
}
```

### 4.7 Einheitliches Fehlerformat

Alle API-Fehler folgen diesem Schema:

```json
{
    "error": true,
    "code": "ERROR_CODE",
    "message": "Menschenlesbare Fehlermeldung",
    "details": {}
}
```

| Code | HTTP | Beschreibung |
|------|------|-------------|
| `INVALID_KEY` | 404 | Lizenzschlüssel unbekannt |
| `LICENSE_EXPIRED` | 403 | Lizenz abgelaufen (nach Grace Period) |
| `LICENSE_REVOKED` | 403 | Lizenz administrativ gesperrt |
| `SITE_LIMIT_REACHED` | 409 | Alle Installations-Slots belegt |
| `INVALID_FINGERPRINT` | 400 | Fingerprint ungültig (nicht 64 Hex-Zeichen) |
| `INVALID_PLATFORM` | 400 | Ungültige Plattform |
| `RATE_LIMITED` | 429 | Zu viele Anfragen |
| `DEVELOPMENT_ONLY` | 403 | Dev-Lizenz auf Produktions-Domain |
| `INVALID_TOKEN` | 401 | JWT ungültig oder abgelaufen |
| `MISSING_PARAMS` | 400 | Pflichtparameter fehlen |
| `INTERNAL_ERROR` | 500 | Interner Serverfehler |

---

## 5. Feature Flags nach Tier

Jeder Tier hat ein vordefiniertes Feature-Set. Einzelne Features können über `features_json` in der Lizenz überschrieben werden.

### 5.1 Feature-Matrix

```json
{
    "free": {
        "cdn_warming": true,
        "cdn_puppeteer": false,
        "social_facebook": false,
        "social_linkedin": false,
        "social_twitter": false,
        "indexnow": false,
        "google_search_console": false,
        "bing_webmaster": false,
        "scheduling": false,
        "max_sitemaps": 1,
        "max_urls": 50,
        "max_workers": 1,
        "diff_detection": false,
        "multi_site": false,
        "screenshots": false,
        "lighthouse": false,
        "webhooks": false,
        "cloudflare": false,
        "whitelabel": false,
        "priority_support": false
    },
    "professional": {
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
        "whitelabel": false,
        "priority_support": false
    },
    "enterprise": {
        "cdn_warming": true,
        "cdn_puppeteer": true,
        "social_facebook": true,
        "social_linkedin": true,
        "social_twitter": true,
        "indexnow": true,
        "google_search_console": true,
        "bing_webmaster": true,
        "scheduling": true,
        "max_sitemaps": -1,
        "max_urls": -1,
        "max_workers": 10,
        "diff_detection": true,
        "multi_site": true,
        "screenshots": true,
        "lighthouse": true,
        "webhooks": true,
        "cloudflare": true,
        "whitelabel": true,
        "priority_support": true
    },
    "development": {
        "_inherits": "enterprise",
        "_restrictions": {
            "allowed_domains": ["localhost", "*.local", "*.dev", "*.test", "127.0.0.1"]
        }
    }
}
```

> **Hinweis:** `max_sitemaps: -1` und `max_urls: -1` bedeuten unbegrenzt.

### 5.2 Feature-Override Beispiel

Für individuelle Anpassungen kann `features_json` in der Lizenz gesetzt werden:

```json
// Beispiel: Professional-Lizenz mit zusätzlichem Google Search Console Zugriff
{
    "google_search_console": true,
    "max_urls": 10000
}
```

Die Merge-Logik: Tier-Defaults werden geladen, dann durch `features_json` überschrieben.

### 5.3 Sub-Pläne (Professional Tier)

| Plan | Max Sites | Max Sitemaps | Max URLs | Preis (indikativ) |
|------|-----------|-------------|----------|-------------------|
| Starter | 1 | 1 | 1.000 | €9/Monat |
| Professional | 3 | 5 | 5.000 | €19/Monat |
| Agency | 10 | Unbegrenzt | 5.000/Sitemap | €49/Monat |

---

## 6. Plattform-spezifische Lizenzierung

### 6.1 Node.js Standalone

**Konfiguration (`.env`):**
```env
LICENSE_KEY=CW-PRO-A1B2C3D4E5F6G7H8
LICENSE_DASHBOARD_URL=https://cachewarmer.drossmedia.de
```

**Fingerprint-Generierung (`src/license/fingerprint.js`):**
```javascript
const crypto = require('crypto');
const os = require('os');
const fs = require('fs');
const path = require('path');

function generateFingerprint() {
    const instanceIdPath = path.join(__dirname, '../../data/.instance-id');

    // Persistente UUID laden oder erstellen
    let instanceId;
    if (fs.existsSync(instanceIdPath)) {
        instanceId = fs.readFileSync(instanceIdPath, 'utf8').trim();
    } else {
        instanceId = crypto.randomUUID();
        fs.mkdirSync(path.dirname(instanceIdPath), { recursive: true });
        fs.writeFileSync(instanceIdPath, instanceId);
    }

    const components = [
        os.hostname(),
        instanceId,
        os.platform(),
        os.arch()
    ];

    return crypto.createHash('sha256')
        .update(components.join('|'))
        .digest('hex');
}
```

**Aktivierung beim Start (`src/license/client.js`):**
```javascript
async function activateLicense() {
    const response = await fetch(`${DASHBOARD_URL}/wp-json/cwlm/v1/activate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            license_key: process.env.LICENSE_KEY,
            fingerprint: generateFingerprint(),
            platform: 'nodejs',
            platform_version: process.version,
            cachewarmer_version: require('../../package.json').version,
            hostname: os.hostname(),
            os_platform: os.platform(),
            os_version: os.release()
        })
    });
    // Token und Features cachen
}
```

### 6.2 Docker

**docker-compose.yml:**
```yaml
services:
  cachewarmer:
    image: drossmedia/cachewarmer:latest
    environment:
      - LICENSE_KEY=CW-PRO-A1B2C3D4E5F6G7H8
      - LICENSE_DASHBOARD_URL=https://cachewarmer.drossmedia.de
    volumes:
      - cachewarmer-data:/app/data  # Persistiert .instance-id

volumes:
  cachewarmer-data:
```

**Fingerprint:** Identisch zu Node.js, aber die `data/.instance-id` Datei wird über ein Docker Volume persistiert, damit der Fingerprint Container-Neustarts überlebt.

### 6.3 WordPress Plugin

**Admin-Seite:** CacheWarmer → Lizenz

```php
// Aktivierung über WordPress Admin UI
// Fingerprint: SHA-256 aus site_url() + wp_version + Domain
function cwlm_generate_wp_fingerprint() {
    $components = [
        get_site_url(),
        get_bloginfo('version'),
        parse_url(get_site_url(), PHP_URL_HOST),
        php_uname('s')
    ];
    return hash('sha256', implode('|', $components));
}
```

**Speicherung:** Lizenzschlüssel und Token werden als WordPress Options gespeichert:
- `cachewarmer_license_key` – Verschlüsselter Lizenzschlüssel
- `cachewarmer_license_token` – JWT Token
- `cachewarmer_license_features` – Gecachte Feature-Flags
- `cachewarmer_license_checked` – Letzter Heartbeat Timestamp

### 6.4 Drupal Modul

**Admin-Konfiguration:** `/admin/config/cachewarmer/license`

```php
// Fingerprint: SHA-256 aus base_url + Drupal-Version + Domain
function cachewarmer_generate_drupal_fingerprint() {
    $components = [
        \Drupal::request()->getSchemeAndHttpHost(),
        \Drupal::VERSION,
        \Drupal::request()->getHost(),
        php_uname('s')
    ];
    return hash('sha256', implode('|', $components));
}
```

**Speicherung:** Über Drupal Config API (`cachewarmer.license` Konfiguration).

---

## 7. Stripe Integration

### 7.1 Webhook-Konfiguration

**Stripe Dashboard → Developers → Webhooks:**

| Einstellung | Wert |
|-------------|------|
| Endpoint URL | `https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/stripe/webhook` |
| API Version | `2024-12-18.acacia` (oder aktuellste) |
| Events | Siehe 7.2 |

### 7.2 Verarbeitete Events

| Stripe Event | CWLM Aktion | Audit Action |
|-------------|-------------|-------------|
| `checkout.session.completed` | Lizenz erstellen (Status: `inactive`), E-Mail senden | `license.created` |
| `invoice.payment_succeeded` | Ablaufdatum verlängern, ggf. `grace_period` → `active` | `license.renewed` |
| `invoice.payment_failed` | `active` → `grace_period` (falls Ablauf erreicht) | `license.expired` |
| `customer.subscription.deleted` | `active`/`grace_period` → `expired` | `license.expired` |
| `customer.subscription.updated` | Plan-Änderung übernehmen (Tier, Max Sites, Features) | `license.updated` |
| `charge.refunded` | → `revoked` | `license.revoked` |
| `charge.dispute.created` | → `revoked` | `license.revoked` |

### 7.3 Webhook-Verarbeitungslogik

```php
function cwlm_process_webhook($event) {
    // 1. Signatur prüfen
    $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $payload = file_get_contents('php://input');
    Webhook::constructEvent($payload, $sig, CWLM_STRIPE_WEBHOOK_SECRET);

    // 2. Idempotenz: Bereits verarbeitetes Event?
    if (cwlm_event_exists($event->id)) {
        return ['received' => true, 'processed' => false, 'reason' => 'duplicate'];
    }

    // 3. Event in DB loggen
    cwlm_log_stripe_event($event);

    // 4. Event verarbeiten
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            $product_map = cwlm_get_product_map($session->metadata->product_id);
            $license_key = cwlm_generate_license_key($product_map->tier);
            cwlm_create_license([
                'license_key'     => $license_key,
                'customer_email'  => $session->customer_details->email,
                'customer_name'   => $session->customer_details->name,
                'tier'            => $product_map->tier,
                'plan'            => $product_map->plan,
                'max_sites'       => $product_map->max_sites,
                'stripe_customer_id'     => $session->customer,
                'stripe_subscription_id' => $session->subscription,
                'expires_at'      => date('Y-m-d H:i:s', strtotime("+{$product_map->duration_days} days")),
            ]);
            cwlm_send_license_email($session->customer_details->email, $license_key);
            break;

        case 'invoice.payment_succeeded':
            $invoice = $event->data->object;
            $license = cwlm_find_by_subscription($invoice->subscription);
            if ($license) {
                cwlm_extend_license($license->id, $product_map->duration_days);
            }
            break;

        // ... weitere Events
    }

    // 5. Audit-Log
    cwlm_audit_log('stripe.webhook.processed', 'stripe', $event->id, [
        'event_type' => $event->type
    ]);
}
```

### 7.4 Produkt-Mapping UI

Im Admin unter **CacheWarmer LM → Produkte**:

- Stripe Produkt-ID und Preis-ID eingeben
- Tier und Plan auswählen
- Max Sites und Laufzeit festlegen
- Aktiv/Inaktiv-Toggle

Beispiel-Mapping:

| Stripe Product | Price | → Tier | Plan | Sites | Tage |
|---------------|-------|--------|------|-------|------|
| `prod_CWStarter` | `price_CWStarterMo` | professional | starter | 1 | 30 |
| `prod_CWStarter` | `price_CWStarterYr` | professional | starter | 1 | 365 |
| `prod_CWPro` | `price_CWProMo` | professional | professional | 3 | 30 |
| `prod_CWPro` | `price_CWProYr` | professional | professional | 3 | 365 |
| `prod_CWAgency` | `price_CWAgencyMo` | professional | agency | 10 | 30 |
| `prod_CWAgency` | `price_CWAgencyYr` | professional | agency | 10 | 365 |
| `prod_CWEnterprise` | `price_CWEntMo` | enterprise | enterprise | 999 | 30 |
| `prod_CWEnterprise` | `price_CWEntYr` | enterprise | enterprise | 999 | 365 |

---

## 8. Admin Dashboard Pages

### 8.1 Übersicht (Dashboard)

**Route:** `admin.php?page=cwlm-dashboard`

**KPI-Karten:**
| KPI | Berechnung |
|-----|-----------|
| Aktive Lizenzen | `COUNT(*) WHERE status = 'active'` |
| Aktive Installationen | `COUNT(*) WHERE is_active = 1` |
| Grace Period | `COUNT(*) WHERE status = 'grace_period'` |
| Neu (30 Tage) | `COUNT(*) WHERE created_at > NOW() - 30 DAYS` |
| Umsatz (MTD) | Summe Stripe-Zahlungen aktueller Monat |
| Ablaufend (30 Tage) | `COUNT(*) WHERE expires_at BETWEEN NOW() AND NOW() + 30 DAYS` |

**Diagramme:**
- Lizenzen nach Tier (Donut: Free/Professional/Enterprise)
- Installationen nach Plattform (Balken: Node.js/Docker/WordPress/Drupal)
- Aktivierungen über Zeit (Linie, 12 Monate rollierend)
- Geografische Verteilung (Weltkarte mit Clustern)

### 8.2 Lizenzen

**Route:** `admin.php?page=cwlm-licenses`

**Funktionen:**
- Tabellenansicht aller Lizenzen (paginiert, sortierbar)
- Schnellsuche: Key, E-Mail, Domain
- Filter: Tier, Status, Plan, Erstellungsdatum
- Bulk-Aktionen: Sperren, Verlängern, CSV-Export
- Button: "Neue Lizenz erstellen" (manuell)

**Einzelansicht:** `admin.php?page=cwlm-licenses&id=42`
- Stammdaten (editierbar)
- Aktive Installationen (mit Deaktivieren-Button)
- Stripe-Historie (Zahlungen, Subscription)
- Audit-Log (gefiltert auf diese Lizenz)
- Feature-Override Editor (JSON)

### 8.3 Installationen

**Route:** `admin.php?page=cwlm-installations`

**Spalten:**
1. ID
2. Lizenz (Link zur Lizenz-Detailseite)
3. Domain / Hostname
4. Plattform + Version
5. CacheWarmer-Version
6. OS
7. Land/Stadt (Flagge + Name)
8. Letzter Check
9. Status (Aktiv/Inaktiv)

**Filter:** Plattform, Land, Aktiv/Inaktiv, Letzter Check (> X Tage)

### 8.4 Audit Log

**Route:** `admin.php?page=cwlm-audit`

Chronologische Liste aller Aktionen mit:
- Zeitstempel
- Aktion (farbcodiert)
- Akteur (System/Admin/API/Stripe)
- Lizenz-Link
- IP-Adresse
- Details (aufklappbar, JSON)

**Filter:** Aktion, Akteur-Typ, Zeitraum, Lizenz-ID

### 8.5 Stripe Events

**Route:** `admin.php?page=cwlm-stripe`

Alle eingegangenen Stripe Webhook Events mit:
- Event ID
- Typ
- Status (Pending/Processed/Failed/Ignored)
- Zugehörige Lizenz
- Empfangen/Verarbeitet Zeitstempel
- Fehlermeldung (bei Failed)

**Aktion:** "Erneut verarbeiten" Button für fehlgeschlagene Events.

### 8.6 Produkte

**Route:** `admin.php?page=cwlm-products`

Stripe Produkt-Mapping Verwaltung (CRUD):
- Stripe Product ID + Price ID
- Tier + Plan Auswahl
- Max Sites + Laufzeit
- Aktiv/Inaktiv Toggle

### 8.7 Einstellungen

**Route:** `admin.php?page=cwlm-settings`

**Tabs:**
1. **Allgemein** – Grace Period, Heartbeat-Intervall, Dev-Domains
2. **Stripe** – Keys (verschleiert angezeigt), Webhook-URL (Copy-Button), Verbindungstest
3. **MaxMind** – License Key, DB-Pfad, Letztes Update, "Jetzt aktualisieren" Button
4. **Rate Limiting** – Limits pro Endpoint konfigurierbar
5. **E-Mail** – Templates für Lizenz-Zustellung, Ablauf-Warnung
6. **DSGVO** – Aufbewahrungsfristen, "Kundendaten löschen" Funktion

---

## 9. Geolocation (MaxMind)

### 9.1 Setup

1. Account bei MaxMind erstellen (kostenlos für GeoLite2)
2. License Key generieren
3. GeoLite2-City.mmdb herunterladen
4. Pfad in `CWLM_MAXMIND_DB_PATH` konfigurieren

### 9.2 Abfrage-Zeitpunkte

- Bei **Aktivierung** einer Installation
- Bei **Heartbeat** (Check), falls Geodaten > 30 Tage alt

### 9.3 Ablauf

```
1. IP-Adresse der Anfrage erfassen
2. MaxMind GeoLite2-City lokal abfragen (kein externer API-Call)
3. Ergebnis in wp_cwlm_geo_data speichern
4. IP-Adresse anonymisieren (letztes Oktett → 0)
5. Anonymisierte IP in wp_cwlm_installations speichern
```

### 9.4 Automatisches DB-Update

WordPress Cronjob (monatlich):
```php
// wp-cron Event: cwlm_update_geoip_db
add_action('cwlm_update_geoip_db', function() {
    $url = "https://download.maxmind.com/app/geoip_download"
         . "?edition_id=GeoLite2-City&license_key=" . CWLM_MAXMIND_LICENSE_KEY
         . "&suffix=tar.gz";
    // Download, entpacken, .mmdb ersetzen
});
```

---

## 10. License Key Format

### 10.1 Struktur

```
CW-{TIER}-{KEY}
│   │      │
│   │      └── 16 Zeichen Hexadezimal (kryptografisch sicher)
│   └────────── Tier-Kennzeichen: FREE, PRO, ENT, DEV
└──────────────  Produkt-Prefix: CacheWarmer
```

### 10.2 Generierung

```php
function cwlm_generate_license_key($tier) {
    $tier_map = [
        'free'         => 'FREE',
        'professional' => 'PRO',
        'enterprise'   => 'ENT',
        'development'  => 'DEV',
    ];

    $prefix = $tier_map[$tier] ?? 'FREE';
    $key = strtoupper(bin2hex(random_bytes(8))); // 16 Hex-Zeichen

    return "CW-{$prefix}-{$key}";
}
```

### 10.3 Validierung (Regex)

```
/^CW-(FREE|PRO|ENT|DEV)-[A-F0-9]{16}$/
```

### 10.4 Beispiele

```
CW-FREE-A1B2C3D4E5F6A7B8
CW-PRO-9F8E7D6C5B4A3210
CW-ENT-1234567890ABCDEF
CW-DEV-FEDCBA0987654321
```

---

## 11. Instance Fingerprint

### 11.1 Zweck

Der Fingerprint identifiziert eine spezifische CacheWarmer-Installation eindeutig. Er wird als SHA-256 Hash aus mehreren Systemkomponenten berechnet und ist plattformabhängig.

### 11.2 Bestandteile nach Plattform

| Plattform | Komponente 1 | Komponente 2 | Komponente 3 | Komponente 4 |
|-----------|-------------|-------------|-------------|-------------|
| Node.js | `os.hostname()` | Persistente UUID (`data/.instance-id`) | `os.platform()` | `os.arch()` |
| Docker | `os.hostname()` | Persistente UUID (Volume: `data/.instance-id`) | `os.platform()` | `os.arch()` |
| WordPress | `get_site_url()` | `get_bloginfo('version')` | Domain | `php_uname('s')` |
| Drupal | Base URL | `\Drupal::VERSION` | Domain | `php_uname('s')` |

### 11.3 Hash-Algorithmus

```
fingerprint = SHA-256( component1 + "|" + component2 + "|" + component3 + "|" + component4 )
```

Ergebnis: 64 Zeichen Hexadezimal-String.

### 11.4 Persistenz

- **Node.js/Docker:** UUID-Datei `data/.instance-id` wird beim ersten Start erstellt und muss persistiert werden (Docker: Volume Mount)
- **WordPress/Drupal:** Fingerprint ergibt sich aus Site-URL und Version – ändert sich bei Domain-Wechsel oder Major-Update (erfordert Re-Aktivierung)

---

## 12. Dateistruktur

```
cachewarmer-license-manager/
├── cachewarmer-license-manager.php     # Plugin-Hauptdatei (Header, Hooks)
├── composer.json                        # PHP-Abhängigkeiten
├── composer.lock
├── uninstall.php                        # Cleanup bei Deinstallation
│
├── includes/
│   ├── class-cwlm-activator.php         # Plugin-Aktivierung (DB-Setup)
│   ├── class-cwlm-deactivator.php       # Plugin-Deaktivierung
│   ├── class-cwlm-loader.php            # Hook/Filter Loader
│   ├── class-cwlm-database.php          # DB-Schema & Migrations
│   ├── class-cwlm-license-manager.php   # Lizenz-CRUD Logik
│   ├── class-cwlm-installation-tracker.php  # Installation-Tracking
│   ├── class-cwlm-geoip.php            # MaxMind Integration
│   ├── class-cwlm-audit-logger.php     # Audit-Trail
│   ├── class-cwlm-rate-limiter.php     # Rate Limiting (Transients)
│   ├── class-cwlm-jwt-handler.php      # JWT Token Management
│   ├── class-cwlm-stripe-handler.php   # Stripe Webhook Verarbeitung
│   ├── class-cwlm-email.php            # E-Mail-Versand (Templates)
│   └── class-cwlm-feature-flags.php    # Tier → Feature Mapping
│
├── admin/
│   ├── class-cwlm-admin.php            # Admin-Menü & Page Registration
│   ├── class-cwlm-admin-dashboard.php  # KPI Dashboard
│   ├── class-cwlm-admin-licenses.php   # Lizenzverwaltung
│   ├── class-cwlm-admin-installations.php  # Installations-Übersicht
│   ├── class-cwlm-admin-audit.php      # Audit-Log Seite
│   ├── class-cwlm-admin-stripe.php     # Stripe Events Seite
│   ├── class-cwlm-admin-products.php   # Produkt-Mapping
│   ├── class-cwlm-admin-settings.php   # Einstellungen
│   │
│   ├── views/                           # PHP-Templates
│   │   ├── dashboard.php
│   │   ├── licenses.php
│   │   ├── license-detail.php
│   │   ├── license-form.php
│   │   ├── installations.php
│   │   ├── audit-log.php
│   │   ├── stripe-events.php
│   │   ├── products.php
│   │   ├── product-form.php
│   │   └── settings.php
│   │
│   ├── css/
│   │   └── cwlm-admin.css              # Dashboard-Styles
│   │
│   └── js/
│       ├── cwlm-dashboard.js           # Chart.js Diagramme
│       ├── cwlm-licenses.js            # Lizenz-Tabelle (DataTables)
│       └── cwlm-admin.js               # Allgemeine Admin-Funktionen
│
├── api/
│   ├── class-cwlm-rest-controller.php  # REST API Base Controller
│   ├── class-cwlm-health-endpoint.php  # GET /health
│   ├── class-cwlm-validate-endpoint.php # POST /validate
│   ├── class-cwlm-activate-endpoint.php # POST /activate
│   ├── class-cwlm-deactivate-endpoint.php # POST /deactivate
│   ├── class-cwlm-check-endpoint.php   # POST /check
│   └── class-cwlm-stripe-webhook.php   # POST /stripe/webhook
│
├── email-templates/
│   ├── license-created.php              # Lizenz-Zustellung
│   ├── license-expiring.php             # Ablauf-Warnung (7 Tage)
│   ├── license-expired.php              # Lizenz abgelaufen
│   └── license-renewed.php              # Verlängerung bestätigt
│
├── data/
│   └── GeoLite2-City.mmdb              # MaxMind DB (git-ignoriert)
│
├── languages/
│   ├── cwlm-de_DE.po                   # Deutsche Übersetzung
│   └── cwlm-de_DE.mo
│
└── tests/
    ├── test-license-manager.php
    ├── test-api-endpoints.php
    ├── test-stripe-webhook.php
    └── test-feature-flags.php
```

---

## 13. Sicherheit

### 13.1 SQL Injection Prevention

Alle Datenbankabfragen nutzen `$wpdb->prepare()`:

```php
// Richtig:
$license = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cwlm_licenses WHERE license_key = %s",
        $license_key
    )
);

// NIEMALS:
// $wpdb->query("SELECT * FROM ... WHERE key = '$license_key'");
```

### 13.2 Input Validation

```php
// License Key Format
if (!preg_match('/^CW-(FREE|PRO|ENT|DEV)-[A-F0-9]{16}$/', $license_key)) {
    return new WP_Error('INVALID_KEY', 'Ungültiges Lizenzschlüssel-Format', ['status' => 400]);
}

// Fingerprint (SHA-256 = 64 Hex-Zeichen)
if (!preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
    return new WP_Error('INVALID_FINGERPRINT', 'Ungültiger Fingerprint', ['status' => 400]);
}

// Platform
if (!in_array($platform, ['nodejs', 'docker', 'wordpress', 'drupal'], true)) {
    return new WP_Error('INVALID_PLATFORM', 'Ungültige Plattform', ['status' => 400]);
}
```

### 13.3 Stripe Webhook Verification

```php
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

try {
    $event = Webhook::constructEvent(
        $payload,
        $sig_header,
        CWLM_STRIPE_WEBHOOK_SECRET
    );
} catch (SignatureVerificationException $e) {
    // Ungültige Signatur → 400
    http_response_code(400);
    exit();
}
```

### 13.4 CORS

API-Endpunkte erlauben Cross-Origin Requests (notwendig für Node.js/Docker-Installationen):

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

### 13.5 HTTPS Enforcement

```php
// In Plugin-Init: HTTPS erzwingen für API-Endpunkte
if (!is_ssl() && !defined('CWLM_ALLOW_HTTP')) {
    wp_die('HTTPS erforderlich', 'SSL Required', ['response' => 403]);
}
```

### 13.6 WordPress Nonce (Admin-Seiten)

Alle Admin-Formulare verwenden WordPress Nonces:
```php
wp_nonce_field('cwlm_save_license', 'cwlm_nonce');
// Verifizierung:
if (!wp_verify_nonce($_POST['cwlm_nonce'], 'cwlm_save_license')) {
    wp_die('Sicherheitscheck fehlgeschlagen');
}
```

---

## 14. Cronjobs & Wartung

### 14.1 Registrierte Cronjobs

| Hook | Intervall | Funktion |
|------|-----------|----------|
| `cwlm_check_expired_licenses` | Täglich | Prüft Lizenzen auf Ablauf, setzt Status-Übergänge |
| `cwlm_cleanup_old_data` | Wöchentlich | Löscht Daten älter als 24 Monate |
| `cwlm_cleanup_rate_limits` | Stündlich | Räumt abgelaufene Rate-Limit-Einträge auf |
| `cwlm_update_geoip_db` | Monatlich | Aktualisiert MaxMind GeoLite2 Datenbank |
| `cwlm_check_stale_installations` | Täglich | Markiert Installationen ohne Heartbeat (> 7 Tage) als inaktiv |
| `cwlm_send_expiry_warnings` | Täglich | Sendet E-Mail-Warnungen 7 Tage vor Ablauf |

### 14.2 Lizenz-Status-Übergänge (Cronjob)

```
Täglicher Cronjob prüft:
1. active + expires_at < NOW()         → grace_period
2. grace_period + expires_at < NOW()-14d → expired
3. Alle expired-Installationen          → is_active = 0
```

### 14.3 Datenbereinigung

```sql
-- Installationen: Deaktivierte > 24 Monate
DELETE FROM wp_cwlm_installations
WHERE is_active = 0 AND deactivated_at < DATE_SUB(NOW(), INTERVAL 24 MONTH);

-- Geodaten: Verwaiste Einträge
DELETE g FROM wp_cwlm_geo_data g
LEFT JOIN wp_cwlm_installations i ON g.installation_id = i.id
WHERE i.id IS NULL;

-- Audit-Logs: Älter als 24 Monate
DELETE FROM wp_cwlm_audit_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 MONTH);

-- Stripe Events: Älter als 24 Monate
DELETE FROM wp_cwlm_stripe_events
WHERE received_at < DATE_SUB(NOW(), INTERVAL 24 MONTH);

-- Rate Limits: Abgelaufene Fenster
DELETE FROM wp_cwlm_rate_limits
WHERE window_end < NOW();
```

---

## 15. Development & Testing

### 15.1 Lokale Entwicklungsumgebung

```bash
# WordPress mit Docker (Entwicklung)
docker compose -f docker-compose.dev.yml up -d

# Plugin-Verzeichnis symlinken
ln -s /path/to/cachewarmer-license-manager /var/www/html/wp-content/plugins/

# Composer Dependencies
cd cachewarmer-license-manager && composer install

# Stripe CLI für lokales Webhook-Testing
stripe listen --forward-to localhost:8080/wp-json/cwlm/v1/stripe/webhook
```

### 15.2 Test-Lizenzen

Für Entwicklung können Test-Lizenzen manuell erstellt werden:
```
CW-DEV-0000000000000001  → Development (alle Features, nur localhost)
CW-FREE-0000000000000002 → Free Tier
CW-PRO-0000000000000003  → Professional Tier
CW-ENT-0000000000000004  → Enterprise Tier
```

### 15.3 PHPUnit Tests

```bash
# Tests ausführen
cd cachewarmer-license-manager
./vendor/bin/phpunit

# Einzelnen Test ausführen
./vendor/bin/phpunit tests/test-api-endpoints.php
```

### 15.4 API-Testing mit cURL

```bash
# Health Check
curl https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/health

# Validate
curl -X POST https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"CW-PRO-A1B2C3D4E5F6G7H8","platform":"nodejs"}'

# Activate
curl -X POST https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/activate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567890123456789012345678901234abcd",
    "platform": "nodejs",
    "platform_version": "20.11.0",
    "cachewarmer_version": "1.0.0",
    "hostname": "dev-machine",
    "os_platform": "linux",
    "os_version": "Ubuntu 22.04"
  }'

# Heartbeat
curl -X POST https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/check \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567890123456789012345678901234abcd",
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "cachewarmer_version": "1.0.0"
  }'

# Deactivate
curl -X POST https://cachewarmer.drossmedia.de/wp-json/cwlm/v1/deactivate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "CW-PRO-A1B2C3D4E5F6G7H8",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567890123456789012345678901234abcd"
  }'
```
