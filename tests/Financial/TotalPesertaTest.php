<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;
use Tests\Support\TestDb;

/**
 * Regression coverage for M_dashboard::get_total_peserta() (application/
 * models/M_dashboard.php:4), the "Siswa Aktif" stat on the superadmin and
 * admin dashboards. It's a cumulative count, not a simple filter: "how many
 * students were already active as of this month/year", using
 * COALESCE(tgl_konversi_siswa, input_at) against a `<= given month/year`
 * cutoff - a real boundary condition (year rollover, two different date
 * columns) rather than a straight COUNT(*) WHERE.
 *
 * This needs peserta rows shaped like Cron's active-student filter
 * (status='Registrasi Kelas' AND status_siswa='Aktif') to test the positive
 * case at all - per tests/README.md's safety rule, such rows must NEVER go
 * into the static seed_test_data.sql fixture (Cron::index() is
 * unauthenticated and has real side effects). So this test creates its own
 * rows in setUp() and unconditionally deletes them in tearDown(), the same
 * way tests/Security/AccessControlTest.php isolates its own mutations -
 * they exist only for the duration of this one test method, never
 * persisted, and Cron's own test (skipped today, see
 * tests/Security/UnauthenticatedEndpointsTest.php) never actually fires an
 * HTTP request that could see them.
 *
 * Querying "as of June 2026" (bulan=6, tahun=2026, matching the rest of
 * this fixture's fixed dates):
 *   - Row A: converted 2026-03-15 (before cutoff) -> counts.
 *   - Row B: converted 2026-09-01 (after cutoff) -> excluded.
 *   - Row C: tgl_konversi_siswa NULL, input_at 2026-01-10 (before cutoff,
 *     via the COALESCE fallback) -> counts.
 *   - Row D: status_siswa='Nonaktif', converted 2026-01-01 (before cutoff,
 *     but wrong status_siswa) -> excluded.
 * Plus the two static fixture rows (status_siswa='Aktif' but
 * status='Trial'; status_siswa='Trial') as free decoys for the
 * status='Registrasi Kelas' filter. Expected total: 2 (rows A and C).
 */
final class TotalPesertaTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedIds as $id) {
            TestDb::deletePesertaById($id);
        }
        $this->insertedIds = [];
    }

    public function test_counts_students_active_as_of_the_given_month_using_conversion_or_input_date(): void
    {
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Cutoff Row A (before, via tgl_konversi_siswa)',
            'status' => 'Registrasi Kelas',
            'status_siswa' => 'Aktif',
            'tgl_konversi_siswa' => '2026-03-15 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Cutoff Row B (after cutoff)',
            'status' => 'Registrasi Kelas',
            'status_siswa' => 'Aktif',
            'tgl_konversi_siswa' => '2026-09-01 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Cutoff Row C (before, via input_at fallback)',
            'status' => 'Registrasi Kelas',
            'status_siswa' => 'Aktif',
            'tgl_konversi_siswa' => null,
            'input_at' => '2026-01-10 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Cutoff Row D (before cutoff, but not Aktif)',
            'status' => 'Registrasi Kelas',
            'status_siswa' => 'Nonaktif',
            'tgl_konversi_siswa' => '2026-01-01 00:00:00',
        ]);

        $client = new ApiClient();
        $client->loginAs('superadmin');
        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::statValue((string) $response->getBody(), 'Siswa Aktif');
        $this->assertSame(
            '2',
            $value,
            'Expected 2 students active as of June 2026 (Row A via tgl_konversi_siswa, ' .
            'Row C via the input_at fallback). Row B is converted after the cutoff, Row D ' .
            "has the wrong status_siswa, and both static fixture rows have status != " .
            "'Registrasi Kelas'. Got: " . var_export($value, true)
        );
    }
}
