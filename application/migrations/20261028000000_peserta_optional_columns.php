<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Citarum PROD has these peserta columns as NOT NULL while the baseline (and
 * DEV, Pemogan, CI) allow NULL. Align them with the baseline definitions so
 * the same code works on every site. On databases that already match, the
 * MODIFY leaves the column as it is.
 */
class Migration_Peserta_optional_columns extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            'ALTER TABLE `peserta`
                MODIFY `nama_ortu` varchar(100) DEFAULT NULL,
                MODIFY `alamat_ortu` varchar(50) DEFAULT NULL,
                MODIFY `alamat_anak` varchar(50) DEFAULT NULL,
                MODIFY `email` varchar(30) DEFAULT NULL,
                MODIFY `src` varchar(50) DEFAULT NULL,
                MODIFY `id_jenis_kelas` int DEFAULT NULL'
        );
    }

    public function down()
    {
        // Intentionally empty: making the columns NOT NULL again would fail
        // as soon as any row holds a NULL.
    }
}
