<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Peserta extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->load->model('m_tagihan');
        $this->load->model('m_user');
        $this->load->library('excel');
        $this->apk = $this->m_masterdata->get_konfig(0);

        if ($this->session->userdata('is_login') !== true) {
            redirect(site_url("Login"));
        }
    }

    public function peserta()
    {
        $data = array('isi' => 'peserta/index');
        $data['jenis_kelas'] = $this->m_masterdata->get_allKelas();
        $data['all_jadwal'] = $this->m_masterdata->get_allJadwal();
        $this->load->view('layouts/wrapper', $data);
    }

    public function peserta_page()
    {
        // Get filter parameters
        $id_kelas = $this->input->post('id_kelas');
        $is_aktif = $this->input->post('is_aktif');

        $this->db->select("p.id_peserta, p.nama_ortu, p.no_hp, p.alamat_ortu,
            p.nama_anak, p.tgl_lahir_anak, p.alamat_anak, p.email,
            p.tgl_terakhir_dihubungi, p.src, p.status, p.catatan,
            p.jk, p.id_jenis_kelas, p.is_aktif, p.tgl_non_aktif, p.input_at,
            p.level_sekolah, p.nama_sekolah, p.status_siswa,
            djk.nama_kelas, djk.biaya, djk.biaya_regis, djk.biaya_buku,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    CASE jk.hari
                        WHEN '1' THEN 'Senin'
                        WHEN '2' THEN 'Selasa'
                        WHEN '3' THEN 'Rabu'
                        WHEN '4' THEN 'Kamis'
                        WHEN '5' THEN 'Jumat'
                        WHEN '6' THEN 'Sabtu'
                        WHEN '7' THEN 'Minggu'
                        ELSE 'Tidak Diketahui'
                    END, 
                    ', ', jk.jam_mulai, '-', jk.jam_selesai
                )
                SEPARATOR '@'
            ) as jadwal")
            ->from('peserta p')
            ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = p.id_jenis_kelas', 'left')
            ->join('jadwal_kelas jk', 'jk.id_kelas = p.id_jenis_kelas AND jk.is_aktif = 1', 'left')
            ->where('status =', 'Registrasi Kelas')
            ->group_start()
            ->where('p.jenis_siswa', 'regular')
            ->or_where('p.jenis_siswa IS NULL')
            ->group_end();

        // Apply filters
        if (!empty($id_kelas)) {
            $this->db->where('p.id_jenis_kelas', $id_kelas);
        }
        if ($is_aktif !== '' && $is_aktif !== null) {
            $this->db->where('p.status_siswa', $is_aktif);
        }

        $list = $this->db->group_by('p.id_peserta')
            ->order_by('p.id_peserta', 'DESC')
            ->get()
            ->result();

        $data = array();
        $this->load->model('m_siswa');

        foreach ($list as $row) {
            // Get level siswa aktif
            $level_aktif = $this->m_siswa->get_level_aktif_siswa($row->id_peserta);
            $level_nama = $level_aktif ? $level_aktif->nama_level : '-';

            // Calculate age
            $umur = $row->tgl_lahir_anak ? getAge($row->tgl_lahir_anak) . ' thn' : '-';

            // Status siswa badge (from status_siswa field)
            $status_siswa = $row->status_siswa ?: 'Aktif';
            $badge_class = ($status_siswa == 'Aktif') ? 'badge-success' : (($status_siswa == 'Cuti') ? 'badge-warning' : 'badge-danger');
            $status_badge = '<span class="badge ' . $badge_class . '">' . $status_siswa . '</span>';

            $data[] = array(
                $row->id_peserta,           // 0
                $row->nama_anak,            // 1
                $umur,                      // 2
                $row->level_sekolah ?: '-', // 3
                $row->nama_sekolah ?: '-',  // 4
                '<span class="badge badge-info">' . $level_nama . '</span>', // 5
                $status_badge,              // 6 - status field
                $row->no_hp,                // 7 (for WhatsApp link)
                $row->is_aktif,             // 8 (for button logic)
                $row->biaya ?: 0,           // 9 (biaya for prorata)
                $row->biaya_regis ?: 0,     // 10
                $row->biaya_buku ?: 0,      // 11
            );
        }

        echo json_encode(array("data" => $data));
    }

    // Partnership siswa
    public function partnership()
    {
        $data = array('isi' => 'peserta/partnership');
        $data['jenis_kelas'] = $this->m_masterdata->get_allKelas();
        $data['all_jadwal'] = $this->m_masterdata->get_allJadwal();
        $this->load->view('layouts/wrapper', $data);
    }

    public function partnership_page()
    {
        // Get filter parameters
        $id_kelas = $this->input->post('id_kelas');
        $is_aktif = $this->input->post('is_aktif');

        $this->db->select("p.id_peserta, p.nama_ortu, p.no_hp, p.alamat_ortu,
            p.nama_anak, p.tgl_lahir_anak, p.alamat_anak, p.email,
            p.tgl_terakhir_dihubungi, p.src, p.status, p.catatan,
            p.jk, p.id_jenis_kelas, p.is_aktif, p.tgl_non_aktif, p.input_at,
            p.level_sekolah, p.nama_sekolah, p.status_siswa,
            djk.nama_kelas, djk.biaya, djk.biaya_regis, djk.biaya_buku,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    CASE jk.hari
                        WHEN '1' THEN 'Senin'
                        WHEN '2' THEN 'Selasa'
                        WHEN '3' THEN 'Rabu'
                        WHEN '4' THEN 'Kamis'
                        WHEN '5' THEN 'Jumat'
                        WHEN '6' THEN 'Sabtu'
                        WHEN '7' THEN 'Minggu'
                        ELSE 'Tidak Diketahui'
                    END, 
                    ', ', jk.jam_mulai, '-', jk.jam_selesai
                )
                SEPARATOR '@'
            ) as jadwal")
            ->from('peserta p')
            ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = p.id_jenis_kelas', 'left')
            ->join('jadwal_kelas jk', 'jk.id_kelas = p.id_jenis_kelas AND jk.is_aktif = 1', 'left')
            ->where('status =', 'Registrasi Kelas')
            ->where('p.jenis_siswa', 'partnership');

        // Apply filters
        if (!empty($id_kelas)) {
            $this->db->where('p.id_jenis_kelas', $id_kelas);
        }
        if ($is_aktif !== '' && $is_aktif !== null) {
            $this->db->where('p.is_aktif', $is_aktif);
        }

        $list = $this->db->group_by('p.id_peserta')
            ->order_by('p.id_peserta', 'DESC')
            ->get()
            ->result();

        $data = array();
        $this->load->model('m_siswa');

        foreach ($list as $row) {
            // Get level siswa aktif
            $level_aktif = $this->m_siswa->get_level_aktif_siswa($row->id_peserta);
            $level_nama = $level_aktif ? $level_aktif->nama_level : '-';

            // Calculate age
            $umur = $row->tgl_lahir_anak ? getAge($row->tgl_lahir_anak) . ' thn' : '-';

            // Status siswa badge
            $status_siswa = $row->status_siswa ?: 'Aktif';
            $badge_class = ($status_siswa == 'Aktif') ? 'badge-success' : (($status_siswa == 'Cuti') ? 'badge-warning' : 'badge-danger');
            $status_badge = '<span class="badge ' . $badge_class . '">' . $status_siswa . '</span>';

            $data[] = array(
                $row->id_peserta,           // 0
                $row->nama_anak,            // 1
                $umur,                      // 2
                $row->level_sekolah ?: '-', // 3
                $row->nama_sekolah ?: '-',  // 4
                '<span class="badge badge-info">' . $level_nama . '</span>', // 5
                $status_badge,              // 6 - status field
                $row->no_hp,                // 7 (for WhatsApp link)
                $row->is_aktif,             // 8 (for button logic)
                $row->biaya ?: 0,           // 9 (biaya for prorata)
                $row->biaya_regis ?: 0,     // 10
                $row->biaya_buku ?: 0,      // 11
            );
        }

        echo json_encode(array("data" => $data));
    }


    public function add_peserta()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');

        $data = array(
            'nama_ortu' => $i->post('nama_ortu'),
            'no_hp' => $i->post('no_hp'),
            'alamat_ortu' => $i->post('alamat_ortu'),
            'nama_anak' => $i->post('nama_anak'),
            'tgl_lahir_anak' => $i->post('tgl_lahir_anak') ? $i->post('tgl_lahir_anak') : null,
            'alamat_anak' => $i->post('alamat_anak') ?: '',
            'email' => $i->post('email'),
            'tgl_terakhir_dihubungi' => $i->post('tgl_terakhir_dihubungi') ? $i->post('tgl_terakhir_dihubungi') : null,
            'src' => $i->post('src'),
            'status' => $i->post('status'),
            'catatan' => $i->post('catatan'),
            'jk' => $i->post('jk'),
            'level_sekolah' => $i->post('level_sekolah') ? $i->post('level_sekolah') : null,
            'nama_sekolah' => $i->post('nama_sekolah'),
            'id_jenis_kelas' => $i->post('id_jenis_kelas'),
            'id_referral' => $i->post('id_referral') ?: null,
            'referral_name' => $i->post('referral_name') ?: null,
            'jenis_siswa' => $i->post('jenis_siswa') ?: 'regular'
        );

        $id_jadwal_kelas = $i->post('id_jadwal_kelas');

        if ($tipe_form == "add") {
            $this->db->insert('peserta', $data);
            $id = $this->db->insert_id();

            $bulan = date('m');
            $tahun = date('Y');

            $kelas = $this->m_masterdata->get_jenis_kelas($i->post('id_jenis_kelas'));

            $invoice_data = array(
                'id_peserta' => $id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'jumlah' => $kelas[0]->biaya,
                'status_bayar' => 'Pending',
                'tgl_bayar' => Date('Y-m-d H:i:s'),
            );

            $this->db->insert('tagihan', $invoice_data);
        } else {
            $id = $i->post('id_peserta');
            $this->db->where('id_peserta', $id)->update('peserta', $data);
            $this->db->delete('peserta_jadwal', ['id_peserta' => $id]);
        }

        // Simpan jadwal
        if ($id_jadwal_kelas && is_array($id_jadwal_kelas)) {
            foreach ($id_jadwal_kelas as $index => $r) {
                if (!empty($r)) {
                    $this->db->insert('peserta_jadwal', array(
                        'id_peserta' => $id,
                        'id_jadwal_kelas' => $r,
                    ));
                }
            }
        }

        echo json_encode('sukses');
    }


    public function get_peserta()
    {
        $id = $this->input->post('id_peserta');
        $peserta = $this->m_masterdata->get_peserta($id); // detail
        $jadwal = $this->m_masterdata->get_peserta_jadwal($id); // array

        echo json_encode([
            'peserta' => $peserta,
            'jadwal' => $jadwal
        ]);
    }

    public function hapus_peserta()
    {
        $id = $this->input->post('id_peserta');
        $hapus = $this->db->delete('peserta', ['id_peserta' => $id]);
        if ($hapus) {
            echo json_encode('success');
        }
    }

    public function set_status()
    {
        $i = $this->input;

        $data = array(
            'is_aktif' => $i->post('aktif'),
            'tgl_non_aktif' => Date('Y-m-d'),
        );

        $this->db->where('id_peserta', $i->post('id_peserta'))->update('peserta', $data);

        //hapus tagihan yang belum dibayar
        $hapus_tagihan_belum_bayar = $this->m_tagihan->hapus_tagihan_belum_bayar_by_id_peserta($i->post('id_peserta'));

        if ($hapus_tagihan_belum_bayar) {
            echo json_encode('success');
        }
    }


    public function kirim_invoice()
    {
        $bulan = $this->input->post('bulan') ?: date('m');
        $tahun = $this->input->post('tahun') ?: date('Y');

        $id = $this->input->post('id_peserta');
        $items = $this->input->post('items'); // Array of items
        $metode_pembayaran = $this->input->post('metode_pembayaran'); // Metode pembayaran
        $is_prorata = $this->input->post('is_prorata') ?: '0';
        $jml_pertemuan = $this->input->post('jml_pertemuan') ?: 8;

        if (empty($items) || !is_array($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada item tagihan']);
            return;
        }

        $peserta_aktif = $this->m_masterdata->get_peserta_aktif_by_id($id);
        if (empty($peserta_aktif)) {
            echo json_encode(['status' => 'error', 'message' => 'Data peserta tidak ditemukan']);
            return;
        }

        $p = $peserta_aktif[0];
        $total_jumlah = 0;
        $total_diskon = 0;
        $tipe_labels = [];
        $wa_breakdown = ""; // Detail breakdown for WA
        $valid_items = [];

        // Process each item
        foreach ($items as $item) {
            $nama_siswa = trim($item['nama_siswa'] ?? '');
            $tipe_biaya = trim($item['tipe_biaya'] ?? '');
            $keterangan = trim($item['keterangan'] ?? '');
            $nilai_biaya = floatval($item['nilai_biaya'] ?? 0);
            $tipe_diskon = trim($item['tipe_diskon'] ?? '');
            $nilai_diskon = floatval($item['nilai_diskon'] ?? 0);
            $subtotal = floatval($item['subtotal'] ?? ($nilai_biaya - $nilai_diskon));

            if (empty($tipe_biaya) || $nilai_biaya <= 0) {
                continue; // Skip invalid items
            }

            // Hitung subtotal jika belum dihitung
            if ($subtotal <= 0) {
                $subtotal = $nilai_biaya - $nilai_diskon;
            }

            $total_jumlah += $subtotal; // Total setelah diskon
            $total_diskon += $nilai_diskon;
            $tipe_labels[] = $tipe_biaya;

            // Build WA breakdown
            $ket_text = $keterangan ? " ($keterangan)" : "";
            $diskon_text = "";
            if ($nilai_diskon > 0 && $tipe_diskon) {
                $diskon_text = " [" . $tipe_diskon . ": -" . rupiah($nilai_diskon) . "]";
            }
            $wa_breakdown .= "   - " . $tipe_biaya . $ket_text . ": " . rupiah($nilai_biaya) . $diskon_text . "\n";

            $valid_items[] = [
                'nama_siswa' => $nama_siswa ?: $p->nama_anak,
                'tipe_biaya' => $tipe_biaya,
                'keterangan' => $keterangan,
                'nilai_biaya' => $nilai_biaya,
                'tipe_diskon' => $tipe_diskon,
                'nilai_diskon' => $nilai_diskon,
                'subtotal' => $subtotal
            ];
        }

        if (empty($valid_items)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada item tagihan yang valid']);
            return;
        }

        // Add total diskon to WA breakdown if any
        if ($total_diskon > 0) {
            $wa_breakdown .= "   Total Diskon: -" . rupiah($total_diskon) . "\n";
        }

        // Remove duplicates from tipe_labels for display
        $tipe_labels_unique = array_unique($tipe_labels);

        // Insert into tagihan table (parent)
        $invoice_data = array(
            'id_peserta' => $p->id_peserta,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jumlah' => $total_jumlah, // Total setelah diskon
            'tipe' => implode(', ', $tipe_labels_unique),
            'status_bayar' => 'Pending',
            'is_prorata' => $is_prorata,
            'jml_pertemuan' => $jml_pertemuan,
            'metode_pembayaran' => $metode_pembayaran // Simpan metode pembayaran
        );

        $this->db->insert('tagihan', $invoice_data);
        $id_tagihan = $this->db->insert_id();

        // Insert into tagihan_detail table (items)
        foreach ($valid_items as $item) {
            $detail_data = array(
                'id_tagihan' => $id_tagihan,
                'id_peserta' => $p->id_peserta,
                'nama_siswa' => $item['nama_siswa'],
                'tipe_biaya' => $item['tipe_biaya'],
                'keterangan' => $item['keterangan'],
                'nilai_biaya' => $item['nilai_biaya'],
                'tipe_diskon' => $item['tipe_diskon'],
                'nilai_diskon' => $item['nilai_diskon'],
                'subtotal' => $item['subtotal']
            );
            $this->db->insert('tagihan_detail', $detail_data);
        }

        // Update WhatsApp log with combined info and breakdown
        $this->kirim_reminder_tagihan($p, $bulan, $tahun, $invoice_data, $wa_breakdown);

        echo json_encode('success');
    }

    public function kirim_reminder_tagihan($data, $bulan, $tahun, $invoice_data, $breakdown = "")
    {
        // Persiapkan teks yang akan dikirim ke WhatsApp 
        $text = '';
        $text .= "*----Remainder Tagihan Pembayaran " . $this->apk[0]->nama_apk . "----* \n\n";
        $text .= "Nama Siswa: " . $data->nama_anak . " \n";
        $text .= "Periode: " . date('F', mktime(0, 0, 0, $bulan, 10)) . " " . $tahun . " \n";
        
        if ($breakdown) {
            $text .= "Detail Tagihan: \n" . $breakdown;
        } else {
            $text .= "Detail Tagihan: " . $invoice_data['tipe'] . " \n";
        }
        
        $text .= "Total Biaya: " . rupiah($invoice_data['jumlah']) . " \n";
        $text .= "----------------------------------------- \n";
        $text .= "Terimakasih, telah menjadikan Kelas kami sebagai media pembelajaran.";

        // Nomor WhatsApp siswa
        $notelp = $data->no_hp;

        // Kirimkan pesan WhatsApp 
        $send_wa = sendWa($notelp, $text, $this->apk[0]->token_wa);
        $resp = json_decode($send_wa, true);

        //Log WA  
        $data_pengiriman = array(
            'id_peserta' => $data->id_peserta,
            'no_hp' => $notelp,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'pesan' => $text,
            'response' => $resp['status'],
            'keterangan' => $resp['status'] == '1' ? 'Sukses' : $resp['reason']
        );

        $this->db->insert('reminder_tagihan', $data_pengiriman);
    }
}
