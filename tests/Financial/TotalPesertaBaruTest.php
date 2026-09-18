<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;
use Tests\Support\TestDb;

/**
 * Regression coverage for M_dashboard::get_total_peserta_baru()
 * (application/models/M_dashboard.php:27), the "Siswa Baru" stat on all
 * three dashboards. Unlike get_total_peserta(), this one does NOT take a
 * bulan/tahun parameter - it's hardcoded to PHP's own current month/year,
 * ignoring the dashboard's own period selector entirely (so "Siswa Baru"
 * always means "this real-world month", even when you've filtered the rest
 * of the page to a different one).
 *
 * That hardcoding means this can't be tested against the fixture's fixed
 * June 2026 dates like the rest of this suite - it depends on whatever
 * "now" actually is when the test runs. So instead of assuming a clean
 * slate, this reads the real baseline count for the current month first
 * (TestDb::countPesertaBaru(), which mirrors the SUT's own query exactly),
 * inserts one more known row, and asserts the rendered total went up by
 * exactly one - correct regardless of what today's date is or what else
 * might already be seeded for it.
 */
final class TotalPesertaBaruTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedIds as $id) {
            TestDb::deletePesertaById($id);
        }
        $this->insertedIds = [];
    }

    public function test_counts_students_converted_in_the_current_real_world_month(): void
    {
        $bulanIni = (int) date('m');
        $tahunIni = (int) date('Y');
        $baseline = TestDb::countPesertaBaru($bulanIni, $tahunIni);

        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Converted just now',
            'tgl_konversi_siswa' => date('Y-m-d H:i:s'),
        ]);
        // Decoy: converted last month, must not be counted as "this month".
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Converted last month (decoy)',
            'tgl_konversi_siswa' => date('Y-m-d H:i:s', strtotime('-1 month')),
        ]);

        $client = new ApiClient();
        $client->loginAs('superadmin');
        $response = $client->get('Dashboard');
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::statValue((string) $response->getBody(), 'Siswa Baru');
        $this->assertSame(
            (string) ($baseline + 1),
            $value,
            "Expected the baseline count ($baseline) plus exactly the one row converted " .
            "just now - the decoy converted last month must not be included. Got: " .
            var_export($value, true)
        );
    }
}
