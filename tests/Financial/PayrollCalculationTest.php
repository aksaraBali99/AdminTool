<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Regression coverage for the teacher payroll calculation in
 * M_hr::generate_payroll_guru() (application/models/M_hr.php:137) and the
 * PPh21 tax-bracket lookup it calls, M_hr::calculate_pph21() (:356). This
 * is real-money logic — silent regressions here mean teachers get paid the
 * wrong amount — so it's tested end-to-end against seeded, known inputs
 * rather than just "did it run without erroring".
 *
 * Fixture (tests/fixtures/seed_test_data.sql): teacher id_guru=1 has 10
 * "Hadir" attendance rows in June 2026, 5 hours/day of 'anak' class, one
 * arrival each. Tariffs: tarif_per_jam_anak=100000, biaya_transport=50000.
 * Two pph21_komponen brackets exist (5% under 60,000,000 annualized, 15%
 * at/over) — the fixture is deliberately sized so the annualized subtotal
 * lands in the SECOND bracket, proving the "highest applicable bracket"
 * query actually discriminates rather than trivially returning the only
 * row that exists.
 *
 * Expected: honor_anak = 50h * 100,000 = 5,000,000; transport = 10 *
 * 50,000 = 500,000; subtotal = 5,500,000; annualized = 66,000,000 -> 15%
 * bracket; pph21_nominal = 825,000; gaji_bersih = 4,675,000.
 */
final class PayrollCalculationTest extends TestCase
{
    public function test_generate_payroll_computes_correct_honor_transport_and_pph21(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->post('Hr/generate_payroll_guru', [
            'id_guru' => '1',
            'bulan' => '6',
            'tahun' => '2026',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('success', $body['status'] ?? null, 'generate_payroll_guru did not report success: ' . $response->getBody());

        $row = TestDb::fetchPayrollGuru(1, 6, 2026);
        $this->assertNotNull($row, 'No payroll_guru row was created for id_guru=1, bulan=6, tahun=2026.');

        $this->assertEqualsWithDelta(50.0, (float) $row['total_jam_ajar_anak'], 0.01, 'total_jam_ajar_anak');
        $this->assertEqualsWithDelta(5000000.0, (float) $row['total_honor_anak'], 0.01, 'total_honor_anak');
        $this->assertEqualsWithDelta(0.0, (float) $row['total_honor_dewasa'], 0.01, 'total_honor_dewasa (no dewasa attendance seeded)');
        $this->assertEqualsWithDelta(5000000.0, (float) $row['total_honor'], 0.01, 'total_honor');
        $this->assertSame(10, (int) $row['total_hari_hadir'], 'total_hari_hadir');
        $this->assertSame(10, (int) $row['total_kedatangan'], 'total_kedatangan');
        $this->assertEqualsWithDelta(500000.0, (float) $row['total_transport'], 0.01, 'total_transport');
        $this->assertEqualsWithDelta(5500000.0, (float) $row['subtotal'], 0.01, 'subtotal');

        $this->assertEqualsWithDelta(
            15.0,
            (float) $row['pph21_persen'],
            0.01,
            'Expected the 15% bracket (annualized subtotal 66,000,000 >= its 60,000,000 floor), ' .
            'got ' . $row['pph21_persen'] . '%. If this is 5%, calculate_pph21() picked the wrong ' .
            'bracket — check its ORDER BY/LIMIT logic in M_hr.php.'
        );
        $this->assertEqualsWithDelta(825000.0, (float) $row['pph21_nominal'], 0.01, 'pph21_nominal');
        $this->assertEqualsWithDelta(4675000.0, (float) $row['total_gaji_bersih'], 0.01, 'total_gaji_bersih');
        $this->assertSame('draft', $row['status']);
    }
}
