<?php
class M_dashboard extends CI_Model
{
    public function get_total_peserta()
    {
        $this->db->from('peserta');
        $this->db->where('status_siswa', 'Aktif');
        return $this->db->count_all_results();
    }

    public function get_total_peserta_baru()
    {
        $this->db->from('peserta');
        $this->db->where('MONTH(tgl_konversi_siswa)', date('m'));
        $this->db->where('YEAR(tgl_konversi_siswa)', date('Y'));
        return $this->db->count_all_results();
    }


    public function get_total_pengajar()
    {
        return $this->db->count_all('pengajar');
    }
    public function get_total_kelas()
    {
        return $this->db->count_all('data_jenis_kelas');
    }
    
    public function get_total_murid_trial()
    {
        $this->db->from('peserta');
        $this->db->where('status_siswa', 'Trial');
        return $this->db->count_all_results();
    }

    public function get_total_pembayaran($month, $year)
    {
        $this->db->select_sum('jumlah');
        $this->db->where('status_bayar', 'Paid');
        $this->db->where('bulan', $month);
        $this->db->where('tahun', $year);
        $query = $this->db->get('tagihan');
        return $query->row()->jumlah ?: 0;
    }

    public function get_total_pengeluaran($month, $year)
    {
        $this->db->select_sum('jumlah');
        $this->db->where('MONTH(tanggal)', $month);
        $this->db->where('YEAR(tanggal)', $year);
        $query = $this->db->get('pengeluaran');
        return $query->row()->jumlah ?: 0;
    }

    public function get_jumlah_transaksi()
    {
        $this->db->where('status_bayar', 'Paid');
        $this->db->where('bulan', date('m'));
        $this->db->where('tahun', date('Y'));
        return $this->db->count_all_results('tagihan');
    }

    public function get_chart_income()
    {
        $this->db->select("bulan, SUM(jumlah) as total");
        $this->db->where('status_bayar', 'Paid');
        $this->db->group_by("bulan");
        $query = $this->db->get("tagihan");
        return $query->result();
    }


    public function get_peserta_baru()
    {
        $result = [];
        for ($i = 2; $i >= 0; $i--) {
            $bulan = date('n', strtotime("-$i month"));
            $tahun = date('Y', strtotime("-$i month"));
            $nama_bulan = date('F', strtotime("-$i month"));

            $total = $this->db->where('MONTH(input_at)', $bulan)
                ->where('YEAR(input_at)', $tahun)
                ->count_all_results('peserta');

            $result[] = [
                'nama_bulan' => $nama_bulan,
                'total_peserta' => $total
            ];
        }

        return $result;
    }

    public function get_data_pembelajaran_per_bulan()
    {
        $bulan = [];
        $tagihan = [];
        $bayar = [];

        for ($i = 1; $i <= 12; $i++) {
            $bulanNama = date('M', mktime(0, 0, 0, $i, 10)); // Jan, Feb, dst

            $total_tagihan = $this->db->select_sum('jumlah')
                ->where('bulan', $i)
                ->where('tahun', date('Y'))
                ->get('tagihan')
                ->row();

            $total_tagihan = isset($total_tagihan->jumlah) ? $total_tagihan->jumlah : 0;
            $row = $this->db->select_sum('jumlah')
                ->where('bulan', $i)
                ->where('tahun', date('Y'))
                ->where('status_bayar', 'Paid')
                ->get('tagihan')
                ->row();

            $total_bayar = isset($row->jumlah) ? $row->jumlah : 0;


            $bulan[] = $bulanNama;
            $tagihan[] = (int) $total_tagihan;
            $bayar[] = (int) $total_bayar;
        }

        return [
            'labels' => $bulan,
            'tagihan' => $tagihan,
            'bayar' => $bayar
        ];
    }


    public function get_pembayaran_bulanan()
    {
        // Tanggal sekarang dan bulan lalu
        $bulan_ini = date('m');
        $tahun_ini = date('Y');

        $bulan_lalu = date('m', strtotime('-1 month'));
        $tahun_lalu = date('Y', strtotime('-1 month'));

        // Total pembayaran bulan ini
        $this->db->select_sum('jumlah');
        $this->db->where('bulan', $bulan_ini);
        $this->db->where('tahun', $tahun_ini);
        $this->db->where('status_bayar', 'Paid');
        $row = $this->db->get('tagihan')->row();
        $total_ini = isset($row->jumlah) ? $row->jumlah : 0;

        // Total tagihan bulan lalu
        $this->db->select_sum('jumlah');
        $this->db->where('bulan', $bulan_lalu);
        $this->db->where('tahun', $tahun_lalu);
        $this->db->where('status_bayar', 'Paid');
        $row_lalu = $this->db->get('tagihan')->row();
        $total_lalu = isset($row_lalu->jumlah) ? $row_lalu->jumlah : 0;

        // Persentase perubahan
        if ($total_lalu > 0) {
            $persentase = (($total_ini - $total_lalu) / $total_lalu) * 100;
        } else {
            $persentase = 100; // default jika bulan lalu nol
        }

        return [
            'total_bulan_ini' => (float) $total_ini,
            'total_bulan_lalu' => (float) $total_lalu,
            'persentase' => round($persentase, 2),
        ];
    }
    
