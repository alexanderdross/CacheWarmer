<?php
/**
 * Template Name: Docs — Data Sources
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Data Sources</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			SearchForge connects to 8 SEO data sources. Learn how to configure each integration.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-doc-section" id="google-search-console">
			<h2>Google Search Console</h2>
			<p>The primary data source for SearchForge. Provides clicks, impressions, CTR, and average position data per page and per keyword.</p>
			<h3>Setup</h3>
			<p>Navigate to <strong>SearchForge &rarr; Data Sources &rarr; Google Search Console</strong> and click <strong>Connect</strong>. Sign in with the Google account that owns your GSC property. Grant read-only access and select your property.</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Per-page: clicks, impressions, CTR, average position</li>
				<li>Per-keyword: same metrics plus device segmentation (desktop, mobile, tablet)</li>
				<li>Date ranges: 7d, 28d, 3m, 6m, 12m</li>
			</ul>
			<h3>Limits</h3>
			<p>Free tier: 10 pages, 100 keywords. Pro and above: unlimited.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="bing-webmaster-tools">
			<h2>Bing Webmaster Tools</h2>
			<p>Bing-specific search data often reveals keywords that Google doesn&rsquo;t surface. Side-by-side comparison with GSC data in your briefs.</p>
			<h3>Setup</h3>
			<p>Go to <strong>Data Sources &rarr; Bing Webmaster Tools</strong>. Authenticate via OAuth or paste your Bing API key from <a href="https://www.bing.com/webmasters/" rel="noopener">Bing Webmaster Tools</a>.</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Clicks, impressions, CTR, average position</li>
				<li>Keyword-level data with Bing-specific search intent signals</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="google-analytics-4">
			<h2>Google Analytics 4</h2>
			<p>Behavioral data that complements search rankings. Understand what users do after they click.</p>
			<h3>Setup</h3>
			<p>Connect via OAuth at <strong>Data Sources &rarr; Google Analytics 4</strong>. Select your GA4 property and data stream.</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Sessions, bounce rate, engagement time per page</li>
				<li>Scroll depth and conversion events</li>
				<li>Traffic source attribution</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="google-keyword-planner">
			<h2>Google Keyword Planner</h2>
			<p>Absolute search volume and competition data to enrich your GSC keywords with market context.</p>
			<h3>Setup</h3>
			<p>Requires a Google Ads account (even without active campaigns). Connect via OAuth at <strong>Data Sources &rarr; Keyword Planner</strong>.</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Monthly search volume (exact and range)</li>
				<li>Competition level (low/medium/high)</li>
				<li>Suggested bid / CPC data</li>
				<li>Seasonal trends (12-month histogram)</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="google-trends">
			<h2>Google Trends</h2>
			<p>Relative interest over time and geographic breakdown for your keywords. Ideal for content calendar planning.</p>
			<h3>Setup</h3>
			<p>Requires a SerpApi key. Enter it at <strong>Data Sources &rarr; Google Trends</strong>.</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Interest over time (relative 0&ndash;100 scale)</li>
				<li>Related queries and rising queries</li>
				<li>Geographic breakdown by region</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="google-business-profile">
			<h2>Google Business Profile</h2>
			<p>Local SEO data for brick-and-mortar businesses. Track how customers find your business listing.</p>
			<h3>Setup</h3>
			<p>Connect via OAuth at <strong>Data Sources &rarr; Google Business Profile</strong>. Select your business location(s).</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Direct vs. discovery queries</li>
				<li>Maps vs. Search impressions</li>
				<li>Customer actions (calls, directions, website clicks)</li>
				<li>Review count and average rating</li>
			</ul>
			<h3>Limits</h3>
			<p>Pro: 1 location. Agency: 10 locations. Enterprise: unlimited.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="bing-places-for-business">
			<h2>Bing Places for Business</h2>
			<p>Bing local search data. Cross-platform comparison with Google Business Profile to identify Bing-only discovery keywords.</p>
			<h3>Setup</h3>
			<p>Authenticate at <strong>Data Sources &rarr; Bing Places</strong> using your Bing Places account credentials.</p>
			<h3>Data pulled</h3>
			<ul class="sf-content">
				<li>Local search impressions and clicks</li>
				<li>Customer actions on Bing</li>
				<li>Cross-platform comparison metrics</li>
			</ul>
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
