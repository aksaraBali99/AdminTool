<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Trans extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->load->model('m_tagihan');
        $this->load->model('m_reminder_tagihan');
        $this->load->model('m_reminder_belajar');
        $this->load->model('m_user');
        $this->load->library('excel');
        $this->apk = $this->m_masterdata->get_konfig(0);
        $this->load->library('datatables');

        if ($this->session->userdata('is_login') !== true) {
            redirect(site_url("Login"));
        }
    }

    public function tagihan()
    {
        $data = array('isi' => 'trans/tagihan');
        $this->load->view('layouts/wrapper', $data);
    }


    public function tagihan_page()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $status_input = $this->input->post('status');

        $list = $this->m_tagihan->get_datatables($bulan, $tahun, $status_input);
        $data = [];
        $no = isset($_POST['start']) ? intval($_POST['start']) + 1 : 1;

        foreach ($list as $t) {
            // Status badge based on new ENUM values
            switch ($t->status_bayar) {
                case 'Paid':
                    $status = '<span class="badge badge-success">Paid</span>';
                    break;
                case 'Late':
                    $status = '<span class="badge badge-warning">Late</span>';
                    break;
                case 'Refund':
                    $status = '<span class="badge badge-info">Refund</span>';
                    break;
                case 'Pending':
                default:
                    $status = '<span class="badge badge-danger">Pending</span>';
                    break;
            }

            $wa_web = 'https://wa.me/' . $t->no_wa;
            $row = [];
            $row[] = $no++;
            $row[] = $t->nama;
            $row[] = $t->no_wa;
            $row[] = date('F', mktime(0, 0, 0, $t->bulan, 10));
            $row[] = $t->tahun;
            $row[] = number_format($t->jumlah, 2, ',', '.');
            if (strpos($t->tipe, 'Biaya Kelas') !== false) {
                // Prorata column
                if (isset($t->is_prorata) && $t->is_prorata == '1') {
                    $jml = isset($t->jml_pertemuan) ? $t->jml_pertemuan : 0;
                    $row[] = '<span class="badge badge-warning">' . $jml . 'x Pertemuan</span>';
                } else {
                    $row[] = '<span class="badge badge-secondary">Full (8x)</span>';
                }
            } else {
                $row[] = '<span class="badge badge-info">-</span>';
            }

            $row[] = '<b><u>' . $t->tipe . '</u></b>';
            $row[] = $status;
            $row[] = $t->tgl_bayar ? date('d-m-Y H:i:s', strtotime($t->tgl_bayar)) : '-';

            // Action buttons based on status
            $actions = '';
            if ($t->status_bayar == 'Pending' || $t->status_bayar == 'Late') {
                $actions .= '<button type="button" id="' . $t->id_tagihan . '" nama="' . $t->nama . '" nominal="' . $t->jumlah . '" class="btn btn-success btn-xs btn-bayar mx-1 my-1">Paid</button>';
                if ($t->status_bayar == 'Pending') {
                    $actions .= '<button type="button" id="' . $t->id_tagihan . '" nama="' . $t->nama . '" class="btn btn-warning btn-xs my-1 btn-late">Late</button>';
                }
                $actions .= '<button type="button" id="' . $t->id_tagihan . '" nama="' . $t->nama . '" class="btn btn-info btn-xs my-1 btn-refund mx-1">Refund</button>';
            } else {
                $actions .= '<a href="#" class="btn btn-secondary btn-xs disabled my-1">Paid</a>';
            }
            $actions .= '<a class="btn btn-link btn-primary" title="Print Invoice" href="' . site_url('Trans/print_invoice/' . $t->id_tagihan) . '" target="_blank"><i class="fa fa-print"></i></a>';
            $actions .= '<a class="btn btn-link btn-primary" title="Kirim Pesan Whatsapp" href="' . $wa_web . '" target="_blank"><i class="fa fa-comment-dots"></i></a>';
            $actions .= '<button type="button" class="btn btn-link btn-danger btn-hapus-tagihan" title="Hapus Tagihan" id="' . $t->id_tagihan . '" nama="' . $t->nama . '"><i class="fa fa-trash"></i></button>';

            $row[] = $actions;
            $row[] = $t->status_bayar;
            $data[] = $row;
        }

        $output = [
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->m_tagihan->count_all(),
            "recordsFiltered" => $this->m_tagihan->count_filtered($bulan, $tahun, $status_input),
            "data" => $data,
        ];

        echo json_encode($output);
    }

    public function bayar()
    {
        $i = $this->input;

        $data = array(
            'status_bayar' => 'Paid',
            'tgl_bayar' => Date('Y-m-d H:i:s'),
        );

        $this->db->where('id_tagihan', $i->post('id_tagihan'))->update('tagihan', $data);

        echo json_encode('success');
    }

    public function late()
    {
        $i = $this->input;

        $data = array(
            'status_bayar' => 'Late',
        );

        $this->db->where('id_tagihan', $i->post('id_tagihan'))->update('tagihan', $data);

        echo json_encode('success');
    }

    public function refund()
    {
        $i = $this->input;

        $data = array(
            'status_bayar' => 'Refund',
            'tgl_bayar' => Date('Y-m-d H:i:s'),
        );

        $this->db->where('id_tagihan', $i->post('id_tagihan'))->update('tagihan', $data);

        echo json_encode('success');
    }

    // Keep ditagih for backward compatibility (alias to late)
    public function ditagih()
    {
        $this->late();
    }

    public function reminder_tagihan()
    {
        $data = array('isi' => 'log/reminder_tagihan');
        $this->load->view('layouts/wrapper', $data);
    }

    public function reminder_tagihan_page()
    {
        $request = $_POST;
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $status = $this->input->post('status');  // Jika ada status untuk filter tambahan

        $list = $this->m_reminder_tagihan->get_datatables($bulan, $tahun, $status);
        $data = [];
        $no = 1;
        foreach ($list as $log) {
            $row = [];
            $row[] = $no++;  // Nomor urut
            $row[] = $log->nama;  // Nama peserta
            $row[] = $log->no_hp;  // Nomor HP
            $row[] = date('F', mktime(0, 0, 0, $log->bulan, 10));  // Nama bulan
            $row[] = $log->tahun;  // Tahun
            $row[] = '<span title="' . $log->pesan . '">' . mb_strimwidth($log->pesan, 0, 40, "...") . '</span>';
            $row[] = $log->response == '1' ? '<center><span class="badge badge-success">Sukses</span></center>' : '<center><span class="badge badge-danger">Gagal</span></center>';  // Response dalam tag code
            $row[] = $log->keterangan;  // Keterangan
            $row[] = date('d-m-Y H:i:s', strtotime($log->waktu_kirim));  // Waktu kirim

            $data[] = $row;
        }

        $output = [
            "draw" => intval($request['draw']),
            "recordsTotal" => $this->m_reminder_tagihan->count_all($bulan, $tahun, $status),
            "recordsFiltered" => $this->m_reminder_tagihan->count_filtered($bulan, $tahun, $status),
            "data" => $data,
        ];

        echo json_encode($output);
    }

    public function reminder_belajar()
    {
        $data = array('isi' => 'log/reminder_belajar');
        $this->load->view('layouts/wrapper', $data);
    }

    public function reminder_belajar_page()
    {
        $request = $_POST;
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $status = $this->input->post('status');  // Jika ada status untuk filter tambahan

        $list = $this->m_reminder_belajar->get_datatables($bulan, $tahun, $status);
        $data = [];
        $no = 1;
        foreach ($list as $log) {
            $row = [];
            $row[] = $no++;  // Nomor urut
            $row[] = $log->nama;  // Nama peserta
            $row[] = $log->no_hp;  // Nomor HP
            $row[] = date('F', mktime(0, 0, 0, $log->bulan, 10));  // Nama bulan
            $row[] = $log->tahun;  // Tahun
            $row[] = '<span title="' . $log->pesan . '">' . mb_strimwidth($log->pesan, 0, 40, "...") . '</span>';
            $row[] = $log->response == '1' ? '<center><span class="badge badge-success">Sukses</span></center>' : '<center><span class="badge badge-danger">Gagal</span></center>';  // Response dalam tag code
            $row[] = date('d-m-Y H:i:s', strtotime($log->waktu_kirim));  // Waktu kirim
            $row[] = $log->keterangan;  // Keterangan

            $data[] = $row;
        }

        $output = [
            "draw" => intval($request['draw']),
            "recordsTotal" => $this->m_reminder_belajar->count_all($bulan, $tahun, $status),
            "recordsFiltered" => $this->m_reminder_belajar->count_filtered($bulan, $tahun, $status),
            "data" => $data,
        ];

        echo json_encode($output);
    }

    public function print_invoice($id_tagihan)
    {
        // Get tagihan details
        $this->db->select('t.*, p.nama_anak, p.nama_ortu, p.no_hp, p.alamat_ortu, p.email, jk.nama_kelas');
        $this->db->from('tagihan t');
        $this->db->join('peserta p', 'p.id_peserta = t.id_peserta');
        $this->db->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left');
        $this->db->where('t.id_tagihan', $id_tagihan);
        $tagihan = $this->db->get()->row();

        if (!$tagihan) {
            show_error('Invoice tidak ditemukan');
            return;
        }

        // Get tagihan items detail
        $this->db->where('id_tagihan', $id_tagihan);
        $items = $this->db->get('tagihan_detail')->result();

        // Default company settings
        $apk = (object)[
            'nama_instansi' => 'English Hub',
            'alamat' => '',
            'no_telp' => ''
        ];

        $data = [
            'tagihan' => $tagihan,
            'items' => $items,
            'apk' => $apk
        ];

        $this->load->view('trans/print_invoice', $data);
    }

    public function hapus_tagihan()
    {
        $id = $this->input->post('id_tagihan');
        $this->db->trans_start();
        $this->db->where('id_tagihan', $id)->delete('tagihan_detail');
        $this->db->where('id_tagihan', $id)->delete('tagihan');
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus tagihan']);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Tagihan berhasil dihapus']);
        }
    }
}
