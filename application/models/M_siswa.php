<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_siswa extends CI_Model
{
    // =============================================
    // MASTER LEVEL OPERATIONS
    // =============================================
    
    public function get_all_level()
    {
        return $this->db->order_by('urutan_level', 'ASC')
                        ->get('mst_level_siswa')
                        ->result();
    }
    
    public function get_all_level_aktif()
    {
        return $this->db->where('status', 'aktif')
                        ->order_by('urutan_level', 'ASC')
                        ->get('mst_level_siswa')
                        ->result();
    }
    
    public function get_level_by_id($id_level)
    {
        return $this->db->where('id_level', $id_level)
                        ->get('mst_level_siswa')
                        ->row();
    }
    
    public function insert_level($data)
    {
        return $this->db->insert('mst_level_siswa', $data);
    }
    
    public function update_level($id_level, $data)
    {
        return $this->db->where('id_level', $id_level)
                        ->update('mst_level_siswa', $data);
    }
    
    public function delete_level($id_level)
    {
        return $this->db->where('id_level', $id_level)
                        ->delete('mst_level_siswa');
    }
    
    // =============================================
    // RIWAYAT LEVEL SISWA OPERATIONS
    // =============================================
    
    public function get_level_aktif_siswa($id_siswa)
    {
        return $this->db->select('r.*, l.nama_level, l.deskripsi')
                        ->from('riwayat_level_siswa r')
                        ->join('mst_level_siswa l', 'l.id_level = r.id_level')
                        ->where('r.id_siswa', $id_siswa)
                        ->where('r.is_aktif', 1)
                        ->get()
                        ->row();
    }
    
    public function get_riwayat_level($id_siswa)
    {
        return $this->db->select('r.*, l.nama_level, l.deskripsi')
                        ->from('riwayat_level_siswa r')
                        ->join('mst_level_siswa l', 'l.id_level = r.id_level')
                        ->where('r.id_siswa', $id_siswa)
                        ->order_by('r.tanggal_kenaikan_level', 'DESC')
                        ->get()
                        ->result();
    }
    
    public function update_level_siswa($id_siswa, $id_level_baru, $catatan = null)
    {
        $this->db->trans_start();
        
        // 1. Set level lama is_aktif = 0, set tanggal_selesai
        $this->db->where('id_siswa', $id_siswa)
                 ->where('is_aktif', 1)
                 ->update('riwayat_level_siswa', [
                     'is_aktif' => 0,
                     'tanggal_selesai' => date('Y-m-d')
                 ]);
        
        // 2. Insert level baru dengan is_aktif = 1
        $this->db->insert('riwayat_level_siswa', [
            'id_siswa' => $id_siswa,
            'id_level' => $id_level_baru,
            'tanggal_kenaikan_level' => date('Y-m-d'),
            'is_aktif' => 1,
            'catatan' => $catatan
        ]);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    public function set_initial_level($id_siswa, $id_level, $catatan = null)
    {
        return $this->db->insert('riwayat_level_siswa', [
            'id_siswa' => $id_siswa,
            'id_level' => $id_level,
            'tanggal_kenaikan_level' => date('Y-m-d'),
            'is_aktif' => 1,
            'catatan' => $catatan
        ]);
    }
    
    // =============================================
    // UJIAN SISWA OPERATIONS
    // =============================================
    
    public function get_riwayat_ujian($id_siswa)
    {
        return $this->db->where('id_siswa', $id_siswa)
                        ->order_by('tanggal_ujian', 'DESC')
                        ->get('ujian_siswa')
                        ->result();
    }
    
    public function get_ujian_by_id($id)
    {
        return $this->db->where('id', $id)
                        ->get('ujian_siswa')
                        ->row();
    }
    
    public function insert_ujian($data)
    {
        return $this->db->insert('ujian_siswa', $data);
    }
    
    public function update_ujian($id, $data)
    {
        return $this->db->where('id', $id)
                        ->update('ujian_siswa', $data);
    }
    
    public function delete_ujian($id)
    {
        return $this->db->where('id', $id)
                        ->delete('ujian_siswa');
    }
    
    // =============================================
    // ABSENSI SISWA OPERATIONS
    // =============================================
    
    public function get_absensi($id_siswa, $date_from = null, $date_to = null)
    {
        $this->db->where('id_siswa', $id_siswa);
        
        if ($date_from) {
            $this->db->where('tanggal >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('tanggal <=', $date_to);
        }
        
        return $this->db->order_by('tanggal', 'DESC')
                        ->get('absensi_siswa')
                        ->result();
    }
    
    public function get_absensi_by_id($id)
    {
        return $this->db->where('id', $id)
                        ->get('absensi_siswa')
                        ->row();
    }
    
    public function insert_absensi($data)
    {
        return $this->db->insert('absensi_siswa', $data);
    }
    
    public function update_absensi($id, $data)
    {
        return $this->db->where('id', $id)
                        ->update('absensi_siswa', $data);
    }
    
    public function check_absensi_exists($id_siswa, $tanggal)
    {
        return $this->db->where('id_siswa', $id_siswa)
                        ->where('tanggal', $tanggal)
                        ->get('absensi_siswa')
                        ->num_rows() > 0;
    }
    
    public function get_rekap_absensi($id_siswa, $date_from, $date_to)
    {
        $this->db->select("
            COUNT(CASE WHEN status_hadir = 'Hadir' THEN 1 END) as total_hadir,
            COUNT(CASE WHEN status_hadir = 'Izin' THEN 1 END) as total_izin,
            COUNT(CASE WHEN status_hadir = 'Alpha' THEN 1 END) as total_alpha,
            COUNT(*) as total_hari
        ");
        $this->db->where('id_siswa', $id_siswa);
        
        if ($date_from) {
            $this->db->where('tanggal >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('tanggal <=', $date_to);
        }
        
        return $this->db->get('absensi_siswa')->row();
    }
    
    // =============================================
    // SERTIFIKAT SISWA OPERATIONS
    // =============================================
    
    public function get_sertifikat($id_siswa)
    {
        return $this->db->select('s.*, l.nama_level')
                        ->from('sertifikat_siswa s')
                        ->join('mst_level_siswa l', 'l.id_level = s.id_level')
                        ->where('s.id_siswa', $id_siswa)
                        ->order_by('s.tanggal_terbit', 'DESC')
                        ->get()
                        ->result();
    }
    
    public function get_sertifikat_by_id($id)
    {
        return $this->db->select('s.*, l.nama_level, p.nama_anak, p.nama_ortu, pengajar.nama')
                        ->from('sertifikat_siswa s')
                        ->join('mst_level_siswa l', 'l.id_level = s.id_level')
                        ->join('pengajar pengajar', 'pengajar.id_pengajar = s.id_guru')
                        ->join('peserta p', 'p.id_peserta = s.id_siswa')
                        ->where('s.id', $id)
                        ->get()
                        ->row();
    }
    
    public function generate_nomor_sertifikat()
    {
        $tahun = date('Y');
        $bulan = date('m');
        
        // Get last number this month
        $last = $this->db->select('nomor_sertifikat')
                         ->like('nomor_sertifikat', "CERT/{$tahun}{$bulan}/", 'after')
                         ->order_by('id', 'DESC')
                         ->limit(1)
                         ->get('sertifikat_siswa')
                         ->row();
        
        if ($last) {
            $parts = explode('/', $last->nomor_sertifikat);
            $num = intval(end($parts)) + 1;
        } else {
            $num = 1;
        }
        
        return sprintf("CERT/%s%s/%04d", $tahun, $bulan, $num);
    }
    
    public function insert_sertifikat($data)
    {
        return $this->db->insert('sertifikat_siswa', $data);
    }
    
    // =============================================
    // SISWA DETAIL
    // =============================================
    
    public function get_siswa_detail($id_siswa)
    {
        return $this->db->select('p.*, jk.nama_kelas, jk.biaya')
                        ->from('peserta p')
                        ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                        ->where('p.id_peserta', $id_siswa)
                        ->get()
                        ->row();
    }
    
    public function update_status_siswa($id_siswa, $data)
    {
        return $this->db->where('id_peserta', $id_siswa)
                        ->update('peserta', $data);
    }

    public function delete_sertifikat($id)
    {
        return $this->db->where('id', $id)->delete('sertifikat_siswa');
    }

    public function delete_absensi($id)
    {
        return $this->db->where('id', $id)->delete('absensi_siswa');
    }

    public function delete_riwayat_level($id, $id_siswa)
    {
        $this->db->trans_start();
        
        // Find if this is the active level
        $level = $this->db->where('id', $id)->get('riwayat_level_siswa')->row();
        
        if ($level && $level->is_aktif == 1) {
            // Delete current
            $this->db->where('id', $id)->delete('riwayat_level_siswa');
            
            // Activate previous level
            $prev = $this->db->where('id_siswa', $id_siswa)
                             ->order_by('id', 'DESC')
                             ->limit(1)
                             ->get('riwayat_level_siswa')
                             ->row();
            
            if ($prev) {
                $this->db->where('id', $prev->id)->update('riwayat_level_siswa', [
                    'is_aktif' => 1,
                    'tanggal_selesai' => null
                ]);
            }
        } else {
            $this->db->where('id', $id)->delete('riwayat_level_siswa');
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
