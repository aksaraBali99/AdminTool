<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;
use Tests\Support\TestDb;

/**
 * Regression coverage for the "Murid Placement Test" stat on the admin
 * dashboard, the total_placement_test half of
 * M_dashboard::get_lead_status_counts(): rows of lead_status_history
 * with status_baru='Placement Test' whose tgl_update falls in the given month.
 */
final class TotalMuridPlacementTestTest extends TestCase
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

    public function test_counts_only_placement_test_leads_updated_in_the_given_month(): void
    {
        $ptBefore = TestDb::countLeadStatus('Placement Test', 6, 2026);
        $trialBefore = TestDb::countLeadStatus('Jadwal Trial', 6, 2026);

        $id1 = TestDb::insertPeserta(['nama_anak' => 'PT in June (first day)']);
        $this->insertedIds[] = $id1;
        TestDb::insertLeadHistory($id1, 'Placement Test', '2026-06-01 00:00:00');

        $id2 = TestDb::insertPeserta(['nama_anak' => 'PT in June (last second)']);
        $this->insertedIds[] = $id2;
        TestDb::insertLeadHistory($id2, 'Placement Test', '2026-06-30 23:59:59');

        $id3 = TestDb::insertPeserta(['nama_anak' => 'PT in July (decoy)']);
        $this->insertedIds[] = $id3;
        TestDb::insertLeadHistory($id3, 'Placement Test', '2026-07-01 00:00:00');

        $id4 = TestDb::insertPeserta(['nama_anak' => 'Trial in June (decoy)']);
        $this->insertedIds[] = $id4;
        TestDb::insertLeadHistory($id4, 'Jadwal Trial', '2026-06-15 00:00:00');

        $client = new ApiClient();
        $client->loginAs('admin');
        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());

        $html = (string) $response->getBody();

        $value = DashboardHtml::cardTitleValue($html, 'Murid Placement Test');
        $this->assertSame(
            (string) ($ptBefore + 2),
            $value,
            "Expected 2 more than the $ptBefore pre-existing status_baru='Placement Test' leads updated in June 2026. The July row is " .
            "outside the month and the 'Jadwal Trial' row is a different status. Got: " .
            var_export($value, true)
        );

        $trial = DashboardHtml::cardTitleValue($html, 'Murid Trial');
        $this->assertSame(
            (string) ($trialBefore + 1),
            $trial,
            "Expected the same query's Trial count to grow by 1 (only the 'Jadwal Trial' decoy). Got: " .
            var_export($trial, true)
        );
    }

    public function test_out_of_range_year_falls_back_to_the_current_year(): void
    {
        // The controller keeps a valid bulan and replaces only the bad tahun
        // with the server's current year, so ?bulan=n&tahun=99999 must match
        // ?bulan=n&tahun=<this year>. The row is dated mid-month so neither a
        // month boundary nor a CLI/server clock difference can move it.
        $bulan = (int) date('n');
        $tahun = (int) date('Y');

        $id = TestDb::insertPeserta(['nama_anak' => 'PT mid-month']);
        $this->insertedIds[] = $id;
        TestDb::insertLeadHistory($id, 'Placement Test', sprintf('%04d-%02d-15 12:00:00', $tahun, $bulan));

        $client = new ApiClient();
        $client->loginAs('admin');

        $expected = DashboardHtml::cardTitleValue(
            (string) $client->get('Dashboard', ['bulan' => (string) $bulan, 'tahun' => (string) $tahun])->getBody(),
            'Murid Placement Test'
        );
        $this->assertNotSame('0', $expected, 'Precondition: the mid-month row should count for this month.');

        $response = $client->get('Dashboard', ['bulan' => (string) $bulan, 'tahun' => '99999']);
        $this->assertSame(200, $response->getStatusCode(), 'An out-of-range tahun must not make the dashboard error out.');

        $invalid = DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Placement Test');
        $this->assertSame(
            $expected,
            $invalid,
            'An out-of-range tahun should fall back to the current year. Got: ' .
            var_export($invalid, true)
        );
    }

    public function test_december_of_the_last_four_digit_year_does_not_error(): void
    {
        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->get('Dashboard', ['bulan' => '12', 'tahun' => '9999']);
        $this->assertSame(200, $response->getStatusCode(), '?bulan=12&tahun=9999 must not make the dashboard error out.');
        $this->assertNotNull(
            DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Placement Test'),
            'The Murid Placement Test card should still render.'
        );
    }
}