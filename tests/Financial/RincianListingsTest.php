<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for the two "recent activity" tables on the
 * superadmin dashboard (application/views/dashboard/index.php):
 *
 *  - get_rincian_pendapatan($year, $limit) (application/models/
 *    M_dashboard.php:263): the most recent Paid tagihan for the year,
 *    newest tgl_bayar first.
 *  - get_rincian_pengeluaran($year, $limit) (:282): the most recent
 *    pengeluaran for the year, newest tanggal first.
 *
 * Both are year-scoped only (no month filter), so - unlike
 * tests/Financial/LabaRugiTest.php's June-specific check - the July 2026
 * tagihan/pengeluaran rows are NOT decoys here; they're legitimately
 * expected to appear alongside the June ones. What each must still exclude
 * is a different status (Pending) or a different year entirely.
 *
 * Both assertions are scoped to their own <tbody> (extracted via
 * extractTbody() below) rather than searched across the whole page body -
 * peserta #2's August tagihan (300,000) and the July pengeluaran (also
 * 300,000) render the identical "Rp 300.000" string, so a whole-page
 * strpos() would find whichever happens to come first in the page and
 * silently test the wrong table.
 */
final class RincianListingsTest extends TestCase
{
    private function extractTbody(string $body, string $id): string
    {
        $found = preg_match('/<tbody id="' . preg_quote($id, '/') . '">(.*?)<\/tbody>/s', $body, $m);
        $this->assertSame(1, $found, "Could not find <tbody id=\"$id\"> in the response body.");
        return $m[1];
    }

    public function test_rincian_pendapatan_lists_every_paid_tagihan_in_the_year_newest_first(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());
        $tbody = $this->extractTbody((string) $response->getBody(), 'tbody-pendapatan');

        $posAug = strpos($tbody, 'Rp 300.000');
        $posJul = strpos($tbody, 'Rp 1.000.000');
        $posJun = strpos($tbody, 'Rp 2.000.000');

        $this->assertNotFalse($posAug, "Peserta #2's August 2026 Paid row (300,000) should appear.");
        $this->assertNotFalse($posJul, "Peserta #1's July 2026 Paid row (1,000,000) should appear here (year-scoped, not a decoy for this query).");
        $this->assertNotFalse($posJun, "Peserta #1's June 2026 Paid row (2,000,000) should appear.");

        $this->assertTrue(
            $posAug < $posJul && $posJul < $posJun,
            'Expected newest-tgl_bayar-first ordering: August, then July, then June. ' .
            "Got positions within the table: Aug=$posAug, Jul=$posJul, Jun=$posJun."
        );

        $this->assertStringNotContainsString(
            'Rp 999.999',
            $tbody,
            "Peserta #2's Pending decoy (999,999) must be excluded (status_bayar != 'Paid')."
        );
        $this->assertStringNotContainsString(
            'Rp 111.111',
            $tbody,
            "Peserta #2's 2025 decoy (111,111) must be excluded (wrong year)."
        );
    }

    public function test_rincian_pengeluaran_lists_every_expense_in_the_year_newest_first(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Dashboard', ['bulan' => '6', 'tahun' => '2026']);
        $this->assertSame(200, $response->getStatusCode());
        $tbody = $this->extractTbody((string) $response->getBody(), 'tbody-pengeluaran');

        $posJul = strpos($tbody, 'Rp 300.000');
        $posJun = strpos($tbody, 'Rp 700.000');

        $this->assertNotFalse($posJul, 'The July 2026 expense (300,000) should appear (year-scoped, not a decoy here).');
        $this->assertNotFalse($posJun, 'The June 2026 expense (700,000) should appear.');
        $this->assertTrue(
            $posJul < $posJun,
            "Expected newest-tanggal-first ordering: July before June. Got positions within the table: Jul=$posJul, Jun=$posJun."
        );
    }
}
