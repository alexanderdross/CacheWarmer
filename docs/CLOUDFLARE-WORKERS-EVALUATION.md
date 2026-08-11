# CacheWarmer auf Cloudflare Workers — Evaluation & Architektur

**Stand:** 2026-08-11 · **Status:** Entscheidungsvorlage, noch nicht umgesetzt

## Ausgangsfrage

Lohnt es sich, CacheWarmer als Cloudflare Worker zu realisieren, statt bzw. neben dem heutigen Node/Docker-Modul? Ziel ist **echtes Warming von Seiten im Cloudflare-CDN** plus Purge/Warm für andere CDNs per API.

**Rahmen:** Nicht kommerziell — nur für eigene Projekte (TradeAero, AIPAero, …). Diese verteilen sich auf **drei getrennte Cloudflare-Accounts** (`trade.aero`, `mail@drossmedia.de`, `alexander.dross@me.com`); nur `mail@drossmedia.de` hat Admin-Rechte auf die einzelnen Accounts. Das Ergebnis ist eine **zusätzliche Komponente**; WordPress-Plugin, Drupal-Modul und Node/Docker-Stack bleiben unangetastet.

Der entscheidende Befund vorab: Der heutige Warmer feuert einen Request ab und protokolliert den HTTP-Status — er prüft **nie**, ob das CDN die Seite tatsächlich gecacht hat. Auf Workers ist genau diese Verifikation trivial. Das, nicht die Performance, ist das eigentliche Argument für den Umbau.

---

## 1. Die Cloudflare-Bausteine

### Browser Run
Das frühere **Browser Rendering**, am 15.04.2026 umbenannt. Headless-Browser auf Cloudflares Netz, zwei Zugriffsarten:

- **Workers Binding** — `puppeteer.launch(env.BROWSER)`, volle CDP-Kontrolle.
- **Quick Actions (REST)** — `/content`, `/screenshot`, `/pdf`, `/markdown`, `/snapshot`, `/scrape`, `/json`, `/links`, `/crawl`.

Limits (Workers Paid): **120 gleichzeitige Browser pro Account**, 1 neue Instanz/Sekunde, REST 10 Req/s. Preis **$0,09 pro Browser-Stunde** plus **$2,00 je zusätzlichem gleichzeitigen Browser** (10 inklusive). Free: 10 Min/Tag, 3 gleichzeitig.

Zwei Details mit direkter Relevanz: Browser Run **rotiert keine IPs** und setzt identifizierende Header (`cf-biso-request-id`, `cf-biso-devtools`). Warming-Traffic ist am Origin damit sauber erkennbar — gut zum Ausfiltern aus Analytics, aber eben auch für Bot-Regeln.

### Kitesurf
Seit **06.08.2026**, in Beta **kostenlos**. Zustandsloser, agent-first Browser, der komplett auf Workers läuft — kein Chromium. **3–7× weniger CPU/RAM** bei Screenshots und HTML-Extraktion. Opt-in per `?browser=kitesurf` an jedem CDP- oder Quick-Action-Endpunkt; bestehende Puppeteer-/Playwright-Clients funktionieren unverändert.

Kann ausdrücklich **nicht**: Video oder WebGL rendern, Bot-Challenges mit echten TLS-Fingerprints verhandeln, langlaufende authentifizierte Sessions halten. Für reines Cache-Warming sind alle drei irrelevant — Kitesurf ist hier die günstigere Wahl, sofern die Seiten damit korrekt rendern.

### Wettbewerb: cache-warmer.com
Kommerzieller SaaS: unbegrenzte Projekte in einer Oberfläche, sofortiges oder geplantes Warming, **wahlweise eigener Crawler oder sitemap.xml**, manuelle URL-Listen, Statistiken pro Lauf, Benachrichtigung über kaputte Seiten. Tarife bis 500.000 Seiten/Monat, 7 Tage Test ohne Kreditkarte. Konkrete Preise nicht verifiziert.

Funktional deckt sich das fast vollständig mit CacheWarmer Free/Premium. Der einzige Punkt, den sie haben und wir nicht: ein **Crawler als Alternative zur Sitemap**. Genau das liefert Browser Run `/crawl` fertig mit — Discovery aus Sitemaps *und* Links, Tiefen-/Seitenlimits, Wildcard-Include/Exclude, `modifiedSince`/`maxAge` für inkrementelles Crawling, `render:false` für Static-Mode, respektiert robots.txt.

---

## 2. Ist-Zustand — was der Code wirklich tut

Verifiziert durch Lesen von `nodejs-docker/src/`, nicht aus der Doku übernommen:

