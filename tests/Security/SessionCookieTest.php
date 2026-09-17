<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for application/config/config.php:417-418 —
 * `cookie_httponly` and `cookie_secure` are both FALSE, which also governs
 * the session cookie (CI3 shares these flags between sessions and regular
 * cookies). With httponly off, any XSS anywhere in the app (see the stored
 * XSS finding in crm/lead.php) can read the session cookie via
 * document.cookie and hijack the account directly.
 *
 * EXPECTED TO FAIL until both flags are set to TRUE.
 */
final class SessionCookieTest extends TestCase
{
    public function test_session_cookie_is_httponly(): void
    {
        $client = new ApiClient();
        $client->loginAs('finance');

        $cookie = $client->rawCookie('ci_session');
        $this->assertNotNull($cookie, 'Expected a ci_session cookie to be set after login.');

        $this->assertTrue(
            (bool) $cookie->getHttpOnly(),
            'The session cookie is missing the HttpOnly flag, so JavaScript ' .
            '(including anything injected via XSS) can read it and steal the session.'
        );
    }

    public function test_session_cookie_is_secure(): void
    {
        $client = new ApiClient();
        $client->loginAs('finance');

        $cookie = $client->rawCookie('ci_session');
        $this->assertNotNull($cookie, 'Expected a ci_session cookie to be set after login.');

        $this->assertTrue(
            (bool) $cookie->getSecure(),
            'The session cookie is missing the Secure flag, so it can be sent over ' .
            'plain HTTP if the app is ever reachable that way.'
        );
    }
}
