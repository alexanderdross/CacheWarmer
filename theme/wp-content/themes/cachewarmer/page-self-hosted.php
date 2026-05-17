<?php
/**
 * Template Name: Self-Hosted
 * Self-hosted Node.js platform page.
 */
$page_og_title = 'CacheWarmer Self-Hosted - Node.js Cache Warming Service';
$page_description = 'Self-hosted Node.js cache warming service with REST API, job queue, and React dashboard. Deploy via Docker or install from source.';
get_header();
cachewarmer_breadcrumb('Self-Hosted');
?>

<!-- Hero -->
<section class="page-hero">
    <div class="container">
        <div class="flex items-center justify-center gap-4 mb-4">
            <?php cachewarmer_icon('server', '', 40); ?>
        </div>
        <h1>Self-Hosted CacheWarmer</h1>
        <p>A standalone Node.js microservice with REST API, BullMQ job queue, SQLite database, and React dashboard. Deploy via Docker or install from source.</p>
    </div>
</section>

<!-- Quick Start -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Deploy with <span class="text-gradient">Docker</span></h2>
        </div>
        <div class="grid grid-3 gap-8">
            <?php
            cachewarmer_step(1, 'Pull the Image', 'Pull the Docker image and create your configuration file with API keys and warming targets.');
            cachewarmer_step(2, 'Start the Service', 'Run the container with Docker Compose. The service starts with Redis for the job queue and SQLite for data storage.');
            cachewarmer_step(3, 'Use the API', 'Submit sitemaps via the REST API or open the React dashboard to monitor warming jobs in real-time.');
            ?>
        </div>
        <div class="text-center mt-8">
            <?php cachewarmer_code_block('# docker-compose.yml
version: "3.8"
services:
  cachewarmer:
    image: drossmedia/cachewarmer:latest
    ports:
      - "3000:3000"
    volumes:
      - ./data:/app/data
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
  redis-data:', 'YAML'); ?>
        </div>
    </div>
</section>

<!-- Architecture -->
<section class="section section-gray">
    <div class="container">
        <div class="section-header">
            <h2>Clean, Modular <span class="text-gradient">Architecture</span></h2>
            <p>Built with TypeScript, Fastify, and BullMQ for reliability and performance.</p>
        </div>
        <div class="home-architecture" role="figure" aria-label="Architecture diagram">
            <div class="arch-diagram">
                <div class="arch-box arch-box-primary">
                    <?php cachewarmer_icon('sitemap', '', 18); ?> XML Sitemap
                </div>
                <div class="arch-connector"></div>
                <div class="arch-box arch-box-muted">
                    <?php cachewarmer_icon('settings', '', 18); ?> Sitemap Parser
                </div>
                <div class="arch-connector"></div>
                <div class="arch-box arch-box-accent">
                    <?php cachewarmer_icon('queue', '', 18); ?> Job Queue (BullMQ + Redis)
                </div>
                <div class="arch-connector"></div>
                <div class="arch-workers">
                    <div class="arch-worker"><?php cachewarmer_icon('file-check', '', 16); ?> Schema</div>
                    <div class="arch-worker"><?php cachewarmer_icon('globe', '', 16); ?> CDN</div>
                    <div class="arch-worker"><?php cachewarmer_icon('facebook', '', 16); ?> Facebook</div>
                    <div class="arch-worker"><?php cachewarmer_icon('linkedin', '', 16); ?> LinkedIn</div>
                    <div class="arch-worker"><?php cachewarmer_icon('twitter', '', 16); ?> Twitter/X</div>
                    <div class="arch-worker"><?php cachewarmer_icon('pinterest', '', 16); ?> Pinterest</div>
                    <div class="arch-worker"><?php cachewarmer_icon('zap', '', 16); ?> IndexNow</div>
                    <div class="arch-worker"><?php cachewarmer_icon('search', '', 16); ?> Google</div>
                    <div class="arch-worker"><?php cachewarmer_icon('search', '', 16); ?> Bing</div>
                    <div class="arch-worker"><?php cachewarmer_icon('cloudflare', '', 16); ?> Cloudflare</div>
                    <div class="arch-worker"><?php cachewarmer_icon('imperva', '', 16); ?> Imperva</div>
                    <div class="arch-worker"><?php cachewarmer_icon('akamai', '', 16); ?> Akamai</div>
                </div>
                <div class="arch-connector"></div>
                <div class="arch-box arch-box-muted">
                    <?php cachewarmer_icon('database', '', 18); ?> SQLite Database
                </div>
                <div class="arch-connector"></div>
                <div class="arch-box arch-box-primary">
                    <?php cachewarmer_icon('terminal', '', 18); ?> React Dashboard
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section section-white">
    <div class="container">
        <div class="section-header">
            <h2>Built for <span class="text-gradient">Developers</span></h2>
        </div>
        <div class="grid grid-3 gap-6">
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('cpu'); ?></div>
                <h3 class="card-title">REST API</h3>
                <p class="card-description">Full REST API built on Fastify. Submit sitemaps, trigger warming jobs, check status, and retrieve logs programmatically.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('queue'); ?></div>
                <h3 class="card-title">BullMQ Job Queue</h3>
                <p class="card-description">Reliable job processing with Redis-backed BullMQ. Built-in rate limiting, retries with exponential backoff, and priority scheduling.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('terminal'); ?></div>
                <h3 class="card-title">React Dashboard</h3>
                <p class="card-description">Web-based dashboard for monitoring warming jobs, viewing logs, and managing sitemaps. Real-time updates via Server-Sent Events.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('docker'); ?></div>
                <h3 class="card-title">Docker Ready</h3>
                <p class="card-description">Pre-built Docker images with Chromium included. Docker Compose configuration for production deployments. Single command to start.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('database'); ?></div>
                <h3 class="card-title">SQLite Storage</h3>
                <p class="card-description">No external database server required. SQLite stores all job data, results, and logs. Lightweight and zero-maintenance.</p>
            </div>
            <div class="card">
                <div class="card-icon"><?php cachewarmer_icon('clock'); ?></div>
                <h3 class="card-title">Cron Scheduler</h3>
                <p class="card-description">Built-in cron scheduler for automatic warming. Configure custom schedules per sitemap. Or trigger via external cron jobs.</p>
            </div>
        </div>
    </div>
</section>

<!-- API Endpoints -->
<section class="section section-gray">
    <div class="container max-w-4xl mx-auto">
        <div class="section-header">
            <h2>REST API Endpoints</h2>
        </div>
        <?php
        cachewarmer_api_endpoint('POST', '/api/warm', 'Submit a sitemap for warming. Specify targets and priority.',
            '{
  "sitemapUrl": "https://example.com/sitemap.xml",
  "targets": ["cdn", "facebook", "linkedin", "indexnow"],
  "priority": "normal"
}',
            '{
  "jobId": "warm-abc123",
  "status": "queued",
  "urlCount": 42,
  "targets": ["cdn", "facebook", "linkedin", "indexnow"],
  "createdAt": "2026-02-28T12:00:00Z"
}'
        );
        cachewarmer_api_endpoint('GET', '/api/jobs', 'List all warming jobs with status and progress.');
        cachewarmer_api_endpoint('GET', '/api/jobs/:id', 'Get detailed status of a specific warming job.');
        cachewarmer_api_endpoint('GET', '/api/status', 'Health check and system status endpoint.');
        ?>
    </div>
