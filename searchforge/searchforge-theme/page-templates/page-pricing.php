<?php
/**
 * Template Name: Pricing
 *
 * @package SearchForge_Theme
 */

get_header();
?>

<section class="sf-section sf-section--dark sf-hero" style="padding: var(--space-3xl) 0;">
	<div class="sf-container" style="text-align: center;">
		<?php get_template_part( 'template-parts/breadcrumb' ); ?>
		<h1><span class="sf-gradient-text">Simple, Transparent Pricing</span></h1>
		<p class="sf-text--inverse-muted" style="font-size: 1.25rem;">
			Start free. Upgrade when you need more power.
		</p>
	</div>
</section>

<?php get_template_part( 'template-parts/pricing' ); ?>

<!-- Detailed Feature Comparison -->
<section class="sf-section" id="compare">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>Full Feature Comparison</h2>
		</div>

		<div class="sf-comparison-table-wrapper">
			<table class="sf-comparison-table">
				<thead>
					<tr>
						<th scope="col">Feature</th>
						<th scope="col">Free</th>
						<th scope="col">Pro &euro;99/yr</th>
						<th scope="col">Agency &euro;249/yr</th>
						<th scope="col">Enterprise &euro;599/yr</th>
					</tr>
				</thead>
				<tbody>
					<tr><td colspan="5" style="background: var(--sf-bg-light); font-weight: 600;">Data Sources</td></tr>
					<tr><td>Google Search Console</td><td>10 pages</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Bing Webmaster Tools</td><td>&mdash;</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Google Analytics 4</td><td>&mdash;</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Keyword Planner</td><td>&mdash;</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Google Trends</td><td>&mdash;</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Google Business Profile</td><td>&mdash;</td><td>1 location</td><td>10 locations</td><td>Unlimited</td></tr>
					<tr><td>Bing Places</td><td>&mdash;</td><td>1 location</td><td>10 locations</td><td>Unlimited</td></tr>
					<tr><td>AI Visibility Monitor</td><td>&mdash;</td><td>20 queries/mo</td><td>200 queries/mo</td><td>Unlimited</td></tr>
					<tr><td>Competitor Intelligence</td><td>&mdash;</td><td>10 keywords/mo</td><td>100 keywords/mo</td><td>Unlimited</td></tr>

					<tr><td colspan="5" style="background: var(--sf-bg-light); font-weight: 600;">Analysis &amp; Intelligence</td></tr>
					<tr><td>SearchForge Score</td><td>Overall only</td><td>Full breakdown</td><td>Full breakdown</td><td>Full breakdown</td></tr>
					<tr><td>Content Gap Analysis</td><td>Top 3</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>AI Content Briefs</td><td>&mdash;</td><td>10/mo</td><td>50/mo</td><td>Unlimited</td></tr>
					<tr><td>Keyword Clustering</td><td>&mdash;</td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td></tr>
					<tr><td>Cannibalization Detection</td><td>&mdash;</td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td></tr>
					<tr><td>Content Decay Alerts</td><td>&mdash;</td><td>Email</td><td>Email + Slack</td><td>All channels</td></tr>

					<tr><td colspan="5" style="background: var(--sf-bg-light); font-weight: 600;">Export &amp; Output</td></tr>
					<tr><td>Markdown Export</td><td>GSC only</td><td>All sources</td><td>All sources</td><td>All sources</td></tr>
					<tr><td>Combined Master Brief</td><td>&mdash;</td><td>Per page</td><td>Per page</td><td>Per page</td></tr>
					<tr><td>llms.txt</td><td>Basic</td><td>Advanced</td><td>Advanced</td><td>Advanced</td></tr>
					<tr><td>WP-CLI</td><td>&mdash;</td><td>&#10003;</td><td>Multi-site</td><td>Multi-site</td></tr>
					<tr><td>REST API</td><td>&mdash;</td><td>Read-only</td><td>Full CRUD</td><td>Full CRUD</td></tr>
					<tr><td>GitHub / GitLab Push</td><td>&mdash;</td><td>&mdash;</td><td>Auto-push</td><td>Auto-push</td></tr>
					<tr><td>Scheduled Exports</td><td>&mdash;</td><td>&mdash;</td><td>Email, cloud</td><td>Email, cloud</td></tr>
					<tr><td>White-label Reports</td><td>&mdash;</td><td>&mdash;</td><td>PDF/HTML</td><td>PDF/HTML</td></tr>

					<tr><td colspan="5" style="background: var(--sf-bg-light); font-weight: 600;">History &amp; Monitoring</td></tr>
					<tr><td>Data Retention</td><td>30 days</td><td>12 months</td><td>24 months</td><td>24 months</td></tr>
					<tr><td>Historical Snapshots</td><td>&mdash;</td><td>Weekly</td><td>Daily</td><td>Daily</td></tr>
					<tr><td>YoY Comparison</td><td>&mdash;</td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td></tr>
					<tr><td>Weekly Digest Email</td><td>&mdash;</td><td>Single site</td><td>All sites</td><td>All sites</td></tr>
					<tr><td>Slack / Discord Alerts</td><td>&mdash;</td><td>&mdash;</td><td>&#10003;</td><td>&#10003;</td></tr>

					<tr><td colspan="5" style="background: var(--sf-bg-light); font-weight: 600;">Scale &amp; Collaboration</td></tr>
					<tr><td>Sites</td><td>1</td><td>1</td><td>10</td><td>Unlimited</td></tr>
					<tr><td>Team Members</td><td>1</td><td>3</td><td>Unlimited</td><td>Unlimited</td></tr>
					<tr><td>Client Portal</td><td>&mdash;</td><td>&mdash;</td><td>&#10003;</td><td>&#10003;</td></tr>
					<tr><td>CacheWarmer Integration</td><td>&mdash;</td><td>Manual</td><td>Auto-trigger</td><td>Auto-trigger</td></tr>
					<tr><td>Audit Log</td><td>&mdash;</td><td>&mdash;</td><td>&mdash;</td><td>&#10003;</td></tr>
					<tr><td>Priority Support</td><td>&mdash;</td><td>&mdash;</td><td>&mdash;</td><td>&#10003;</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>

