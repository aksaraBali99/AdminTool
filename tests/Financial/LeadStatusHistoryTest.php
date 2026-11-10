<?php

namespace Tests\Financial;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * The dashboard's Murid Trial / Placement Test cards count lead_status_history,
 * so Masterdata > Data Peserta (Peserta::add_peserta, add and edit) must leave
 * a history row whenever it sets or changes a lead's status.
 */
final class LeadStatusHistoryTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        foreach ($this->insertedIds as $id) {
            TestDb::connect()->prepare('DELETE FROM tagihan WHERE id_peserta = ?')->execute([$id]);
            TestDb::deleteLeadHistoryByPesertaId($id);
            TestDb::deletePesertaById($id);
        }
        $this->insertedIds = [];
    }

    public function test_data_peserta_add_and_edit_log_status_changes(): void
    {
        $nama = 'Peserta Form Lead ' . uniqid();
        $form = [
            'nama_ortu' => 'Form Parent',
            'no_hp' => '5558888800',
            'nama_anak' => $nama,
            'status' => 'Jadwal Trial',
            'jk' => 'L',
            'id_jenis_kelas' => '1',
            'jenis_siswa' => 'regular',
            // The browser form always posts these, empty or not.
            'alamat_ortu' => '',
            'email' => '',
            'src' => '',
            'catatan' => '',
            'nama_sekolah' => '',
        ];

        $client = new ApiClient();
        $client->loginAs('admin');

        $response = $client->post('Peserta/add_peserta', $form + ['tipe_form' => 'add']);
        $this->assertSame(200, $response->getStatusCode(), strip_tags((string) $response->getBody()));

        $stmt = TestDb::connect()->prepare('SELECT id_peserta FROM peserta WHERE nama_anak = ?');
        $stmt->execute([$nama]);
        $id = (int) $stmt->fetchColumn();
        $this->assertGreaterThan(0, $id, 'add_peserta should have created the peserta row.');
        $this->insertedIds[] = $id;

        $this->assertSame([[null, 'Jadwal Trial']], $this->history($id), 'Adding a peserta should log its initial status.');

        // Saving with the same status logs nothing; changing it logs the move.
        $client->post('Peserta/add_peserta', $form + ['tipe_form' => 'edit', 'id_peserta' => (string) $id]);
        $client->post('Peserta/add_peserta', ['status' => 'Placement Test'] + $form + ['tipe_form' => 'edit', 'id_peserta' => (string) $id]);

        $this->assertSame(
            [[null, 'Jadwal Trial'], ['Jadwal Trial', 'Placement Test']],
            $this->history($id),
            'Editing a peserta should log a status change, and only when the status actually changes.'
        );
    }

    private function history(int $id): array
    {
        $stmt = TestDb::connect()->prepare('SELECT status_lama, status_baru FROM lead_status_history WHERE id_peserta = ? ORDER BY id');
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_NUM);
    }
}
