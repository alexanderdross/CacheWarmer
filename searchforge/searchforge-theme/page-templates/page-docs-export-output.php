<?php
/**
 * Template Name: Docs — Export & Output
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">Export &amp; Output</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			Export SEO data as LLM-ready markdown briefs, llms.txt files, bulk ZIPs, and scheduled reports.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-doc-section" id="markdown-briefs">
			<h2>Markdown Briefs</h2>
			<p>The core export format. Per-page markdown documents containing all SEO data structured for LLM consumption.</p>
			<h3>Brief types</h3>
			<ul class="sf-content">
				<li><strong>Per-source brief</strong> &mdash; Data from a single source (e.g., GSC only).</li>
				<li><strong>Combined brief</strong> &mdash; Merged data from all connected sources for one page.</li>
				<li><strong>LLM Quick Brief</strong> &mdash; Token-optimized format for cost-efficient LLM usage.</li>
			</ul>
			<h3>Export options</h3>
			<p>Copy to clipboard, download as <code>.md</code>, or push to GitHub/GitLab. Free tier: GSC data only. Pro: all sources.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="combined-master-brief">
			<h2>Combined Master Brief</h2>
			<p>A single document that merges data from all connected sources for one page into a comprehensive SEO brief.</p>
			<h3>Contents</h3>
			<ul class="sf-content">
				<li>SearchForge Score with component breakdown</li>
				<li>GSC metrics (clicks, impressions, CTR, position)</li>
				<li>GA4 behavioral metrics</li>
				<li>Keyword Planner volume data</li>
				<li>Competitor context from SERP snapshots</li>
				<li>AI visibility status</li>
				<li>Actionable recommendations</li>
			</ul>
			<p>Available on Pro and above.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="llms-txt-generation">
			<h2>llms.txt Generation</h2>
			<p>SearchForge auto-generates and maintains <code>/llms.txt</code> and <code>/llms-full.txt</code> files that help AI crawlers understand your site structure.</p>
			<h3>Basic (Free)</h3>
			<p>Lists your top pages with titles and meta descriptions. Auto-updated on each sync.</p>
			<h3>Advanced (Pro)</h3>
			<ul class="sf-content">
				<li>Full content summaries per page</li>
				<li>Topic cluster grouping</li>
				<li>Structured metadata (publication date, last update, author)</li>
				<li>Custom sections and exclusions</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="zip-bulk-export">
			<h2>ZIP Bulk Export</h2>
			<p>Export all briefs for your entire site as a single ZIP archive. Useful for LLM context windows that accept file uploads.</p>
			<h3>Contents</h3>
			<ul class="sf-content">
				<li>One <code>.md</code> file per page</li>
				<li>A master index file listing all pages with scores</li>
				<li>Optional: CSV summary for spreadsheet analysis</li>
			</ul>
			<p>Available on Agency and Enterprise tiers.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="scheduled-exports">
			<h2>Scheduled Exports</h2>
			<p>Automate recurring exports on a daily or weekly schedule. Delivered via email, cloud storage, or Git push.</p>
			<h3>Delivery options</h3>
			<ul class="sf-content">
				<li><strong>Email</strong> &mdash; ZIP attachment or inline summary.</li>
				<li><strong>Cloud storage</strong> &mdash; Push to Amazon S3, Google Cloud Storage, or Dropbox.</li>
				<li><strong>Git</strong> &mdash; Auto-commit to GitHub or GitLab repository.</li>
			</ul>
			<p>Available on Agency and Enterprise tiers.</p>
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