<!-- Lifetime Deals -->
<section class="sf-section sf-section--light">
	<div class="sf-container sf-container--narrow" style="text-align: center;">
		<h2>Lifetime Deals Available</h2>
		<p class="sf-text--muted" style="margin-bottom: var(--space-xl);">Pay once, use forever. No recurring fees.</p>
		<div class="sf-grid sf-grid--2">
			<div class="sf-card sf-card--bordered" style="text-align: center;">
				<h3>Lifetime Pro</h3>
				<p style="font-size: 2.5rem; font-family: 'Outfit', sans-serif; font-weight: 700; margin: var(--space-md) 0;">&euro;249</p>
				<p class="sf-text--muted">One-time payment. All Pro features forever for 1 site.</p>
				<a href="/checkout/?tier=lifetime-pro" class="sf-btn sf-btn--primary" style="margin-top: var(--space-md);">Get Lifetime Pro</a>
			</div>
			<div class="sf-card sf-card--bordered" style="text-align: center;">
				<h3>Lifetime Agency</h3>
				<p style="font-size: 2.5rem; font-family: 'Outfit', sans-serif; font-weight: 700; margin: var(--space-md) 0;">&euro;599</p>
				<p class="sf-text--muted">One-time payment. All Agency features forever for 10 sites.</p>
				<a href="/checkout/?tier=lifetime-agency" class="sf-btn sf-btn--primary" style="margin-top: var(--space-md);">Get Lifetime Agency</a>
			</div>
		</div>
	</div>
</section>

<!-- Bundle -->
<?php get_template_part( 'template-parts/cachewarmer-bundle' ); ?>

<!-- FAQ -->
<section class="sf-section" id="faq">
	<div class="sf-container sf-container--narrow">
		<div class="sf-section__header">
			<h2>Pricing FAQ</h2>
		</div>

		<div class="sf-faq" role="list">
			<?php
			$pricing_faqs = [
				[ 'q' => 'Can I try Pro before buying?',                  'a' => 'Yes. Every new installation gets a 14-day Pro trial with all features unlocked. No credit card required.' ],
				[ 'q' => 'What payment methods do you accept?',           'a' => 'We accept all major credit cards (Visa, Mastercard, Amex) and SEPA direct debit via Stripe. All transactions are processed securely by Stripe.' ],
				[ 'q' => 'Can I upgrade or downgrade at any time?',       'a' => 'Yes. Upgrade immediately and get pro-rated credit. Downgrade at the end of your billing period. No lock-in.' ],
				[ 'q' => 'What happens when my subscription expires?',    'a' => 'Your site reverts to the free tier. All your data is preserved, but Pro-only features become read-only. You can re-subscribe anytime to regain full access.' ],
				[ 'q' => 'Do you offer refunds?',                         'a' => 'Yes. 30-day money-back guarantee, no questions asked. If SearchForge isn\'t right for you, we\'ll refund your payment in full.' ],
				[ 'q' => 'Is there a development/staging license?',       'a' => 'Yes. Development licenses are free and include Enterprise features, restricted to localhost, *.local, *.dev, and *.test domains.' ],
				[ 'q' => 'Do you offer discounts for non-profits?',       'a' => 'Yes. Contact us at support@drossmedia.de with proof of non-profit status for a 50% discount on any tier.' ],
			];
			foreach ( $pricing_faqs as $i => $faq ) :
				$slug = sanitize_title( $faq['q'] );
			?>
				<div class="sf-faq__item" id="<?php echo esc_attr( $slug ); ?>" role="listitem">
					<button class="sf-faq__question" aria-expanded="false" aria-controls="pricing-faq-<?php echo esc_attr( $i ); ?>" title="<?php echo esc_attr( $faq['q'] ); ?>">
						<span><?php echo esc_html( $faq['q'] ); ?></span>
						<span class="sf-faq__chevron" aria-hidden="true"></span>
					</button>
					<div class="sf-faq__answer" id="pricing-faq-<?php echo esc_attr( $i ); ?>" hidden>
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
			$pricing_faqs
		),
	],
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
?>
</script>

<?php
get_footer();
