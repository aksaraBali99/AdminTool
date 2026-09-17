<?php
/**
 * Router for `php -S`, used only by tests/run-tests.sh.
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
$_SERVER['CI_ENV'] = getenv('CI_ENV') ?: 'testing';
require __DIR__ . '/../index.php';
