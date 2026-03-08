# Changelog

All notable changes to the SearchForge WordPress plugin will be documented in this file.

## [2.0.0] - 2026-03-08

### Added
- Comprehensive SEO metadata across the marketing site (meta tags, Open Graph, Twitter Cards).
- Product schema markup with AggregateRating for rich search results.
- SiteNavigationElement structured data for all site pages.
- URL normalization for deduplication in CacheWarmer bundle integration.

### Changed
- Version bump from 1.9.0 to 2.0.0 marking feature completeness.

## [1.9.0] - 2026-03-06

### Added
- Competitor domain tracking with auto-detection from shared keywords.
- SERP snapshot capture for top keywords (positions 1-10).
- Content gap analysis vs. competitors.
- SERP feature tracking (featured snippets, PAA, video packs).
- Competitor markdown export for LLM context.

### Fixed
- GA4 column name mismatch in page detail view.
- Weekly digest email array path alignment.

## [1.8.0] - 2026-02-28

### Added
- Bulk page selection for batch brief export.
- Weekly digest email with key metric changes.
- Dashboard chart for clicks/impressions over time.

### Improved
- Export ZIP now includes all brief formats.

## [1.7.0] - 2026-02-21

### Added
- Detailed per-page view with Chart.js visualizations.
- Keyword table with sorting and filtering.
- Position tracking chart per keyword.
- GA4 behavior metrics integration on page detail.

## [1.6.0] - 2026-02-14

### Added
- API key authentication for external access.
- Pagination and search on pages/keywords lists.
- Onboarding wizard for first-time setup.
- Response caching for dashboard performance.

### Security
- API keys accepted via headers only (not query parameters).

## [1.5.0] - 2026-02-07

### Added
- Ranking drop alerts (email notifications).
- Content decay detection with 7d/30d/90d trends.
- New keyword detection alerts.
- Monitoring dashboard with alert history.

## [1.0.0] - 2026-01-15

### Added
- Google Search Console integration with OAuth.
- Per-page markdown brief export.
- Dashboard with GSC overview.
- llms.txt auto-generation.
- SearchForge Score (basic).
- Free tier with 10-page limit.
