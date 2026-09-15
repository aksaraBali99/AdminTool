<?php
/**
 * Router for `php -S`, used by tests/run-tests.sh and tests/serve-test-app.sh.
 *
 * index.php picks its ENVIRONMENT branch from $_SERVER['CI_ENV'], not
 * getenv('CI_ENV') — and PHP's built-in server does not forward the
 * parent shell's exported environment variables into $_SERVER the way
 * Apache's SetEnv does. Without this, ENVIRONMENT stays 'development',
 * which calls error_reporting(-1) and reliably crashes PHPExcel's library
 * bootstrap under PHP 8.3 (see docs/LOCAL_SETUP.md's Known Issues section —
 * PHPExcel is only verified working with the reduced error_reporting that
 * 'testing'/'production' use).
 */

// Static assets (css/js/images/fonts) must be served as-is, not routed
// through CI3. PHP's built-in server hands *every* request to this script
// unless it returns false for one — and CI3's URI-segment parsing can't
// make sense of a path like /assets/js/core/jquery.js (there's no
// index.php segment in it), so it silently falls back to the default
// controller. A browser asking for jquery.min.js got the Login page's HTML
// back instead — a syntax error the moment the script runs, which breaks
// every bit of client-side JS on the page. The Guzzle-based PHPUnit suites
// never hit this: they only ever request controller endpoints directly,
// never the <link>/<script> assets a real browser also fetches.
if (PHP_SAPI === 'cli-server') {
    $docroot = realpath(__DIR__ . '/..');
    $path = realpath($docroot . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path !== false && is_file($path) && strncmp($path, $docroot, strlen($docroot)) === 0) {
        return false;
    }
}

$_SERVER['CI_ENV'] = getenv('CI_ENV') ?: 'testing';
require __DIR__ . '/../index.php';