| Bereich | Realität |
|---|---|
| CDN-Warming | Echtes Chromium via `puppeteer-core`, `page.goto(…, {waitUntil:"networkidle0"})`. Zwei Navigationen je URL (Desktop-UA, dann 375×812 Mobile-UA) auf derselben Page. |
| Nebenläufigkeit | Feste Batch-Slices `urls.slice(i, i+3)` + `Promise.all` — eine langsame URL blockiert den ganzen Batch. |
| Cache-Verifikation | **Keine.** `cf-cache-status`/`x-cache`/`age` werden ausgelesen und als JSON abgelegt, aber Erfolg = HTTP 2xx–3xx. Kein zweiter Request, keine HIT-Prüfung, keine Hit-Rate. Einziger Konsument ist ein `/HIT/i`-Regex für eine Badge-Farbe in `JobDetail.tsx`. |
| Job-Queue | **BullMQ und ioredis stehen in `package.json`, werden aber nirgends importiert.** Ausführung ist ein Fire-and-Forget-Promise in der Next.js-Route; Dedup über ein prozesslokales `Set`. Nach einem Neustart hängen Jobs dauerhaft auf `running`. Redis im `docker-compose.yml` ist reine Attrappe. |
| Reihenfolge | `cdn-purge` läuft als **letztes** Target — also *nach* dem Warming. Der Purge wirft den gerade aufgebauten Cache weg. |
| Priority-Warming | Toter Code: `urls` wird aus `sitemapUrls` kopiert, *danach* wird `sitemapUrls` sortiert. Die Sortierung erreicht den Warmer nie. |
| Akamai EdgeGrid | Handgeschrieben. Der Timestamp entfernt via `replace(/[-:]/g,"")` auch die Doppelpunkte der Uhrzeit; die Spec verlangt `yyyyMMddTHH:mm:ss+0000`. Signatur dürfte gegen die echte API scheitern — vor Nutzung testen. |
| Cloudflare-Purge | Batchgröße 30 fest verdrahtet. Cloudflare erlaubt **100 Operationen pro Request** (500 auf Enterprise). |
| Mehrere Zonen | `config.yaml` kennt genau eine `cloudflare.zoneId`. Für TradeAero + AIPAero + … reicht das nicht. |
| Schema-Validierung | Lädt jede Seite **ein zweites Mal** über `structured-data-testing-tool` (eigener HTTP-Client), parallel zur Chromium-Navigation. |

Zwei Beobachtungen dazu: Der Desktop/Mobile-Doppelpass ist wahrscheinlich Verschwendung — Cloudflare cached standardmäßig **nicht** nach User-Agent, beide Requests treffen also dasselbe Cache-Objekt. Zufällig wirkt der zweite Pass damit wie eine Verifikation — nur wird das Ergebnis nirgends ausgewertet.

### Das Badge ist semantisch verkehrt herum

`JobDetail.tsx:50` färbt `HIT` grün und alles andere gelb. Für einen *Warmer* ist das rückwärts: Auf dem Füll-Request nach einem Purge ist **MISS das Erfolgssignal** — du hast den Cache gerade befüllt. `HIT` heißt, jemand war schneller und der Job hat nichts bewirkt. Solange es keine Trennung zwischen Füll- und Prüf-Request gibt, kann das Badge ohnehin nichts Belastbares aussagen; es zeigt einfach den Header des letzten Requests.

### Und das betrifft nicht nur das Node-Modul

Zwei weitere verifizierte Defekte in den anderen Editionen:

- **WordPress: die Concurrency-Einstellung tut nichts.** `class-cachewarmer-cdn-warmer.php:88` chunked mit `array_chunk( $urls, $this->concurrency )` — und iteriert die Chunks dann mit einer **sequentiellen** inneren `foreach`. Es gibt keinerlei Parallelität; das Chunking ist wirkungslos. Die als Premium/Enterprise verkaufte "CDN Concurrency (1–20)" ist in WordPress ein reiner Anzeigewert.
- **Akamai: die Propagationszeit wird verworfen.** Die API liefert `estimatedSeconds` zurück; `cdn-purge-warm.ts:301` schreibt den Wert ins Log und ignoriert ihn. Genau diese Zahl bräuchte man, um vor dem Re-Warming korrekt zu warten.

Bemerkenswert nebenbei: Das WordPress-Plugin warmt mit `wp_remote_get()`, das Drupal-Modul mit Guzzle — **nur die Node-Edition startet überhaupt einen Browser**, verkauft wird in allen dreien dasselbe Feature. Zwei von drei ausgelieferten Implementierungen belegen also bereits, dass Chromium für CDN-Warming nicht nötig ist.

---

## 3. Was "echtes Warming" auf Cloudflare bedeutet

