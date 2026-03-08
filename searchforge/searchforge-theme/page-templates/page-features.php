<?php
/**
 * Template Name: Features
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Every Feature You Need</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			SearchForge unifies 8 SEO data sources into LLM-ready intelligence. Here&rsquo;s everything included.
		</p>
	</div>
</section>

<!-- Data Sources -->
<section class="sf-section" id="data-sources">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>8 Data Sources, One Dashboard</h2>
			<p class="sf-text--muted">Connect once. Sync automatically. All data flows into unified per-page briefs.</p>
		</div>

		<div class="sf-grid sf-grid--2">
			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/gsc.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Google Search Console</h3>
					<p>Clicks, impressions, CTR, and average position per page and per query. Device segmentation (desktop, mobile, tablet). Date range selection from 7 days to 12 months. Free tier: 10 pages. Pro: unlimited.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/bing.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Bing Webmaster Tools</h3>
					<p>Bing-specific click and impression data, often revealing keywords Google doesn&rsquo;t show. Side-by-side comparison with GSC data. OAuth or API key authentication.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/ga4.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Google Analytics 4</h3>
					<p>Bounce rate, engagement time, scroll depth, and conversion attribution per page. Correlate on-page behavior with search rankings to identify content mismatches.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/kwp.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Google Keyword Planner</h3>
					<p>Monthly search volume, competition level, CPC data, and seasonal trends. Enrich GSC keywords with absolute volume data and discover content gaps.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/trends.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Google Trends</h3>
					<p>Relative interest over time, related queries, rising queries, and geographic breakdown. Detect seasonal patterns for your content calendar.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/gbp.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Google Business Profile</h3>
					<p>Local SEO keywords that trigger your business listing. Direct vs. discovery queries, Maps vs. Search split, customer actions, and review sentiment analysis. Pro: 1 location. Agency: 10.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/bing-places.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Bing Places for Business</h3>
					<p>Bing local search impressions and actions. Cross-platform comparison with Google Business Profile to identify Bing-only discovery keywords.</p>
				</div>
			</div>

			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/serp.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>SERP Intelligence (via SerpApi)</h3>
					<p>Competitor SERP snapshots, content gap analysis, SERP feature tracking, and AI visibility monitoring. Uses your own SerpApi key with metered access.</p>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- SearchForge Score -->
<section class="sf-section sf-section--light" id="score">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>SearchForge Score</h2>
			<p class="sf-text--muted">A proprietary 0&ndash;100 SEO health score with actionable breakdown.</p>
		</div>

		<div class="sf-grid sf-grid--2">
			<div class="sf-card sf-card--bordered">
				<h3>Technical SEO (25%)</h3>
				<p class="sf-card__desc">Schema markup presence, mobile-friendliness from GSC device data, Core Web Vitals hints, and heading structure analysis.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3>Content Quality (25%)</h3>
				<p class="sf-card__desc">Keyword coverage depth, heading hierarchy, content length vs. competitors, and internal linking density.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3>Authority (25%)</h3>
				<p class="sf-card__desc">GSC link data, internal link count, referring domains (where available), and brand query volume.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3>Momentum (25%)</h3>
				<p class="sf-card__desc">Ranking trends (7d/30d/90d), impression growth rate, new keyword acquisitions, and CTR vs. expected CTR for position.</p>
			</div>
		</div>

		<p style="text-align: center; margin-top: var(--space-xl); color: var(--sf-text-muted);">
			Free tier shows the overall score. Pro unlocks the full breakdown with per-component recommendations.
		</p>
	</div>
</section>

<!-- AI Visibility -->
<section class="sf-section" id="aeo">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>AI Visibility Monitor (AEO)</h2>
			<p class="sf-text--muted">40%+ of queries are now answered by AI without a click. Are you being cited?</p>
		</div>

		<div class="sf-grid sf-grid--3">
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">AI Citation Tracking</h3>
				<p class="sf-card__desc">Monitor if and how your pages are cited in ChatGPT, Perplexity, Google AI Overviews, and Bing Copilot.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">AI Visibility Score</h3>
				<p class="sf-card__desc">Proprietary 0&ndash;100 score measuring how likely your page is to be cited by AI engines, tracked over time.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Structured Data Audit</h3>
				<p class="sf-card__desc">Check if pages have schema markup that AI engines prefer. Get recommendations for FAQ, HowTo, and Article schemas.</p>
			</div>
		</div>
	</div>
</section>

<!-- Competitor Intelligence -->
<section class="sf-section sf-section--light" id="competitors">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Competitor SERP Intelligence</h2>
			<p class="sf-text--muted">Understand who ranks above and below you &mdash; without paying $1,400/yr for Semrush.</p>
		</div>

		<div class="sf-grid sf-grid--2">
			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/competitors.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>SERP Snapshots</h3>
					<p>For your top keywords, capture who ranks in positions 1&ndash;10. Auto-identify recurring competitor domains across your keyword set.</p>
				</div>
			</div>
			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/clustering.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Content Gaps</h3>
					<p>Discover keywords where competitors rank but you don&rsquo;t. Prioritize by estimated traffic potential and difficulty.</p>
				</div>
			</div>
			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/dashboard.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>SERP Feature Tracking</h3>
					<p>Track featured snippets, People Also Ask, video packs, and image packs. Know which features appear and who owns them.</p>
				</div>
			</div>
			<div class="sf-feature-card">
				<div class="sf-feature-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/export.svg" alt="" width="24" height="24">
				</div>
				<div class="sf-feature-card__content">
					<h3>Competitor Markdown Export</h3>
					<p>Export competitor analysis per page for LLM context. Feed directly into Claude or ChatGPT for AI-assisted competitive strategy.</p>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Analysis Tools -->
<section class="sf-section" id="analysis">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Analysis &amp; Intelligence</h2>
		</div>

		<div class="sf-grid sf-grid--3">
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Keyword Clustering</h3>
				<p class="sf-card__desc">Group GSC keywords by semantic similarity into topic clusters, each linked to a pillar page suggestion. Runs locally in PHP.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Cannibalization Detection</h3>
				<p class="sf-card__desc">Identify pages competing for the same keywords. Multiple URLs ranking for the same query, flagged with severity scores.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Content Decay Detection</h3>
				<p class="sf-card__desc">Alert when a page&rsquo;s rankings decline. 7-day, 30-day, and 90-day trend analysis with velocity metrics.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">AI Content Briefs</h3>
				<p class="sf-card__desc">AI-generated per-page briefs with change recommendations and expected impact. Uses your own OpenAI or Claude API key.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Historical Trends</h3>
				<p class="sf-card__desc">12-month rolling snapshots with year-over-year comparison. Per-keyword ranking trajectory and velocity metrics.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Internal Linking Suggestions</h3>
				<p class="sf-card__desc">Based on keyword overlap between pages, suggest internal links to consolidate authority and improve crawlability.</p>
			</div>
		</div>
	</div>
</section>

<!-- Export & Integration -->
<section class="sf-section sf-section--light" id="export">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Export &amp; Integrations</h2>
		</div>

		<div class="sf-grid sf-grid--3">
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Markdown Briefs</h3>
				<p class="sf-card__desc">Per-page, per-source, and combined master briefs. LLM Quick Brief format optimized for token efficiency.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">llms.txt Generation</h3>
				<p class="sf-card__desc">Auto-generate and maintain /llms.txt and /llms-full.txt for AI crawler discoverability. Basic (Free) and Advanced (Pro).</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">WP-CLI</h3>
				<p class="sf-card__desc"><code>wp searchforge export --format=master-brief</code>. Full terminal access to all SearchForge data and export commands.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">REST API</h3>
				<p class="sf-card__desc">Programmatic access under <code>searchforge/v1</code> namespace. Read-only (Pro) or full CRUD (Agency).</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">GitHub / GitLab Push</h3>
				<p class="sf-card__desc">Push markdown briefs directly to a repository. Perfect for Claude Code workflows and version-controlled SEO tracking.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Notion / Sheets Sync</h3>
				<p class="sf-card__desc">Direct export to Notion databases or Google Sheets for custom dashboards and team collaboration.</p>
			</div>
		</div>
	</div>
</section>

<!-- CTA -->
<section class="sf-section sf-section--dark sf-final-cta">
	<div class="sf-container" style="text-align: center;">
		<h2>Ready to Try SearchForge?</h2>
		<p class="sf-text--inverse-muted">Start free with Google Search Console. Upgrade to Pro when you need more.</p>
		<div class="sf-hero__actions" style="justify-content: center; margin-top: var(--space-xl);">
			<a href="/pricing/" class="sf-btn sf-btn--primary sf-btn--lg">View Pricing</a>
			<a href="/docs/" class="sf-btn sf-btn--outline-light sf-btn--lg">Read the Docs</a>
		</div>
	</div>
</section>

<?php
get_footer();
