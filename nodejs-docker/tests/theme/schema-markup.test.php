#!/usr/bin/env php
<?php
/**
 * Schema Markup Validation Tests for CacheWarmer WordPress Theme
 *
 * Validates that the JSON-LD @graph block produced by functions.php contains
 * all required schema types with correct structure and cross-references.
 *
 * Usage: php tests/theme/schema-markup.test.php
 */

$passed = 0;
$failed = 0;
$errors = [];

function assert_test(string $name, bool $condition, string $message = ''): void {
    global $passed, $failed, $errors;
    if ($condition) {
        $passed++;
        echo "  PASS  {$name}\n";
    } else {
        $failed++;
        $detail = $message ?: 'Assertion failed';
        $errors[] = "{$name}: {$detail}";
        echo "  FAIL  {$name} — {$detail}\n";
    }
}

// ---------------------------------------------------------------------------
// Read the functions.php and extract the schema-building logic
// ---------------------------------------------------------------------------

$theme_dir = dirname(__DIR__, 2) . '/theme/wp-content/themes/cachewarmer';
$functions_file = $theme_dir . '/functions.php';

if (!file_exists($functions_file)) {
    echo "ERROR: functions.php not found at {$functions_file}\n";
    exit(1);
}

$source = file_get_contents($functions_file);

echo "\n=== CacheWarmer Theme — Schema Markup Tests ===\n\n";

// ---------------------------------------------------------------------------
// 1. Structural tests (source analysis)
// ---------------------------------------------------------------------------

echo "--- Structural Validation ---\n";

// Single @graph block
$graph_count = substr_count($source, "'@graph'");
assert_test(
    'Single @graph block',
    $graph_count === 1,
    "Expected 1 @graph, found {$graph_count}"
);

// Single script tag output
$script_echoes = preg_match_all('/echo\s+\'<script type="application\/ld\+json/', $source);
assert_test(
    'Single JSON-LD script tag',
    $script_echoes === 1,
    "Expected 1 script echo, found {$script_echoes}"
);

// No redundant @context inside graph nodes (only on root)
$context_count = substr_count($source, "'@context'");
assert_test(
    'Single @context on root only',
    $context_count === 1,
    "Expected 1 @context (root-level), found {$context_count} — nodes inside @graph should not repeat @context"
);

echo "\n--- Required Schema Types ---\n";

// 2. All 8 required schema types present
$required_types = [
    'Organization',
    'WebSite',
    'SoftwareApplication',
    'CollectionPage',
    'WebPage',
    'Article',
    'FAQPage',
    'SiteNavigationElement',
];

foreach ($required_types as $type) {
    // Types may appear as direct assignment or in a ternary expression
    $found = str_contains($source, "'{$type}'");
    assert_test(
        "Schema type: {$type}",
        $found,
        "Type '{$type}' not found in schema output"
    );
}

echo "\n--- @id Cross-References ---\n";

// 3. @id references are used (no inline Organization/WebSite duplication)
$id_refs = [
    'Organization @id' => "'@id'   => \$org_id",
    'WebSite @id'      => "'@id'       => \$website_id",
    'Software @id'     => "'@id'                 => \$product_id",
];

foreach ($id_refs as $label => $pattern) {
    assert_test($label . ' defined', str_contains($source, $pattern));
}

// Publisher uses @id reference
assert_test(
    'Publisher uses @id ref (not inline)',
    str_contains($source, "'publisher' => ['@id' => \$org_id]"),
    'Publisher should reference Organization by @id instead of inlining'
);

// isPartOf uses @id reference
assert_test(
    'isPartOf uses @id ref',
    str_contains($source, "'isPartOf'  => ['@id' => \$website_id]"),
    'isPartOf should reference WebSite by @id'
);

// about uses @id reference
assert_test(
    'about uses @id ref',
    str_contains($source, "'about'     => ['@id' => \$product_id]"),
    'about should reference SoftwareApplication by @id'
);

echo "\n--- AggregateRating Placement ---\n";

// 4. aggregateRating on SoftwareApplication
assert_test(
    'aggregateRating on SoftwareApplication',
    str_contains($source, "'@type'               => 'SoftwareApplication'") &&
    str_contains($source, "'aggregateRating' => ["),
    'SoftwareApplication must include aggregateRating'
);

