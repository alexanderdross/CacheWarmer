<?php
/**
 * Template Name: Docs — Getting Started
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Getting Started</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			Install SearchForge, activate your license, connect Google Search Console, and export your first SEO brief in under 10 minutes.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-doc-section" id="installation">
			<h2>Installation</h2>
			<p>SearchForge installs like any WordPress plugin. Download the ZIP from your account dashboard or install directly from the WordPress plugin directory.</p>
			<ol class="sf-content">
				<li>Go to <strong>Plugins &rarr; Add New</strong> in your WordPress admin.</li>
				<li>Click <strong>Upload Plugin</strong> and select the <code>searchforge.zip</code> file.</li>
				<li>Click <strong>Install Now</strong>, then <strong>Activate</strong>.</li>
				<li>The SearchForge menu appears in your admin sidebar.</li>
			</ol>
			<p>Requirements: WordPress 6.0+, PHP 8.0+, and a valid SSL certificate for OAuth connections.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="license-activation">
			<h2>License Activation</h2>
			<p>Free tier works without a license key. For Pro, Agency, or Enterprise, enter your key in <strong>SearchForge &rarr; Settings &rarr; License</strong>.</p>
			<ol class="sf-content">
				<li>Copy your license key from the purchase confirmation email or your account at <code>searchforge.drossmedia.de/account/</code>.</li>
				<li>Navigate to <strong>SearchForge &rarr; Settings &rarr; License</strong>.</li>
				<li>Paste the key and click <strong>Activate</strong>.</li>
				<li>Your tier and expiry date appear immediately. Features unlock within seconds.</li>
			</ol>
			<p>Each license is valid for one production domain. Development domains (<code>localhost</code>, <code>*.local</code>, <code>*.dev</code>, <code>*.test</code>) are free and unlimited.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="connecting-google-search-console">
			<h2>Connecting Google Search Console</h2>
			<p>Google Search Console is the primary data source and works on every tier, including Free.</p>
			<ol class="sf-content">
				<li>Go to <strong>SearchForge &rarr; Data Sources</strong>.</li>
				<li>Click <strong>Connect Google Search Console</strong>.</li>
				<li>Sign in with the Google account that has access to your GSC property.</li>
				<li>Grant the requested permissions (read-only access to Search Console data).</li>
				<li>Select your GSC property from the dropdown and click <strong>Save</strong>.</li>
			</ol>
			<p>SearchForge uses OAuth 2.0 &mdash; your credentials are never stored. The access token is encrypted in your WordPress database.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="your-first-data-sync">
			<h2>Your First Data Sync</h2>
			<p>After connecting GSC, trigger your first sync to pull ranking data into SearchForge.</p>
			<ol class="sf-content">
				<li>Go to <strong>SearchForge &rarr; Dashboard</strong>.</li>
				<li>Click <strong>Sync Now</strong> or wait for the automatic daily sync.</li>
				<li>SearchForge pulls clicks, impressions, CTR, and position data for each page and keyword.</li>
				<li>Free tier: up to 10 pages and 100 keywords. Pro: unlimited.</li>
			</ol>
			<p>The first sync usually takes 30&ndash;60 seconds depending on your site size. Subsequent syncs are incremental and faster.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="exporting-your-first-brief">
			<h2>Exporting Your First Brief</h2>
			<p>The core output of SearchForge is the per-page markdown brief &mdash; a structured document ready for LLMs.</p>
			<ol class="sf-content">
				<li>Navigate to <strong>SearchForge &rarr; Pages</strong> and click any page.</li>
				<li>Review the data summary: rankings, top keywords, trends, and SearchForge Score.</li>
				<li>Click <strong>Export Markdown Brief</strong>.</li>
				<li>Copy the brief and paste it into Claude Code, ChatGPT, or save it as a <code>.md</code> file.</li>
			</ol>
			<p>The brief includes all the context an LLM needs to give you actionable SEO recommendations for that specific page.</p>
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
