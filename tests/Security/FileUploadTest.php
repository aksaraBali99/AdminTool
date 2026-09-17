<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Regression coverage for Import::import_excel_siswa() (application/
 * controllers/Import.php:21-25), which passes an uploaded file straight to
 * PHPExcel_IOFactory::load() with no allowed_types/mime check at all —
 * contrast with Hr.php:164 and MasterData.php:310, which both restrict
 * upload types. PHPExcel is an abandoned library with a history of XXE
 * vulnerabilities in its XML-based readers.
 *
 * This test deliberately does NOT attempt to build a working XXE payload —
 * that crosses from a defensive regression test into exploit authoring.
 * It only proves the narrower, sufficient claim: the endpoint has no
 * intentional file-type allowlist, so it never returns a clean, deliberate
 * 4xx rejection for an obviously-wrong file type — whatever happens instead
 * (today: an uncaught PHPExcel exception) is accidental, not validated.
 *
 * EXPECTED TO FAIL until the endpoint validates the upload's extension/mime
 * type before handing it to PHPExcel.
 */
final class FileUploadTest extends TestCase
{
    public function test_import_endpoint_rejects_non_spreadsheet_uploads_deliberately(): void
    {
        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->postMultipart('Import/import_excel_siswa', [
            [
                'name' => 'fileExcel',
                'contents' => "This is plainly not a spreadsheet.\n",
                'filename' => 'not_a_spreadsheet.exe',
            ],
        ]);

        $this->assertContains(
            $response->getStatusCode(),
            [400, 422],
            'Expected a deliberate 4xx rejection for a disallowed file type, got HTTP ' .
            $response->getStatusCode() . '. The endpoint should validate the upload ' .
            'before handing it to PHPExcel, the same way Hr.php and MasterData.php ' .
            'already restrict allowed_types on their own upload endpoints.'
        );
    }
}
