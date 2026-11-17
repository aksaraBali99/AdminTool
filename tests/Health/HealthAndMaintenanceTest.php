<?php

namespace Tests\Health;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * The Health page and maintenance mode, which the automated deploy relies on
 * (docs/HEALTH_AND_MAINTENANCE.md):
 *  - Health answers JSON without a login, and reports the database and
 *    migrations as ok on the migrated test database.
 *  - While maintenance.on exists next to index.php, every page answers 503
 *    with the maintenance page, except Health.
 */
final class HealthAndMaintenanceTest extends TestCase
{
    private const FLAG = __DIR__ . '/../../maintenance.on';

    protected function tearDown(): void
    {
        // Never leave the test app stuck in maintenance mode.
        if (file_exists(self::FLAG)) {
            unlink(self::FLAG);
        }
    }

    public function test_health_reports_ok_without_login(): void
    {
        $response = ApiClient::anonymous()->get('Health');

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertSame('ok', $body['status']);
        $this->assertSame('ok', $body['database']);
        $this->assertSame('ok', $body['migrations']);
        $this->assertSame(trim((string) file_get_contents(__DIR__ . '/../../VERSION')), $body['version']);
        $this->assertSame(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, $body['php']);
        $this->assertMatchesRegularExpression('/^\d{14}$/', (string) $body['db_version']);
        $this->assertFalse($body['maintenance']);
    }

    public function test_maintenance_mode_blocks_pages_but_not_health(): void
    {
        touch(self::FLAG);

        $login = ApiClient::anonymous()->get('Login');
        $this->assertSame(503, $login->getStatusCode());
        $this->assertSame('120', $login->getHeaderLine('Retry-After'));
        $this->assertStringContainsString('Sedang dalam pemeliharaan', (string) $login->getBody());

        $post = ApiClient::anonymous()->post('Login/aksi_login', [
            'username' => 'test_admin',
            'password' => 'TestPass123!',
        ]);
        $this->assertSame(503, $post->getStatusCode(), 'Saving anything must be blocked during maintenance.');

        $health = ApiClient::anonymous()->get('Health');
        $this->assertSame(200, $health->getStatusCode());
        $this->assertTrue(json_decode((string) $health->getBody(), true)['maintenance']);
    }

    public function test_site_works_again_after_maintenance_ends(): void
    {
        touch(self::FLAG);
        unlink(self::FLAG);

        $this->assertNotSame(503, ApiClient::anonymous()->get('Login')->getStatusCode());
    }
}
