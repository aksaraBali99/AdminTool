<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DbCont extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database(); // Memuat database
        $this->load->model('m_db');
    }
    private function dropAllTables()
    {
        // Menonaktifkan foreign key checks
        $this->db->query("SET FOREIGN_KEY_CHECKS=0");

        // Ambil daftar tabel dari database
        $tables = $this->db->list_tables();

        // Hapus semua tabel
        foreach ($tables as $table) {
            $this->db->query("DROP TABLE IF EXISTS `$table`"); // Menghapus tabel
        }

        // Mengaktifkan kembali foreign key checks
        $this->db->query("SET FOREIGN_KEY_CHECKS=1");
    }
    public function resetData()
    {
        if ($this->session->userdata('jabatan') == 'superadmin') {

            // Hapus semua data di database target
            $this->dropAllTables();

            // Path ke file SQL
            $filePath = FCPATH . '/database/apk_pilkada.sql'; // Ganti dengan nama file Anda

            if (file_exists($filePath)) {
                $sql = file_get_contents($filePath); // Membaca file SQL

                // Pisahkan perintah SQL berdasarkan delimiter
                $queries = explode(';', $sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if ($query) { // Pastikan query tidak kosong
                        // Eksekusi setiap query
                        if ($this->db->query($query) === FALSE) {
                            echo "Error importing query: " . $this->db->error();
                        }
                    }
                }
                $script = "<script>
                alert('Success');window.location.href = '" . site_url('Login/index') . "';</script>";
                echo $script;
            } else {
                echo "File not found.";
            }
        } else {
            echo 'Rejected';
        }
    }
}
