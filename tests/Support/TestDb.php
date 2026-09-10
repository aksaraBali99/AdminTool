<?php

namespace Tests\Support;

use PDO;

/**
 * Direct PDO access to the admintool_test database for setup/assertions
 * that go beyond what's observable over HTTP (e.g. "did a new superadmin
 * row actually get created"). bootstrap.php already refuses to run unless
 * TEST_DB_NAME=admintool_test, so this connects to that same fixed DB.
 */
class TestDb
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
            $user = getenv('TEST_DB_USER') ?: 'root';
            $pass = getenv('TEST_DB_PASS') ?: '';
            self::$pdo = new PDO(
                "mysql:host=$host;dbname=admintool_test;charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }

    public static function countUsersWithRole(string $jabatan): int
    {
        $stmt = self::connect()->prepare('SELECT COUNT(*) FROM user WHERE jabatan = ?');
        $stmt->execute([$jabatan]);
        return (int) $stmt->fetchColumn();
    }

    public static function passwordHashFor(string $username): ?string
    {
        $stmt = self::connect()->prepare('SELECT password FROM user WHERE username = ?');
        $stmt->execute([$username]);
        $val = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    /** Deletes any rows this test run may have added to `user`, beyond the 3 seeded accounts. */
    public static function pruneUsersCreatedByTests(): void
    {
        self::connect()->exec("DELETE FROM user WHERE username NOT IN ('test_superadmin', 'test_admin', 'test_finance')");
    }

    public static function fetchPayrollGuru(int $idGuru, int $bulan, int $tahun): ?array
    {
        $stmt = self::connect()->prepare(
            'SELECT * FROM payroll_guru WHERE id_guru = ? AND bulan = ? AND tahun = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$idGuru, $bulan, $tahun]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Restores all three seeded accounts' password and jabatan to their
     * known fixture values (see seed_test_data.sql). Access-control tests
     * deliberately attempt to corrupt these via the app's own endpoints —
     * call this from tearDown(), unconditionally, so a test that throws
     * mid-assertion (e.g. an assertTrue() failure, which is the whole
     * point of these tests when the vulnerability is still present) can't
     * skip cleanup and poison every later test in the same run.
     */
    public static function resetSeededAccountCredentials(): void
    {
        // Same hash seed_test_data.sql uses for TestPass123! on all three.
        $hash = '$2y$10$HVXT1Uy83kGgAkGeyzvYleKHgJnKbD//2YA5lVUurjJs2GoE4T43a';
        $stmt = self::connect()->prepare('UPDATE user SET password = ?, jabatan = ? WHERE username = ?');
        $stmt->execute([$hash, 'superadmin', 'test_superadmin']);
        $stmt->execute([$hash, 'admin', 'test_admin']);
        $stmt->execute([$hash, 'finance', 'test_finance']);
    }
}
