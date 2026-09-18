<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for the two pie-chart data sources on the superadmin
 * dashboard, both embedded directly as JSON in an inline <script> tag
 * (application/views/dashboard/index.php) rather than fetched via AJAX:
 *
 *  - get_pendapatan_per_kategori($year) (application/models/
 *    M_dashboard.php:298): SUM(tagihan.jumlah) WHERE status_bayar='Paid'
 *    AND tahun=$year, grouped by the student's class category. This is an
 *    INNER JOIN through peserta to data_jenis_kelas - a peserta row with no
 *    id_jenis_kelas set (like fixture peserta #1) is silently excluded
 *    entirely, not just uncategorized. Only fixture peserta #2 has
 *    id_jenis_kelas set, so only its tagihan can appear here at all; see
 *    the comment on peserta #2 in seed_test_data.sql.
 *  - get_pengeluaran_per_kategori($year) (:317): SUM(pengeluaran.jumlah)
 *    WHERE YEAR(tanggal)=$year, grouped by pengeluaran's own `kategori`
 *    column directly (no join, no status filter - pengeluaran has no
 *    concept of "paid").
 *
 * Both use $year alone, no month filter, so both June AND July 2026
 * pengeluaran rows land in the same 2026 total.
 */
final class KategoriChartsTest extends TestCase
{
    public function test_pendapatan_per_kategori_only_includes_a_classed_peserta(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertStringContainsString(
            '[{"kategori":"Test Class","total":"300000.00"}]',
            $body,
            'Expected pendapatan_kategori for 2026 to be exactly one category, ' .
            "'Test Class' totalling 300,000 (peserta #2's one Paid 2026 row). Peserta " .
            "#1's 2,000,000 must be absent (its id_jenis_kelas is NULL, excluded by the " .
            "INNER JOIN), and peserta #2's Pending and wrong-year decoy rows must also " .
            'be excluded.'
        );
    }

    public function test_pengeluaran_per_kategori_sums_across_the_whole_year_regardless_of_month(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertStringContainsString(
            '[{"kategori":"Test Expense","total":"1000000.00"}]',
            $body,
            "Expected pengeluaran_kategori for 2026 to total 1,000,000 - both the June " .
            "(700,000) and July (300,000) fixture rows, since this query has no month " .
            'filter, only a year filter.'
        );
    }
}