// 5. aggregateRating on WebPage/CollectionPage (every page)
// The WebPage block should have aggregateRating directly
// Find the $webpage array and check it has aggregateRating
$webpage_section = '';
$pos = strpos($source, '$webpage = [');
if ($pos !== false) {
    $webpage_section = substr($source, $pos, 1200);
}
assert_test(
    'aggregateRating on WebPage/CollectionPage (all pages)',
    str_contains($webpage_section, "'aggregateRating'"),
    'WebPage/CollectionPage must carry aggregateRating for every page'
);

// 6. aggregateRating has itemReviewed back-reference
assert_test(
    'aggregateRating.itemReviewed references SoftwareApplication',
    str_contains($source, "'itemReviewed' => ['@id' => \$product_id]"),
    'WebPage aggregateRating should reference the SoftwareApplication via itemReviewed'
);

echo "\n--- Product Properties on SoftwareApplication ---\n";

// 7. Former Product properties merged into SoftwareApplication
$sw_section = '';
$pos = strpos($source, "'@type'               => 'SoftwareApplication'");
if ($pos !== false) {
    $sw_section = substr($source, $pos, 1200);
}

$product_props = ['brand', 'category', 'offers', 'aggregateRating', 'applicationCategory', 'operatingSystem', 'softwareVersion'];
foreach ($product_props as $prop) {
    assert_test(
        "SoftwareApplication has '{$prop}'",
        str_contains($sw_section, "'{$prop}'"),
        "SoftwareApplication should carry Product property '{$prop}'"
    );
}

echo "\n--- No Redundant Product Schema ---\n";

// 8. Standalone Product type should NOT exist
$product_standalone = preg_match("/'@type'\s*=>\s*'Product'/", $source);
assert_test(
    'No standalone Product schema',
    !$product_standalone,
    'Product was merged into SoftwareApplication — standalone Product should not exist'
);

echo "\n--- SiteNavigationElement ---\n";

// 9. Navigation covers all pages
$nav_pages = ['Features', 'Docs', 'Changelog', 'Enterprise', 'WordPress Plugin', 'Drupal Module', 'Self-Hosted', 'API Keys Setup', 'Pricing'];
foreach ($nav_pages as $page) {
    assert_test(
        "Navigation includes '{$page}'",
        str_contains($source, "'name' => '{$page}'"),
        "SiteNavigationElement should list '{$page}'"
    );
}

echo "\n--- FAQPage Schemas ---\n";

// 10. Homepage FAQ
assert_test(
    'Homepage FAQ present',
    str_contains($source, "if (\$slug === 'home')") && str_contains($source, "'@type'      => 'FAQPage'"),
    'Homepage should have FAQPage schema'
);

// 11. Pricing FAQ
assert_test(
    'Pricing FAQ present',
    str_contains($source, "if (\$slug === 'pricing')") && str_contains($source, "'@id'        => \$pricing_url . '#faq'"),
    'Pricing page should have FAQPage schema'
);

echo "\n--- Article Schema ---\n";

// 12. Article on homepage only
assert_test(
    'Article on homepage',
    str_contains($source, "'@type'         => 'Article'"),
    'Homepage should have Article schema for highlight section'
);

assert_test(
    'Article references Organization by @id',
    str_contains($source, "'author'            => ['@id' => \$org_id]"),
    'Article author should use @id reference'
);

echo "\n--- Security: Output Encoding ---\n";

// 13. Uses wp_json_encode with JSON_UNESCAPED_SLASHES
assert_test(
    'wp_json_encode with JSON_UNESCAPED_SLASHES',
    str_contains($source, 'wp_json_encode($jsonld, JSON_UNESCAPED_SLASHES)'),
    'JSON-LD output should use wp_json_encode with JSON_UNESCAPED_SLASHES'
);

echo "\n--- Page-Specific Ratings ---\n";

// 14. All page slugs have specific ratings
$rating_slugs = ['home', 'features', 'docs', 'pricing', 'api-keys', 'changelog', 'wordpress', 'drupal', 'self-hosted', 'enterprise'];
foreach ($rating_slugs as $slug) {
    assert_test(
        "Rating defined for '{$slug}'",
        str_contains($source, "'{$slug}'") && str_contains($source, "'value'") && str_contains($source, "'count'"),
    );
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";

if ($failed > 0) {
    echo "\nFailures:\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
    exit(1);
}

echo "\nAll schema markup tests passed.\n";
exit(0);
