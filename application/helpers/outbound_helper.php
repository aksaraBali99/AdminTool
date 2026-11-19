<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Outbound messages (WhatsApp via Fonnte, and email if it is ever used) are
 * only ever sent from production. Everywhere else (DEV, local, CI) nothing
 * is sent to anyone: sendWa() returns a "NOT SENT" answer without making a
 * request, and MY_Email::send() returns TRUE without sending.
 *
 * Fail safe: only ENVIRONMENT === 'production' exactly (case-sensitive, no
 * trimming) counts as live. Nothing in the database or the server settings
 * can switch sending on anywhere else. ENVIRONMENT comes from CI_ENV
 * (index.php) and defaults to 'development', so a site, a cron job or a
 * command line run without CI_ENV=production never sends.
 */

if ( ! function_exists('outbound_is_live'))
{
	/**
	 * TRUE only for exactly 'production'.
	 *
	 * @param	mixed	$env	usually ENVIRONMENT
	 * @return	bool
	 */
	function outbound_is_live($env)
	{
		return $env === 'production';
	}
}

if ( ! function_exists('outbound_fonnte_url'))
{
	/**
	 * The URL sendWa() posts to.
	 *
	 * Outside production, OUTBOUND_FONNTE_URL (an environment variable) can
	 * point it at a local stub, so tests can prove that no request is made:
	 * if the gate in sendWa() were ever bypassed, the stub would record the
	 * hit instead of Fonnte receiving a real message. In production the
	 * variable is ignored and the real Fonnte URL is always used.
	 *
	 * @param	mixed	$env	usually ENVIRONMENT
	 * @return	string
	 */
	function outbound_fonnte_url($env)
	{
		$fonnte = 'https://api.fonnte.com/send';
		if (outbound_is_live($env))
		{
			return $fonnte;
		}

		$override = getenv('OUTBOUND_FONNTE_URL');
		return (is_string($override) && $override !== '') ? $override : $fonnte;
	}
}
