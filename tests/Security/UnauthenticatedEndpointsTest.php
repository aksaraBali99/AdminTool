<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;

/**
 * Two independent unauthenticated-access findings from the audit:
 *
 *  1. Cron::index() has no is_login check and its CLI-only guard is
 *     commented out, so any anonymous request triggers real side effects
 *     (billing-record inserts + outbound WhatsApp sends via a third-party
 *     API) for every active student.
 *
 *     SAFETY: tests/fixtures/seed_test_data.sql deliberately seeds zero
 *     rows matching Cron's active-student filter (status='Registrasi Kelas'
 *     AND status_siswa='Aktif'), so even though this test's request DOES
 *     execute the real, currently-unauthenticated controller, its
 *     per-student loop body never runs — no fixture row is ever inserted
 *     and no outbound network call is ever made. Do not "fix" the fixture
 *     to include a matching row without re-reading this comment.
 *
 *  2. Pub::get_peserta_jadwal() is intentionally public (no login required
 *     by design) but returns each student's name and phone number for any
 *     sequential, guessable schedule ID — full-roster PII harvesting with
 *     no authentication at all.
 *
 * EXPECTED TO FAIL until both are fixed.
 */
final class UnauthenticatedEndpointsTest extends TestCase
{
    public function test_cron_endpoint_requires_authentication(): void
    {
        $response = ApiClient::anonymous()->get('Cron');

        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'Cron/index responded 200 to a fully anonymous request. This endpoint ' .
            'inserts billing records and sends real WhatsApp messages per active ' .
            'student and must require authentication (its own commented-out ' .
            'is_cli_request() guard, or an is_login check, or both).'
        );
    }

    public function test_public_schedule_lookup_does_not_leak_student_contact_details(): void
    {
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
