<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Student attendance revamp (task #12): record the teacher and the number of
 * sessions (1 or 2) for each attendance row, and add 'Sakit' as a status.
 *
 * jumlah_sesi is NOT NULL DEFAULT 1 so existing rows count as one session
 * each, and the monthly quota (SUM(jumlah_sesi)) never skips a NULL.
 */
class Migration_Add_teacher_and_session_to_absensi_siswa extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `absensi_siswa`
                ADD COLUMN `id_guru` int DEFAULT NULL AFTER `tanggal`,
                ADD COLUMN `jumlah_sesi` int NOT NULL DEFAULT 1 AFTER `id_guru`,
                MODIFY `status_hadir` enum('Hadir','Izin','Sakit','Alpha') NOT NULL"
        );
    }

    public function down()
    {
        // Intentionally empty: removing 'Sakit' from the enum would fail
        // once any row uses it, and dropping the columns loses data.
    }
}
