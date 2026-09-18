<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;

/**
 * Regression coverage for M_dashboard::get_total_murid_trial() (application/
 * models/M_dashboard.php:44), the "Murid Trial" stat on the admin
 * dashboard: COUNT(*) FROM peserta WHERE status_siswa='Trial'. No period
 * filter - this is an all-time count.
 *
 * Note `status` and `status_siswa` are different columns: fixture peserta
 * #1 has `status`='Trial' (a CRM lead-stage value) but `status_siswa`=
 * 'Aktif', so it must NOT count here - it's the decoy proving this reads
 * status_siswa, not status. Fixture peserta #2 has status_siswa='Trial' and
 * is the one row that should count. Expected: 1.
 */
final class TotalMuridTrialTest extends TestCase
{
    public function test_counts_only_peserta_rows_with_status_siswa_trial(): void
    {
        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->get('Dashboard');
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Trial');
        $this->assertSame(
            '1',
            $value,
            "Expected 1 (only peserta #2, status_siswa='Trial'). Peserta #1 has " .
            "status='Trial' but status_siswa='Aktif' and must not be counted - if this " .
            'is higher, the query may be reading the wrong column. Got: ' .
            var_export($value, true)
        );
    }
}
