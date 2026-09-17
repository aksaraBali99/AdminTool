<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for M_dashboard::get_laba_rugi() (application/
 * models/M_dashboard.php:224), the profit/loss figure shown on the
 * superadmin financial dashboard: SUM(tagihan.jumlah WHERE status_bayar=
 * 'Paid') for the given month/year, minus SUM(pengeluaran.jumlah) for the
 * same month/year.
 *
 * Fixture (tests/fixtures/seed_test_data.sql) for June 2026: one Paid
 * tagihan of 2,000,000, plus decoys the query must exclude (a Pending
 * tagihan, and both a tagihan and a pengeluaran dated July 2026); one
 * pengeluaran of 700,000. Expected laba_rugi = 2,000,000 - 700,000 =
 * 1,300,000, rendered by dashboard/index.php as "Rp 1.300.000" (Indonesian
 * thousands separator).
 *
 * There's no JSON endpoint for this figure — Dashboard::index() only
 * renders it into the superadmin HTML dashboard — so this test drives the
 * real page via its bulan/tahun query params and checks the rendered
 * value, rather than scraping fragile markup.
 */
final class LabaRugiTest extends TestCase
{
    public function test_laba_rugi_excludes_unpaid_and_other_month_amounts(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();

        $this->assertStringContainsString(
            'Rp 1.300.000',
            $body,
            'Expected laba_rugi for June 2026 to render as "Rp 1.300.000" ' .
            '(2,000,000 paid - 700,000 expenses). If a decoy row (a Pending ' .
            'tagihan, or a July-dated row) leaked in, this total would be off.'
        );
    }
}
