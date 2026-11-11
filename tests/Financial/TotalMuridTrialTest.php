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
        // Pinned to the 15th of a fixed month and requested explicitly, so the
        // result doesn't depend on when (or in which timezone) the test runs.
        $bulan = 6;
        $tahun = 2026;
        $before = TestDb::countLeadStatus('Jadwal Trial', $bulan, $tahun);

        $id1 = TestDb::insertPeserta(['nama_anak' => 'Decoy Trial']);
        $this->insertedIds[] = $id1;
        TestDb::insertLeadHistory($id1, 'Trial', '2026-06-15 12:00:00');

        $id2 = TestDb::insertPeserta(['nama_anak' => 'Target Jadwal Trial']);
        $this->insertedIds[] = $id2;
        TestDb::insertLeadHistory($id2, 'Jadwal Trial', '2026-06-15 12:00:00');

        // Logged twice in the month: still one student.
        TestDb::insertLeadHistory($id2, 'Jadwal Trial', '2026-06-20 12:00:00');

        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->get('Dashboard', ['bulan' => (string) $bulan, 'tahun' => (string) $tahun]);
        $this->assertSame(200, $response->getStatusCode());

        $value = DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Trial');

        $this->assertSame(
            (string) ($before + 1),
            $value,
            "Expected one more than the $before pre-existing 'Jadwal Trial' leads: the target counts once " .
            "despite two history rows, and the decoy has status_baru='Trial'. Got: " .
            var_export($value, true)
        );
    }
}