<?php
/**
 * Template Name: Docs — Integrations
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Integrations</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			SearchForge integrates with popular SEO plugins, cache tools, Git platforms, and productivity apps.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-doc-section" id="yoast-seo">
			<h2>Yoast SEO</h2>
			<p>SearchForge reads Yoast SEO metadata to enrich briefs with on-page optimization context.</p>
			<h3>What gets pulled</h3>
			<ul class="sf-content">
				<li>Focus keyword and SEO title</li>
				<li>Meta description and readability score</li>
				<li>Canonical URL and schema settings</li>
				<li>Internal linking suggestions from Yoast Premium</li>
			</ul>
			<h3>Setup</h3>
			<p>Automatic. If Yoast SEO is active, SearchForge detects it and includes Yoast data in briefs. No configuration needed.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="rank-math">
			<h2>Rank Math</h2>
			<p>Full integration with Rank Math&rsquo;s SEO data, including their proprietary SEO score.</p>
			<h3>What gets pulled</h3>
			<ul class="sf-content">
				<li>Focus keywords (up to 5 per page)</li>
				<li>Rank Math SEO score</li>
				<li>Schema markup settings</li>
				<li>Redirect rules</li>
			</ul>
			<h3>Setup</h3>
			<p>Automatic. Detected when Rank Math is active. SearchForge shows both its own score and Rank Math&rsquo;s score side by side.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="aioseo">
			<h2>AIOSEO</h2>
			<p>Integration with All in One SEO for sites using AIOSEO as their primary SEO plugin.</p>
			<h3>What gets pulled</h3>
			<ul class="sf-content">
				<li>SEO title and meta description</li>
				<li>Focus keyword and TruSEO score</li>
				<li>Schema settings and social media metadata</li>
			</ul>
			<h3>Setup</h3>
			<p>Automatic detection when AIOSEO is active.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="cachewarmer">
			<h2>CacheWarmer</h2>
			<p>Trigger cache warming after content changes detected by SearchForge. Ensures updated pages are always served from cache.</p>
			<h3>How it works</h3>
			<ul class="sf-content">
				<li><strong>Manual mode (Pro)</strong> &mdash; Click &ldquo;Warm Cache&rdquo; from any page in SearchForge.</li>
				<li><strong>Auto-trigger (Agency/Enterprise)</strong> &mdash; Automatically warm cache when SearchForge detects content changes or after scheduled exports.</li>
			</ul>
			<h3>Setup</h3>
			<p>Requires the <a href="https://cachewarmer.drossmedia.de/" rel="noopener">CacheWarmer plugin</a> installed and activated. Configure at <strong>SearchForge &rarr; Settings &rarr; Integrations &rarr; CacheWarmer</strong>.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="github-gitlab">
			<h2>GitHub &amp; GitLab</h2>
			<p>Push markdown briefs directly to a Git repository. Ideal for Claude Code workflows and version-controlled SEO tracking.</p>
			<h3>Setup</h3>
			<ol class="sf-content">
				<li>Go to <strong>SearchForge &rarr; Settings &rarr; Integrations &rarr; Git</strong>.</li>
				<li>Enter your repository URL and personal access token.</li>
				<li>Choose the branch and directory for brief files.</li>
				<li>Enable auto-push on sync or schedule.</li>
			</ol>
			<p>Available on Agency and Enterprise tiers.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="notion-export">
			<h2>Notion Export</h2>
			<p>Export SEO data directly to Notion databases for custom dashboards and team collaboration.</p>
			<h3>Setup</h3>
			<ol class="sf-content">
				<li>Create a Notion integration at <a href="https://www.notion.so/my-integrations" rel="noopener">notion.so/my-integrations</a>.</li>
				<li>Share your target database with the integration.</li>
				<li>Paste the integration token and database ID into <strong>SearchForge &rarr; Settings &rarr; Integrations &rarr; Notion</strong>.</li>
			</ol>
			<h3>Sync behavior</h3>
			<p>Creates or updates a row per page with SearchForge Score, top keywords, traffic metrics, and a link to the full brief.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="google-sheets">
			<h2>Google Sheets</h2>
			<p>Export SEO metrics to Google Sheets for custom reporting, pivot tables, and sharing with stakeholders.</p>
			<h3>Setup</h3>
			<ol class="sf-content">
				<li>Connect via OAuth at <strong>SearchForge &rarr; Settings &rarr; Integrations &rarr; Google Sheets</strong>.</li>
				<li>Select an existing spreadsheet or create a new one.</li>
				<li>Choose which metrics to export (scores, keywords, traffic, etc.).</li>
			</ol>
			<h3>Sync behavior</h3>
			<p>Appends a new sheet tab per sync date, preserving historical snapshots. Or overwrite mode for a live dashboard.</p>
		</article>

	</div>
</section>

<section class="sf-section sf-section--light" style="text-align: center;">
	<div class="sf-container sf-container--narrow">
		<p class="sf-text--muted">
			<a href="/docs/">&larr; Back to Documentation</a>
		</p>
	</div>
</section>

<?php
get_footer();
