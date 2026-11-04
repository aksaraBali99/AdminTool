<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;
use Tests\Support\TestDb;

/**
 * Regression coverage for the "Murid Trial" stat on the admin dashboard,
 * the total_trial half of M_dashboard::get_lead_status_counts(): rows of
 * lead_status_history with status_baru='Jadwal Trial' whose tgl_update falls in the
 * given month (defaulting to the current real-world month/year when no
 * bulan/tahun is passed, as here).
 */
final class TotalMuridTrialTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedIds as $id) {
            TestDb::deleteLeadHistoryByPesertaId($id);
            TestDb::deletePesertaById($id);
        }
        $this->insertedIds = [];
    }

    public function test_counts_only_peserta_rows_with_status_jadwal_trial_this_month(): void
    {
        $id1 = TestDb::insertPeserta(['nama_anak' => 'Decoy Trial']);
        $this->insertedIds[] = $id1;
        TestDb::insertLeadHistory($id1, 'Trial', date('Y-m-d H:i:s'));

        $id2 = TestDb::insertPeserta(['nama_anak' => 'Target Jadwal Trial']);
        $this->insertedIds[] = $id2;
        TestDb::insertLeadHistory($id2, 'Jadwal Trial', date('Y-m-d H:i:s'));

        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->get('Dashboard');
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Trial');
        
        $this->assertSame(
            '1',
            $value,
            "Expected 1 dynamic target row with status_baru='Jadwal Trial'. The decoy has " .
            "status_baru='Trial' and must not be counted. Got: " .
            var_export($value, true)
        );
    }
}