<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;

/**
 * Regression coverage for M_dashboard::get_total_murid_trial() (application/
 * models/M_dashboard.php:55), the "Murid Trial" stat on the admin
 * dashboard: COUNT(*) FROM peserta WHERE status='Jadwal Trial' AND
 * MONTH/YEAR(input_at) match the given period (defaulting to the current
 * real-world month/year when no bulan/tahun is passed, as here).
 *
 * Note `status` and `status_siswa` are different columns: fixture peserta
 * #1 has `status`='Trial' (a CRM lead-stage value, not 'Jadwal Trial'), so
 * it must NOT count here - it's the decoy proving this reads `status`, and
 * specifically the 'Jadwal Trial' value, not any other stage. Fixture
 * peserta #2 has `status`='Jadwal Trial' and is the one row that should
 * count - its `input_at` is left at the column default (CURRENT_TIMESTAMP),
 * so it naturally falls in "this month" since the fixture is reseeded
 * immediately before this test runs. Expected: 1.
 */
final class TotalMuridTrialTest extends TestCase
{
    public function test_counts_only_peserta_rows_with_status_jadwal_trial_this_month(): void
    {
        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->get('Dashboard');
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Trial');
        $this->assertSame(
            '1',
            $value,
            "Expected 1 (only peserta #2, status='Jadwal Trial'). Peserta #1 has " .
            "status='Trial' and must not be counted - if this is higher, the query may " .
            'be reading the wrong status value or ignoring the month filter. Got: ' .
            var_export($value, true)
        );
    }
}