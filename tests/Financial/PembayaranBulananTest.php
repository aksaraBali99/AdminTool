<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Regression coverage for M_dashboard::get_pembayaran_bulanan()
 * (application/models/M_dashboard.php:148), which drives the finance
 * dashboard's "Perbandingan Pembayaran" card: this real-world month's Paid
 * total, last real-world month's Paid total, and the percentage change
 * between them - with a special-cased +100% when last month was zero
 * (avoiding a division by zero), rather than the -100%/undefined a naive
 * formula would produce.
 *
 * Like get_total_peserta_baru(), this is hardcoded to PHP's current
 * date - no bulan/tahun parameter - so it can't be tested against the
 * fixture's fixed June 2026 dates. This reads the real baseline for
 * "this month"/"last month" first (TestDb::sumPaidTagihan(), mirroring the
 * SUT's own query), inserts one more known Paid row into each period, and
 * recomputes the expected totals and percentage from (baseline + inserted)
 * using the exact same formula the SUT uses - correct regardless of what
 * today's date is or what else is already seeded for it.
 */
final class PembayaranBulananTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedIds as $id) {
            TestDb::deleteTagihanById($id);
        }
        $this->insertedIds = [];
    }

    public function test_percentage_change_between_this_month_and_last_month(): void
    {
        $bulanIni = (int) date('m');
        $tahunIni = (int) date('Y');
        $bulanLalu = (int) date('m', strtotime('-1 month'));
        $tahunLalu = (int) date('Y', strtotime('-1 month'));

        $baselineIni = TestDb::sumPaidTagihan($bulanIni, $tahunIni);
        $baselineLalu = TestDb::sumPaidTagihan($bulanLalu, $tahunLalu);

        $this->insertedIds[] = TestDb::insertTagihan([
            'bulan' => $bulanIni,
            'tahun' => $tahunIni,
            'jumlah' => 400000,
            'status_bayar' => 'Paid',
        ]);
        $this->insertedIds[] = TestDb::insertTagihan([
            'bulan' => $bulanLalu,
            'tahun' => $tahunLalu,
            'jumlah' => 200000,
            'status_bayar' => 'Paid',
        ]);

        $expectedIni = $baselineIni + 400000;
        $expectedLalu = $baselineLalu + 200000;
        $expectedPersentase = $expectedLalu > 0
            ? round((($expectedIni - $expectedLalu) / $expectedLalu) * 100, 2)
            : 100.0;

        $client = new ApiClient();
        $client->loginAs('finance');
        $response = $client->get('Dashboard');
        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertStringContainsString(
            'Rp ' . number_format($expectedIni, 0, ',', '.'),
            $body,
            "Expected this month's total to render as the baseline plus the 400,000 " .
            'just inserted.'
        );
        $this->assertStringContainsString(
            'Rp ' . number_format($expectedLalu, 0, ',', '.'),
            $body,
            "Expected last month's total to render as the baseline plus the 200,000 " .
            'just inserted.'
        );

        // finance.php renders the percentage as a bare number (no decimals
        // trimmed) inside a success/danger badge depending on sign - match
        // whichever sign this particular baseline+delta combination lands
        // on, same way the view itself branches.
        $expectedBadge = $expectedPersentase >= 0 ? 'badge-success' : 'badge-danger';
        // PHP's default (string) cast of a float already renders 50.0 as
        // "50" and 33.33 as "33.33", matching how finance.php echoes
        // $persentase_pembayaran directly - no extra formatting needed.
        $expectedDisplayed = (string) ($expectedPersentase >= 0 ? $expectedPersentase : abs($expectedPersentase));

        $this->assertMatchesRegularExpression(
            '/' . preg_quote($expectedBadge, '/') . '"><i class="fas fa-arrow-(up|down)"><\/i> ' .
            preg_quote($expectedDisplayed, '/') . '%/',
            $body,
            "Expected the percentage badge to show $expectedDisplayed% ($expectedBadge), " .
            "computed as (($expectedIni - $expectedLalu) / $expectedLalu) * 100. Full body " .
            'available in the failure diff.'
        );
    }
}
