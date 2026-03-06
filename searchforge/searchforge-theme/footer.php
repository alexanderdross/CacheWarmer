</main>

<footer class="sf-footer" role="contentinfo">
	<div class="sf-container sf-footer__inner">
		<div class="sf-footer__brand">
			<img src="<?php echo esc_url( SF_THEME_URI ); ?>/assets/images/logo-white.svg" alt="SearchForge" width="140" height="28">
			<p class="sf-footer__tagline">SEO Data, LLM-Ready.</p>
		</div>

		<div class="sf-footer__columns">
			<div class="sf-footer__column">
				<h3>Resources</h3>
				<ul>
					<li><a href="#features">Features</a></li>
					<li><a href="/pricing/">Pricing</a></li>
					<li><a href="/changelog/">Changelog</a></li>
					<li><a href="/enterprise/">Enterprise</a></li>
				</ul>
			</div>
			<div class="sf-footer__column">
				<h3>Documentation</h3>
				<ul>
					<li><a href="/docs/">Getting Started</a></li>
					<li><a href="/docs/api/">REST API</a></li>
					<li><a href="/docs/wp-cli/">WP-CLI</a></li>
					<li><a href="/docs/configuration/">Configuration</a></li>
				</ul>
			</div>
			<div class="sf-footer__column">
				<h3>Data Sources</h3>
				<ul>
					<li><a href="/docs/gsc/">Google Search Console</a></li>
					<li><a href="/docs/bing/">Bing Webmaster Tools</a></li>
					<li><a href="/docs/ga4/">Google Analytics 4</a></li>
					<li><a href="/docs/trends/">Google Trends</a></li>
					<li><a href="/docs/kwp/">Keyword Planner</a></li>
					<li><a href="/docs/gbp/">Business Profile</a></li>
				</ul>
			</div>
			<div class="sf-footer__column">
				<h3>Integrations</h3>
				<ul>
					<li><a href="/docs/yoast/">Yoast SEO</a></li>
					<li><a href="/docs/rank-math/">Rank Math</a></li>
					<li><a href="/docs/cachewarmer/">CacheWarmer</a></li>
					<li><a href="/docs/github/">GitHub Export</a></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="sf-footer__bottom">
		<div class="sf-container sf-footer__bottom-inner">
			<p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> Dross:Media GmbH &middot; Made with &hearts; in Stuttgart</p>
			<ul class="sf-footer__legal">
				<li><a href="https://dross.net/imprint/">Imprint</a></li>
				<li><a href="https://dross.net/privacy-policy/">Privacy Policy</a></li>
				<li><a href="https://dross.net/contact/?topic=searchforge">Contact</a></li>
			</ul>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
