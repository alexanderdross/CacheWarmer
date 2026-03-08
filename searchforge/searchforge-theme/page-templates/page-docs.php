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
				<h3 class="sf-card__title"><a href="/docs/getting-started/">Getting Started</a></h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/getting-started/#installation">Installation</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/getting-started/#license-activation">License Activation</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/getting-started/#connecting-google-search-console">Connecting Google Search Console</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/getting-started/#your-first-data-sync">Your First Data Sync</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/getting-started/#exporting-your-first-brief">Exporting Your First Brief</a></li>
				</ul>
			</div>

			<!-- Data Sources -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/layers.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title"><a href="/docs/data-sources/">Data Sources</a></h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#google-search-console">Google Search Console</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#bing-webmaster-tools">Bing Webmaster Tools</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#google-analytics-4">Google Analytics 4</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#google-keyword-planner">Google Keyword Planner</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#google-trends">Google Trends</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#google-business-profile">Google Business Profile</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/data-sources/#bing-places-for-business">Bing Places for Business</a></li>
				</ul>
			</div>

			<!-- Features -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/score.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title"><a href="/docs/features/">Features</a></h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#searchforge-score">SearchForge Score</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#ai-visibility-monitor">AI Visibility Monitor</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#competitor-intelligence">Competitor Intelligence</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#ai-content-briefs">AI Content Briefs</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#keyword-clustering">Keyword Clustering</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#cannibalization-detection">Cannibalization Detection</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/features/#alerts-monitoring">Alerts &amp; Monitoring</a></li>
				</ul>
			</div>

			<!-- Export & Output -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/export.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title"><a href="/docs/export-output/">Export &amp; Output</a></h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/export-output/#markdown-briefs">Markdown Briefs</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/export-output/#combined-master-brief">Combined Master Brief</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/export-output/#llms-txt-generation">llms.txt Generation</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/export-output/#zip-bulk-export">ZIP Bulk Export</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/export-output/#scheduled-exports">Scheduled Exports</a></li>
				</ul>
			</div>

			<!-- Developer -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/markdown.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title"><a href="/docs/developer/">Developer</a></h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/developer/#rest-api-reference">REST API Reference</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/developer/#wp-cli-commands">WP-CLI Commands</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/developer/#actions-filters">Actions &amp; Filters</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/developer/#api-key-authentication">API Key Authentication</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/developer/#webhook-events">Webhook Events</a></li>
				</ul>
			</div>

			<!-- Integrations -->
			<div class="sf-card sf-card--bordered">
				<div class="sf-card__icon" aria-hidden="true">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/clustering.svg" alt="" width="24" height="24">
				</div>
				<h3 class="sf-card__title"><a href="/docs/integrations/">Integrations</a></h3>
				<ul style="list-style: none; margin-top: var(--space-md);">
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#yoast-seo">Yoast SEO</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#rank-math">Rank Math</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#aioseo">AIOSEO</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#cachewarmer">CacheWarmer</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#github-gitlab">GitHub &amp; GitLab</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#notion-export">Notion Export</a></li>
					<li style="padding: var(--space-xs) 0;"><a href="/docs/integrations/#google-sheets">Google Sheets</a></li>
				</ul>
			</div>

		</div>
	</div>
</section>

<?php
get_footer();
