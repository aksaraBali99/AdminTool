<?php
/**
 * Router for the `php -S` server in tests/deploy/deploy-test.sh.
 *
 * Serves whatever the fake site's public_html points to at the moment of
 * each request (the server runs with realpath_cache_size=0), the way the
 * real web server follows the public_html -> current -> releases/<id> links.
 */
$docroot = getenv('DEPLOY_TEST_DOCROOT');
clearstatcache(true);

$_SERVER['CI_ENV'] = 'testing';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $docroot . '/index.php';

chdir($docroot);
require $docroot . '/index.php';
