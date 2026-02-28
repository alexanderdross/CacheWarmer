# CacheWarmer — Versionen & Preisstufen (Free / Premium / Enterprise)

## Inhaltsverzeichnis

1. [Empfohlener Ansatz](#1-empfohlener-ansatz)
2. [Feature-Vergleichstabelle](#2-feature-vergleichstabelle)
3. [Detaillierte Begründung je Kategorie](#3-detaillierte-begründung-je-kategorie)
4. [Technische Umsetzung der Lizenzstufen](#4-technische-umsetzung-der-lizenzstufen)
5. [Preisempfehlung](#5-preisempfehlung)
6. [Zusätzliche Feature-Ideen & Empfehlungen](#6-zusätzliche-feature-ideen--empfehlungen)

---

## 1. Empfohlener Ansatz

### Hybrid-Modell: Feature-Gating + Mengen-Limits

**Empfehlung:** Ein reines Feature-Gating (z.B. „Facebook nur in Premium") frustriert Nutzer, weil sie die Funktionen nicht ausprobieren können. Ein reines Mengen-Limit (z.B. „nur 20 URLs") lässt die Free-Version verkrüppelt wirken. Die beste Strategie ist ein **Hybrid-Modell**:

| Strategie | Vorteil | Nachteil |
|-----------|---------|----------|
| Nur Feature-Gating | Klare Abgrenzung | Kein „Try before you buy" |
| Nur Mengen-Limits | Voller Funktionsumfang erlebbar | Kein Upsell-Anreiz für Power-Features |
| **Hybrid (empfohlen)** | **Nutzer erleben den Mehrwert, wachsen natürlich in Premium** | Etwas komplexere Implementierung |

### Kernprinzip

> **Die Free-Version soll _sofort nützlich_ sein und echten Mehrwert liefern.**
> Premium schaltet sich natürlich frei, wenn die Website wächst oder Social-Media-Präsenz wichtiger wird.

**Begründung:**
- **Free-Nutzer werden zu Botschaftern** — wer mit der Free-Version zufrieden ist, empfiehlt das Plugin weiter
- **Natürliche Conversion** — sobald eine Website >50 Seiten hat oder Social-Media-Caching braucht, ist Premium der logische nächste Schritt
- **Enterprise-Bedarf** entsteht bei Agenturen, Multi-Site-Betreibern und großen Unternehmen organisch

---

## 2. Feature-Vergleichstabelle

### 2.1 Warming-Targets

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **CDN Edge Cache** (Desktop + Mobile) | ✅ | ✅ | ✅ |
| **IndexNow** (Bing, Yandex, Seznam, Naver) | ✅ | ✅ | ✅ |
| **Facebook Sharing Debugger** | — | ✅ | ✅ |
| **LinkedIn Post Inspector** | — | ✅ | ✅ |
| **Twitter/X Card Validator** | — | ✅ | ✅ |
| **Google Indexing API** | — | ✅ | ✅ |
| **Bing Webmaster URL Submission** | — | ✅ | ✅ |

### 2.2 Mengen-Limits

| Limit | Free | Premium | Enterprise |
|-------|:----:|:-------:|:----------:|
| **URLs pro Warming-Job** | 50 | 10.000 | Unbegrenzt |
| **Registrierte Sitemaps** | 2 | 25 | Unbegrenzt |
| **Externe Sitemaps** (fremde Domains) | 1 | 10 | Unbegrenzt |
| **Jobs pro Tag** | 3 | 50 | Unbegrenzt |
| **Log-Aufbewahrung** | 7 Tage | 90 Tage | 365 Tage |

### 2.3 Scheduling & Automatisierung

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **Manuelles Warming** (Dashboard-Button) | ✅ | ✅ | ✅ |
| **Geplantes Warming** (Scheduler) | — | ✅ | ✅ |
| **Frequenz-Optionen** | — | Täglich / 12h / 6h | Stündlich + Custom Cron |
| **Warming bei Beitrags-Veröffentlichung** | — | ✅ | ✅ |
| **Multi-Sitemap Batch-Warming** | — | — | ✅ |

### 2.4 REST API & Integration

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **REST API Zugang** | — | ✅ | ✅ |
| **API-Authentifizierung** (Bearer Token) | — | ✅ | ✅ |
| **API Rate-Limit** | — | 60 Req/min | Unbegrenzt |
| **Webhook-Benachrichtigungen** | — | — | ✅ |
| **CI/CD Integration** (API-gesteuert) | — | — | ✅ |

### 2.5 Dashboard & Reporting

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **Status-Dashboard** (Queued/Running/Done/Failed) | ✅ | ✅ | ✅ |
| **Job-Tabelle mit Fortschritt** | ✅ | ✅ | ✅ |
| **Job-Detail-Modal** (per-URL Status) | ✅ | ✅ | ✅ |
| **Per-Target Statistiken** | — | ✅ | ✅ |
| **Historische Auswertungen** | — | — | ✅ |
| **CSV/JSON-Export** | — | ✅ | ✅ |
| **Zeitbasierte Charts** (Warming-Trends) | — | — | ✅ |

### 2.6 Konfiguration & Tuning

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **CDN Concurrency** (parallel Requests) | Fix: 2 | 1–10 | 1–20 |
| **Custom User-Agent** | — | ✅ | ✅ |
| **Erweiterte Timeout-Einstellungen** | — | ✅ | ✅ |
| **Service-spezifisches Rate-Limiting** | — | ✅ | ✅ |
| **Log-Level Konfiguration** | Fix: info | Wählbar | Wählbar |

### 2.7 Multi-Site & Agentur

| Feature | Free | Premium | Enterprise |
|---------|:----:|:-------:|:----------:|
| **Single-Site Lizenz** | ✅ | ✅ | — |
| **Multi-Site / Netzwerk** | — | — | ✅ |
| **White-Label** (eigenes Branding) | — | — | ✅ |
| **Zentrale Verwaltung** (alle Sites) | — | — | ✅ |
| **Prioritäts-Support** | — | E-Mail | E-Mail + Live-Chat |

---

## 3. Detaillierte Begründung je Kategorie

### 3.1 Warum CDN + IndexNow in Free?

**CDN Warming** ist die Kernfunktion des Plugins — ohne sie gibt es keinen Grund, CacheWarmer zu installieren. CDN Warming in der Free-Version zu haben, stellt sicher, dass jeder Nutzer _sofort echten Mehrwert_ bekommt.

**IndexNow** ist ein offenes, kostenloses Protokoll. Es hinter einer Paywall zu verstecken, wäre kontraproduktiv und würde das Plugin gegenüber Konkurrenten benachteiligen, die IndexNow kostenlos anbieten (z.B. Yoast SEO, Rank Math).

### 3.2 Warum Social Media nur in Premium?

Social-Media-Cache-Warming (Facebook, LinkedIn, Twitter/X) ist ein **Nischen-Feature für Content-Marketer und SEO-Profis**. Diese Zielgruppe:
- Versteht den Wert und ist bereit zu zahlen
- Hat typischerweise Budget für Marketing-Tools
- Benötigt die Funktion regelmäßig (nicht einmalig)

Die Social-Media-APIs erfordern zudem eigene Credentials (Facebook App, LinkedIn Cookie), was die Einrichtung komplexer macht — ideal für eine Premium-Zielgruppe.

### 3.3 Warum Google/Bing Indexing nur in Premium?

Die **Google Indexing API** hat ein tägliches Limit von 200 URLs und erfordert ein Google Cloud Service Account. **Bing Webmaster URL Submission** braucht einen API-Key. Beides:
- Ist für SEO-Profis gedacht, die bereits bezahlte Tools nutzen
- Erfordert technisches Setup (Service Account, API Keys)
- Bietet messbaren ROI (schnellere Indexierung = schnellere Rankings)

### 3.4 Warum 50 URLs in Free?

| Seitenanzahl | Typische Website |
|-------------:|-----------------|
| < 20 | Kleine Unternehmensseite, Portfolio |
| 20–50 | **Mittelgroße Unternehmensseite, kleiner Blog** |
| 50–500 | Aktiver Blog, E-Commerce (klein) |
| 500–10.000 | Großer Blog, E-Commerce (mittel) |
| > 10.000 | News-Portal, Großer E-Commerce |

**50 URLs** deckt die Mehrheit kleiner Websites ab und gibt genug Spielraum, um den Wert des Plugins zu erleben. Sobald eine Website wächst (>50 Seiten), ist der Upgrade-Anreiz natürlich.

### 3.5 Warum 2 Sitemaps in Free, aber nur 1 externe?

Die Free-Version erlaubt:
- **1 lokale Sitemap** (die eigene Website)
- **1 externe Sitemap** (z.B. eine Subdomain oder ein CDN)

Das reicht für einfache Setups. Premium-Nutzer (z.B. Agenturen, die mehrere Kundendomains verwalten) brauchen mehr — ein natürlicher Upsell-Pfad.

### 3.6 Warum kein Scheduler in Free?

Automatisches, geplantes Warming ist ein **„Set-and-forget"-Feature**, das enormen Zeitwert bietet. Genau deshalb gehört es in Premium:
- Free-Nutzer können manuell aufwärmen (bewusste Aktion)
- Premium-Nutzer automatisieren den Prozess (zeitsparend)
- Der Unterschied ist _spürbar_ — das treibt die Conversion

### 3.7 Warum REST API nur in Premium?

Die REST API ist relevant für:
- Entwickler, die CacheWarmer in CI/CD-Pipelines integrieren
- Agenturen, die das Warming programmatisch steuern
- Unternehmen mit eigenen Automatisierungen

Diese Zielgruppe hat Budget und erwartet kostenpflichtige Tools.

---

## 4. Technische Umsetzung der Lizenzstufen

### 4.1 Lizenzprüfung (WordPress)

```php
// Konstante in wp-config.php oder Plugin-Option
define('CACHEWARMER_LICENSE_TIER', 'free'); // 'free' | 'premium' | 'enterprise'

// Oder via Lizenzschlüssel-Validierung (empfohlen)
class CacheWarmer_License {
    const TIER_FREE       = 'free';
    const TIER_PREMIUM    = 'premium';
    const TIER_ENTERPRISE = 'enterprise';

    const LIMITS = [
        'free' => [
            'max_urls_per_job'     => 50,
            'max_sitemaps'         => 2,
            'max_external_sitemaps'=> 1,
            'max_jobs_per_day'     => 3,
            'log_retention_days'   => 7,
            'cdn_concurrency'      => 2,
            'allowed_targets'      => ['cdn', 'indexnow'],
            'scheduler_enabled'    => false,
            'api_enabled'          => false,
            'export_enabled'       => false,
        ],
        'premium' => [
            'max_urls_per_job'     => 10000,
            'max_sitemaps'         => 25,
            'max_external_sitemaps'=> 10,
            'max_jobs_per_day'     => 50,
            'log_retention_days'   => 90,
            'cdn_concurrency'      => 10,
            'allowed_targets'      => ['cdn', 'indexnow', 'facebook', 'linkedin', 'twitter', 'google', 'bing'],
            'scheduler_enabled'    => true,
            'api_enabled'          => true,
            'export_enabled'       => true,
        ],
        'enterprise' => [
            'max_urls_per_job'     => PHP_INT_MAX,
            'max_sitemaps'         => PHP_INT_MAX,
            'max_external_sitemaps'=> PHP_INT_MAX,
            'max_jobs_per_day'     => PHP_INT_MAX,
            'log_retention_days'   => 365,
            'cdn_concurrency'      => 20,
            'allowed_targets'      => ['cdn', 'indexnow', 'facebook', 'linkedin', 'twitter', 'google', 'bing'],
            'scheduler_enabled'    => true,
            'api_enabled'          => true,
            'export_enabled'       => true,
        ],
    ];
}
```

### 4.2 UI-Hinweise für gesperrte Features

Gesperrte Features werden **nicht versteckt**, sondern mit einem Upgrade-Hinweis versehen:

```
┌─ Facebook Sharing Debugger ─────────────────────────┐
│  🔒 Verfügbar ab Premium                            │
│                                                      │
│  Aktualisiere OG-Tags im Facebook-Cache für alle    │
│  deine Seiten automatisch.                          │
│                                                      │
│  [Upgrade auf Premium →]                            │
└─────────────────────────────────────────────────────┘
```

### 4.3 Lizenzvalidierung

**Empfehlung:** Lizenzschlüssel-basiertes System mit optionalem Server-Check:

```
Nutzer kauft Lizenz → Erhält Schlüssel → Gibt Schlüssel im Plugin ein
→ Plugin prüft Schlüssel gegen Lizenzserver (1x täglich)
→ Features werden entsprechend freigeschaltet
```

Fallback: Wenn der Lizenzserver nicht erreichbar ist, gilt die letzte bekannte Lizenzstufe für 30 Tage.

---

## 5. Preisempfehlung

### WordPress Plugin

| Version | Preis | Lizenz |
|---------|------:|--------|
| **Free** | 0 € | Unbegrenzt, 1 Site |
| **Premium** | 79 € / Jahr | 1 Site, 1 Jahr Updates + Support |
| **Enterprise Starter** | 499 € / Jahr | Bis 5 Sites, Priority Support |
| **Enterprise Professional** | 1.499 € / Jahr | Bis 25 Sites, Webhooks, White-Label, SLA |
| **Enterprise Corporate** | ab 4.999 € / Jahr | Unbegrenzte Sites, Custom Development, Dedicated Account Manager |

### Drupal Modul

| Version | Preis | Lizenz |
|---------|------:|--------|
| **Free** | 0 € | Unbegrenzt, 1 Site |
| **Premium** | 99 € / Jahr | 1 Site, 1 Jahr Updates + Support |
| **Enterprise Starter** | 699 € / Jahr | Bis 5 Sites, Priority Support |
| **Enterprise Professional** | 1.999 € / Jahr | Bis 25 Sites, Webhooks, White-Label, SLA |
| **Enterprise Corporate** | ab 5.999 € / Jahr | Unbegrenzte Sites, Custom Development, Dedicated Account Manager |

> **Hinweis:** Drupal-Preise sind höher, da der Markt kleiner und die Zielgruppe professioneller ist (Agenturen, Unternehmen, regulierte Branchen).

### Lifetime-Option

| Version | Preis |
|---------|------:|
| **Premium Lifetime** | 199 € (WP) / 249 € (Drupal) |
| **Enterprise Starter Lifetime** | 1.299 € (WP) / 1.799 € (Drupal) |
| **Enterprise Professional Lifetime** | 3.999 € (WP) / 4.999 € (Drupal) |

---

## 6. Zusätzliche Feature-Ideen & Empfehlungen

### 6.1 Hohe Priorität (kurzfristig umsetzbar)

#### a) Warming bei Beitrags-Veröffentlichung (Premium)
Automatisches Warming, wenn ein Beitrag/eine Seite veröffentlicht oder aktualisiert wird.

```
Beitrag veröffentlicht → CacheWarmer wird getriggert
→ CDN + Social Media Caches werden sofort aufgewärmt
→ Beim Teilen auf Facebook/LinkedIn ist die Vorschau sofort korrekt
```

**Begründung:** Das ist eines der wertvollsten Features — Nutzer müssen nicht manuell warming starten nach jeder Veröffentlichung. Großer Differenzierungspunkt gegenüber Konkurrenten.

#### b) Warming-Fortschritt per E-Mail (Premium)
Benachrichtigung per E-Mail, wenn ein Warming-Job abgeschlossen ist oder fehlschlägt.

#### c) Sitemap Auto-Erkennung
Automatische Erkennung der lokalen Sitemap(s) beim ersten Plugin-Start:
- `/sitemap.xml`
- `/sitemap_index.xml`
- Yoast SEO Sitemap
- Rank Math Sitemap
- XML Sitemap Generator Sitemaps

#### d) Bulk-Import für externe Sitemaps
Textarea-Feld, in dem mehrere Sitemap-URLs (eine pro Zeile) gleichzeitig hinzugefügt werden können.

#### e) URL-Blacklist / Exclude-Pattern (Premium)
Regex-basiertes Ausschließen bestimmter URLs vom Warming:
```
# Beispiele
/wp-admin/*
/tag/*
/author/*
*.pdf
```

### 6.2 Mittlere Priorität (mittelfristig)

#### f) Warming-Vorschau / Dry-Run
Sitemap parsen und alle URLs anzeigen, _bevor_ das Warming gestartet wird. Nutzer können URLs an-/abwählen.

#### g) OG-Tag Validator (Premium)
Beim CDN-Warming gleichzeitig die OG-Tags (og:title, og:description, og:image) der Seiten auslesen und im Dashboard anzeigen. Warnung bei fehlenden oder ungültigen Tags.

#### h) Webhook-Benachrichtigungen (Enterprise)
```json
POST https://your-webhook-url.com/cachewarmer
{
  "event": "job.completed",
  "jobId": "abc-123",
  "status": "completed",
  "urlCount": 142,
  "duration": 87000,
  "targets": { "cdn": 142, "facebook": 140, "indexnow": 142 }
}
```

Unterstützte Events: `job.started`, `job.completed`, `job.failed`, `url.failed`

#### i) Warming-Queue Prioritäten (Enterprise)
Jobs können mit Priorität (low / normal / high / critical) versehen werden. High/Critical-Jobs werden bevorzugt verarbeitet.

#### j) Multi-User Rechte (Enterprise / Drupal)
Feingranulare Berechtigungen:
- `cachewarmer.view` — Dashboard ansehen
- `cachewarmer.warm` — Warming starten
- `cachewarmer.manage_sitemaps` — Sitemaps verwalten
- `cachewarmer.configure` — Einstellungen ändern
- `cachewarmer.api` — REST API nutzen

### 6.3 Niedrigere Priorität (langfristig)

#### k) Performance-Monitoring (Enterprise)
Messung der TTFB (Time to First Byte) und Seitenladezeit vor und nach dem Warming. Verlaufsgrafik im Dashboard.

#### l) Wettbewerber-Warming (Enterprise)
Möglichkeit, die Sitemaps von Wettbewerbern zu überwachen und automatisch zu analysieren (nur Monitoring, kein aktives Warming fremder Seiten).

#### m) WP-CLI / Drush Integration
```bash
# WordPress
wp cachewarmer warm --sitemap=https://example.com/sitemap.xml --targets=cdn,facebook

# Drupal
drush cachewarmer:warm --sitemap=https://example.com/sitemap.xml --targets=cdn,facebook
```

#### n) Slack/Discord/Teams Integration (Enterprise)
Benachrichtigungen in Chat-Tools, wenn Jobs abgeschlossen oder fehlgeschlagen sind.

#### o) Cloudflare / Fastly / Varnish Integration
Direkte Integration mit CDN-APIs zum gezielten Cache-Purge _vor_ dem Warming:
1. Cache purgen (Cloudflare API)
2. Seite aufrufen (CDN Warming)
3. Frischer Cache ist aktiv

#### p) AMP & hreflang Support
- AMP-Seiten separat aufwärmen
- Bei hreflang-Tags alle Sprachversionen einer Seite automatisch mit aufwärmen

### 6.4 Architektur-Empfehlungen

#### Freemium-Plugin-Repository Strategie
- **Free-Version** wird auf wordpress.org / drupal.org gehostet (maximale Sichtbarkeit)
- **Premium/Enterprise** als Add-on oder Upgrade über einen eigenen Lizenzserver
- Free-Version enthält den vollständigen Code, aber mit Limit-Checks
- Kein separates „Pro"-Plugin — ein einziges Plugin mit Lizenz-Aktivierung

#### Lizenzserver
Empfehlung: **Eigener Lizenzserver** oder Drittanbieter-Lösung:
- [Freemius](https://freemius.com) — spezialisiert auf WordPress-Plugin-Monetarisierung
- [Easy Digital Downloads](https://easydigitaldownloads.com) — Self-hosted
- [WooCommerce + Software Licensing](https://woocommerce.com)
- Oder: Eigener Microservice (Node.js, passt zur bestehenden Architektur)

---

## Zusammenfassung

```
┌──────────────────────────────────────────────────────────────────┐
│                        FREE                                       │
│  CDN Warming + IndexNow | 50 URLs | 2 Sitemaps | Manuell         │
│  → Sofort nützlich für kleine Websites                            │
├──────────────────────────────────────────────────────────────────┤
│                    PREMIUM (79€/Jahr)                              │
│  + Facebook, LinkedIn, Twitter, Google, Bing                      │
│  + 10.000 URLs | 25 Sitemaps | Scheduler | REST API              │
│  → Für Content-Marketer und SEO-Profis                            │
├──────────────────────────────────────────────────────────────────┤
│                ENTERPRISE STARTER (499€/Jahr)                     │
│  + Bis 5 Sites | Priority Support | Alle Premium-Features        │
│  → Für kleine Agenturen und wachsende Unternehmen                │
├──────────────────────────────────────────────────────────────────┤
│              ENTERPRISE PROFESSIONAL (1.499€/Jahr)                │
│  + Bis 25 Sites | Webhooks | White-Label | SLA                   │
│  → Für mittelgroße Agenturen und Unternehmen                     │
├──────────────────────────────────────────────────────────────────┤
│              ENTERPRISE CORPORATE (ab 4.999€/Jahr)                │
│  + Unbegrenzte Sites | Custom Dev | Dedicated Account Manager    │
│  → Für Großunternehmen, OEM und regulierte Branchen              │
└──────────────────────────────────────────────────────────────────┘
```
