<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pengeluaran extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->load->model('m_pengeluaran');
        $this->load->library('excel');
        $this->apk = $this->m_masterdata->get_konfig(0);
        $this->load->library('datatables');

        if ($this->session->userdata('is_login') !== true  ) {
            redirect(site_url("Login"));
        }
    }

    public function index()
    {
        $data = array('isi' => 'trans/pengeluaran');
        $data['bulan'] = date('m');
        $data['tahun'] = date('Y');
        $this->load->view('layouts/wrapper', $data);
    }

    public function pengeluaran_page()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        $list = $this->m_pengeluaran->get_datatables($bulan, $tahun);
        $data = [];
        $no = 1;
        foreach ($list as $t) {
            $row = [];
            $row[] = $no++;
            $row[] = $t->tanggal;
            $row[] = $t->kategori;
            $row[] = $t->keterangan;
            $row[] = number_format($t->jumlah, 2, ',', '.');
            $row[] = $t->id_pengeluaran;
            $data[] = $row;
        }

        $output = [
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->m_pengeluaran->count_all($bulan, $tahun),
            "recordsFiltered" => $this->m_pengeluaran->count_filtered($bulan, $tahun),
            "data" => $data,
        ];

        echo json_encode($output);
    }

    public function get_pengeluaran()
    {
        $id_pengeluaran = $this->input->post('id_pengeluaran');
        $data = $this->m_pengeluaran->get_pengeluaran($id_pengeluaran);
        echo json_encode($data);
    }

    public function add_pengeluaran()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');

        if ($tipe_form == "add") {
            $data = array(
                'tanggal' => $this->input->post('tanggal'),
                'kategori' => $this->input->post('kategori'),
                'keterangan' => $this->input->post('keterangan'),
                'jumlah' => $this->input->post('jumlah'),
            );
            $cek = $this->db->insert('pengeluaran', $data);
        } else {
            $data = array(
                'tanggal' => $this->input->post('tanggal'),
                'kategori' => $this->input->post('kategori'),
                'keterangan' => $this->input->post('keterangan'),
                'jumlah' => $this->input->post('jumlah'),
            );

            $this->m_pengeluaran->update_pengeluaran($this->input->post('id_pengeluaran'), $data);
        }


        echo json_encode(array("status" => "success"));
    }

    public function hapus_pengeluaran()
    {
        $id_pengeluaran = $this->input->post('id_pengeluaran');
        $hapus_data = $this->db->delete('pengeluaran', array('id_pengeluaran' => $id_pengeluaran));
        echo json_encode(array("status" => "success"));
    }
}
