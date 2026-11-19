<?php

namespace Tests\Outbound;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Sending an invoice on the test server (CI_ENV=testing) must not send a
 * WhatsApp message: sendWa() returns a "NOT SENT" answer, which is logged in
 * reminder_tagihan, and no request leaves the app.
 *
 * tests/run-tests.sh points the app's Fonnte URL (OUTBOUND_FONNTE_URL) at a
 * local stub (tests/stubs/fonnte-stub.php) that records every hit in
 * FONNTE_STUB_HITS. If the production-only gate were bypassed, the request
 * would land there and this test would fail, without anything reaching
 * Fonnte.
 *
 * Uses seeded peserta #2 (status 'Jadwal Trial', so outside Cron's filter;
 * it has a class, which kirim_invoice needs) with a far-future period, and
 * deletes every row it creates in tearDown().
 */
final class KirimInvoiceNotSentTest extends TestCase
{
    private const ID_PESERTA = 2;
    private const BULAN = 1;
    private const TAHUN = 2031;

    private int $maxLogBefore;
    private int $maxTagihanBefore;

    protected function setUp(): void
    {
        $this->assertNotFalse(getenv('OUTBOUND_FONNTE_URL'), 'Run via tests/run-tests.sh: it starts the Fonnte stub.');
        $this->assertNotFalse(getenv('FONNTE_STUB_HITS'), 'Run via tests/run-tests.sh: it starts the Fonnte stub.');

        $db = TestDb::connect();
        $this->maxLogBefore = (int) $db->query('SELECT COALESCE(MAX(id_log), 0) FROM reminder_tagihan')->fetchColumn();
        $this->maxTagihanBefore = (int) $db->query('SELECT COALESCE(MAX(id_tagihan), 0) FROM tagihan')->fetchColumn();
    }

    protected function tearDown(): void
    {
        $db = TestDb::connect();
        $db->prepare('DELETE FROM reminder_tagihan WHERE id_log > ?')->execute([$this->maxLogBefore]);
        $db->prepare('DELETE FROM tagihan_detail WHERE id_tagihan > ?')->execute([$this->maxTagihanBefore]);
        $db->prepare('DELETE FROM tagihan WHERE id_tagihan > ?')->execute([$this->maxTagihanBefore]);
    }

    private function stubHits(): int
    {
        $file = getenv('FONNTE_STUB_HITS');
        clearstatcache(true, $file);
        return is_file($file) ? count(file($file)) : 0;
    }

    public function test_the_stub_records_hits(): void
    {
        // Control: proves a request to the stub would be counted, so "zero
        // hits" below really means "no request was made".
        $before = $this->stubHits();
        $response = (new Client(['http_errors' => false, 'timeout' => 10]))
            ->post(getenv('OUTBOUND_FONNTE_URL'), ['form_params' => ['target' => '0', 'message' => 'control']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($before + 1, $this->stubHits());
    }

    public function test_kirim_invoice_logs_not_sent_and_makes_no_request(): void
    {
        $client = new ApiClient();
        $client->loginAs('admin');
        $hitsBefore = $this->stubHits();

        $response = $client->post('Peserta/kirim_invoice', [
            'id_peserta' => self::ID_PESERTA,
            'bulan' => self::BULAN,
            'tahun' => self::TAHUN,
            'metode_pembayaran' => 'Cash',
            'items' => [
                ['tipe_biaya' => 'Biaya Kelas', 'nilai_biaya' => 1000, 'keterangan' => 'outbound test'],
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('"success"', trim((string) $response->getBody()));

        $stmt = TestDb::connect()->prepare(
            'SELECT no_hp, response, keterangan FROM reminder_tagihan
             WHERE id_log > ? AND id_peserta = ? AND tahun = ? AND bulan = ?'
        );
        $stmt->execute([$this->maxLogBefore, self::ID_PESERTA, self::TAHUN, self::BULAN]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(1, $rows, 'kirim_invoice logs one WhatsApp attempt in reminder_tagihan.');
        $this->assertStringStartsWith('NOT SENT (testing)', (string) $rows[0]['keterangan']);
        $this->assertNotSame('Sukses', $rows[0]['keterangan']);
        $this->assertSame(0, $this->stubHits() - $hitsBefore, 'sendWa() made a request outside production.');
    }

    public function test_kirim_reminder_tagihan_is_not_a_url(): void
    {
        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->get('Peserta/kirim_reminder_tagihan');

        $this->assertSame(404, $response->getStatusCode(), 'kirim_reminder_tagihan() must be private, not callable by URL.');
    }
}
