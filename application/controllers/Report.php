<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->load->model('m_user');
        $this->load->model('m_tagihan');
        $this->load->library('excel');
        $this->apk = $this->m_masterdata->get_konfig(0);


        if ($this->session->userdata('is_login') !== true) {
            redirect(site_url("Login"));
        }
    }


    public function lap_peserta_aktif()
    {
        if ($this->session->userdata('jabatan') !== 'superadmin') {
            redirect(site_url("Login"));
        }
        $data = array('isi' => 'report/peserta_aktif');
        $data['kelas'] = $this->m_masterdata->get_allKelas(); // detail 
        $this->load->view('layouts/wrapper', $data);
    }

    public function get_laporan_peserta()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $id_kelas = $this->input->post('id_kelas');

        $this->db->select('p.nama, p.no_wa, k.nama_kelas, t.bulan, t.tahun, t.tgl_bayar');
        $this->db->from('tagihan t');
        $this->db->join('peserta p', 'p.id_peserta = t.id_peserta');
        $this->db->join('data_jenis_kelas k', 'k.id_jenis_kelas = p.id_jenis_kelas');
        $this->db->where('t.status_bayar', 1);

        if ($bulan) $this->db->where('t.bulan', $bulan);
        if ($tahun) $this->db->where('t.tahun', $tahun);
        if ($id_kelas) $this->db->where('k.id_jenis_kelas', $id_kelas);

        $query = $this->db->get()->result();
        echo json_encode($query);
    }


    public function lap_pembayaran_peserta()
    {
        if ($this->session->userdata('jabatan') !== 'superadmin') {
            redirect(site_url("Login"));
        }
        $data = array('isi' => 'report/rekap_pembayaran');
        $this->load->view('layouts/wrapper', $data);
    }

    public function get_rekap_pembayaran()
    {
        // Ambil data bulan dan tahun yang dipilih dari request
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        // Panggil model untuk mengambil rekap pembayaran
        $rekap = $this->m_tagihan->get_rekap_pembayaran($bulan, $tahun);

        // Kirim data ke client-side dalam format JSON
        echo json_encode($rekap);
    }

    // Fungsi untuk mendapatkan detail peserta berdasarkan kelas
    public function get_peserta_by_kelas()
    {
        // Ambil data id kelas yang dipilih dari request
        $id_kelas = $this->input->post('id_kelas');
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        // Panggil model untuk mendapatkan detail peserta berdasarkan kelas
        $peserta = $this->m_tagihan->get_peserta_by_kelas($id_kelas, $bulan, $tahun);

        // Kirim data detail peserta dalam format JSON
        echo json_encode($peserta);
    }

    public function lap_keberlanjutan_peserta()
    {
        if ($this->session->userdata('jabatan') !== 'superadmin') {
            redirect(site_url("Login"));
        }
        $data = array('isi' => 'report/keberlanjutan_peserta');
        $this->load->view('layouts/wrapper', $data);
    }

    public function get_perbandingan_rentang()
    {
        $periode = json_decode($this->input->post('periode'), true);
        $data = $this->m_tagihan->get_perbandingan_rentang($periode);
        echo json_encode($data);
    }

    public function lap_jadwal_pengajar()
    {
        $data = array('isi' => 'report/jadwal_pengajar');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar(); // detail  
        $data['jadwal_per_hari'] = $this->m_masterdata->get_allJadwal(); // detail  
        $this->load->view('layouts/wrapper', $data);
    }

    public function get_jadwal_kosong_pengajar()
    {
        // Mengambil data inputan filter
        $id_pengajar = $this->input->post('id_pengajar');
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        // Validasi input (optional)
        if (empty($id_pengajar) || empty($bulan) || empty($tahun)) {
            echo json_encode([]);
            return;
        }

        // Ambil data jadwal kosong pengajar dari model
        $data = $this->m_tagihan->get_jadwal_kosong_pengajar($id_pengajar, $bulan, $tahun);

        // Mengirimkan data ke tampilan dalam bentuk JSON
        echo json_encode($data);
    }

    public function lap_transaksi()
    {
        if ($this->session->userdata('jabatan') !== 'superadmin') {
            redirect(site_url("Login"));
        }
        $data = array('isi' => 'report/lap_transaksi');
        $this->load->view('layouts/wrapper', $data);
    }

    public function lap_transaksi_page()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $tipe = $this->input->post('tipe');
        $draw = $this->input->post('draw');

        $result = $this->m_tagihan->get_transaksi($bulan, $tahun, $tipe);

        $data = [];
        $no = 1;

        foreach ($result['data'] as $row) {
            $data[] = [
                'no' => $no++,
                'tanggal' => date('d-m-Y', strtotime($row->tanggal)),
                'tipe' =>  $row->tipe == 'pemasukan'
                    ? '<span class="badge badge-success">Pemasukan</span>'
                    : '<span class="badge badge-danger">Pengeluaran</span>',
                'deskripsi' => $row->deskripsi,
                'jumlah' => 'Rp ' . number_format($row->jumlah, 0, ',', '.'),
                'keterangan' => $row->keterangan
            ];
        }

        $total_saldo = $result['total_pemasukan'] - $result['total_pengeluaran'];

        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => count($data),
            "recordsFiltered" => count($data),
            "data" => $data,
            "total_formatted" => 'Rp ' . number_format($total_saldo, 0, ',', '.')
        ]);
    }
    
    // =============================================
    // DASHBOARD STATISTIK MURID
    // =============================================
    
    public function lap_statistik_murid()
    {
        $data = array('isi' => 'report/dashboard_statistik');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_statistik_murid()
    {
        // Inquiry - orangtua yang menghubungi (status = Inquiry)
        $inquiry = $this->db->where('status', 'Inquiry')->count_all_results('peserta');
        
        // Total trial (status = Trial)
        $trial = $this->db->where('status', 'Trial')->count_all_results('peserta');
        
        // Murid trial (status_siswa = Trial)
        $murid_trial = $this->db->where('status_siswa', 'Trial')->where('status', 'Registrasi Kelas')->count_all_results('peserta');
        
        // Murid aktif (status_siswa = Aktif AND is_aktif = 1)
        $aktif = $this->db->where('status_siswa', 'Aktif')->where('is_aktif', 1)->where('status', 'Registrasi Kelas')->count_all_results('peserta');
        
        // Murid cuti (status_siswa = Cuti)
        $cuti = $this->db->where('status_siswa', 'Cuti')->where('status', 'Registrasi Kelas')->count_all_results('peserta');
        
        // Murid mengundurkan diri (is_aktif = 0 OR status_siswa = Mengundurkan Diri)
        $resign = $this->db->where('status_siswa', 'Mengundurkan Diri')->count_all_results('peserta');
        
        // Total kelas
        $total_kelas = $this->db->count_all_results('data_jenis_kelas');
        
        // Total jadwal aktif
        $total_jadwal = $this->db->count_all_results('pengajar_jadwal');
        
        echo json_encode([
            'inquiry' => $inquiry,
            'trial' => $trial,
            'murid_trial' => $murid_trial,
            'aktif' => $aktif,
            'cuti' => $cuti,
            'resign' => $resign,
            'total_kelas' => $total_kelas,
            'total_jadwal' => $total_jadwal
        ]);
    }
    
    public function get_inquiry_orangtua()
    {
        $list = $this->db->select('p.*, jk.nama_kelas')
                         ->from('peserta p')
                         ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                         ->where('p.status', 'Inquiry')
                         ->order_by('p.tgl_terakhir_dihubungi', 'DESC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $status_badge = '<span class="badge badge-warning">Inquiry</span>';
            
            $data[] = array(
                $no++,
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_anak ?: '-',
                $row->tgl_terakhir_dihubungi ? date('d/m/Y', strtotime($row->tgl_terakhir_dihubungi)) : '-',
                $row->src ?: '-',
                $status_badge
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function get_kelas_jadwal()
    {
        $list = $this->db->select('jk.id_jenis_kelas, jk.nama_kelas, jk.biaya,
                                   COUNT(DISTINCT p.id_peserta) as total_murid,
                                   COUNT(DISTINCT pj.id_jadwal_pengajar) as total_jadwal')
                         ->from('data_jenis_kelas jk')
                         ->join('peserta p', 'p.id_jenis_kelas = jk.id_jenis_kelas AND p.is_aktif = 1', 'left')
                         ->join('peserta_jadwal psj', 'psj.id_peserta = p.id_peserta', 'left')
                         ->join('pengajar_jadwal pj', 'pj.id_jadwal_pengajar = psj.id_jadwal_pengajar', 'left')
                         ->group_by('jk.id_jenis_kelas')
                         ->order_by('total_murid', 'DESC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $data[] = array(
                $no++,
                $row->nama_kelas,
                '<span class="badge badge-primary">' . $row->total_murid . ' murid</span>',
                '<span class="badge badge-info">' . $row->total_jadwal . ' jadwal</span>',
                'Rp ' . number_format($row->biaya)
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    // =============================================
    // SEPARATE REPORT PAGES
    // =============================================
    
    public function lap_inquiry_orangtua()
    {
        $data = array('isi' => 'report/inquiry_orangtua');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_data_inquiry()
    {
        $list = $this->db->select('p.*, jk.nama_kelas')
                         ->from('peserta p')
                         ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                         ->where('p.status', 'Info Harga')
                         ->order_by('p.tgl_terakhir_dihubungi', 'DESC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $data[] = array(
                $no++,
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_anak ?: '-',
                $row->tgl_terakhir_dihubungi ? date('d/m/Y', strtotime($row->tgl_terakhir_dihubungi)) : '-',
                $row->src ?: '-',
                $row->nama_kelas ?: '-'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function lap_murid_trial()
    {
        $data = array('isi' => 'report/murid_trial');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_data_murid_trial()
    {
        // Ambil data dari lead_status_history dimana status_baru = 'Jadwal Trial'
        $list = $this->db->select('p.id_peserta, p.nama_anak, p.nama_ortu, p.no_hp, 
                                   jk.nama_kelas, h.tgl_update as tgl_trial, p.status')
                         ->from('lead_status_history h')
                         ->join('peserta p', 'p.id_peserta = h.id_peserta')
                         ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                         ->where('h.status_baru', 'Jadwal Trial')
                         ->group_by('p.id_peserta') // Ambil satu record per peserta
                         ->order_by('h.tgl_update', 'DESC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            // Status badge berdasarkan status saat ini
            $status_badge = '<span class="badge badge-info">' . $row->status . '</span>';
            
            // Keterangan: jika status bukan Jadwal Trial = Selesai, jika masih = Trial Belum Selesai
            if ($row->status == 'Jadwal Trial') {
                $keterangan = '<span class="badge badge-warning">Trial Belum Selesai</span>';
            } else {
                $keterangan = '<span class="badge badge-success">Selesai</span>';
            }
            
            $data[] = array(
                $no++,
                $row->nama_anak ?: '-',
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_kelas ?: '-',
                $row->tgl_trial ? date('d/m/Y H:i', strtotime($row->tgl_trial)) : '-',
                $status_badge,
                $keterangan
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function lap_murid_aktif()
    {
        $data = array('isi' => 'report/murid_aktif');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_data_murid_aktif()
    {
        $this->load->model('m_siswa');
        
        $list = $this->db->select('p.*, jk.nama_kelas')
                         ->from('peserta p')
                         ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                         ->where('p.status', 'Registrasi Kelas') 
                         ->where('p.status_siswa', 'Aktif')
                         ->order_by('p.nama_anak', 'ASC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $level_aktif = $this->m_siswa->get_level_aktif_siswa($row->id_peserta);
            $level_nama = $level_aktif ? $level_aktif->nama_level : '-';
            
            $data[] = array(
                $no++,
                $row->nama_anak ?: '-',
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_kelas ?: '-',
                '<span class="badge badge-info">' . $level_nama . '</span>',
                '<span class="badge badge-success">Aktif</span>'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function lap_murid_cuti()
    {
        $data = array('isi' => 'report/murid_cuti');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_data_murid_cuti()
    {
        $list = $this->db->select('p.*, jk.nama_kelas')
                         ->from('peserta p')
                         ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                         ->where('p.status_siswa', 'Cuti')
                         ->order_by('p.nama_anak', 'ASC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $data[] = array(
                $no++,
                $row->nama_anak ?: '-',
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_kelas ?: '-',
                $row->catatan ?: '-',
                '<span class="badge badge-warning">Cuti</span>'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function lap_murid_resign()
    {
        $data = array('isi' => 'report/murid_resign');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_data_murid_resign()
    {
        $list = $this->db->select('p.*, jk.nama_kelas')
                         ->from('peserta p')
                         ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                         ->group_start()
                             ->where('p.status_siswa', 'Non Aktif') 
                         ->group_end()
                         ->order_by('p.tgl_non_aktif', 'DESC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $data[] = array(
                $no++,
                $row->nama_anak ?: '-',
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_kelas ?: '-',
                $row->tgl_non_aktif ? date('d/m/Y', strtotime($row->tgl_non_aktif)) : '-',
                '<span class="badge badge-danger">Mengundurkan Diri</span>'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function lap_kelas_jadwal()
    {
        $data = array('isi' => 'report/kelas_jadwal');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_kelas_jadwal_summary()
    {
        $hari_arr = array('', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
        
        // Query menggunakan struktur baru: jadwal_kelas dan peserta_jadwal.id_jadwal_kelas
        $list = $this->db->select('jk.id, jk.hari, jk.jam_mulai, jk.jam_selesai, jk.ruangan, jk.tipe_kelas, jk.jenis_jadwal,
                                   djk.id_jenis_kelas, djk.nama_kelas, pg.nama as nama_guru,
                                   (SELECT COUNT(DISTINCT pj.id_peserta) 
                                    FROM peserta_jadwal pj 
                                    JOIN peserta p ON p.id_peserta = pj.id_peserta 
                                    WHERE pj.id_jadwal_kelas = jk.id 
                                    AND p.status = "Registrasi Kelas" 
                                    AND p.status_siswa = "Aktif") as total_murid_aktif,
                                   (SELECT COUNT(DISTINCT pj.id_peserta) 
                                    FROM peserta_jadwal pj 
                                    JOIN peserta p ON p.id_peserta = pj.id_peserta 
                                    WHERE pj.id_jadwal_kelas = jk.id 
                                    AND p.status != "Registrasi Kelas") as total_lead')
                         ->from('jadwal_kelas jk')
                         ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = jk.id_kelas')
                         ->join('pengajar pg', 'pg.id_pengajar = jk.id_guru')
                         ->where('jk.is_aktif', 1)
                         ->order_by('djk.nama_kelas', 'ASC')
                         ->order_by('jk.hari', 'ASC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            $hari_nama = isset($hari_arr[$row->hari]) ? $hari_arr[$row->hari] : '-';
            
            // Tipe & Jenis badge
            $tipe_badge = ($row->tipe_kelas == 'dewasa') 
                ? '<span class="badge badge-info">Dewasa</span>' 
                : '<span class="badge badge-success">Anak</span>';
            
            $jenis_badge = '';
            if ($row->jenis_jadwal == 'Trial Class') {
                $jenis_badge = '<span class="badge badge-warning">Trial</span>';
            } else if ($row->jenis_jadwal == 'Placement Test') {
                $jenis_badge = '<span class="badge badge-primary">Placement</span>';
            } else {
                $jenis_badge = '<span class="badge badge-secondary">Regular</span>';
            }
            
            $data[] = array(
                $no++,
                $row->nama_kelas,
                $tipe_badge . ' ' . $jenis_badge,
                '<span class="badge badge-secondary">' . $hari_nama . '</span>',
                substr($row->jam_mulai, 0, 5) . ' - ' . substr($row->jam_selesai, 0, 5),
                $row->nama_guru,
                '<span class="badge badge-primary">' . $row->total_murid_aktif . ' siswa</span> <span class="badge badge-warning">' . $row->total_lead . ' lead</span>',
                '<button class="btn btn-info btn-sm btn-detail-murid" data-jadwal="' . $row->id . '" data-nama="' . $row->nama_kelas . '"><i class="fa fa-users"></i> Detail</button>'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function get_murid_by_kelas($id_jadwal = null)
    {
        $id_jadwal = $id_jadwal ?: $this->input->get('id_jadwal');
        
        // Get peserta yang terdaftar di jadwal ini
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
    
    // =============================================
    // LAPORAN LEAD (Status != Registrasi Kelas)
    // =============================================
    
    public function lap_lead()
    {
        $jabatan = $this->session->userdata('jabatan');
        if ($jabatan !== 'superadmin' && $jabatan !== 'admin') {
            redirect(site_url("Dashboard"));
        }
        $data = array('isi' => 'report/lap_lead');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_data_lead()
    {
        $status_filter = $this->input->post('status');
        
        $this->db->select('p.*, jk.nama_kelas')
                 ->from('peserta p')
                 ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                 ->where('p.status !=', 'Registrasi Kelas');
        
        if (!empty($status_filter)) {
            $this->db->where('p.status', $status_filter);
        }
        
        $list = $this->db->order_by('p.tgl_terakhir_dihubungi', 'DESC')
                         ->order_by('p.id_peserta', 'DESC')
                         ->get()
                         ->result();
        
        $data = array();
        $no = 1;
        
        foreach ($list as $row) {
            // Status badge color
            $status_color = 'warning';
            if ($row->status == 'Info Harga') $status_color = 'info';
            else if ($row->status == 'Jadwal Trial') $status_color = 'primary';
            else if ($row->status == 'Placement Test') $status_color = 'success';
            
            // Jenis siswa badge
            $jenis_badge = ($row->jenis_siswa == 'partnership') 
                ? '<span class="badge badge-warning">Partnership</span>' 
                : '<span class="badge badge-secondary">Regular</span>';
            
            // Format tgl terakhir dihubungi dengan warna (lebih dari 7 hari = merah)
            $tgl_kontak = '-';
            $kontak_badge_class = 'badge-secondary';
            if ($row->tgl_terakhir_dihubungi) {
                $tgl_kontak = date('d/m/Y', strtotime($row->tgl_terakhir_dihubungi));
                $diff = floor((time() - strtotime($row->tgl_terakhir_dihubungi)) / (60 * 60 * 24));
                if ($diff > 7) {
                    $kontak_badge_class = 'badge-danger';
                } else if ($diff > 3) {
                    $kontak_badge_class = 'badge-warning';
                } else {
                    $kontak_badge_class = 'badge-success';
                }
            }
            
            $data[] = array(
                $no++,
                $row->nama_anak ?: '-',
                $row->nama_ortu ?: '-',
                $row->no_hp,
                $row->nama_kelas ?: '-',
                '<span class="badge ' . $status_color . '">' . $row->status . '</span>',
                '<span class="badge ' . $kontak_badge_class . '">' . $tgl_kontak . '</span>',
                $jenis_badge,
                $row->src ?: '-',
                $row->catatan ?: '-'
            );
        }
        
        echo json_encode(array("data" => $data));
    }
}
