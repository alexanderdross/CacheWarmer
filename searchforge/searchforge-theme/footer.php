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
					<li><a href="/docs/searchforge-rest-api-reference/">REST API</a></li>
					<li><a href="/docs/searchforge-wp-cli-commands/">WP-CLI</a></li>
					<li><a href="/docs/searchforge-installation-guide/">Configuration</a></li>
				</ul>
			</div>
			<div class="sf-footer__column">
				<h3>Data Sources</h3>
				<ul>
					<li><a href="/docs/google-search-console-integration/">Google Search Console</a></li>
					<li><a href="/docs/bing-webmaster-tools-integration/">Bing Webmaster Tools</a></li>
					<li><a href="/docs/google-analytics-4-integration/">Google Analytics 4</a></li>
					<li><a href="/docs/google-trends-integration/">Google Trends</a></li>
					<li><a href="/docs/google-keyword-planner-integration/">Keyword Planner</a></li>
					<li><a href="/docs/google-business-profile-integration/">Business Profile</a></li>
				</ul>
			</div>
			<div class="sf-footer__column">
				<h3>Integrations</h3>
				<ul>
					<li><a href="/docs/yoast-seo-integration/">Yoast SEO</a></li>
					<li><a href="/docs/rank-math-integration/">Rank Math</a></li>
					<li><a href="/docs/cachewarmer-integration/">CacheWarmer</a></li>
					<li><a href="/docs/github-gitlab-export/">GitHub Export</a></li>
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
