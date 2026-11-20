<?php

namespace Tests\Outbound;

use PHPUnit\Framework\TestCase;

/**
 * MY_Email (application/libraries/MY_Email.php): outside production send()
 * returns TRUE without sending. Tested directly on CodeIgniter's own
 * CI_Email, each case in its own PHP process because ENVIRONMENT is a
 * constant. A subclass replaces the one method that would hand the message
 * to mail/sendmail/SMTP, and counts calls to it instead.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MyEmailTest extends TestCase
{
    private function loadEmail(string $environment): \MY_Email
    {
        define('BASEPATH', __DIR__ . '/../../system/');
        define('ENVIRONMENT', $environment);
        // The few CodeIgniter globals CI_Email uses on this path (the app
        // defines them in system/core/Utf8.php and Common.php).
        define('ICONV_ENABLED', extension_loaded('iconv'));
        define('MB_ENABLED', extension_loaded('mbstring'));
        if (!function_exists('config_item')) {
            eval('function config_item($item) { return $item === "charset" ? "UTF-8" : null; }');
        }
        if (!function_exists('log_message')) {
            eval('function log_message($level, $message) { $GLOBALS["logged"][] = $message; }');
        }
        if (!function_exists('is_php')) {
            eval('function is_php($version) { return version_compare(PHP_VERSION, $version, ">="); }');
        }
        require_once __DIR__ . '/../../application/helpers/outbound_helper.php';
        require_once __DIR__ . '/../../system/libraries/Email.php';
        require_once __DIR__ . '/../../application/libraries/MY_Email.php';
        if (!class_exists('SpyEmail', false)) {
            eval('class SpyEmail extends MY_Email {
                public $handedOver = 0;
                protected function _spool_email() { $this->handedOver++; return TRUE; }
            }');
        }

        $email = new \SpyEmail(['protocol' => 'mail', 'mailtype' => 'text']);
        $email->from('noreply@example.invalid', 'Test');
        $email->to('student@example.invalid');
        $email->subject('Invoice');
        $email->message('Hello');
        return $email;
    }

    public function test_outside_production_nothing_is_handed_over(): void
    {
        $email = $this->loadEmail('testing');

        $this->assertTrue($email->send(), 'send() returns TRUE so calling code carries on as usual');
        $this->assertSame(0, $email->handedOver, 'Outside production the message must never reach mail/sendmail/SMTP.');
        $this->assertStringContainsString('Email NOT SENT (testing)', implode("\n", $GLOBALS['logged'] ?? []));
        $this->assertStringNotContainsString('student@example.invalid', implode("\n", $GLOBALS['logged'] ?? []), 'Logs a count, not addresses.');
    }

    public function test_almost_production_is_still_blocked(): void
    {
        $email = $this->loadEmail('Production');

        $this->assertTrue($email->send());
        $this->assertSame(0, $email->handedOver);
    }

    /** Control: the spy really sees a send, so the two tests above mean something. */
    public function test_in_production_the_message_is_handed_over(): void
    {
        $email = $this->loadEmail('production');

        $email->send();
        $this->assertSame(1, $email->handedOver, 'In production MY_Email must send as CI_Email does.');
    }
}
