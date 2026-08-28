<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_penjadwalan extends CI_Model
{
    // =============================================
    // BRANCH OPERATIONS
    // =============================================
    
    public function get_all_branch()
    {
        return $this->db->where('status', 'aktif')
                        ->get('data_branch')
                        ->result();
    }
    
    public function get_branch_by_id($id)
    {
        return $this->db->where('id_branch', $id)->get('data_branch')->row();
    }

    // =============================================
    // JADWAL KELAS OPERATIONS
    // =============================================
    
    public function get_all_jadwal_kelas($filters = array())
    {
        $this->db->select('jk.*, p.nama as nama_pengajar, dk.nama_kelas, b.nama_branch')
                 ->from('jadwal_kelas jk')
                 ->join('pengajar p', 'p.id_pengajar = jk.id_guru')
                 ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas')
                 ->join('data_branch b', 'b.id_branch = jk.id_branch','left');
        
        if (!empty($filters['id_branch'])) {
            $this->db->where('jk.id_branch', $filters['id_branch']);
        }
        if (!empty($filters['id_guru'])) {
            $this->db->where('jk.id_guru', $filters['id_guru']);
        }
        if (isset($filters['is_aktif'])) {
            $this->db->where('jk.is_aktif', $filters['is_aktif']);
        }
        
        return $this->db->order_by('jk.hari', 'ASC')
                        ->order_by('jk.jam_mulai', 'ASC')
                        ->get()
                        ->result();
    }
    
    public function get_jadwal_kelas_by_id($id)
    {
        return $this->db->select('jk.*, p.nama as nama_pengajar, dk.nama_kelas, b.nama_branch')
                        ->from('jadwal_kelas jk')
                        ->join('pengajar p', 'p.id_pengajar = jk.id_guru')
                        ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas')
                        ->join('data_branch b', 'b.id_branch = jk.id_branch','left')
                        ->where('jk.id', $id)
                        ->get()
                        ->row();
    }
    
    public function insert_jadwal_kelas($data)
    {
        return $this->db->insert('jadwal_kelas', $data);
    }
    
    public function update_jadwal_kelas($id, $data)
    {
        return $this->db->where('id', $id)->update('jadwal_kelas', $data);
    }
    
    public function delete_jadwal_kelas($id)
    {
        return $this->db->where('id', $id)->update('jadwal_kelas', ['is_aktif' => 0]);
    }
    
    // Check bentrok guru
    public function check_bentrok_guru($id_guru, $hari, $jam_mulai, $jam_selesai, $exclude_id = null)
    {
        $this->db->where('id_guru', $id_guru);
        $this->db->where('hari', $hari);
        $this->db->where('is_aktif', 1);
        $this->db->where("(jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai')");
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->get('jadwal_kelas')->num_rows() > 0;
    }
    
    // Check bentrok ruangan
    public function check_bentrok_ruangan($ruangan, $id_branch, $hari, $jam_mulai, $jam_selesai, $exclude_id = null)
    {
        if (empty($ruangan)) return false;
        
        $this->db->where('ruangan', $ruangan);
        $this->db->where('id_branch', $id_branch);
        $this->db->where('hari', $hari);
        $this->db->where('is_aktif', 1);
        $this->db->where("(jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai')");
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->get('jadwal_kelas')->num_rows() > 0;
    }
    
    // Get jadwal guru hari ini
    public function get_jadwal_guru_hari_ini($id_guru)
    {
        $hari = date('N'); // 1=Monday, 7=Sunday
        return $this->db->select('jk.*, dk.nama_kelas, b.nama_branch')
                        ->from('jadwal_kelas jk')
                        ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas')
                        ->join('data_branch b', 'b.id_branch = jk.id_branch','left')
                        ->where('jk.id_guru', $id_guru)
                        ->where('jk.hari', $hari)
                        ->where('jk.is_aktif', 1)
                        ->order_by('jk.jam_mulai', 'ASC')
                        ->get()
                        ->result();
    }

    // =============================================
    // ABSENSI GURU OPERATIONS
    // =============================================
    
    public function get_absensi_guru($filters = array())
    {
        $this->db->select('ag.*, p.nama as nama_pengajar, b.nama_branch, dk.nama_kelas')
                 ->from('absensi_guru ag')
                 ->join('pengajar p', 'p.id_pengajar = ag.id_guru')
                 ->join('data_branch b', 'b.id_branch = ag.id_branch','left')
                 ->join('jadwal_kelas jk', 'jk.id = ag.id_jadwal_kelas', 'left')
                 ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas', 'left')
                 ->where('ag.is_deleted', 0);
        
        if (!empty($filters['tanggal'])) {
            $this->db->where('ag.tanggal', $filters['tanggal']);
        }
        if (!empty($filters['tanggal_dari'])) {
            $this->db->where('ag.tanggal >=', $filters['tanggal_dari']);
        }
        if (!empty($filters['tanggal_sampai'])) {
            $this->db->where('ag.tanggal <=', $filters['tanggal_sampai']);
        }
        if (!empty($filters['id_guru'])) {
            $this->db->where('ag.id_guru', $filters['id_guru']);
        }
        if (!empty($filters['id_branch'])) {
            $this->db->where('ag.id_branch', $filters['id_branch']);
        }
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $this->db->where('MONTH(ag.tanggal)', $filters['bulan']);
            $this->db->where('YEAR(ag.tanggal)', $filters['tahun']);
        }
        
        return $this->db->order_by('ag.tanggal', 'DESC')
                        ->order_by('ag.jam_mulai', 'ASC')
                        ->get()
                        ->result();
    }
    
    public function get_absensi_by_id($id)
    {
        return $this->db->where('id', $id)->where('is_deleted', 0)->get('absensi_guru')->row();
    }
    
    public function insert_absensi($data)
    {
        return $this->db->insert('absensi_guru', $data);
    }
    
    public function update_absensi($id, $data)
    {
        return $this->db->where('id', $id)->update('absensi_guru', $data);
    }
    
    public function soft_delete_absensi($id)
    {
        return $this->db->where('id', $id)->update('absensi_guru', ['is_deleted' => 1]);
    }

    // =============================================
    // PAYROLL OPERATIONS
    // =============================================
    
    public function get_payroll_data($bulan, $tahun, $id_guru = null, $id_branch = null)
    {
        $this->db->select("
            ag.id_guru,
            p.nama as nama_pengajar,
            p.biaya_transport as tarif_transport,
            SUM(CASE WHEN ag.tipe_kelas = 'anak' AND ag.status_hadir = 'Hadir' THEN ag.total_jam ELSE 0 END) as total_jam_anak,
            SUM(CASE WHEN ag.tipe_kelas = 'dewasa' AND ag.status_hadir = 'Hadir' THEN ag.total_jam ELSE 0 END) as total_jam_dewasa,
            SUM(CASE WHEN ag.tipe_kelas = 'anak' AND ag.status_hadir = 'Hadir' THEN ag.total_jam * ag.tarif_per_jam ELSE 0 END) as honor_anak,
            SUM(CASE WHEN ag.tipe_kelas = 'dewasa' AND ag.status_hadir = 'Hadir' THEN ag.total_jam * ag.tarif_per_jam ELSE 0 END) as honor_dewasa,
            COUNT(DISTINCT CASE WHEN ag.status_hadir = 'Hadir' THEN ag.tanggal END) as total_hari_hadir,
            SUM(CASE WHEN ag.status_hadir = 'Hadir' THEN COALESCE(ag.jumlah_kedatangan, 1) ELSE 0 END) as total_kedatangan,
            COUNT(DISTINCT CASE WHEN ag.status_hadir = 'Izin' THEN ag.tanggal END) as total_hari_izin,
            COUNT(DISTINCT CASE WHEN ag.status_hadir = 'Alpha' THEN ag.tanggal END) as total_hari_alpha
        ");
        $this->db->from('absensi_guru ag');
        $this->db->join('pengajar p', 'p.id_pengajar = ag.id_guru');
        $this->db->where('ag.is_deleted', 0);
        $this->db->where('MONTH(ag.tanggal)', $bulan);
        $this->db->where('YEAR(ag.tanggal)', $tahun);
        
        if ($id_guru) {
            $this->db->where('ag.id_guru', $id_guru);
        }
        if ($id_branch) {
            $this->db->where('ag.id_branch', $id_branch);
        }
        
        $this->db->group_by('ag.id_guru');
        return $this->db->get()->result();
    }
    
    public function get_payroll_detail($id_guru, $bulan, $tahun)
    {
        return $this->db->select('ag.*, dk.nama_kelas')
                        ->from('absensi_guru ag')
                        ->join('jadwal_kelas jk', 'jk.id = ag.id_jadwal_kelas', 'left')
                        ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas', 'left')
                        ->where('ag.id_guru', $id_guru)
                        ->where('ag.is_deleted', 0)
                        ->where('MONTH(ag.tanggal)', $bulan)
                        ->where('YEAR(ag.tanggal)', $tahun)
                        ->order_by('ag.tanggal', 'ASC')
                        ->get()
                        ->result();
    }

    // =============================================
    // PENEMPATAN GURU OPERATIONS
    // =============================================
    
    public function get_penempatan($filters = array())
    {
        $this->db->select('pg.*, p.nama as nama_pengajar, dk.nama_kelas, b.nama_branch')
                 ->from('penempatan_guru pg')
                 ->join('pengajar p', 'p.id_pengajar = pg.id_guru')
                 ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = pg.id_kelas')
                 ->join('data_branch b', 'b.id_branch = pg.id_branch','left');
        
        // if (!empty($filters['status'])) {
        //     $this->db->where('pg.status', $filters['status']);
        // }
        if (!empty($filters['id_guru'])) {
            $this->db->where('pg.id_guru', $filters['id_guru']);
        }
        
        return $this->db->order_by('pg.tanggal_mulai', 'DESC')->get()->result();
    }
    
    public function get_penempatan_by_id($id)
    {
        return $this->db->where('id', $id)->get('penempatan_guru')->row();
    }
    
    public function insert_penempatan($data)
    {
        return $this->db->insert('penempatan_guru', $data);
    }
    
    public function update_penempatan($id, $data)
    {
        return $this->db->where('id', $id)->update('penempatan_guru', $data);
    }

    public function delete_penempatan($id)
    {
        return $this->db->where('id', $id)->delete('penempatan_guru');
    }

    // =============================================
    // RESCHEDULE OPERATIONS
    // =============================================
    
    public function get_reschedule($filters = array())
    {
        $this->db->select('r.*, jk.id_kelas, dk.nama_kelas, p.nama as nama_pengajar, u.nama as approved_by_name')
                 ->from('reschedule_kelas r')
                 ->join('jadwal_kelas jk', 'jk.id = r.id_jadwal_kelas')
                 ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas')
                 ->join('pengajar p', 'p.id_pengajar = jk.id_guru')
                 ->join('user u', 'u.id_user = r.approved_by', 'left');
        
        if (!empty($filters['status'])) {
            $this->db->where('r.status', $filters['status']);
        }
        
        return $this->db->order_by('r.created_at', 'DESC')->get()->result();
    }
    
    public function get_reschedule_by_id($id)
    {
        return $this->db->where('id', $id)->get('reschedule_kelas')->row();
    }
    
    public function insert_reschedule($data)
    {
        return $this->db->insert('reschedule_kelas', $data);
    }
    
    public function approve_reschedule($id, $status, $approved_by)
    {
        return $this->db->where('id', $id)->update('reschedule_kelas', [
            'status' => $status,
            'approved_by' => $approved_by,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function delete_reschedule($id)
    {
        return $this->db->where('id', $id)->delete('reschedule_kelas');
    }

    // =============================================
    // LIBUR NASIONAL OPERATIONS
    // =============================================
    
    public function get_all_libur($tahun = null)
    {
        if ($tahun) {
            $this->db->where('YEAR(tanggal)', $tahun);
        }
        return $this->db->order_by('tanggal', 'ASC')->get('libur_nasional')->result();
    }
    
    public function get_libur_by_id($id)
    {
        return $this->db->where('id', $id)->get('libur_nasional')->row();
    }
    
    public function insert_libur($data)
    {
        return $this->db->insert('libur_nasional', $data);
    }
    
    public function update_libur($id, $data)
    {
        return $this->db->where('id', $id)->update('libur_nasional', $data);
    }
    
    public function delete_libur($id)
    {
        return $this->db->where('id', $id)->delete('libur_nasional');
    }
    
    public function is_libur($tanggal)
    {
        return $this->db->where('tanggal', $tanggal)->get('libur_nasional')->row();
    }

    // =============================================
    // PERTEMUAN / 8x RULE OPERATIONS
    // =============================================
    
    public function count_pertemuan_bulan($id_jadwal_kelas, $bulan, $tahun)
    {
        return $this->db->where('id_jadwal_kelas', $id_jadwal_kelas)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->where('status', 'terlaksana')
                        ->get('pertemuan_kelas')
                        ->num_rows();
    }
    
    public function insert_pertemuan($data)
    {
        return $this->db->insert('pertemuan_kelas', $data);
    }
    
    public function get_pertemuan_kelas($id_jadwal_kelas, $bulan = null, $tahun = null)
    {
        $this->db->where('id_jadwal_kelas', $id_jadwal_kelas);
        if ($bulan) $this->db->where('bulan', $bulan);
        if ($tahun) $this->db->where('tahun', $tahun);
        return $this->db->order_by('tanggal', 'DESC')->get('pertemuan_kelas')->result();
    }

    // =============================================
    // CALENDAR / EVENTS
    // =============================================
    
    public function get_events($start_date, $end_date, $filters = array())
    {
        // Get jadwal kelas as recurring events
        $this->db->select('jk.*, p.nama as nama_pengajar, dk.nama_kelas, b.nama_branch')
                 ->from('jadwal_kelas jk')
                 ->join('pengajar p', 'p.id_pengajar = jk.id_guru')
                 ->join('data_jenis_kelas dk', 'dk.id_jenis_kelas = jk.id_kelas')
                 ->join('data_branch b', 'b.id_branch = jk.id_branch','left')
                 ->where('jk.is_aktif', 1);
        
        if (!empty($filters['id_branch'])) {
            $this->db->where('jk.id_branch', $filters['id_branch']);
        }
        if (!empty($filters['id_guru'])) {
            $this->db->where('jk.id_guru', $filters['id_guru']);
        }
        
        return $this->db->get()->result();
    }
    
    public function get_libur_between($start_date, $end_date)
    {
        return $this->db->where('tanggal >=', $start_date)
                        ->where('tanggal <=', $end_date)
                        ->get('libur_nasional')
                        ->result();
    }
}
