<?php

namespace Tests\Outbound;

use PHPUnit\Framework\TestCase;

/**
 * The production-only gate for outbound messages
 * (application/helpers/outbound_helper.php), tested directly: no server.
 */
final class OutboundGateTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        defined('BASEPATH') or define('BASEPATH', __DIR__ . '/../../system/');
        require_once __DIR__ . '/../../application/helpers/outbound_helper.php';
    }

    /** @var string|false OUTBOUND_FONNTE_URL as run-tests.sh set it; other tests use it. */
    private $savedUrl;

    protected function setUp(): void
    {
        $this->savedUrl = getenv('OUTBOUND_FONNTE_URL');
    }

    protected function tearDown(): void
    {
        putenv($this->savedUrl === false ? 'OUTBOUND_FONNTE_URL' : 'OUTBOUND_FONNTE_URL=' . $this->savedUrl);
    }

    public function test_only_exactly_production_is_live(): void
    {
        $this->assertTrue(outbound_is_live('production'));
    }

    /**
     * @dataProvider notLive
     */
    public function test_anything_else_is_not_live($env): void
    {
        $this->assertFalse(outbound_is_live($env), 'Must not send for ENVIRONMENT=' . var_export($env, true));
    }

    public function notLive(): array
    {
        return [
            'testing' => ['testing'],
            'development' => ['development'],
            'empty string' => [''],
            'null' => [null],
            'capitalised' => ['Production'],
            'upper case' => ['PRODUCTION'],
            'trailing space' => ['production '],
            'leading space' => [' production'],
            'trailing newline' => ["production\n"],
            'true' => [true],
            'array' => [['production']],
        ];
    }

    public function test_production_ignores_the_url_override(): void
    {
        putenv('OUTBOUND_FONNTE_URL=http://127.0.0.1:1/stub');
        $this->assertSame('https://api.fonnte.com/send', outbound_fonnte_url('production'));
    }

    public function test_outside_production_the_url_can_point_at_a_stub(): void
    {
        putenv('OUTBOUND_FONNTE_URL=http://127.0.0.1:1/stub');
        $this->assertSame('http://127.0.0.1:1/stub', outbound_fonnte_url('testing'));
        $this->assertSame('http://127.0.0.1:1/stub', outbound_fonnte_url('Production'));
    }

    public function test_without_an_override_the_url_is_fonnte(): void
    {
        putenv('OUTBOUND_FONNTE_URL');
        $this->assertSame('https://api.fonnte.com/send', outbound_fonnte_url('testing'));
        putenv('OUTBOUND_FONNTE_URL=');
        $this->assertSame('https://api.fonnte.com/send', outbound_fonnte_url('testing'));
    }
}
