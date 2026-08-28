<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Public Controller
 * Controller untuk akses publik tanpa login
 */
class Pub extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->apk = $this->m_masterdata->get_konfig(0);
        // NO LOGIN CHECK - Public access
    }

    /**
     * Laporan Kelas & Jadwal - Public Access
     */
    public function jadwal_kelas()
    {
        $data['title'] = 'Jadwal Kelas';
        $data['apk'] = $this->apk;
        $this->load->view('public/jadwal_kelas', $data);
    }

    /**
     * Get Jadwal Data for DataTable
     */
    public function get_jadwal_data()
    {
        $hari_arr = array('', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
        
        // Query menggunakan struktur baru: peserta_jadwal.id_jadwal_kelas
        $list = $this->db->select('jk.id, jk.hari, jk.jam_mulai, jk.jam_selesai, jk.ruangan, jk.tipe_kelas, jk.jenis_jadwal,
                                   djk.nama_kelas, pg.nama as nama_guru,
                                   (SELECT COUNT(DISTINCT pj.id_peserta) 
                                    FROM peserta_jadwal pj 
                                    JOIN peserta p ON p.id_peserta = pj.id_peserta 
                                    WHERE pj.id_jadwal_kelas = jk.id 
                                    AND p.status = "Registrasi Kelas" 
                                    AND p.status_siswa = "Aktif") as total_siswa,
                                   (SELECT COUNT(DISTINCT pj.id_peserta) 
                                    FROM peserta_jadwal pj 
                                    JOIN peserta p ON p.id_peserta = pj.id_peserta 
                                    WHERE pj.id_jadwal_kelas = jk.id 
                                    AND p.status != "Registrasi Kelas") as total_lead')
                         ->from('jadwal_kelas jk')
                         ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = jk.id_kelas')
                         ->join('pengajar pg', 'pg.id_pengajar = jk.id_guru')
                         ->where('jk.is_aktif', 1)
                         ->order_by('jk.hari', 'ASC')
                         ->order_by('jk.jam_mulai', 'ASC')
                         ->get()
                         ->result();
        
        $data = array();
        
        foreach ($list as $row) {
            $hari_nama = isset($hari_arr[$row->hari]) ? $hari_arr[$row->hari] : '-';
            
            // Tipe kelas badge
            $tipe_badge = $row->tipe_kelas == 'dewasa' 
                ? '<span class="badge badge-info">Dewasa</span>' 
                : '<span class="badge badge-primary">Anak</span>';
            
            // Jenis jadwal badge
            $jenis_badge = '';
            if ($row->jenis_jadwal == 'Trial Class') {
                $jenis_badge = '<span class="badge badge-warning">Trial</span>';
            } else if ($row->jenis_jadwal == 'Placement Test') {
                $jenis_badge = '<span class="badge badge-secondary">Placement</span>';
            } else {
                $jenis_badge = '<span class="badge badge-success">Regular</span>';
            }
            
            $data[] = array(
                '<span class="badge badge-dark">' . $hari_nama . '</span>',
                substr($row->jam_mulai, 0, 5) . ' - ' . substr($row->jam_selesai, 0, 5),
                $row->nama_kelas,
                $row->nama_guru,
                $row->ruangan ?: '-',
                $tipe_badge . ' ' . $jenis_badge,
                '<span class="badge badge-success">' . $row->total_siswa . ' siswa</span> <span class="badge badge-warning">' . $row->total_lead . ' lead</span>',
                '<button class="btn btn-sm btn-info btn-detail" data-id="' . $row->id . '" data-nama="' . $row->nama_kelas . '"><i class="fas fa-users"></i></button>'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    /**
     * Get Peserta by Jadwal ID
     */
    public function get_peserta_jadwal($id_jadwal)
    {
        $list = $this->db->select('p.id_peserta, p.nama_anak, p.nama_ortu, p.no_hp, p.status, p.status_siswa')
                         ->from('peserta_jadwal pj')
                         ->join('peserta p', 'p.id_peserta = pj.id_peserta')
                         ->where('pj.id_jadwal_kelas', $id_jadwal)
                         ->order_by('p.status', 'ASC')
                         ->order_by('p.nama_anak', 'ASC')
                         ->get()
                         ->result();
        
        echo json_encode($list);
    }

    /**
     * Get Summary Statistics
     */
    public function get_summary()
    {
        // Total kelas aktif
        $total_kelas = $this->db->where('is_aktif', 1)->count_all_results('jadwal_kelas');
        
        // Total siswa aktif
        $total_siswa = $this->db->where('status', 'Registrasi Kelas')
                                 ->where('status_siswa', 'Aktif')
                                 ->count_all_results('peserta');
        
        // Total guru aktif
        $total_guru = $this->db->where('status', 'Aktif')->count_all_results('pengajar');
        
        // Jadwal per hari
        $jadwal_per_hari = $this->db->select('hari, COUNT(*) as jumlah')
                                    ->from('jadwal_kelas')
                                    ->where('is_aktif', 1)
                                    ->group_by('hari')
                                    ->get()
                                    ->result();
        
        echo json_encode([
            'total_kelas' => $total_kelas,
            'total_siswa' => $total_siswa,
            'total_guru' => $total_guru,
            'jadwal_per_hari' => $jadwal_per_hari
        ]);
    }
}
