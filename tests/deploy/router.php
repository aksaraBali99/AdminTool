<?php
/**
 * Router for the `php -S` server in tests/deploy/deploy-test.sh.
 *
 * Serves whatever the fake site's public_html points to at the moment of
 * each request (the server runs with realpath_cache_size=0 and
 * opcache.enable=0, so neither cache serves the previous release), the way
 * the real web server follows the public_html -> current -> releases/<id>
 * links.
 *
 * Each request is logged to the server's log (shown by the test when a
 * check fails): the folder it was served from, the HTTP status, the start
 * of the answer and PHP's opcache state. Test data only; the test checks
 * its database password is never in that log.
 */
$docroot = getenv('DEPLOY_TEST_DOCROOT');
clearstatcache(true);

$_SERVER['CI_ENV'] = 'testing';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $docroot . '/index.php';

$served_from = realpath($docroot);
$uri = $_SERVER['REQUEST_URI'] ?? '';
ob_start(function ($body, $phase) use ($served_from, $uri) {
    static $logged = false;
    if (!$logged) {
        $logged = true;
        $start = preg_replace('/[^\x20-\x7e]+/', ' ', substr($body, 0, 160));
        $opcache = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
        error_log(sprintf('router: %s from %s -> HTTP %d: %s [sapi=%s opcache.enable=%s enable_cli=%s active=%s revalidate_freq=%s]',
            $uri, $served_from, http_response_code(), $start, PHP_SAPI,
            var_export(ini_get('opcache.enable'), true), var_export(ini_get('opcache.enable_cli'), true),
            is_array($opcache) && !empty($opcache['opcache_enabled']) ? 'yes' : 'no',
            var_export(ini_get('opcache.revalidate_freq'), true)));
    }
    return $body;
});

chdir($docroot);
require $docroot . '/index.php';