Drei Fakten aus der Cloudflare-Doku:

**a) Ein Worker-`fetch()` läuft durch den Cache.** Wörtlich: *"When a Worker calls `fetch()`, the request passes through Cloudflare's cache and Tiered Cache (if enabled)."* Steuerbar über das `cf`-Objekt: `cacheEverything`, `cacheTtl`, `cacheTtlByStatus`, `cacheTags`, `cacheKey` (Enterprise). Ein Worker kann den CDN-Cache also tatsächlich füllen — mit einer Kontrolle, die ein externer HTTP-Client schlicht nicht hat.

**b) Der Cache ist pro Rechenzentrum.** Lower Tier je Datacenter, darüber ein Upper Tier, das Fills aggregiert. Wer aus *einem* Ort warmt, füllt einen Lower Tier plus den Upper Tier. Besucher in anderen Regionen bekommen weiterhin lokal MISS — holen sich die Seite aber aus dem Upper Tier statt vom Origin. Das ist der Grund, warum das heutige Warming aus einem einzigen Docker-Container überhaupt etwas bringt, und zugleich seine Obergrenze.

**c) Zonenfremdes Warming ist nicht privilegiert.** *"Workers operating on behalf of different zones cannot affect each other's cache… that zone fully controls how its own content is cached within Cloudflare; you cannot override it."*

**Punkt (c) greift hier — und zwar voll.** `trade.aero`, `mail@drossmedia.de` und `alexander.dross@me.com` sind **getrennte Cloudflare-Accounts**; nur `mail@drossmedia.de` hat Admin-Rechte auf die einzelnen Accounts. Für Workers und Cache zählt aber die **Account-Grenze, nicht die Benutzerberechtigung** — ein Admin-Login über mehrere Accounts führt sie nicht zusammen. Ein Worker in Account A, der eine Zone in Account B warmt, ist aus Cache-Sicht ein ganz gewöhnlicher externer Request.

Entscheidend ist, was (c) genau verbietet. Es beschränkt die **Steuerung**, nicht das Warming:

| Operation | Über Account-Grenze hinweg? |
|---|---|
| Seite warmen (Cache-Fill auslösen) | **Ja** — der Request läuft durch die Zone und füllt deren Cache nach deren Regeln |
| `cf-cache-status` lesen, also verifizieren | **Ja** — ein gewöhnlicher Response-Header |
| Purge per API | **Ja** — ein API-Token von `mail@drossmedia.de` kann alle Accounts abdecken, auf die der Benutzer Zugriff hat |
| `cacheEverything`, `cacheTtl`, `cacheKey`, `cacheTags` setzen | **Nein** — das `cf`-Objekt wird an der Grenze verworfen |
| Service Bindings zu einem Worker im anderen Account | **Nein** — Service Bindings sind account-intern |

Die praktische Konsequenz ist angenehmer, als sie klingt: **Purge, Warming und Verifikation funktionieren accountübergreifend aus einem einzigen Worker.** Nur die Feinsteuerung des Cache-Fills braucht einen Worker im jeweiligen Account.

### Drei Stufen — und nur eine davon ist neu

Aus (b) folgt eine Ehrlichkeitsstufung, die man vor dem Bauen festhalten sollte:

| Stufe | Was sie garantiert | Wie erreichbar | Realer Wert |
|---|---|---|---|
| **L1 Origin-Schutz** | Upper Tier gefüllt, Origin entlastet | Ein Warm-Request von irgendwo | Hoch — und **der heutige Docker-Stack liefert das bereits.** |
| **L2 Regionale Edge** | Lower Tier in N Regionen warm | Fan-out aus N Regionen | Wandelt einen Upper-Tier-Hit (~100–250 ms) in einen Lower-Tier-Hit (~10–30 ms). Real, aber inkrementell. **Nur Workers/DO können das.** |
| **L3 Globale Edge** | Jedes Colo warm | Nicht erreichbar | Gibt es nicht. |

Das ist der wichtigste Dämpfer dieser Evaluation: **L2 ist eine Latenzoptimierung auf einem bereits schnellen Pfad, keine Beseitigung von Misses.** Mit aktivem Tiered Cache hat der L1-Warm den Origin-Roundtrip schon eliminiert. Multi-Region kauft nur die Differenz zwischen Upper- und Lower-Tier-Hit — und ob die spürbar ist, muss gemessen werden (Verifikationsschritt 3), bevor man Aufwand hineinsteckt.


### Die eigentliche Erkenntnis: für Cache-Warming braucht es keinen Browser

