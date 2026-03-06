<?php
/**
 * Template Name: Documentation
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Documentation</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem;">
			Everything you need to install, configure, and get the most out of SearchForge.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container">
		<div class="sf-grid sf-grid--3">

			<!-- Getting Started -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/sync.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title">Getting Started</h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-installation-guide/">How to Install SearchForge</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-license-activation/">Activate Your License Key</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/connect-google-search-console/">Connect Google Search Console</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/first-seo-data-sync/">Run Your First SEO Data Sync</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/export-first-seo-content-brief/">Export Your First Content Brief</a></li>
				</ul>
			</div>

			<!-- Data Sources -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/layers.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title">Data Sources</h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/google-search-console-integration/">Google Search Console Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/bing-webmaster-tools-integration/">Bing Webmaster Tools Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/google-analytics-4-integration/">Google Analytics 4 Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/google-keyword-planner-integration/">Google Keyword Planner Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/google-trends-integration/">Google Trends Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/google-business-profile-integration/">Google Business Profile Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/bing-places-integration/">Bing Places for Business Integration</a></li>
				</ul>
			</div>

			<!-- Features -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/score.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title">Features</h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-seo-score/">SearchForge SEO Score Explained</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/ai-visibility-monitor/">AI Visibility Monitor (AEO Tracking)</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/seo-competitor-intelligence/">SEO Competitor Intelligence</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/ai-seo-content-briefs/">AI-Powered SEO Content Briefs</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/keyword-clustering-wordpress/">Keyword Clustering for WordPress</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/keyword-cannibalization-detection/">Keyword Cannibalization Detection</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/seo-alerts-monitoring/">SEO Alerts &amp; Content Decay Monitoring</a></li>
				</ul>
			</div>

			<!-- Export & Output -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/export.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title">Export &amp; Output</h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/markdown-seo-brief-export/">Markdown SEO Brief Export</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/combined-master-brief/">Combined Master Brief</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/llms-txt-generation/">llms.txt Generation for LLMs</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/bulk-seo-data-export/">Bulk SEO Data ZIP Export</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/scheduled-seo-exports/">Scheduled SEO Data Exports</a></li>
				</ul>
			</div>

			<!-- Developer -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/markdown.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title">Developer</h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-rest-api-reference/">REST API Reference</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-wp-cli-commands/">WP-CLI Commands</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-hooks-actions-filters/">Actions &amp; Filters Reference</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-api-key-authentication/">API Key Authentication</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/searchforge-webhook-events/">Webhook Events Reference</a></li>
				</ul>
			</div>

			<!-- Integrations -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/clustering.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title">Integrations</h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/yoast-seo-integration/">Yoast SEO Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/rank-math-integration/">Rank Math Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/aioseo-integration/">AIOSEO Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/cachewarmer-integration/">CacheWarmer Integration</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/github-gitlab-export/">GitHub &amp; GitLab Export</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/notion-seo-export/">Notion SEO Data Export</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/google-sheets-seo-export/">Google Sheets SEO Export</a></li>
				</ul>
			</div>

		</div>
	</div>
</section>

<?php
get_footer();
