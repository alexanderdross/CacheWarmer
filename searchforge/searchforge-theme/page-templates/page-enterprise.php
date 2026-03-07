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
		<?php get_template_part( 'template-parts/breadcrumb' ); ?>
		<h1><span class="sf-gradient-text">SearchForge Enterprise</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem; max-width: 640px; margin: var(--space-md) auto 0;">
			Unlimited sites, unlimited scale. For agencies and organizations managing SEO across a portfolio.
		</p>
		<div class="sf-hero__actions" style="justify-content: center; margin-top: var(--space-xl);">
			<a href="/checkout/?tier=enterprise" class="sf-btn sf-btn--primary sf-btn--lg">Get Enterprise</a>
			<a href="https://dross.net/contact/?topic=searchforge-enterprise" class="sf-btn sf-btn--outline-light sf-btn--lg">Contact Sales</a>
		</div>
	</div>
</section>

<!-- Enterprise Features -->
<section class="sf-section" id="features">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Everything in Agency, plus:</h2>
		</div>

		<div class="sf-grid sf-grid--3">
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited Sites</h3>
				<p class="sf-card__desc">Manage SEO data across your entire portfolio. No per-site fees, no scaling limits. One license for all your WordPress installations.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited Locations</h3>
				<p class="sf-card__desc">Google Business Profile and Bing Places for all your locations. Local SEO at scale for multi-location businesses and franchises.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited AEO Queries</h3>
				<p class="sf-card__desc">Monitor AI visibility across your entire keyword set. No monthly query caps on ChatGPT, Perplexity, and Google AI Overview tracking.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Unlimited Competitor Intel</h3>
				<p class="sf-card__desc">No caps on SERP snapshots, content gap analysis, or competitor monitoring. Track every keyword that matters to your business.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Priority Support</h3>
				<p class="sf-card__desc">Direct email support with guaranteed 24-hour response time. Dedicated onboarding assistance and migration support.</p>
			</div>
			<div class="sf-card sf-card--accent">
				<h3 class="sf-card__title">Audit Log</h3>
				<p class="sf-card__desc">Complete audit trail of all actions, exports, and configuration changes. Required for compliance workflows and team accountability.</p>
			</div>
		</div>
	</div>
</section>

<!-- Use Cases -->
<section class="sf-section sf-section--light" id="use-cases">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Built for Scale</h2>
			<p class="sf-text--muted">Enterprise is designed for teams and organizations that manage SEO across multiple properties.</p>
		</div>

		<div class="sf-grid sf-grid--3">
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Digital Agencies</h3>
				<p class="sf-card__desc">Manage all client sites from one license. White-label reports, client portals, and automated export scheduling save hours per week.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">Multi-Site Publishers</h3>
				<p class="sf-card__desc">Track SEO performance across a network of content sites. Centralized dashboards, cross-site keyword analysis, and unified reporting.</p>
			</div>
			<div class="sf-card sf-card--bordered">
				<h3 class="sf-card__title">E-commerce Portfolios</h3>
				<p class="sf-card__desc">Monitor product page rankings, local store visibility, and AI citations across your entire catalog and location network.</p>
			</div>
		</div>
	</div>
</section>

<!-- Full Feature Comparison -->
<section class="sf-section" id="compare">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Enterprise vs. Agency</h2>
		</div>

		<div class="sf-comparison-table-wrapper">
			<table class="sf-comparison-table">
				<thead>
					<tr>
						<th scope="col">Capability</th>
						<th scope="col">Agency &euro;249/yr</th>
						<th scope="col">Enterprise &euro;599/yr</th>
					</tr>
				</thead>
				<tbody>
					<tr><td>Sites</td><td>10</td><td>Unlimited</td></tr>
					<tr><td>Team Members</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Google Business Profile Locations</td><td>10</td><td>Unlimited</td></tr>
					<tr><td>Bing Places Locations</td><td>10</td><td>Unlimited</td></tr>
					<tr><td>AI Visibility Queries</td><td>200/month</td><td>Unlimited</td></tr>
					<tr><td>Competitor Keywords</td><td>100/month</td><td>Unlimited</td></tr>
					<tr><td>AI Content Briefs</td><td>50/month</td><td>Unlimited</td></tr>
					<tr><td>Data Retention</td><td>24 months</td><td>24 months</td></tr>
					<tr><td>Audit Log</td><td>&mdash;</td><td>&#10003;</td></tr>
					<tr><td>Priority Support (24h SLA)</td><td>&mdash;</td><td>&#10003;</td></tr>
					<tr><td>Custom Sync Frequencies</td><td>&mdash;</td><td>&#10003;</td></tr>
					<tr><td>Discord &amp; Webhook Alerts</td><td>&mdash;</td><td>&#10003;</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>