Ein CDN cached die **Antwort auf einen GET**. Ein Browser fügt genau eine Sache hinzu: er lädt die Subresources (CSS, JS, Bilder, Fonts) und warmt damit auch die. Das ist der einzige echte Mehrwert von Puppeteer beim Warming — und er lässt sich auf Workers deutlich billiger haben:

```
fetch(html) → HTMLRewriter parst <link>, <script>, <img>, <source> → fetch() dieser Assets
```

`HTMLRewriter` ist ein Streaming-Parser in der Workers-Runtime, kostet praktisch keine CPU-Zeit und braucht kein DOM. Das liefert ~95 % des Browser-Nutzens zu ~0 % der Browser-Kosten. Browser Run bleibt nur für den Rest nötig: clientseitig gerenderte Seiten (SPA), lazy-loaded Assets, Above-the-fold-Screenshots als Qualitätsbeleg.

### Und die Verifikation, die heute fehlt

Auf Workers wird aus "warm" ein prüfbares Ergebnis:

```
1. fetch(url, {cf:{cacheEverything:true, cacheTtl:…}})   → Fill
2. fetch(url)                                             → cf-cache-status auslesen
3. HIT  → verified
   MISS → wirklich nicht cachebar (Set-Cookie, no-store, Bypass-Regel) → melden
```

Damit wird aus "200 URLs angefragt" ein "187 von 200 nachweislich im Edge-Cache, 13 nicht cachebar — hier sind sie". Das ist der Unterschied zwischen einem Warmer und einem Cache-Audit, und der stärkste Grund für den Umbau.

> Empirisch zu bestätigen: ob `cf-cache-status` auf einem Worker-Subrequest zur eigenen Zone zuverlässig gesetzt ist. Ein 10-Zeilen-Worker gegen eine Testzone klärt das in Minuten — **vor** dem Rest bauen.

---

## 4. Bewertung: Ist-Zustand vs. Workers

| Kriterium | Docker + Puppeteer (heute) | Worker |
|---|---|---|
| Cache-Fill | Externer GET, keine Steuerung | `fetch()` mit `cacheEverything`/`cacheTtl`/`cacheTags` |
| Verifikation | Keine | HIT-Assertion, praktisch gratis |
| Geografische Abdeckung | 1 Standort, 1 Egress-IP | Mehrere Regionen via DO-`locationHint` (wnam, enam, weur, eeur, apac, apac-ne, apac-se, oc — sam/afr/me weichen aus) |
| Nebenläufigkeit | 3 (RAM-gebunden) | Hunderte parallele `fetch()`; Subrequests werden **nicht berechnet** |
| Durchsatzgrenze | Chromium-RAM | 10.000 Subrequests/Invocation (auf 10 Mio. konfigurierbar) |
| Laufzeit | Unbegrenzt | Cron/Queue/DO-Alarm: **15 Min** → Workflows für längere Läufe |
| Persistenz | SQLite, an einen Host gebunden | D1 |
| Job-Durability | Keine (Restart = hängende Jobs) | Queues/Workflows mit Retry |
| Betrieb | VPS, Docker, Chromium-Updates | Kein Server |
| Schema-Validierung | `structured-data-testing-tool` (Node-only) | **Muss neu geschrieben werden** (HTMLRewriter + JSON-LD-Parser) |
| Free-Tier-Tauglichkeit | Uneingeschränkt | Nur 50 externe Subrequests/Invocation → Paid ($5/Mon.) faktisch Pflicht |

### Kosten

| Volumen | Worker (`fetch` + HTMLRewriter) | Worker + Kitesurf | Worker + Browser Run | VPS Docker |
|---|---|---|---|---|
| 1.000 URLs/Mon. | $0 (im $5-Sockel) | $0 (Beta) | ~$0,08 | €5–20/Mon. |
| 10.000 URLs/Mon. | $0 | $0 (Beta) | ~$0,75 | €5–20/Mon. |
| 100.000 URLs/Mon. | $0 | $0 (Beta) | ~$7,50 | €10–40/Mon. |

Basis: $0,09/Browser-Stunde, ~3 s je Seite. Der Duration-Anteil ist marginal — der Kostentreiber bei Browser Run ist die **Concurrency** ($2,00 je Browser über die 10 inklusive; 30 parallel = +$40/Mon.). Anders gesagt: mit den 10 inklusive Browsern dauern 10.000 Seiten ~50 Minuten, was für einen Nachtlauf reicht und nichts extra kostet — man muss den Lauf nur über Workflows stückeln, weil ein Cron-Trigger nach 15 Minuten endet.

Reines `fetch()`-Warming ist innerhalb des $5-Sockels effektiv kostenlos, weil Cloudflare Subrequests nicht berechnet und Wartezeit auf Netz nicht als CPU-Zeit zählt.