</section>

<!-- Tech Stack -->
<section class="section section-white">
    <div class="container max-w-4xl mx-auto">
        <div class="section-header">
            <h2>Tech Stack</h2>
        </div>
        <div class="grid grid-2 gap-6">
            <div class="card">
                <h3 class="card-title">Core</h3>
                <ul class="feature-list">
                    <li>Node.js 20+ with TypeScript</li>
                    <li>Fastify web framework</li>
                    <li>Puppeteer with Chromium</li>
                    <li>BullMQ + Redis job queue</li>
                    <li>SQLite via better-sqlite3</li>
                </ul>
            </div>
            <div class="card">
                <h3 class="card-title">Deployment</h3>
                <ul class="feature-list">
                    <li>Docker with pre-built images</li>
                    <li>Docker Compose for production</li>
                    <li>Environment-based configuration</li>
                    <li>Structured logging with Pino</li>
                    <li>Health check endpoint</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="gradient-hero cta-section">
    <div class="container text-center">
        <h2>Deploy CacheWarmer on Your Infrastructure</h2>
        <p class="hero-subtitle">Full control, full flexibility. Deploy via Docker in minutes.</p>
        <div class="hero-buttons">
            <a href="<?php echo esc_url(home_url('/docs/')); ?>" class="btn btn-white btn-lg" title="CacheWarmer Documentation - Self-Hosted Deployment Guide">
                <?php cachewarmer_icon('book', '', 20); ?> Read the Docs
            </a>
            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="btn btn-outline-white btn-lg" title="CacheWarmer Pricing - Free, Premium &amp; Enterprise Plans">
                <?php cachewarmer_icon('tag', '', 20); ?> View Pricing
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
