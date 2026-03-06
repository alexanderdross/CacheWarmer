<?php
/**
 * Template Name: Enterprise
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<h1><span class="sf-gradient-text">SearchForge Enterprise</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			Unlimited sites, unlimited scale. For agencies and organizations managing SEO across a portfolio.
		</p>
	</div>
</section>

<section class="sf-section">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Enterprise includes everything in Agency, plus:</h2>
		</div>

		<div class="sf-grid sf-grid--3">
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited Sites</h3>
				<p class="sf-card__desc">Manage SEO data across your entire portfolio. No per-site fees, no scaling limits.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited Locations</h3>
				<p class="sf-card__desc">Google Business Profile and Bing Places for all your locations. Local SEO at scale.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited AEO Queries</h3>
				<p class="sf-card__desc">Monitor AI visibility across your entire keyword set. No monthly query caps.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Priority Support</h3>
				<p class="sf-card__desc">Direct email support with guaranteed 24-hour response time. Dedicated onboarding assistance.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Audit Log</h3>
				<p class="sf-card__desc">Complete audit trail of all actions, exports, and configuration changes. Required for compliance workflows.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Custom Configuration</h3>
				<p class="sf-card__desc">Custom data retention, sync frequencies, and export schedules tailored to your organization&rsquo;s needs.</p>
			</div>
		</div>
	</div>
</section>

<section class="sf-section sf-section--light">
	<div class="sf-container sf-container--narrow" style="text-align: center;">
		<h2>&euro;599/year</h2>
		<p class="sf-text--muted" style="margin-bottom: var(--space-xl);">
			Or &euro;1,499 one-time for a lifetime Enterprise license. Volume discounts available for 5+ licenses.
		</p>

		<div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
			<a href="/checkout/?tier=enterprise" class="sf-btn sf-btn--primary sf-btn--lg">Get Enterprise</a>
			<a href="https://dross.net/contact/?topic=searchforge" class="sf-btn sf-btn--outline sf-btn--lg">Contact Sales</a>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/cachewarmer-bundle' ); ?>

<?php
get_footer();
