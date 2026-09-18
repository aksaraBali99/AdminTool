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

    /**
     * Inserts a `peserta` row with sensible defaults for every NOT NULL
     * column, overridable via $overrides. Returns the new id_peserta.
     *
     * For tests that need a row shaped like Cron's active-student filter
     * (status='Registrasi Kelas' AND status_siswa='Aktif') this is
     * deliberately the only way to get one - never add such a row to the
     * static seed_test_data.sql fixture (see the safety note there and in
     * tests/README.md). Callers doing that MUST delete it in tearDown()
     * via deletePesertaById(), unconditionally, so it never outlives the
     * single test method that created it.
     */
    public static function insertPeserta(array $overrides = []): int
    {
        $defaults = [
            'nama_ortu' => 'Dynamic Test Parent',
            'no_hp' => '5559999900',
            'nama_anak' => 'Dynamic Test Child',
            'status' => 'Trial',
            'catatan' => '',
            'jk' => 'L',
            'id_jenis_kelas' => null,
            'is_aktif' => 1,
            'tgl_non_aktif' => '2000-01-01',
            'status_siswa' => 'Aktif',
            'input_at' => date('Y-m-d H:i:s'),
            'tgl_konversi_siswa' => null,
        ];
        $data = array_merge($defaults, $overrides);
        $cols = array_keys($data);
        $sql = 'INSERT INTO peserta (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        self::connect()->prepare($sql)->execute(array_values($data));
        return (int) self::connect()->lastInsertId();
    }

    public static function deletePesertaById(int $idPeserta): void
    {
        self::connect()->prepare('DELETE FROM peserta WHERE id_peserta = ?')->execute([$idPeserta]);
    }

    /** Inserts a `tagihan` row with sensible defaults, overridable via $overrides. Returns the new id_tagihan. */
    public static function insertTagihan(array $overrides = []): int
    {
        $defaults = [
            'id_peserta' => 1,
            'bulan' => (int) date('m'),
            'tahun' => (int) date('Y'),
            'jumlah' => 100000,
            'status_bayar' => 'Paid',
            'tipe' => 'Biaya Kelas',
            'tgl_bayar' => date('Y-m-d H:i:s'),
        ];
        $data = array_merge($defaults, $overrides);
        $cols = array_keys($data);
        $sql = 'INSERT INTO tagihan (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        self::connect()->prepare($sql)->execute(array_values($data));
        return (int) self::connect()->lastInsertId();
    }

    public static function deleteTagihanById(int $idTagihan): void
    {
        self::connect()->prepare('DELETE FROM tagihan WHERE id_tagihan = ?')->execute([$idTagihan]);
    }

    /**
     * Mirrors M_dashboard::get_pembayaran_bulanan()'s own SUM exactly, so
     * tests can read the pre-existing baseline for "this month"/"last
     * month" before inserting their own row, instead of assuming a clean
     * slate that a fixed-date fixture (everything else is pinned to June
     * 2026) can't guarantee for whatever month a test happens to run in.
     */
    public static function sumPaidTagihan(int $bulan, int $tahun): float
    {
        $stmt = self::connect()->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM tagihan WHERE status_bayar = 'Paid' AND bulan = ? AND tahun = ?");
        $stmt->execute([$bulan, $tahun]);
        return (float) $stmt->fetchColumn();
    }

    /** Mirrors M_dashboard::get_total_peserta_baru()'s own COUNT exactly - see sumPaidTagihan()'s docblock for why. */
    public static function countPesertaBaru(int $bulan, int $tahun): int
    {
        $stmt = self::connect()->prepare('SELECT COUNT(*) FROM peserta WHERE MONTH(tgl_konversi_siswa) = ? AND YEAR(tgl_konversi_siswa) = ?');
        $stmt->execute([$bulan, $tahun]);
        return (int) $stmt->fetchColumn();
    }
}
