<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{

    // Biar hanya bisa diakses dari CLI
    public function __construct()
    {
        parent::__construct();
        // if (!$this->input->is_cli_request()) {
        //     exit('Akses hanya melalui CLI');
        // }
        $this->load->helper('custom_helper');
        $this->load->model('m_masterdata');
        $this->load->model('m_tagihan');
        $this->apk = $this->m_masterdata->get_konfig(0);
    }

    public function index()
    {
        $bulan = date('m');
        $tahun = date('Y');

        $peserta_aktif = $this->m_masterdata->get_peserta_aktif();

        foreach ($peserta_aktif as $row) {

            $invoice_data = array(
                'id_peserta' => $row->id_peserta,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'jumlah' => $row->biaya,
                'status_bayar' => 0,
            );

            $this->db->insert('tagihan', $invoice_data);

            $send = $this->kirim_reminder_tagihan($row, $bulan, $tahun);
        }
    }

    public function kirim_reminder_tagihan($data, $bulan, $tahun)
    {
        // Persiapkan teks yang akan dikirim ke WhatsApp 
        $text = '';
        $text .= "*----Remainder Tagihan Pembayaran " . $this->apk[0]->nama_apk . "----* \n";
        $text .= "Nama Siswa: " . $data->nama . " \n";
        $text .= "Kelas: " . $data->nama_kelas . " \n";
        $text .= "Bulan: " . date('F', mktime(0, 0, 0, $bulan, 1)) . " \n";
        $text .= "Tahun: " . $tahun . " \n";
        $text .= "Biaya: " . rupiah($data->biaya) . " \n";
        $text .= "----------------------------------------- \n";
        $text .= "Terimakasih, telah menjadikan Kelas kami sebagai media pembelajaran.";

        // Nomor WhatsApp siswa
        $notelp = $data->no_wa;

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


    // public function index_belajar()
    // {
    //     $bulan = date('m');
    //     $tahun = date('Y');

    //     $peserta_ada_jadwal = $this->m_masterdata->get_peserta_aktif_dengan_jadwal_dan_pembayaran();
    //     foreach ($peserta_ada_jadwal as $row) {
    //         $send = $this->kirim_reminder_belajar($row, $bulan, $tahun);
    //     }
    // }

    // public function kirim_reminder_belajar($data, $bulan, $tahun)
    // {

    //     $hari_map = [
    //         '1'    => 'Senin',
    //         '2'   => 'Selasa',
    //         '3' => 'Rabu',
    //         '4'  => 'Kamis',
    //         '5'    => 'Jumat',
    //         '6'  => 'Sabtu',
    //         '7'    => 'Minggu',
    //     ];

    //     // Persiapkan teks yang akan dikirim ke WhatsApp 
    //     $text = '';
    //     $text .= "*----Remainder Jadwal " . $this->apk[0]->nama_apk . "----* \n";
    //     $text .= "Nama Siswa: " . $data->nama . " \n";
    //     $text .= "Kelas: " . $data->nama_kelas . " \n";
    //     $text .= "Pengajar: " . $data->nm_pengajar . " \n";
    //     $text .= "Hari: " . $hari_map[$data->hari] . " \n";
    //     $text .= "Jam Mulai: " . $data->jam_mulai . '-' . $data->jam_selesai . " \n";
    //     $text .= "----------------------------------------- \n";
    //     $text .= "Harap konfirmasi jika ada kendala terkait jadwal kelas kepada kami, Terimakasih.";

    //     // Nomor WhatsApp siswa
    //     $notelp = $data->no_wa;

    //     // Kirimkan pesan WhatsApp
    //     $send_wa = sendWa($notelp, $text, $this->apk[0]->token_wa);
    //     $resp = json_decode($send_wa, true);

    //     //Log WA  
    //     $data_pengiriman = array(
    //         'id_peserta' => $data->id_peserta,
    //         'no_hp' => $notelp,
    //         'tahun' => $tahun,
    //         'bulan' => $bulan,
    //         'pesan' => $text,
    //         'response' => $resp['status'],
    //         'keterangan' => $resp['status'] == '1' ? 'Sukses' : $resp['reason']
    //     );

    //     $this->db->insert('reminder_belajar', $data_pengiriman);
    // }
}
