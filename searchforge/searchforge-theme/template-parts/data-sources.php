<?php
/**
 * Template part: Data sources showcase.
 *
 * @package SearchForge_Theme
 */

$sources = [
	[ 'id' => 'gsc',    'name' => 'Google Search Console', 'tier' => 'Free (10 pages)' ],
	[ 'id' => 'bing',   'name' => 'Bing Webmaster Tools',  'tier' => 'Pro' ],
	[ 'id' => 'ga4',    'name' => 'Google Analytics 4',     'tier' => 'Pro' ],
	[ 'id' => 'kwp',    'name' => 'Keyword Planner',        'tier' => 'Pro' ],
	[ 'id' => 'trends', 'name' => 'Google Trends',          'tier' => 'Pro' ],
	[ 'id' => 'gbp',    'name' => 'Business Profile',       'tier' => 'Pro' ],
	[ 'id' => 'bing-places', 'name' => 'Bing Places',       'tier' => 'Pro' ],
	[ 'id' => 'serp',   'name' => 'SERP Intelligence',      'tier' => 'Pro' ],
];
?>

<section class="sf-section sf-section--light" id="data-sources">
	<div class="sf-container">
		<div class="sf-section__header">
			<h2>8 Data Sources. One Unified Brief.</h2>
			<p class="sf-text--muted">Connect once, sync automatically. All data flows into a single per-page brief.</p>
		</div>

		<div class="sf-source-badges">
			<?php foreach ( $sources as $source ) : ?>
				<span class="sf-badge sf-badge--source">
					<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/icons/<?php echo esc_attr( $source['id'] ); ?>.svg" alt="" width="16" height="16" aria-hidden="true">
					<?php echo esc_html( $source['name'] ); ?>
					<span class="sf-badge__tier"><?php echo esc_html( $source['tier'] ); ?></span>
				</span>
			<?php endforeach; ?>
		</div>

		<div class="sf-code-block sf-code-block--large">
			<div class="sf-code-block__header">
				<span class="sf-code-block__dot sf-code-block__dot--red"></span>
				<span class="sf-code-block__dot sf-code-block__dot--yellow"></span>
				<span class="sf-code-block__dot sf-code-block__dot--green"></span>
				<span class="sf-code-block__filename">master-brief-organic-coffee.md</span>
			</div>
			<pre class="sf-code-block__content"><code># Combined Master Brief: /organic-coffee/
**Generated:** 2026-03-06 · **Sources:** GSC, Bing, GA4, Trends, KWP

## Search Performance (GSC + Bing)
| Query               | Google Pos | Bing Pos | Volume | Trend   |
|---------------------|-----------|----------|--------|---------|
| best organic coffee | 3.2       | 2.8      | 1,900  | Stable  |
| organic coffee beans| 5.1       | 4.3      | 1,200  | ↑ +12%  |
| fair trade coffee   | 8.4       | 6.1      | 880    | ↑ +8%   |

## On-Page Behavior (GA4)
| Metric          | Value  | Benchmark | Signal           |
|-----------------|--------|-----------|------------------|
| Bounce Rate     | 68%    | 45%       | Content mismatch |
| Engagement Time | 1:12   | 2:30      | Below average    |

## AI Visibility (AEO Monitor)
| Engine           | Cited | Citation Rate | Trend    |
|------------------|-------|---------------|----------|
| Google AI Overview| Yes  | 58%           | Stable   |
| ChatGPT          | Yes   | 25%           | ↑ +15%   |
| Perplexity       | No    | 0%            | —        |</code></pre>
		</div>
	</div>
</section>
