<?php

namespace Tests\Health;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * The Health page and maintenance mode, which the automated deploy relies on
 * (docs/HEALTH_AND_MAINTENANCE.md):
 *  - Health answers JSON without a login, and reports the database and
 *    migrations as ok on the migrated test database.
 *  - While the maintenance file exists, every page answers 503 with the
 *    maintenance page, except Health.
 *  - The maintenance file is MAINTENANCE_FILE when set (tests/run-tests.sh
 *    sets it, as a deployed site's shared .htaccess does), not the default
 *    maintenance.on next to index.php.
 */
final class HealthAndMaintenanceTest extends TestCase
{
    private const DEFAULT_FLAG = __DIR__ . '/../../maintenance.on';

    private function flag(): string
    {
        return getenv('MAINTENANCE_FILE') ?: self::DEFAULT_FLAG;
    }

    protected function tearDown(): void
    {
        // Never leave the test app stuck in maintenance mode.
        foreach ([$this->flag(), self::DEFAULT_FLAG] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
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
        touch($this->flag());

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
        touch($this->flag());
        $this->assertSame(503, ApiClient::anonymous()->get('Login')->getStatusCode(), 'Maintenance should be on.');

        unlink($this->flag());

        $this->assertNotSame(503, ApiClient::anonymous()->get('Login')->getStatusCode(), 'Maintenance should be off again.');
        $health = json_decode((string) ApiClient::anonymous()->get('Health')->getBody(), true);
        $this->assertFalse($health['maintenance']);
    }

    public function test_maintenance_file_setting_replaces_the_default_location(): void
    {
        if (!getenv('MAINTENANCE_FILE')) {
            $this->markTestSkipped('MAINTENANCE_FILE is not set; run via tests/run-tests.sh.');
        }

        // With MAINTENANCE_FILE set, a maintenance.on next to index.php (for
        // example left in an old release folder) must not switch the site off.
        touch(self::DEFAULT_FLAG);

        $this->assertNotSame(503, ApiClient::anonymous()->get('Login')->getStatusCode());
        $health = json_decode((string) ApiClient::anonymous()->get('Health')->getBody(), true);
        $this->assertFalse($health['maintenance']);
    }
}
