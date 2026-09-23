<?php

namespace Tests\Support;

/**
 * Extracts a specific stat's rendered value out of a Dashboard page's HTML,
 * anchored on its label text - not a bare assertStringContainsString() on
 * the raw number, which would be too weak (a small integer like "1" or "2"
 * can trivially appear elsewhere on the page by coincidence). Currency
 * values formatted as "Rp X.XXX.XXX" are distinctive enough on their own
 * and don't need this - see tests/Financial/LabaRugiTest.php.
 *
 * The three dashboard views use two different label/value orderings:
 * application/views/dashboard/index.php (superadmin) prints the value
 * first, then the label; admin.php and finance.php print the label first.
 */
class DashboardHtml
{
    /** application/views/dashboard/index.php's `.stat-value` / `.stat-label` pairs (value, then label). */
    public static function statValue(string $html, string $label): ?string
    {
        $pattern = '/class="stat-value">([^<]*)<\/div>\s*<div class="stat-label">' . preg_quote($label, '/') . '</';
        return preg_match($pattern, $html, $m) ? trim($m[1]) : null;
    }

    /** admin.php's / finance.php's `.card-category` / `.card-title` pairs (label, then value). */
    public static function cardTitleValue(string $html, string $label): ?string
    {
        $pattern = '/class="card-category">' . preg_quote($label, '/') . '<\/p>\s*<h4 class="card-title[^"]*">([^<]*)<\/h4>/';
        return preg_match($pattern, $html, $m) ? trim($m[1]) : null;
    }
}
