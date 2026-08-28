<?php
class M_tagihan extends CI_Model
{

    // Kolom yang dapat diurutkan - sesuai dengan urutan kolom di view (skip kolom No karena manual)
    // Index 0 = No (skip), 1 = Nama, 2 = No HP, 3 = Bulan, 4 = Tahun, 5 = Jumlah, 6 = Prorata, 7 = Tipe, 8 = Status, 9 = Tgl Bayar, 10 = Fungsi
    var $column_order = [null, 'p.nama_anak', 'p.no_hp', 't.bulan', 't.tahun', 't.jumlah', 't.is_prorata', 't.tipe', 't.status_bayar', 't.tgl_bayar', null];
    var $column_search = ['p.nama_anak', 'p.no_hp', 't.tipe']; // Kolom yang bisa dicari
    var $order = ['t.id_tagihan' => 'desc']; // Default: Invoice terbaru dulu

    public function __construct()
    {
        parent::__construct();
    }

    private function _get_datatables_query($bulan, $tahun, $status)
    {
        $this->db->select('t.id_tagihan, p.nama_anak as nama, p.no_hp as no_wa, t.bulan, t.tahun, t.jumlah, t.status_bayar, t.tgl_bayar, t.is_prorata, t.jml_pertemuan, t.tipe');
        $this->db->from('tagihan t');
        $this->db->join('peserta p', 'p.id_peserta = t.id_peserta');

        if ($bulan) {
            $this->db->where('t.bulan', $bulan);
        }

        if ($tahun) {
            $this->db->where('t.tahun', $tahun);
        }

        if ($status !== null && $status !== '') {
            $this->db->where('t.status_bayar', $status);
        }

        // Pencarian
        if ($this->input->post('search')['value']) {
            $search_value = $this->input->post('search')['value'];
            $this->db->group_start();
            foreach ($this->column_search as $item) {
                $this->db->or_like($item, $search_value);
            }
            $this->db->group_end();
        }

        // Urutan - perbaiki agar tidak error saat sorting
        if (isset($_POST['order'])) {
            $order_column = $_POST['order'][0]['column'];
            $order_dir = $_POST['order'][0]['dir'];
            
            // Pastikan kolom yang dipilih bisa diurutkan (tidak null)
            if (isset($this->column_order[$order_column]) && $this->column_order[$order_column] !== null) {
                $this->db->order_by($this->column_order[$order_column], $order_dir);
            } else {
                // Fallback ke default order jika kolom tidak valid
                $order = $this->order;
                $this->db->order_by(key($order), $order[key($order)]);
            }
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    public function get_datatables($bulan, $tahun, $status)
    {
        $this->_get_datatables_query($bulan, $tahun, $status);
        if ($_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start']);
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function count_filtered($bulan, $tahun, $status)
    {
        $this->_get_datatables_query($bulan, $tahun, $status);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from('tagihan t');
        return $this->db->count_all_results();
    }

    public function bayar_tagihan($id_tagihan)
    {
        $this->db->set('status_bayar', 'Paid');
        $this->db->set('tgl_bayar', date('Y-m-d H:i:s'));
        $this->db->where('id_tagihan', $id_tagihan);
        $this->db->update('tagihan');
    }


    public function get_rekap_pembayaran($bulan, $tahun)
    {
        $this->db->select("kelas.id_jenis_kelas, kelas.nama_kelas, 
                           COUNT(peserta.id_peserta) AS jumlah_peserta, 
                           SUM(tagihan.jumlah) AS total_tagihan, 
                           SUM(IF(tagihan.status_bayar = 'Paid', tagihan.jumlah, 0)) AS total_sudah_bayar, 
                           SUM(IF(tagihan.status_bayar != 'Paid', tagihan.jumlah, 0)) AS total_belum_bayar");
        $this->db->from('data_jenis_kelas as kelas');
        $this->db->join('peserta', 'kelas.id_jenis_kelas = peserta.id_jenis_kelas');
        $this->db->join('tagihan', 'peserta.id_peserta = tagihan.id_peserta');
        $this->db->where('tagihan.bulan', $bulan);
        $this->db->where('tagihan.tahun', $tahun);
        $this->db->group_by('kelas.id_jenis_kelas');
        $query = $this->db->get();
        return $query->result();
    }

    // Fungsi untuk mendapatkan detail peserta per kelas
    public function get_peserta_by_kelas($id_kelas, $bulan, $tahun)
    {
        $this->db->select('tagihan.id_tagihan, peserta.nama_anak as nama, peserta.no_hp as no_wa, tagihan.tgl_bayar, tagihan.jumlah AS jumlah_bayar');
        $this->db->from('peserta');
        $this->db->join('tagihan', 'peserta.id_peserta = tagihan.id_peserta');
        $this->db->where('peserta.id_jenis_kelas', $id_kelas);
        $this->db->where('tagihan.bulan', $bulan);
        $this->db->where('tagihan.tahun', $tahun);
        $query = $this->db->get();
        return $query->result();
    }

    public function hapus_tagihan_belum_bayar_by_id_peserta($id_peserta)
    {
        $bulan = date('m');
        $tahun = date('Y');

        $this->db->where('id_peserta', $id_peserta);
        $this->db->where('status_bayar !=', 'Paid');

        $this->db->group_start()
            ->where('tahun >', $tahun)
            ->or_group_start()
            ->where('tahun', $tahun)
            ->where('bulan >=', $bulan)
            ->group_end()
            ->group_end();

        return $this->db->delete('tagihan');
    }

    public function get_perbandingan_rentang($periode)
    {
        $kelasList = $this->db->get('data_jenis_kelas')->result();
        $result = [];

        foreach ($kelasList as $kelas) {
            $row = [
                'nama_kelas' => $kelas->nama_kelas,
                'data' => []
            ];

            foreach ($periode as $p) {
                $this->db->select('COUNT(*) as total');
                $this->db->from('tagihan t');
                $this->db->join('peserta p', 'p.id_peserta = t.id_peserta');
                $this->db->where('p.id_jenis_kelas', $kelas->id_jenis_kelas);
                $this->db->where('t.status_bayar', 'Paid');
                $this->db->where('t.bulan', $p['bulan']);
                $this->db->where('t.tahun', $p['tahun']);
                $query = $this->db->get()->row();

                $key = $p['bulan'] . '-' . $p['tahun'];
                $row['data'][$key] = (int)$query->total;
            }

            $result[] = $row;
        }

        return $result;
    }



    public function get_jadwal_kosong_pengajar($id_pengajar, $bulan, $tahun)
    {
        // Ambil semua jadwal default pengajar
        $this->db->where('id_pengajar', $id_pengajar);
        $jadwal = $this->db->get('pengajar_jadwal')->result();

        $hasil = [];

        $namaHari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        foreach ($jadwal as $j) {
            $this->db->select('CONCAT(p.nama_anak, " (", p.no_hp, ")") as nama_peserta, bayar.status_bayar');
            $this->db->from('peserta_jadwal pj');
            $this->db->join('pengajar_jadwal pej', 'pej.id_jadwal_pengajar = pj.id_jadwal_pengajar');
            $this->db->join('peserta p', 'p.id_peserta = pj.id_peserta');
            $this->db->join('tagihan bayar', 'bayar.id_peserta = p.id_peserta');
            $this->db->group_start()
                ->where('bayar.status_bayar', 'Paid')
                ->or_where('bayar.status_bayar', 'Late')
                ->group_end();
            $this->db->where('bayar.bulan', $bulan);
            $this->db->where('bayar.tahun', $tahun);
            $this->db->where('pej.hari', $j->hari);
            $this->db->where('pej.jam_mulai', $j->jam_mulai);
            $this->db->where('pej.jam_selesai', $j->jam_selesai);
            $this->db->where('pej.id_pengajar', $id_pengajar);

            $result = $this->db->get()->result();
            $terpakai = !empty($result);
            // Ambil semua nama peserta dan gabungkan dengan koma
            $nama_peserta = '';
            if ($terpakai) {
                $nama_list = array_map(function ($item) {
                    return $item->nama_peserta;
                }, $result);

                $nama_peserta = implode(', ', $nama_list);
            }


            $hasil[] = [
                'hari' => isset($namaHari[$j->hari]) ? $namaHari[$j->hari] : 'Tidak Diketahui',
                'jam_mulai' => $j->jam_mulai,
                'jam_selesai' => $j->jam_selesai,
                'terpakai' => $terpakai,
                'nama_peserta' => $nama_peserta,
                'status_bayar' => !empty($result) ? $result[0]->status_bayar : ''
            ];
        }

        return $hasil;
    }
    // var_dump('<pre>');
    // var_dump($result);
    // die;

    public function get_transaksi($bulan, $tahun, $tipe)
    {
        $results = [];
        $total_pemasukan = 0;
        $total_pengeluaran = 0;

        if ($tipe == 'semua' || $tipe == 'pengeluaran') {
            $this->db->select('tanggal, "pengeluaran" as tipe, kategori as deskripsi, jumlah, keterangan');
            $this->db->from('pengeluaran');
            $this->db->where('MONTH(tanggal)', $bulan);
            $this->db->where('YEAR(tanggal)', $tahun);
            $pengeluaran = $this->db->get()->result();

            foreach ($pengeluaran as $p) {
                $total_pengeluaran += $p->jumlah;
            }

            $results = array_merge($results, $pengeluaran);
        }

        if ($tipe == 'semua' || $tipe == 'pemasukan') {
            $this->db->select('tgl_bayar as tanggal, "pemasukan" as tipe, CONCAT("Pembayaran ", nama_anak) as deskripsi, jumlah as jumlah, "Biaya Kelas" as keterangan');
            $this->db->from('tagihan');
            $this->db->join('peserta', 'peserta.id_peserta = tagihan.id_peserta');
            $this->db->where('tagihan.bulan', $bulan);
            $this->db->where('tagihan.tahun', $tahun);
            $this->db->where('tagihan.status_bayar', 'Paid');
            $pemasukan = $this->db->get()->result();

            foreach ($pemasukan as $p) {
                $total_pemasukan += $p->jumlah;
            }

            $results = array_merge($results, $pemasukan);
        }

        // Urutkan berdasarkan tanggal
        usort($results, function ($a, $b) {
            return strtotime($a->tanggal) - strtotime($b->tanggal);
        });

        return [
            'data' => $results,
            'total_pemasukan' => $total_pemasukan,
            'total_pengeluaran' => $total_pengeluaran
        ];
    }
}
