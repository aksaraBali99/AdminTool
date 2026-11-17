<?php

namespace Tests\Siswa;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Student attendance (task #12): each row records the teacher and 1 or 2
 * sessions, 'Sakit' is a valid status, and a student can't go over 8
 * 'Hadir' sessions in a calendar month (Siswa::input_absensi).
 *
 * Every test works on its own freshly inserted peserta, deleted in
 * tearDown(); absensi_siswa rows go with it (ON DELETE CASCADE). Dates are
 * pinned to March 2026 so the quota month never depends on today's date.
 */
final class AbsensiSiswaTest extends TestCase
{
    private const ID_GURU = 1; // 'Test Teacher' in seed_test_data.sql

    private ApiClient $client;
    private int $idSiswa;

    protected function setUp(): void
    {
        $this->idSiswa = TestDb::insertPeserta([
            'nama_anak' => 'Absensi Test ' . uniqid(),
            'status' => 'Registrasi Kelas',
            'status_siswa' => 'Nonaktif',
        ]);
        $this->client = new ApiClient();
        $this->client->loginAs('admin');
    }

    protected function tearDown(): void
    {
        TestDb::deletePesertaById($this->idSiswa);
    }

    public function test_migration_adds_teacher_session_and_sakit_columns(): void
    {
        $stmt = TestDb::connect()->query(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_siswa'
               AND COLUMN_NAME IN ('id_guru', 'jumlah_sesi', 'status_hadir')"
        );
        $cols = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $cols[$row['COLUMN_NAME']] = $row;
        }

        $this->assertArrayHasKey('id_guru', $cols, 'Run `php index.php migrate` against admintool_test.');
        $this->assertSame('YES', $cols['id_guru']['IS_NULLABLE']);
        $this->assertArrayHasKey('jumlah_sesi', $cols);
        $this->assertSame('NO', $cols['jumlah_sesi']['IS_NULLABLE'], 'NULL sessions would be skipped by the quota SUM.');
        $this->assertSame('1', $cols['jumlah_sesi']['COLUMN_DEFAULT']);
        $this->assertSame("enum('Hadir','Izin','Sakit','Alpha')", $cols['status_hadir']['COLUMN_TYPE']);
    }

    public function test_saves_teacher_sessions_and_sakit_and_reports_them(): void
    {
        $this->assertSuccess($this->absen('2026-03-02', 'Hadir', 2));
        $this->assertSuccess($this->absen('2026-03-03', 'Sakit', 1));

        $data = $this->getAbsensi('2026-03-01', '2026-03-31');

        $this->assertCount(2, $data['absensi']);
        $hadir = $data['absensi'][1]; // ordered newest first
        $this->assertSame('2026-03-02', $hadir['tanggal']);
        $this->assertSame('Test Teacher', $hadir['nama_guru']);
        $this->assertSame('2', (string) $hadir['jumlah_sesi']);

        // "Hadir" counts sessions, the other statuses count days.
        $this->assertSame('2', (string) $data['rekap']['total_hadir']);
        $this->assertSame('1', (string) $data['rekap']['total_sakit']);
        $this->assertSame('2', (string) $data['rekap']['total_hari']);
    }

    public function test_monthly_quota_caps_hadir_at_eight_sessions(): void
    {
        foreach (['2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'] as $tanggal) {
            $this->assertSuccess($this->absen($tanggal, 'Hadir', 2));
        }
        // 8 sessions used: one more Hadir session is refused...
        $over = $this->absen('2026-03-06', 'Hadir', 1);
        $this->assertSame('error', $over['status']);
        $this->assertStringContainsString('kuota', $over['message']);
        $this->assertSame(0, $this->countRows('2026-03-06'));

        // ...but non-Hadir statuses and the next month are not limited.
        $this->assertSuccess($this->absen('2026-03-09', 'Izin', 1));
        $this->assertSuccess($this->absen('2026-04-01', 'Hadir', 2));
    }

    public function test_reports_when_the_eighth_session_completes_the_month(): void
    {
        foreach (['2026-03-02', '2026-03-03', '2026-03-04'] as $tanggal) {
            $this->absen($tanggal, 'Hadir', 2);
        }
        $last = $this->absen('2026-03-05', 'Hadir', 2);

        $this->assertSuccess($last);
        $this->assertSame(8, $last['total_sesi']);
        $this->assertTrue($last['is_completed']);
    }

    /**
     * @dataProvider invalidInputs
     */
    public function test_rejects_invalid_input_without_saving(array $override): void
    {
        $form = $override + [
            'id_siswa' => (string) $this->idSiswa,
            'tanggal' => '2026-03-10',
            'id_guru' => (string) self::ID_GURU,
            'jumlah_sesi' => '1',
            'status_hadir' => 'Hadir',
            'keterangan' => '',
        ];
        $response = $this->client->post('Siswa/input_absensi', $form);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($body, 'Expected a JSON error, got: ' . strip_tags((string) $response->getBody()));
        $this->assertSame('error', $body['status']);
        $this->assertSame(0, $this->countRows());
    }

    public static function invalidInputs(): array
    {
        return [
            'unknown status' => [['status_hadir' => 'Libur']],
            'three sessions' => [['jumlah_sesi' => '3']],
            'no teacher' => [['id_guru' => '']],
            'impossible date' => [['tanggal' => '2026-02-31']],
            'not a date' => [['tanggal' => 'tomorrow']],
        ];
    }

    public function test_rejects_a_second_entry_on_the_same_date(): void
    {
        $this->assertSuccess($this->absen('2026-03-10', 'Hadir', 1));

        $again = $this->absen('2026-03-10', 'Izin', 1);

        $this->assertSame('error', $again['status']);
        $this->assertStringContainsString('10/03/2026', $again['message']);
        $this->assertSame(1, $this->countRows());
    }

    public function test_pdf_with_empty_from_date_covers_only_one_month(): void
    {
        $this->absen('2026-03-02', 'Hadir', 2);

        // An empty date_from used to be read as January 1970, listing every
        // month since then in the quota section (~16 pages).
        $response = $this->client->get("Siswa/download_absensi/{$this->idSiswa}", [
            'date_from' => '',
            'date_to' => '2026-03-31',
        ]);
        $pdf = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('%PDF', $pdf, 'Expected a PDF, got: ' . substr(strip_tags($pdf), 0, 300));
        $this->assertSame(1, preg_match_all('#/Type\s*/Page\b(?!s)#', $pdf), 'The report should fit on one page.');
    }

    private function absen(string $tanggal, string $status, int $sesi): array
    {
        $response = $this->client->post('Siswa/input_absensi', [
            'id_siswa' => (string) $this->idSiswa,
            'tanggal' => $tanggal,
            'id_guru' => (string) self::ID_GURU,
            'jumlah_sesi' => (string) $sesi,
            'status_hadir' => $status,
            'keterangan' => '',
        ]);
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Expected JSON, got: ' . strip_tags((string) $response->getBody()));
        return $body;
    }

    private function getAbsensi(string $from, string $to): array
    {
        $response = $this->client->post('Siswa/get_absensi', [
            'id_siswa' => (string) $this->idSiswa,
            'date_from' => $from,
            'date_to' => $to,
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    private function assertSuccess(array $body): void
    {
        $this->assertSame('success', $body['status'], $body['message'] ?? '');
    }

    private function countRows(?string $tanggal = null): int
    {
        $sql = 'SELECT COUNT(*) FROM absensi_siswa WHERE id_siswa = ?';
        $params = [$this->idSiswa];
        if ($tanggal !== null) {
            $sql .= ' AND tanggal = ?';
            $params[] = $tanggal;
        }
        $stmt = TestDb::connect()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