<!-- Pricing -->
<section class="sf-section sf-section--light" id="pricing">
	<div class="sf-container sf-container--narrow" style="text-align: center;">
		<h2>&euro;599/year</h2>
		<p class="sf-text--muted" style="margin-bottom: var(--space-lg);">
			Or &euro;1,499 one-time for a lifetime Enterprise license.
		</p>
		<p class="sf-text--muted" style="margin-bottom: var(--space-xl);">
			Volume discounts available for 5+ licenses. All prices exclude VAT where applicable.
		</p>

		<div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
			<a href="/checkout/?tier=enterprise" class="sf-btn sf-btn--primary sf-btn--lg">Get Enterprise &euro;599/yr</a>
			<a href="/checkout/?tier=lifetime-enterprise" class="sf-btn sf-btn--outline sf-btn--lg">Lifetime &euro;1,499</a>
		</div>

		<p class="sf-text--muted" style="margin-top: var(--space-lg); font-size: 0.875rem;">
			30-day money-back guarantee. Cancel anytime. Development domains are always free.
		</p>
	</div>
</section>

<!-- FAQ -->
<section class="sf-section" id="faq">
	<div class="sf-container sf-container--narrow">
		<div class="sf-section__header">
			<h2>Enterprise FAQ</h2>
		</div>

		<div class="sf-faq" role="list">
			<?php
			$enterprise_faqs = [
				[ 'q' => 'What counts as a "site" for licensing?',             'a' => 'Each WordPress installation (domain) counts as one site. Subdomains count separately. Staging and development domains (localhost, *.local, *.dev, *.test) are always free and do not count toward your limit.' ],
				[ 'q' => 'Can I share one Enterprise license across clients?',  'a' => 'Yes. Enterprise covers unlimited WordPress installations. You can use it across all your client sites, your own properties, and any staging environments.' ],
				[ 'q' => 'Is there volume pricing for large deployments?',      'a' => 'Yes. Contact us at support@drossmedia.de for custom pricing on 5+ licenses, dedicated support SLAs, or custom feature development.' ],
				[ 'q' => 'What does "priority support" include?',              'a' => 'Guaranteed 24-hour response time via email. Dedicated onboarding call for new installations. Direct access to the development team for bug reports and feature requests.' ],
				[ 'q' => 'How does the audit log work?',                       'a' => 'Every action in SearchForge is logged: syncs, exports, setting changes, API key usage, and team member activity. Logs are stored in your WordPress database and can be exported as CSV.' ],
				[ 'q' => 'Can I downgrade from Enterprise to Agency?',         'a' => 'Yes. Downgrade takes effect at the end of your billing period. Sites beyond the Agency limit (10) will become read-only. No data is deleted.' ],
			];
			foreach ( $enterprise_faqs as $i => $faq ) :
				$slug = sanitize_title( $faq['q'] );
			?>
				<div class="sf-faq__item" id="<?php echo esc_attr( $slug ); ?>" role="listitem">
					<button class="sf-faq__question" aria-expanded="false" aria-controls="enterprise-faq-<?php echo esc_attr( $i ); ?>" title="<?php echo esc_attr( $faq['q'] ); ?>">
						<span><?php echo esc_html( $faq['q'] ); ?></span>
						<span class="sf-faq__chevron" aria-hidden="true"></span>
					</button>
					<div class="sf-faq__answer" id="enterprise-faq-<?php echo esc_attr( $i ); ?>" hidden>
						<p><?php echo esc_html( $faq['a'] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<script type="application/ld+json">
<?php
echo wp_json_encode(
	[
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(
			function ( $faq ) {
				return [
					'@type' => 'Question',
					'name'  => $faq['q'],
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $faq['a'],
					],
				];
			},
			$enterprise_faqs
		),
	],
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
?>
</script>

<?php get_template_part( 'template-parts/cachewarmer-bundle' ); ?>

<!-- Final CTA -->
<section class="sf-section sf-section--dark sf-final-cta">
	<div class="sf-container" style="text-align: center;">
		<h2>Ready to Scale Your SEO Intelligence?</h2>
		<p class="sf-text--inverse-muted">Start with a 14-day free trial of all Enterprise features. No credit card required.</p>
		<div class="sf-hero__actions" style="justify-content: center; margin-top: var(--space-xl);">
			<a href="/checkout/?tier=enterprise" class="sf-btn sf-btn--primary sf-btn--lg">Start Free Trial</a>
			<a href="https://dross.net/contact/?topic=searchforge-enterprise" class="sf-btn sf-btn--outline-light sf-btn--lg">Talk to Sales</a>
		</div>
	</div>
</section>

<?php
get_footer();