### Nicht der Preis entscheidet, sondern der Durchsatz

Alle Optionen liegen zwischen $0 und ~$60/Monat. Bei diesen Beträgen ist Kosten kein Argument — wer den Umbau mit Ersparnis begründet, erzählt die falsche Geschichte.

Der reale Unterschied ist die Laufzeit. Der heutige Stack fährt Concurrency 3 mit **zwei** sequentiellen `networkidle0`-Navigationen je URL, also grob 1.500 URLs/Stunde — **100.000 URLs dauern rund 64 Stunden**, und weil die Batches über `urls.slice(i, i+3)` + `Promise.all` laufen, blockiert eine hängende URL ihren gesamten Dreierblock. Ein `fetch()`-basierter Motor mit hoher Parallelität erledigt denselben Lauf in unter einer Stunde. Das — zusammen mit Durability und Multi-Region — ist der Fall für Workers.

---

## 5. Empfohlene Architektur

**Neue, eigenständige Komponente `cloudflare-worker/` im Monorepo.** Kein Ersatz, keine gemeinsame Codebasis mit `nodejs-docker/` — die Runtimes sind zu verschieden, ein geteilter Layer würde beide Seiten verbiegen.

### Hub-and-Satellite statt Deploy-überall

Weil Warming, Verifikation und Purge die Account-Grenze überqueren und nur die `cf`-Feinsteuerung nicht, ist ein Deploy in *jeden* Account unnötiger Aufwand. Besser gestuft:

**Stufe 1 — nur der Hub (Start hier).** Ein einziger Worker in `mail@drossmedia.de`: Orchestrierung, D1, Reporting, Purge für alle Accounts über ein Admin-API-Token, plus L1-Warming und Verifikation für *alle* Zonen. Deckt den überwiegenden Teil des Nutzens ab, kostet einmal $5/Monat, und es gibt genau einen Ort für Code, Zustand und Secrets.

**Stufe 2 — Satelliten nur dort, wo es sich messbar lohnt.** Ein baugleicher Worker im jeweiligen Account, wenn eine Zone `cacheEverything`/`cacheTtl`-Kontrolle oder regionalen Lower-Tier-Fill (L2) wirklich braucht. Der Satellit meldet Ergebnisse per HTTPS an den Hub zurück — Service Bindings gehen über Account-Grenzen nicht, also ein Endpunkt mit Shared Secret im Worker-Secret.

Operativ macht **Wrangler Auth Profiles** (seit Juli 2026) das sauber: je Account ein Profil, an ein Verzeichnis gebunden, plus `account_id` in der Wrangler-Config als Fehlgriff-Sicherung — damit kann ein `deploy` nicht im falschen Account landen.

```sh
wrangler auth create trade-aero
wrangler auth activate trade-aero ./cloudflare-worker/deploy/trade-aero
```

Was bei getrennten Accounts zu beachten ist:

- **Workers Paid gilt pro Account** — $5/Monat je Satellit. Nur-Hub bleibt bei $5.
- **Browser-Run-Kontingente sind pro Account** (10 gleichzeitige Browser, 10 Std./Monat inklusive). Getrennte Accounts heißen getrennte Töpfe — mehr Parallelität, aber auch mehrere Rechnungen.
- **Purge-Ratenlimits sind pro Account**, hier also ein Vorteil: kein gemeinsamer Bucket, keine Konkurrenz zwischen den Projekten.
- **D1 lebt in einem Account.** Zentral im Hub halten, nicht je Satellit — sonst zerfällt das Reporting.

```
Cron Trigger (nachts)
   └─→ Workflow "warm-site"            ← überlebt den 15-Min-Deckel, Retry pro Step
        ├─ Step 1  Sitemap holen + parsen        (fetch + HTMLRewriter/XML)
        │          alternativ: Browser Run /crawl für sitemap-lose Seiten
        ├─ Step 2  Purge (optional, ZUERST)      → CF-Zone-API, Batch 100
        ├─ Step 3  Fan-out Warming
        │          ├─ Region-DOs (locationHint) → je Region fetch() pro URL
        │          ├─ HTMLRewriter → Asset-URLs → fetch()
        │          └─ Browser Run/Kitesurf nur für als "SPA" markierte Pfade
        ├─ Step 4  Verify: 2. fetch() → cf-cache-status HIT/MISS
        └─ Step 5  Ergebnisse → D1, Report
```

Die wesentlichen Entscheidungen:

