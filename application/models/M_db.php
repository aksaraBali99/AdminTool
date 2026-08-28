<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_db extends CI_Model
{

    public function truncateAllTables()
    {
        // Ambil daftar tabel dari database target
        $tables = $this->db->list_tables();

        // Hapus semua data dalam tabel target
        foreach ($tables as $table) {
            $this->db->empty_table($table); // Hapus semua data
        }
    }

    public function insertData($table, $data)
    {
        // Insert data ke tabel yang sesuai
        $this->db->insert($table, (array)$data); // Mengonversi objek ke array
    }
}
