<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->load->model('m_user');
        $this->load->model('m_dashboard');
        $this->apk = $this->m_masterdata->get_konfig(0);
        if ($this->session->userdata('is_login') !== true) {
            redirect(site_url("Login"));
        }
    }

    public function index()
    {
        $jabatan = $this->session->userdata('jabatan');
        $bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
        $tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
        
        // Common data for all dashboards
        $data = array();
        
        if ($jabatan == 'superadmin') {
            // Superadmin - Financial Dashboard
            $data['isi'] = 'dashboard/index';
            $data['total_peserta'] = $this->m_dashboard->get_total_peserta();
            $data['total_pengajar'] = $this->m_dashboard->get_total_pengajar();
            $data['total_peserta_baru'] = $this->m_dashboard->get_total_peserta_baru();
            
            // Financial data - filtered by period
            $data['saldo_kas'] = $this->m_dashboard->get_saldo_kas();
            $data['laba_rugi'] = $this->m_dashboard->get_laba_rugi($bulan, $tahun);
            $data['spp_belum_dibayar'] = $this->m_dashboard->get_spp_belum_dibayar($bulan, $tahun);
            $data['total_pembayaran'] = $this->m_dashboard->get_total_pembayaran($bulan, $tahun);
            $data['utang_payroll'] = $this->m_dashboard->get_utang_payroll();
            
            // Rincian data
            $data['rincian_pendapatan'] = $this->m_dashboard->get_rincian_pendapatan($tahun, 10);
            $data['rincian_pengeluaran'] = $this->m_dashboard->get_rincian_pengeluaran($tahun, 10);
            
            // Pie chart data
            $data['pendapatan_kategori'] = $this->m_dashboard->get_pendapatan_per_kategori($tahun);
            $data['pengeluaran_kategori'] = $this->m_dashboard->get_pengeluaran_per_kategori($tahun);
            
            
        } elseif ($jabatan == 'admin') {
            // Admin - Placeholder untuk dashboard khusus (akan dijelaskan user)
            $data['isi'] = 'dashboard/admin';
            $data['total_peserta'] = $this->m_dashboard->get_total_peserta();
            $data['total_pengajar'] = $this->m_dashboard->get_total_pengajar();
            $data['total_kelas'] = $this->m_dashboard->get_total_kelas();
            $data['total_peserta_baru'] = $this->m_dashboard->get_total_peserta_baru();
            $data['total_murid_trial'] = $this->m_dashboard->get_total_murid_trial();
            $data['spp_belum_dibayar'] = $this->m_dashboard->get_spp_belum_dibayar($bulan, $tahun);

            
        } elseif ($jabatan == 'finance') {
            // Finance - Keuangan & HR focused dashboard
            $data['isi'] = 'dashboard/finance';
            $data['total_pembayaran'] = $this->m_dashboard->get_total_pembayaran($bulan, $tahun);
            $data['total_pengeluaran'] = $this->m_dashboard->get_total_pengeluaran($bulan, $tahun);
            $data['spp_belum_dibayar'] = $this->m_dashboard->get_spp_belum_dibayar($bulan, $tahun);
            $data['laba_rugi'] = $this->m_dashboard->get_laba_rugi($bulan, $tahun);
            $pembayaran = $this->m_dashboard->get_pembayaran_bulanan();
            $data['total_pembayaran_ini'] = $pembayaran['total_bulan_ini'];
            $data['total_pembayaran_lalu'] = $pembayaran['total_bulan_lalu'];
            $data['persentase_pembayaran'] = $pembayaran['persentase'];
            
        } else {
            // Default fallback
            $data['isi'] = 'dashboard/index';
            $data['total_peserta'] = $this->m_dashboard->get_total_peserta();
            $data['total_pengajar'] = $this->m_dashboard->get_total_pengajar();
            $data['total_kelas'] = $this->m_dashboard->get_total_kelas();
            $data['total_pembayaran'] = $this->m_dashboard->get_total_pembayaran($bulan, $tahun);
            $data['total_pengeluaran'] = $this->m_dashboard->get_total_pengeluaran($bulan, $tahun);
            $data['jumlah_transaksi'] = $this->m_dashboard->get_jumlah_transaksi();
            $data['chart_income'] = $this->m_dashboard->get_chart_income();
            $pembayaran = $this->m_dashboard->get_pembayaran_bulanan();
            $data['total_pembayaran_ini'] = $pembayaran['total_bulan_ini'];
            $data['total_pembayaran_lalu'] = $pembayaran['total_bulan_lalu'];
            $data['persentase_pembayaran'] = $pembayaran['persentase'];
        }

        $this->load->view('layouts/wrapper', $data);
    }

    public function get_pembelajaran_per_bulan()
    {
        $data = $this->m_dashboard->get_data_pembelajaran_per_bulan();
        echo json_encode($data);
    }

    public function get_peserta_baru_per_3_bulan()
    {
        $data = $this->m_dashboard->get_peserta_baru();


        $labels = array_column($data, 'nama_bulan');
        $jumlah = array_column($data, 'total_peserta');

        echo json_encode([
            'labels' => $labels,
            'data' => $jumlah
        ]);
    }
}