- **Workflows statt Queues** als Orchestrator. Der 15-Minuten-Deckel gilt pro Invocation, nicht pro Workflow; jeder Step bekommt eigenes Retry, und der Lauf übersteht Deploys. **Step-Granularität: ein Step je Batch von 50–100 URLs, nicht je URL** — das Step-Limit liegt bei 10.000 (max. 25.000), ein Step pro URL würde also bei 10k URLs anschlagen. Mit 50 URLs je Step landet man bei ~600 Subrequests pro Step (2 Verify-Requests + ~10 Assets je URL), deutlich unter dem 10.000er-Limit. `step.sleep` ist das richtige Mittel für die Purge-Propagation; schlafende Instanzen zählen nicht gegen Concurrency-Limits.
- **Purge vor Warm.** Behebt den Reihenfolgefehler des Ist-Zustands. Purge-Batch auf 100 statt 30 (Cloudflare-Maximum, 500 auf Enterprise). Ratenlimit beachten: Business 10 Req/s, Free nur 5/Min.
- **Ein Durable Object je Zielregion**, per `locationHint` platziert, macht das Warming von dort. Das ist der Punkt, den der Docker-Container prinzipiell nicht kann. `locationHint` ist Best-Effort — als "mehrere Regionen statt einer" verstehen, nicht als Garantie.
- **`fetch()` + HTMLRewriter als Standardpfad.** Browser Run nur pro Pfadmuster opt-in. Wenn ein Browser nötig ist, zuerst Kitesurf (Beta gratis, 3–7× günstiger) mit Fallback auf Chromium, wenn das Rendering nicht trägt.
- **D1 für den Zustand**, Schema 1:1 aus `nodejs-docker/src/lib/db/database.ts` übernehmen (`sitemaps`, `jobs`, `url_results`, `schema_results`) plus zwei Spalten in `url_results`: `verified_status` (hit/miss/unknown) und `warm_region`.
- **Multi-Account und Multi-Zone von Anfang an**: Konfiguration als Liste `{account, accountId, zone, zoneId, sitemap, regions[], browserPaths[]}` — nicht die eine `zoneId` aus `config.yaml` nachbauen. Das Purge-Token wird je Account nachgeschlagen.
- **Fremd-CDNs** (Imperva, Akamai, Fastly) bleiben reine API-Aufrufe und funktionieren per `fetch()` unverändert. Fastly wäre billig nachzurüsten (`POST /service/{id}/purge/{key}` mit `Fastly-Key`).

### Zwei Fallen bei der Akamai-Portierung

EdgeGrid nach WebCrypto zu portieren ist mechanisch — aber zwei Details erzeugen stillschweigend 401er:

1. **Den Timestamp-Bug nicht mitportieren.** Korrekt ist `yyyyMMddTHH:mm:ss+0000`, die Doppelpunkte der Uhrzeit bleiben erhalten.
2. **Das Schlüsselmaterial des zweiten HMAC nicht dekodieren.** Der Node-Code macht:
   ```ts
   const signingKey = createHmac("sha256", clientSecret).update(timestamp).digest("base64");
   const signature  = createHmac("sha256", signingKey).update(dataToSign).digest("base64");
   ```
   Der zweite Aufruf bekommt `signingKey` als **Base64-String**, und Node schlüsselt auf den UTF-8-Bytes *dieses Strings* — nicht auf den 32 dekodierten Rohbytes. Eine naive Portierung, die vor `importKey` erst base64-dekodiert, erzeugt eine andere Signatur und scheitert an der Authentifizierung. Also `new TextEncoder().encode(signingKeyBase64String)` importieren.

   `crypto.randomUUID()` gibt es auf Workers, die Nonce ist also unkritisch; umzustellen sind nur die synchronen `node:crypto`-Aufrufe auf async `crypto.subtle`.

### Was nicht mitkommt

- **`structured-data-testing-tool`** läuft nicht auf Workers (Node-HTTP-Client). Schema-Validierung wäre eine Neuimplementierung: JSON-LD via HTMLRewriter aus `<script type="application/ld+json">` ziehen und gegen ein eigenes Regelwerk prüfen. **Empfehlung: im ersten Schritt weglassen.** Das Docker-Modul kann das weiterhin; mit Cache-Warming hat es ohnehin nichts zu tun.
- **`googleapis`** (Node) — JWT-Signierung für die Indexing API müsste über WebCrypto neu gebaut werden (~50 Zeilen RS256). Ebenfalls Phase 2; Social- und Search-Targets sind kein Cloudflare-Thema.
- **Die Next.js-UI.** Für den Eigenbedarf reicht anfangs ein `GET /report`-Endpunkt oder Workers Logs.

---

## 6. Risiken und offene Punkte

### Was gar nicht funktioniert

