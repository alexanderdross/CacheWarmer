<?php
/**
 * Template Name: Changelog
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Changelog</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem;">
			What&rsquo;s new in SearchForge. Every release, documented.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-changelog-entry">
			<div class="sf-changelog-entry__header">
				<h2>v1.9.0 <span class="sf-badge sf-badge--accent">Latest</span></h2>
				<time class="sf-text--muted">March 6, 2026</time>
			</div>
			<h3>Competitor Tracking &amp; SERP Intelligence</h3>
			<ul>
				<li><strong>New:</strong> Competitor domain tracking with auto-detection from shared keywords</li>
				<li><strong>New:</strong> SERP snapshot capture for top keywords (positions 1&ndash;10)</li>
				<li><strong>New:</strong> Content gap analysis vs. competitors</li>
				<li><strong>New:</strong> SERP feature tracking (featured snippets, PAA, video packs)</li>
				<li><strong>New:</strong> Competitor markdown export for LLM context</li>
				<li><strong>Fix:</strong> GA4 column name mismatch in page detail view</li>
				<li><strong>Fix:</strong> Weekly digest email array path alignment</li>
			</ul>
		</article>

		<article class="sf-changelog-entry">
			<div class="sf-changelog-entry__header">
				<h2>v1.8.0</h2>
				<time class="sf-text--muted">February 28, 2026</time>
			</div>
			<h3>Bulk Actions &amp; Weekly Digest</h3>
			<ul>
				<li><strong>New:</strong> Bulk page selection for batch brief export</li>
				<li><strong>New:</strong> Weekly digest email with key metric changes</li>
				<li><strong>New:</strong> Dashboard chart for clicks/impressions over time</li>
				<li><strong>Improved:</strong> Export ZIP now includes all brief formats</li>
			</ul>
		</article>

		<article class="sf-changelog-entry">
			<div class="sf-changelog-entry__header">
				<h2>v1.7.0</h2>
				<time class="sf-text--muted">February 21, 2026</time>
			</div>
			<h3>Page Detail View &amp; Charts</h3>
			<ul>
				<li><strong>New:</strong> Detailed per-page view with Chart.js visualizations</li>
				<li><strong>New:</strong> Keyword table with sorting and filtering</li>
				<li><strong>New:</strong> Position tracking chart per keyword</li>
				<li><strong>New:</strong> GA4 behavior metrics integration on page detail</li>
			</ul>
		</article>

		<article class="sf-changelog-entry">
			<div class="sf-changelog-entry__header">
				<h2>v1.6.0</h2>
				<time class="sf-text--muted">February 14, 2026</time>
			</div>
			<h3>API Keys, Pagination &amp; Onboarding</h3>
			<ul>
				<li><strong>New:</strong> API key authentication for external access</li>
				<li><strong>New:</strong> Pagination and search on pages/keywords lists</li>
				<li><strong>New:</strong> Onboarding wizard for first-time setup</li>
				<li><strong>New:</strong> Response caching for dashboard performance</li>
				<li><strong>Security:</strong> API keys accepted via headers only (not query parameters)</li>
			</ul>
		</article>

		<article class="sf-changelog-entry">
			<div class="sf-changelog-entry__header">
				<h2>v1.5.0</h2>
				<time class="sf-text--muted">February 7, 2026</time>
			</div>
			<h3>Alert System &amp; Content Decay</h3>
			<ul>
				<li><strong>New:</strong> Ranking drop alerts (email notifications)</li>
				<li><strong>New:</strong> Content decay detection with 7d/30d/90d trends</li>
				<li><strong>New:</strong> New keyword detection alerts</li>
				<li><strong>New:</strong> Monitoring dashboard with alert history</li>
			</ul>
		</article>

		<article class="sf-changelog-entry">
			<div class="sf-changelog-entry__header">
				<h2>v1.0.0</h2>
				<time class="sf-text--muted">January 15, 2026</time>
			</div>
			<h3>Initial Release</h3>
			<ul>
				<li><strong>New:</strong> Google Search Console integration with OAuth</li>
				<li><strong>New:</strong> Per-page markdown brief export</li>
				<li><strong>New:</strong> Dashboard with GSC overview</li>
				<li><strong>New:</strong> llms.txt auto-generation</li>
				<li><strong>New:</strong> SearchForge Score (basic)</li>
				<li><strong>New:</strong> Free tier with 10-page limit</li>
			</ul>
		</article>

	</div>
</section>

<?php
get_footer();
