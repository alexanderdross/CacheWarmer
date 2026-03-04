<?php

namespace SearchForge\Export;

use SearchForge\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class MarkdownExporter {

	/**
	 * Generate a markdown brief for a single page.
	 *
	 * @return string|\WP_Error
	 */
	public function generate_page_brief( string $page_path ): string|\WP_Error {
		global $wpdb;

		$latest_date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND source = 'gsc'",
			$page_path
		) );

		if ( ! $latest_date ) {
			return new \WP_Error( 'no_data', __( 'No data found for this page.', 'searchforge' ) );
		}

		$page_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}sf_snapshots
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc' AND device = 'all'",
			$page_path,
			$latest_date
		), ARRAY_A );

		$keywords = $wpdb->get_results( $wpdb->prepare(
			"SELECT query, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_keywords
			WHERE page_path = %s AND snapshot_date = %s AND source = 'gsc'
			ORDER BY clicks DESC
			LIMIT 50",
			$page_path,
			$latest_date
		), ARRAY_A );

		$site_url = home_url();
		$full_url = $site_url . $page_path;

		$md  = "# SEO Brief: {$page_path}\n";
		$md .= "**Source:** SearchForge (Google Search Console)\n";
		$md .= "**URL:** {$full_url}\n";
		$md .= "**Data Period:** 28 days ending {$latest_date}\n";
		$md .= "**Generated:** " . gmdate( 'Y-m-d H:i' ) . " UTC\n\n";

		$md .= "---\n\n";

		// Page metrics.
		$md .= "## Page Performance\n";
		$md .= "| Metric | Value |\n";
		$md .= "|--------|-------|\n";
		$md .= "| Clicks | " . number_format( (int) $page_data['clicks'] ) . " |\n";
		$md .= "| Impressions | " . number_format( (int) $page_data['impressions'] ) . " |\n";
		$md .= "| CTR | " . round( (float) $page_data['ctr'] * 100, 1 ) . "% |\n";
		$md .= "| Avg Position | " . round( (float) $page_data['position'], 1 ) . " |\n\n";

		// Keywords.
		if ( ! empty( $keywords ) ) {
			$md .= "## Keywords (" . count( $keywords ) . " shown)\n";
			$md .= "| Keyword | Clicks | Impressions | CTR | Position |\n";
			$md .= "|---------|--------|-------------|-----|----------|\n";

			foreach ( $keywords as $kw ) {
				$md .= sprintf(
					"| %s | %s | %s | %s%% | %s |\n",
					$this->escape_md( $kw['query'] ),
					number_format( (int) $kw['clicks'] ),
					number_format( (int) $kw['impressions'] ),
					round( (float) $kw['ctr'] * 100, 1 ),
					round( (float) $kw['position'], 1 )
				);
			}

			$md .= "\n";

			// Quick insights.
			$md .= "## Quick Insights\n";
			$md .= $this->generate_insights( $page_data, $keywords );
		}

		return $md;
	}

	/**
	 * Generate a master brief for the entire site.
	 *
	 * @return string|\WP_Error
	 */
	public function generate_site_brief(): string|\WP_Error {
		global $wpdb;

		$latest_date = $wpdb->get_var(
			"SELECT MAX(snapshot_date) FROM {$wpdb->prefix}sf_snapshots WHERE source = 'gsc'"
		);

		if ( ! $latest_date ) {
			return new \WP_Error( 'no_data', __( 'No GSC data available. Run a sync first.', 'searchforge' ) );
		}

		$page_limit = Settings::get_page_limit();
		$limit_clause = $page_limit > 0 ? $wpdb->prepare( 'LIMIT %d', $page_limit ) : '';

		$pages = $wpdb->get_results( $wpdb->prepare(
			"SELECT page_path, clicks, impressions, ctr, position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND snapshot_date = %s AND device = 'all'
			ORDER BY clicks DESC
			{$limit_clause}",
			$latest_date
		), ARRAY_A );

		$totals = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(clicks) as clicks, SUM(impressions) as impressions,
				AVG(ctr) as ctr, AVG(position) as position
			FROM {$wpdb->prefix}sf_snapshots
			WHERE source = 'gsc' AND snapshot_date = %s AND device = 'all'",
			$latest_date
		), ARRAY_A );

		$site_url = home_url();

		$md  = "# SearchForge Site Brief: {$site_url}\n";
		$md .= "**Source:** Google Search Console\n";
		$md .= "**Data Period:** 28 days ending {$latest_date}\n";
		$md .= "**Generated:** " . gmdate( 'Y-m-d H:i' ) . " UTC\n";
		$md .= "**Pages Tracked:** " . count( $pages ) . "\n\n";

		$md .= "---\n\n";

		$md .= "## Site Overview\n";
		$md .= "| Metric | Value |\n";
		$md .= "|--------|-------|\n";
		$md .= "| Total Clicks | " . number_format( (int) $totals['clicks'] ) . " |\n";
		$md .= "| Total Impressions | " . number_format( (int) $totals['impressions'] ) . " |\n";
		$md .= "| Avg CTR | " . round( (float) $totals['ctr'] * 100, 1 ) . "% |\n";
		$md .= "| Avg Position | " . round( (float) $totals['position'], 1 ) . " |\n\n";

		$md .= "## Top Pages\n";
		$md .= "| Page | Clicks | Impressions | CTR | Position |\n";
		$md .= "|------|--------|-------------|-----|----------|\n";

		foreach ( $pages as $page ) {
			$md .= sprintf(
				"| %s | %s | %s | %s%% | %s |\n",
				$this->escape_md( $page['page_path'] ),
				number_format( (int) $page['clicks'] ),
				number_format( (int) $page['impressions'] ),
				round( (float) $page['ctr'] * 100, 1 ),
				round( (float) $page['position'], 1 )
			);
		}

		$md .= "\n";

		return $md;
	}

	/**
	 * Generate heuristic insights without external AI.
	 */
	private function generate_insights( array $page_data, array $keywords ): string {
		$md = '';

		// High impressions, low CTR.
		$low_ctr = array_filter( $keywords, function ( $kw ) {
			return (int) $kw['impressions'] > 50 && (float) $kw['ctr'] < 0.02;
		} );

		if ( ! empty( $low_ctr ) ) {
			$md .= "\n### Low CTR Opportunities\n";
			$md .= "These keywords have high impressions but low CTR — consider optimizing title/description:\n\n";
			foreach ( array_slice( $low_ctr, 0, 5 ) as $kw ) {
				$md .= sprintf(
					"- **%s** — %s impressions, %s%% CTR, position %s\n",
					$kw['query'],
					number_format( (int) $kw['impressions'] ),
					round( (float) $kw['ctr'] * 100, 1 ),
					round( (float) $kw['position'], 1 )
				);
			}
			$md .= "\n";
		}

		// Close to page 1.
		$almost_page1 = array_filter( $keywords, function ( $kw ) {
			$pos = (float) $kw['position'];
			return $pos > 10 && $pos <= 20 && (int) $kw['impressions'] > 20;
		} );

		if ( ! empty( $almost_page1 ) ) {
			$md .= "\n### Almost Page 1 (positions 11-20)\n";
			$md .= "Keywords close to page 1 with existing impressions:\n\n";
			foreach ( array_slice( $almost_page1, 0, 5 ) as $kw ) {
				$md .= sprintf(
					"- **%s** — position %s, %s impressions\n",
					$kw['query'],
					round( (float) $kw['position'], 1 ),
					number_format( (int) $kw['impressions'] )
				);
			}
			$md .= "\n";
		}

		if ( empty( $md ) ) {
			$md = "No specific actionable insights detected for this page.\n";
		}

		return $md;
	}

	private function escape_md( string $text ): string {
		return str_replace( '|', '\\|', $text );
	}
}
