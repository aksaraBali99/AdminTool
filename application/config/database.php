<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Database credentials were stripped before this was pushed to a public repo.
 * Fill these in locally (or better, load them from environment variables /
 * a git-ignored application/config/database.local.php) — never commit real
 * credentials here.
 */

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => getenv('DB_HOST') ?: 'localhost',
	'username' => getenv('DB_USER') ?: '',
	'password' => getenv('DB_PASS') ?: '',
	'database' => getenv('DB_NAME') ?: '',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
