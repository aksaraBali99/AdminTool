<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * CodeIgniter loads this file after the base application/config/config.php
 * whenever CI_ENV=testing (see index.php). That's not only our test
 * harnesses (tests/run-tests.sh, tests/serve-test-app.sh) - docs/LOCAL_SETUP.md
 * also has developers set CI_ENV=testing on their real Laragon vhost, purely
 * to get the same reduced error_reporting as 'production' instead of being
 * flooded with PHP 8.x deprecation notices from this PHP 7-era codebase.
 * That vhost serves the app over real HTTPS.
 *
 * The base config hardcodes base_url to "https://", which is fine behind
 * the real HTTPS proxy this app runs on in production, but every link,
 * redirect and AJAX call the app generates (site_url()/base_url()) then
 * points at https:// even when tests are serving it over plain HTTP via
 * `php -S`. A real browser can't complete a TLS handshake against that and
 * silently fails (PHPUnit/Guzzle tests never notice, since they hit literal
 * paths directly instead of following the app's own generated links) - see
 * ../../../e2e/README.md.
 *
 * So this can't just hardcode "http://" either - that broke the Laragon
 * vhost instead: browsers refuse to load http:// <script src> as "mixed
 * content" on an https:// page, so jQuery silently never loads and the
 * login form's submit handler never attaches (no request, no error, just a
 * dead Login button - the report that led to finding this). Detect the
 * actual scheme instead, matching what the base config's own https:// only
 * gets right for production.
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$config['base_url'] = $scheme . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']) . "/";
