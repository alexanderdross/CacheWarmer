<?php

namespace SearchForge\Export;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class LlmsTxt {

	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'template_redirect', [ $this, 'handle_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
	}

	public function add_rewrite_rules(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?searchforge_llms=1', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?searchforge_llms=full', 'top' );
	}

	public function add_query_vars( array $vars ): array {
		$vars[] = 'searchforge_llms';
		return $vars;
	}

	public function handle_request(): void {
		$llms = get_query_var( 'searchforge_llms' );
		if ( ! $llms ) {
			return;
		}

		if ( ! Settings::get( 'llms_txt_enabled' ) ) {
			status_header( 404 );
			exit;
		}

		$content = 'full' === $llms ? $this->generate_full() : $this->generate_basic();

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=86400' );
		header( 'X-Generator: SearchForge/' . SEARCHFORGE_VERSION );

		echo $content;
		exit;
	}

	/**
	 * Generate basic llms.txt — site info + page list.
	 */
	private function generate_basic(): string {
		$site_name = get_bloginfo( 'name' );
		$site_desc = get_bloginfo( 'description' );
		$site_url  = home_url();

		$txt  = "# {$site_name}\n\n";
		$txt .= "> {$site_desc}\n\n";

		// Important pages.
		$txt .= "## Important Pages\n\n";
		$txt .= $this->get_important_pages();

		// Sitemap reference.
		$sitemap_url = home_url( '/sitemap.xml' );
		$txt .= "\n## Sitemap\n\n";
		$txt .= "- [{$sitemap_url}]({$sitemap_url})\n";

		return $txt;
	}

	/**
	 * Generate llms-full.txt — includes SEO data if Pro.
	 */
	private function generate_full(): string {
		$txt = $this->generate_basic();

		if ( ! Settings::is_pro() ) {
			$txt .= "\n## Note\n\n";
			$txt .= "Upgrade to SearchForge Pro for enriched SEO data in this file.\n";
			return $txt;
		}

		global $wpdb;

		$latest_date = $wpdb->get_var(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots WHERE source = 'gsc'"
		);

		if ( ! $latest_date ) {
			return $txt;
		}

		$top_pages = $wpdb->get_results( $wpdb->prepare(
			"SELECT page_path, clicks, impressions, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND snapshot_date = %s AND device = 'all'
			ORDER BY clicks DESC
			LIMIT 50",
			$latest_date
		), ARRAY_A );

		if ( empty( $top_pages ) ) {
			return $txt;
		}

		$txt .= "\n## Search Performance (last 28 days)\n\n";

		foreach ( $top_pages as $page ) {
			$url = home_url( $page['page_path'] );
			$txt .= sprintf(
				"- [%s](%s) — %s clicks, %s impressions, position %s\n",
				$page['page_path'],
				$url,
				number_format( (int) $page['clicks'] ),
				number_format( (int) $page['impressions'] ),
				round( (float) $page['position'], 1 )
			);
		}

		// Top keywords.
		$top_keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT query, SUM(clicks) as clicks, AVG(position) as position
			FROM {$wpdb->prefix}sf_keywords
			WHERE source = 'gsc' AND snapshot_date = %s
			GROUP BY query
			ORDER BY clicks DESC
			LIMIT 30",
			$latest_date
		), ARRAY_A );

		if ( ! empty( $top_keywords ) ) {
			$txt .= "\n## Top Search Queries\n\n";
			foreach ( $top_keywords as $kw ) {
				$txt .= sprintf(
					"- \"%s\" — %s clicks, position %s\n",
					$kw['query'],
					number_format( (int) $kw['clicks'] ),
					round( (float) $kw['position'], 1 )
				);
			}
		}

		return $txt;
	}

	/**
	 * Get important pages (published posts/pages) for llms.txt.
	 */
	private function get_important_pages(): string {
		$txt = '';

		// Homepage.
		$txt .= "- [" . get_bloginfo( 'name' ) . "](" . home_url() . ")\n";

		// Top published pages.
		$pages = get_posts( [
			'post_type'      => [ 'page', 'post' ],
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		] );

		foreach ( $pages as $page ) {
			$txt .= sprintf(
				"- [%s](%s)\n",
				$page->post_title,
				get_permalink( $page )
			);
		}

		return $txt;
	}
}