- **`caches.default` ist nicht der CDN-Cache.** Die Cache API arbeitet nur auf dem Rechenzentrum, das den aktuellen Request bedient, und interagiert ausdrücklich **nicht** mit dem Tiered Cache. Sie zum Warmen einer Seite zu benutzen ist ein vollständiger No-Op — die naheliegendste Falle im ganzen Entwurf.
- **Es gibt keine Pre-Population-API.** Laut Doku: *"A response is only cached once it has been served at least once."* Warming muss ein echter Request sein.
- **Workers Free scheidet aus** — 50 externe Subrequests pro Invocation machen Batch-Warming strukturell unmöglich.
- **`structured-data-testing-tool` läuft nicht auf Workers.** JSON-LD ließe sich per HTMLRewriter extrahieren, Microdata und RDFa nicht. Schema-Validierung bleibt im Node-Modul.

### Risiken

| Risiko | Umgang |
|---|---|
| `cf-cache-status` auf Worker-Subrequests unzuverlässig | **Zuerst empirisch prüfen** — trägt die gesamte Verifikationsidee |
| Browser Run wird vom Origin als Bot erkannt und liefert eine Bot-Variante | Die würde dann **im Cache landen und echten Besuchern ausgeliefert**. Vor Einsatz gegen eine echte Seite gegenprüfen. |
| Purge-Ratenlimits gelten **pro Account**, nicht pro Job | Bei mehreren Zonen im selben Account ein globaler Token-Bucket (ein DO oder das native Rate-Limiting-Binding) — der feste `delay(500)` von heute ist kein Limiter |
| D1 schreibt single-region; mehrere Regions-DOs schreiben quer | Im DO puffern, per `D1.batch()` je Step flushen |
| Zonen liegen in drei getrennten Accounts | Hub-Worker deckt Warming, Verifikation und Purge accountübergreifend ab; Satelliten nur bei nachgewiesenem Bedarf. Admin-Rechte ersetzen die Account-Grenze **nicht**. |
| Purge-Token muss drei Accounts abdecken | API-Token von `mail@drossmedia.de` mit `Zone:Cache Purge` über alle drei Accounts scopen — Tokens sind benutzer-, nicht accountgebunden |
| `locationHint` ist Best-Effort, sam/afr/me weichen aus | Tatsächliche Colo über `request.cf.colo` protokollieren |
| Warming-Requests verzerren Analytics | Eigener User-Agent + Header; Browser Run ist über `cf-biso-*` ohnehin markiert |
| Kitesurf ist Beta — Preis und Verfügbarkeit können kippen | Als optionales Backend hinter einem Schalter, Chromium-Fallback |
| Selbstverstärkung: Worker auf derselben Zone fetcht die Zone | Warming-Worker auf eigener Route/Subdomain deployen, nicht auf der Zonen-Route |
| Cache-Fill zählt nicht unter allen Cache Rules als Fill | Bei Bypass-Regeln oder `Set-Cookie` bleibt es MISS — genau das soll der Report zeigen |

---

## 7. Verifikationsplan

Reihenfolge ist wichtig — Schritt 1 entscheidet, ob der Rest sinnvoll ist.

1. **Spike (halber Tag).** Minimal-Worker auf einer eigenen Zone: `fetch(url, {cf:{cacheEverything:true}})`, dann zweiter `fetch(url)`, `cf-cache-status` und `request.cf.colo` loggen. Erwartung: zweiter Request meldet HIT. **Trägt das nicht, ist der Hauptnutzen weg — dann nur die Bugs in Abschnitt 8 fixen.**
2. **Accountübergreifender Fill — der Gating-Test.** Zone in Account B purgen, aus dem Hub-Worker in `mail@drossmedia.de` warmen, erneut abrufen und auf HIT prüfen. **Scheitert das, fällt der Hub-Ansatz weg und es braucht einen Satelliten je Account** — der Unterschied zwischen einem Deploy und dreien.
3. **Tier-Delta messen.** TTFB derselben URL in drei Zuständen vergleichen: kalt, nur Upper Tier warm (L1), Lower Tier warm (L2). **Das ist die Zahl, die entscheidet, ob Multi-Region überhaupt Aufwand verdient.** Fällt sie klein aus, reicht der Hub allein und Abschnitt 5 Stufe 2 entfällt ersatzlos.
4. **Regionstest.** Zwei DOs mit `locationHint: "weur"` und `"enam"`, dieselbe URL warmen, tatsächliches Colo über das `cf-ray`-Suffix vergleichen. Belegt, dass die Regionsverteilung real ist und nicht nur angefragt.
5. **Purge→Warm→Verify** über 20 URLs einer echten Zone: nach dem Lauf müssen alle 20 HIT melden. Dabei die nötige Wartezeit zwischen Fill und Probe kalibrieren (0 / 100 ms / 500 ms / 1 s).
6. **Assetpfad.** HTMLRewriter-Extraktion gegen eine echte Seite; Zahl gefundener Assets mit dem DevTools-Netzwerk-Tab abgleichen.
7. **Lasttest.** 1.000 URLs in einem Workflow — Subrequest-Verbrauch und Laufzeit gegen die Limits messen, bevor `limits.subrequests` hochgesetzt wird.
8. **Vergleich.** Denselben Sitemap-Lauf durch Docker-Version und Worker schicken; Dauer und Zahl verifizierter HITs gegenüberstellen.

