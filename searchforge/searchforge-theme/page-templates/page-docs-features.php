<?php
/**
 * Template Name: Docs — Features
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<?php get_template_part( 'template-parts/breadcrumb' ); ?>
		<h1><span class="sf-gradient-text">Features</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			Analysis, intelligence, and monitoring tools that turn raw SEO data into actionable insights.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-doc-section" id="searchforge-score">
			<h2>SearchForge Score</h2>
			<p>A proprietary 0&ndash;100 SEO health score calculated per page. The score combines four equally weighted components.</p>
			<h3>Components</h3>
			<ul class="sf-content">
				<li><strong>Technical SEO (25%)</strong> &mdash; Schema markup, mobile-friendliness, Core Web Vitals hints, heading structure.</li>
				<li><strong>Content Quality (25%)</strong> &mdash; Keyword coverage, heading hierarchy, content length vs. competitors, internal linking.</li>
				<li><strong>Authority (25%)</strong> &mdash; GSC link data, internal links, referring domains, brand query volume.</li>
				<li><strong>Momentum (25%)</strong> &mdash; Ranking trends (7d/30d/90d), impression growth, new keywords, CTR vs. expected.</li>
			</ul>
			<p>Free tier shows the overall score. Pro unlocks the full breakdown with per-component recommendations.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="ai-visibility-monitor">
			<h2>AI Visibility Monitor</h2>
			<p>Track whether AI engines cite your pages when answering user queries. Covers Google AI Overviews, ChatGPT, Perplexity, and Bing Copilot.</p>
			<h3>How it works</h3>
			<ol class="sf-content">
				<li>Define target keywords in <strong>SearchForge &rarr; AI Visibility</strong>.</li>
				<li>SearchForge queries SerpApi periodically to check AI-generated answers.</li>
				<li>Results show if your pages are cited, quoted, or linked in AI responses.</li>
				<li>Track citation rate trends over time and identify gaps.</li>
			</ol>
			<h3>Limits</h3>
			<p>Pro: 20 queries/month. Agency: 200 queries/month. Enterprise: unlimited. Requires a SerpApi key.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="competitor-intelligence">
			<h2>Competitor Intelligence</h2>
			<p>Understand who ranks above and below you for your target keywords without expensive third-party tools.</p>
			<h3>Capabilities</h3>
			<ul class="sf-content">
				<li><strong>SERP Snapshots</strong> &mdash; Capture positions 1&ndash;10 for your keywords. Auto-identify recurring competitors.</li>
				<li><strong>Content Gaps</strong> &mdash; Discover keywords where competitors rank but you don&rsquo;t.</li>
				<li><strong>SERP Feature Tracking</strong> &mdash; Featured snippets, People Also Ask, video/image packs.</li>
				<li><strong>Competitor Markdown Export</strong> &mdash; Export competitor analysis for LLM context.</li>
			</ul>
			<h3>Limits</h3>
			<p>Pro: 10 keywords/month. Agency: 100 keywords/month. Enterprise: unlimited.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="ai-content-briefs">
			<h2>AI Content Briefs</h2>
			<p>AI-generated per-page briefs with specific content change recommendations and estimated impact.</p>
			<h3>How it works</h3>
			<p>SearchForge sends your page&rsquo;s SEO data (rankings, keywords, competitor context) to an LLM and returns structured recommendations. Uses your own OpenAI or Claude API key &mdash; we never see the content.</p>
			<h3>Output includes</h3>
			<ul class="sf-content">
				<li>Recommended title and meta description changes</li>
				<li>Content gaps to fill based on competitor analysis</li>
				<li>Internal linking suggestions</li>
				<li>Estimated ranking impact (directional)</li>
			</ul>
			<h3>Limits</h3>
			<p>Pro: 10 briefs/month. Agency: 50/month. Enterprise: unlimited.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="keyword-clustering">
			<h2>Keyword Clustering</h2>
			<p>Automatically group GSC keywords by semantic similarity into topic clusters, each linked to a pillar page suggestion.</p>
			<h3>How it works</h3>
			<p>SearchForge uses TF-IDF similarity scoring on your keyword set, running entirely in PHP on your server. No external API calls required.</p>
			<h3>Output</h3>
			<ul class="sf-content">
				<li>Cluster groups with a primary keyword and related terms</li>
				<li>Suggested pillar page URL for each cluster</li>
				<li>Cluster overlap detection (keywords appearing in multiple clusters)</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="cannibalization-detection">
			<h2>Cannibalization Detection</h2>
			<p>Identify pages competing for the same keywords. When multiple URLs rank for the same query, SearchForge flags them with severity scores.</p>
			<h3>Detection criteria</h3>
			<ul class="sf-content">
				<li>Two or more pages appearing in GSC data for the same keyword</li>
				<li>Position fluctuation indicating Google alternating between pages</li>
				<li>Severity: Low (different intents), Medium (overlap), High (direct competition)</li>
			</ul>
			<h3>Resolution suggestions</h3>
			<p>For each flagged pair, SearchForge suggests: merge content, add canonical, differentiate intent, or redirect.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="alerts-monitoring">
			<h2>Alerts &amp; Monitoring</h2>
			<p>Automated notifications when your rankings change significantly or content starts decaying.</p>
			<h3>Alert types</h3>
			<ul class="sf-content">
				<li><strong>Ranking drops</strong> &mdash; Page falls 5+ positions for a tracked keyword.</li>
				<li><strong>Content decay</strong> &mdash; Traffic declining over 7d, 30d, or 90d windows.</li>
				<li><strong>New keyword acquisitions</strong> &mdash; Pages ranking for new keywords not seen before.</li>
				<li><strong>AI visibility changes</strong> &mdash; Gained or lost AI citations.</li>
			</ul>
			<h3>Channels</h3>
			<p>Pro: email alerts. Agency: email + Slack. Enterprise: email + Slack + Discord + webhooks.</p>
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
