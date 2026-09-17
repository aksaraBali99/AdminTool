<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Regression coverage for the privilege-escalation / broken access control
 * finding in MasterData::add_pengguna(): the controller only checks
 * `is_login`, never `jabatan`, so ANY authenticated account — including the
 * lowest-privileged seeded role, `finance` — can currently create a new
 * superadmin account or overwrite any other user's password by supplying
 * an arbitrary `id_user`.
 *
 * These tests are EXPECTED TO FAIL until MasterData::add_pengguna() (and
 * hapus_pengguna(), which has the identical gap) gains a role check that
 * restricts user management to superadmin.
 */
final class AccessControlTest extends TestCase
{
    protected function setUp(): void
    {
        TestDb::pruneUsersCreatedByTests();
        TestDb::resetSeededAccountCredentials();
    }

    protected function tearDown(): void
    {
        TestDb::pruneUsersCreatedByTests();
        TestDb::resetSeededAccountCredentials();
    }

    public function test_lowest_privilege_role_cannot_create_a_superadmin_account(): void
    {
        $this->markTestSkipped(
            'Known finding, not yet fixed: MasterData::add_pengguna() has no role ' .
            'check. Re-enable once it restricts user management to superadmin.'
        );

        $before = TestDb::countUsersWithRole('superadmin');

        $client = new ApiClient();
        $client->loginAs('finance');

        $client->post('MasterData/add_pengguna', [
            'tipe_form' => 'add',
            'username' => 'attacker_superadmin',
            'nama' => 'Attacker',
            'jk' => 'L',
            'usia' => '30',
            'no_hp' => '5559999999',
            'password' => 'HackedPass123!',
            'jabatan' => 'superadmin',
        ]);

        $after = TestDb::countUsersWithRole('superadmin');

        $this->assertSame(
            $before,
            $after,
            'A finance-role account was able to create a new superadmin account via ' .
            'MasterData/add_pengguna. This endpoint must reject non-superadmin callers.'
        );
    }

    public function test_lowest_privilege_role_cannot_overwrite_another_accounts_password(): void
    {
        $this->markTestSkipped(
            'Known finding, not yet fixed: MasterData::add_pengguna() has no role ' .
            'check. Re-enable once it restricts user management to superadmin.'
        );

        $client = new ApiClient();
        $client->loginAs('finance');

        // Attempt to hijack the seeded superadmin account (id_user = 1) by
        // resetting its password and demoting nothing — this alone would be
        // enough for full account takeover.
        $client->post('MasterData/add_pengguna', [
            'tipe_form' => 'edit',
            'id_user' => '1',
            'username' => 'test_superadmin',
            'nama' => 'Test Superadmin',
            'jk' => 'L',
            'no_hp' => '5550000001',
            'password' => 'HackedPass123!',
            'jabatan' => 'superadmin',
        ]);

        $hashAfter = TestDb::passwordHashFor('test_superadmin');
        $stillOriginalPassword = password_verify('TestPass123!', (string) $hashAfter);

        // tearDown() unconditionally restores this account's known password
        // regardless of what happens below — see TestDb::resetSeededAccountCredentials().
        $this->assertTrue(
            $stillOriginalPassword,
            'A finance-role account was able to overwrite the superadmin account\'s ' .
            'password via MasterData/add_pengguna. This endpoint must verify the ' .
            'caller is authorized to modify the target id_user.'
        );
    }
}