---

## 8. Vorher: Fixes am Ist-Zustand

Diese Defekte bestehen unabhängig von der Workers-Entscheidung, sind billig zu beheben und wirken in **allen drei Editionen** — auch für Projekte, die nie auf Cloudflare laufen:

1. `cdn-purge` **vor** dem CDN-Warming ausführen (`nodejs-docker/src/lib/queue/job-manager.ts:191` vs. `:279`). Aktuell zerstört der Purge den gerade aufgebauten Cache.
2. Priority-Sortierung reparieren — `sitemapUrls` sortieren, *bevor* `urls` daraus abgeleitet wird (`:148` / `:163`).
3. **WordPress-Concurrency echt machen** — `class-cachewarmer-cdn-warmer.php:88` chunked, iteriert aber sequentiell. Ohne `Requests::request_multiple()` o. ä. ist die Einstellung wirkungslos.
4. Akamai-EdgeGrid-Timestamp auf `yyyyMMddTHH:mm:ss+0000` korrigieren.
5. Akamais `estimatedSeconds` auswerten statt nur zu loggen (`cdn-purge-warm.ts:301`) — vor dem Re-Warming so lange warten.
6. Cloudflare-Purge-Batch von 30 auf 100 anheben.
7. `bullmq` und `ioredis` aus `package.json` entfernen, Redis aus `docker-compose.yml` — oder tatsächlich verwenden. Aktuell nur irreführender Ballast.
8. Warm-then-Verify einführen und das Badge korrigieren: MISS auf dem Füll-Request ist der Erfolg, nicht der Fehler.
9. Doppelten Fetch in `schema-validator.ts` abschaffen — das bereits geladene HTML weiterreichen statt jede Seite ein zweites Mal zu holen.

---

## 9. Empfehlung in Reihenfolge

1. **Abschnitt 8 abarbeiten.** Höchster Nutzen pro Aufwand, keine Cloudflare-Abhängigkeit, wirkt auf alle Editionen.
2. **Puppeteer für CDN-Warming durch `fetch()` + HTMLRewriter ersetzen** — im Node-Modul. Nimmt den Chromium-Speicherboden weg und beschleunigt um Größenordnungen. Puppeteer bleibt nur für die Social-Warmer, den einzigen legitimen Browser-Bedarf.
3. **Spike fahren** (Verifikationsschritte 1–4). Erst danach entscheiden.
4. **Falls der Spike trägt: Worker als zusätzlicher Motor** in `cloudflare-worker/`, Workflows als Spine, Regions-DOs für den Fan-out.

Die unbequeme Zusammenfassung: Der Workers-Umbau ist ein legitimes *Feature* — Multi-Region-Fill und belastbare Verifikation — aber er repariert nicht, was heute kaputt ist. Ihn vor den Schritten 1 und 2 zu bauen würde die Bugs nur in eine neue Runtime umziehen.

---

## Quellen

- [Cloudflare Browser Run](https://developers.cloudflare.com/browser-run/) · [Kitesurf](https://developers.cloudflare.com/browser-run/kitesurf/) · [Quick Actions](https://developers.cloudflare.com/browser-run/quick-actions/) · [/crawl](https://developers.cloudflare.com/browser-run/quick-actions/crawl-endpoint/)
- [Cache & Workers](https://developers.cloudflare.com/cache/interaction-cloudflare-products/workers/) · [Cache using fetch](https://developers.cloudflare.com/workers/examples/cache-using-fetch/) · [Tiered Cache](https://developers.cloudflare.com/cache/how-to/tiered-cache/)
- [Workers Limits](https://developers.cloudflare.com/workers/platform/limits/) · [Workflows Limits](https://developers.cloudflare.com/workflows/reference/limits/) · [Durable Objects Data Location](https://developers.cloudflare.com/durable-objects/reference/data-location/)
- [Purge Cache Limits](https://developers.cloudflare.com/cache/how-to/purge-cache/)
- [cache-warmer.com](https://www.cache-warmer.com/)