    // =============================================
    // SUPERADMIN DASHBOARD - FINANCIAL FOCUS
    // =============================================
    
    /**
     * Get SPP yang belum dibayar
     */
    public function get_spp_belum_dibayar($bulan = null, $tahun = null)
    {
        $this->db->select_sum('jumlah');
        $this->db->where('status_bayar !=', 'Paid');
        if ($bulan !== null) {
            $this->db->where('bulan', $bulan);
        }
        if ($tahun !== null) {
            $this->db->where('tahun', $tahun);
        }
        $query = $this->db->get('tagihan');
        return $query->row()->jumlah ?: 0;
    }
    
    /**
     * Get utang payroll (payroll yang belum dibayar - status draft/approved)
     */
    public function get_utang_payroll()
    {
        $this->db->select_sum('total_gaji_bersih');
        $this->db->where_in('status', array('draft', 'approved'));
        $query = $this->db->get('payroll_guru');
        return $query->row()->total_gaji_bersih ?: 0;
    }
    
    /**
     * Get saldo kas (total pemasukan - total pengeluaran)
     */
    public function get_saldo_kas()
    {
        // Total pemasukan
        $this->db->select_sum('jumlah');
        $this->db->where('status_bayar', 'Paid');
        $pemasukan = $this->db->get('tagihan')->row()->jumlah ?: 0;
        
        // Total pengeluaran
        $this->db->select_sum('jumlah');
        $pengeluaran = $this->db->get('pengeluaran')->row()->jumlah ?: 0;
        
        return $pemasukan - $pengeluaran;
    }
    
    /**
     * Get laba rugi bulan ini
     */
    public function get_laba_rugi($month = null, $year = null)
    {
        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        
        // Total pemasukan bulan ini
        $this->db->select_sum('jumlah');
        $this->db->where('status_bayar', 'Paid');
        $this->db->where('bulan', $month);
        $this->db->where('tahun', $year);
        $pemasukan = $this->db->get('tagihan')->row()->jumlah ?: 0;
        
        // Total pengeluaran bulan ini
        $this->db->select_sum('jumlah');
        $this->db->where('MONTH(tanggal)', $month);
        $this->db->where('YEAR(tanggal)', $year);
        $pengeluaran = $this->db->get('pengeluaran')->row()->jumlah ?: 0;
        
        return $pemasukan - $pengeluaran;
    }
    
    /**
     * Get rincian pendapatan terbaru
     */
    public function get_rincian_pendapatan($year = null, $limit = 10)
    {
        $year = $year ?: date('Y');
        
        return $this->db->select('t.*, p.nama_anak, p.nama_ortu, djk.nama_kelas')
                        ->from('tagihan t')
                        ->join('peserta p', 'p.id_peserta = t.id_peserta')
                        ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = p.id_jenis_kelas', 'left')
                        ->where('t.status_bayar', 'Paid')
                        ->where('t.tahun', $year)
                        ->order_by('t.tgl_bayar', 'DESC')
                        ->limit($limit)
                        ->get()
                        ->result();
    }
    
    /**
     * Get rincian pengeluaran terbaru
     */
    public function get_rincian_pengeluaran($year = null, $limit = 10)
    {
        $year = $year ?: date('Y');
        
        return $this->db->select('*')
                        ->from('pengeluaran')
                        ->where('YEAR(tanggal)', $year)
                        ->order_by('tanggal', 'DESC')
                        ->limit($limit)
                        ->get()
                        ->result();
    }
    
    /**
     * Get pendapatan per kategori (untuk pie chart)
     */
    public function get_pendapatan_per_kategori($year = null)
    {
        $year = $year ?: date('Y');
        
        return $this->db->select('djk.nama_kelas as kategori, SUM(t.jumlah) as total')
                        ->from('tagihan t')
                        ->join('peserta p', 'p.id_peserta = t.id_peserta')
                        ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = p.id_jenis_kelas')
                        ->where('t.status_bayar', 'Paid')
                        ->where('t.tahun', $year)
                        ->group_by('djk.id_jenis_kelas')
                        ->order_by('total', 'DESC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get pengeluaran per kategori (untuk pie chart)
     */
    public function get_pengeluaran_per_kategori($year = null)
    {
        $year = $year ?: date('Y');
        
        return $this->db->select('kategori, SUM(jumlah) as total')
                        ->from('pengeluaran')
                        ->where('YEAR(tanggal)', $year)
                        ->group_by('kategori')
                        ->order_by('total', 'DESC')
                        ->get()
                        ->result();
    }
}
