<?php
/**
 * Template Name: Docs — Developer
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<?php get_template_part( 'template-parts/breadcrumb' ); ?>
		<h1><span class="sf-gradient-text">Developer</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			REST API, WP-CLI, hooks, webhooks, and API key authentication for programmatic access.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container sf-container--narrow">

		<article class="sf-doc-section" id="rest-api-reference">
			<h2>REST API Reference</h2>
			<p>Programmatic access to all SearchForge data under the <code>searchforge/v1</code> namespace.</p>
			<h3>Base URL</h3>
			<p><code>https://your-site.com/wp-json/searchforge/v1/</code></p>
			<h3>Key endpoints</h3>
			<ul class="sf-content">
				<li><code>GET /pages</code> &mdash; List all tracked pages with scores.</li>
				<li><code>GET /pages/{id}/brief</code> &mdash; Get the markdown brief for a specific page.</li>
				<li><code>GET /pages/{id}/keywords</code> &mdash; Keywords for a page with metrics.</li>
				<li><code>GET /score/{id}</code> &mdash; SearchForge Score with component breakdown.</li>
				<li><code>POST /sync</code> &mdash; Trigger a manual data sync (Agency/Enterprise).</li>
				<li><code>GET /export/zip</code> &mdash; Download bulk ZIP export.</li>
			</ul>
			<h3>Access levels</h3>
			<p>Pro: read-only endpoints. Agency and Enterprise: full CRUD including sync triggers and export scheduling.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="wp-cli-commands">
			<h2>WP-CLI Commands</h2>
			<p>Full terminal access to SearchForge data and operations.</p>
			<h3>Available commands</h3>
			<ul class="sf-content">
				<li><code>wp searchforge sync</code> &mdash; Trigger a data sync for all connected sources.</li>
				<li><code>wp searchforge export --format=brief --page={id}</code> &mdash; Export a single page brief.</li>
				<li><code>wp searchforge export --format=master-brief</code> &mdash; Export combined master brief.</li>
				<li><code>wp searchforge export --format=zip</code> &mdash; Generate and download bulk ZIP.</li>
				<li><code>wp searchforge score --page={id}</code> &mdash; Show SearchForge Score for a page.</li>
				<li><code>wp searchforge llms-txt regenerate</code> &mdash; Force regenerate llms.txt files.</li>
			</ul>
			<p>Available on Pro and above. Multi-site support on Agency and Enterprise.</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="actions-filters">
			<h2>Actions &amp; Filters</h2>
			<p>WordPress hooks for extending and customizing SearchForge behavior.</p>
			<h3>Key actions</h3>
			<ul class="sf-content">
				<li><code>searchforge/sync/complete</code> &mdash; Fires after a successful data sync.</li>
				<li><code>searchforge/brief/exported</code> &mdash; Fires after a brief is exported.</li>
				<li><code>searchforge/score/calculated</code> &mdash; Fires after score recalculation.</li>
				<li><code>searchforge/alert/triggered</code> &mdash; Fires when an alert condition is met.</li>
			</ul>
			<h3>Key filters</h3>
			<ul class="sf-content">
				<li><code>searchforge/brief/content</code> &mdash; Modify brief content before export.</li>
				<li><code>searchforge/score/weights</code> &mdash; Adjust score component weights.</li>
				<li><code>searchforge/llms_txt/content</code> &mdash; Filter llms.txt output before writing.</li>
				<li><code>searchforge/export/filename</code> &mdash; Customize export file naming.</li>
			</ul>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="api-key-authentication">
			<h2>API Key Authentication</h2>
			<p>Authenticate REST API requests with a static API key instead of WordPress cookies or nonces.</p>
			<h3>Setup</h3>
			<ol class="sf-content">
				<li>Go to <strong>SearchForge &rarr; Settings &rarr; API</strong>.</li>
				<li>Click <strong>Generate API Key</strong>.</li>
				<li>Copy the key (shown only once).</li>
			</ol>
			<h3>Usage</h3>
			<p>Pass the key via HTTP header:</p>
			<p><code>X-SearchForge-Key: sf_live_abc123...</code></p>
			<p>Or as a query parameter: <code>?sf_key=sf_live_abc123...</code></p>
			<p>Keys can be revoked anytime from the settings page. Each key has configurable permissions (read-only or full access).</p>
		</article>

		<hr style="border: none; border-top: 1px solid var(--sf-border); margin: var(--space-2xl) 0;">

		<article class="sf-doc-section" id="webhook-events">
			<h2>Webhook Events</h2>
			<p>Receive HTTP POST callbacks when events occur in SearchForge. Useful for CI/CD pipelines, Slack bots, or custom dashboards.</p>
			<h3>Setup</h3>
			<p>Configure webhook URLs at <strong>SearchForge &rarr; Settings &rarr; Webhooks</strong>. Each URL can subscribe to specific events.</p>
			<h3>Available events</h3>
			<ul class="sf-content">
				<li><code>sync.complete</code> &mdash; Data sync finished successfully.</li>
				<li><code>sync.failed</code> &mdash; Data sync encountered an error.</li>
				<li><code>alert.ranking_drop</code> &mdash; Significant ranking decrease detected.</li>
				<li><code>alert.content_decay</code> &mdash; Content decay threshold exceeded.</li>
				<li><code>export.complete</code> &mdash; Scheduled export finished.</li>
				<li><code>score.changed</code> &mdash; SearchForge Score changed significantly.</li>
			</ul>
			<h3>Payload format</h3>
			<p>JSON payload with event type, timestamp, affected page/keyword, and relevant metrics. HMAC-SHA256 signature in <code>X-SearchForge-Signature</code> header for verification.</p>
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
