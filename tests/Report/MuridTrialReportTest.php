<?php

namespace Tests\Report;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for Report::get_data_murid_trial(), the Ajax source of
 * the "Laporan Murid Trial" DataTable. It used to GROUP BY p.id_peserta while
 * selecting the non-aggregated h.tgl_update, which MySQL 5.7+/8 rejects under
 * its default ONLY_FULL_GROUP_BY sql_mode (error 1055). CI3 then returned its
 * HTML "Database Error" page instead of JSON and DataTables showed
 * "Ajax error". MySQL rejects such a query at prepare time, so this fails
 * against the broken query even with no lead_status_history rows seeded.
 */
final class MuridTrialReportTest extends TestCase
{
    public function test_endpoint_returns_datatables_json(): void
    {
        $client = new ApiClient();
        $client->loginAs('superadmin');

        $response = $client->get('Report/get_data_murid_trial');
        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $json = json_decode($body, true);
        $this->assertIsArray(
            $json,
            'Expected a JSON body for DataTables, got: ' . substr(strip_tags($body), 0, 300)
        );
        $this->assertArrayHasKey('data', $json);
        $this->assertIsArray($json['data']);
    }
}
