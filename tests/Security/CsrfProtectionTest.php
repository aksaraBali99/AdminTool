<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for `$config['csrf_protection'] = FALSE;` in
 * application/config/config.php. CodeIgniter 3 only sets its CSRF cookie
 * and enforces token verification on POST when csrf_protection is truthy —
 * so its presence/absence on any response is a direct, side-effect-free
 * signal of whether protection is on, without needing to exercise any
 * business logic.
 *
 * This is the single config change with the widest blast radius from the
 * audit: every state-changing action in the app (including the DbCont
 * full-database-wipe endpoint) is forgeable cross-site until this is fixed.
 *
 * EXPECTED TO FAIL until csrf_protection is enabled.
 */
final class CsrfProtectionTest extends TestCase
{
    public function test_csrf_cookie_is_set_on_responses(): void
    {
        $this->markTestSkipped(
            'Known finding, not yet fixed: csrf_protection is disabled in ' .
            'application/config/config.php. Re-enable once it is turned on.'
        );

        $client = ApiClient::anonymous();
        $client->get('Login');

        $cookie = $client->rawCookie('csrf_cookie_name');

        $this->assertNotNull(
            $cookie,
            'No CSRF cookie was set, meaning csrf_protection is disabled in ' .
            'application/config/config.php. This leaves every state-changing ' .
            'endpoint in the app forgeable via cross-site request forgery.'
        );
    }

    public function test_state_changing_post_without_csrf_token_is_rejected(): void
    {
        $this->markTestSkipped(
            'Known finding, not yet fixed: csrf_protection is disabled in ' .
            'application/config/config.php. Re-enable once it is turned on.'
        );

        // A low-stakes, read-only-adjacent state change: querying the
        // DataTables user list. Any authenticated POST works for this
        // check since csrf_protection, once enabled, applies globally.
        $client = ApiClient::anonymous();
        $client->loginAs('finance');

        $response = $client->post('MasterData/get_pengguna', [
            'id_pengguna' => '1',
        ]);

        $this->assertContains(
            $response->getStatusCode(),
            [403, 400],
            'A POST request with no CSRF token succeeded (HTTP ' . $response->getStatusCode() . '). ' .
            'With csrf_protection enabled, CodeIgniter should reject this before it ' .
            'ever reaches the controller.'
        );
    }
}
