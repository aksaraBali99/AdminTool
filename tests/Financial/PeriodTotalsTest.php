<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for two of M_dashboard's period-scoped SUM queries,
 * both rendered as the "Total Pemasukan" and "SPP Belum Dibayar" stats on
 * the superadmin/admin/finance dashboards:
 *
 *  - get_total_pembayaran($bulan, $tahun) (application/models/
 *    M_dashboard.php:51): SUM(tagihan.jumlah) WHERE status_bayar='Paid'
 *    for the given month/year.
 *  - get_spp_belum_dibayar($bulan, $tahun) (:194): same idea but
 *    status_bayar != 'Paid' - "how much is still owed".
 *
 * Fixture (tests/fixtures/seed_test_data.sql) for June 2026: one Paid
 * tagihan of 2,000,000 (id_peserta=1), one Pending tagihan of 500,000 (same
 * peserta, same month - the "still owed" one), plus a decoy Paid tagihan in
 * July 2026 that both queries must exclude via the month filter.
 */
final class PeriodTotalsTest extends TestCase
{
    public function test_total_pemasukan_and_spp_belum_dibayar_for_june_2026(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertStringContainsString(
            'Rp 2.000.000',
            $body,
            'Expected total_pembayaran (Paid tagihan for June 2026) to render as ' .
            '"Rp 2.000.000". The Pending tagihan and the July decoy must be excluded.'
        );

        $this->assertStringContainsString(
            'Rp 500.000',
            $body,
            'Expected spp_belum_dibayar (non-Paid tagihan for June 2026) to render as ' .
            '"Rp 500.000" - the one Pending row. The Paid row and the July decoy must ' .
            'be excluded.'
        );
    }
}
