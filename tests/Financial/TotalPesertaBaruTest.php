<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;
use Tests\Support\TestDb;

/**
 * Regression coverage for M_dashboard::get_total_peserta_baru()
 * (application/models/M_dashboard.php:27), the "Siswa Baru" stat on all
 * three dashboards. It now takes a bulan/tahun parameter, wired from the
 * dashboard's own period selector via Dashboard.php's `?bulan=&tahun=` -
 * that wiring (task #16) is exactly what test_counts_students_converted_
 * in_a_given_past_month covers below, using the fixture's fixed June 2026
 * dates like the rest of this suite.
 *
 * The query itself counts status='Registrasi Kelas' peserta whose
 * MONTH/YEAR(COALESCE(tgl_konversi_siswa, input_at)) matches the given
 * period: tgl_konversi_siswa when a lead was converted, falling back to
 * input_at for students enrolled directly (never went through a CRM lead,
 * so tgl_konversi_siswa stays NULL). test_counts_students_converted_in_a_
 * given_past_month exercises both paths plus the status filter.
 *
 * test_counts_students_converted_in_the_current_real_world_month separately
 * covers the *default*-argument path (no ?bulan=&tahun= in the request,
 * e.g. Dashboard.php falling back to date('m')/date('Y')) - that can't use
 * the fixture's fixed June 2026 dates since it depends on whatever "now"
 * actually is when the test runs. So instead of assuming a clean slate, it
 * reads the real baseline count for the current month first
 * (TestDb::countPesertaBaru(), which mirrors the SUT's own query exactly,
 * including the status filter and the COALESCE fallback), inserts one more
 * known row, and asserts the rendered total went up by exactly one -
 * correct regardless of what today's date is or what else might already be
 * seeded for it.
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
            'status' => 'Registrasi Kelas',
            'tgl_konversi_siswa' => date('Y-m-d H:i:s'),
        ]);

        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Converted last month (decoy)',
            'status' => 'Registrasi Kelas',
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

    public function test_counts_students_converted_in_a_given_past_month(): void
    {
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Row A (converted in-month, via tgl_konversi_siswa)',
            'status' => 'Registrasi Kelas',
            'tgl_konversi_siswa' => '2026-06-15 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Row B (input_at fallback, in-month)',
            'status' => 'Registrasi Kelas',
            'tgl_konversi_siswa' => null,
            'input_at' => '2026-06-20 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Row C (converted in a different month - excluded)',
            'status' => 'Registrasi Kelas',
            'tgl_konversi_siswa' => '2026-05-15 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Row D (Jadwal Trial, not Registrasi)',
            'status' => 'Jadwal Trial',
            'tgl_konversi_siswa' => '2026-06-10 00:00:00',
        ]);

        $client = new ApiClient();
        $client->loginAs('superadmin');
        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::statValue((string) $response->getBody(), 'Siswa Baru');
        $this->assertSame(
            '2',
            $value,
            'Expected 2 students new in June 2026 (Row A via tgl_konversi_siswa, Row B via ' .
            'the input_at fallback). Row C was converted in a different month, and Row D is ' .
            "in-month but not status='Registrasi Kelas'. Got: " . var_export($value, true)
        );
    }
}
