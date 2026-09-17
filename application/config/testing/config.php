<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * CodeIgniter loads this file after the base application/config/config.php
 * whenever CI_ENV=testing (see index.php) - only our test harnesses set
 * that (tests/run-tests.sh, tests/serve-test-app.sh), never production.
 *
 * The base config hardcodes base_url to "https://", which is fine behind
 * the real HTTPS proxy this app runs on in production, but every link,
 * redirect and AJAX call the app generates (site_url()/base_url()) then
 * points at https:// even when tests are serving it over plain HTTP via
 * `php -S`. A real browser can't complete a TLS handshake against that and
 * silently fails (PHPUnit/Guzzle tests never notice, since they hit literal
 * paths directly instead of following the app's own generated links) - see
 * ../../../e2e/README.md.
 */
$config['base_url'] = "http://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']) . "/";
