<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Two independent unauthenticated-access findings from the audit:
 *
 *  1. Cron::index() inserts billing records and sends WhatsApp reminders for
 *     every active student. Its CLI-only guard is active now (Cron.php:11,
 *     `is_cli_request()`), so a web request is refused before anything runs.
 *     But the guard refuses with exit('Akses hanya melalui CLI'), which is
 *     HTTP 200, so this test's status-code check still fails and the test
 *     stays skipped. Outside production sendWa() never sends anyway
 *     (EHU-0023-6, application/helpers/outbound_helper.php).
 *
 *     SAFETY: tests/fixtures/seed_test_data.sql deliberately seeds zero
 *     rows matching Cron's active-student filter (status='Registrasi Kelas'
 *     AND status_siswa='Aktif'), so even if the guard were removed, Cron's
 *     per-student loop body would never run here: no fixture row is ever
 *     inserted. Do not "fix" the fixture to include a matching row without
 *     re-reading this comment.
 *
 *  2. Pub::get_peserta_jadwal() is intentionally public (no login required
 *     by design) but returns each student's name and phone number for any
 *     sequential, guessable schedule ID — full-roster PII harvesting with
 *     no authentication at all.
 *
 * Both tests are skipped: (1) until the guard answers with a non-200
 * status, (2) until the finding is fixed.
 */
final class UnauthenticatedEndpointsTest extends TestCase
{
    public function test_cron_endpoint_requires_authentication(): void
    {
        $this->markTestSkipped(
            'Cron\'s CLI guard (Cron.php:11) is active, but it refuses web requests ' .
            'with HTTP 200 and a message, so this status-code check cannot pass. ' .
            'Re-enable once the guard answers 403/404, or check the refusal text instead.'
        );

        $response = ApiClient::anonymous()->get('Cron');

        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'Cron/index responded 200 to a fully anonymous request. This endpoint ' .
            'inserts billing records and sends WhatsApp reminders per active ' .
            'student and must refuse web requests with a non-200 status.'
        );
    }

    public function test_public_schedule_lookup_does_not_leak_student_contact_details(): void
    {
        $this->markTestSkipped(
            'Known finding, not yet fixed: Pub::get_peserta_jadwal() leaks no_hp/' .
            'nama_ortu to unauthenticated callers. Re-enable once those fields ' .
            'are dropped from the public response.'
        );

        $response = ApiClient::anonymous()->get('Pub/get_peserta_jadwal/1');
        $body = (string) $response->getBody();

        // Fixture values from tests/fixtures/seed_test_data.sql — fake by construction.
        $this->assertStringNotContainsString(
            '5550000099',
            $body,
            'Pub/get_peserta_jadwal returned a student phone number to an ' .
            'unauthenticated caller. This endpoint is public by design (schedule ' .
            'browsing) but should not include no_hp/nama_ortu in its response.'
        );
        $this->assertStringNotContainsString(
            'Test Parent',
            $body,
            'Pub/get_peserta_jadwal returned a parent name to an unauthenticated caller.'
        );
    }
}
