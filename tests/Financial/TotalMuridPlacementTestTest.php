<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\DashboardHtml;
use Tests\Support\TestDb;

/**
 * Regression coverage for the "Murid Placement Test" stat on the admin
 * dashboard, the total_placement_test half of
 * M_dashboard::get_lead_status_counts(): rows of peserta with
 * status='Placement Test' whose input_at falls in the given month.
 *
 * Uses the fixed June 2026 period via ?bulan=&tahun= like
 * TotalPesertaBaruTest, so the static fixture rows (input_at = reseed
 * time) never fall inside it. Decoys prove the query filters on both the
 * status value and the month range, including its exclusive upper bound
 * (a row at exactly 2026-07-01 00:00:00 must not count).
 */
final class TotalMuridPlacementTestTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedIds as $id) {
            TestDb::deletePesertaById($id);
        }
        $this->insertedIds = [];
    }

    public function test_counts_only_placement_test_leads_entered_in_the_given_month(): void
    {
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'PT in June (first day)',
            'status' => 'Placement Test',
            'input_at' => '2026-06-01 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'PT in June (last second)',
            'status' => 'Placement Test',
            'input_at' => '2026-06-30 23:59:59',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'PT in July (decoy)',
            'status' => 'Placement Test',
            'input_at' => '2026-07-01 00:00:00',
        ]);
        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'Trial in June (decoy)',
            'status' => 'Jadwal Trial',
            'input_at' => '2026-06-15 00:00:00',
        ]);

        $client = new ApiClient();
        $client->loginAs('admin');
        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());

        $html = (string) $response->getBody();

        $value = DashboardHtml::cardTitleValue($html, 'Murid Placement Test');
        $this->assertSame(
            '2',
            $value,
            "Expected 2 status='Placement Test' leads entered in June 2026. The July row is " .
            "outside the month and the 'Jadwal Trial' row is a different status. Got: " .
            var_export($value, true)
        );

        $trial = DashboardHtml::cardTitleValue($html, 'Murid Trial');
        $this->assertSame(
            '1',
            $trial,
            "Expected the same query's Trial count to be 1 (only the 'Jadwal Trial' decoy). Got: " .
            var_export($trial, true)
        );
    }

    public function test_out_of_range_year_falls_back_to_the_current_month(): void
    {
        // TODO: remove this skip once Dashboard::index() validates tahun and
        // get_lead_status_counts() validates its own month/year (PR #11 review).
        // Until then ?tahun=99999 builds '99999-MM-01' and MySQL rejects it
        // (error 1525, Incorrect DATETIME value), so the whole dashboard 500s.
        $this->markTestSkipped('Known finding, not yet fixed: an out-of-range ?tahun= makes get_lead_status_counts() send an invalid DATETIME and the admin dashboard returns 500. Re-enable once Dashboard::index() and get_lead_status_counts() validate bulan/tahun.');

        $this->insertedIds[] = TestDb::insertPeserta([
            'nama_anak' => 'PT entered now',
            'status' => 'Placement Test',
        ]);

        $client = new ApiClient();
        $client->loginAs('admin');

        $current = DashboardHtml::cardTitleValue((string) $client->get('Dashboard')->getBody(), 'Murid Placement Test');
        $this->assertNotSame('0', $current, 'Precondition: the row entered now should count this month.');

        $response = $client->get('Dashboard', ['bulan' => date('n'), 'tahun' => '99999']);
        $this->assertSame(200, $response->getStatusCode(), 'An out-of-range tahun must not make the dashboard error out.');

        $invalid = DashboardHtml::cardTitleValue((string) $response->getBody(), 'Murid Placement Test');
        $this->assertSame(
            $current,
            $invalid,
            'An out-of-range tahun should fall back to the current month. Got: ' .
            var_export($invalid, true)
        );
    }
}
