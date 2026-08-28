<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_hr');
        $this->load->model('m_masterdata');
        $this->apk = $this->m_masterdata->get_konfig(0);

        if ($this->session->userdata('is_login') !== true ) {
            redirect(site_url("Login"));
        }
    }

    // =============================================
    // DATA KARYAWAN
    // =============================================
    
    public function karyawan()
    {
        $data = array('isi' => 'hr/karyawan');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function karyawan_page()
    {
        $filters = array(
            'status_karyawan' => $this->input->post('status_karyawan'),
            'jenis_karyawan' => $this->input->post('jenis_karyawan')
        );
        
        $list = $this->m_hr->get_all_karyawan($filters);
        $data = array();
        
        foreach ($list as $row) {
            $status_badge = '';
            switch ($row->status_karyawan) {
                case 'Aktif': $status_badge = '<span class="badge badge-success">Aktif</span>'; break;
                case 'Dipecat': $status_badge = '<span class="badge badge-danger">Dipecat</span>'; break;
                case 'Mengundurkan Diri': $status_badge = '<span class="badge badge-warning">Mengundurkan Diri</span>'; break;
            }
            
            $jenis_badge = '';
            switch ($row->jenis_karyawan) {
                case 'Full Time': $jenis_badge = '<span class="badge badge-primary">Full Time</span>'; break;
                case 'Part Time': $jenis_badge = '<span class="badge badge-info">Part Time</span>'; break;
                case 'Freelance': $jenis_badge = '<span class="badge badge-secondary">Freelance</span>'; break;
            }
            
            $data[] = array(
                $row->id_karyawan,
                $row->nama,
                $row->posisi ?: '-',
                $jenis_badge,
                $status_badge,
                date('d/m/Y', strtotime($row->tanggal_mulai_kerja)),
                $row->total_dokumen ?: 0,
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_karyawan()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        
        $data = array(
            'nama' => $i->post('nama'),
            'no_ktp' => $i->post('no_ktp'),
            'no_telp' => $i->post('no_telp'),
            'alamat' => $i->post('alamat'),
            'npwp' => $i->post('npwp'),
            'status_ktp' => $i->post('status_ktp'),
            'tanggal_mulai_kerja' => $i->post('tanggal_mulai_kerja'),
            'posisi' => $i->post('posisi'),
            'jenis_karyawan' => $i->post('jenis_karyawan'),
            'status_karyawan' => $i->post('status_karyawan'),
            'gaji_pokok' => $i->post('gaji_pokok') ?: 0,
            'tunjangan' => $i->post('tunjangan') ?: 0
        );
        
        if ($i->post('status_karyawan') != 'Aktif' && $i->post('tanggal_keluar')) {
            $data['tanggal_keluar'] = $i->post('tanggal_keluar');
        }
        
        if ($tipe_form == 'add') {
            $id = $this->m_hr->insert_karyawan($data);
            
            // Link to pengajar if selected
            if ($i->post('id_pengajar')) {
                $this->m_hr->link_pengajar_to_karyawan($i->post('id_pengajar'), $id);
            }
            
            // Audit log
            $this->m_hr->log('karyawan', $id, 'INSERT', null, $data, $this->session->userdata('id_user'));
            
            $result = $id > 0;
        } else {
            $id = $i->post('id_karyawan');
            $old_data = $this->m_hr->get_karyawan($id);
            $result = $this->m_hr->update_karyawan($id, $data);
            
            // Update pengajar link if changed
            if ($i->post('id_pengajar')) {
                $this->m_hr->link_pengajar_to_karyawan($i->post('id_pengajar'), $id);
            }
            
            // Audit log
            $this->m_hr->log('karyawan', $id, 'UPDATE', $old_data, $data, $this->session->userdata('id_user'));
        }
        
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function get_karyawan()
    {
        $id = $this->input->post('id_karyawan');
        $karyawan = $this->m_hr->get_karyawan($id);
        echo json_encode($karyawan);
    }
    
    public function hapus_karyawan()
    {
        $id = $this->input->post('id_karyawan');
        $old_data = $this->m_hr->get_karyawan($id);
        $result = $this->m_hr->delete_karyawan($id);
        
        $this->m_hr->log('karyawan', $id, 'DELETE', $old_data, null, $this->session->userdata('id_user'));
        
        echo json_encode($result ? 'sukses' : 'gagal');
    }

    // =============================================
    // DOKUMEN KARYAWAN
    // =============================================
    
    public function dokumen($id_karyawan)
    {
        $data = array('isi' => 'hr/dokumen_karyawan');
        $data['karyawan'] = $this->m_hr->get_karyawan($id_karyawan);
        $data['dokumen'] = $this->m_hr->get_dokumen($id_karyawan);
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function upload_dokumen()
    {
        $id_karyawan = $this->input->post('id_karyawan');
        $jenis_dokumen = $this->input->post('jenis_dokumen');
        
        // Create directory if not exists
        $upload_path = './uploads/karyawan/' . $id_karyawan . '/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'pdf|jpg|jpeg|png|doc|docx';
        $config['max_size'] = 5120; // 5MB
        $config['file_name'] = time() . '_' . $_FILES['file']['name'];
        
        $this->load->library('upload', $config);
        
        if ($this->upload->do_upload('file')) {
            $file_data = $this->upload->data();
            
            $data = array(
                'id_karyawan' => $id_karyawan,
                'jenis_dokumen' => $jenis_dokumen,
                'nama_file' => $file_data['orig_name'],
                'file_path' => 'uploads/karyawan/' . $id_karyawan . '/' . $file_data['file_name']
            );
            
            $this->m_hr->insert_dokumen($data);
            echo json_encode(['status' => 'success', 'message' => 'Dokumen berhasil diupload']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->upload->display_errors('', '')]);
        }
    }
    
    public function download_dokumen($id)
    {
        $dokumen = $this->m_hr->get_dokumen_by_id($id);
        if ($dokumen) {
            $this->load->helper('download');
            force_download('./' . $dokumen->file_path, NULL);
        } else {
            show_404();
        }
    }
    
    public function hapus_dokumen()
    {
        $id = $this->input->post('id');
        $dokumen = $this->m_hr->get_dokumen_by_id($id);
        
        if ($dokumen) {
            // Delete file
            if (file_exists('./' . $dokumen->file_path)) {
                unlink('./' . $dokumen->file_path);
            }
            $this->m_hr->delete_dokumen($id);
            echo json_encode('sukses');
        } else {
            echo json_encode('gagal');
        }
    }

    // =============================================
    // PAYROLL GURU
    // =============================================
    
    public function payroll_guru()
    {
        $data = array('isi' => 'hr/payroll_guru');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function payroll_guru_page()
    {
        $filters = array(
            'bulan' => $this->input->post('bulan') ?: date('n'),
            'tahun' => $this->input->post('tahun') ?: date('Y'),
            'id_guru' => $this->input->post('id_guru'),
            'status' => $this->input->post('status')
        );
        
        $list = $this->m_hr->get_payroll_guru($filters);
        $data = array();
        
        foreach ($list as $row) {
            $status_badge = '';
            switch ($row->status) {
                case 'draft': $status_badge = '<span class="badge badge-secondary">Draft</span>'; break;
                case 'approved': $status_badge = '<span class="badge badge-success">Approved</span>'; break;
                case 'paid': $status_badge = '<span class="badge badge-primary">Paid</span>'; break;
            }
            
            $data[] = array(
                $row->id,
                $row->nama_guru,
                number_format($row->total_jam_ajar_anak + $row->total_jam_ajar_dewasa, 1) . ' jam',
                'Rp ' . number_format($row->total_honor),
                $row->total_hari_hadir . ' hari',
                $row->total_kedatangan . ' kali',
                'Rp ' . number_format($row->total_transport),
                'Rp ' . number_format($row->pph21_nominal),
                'Rp ' . number_format($row->total_potongan ?: 0),
                'Rp ' . number_format($row->total_gaji_bersih),
                $status_badge,
                $row->status
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function generate_payroll_guru()
    {
        $id_guru = $this->input->post('id_guru');
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        
        // Check if already paid
        $existing = $this->m_hr->check_payroll_guru_exists($id_guru, $bulan, $tahun);
        if ($existing && $existing->status != 'draft') {
            echo json_encode(['status' => 'error', 'message' => 'Payroll sudah di-approve/bayar, tidak bisa di-regenerate']);
            return;
        }
        
        $result = $this->m_hr->generate_payroll_guru($id_guru, $bulan, $tahun);
        
        if ($result) {
            $this->m_hr->log('payroll_guru', $result, 'INSERT', null, ['generated'], $this->session->userdata('id_user'));
            echo json_encode(['status' => 'success', 'message' => 'Payroll berhasil di-generate', 'id' => $result]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal generate payroll atau tidak ada data absensi']);
        }
    }
    
    public function generate_all_payroll_guru()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        
        $pengajar = $this->m_masterdata->get_allPengajar();
        $success = 0;
        $failed = 0;
        
        foreach ($pengajar as $p) {
            $result = $this->m_hr->generate_payroll_guru($p->id_pengajar, $bulan, $tahun);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil: $success, Gagal/Tidak ada data: $failed"
        ]);
    }
    
    public function approve_payroll_guru()
    {
        $id = $this->input->post('id');
        $payroll = $this->m_hr->get_payroll_guru_by_id($id);
        
        if ($payroll->status != 'draft') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya payroll draft yang bisa di-approve']);
            return;
        }
        
        $result = $this->m_hr->approve_payroll_guru($id, $this->session->userdata('id_user'));
        $this->m_hr->log('payroll_guru', $id, 'APPROVE', ['status' => 'draft'], ['status' => 'approved'], $this->session->userdata('id_user'));
        
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error']);
    }
    
    public function pay_payroll_guru()
    {
        $id = $this->input->post('id');
        $payroll = $this->m_hr->get_payroll_guru_by_id($id);
        
        if ($payroll->status != 'approved') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya payroll yang sudah approved yang bisa dibayar']);
            return;
        }
        
        // Insert to pengeluaran
        $id_pengeluaran = $this->m_hr->insert_pengeluaran_from_payroll('guru', $payroll);
        
        // Update payroll status
        $result = $this->m_hr->pay_payroll_guru($id, $id_pengeluaran);
        $this->m_hr->log('payroll_guru', $id, 'PAID', ['status' => 'approved'], ['status' => 'paid', 'id_pengeluaran' => $id_pengeluaran], $this->session->userdata('id_user'));
        
        echo json_encode($result ? ['status' => 'success', 'message' => 'Payroll berhasil dibayar dan masuk ke pengeluaran'] : ['status' => 'error']);
    }
    
    public function payslip_guru($id)
    {
        $data['payroll'] = $this->m_hr->get_payroll_guru_by_id($id);
        $data['apk'] = $this->apk;
        
        // Get guru details
        $data['guru'] = $this->m_masterdata->get_pengajar($data['payroll']->id_guru);
        
        $this->load->library('pdf');
        $pdf = new Pdf();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        $p = $data['payroll'];
        $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'SLIP GAJI', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Periode: ' . $bulan_nama[$p->bulan] . ' ' . $p->tahun, 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Info Karyawan
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nama', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $p->nama_guru, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Posisi', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, 'Guru', 0, 1);
        
        $pdf->Ln(5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Penghasilan
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PENGHASILAN', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(100, 6, 'Honor Kelas Anak (' . $p->total_jam_ajar_anak . ' jam x Rp ' . number_format($p->tarif_per_jam_anak) . ')', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->total_honor_anak), 0, 1, 'R');
        
        $pdf->Cell(100, 6, 'Honor Kelas Dewasa (' . $p->total_jam_ajar_dewasa . ' jam x Rp ' . number_format($p->tarif_per_jam_dewasa) . ')', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->total_honor_dewasa), 0, 1, 'R');
        
        $pdf->Cell(100, 6, 'Transport (' . $p->total_hari_hadir . ' hari x Rp ' . number_format($p->biaya_transport_per_hari) . ')', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->total_transport), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(100, 8, 'SUBTOTAL', 0, 0);
        $pdf->Cell(0, 8, 'Rp ' . number_format($p->subtotal), 0, 1, 'R');
        
        $pdf->Ln(3);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Potongan
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'POTONGAN', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(100, 6, 'PPh21 (' . $p->pph21_persen . '%)', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->pph21_nominal), 0, 1, 'R');
        
        // Get itemized potongan
        $potongan_list = $this->m_hr->get_potongan('guru', $id);
        foreach ($potongan_list as $pot) {
            $pdf->Cell(100, 6, $pot->keterangan, 0, 0);
            $pdf->Cell(0, 6, 'Rp ' . number_format($pot->nominal), 0, 1, 'R');
        }
        
        // Show total if there are additional deductions
        if (count($potongan_list) > 0) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(100, 6, 'Subtotal Potongan Lainnya', 0, 0);
            $pdf->Cell(0, 6, 'Rp ' . number_format($p->total_potongan), 0, 1, 'R');
            $pdf->SetFont('helvetica', '', 10);
        }
        
        $pdf->Ln(5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Total
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(100, 10, 'GAJI BERSIH', 1, 0, 'L', true);
        $pdf->Cell(0, 10, 'Rp ' . number_format($p->total_gaji_bersih), 1, 1, 'R', true);
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Slip ini digenerate secara otomatis oleh sistem pada ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        
        $filename = 'Payslip_' . preg_replace('/[^A-Za-z0-9]/', '_', $p->nama_guru) . '_' . $p->bulan . '_' . $p->tahun . '.pdf';
        $pdf->Output($filename, 'D');
    }

    // =============================================
    // PAYROLL STAFF
    // =============================================
    
    public function payroll_staff()
    {
        $data = array('isi' => 'hr/payroll_staff');
        $data['karyawan'] = $this->m_hr->get_karyawan_aktif();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function payroll_staff_page()
    {
        $filters = array(
            'bulan' => $this->input->post('bulan') ?: date('n'),
            'tahun' => $this->input->post('tahun') ?: date('Y'),
            'id_karyawan' => $this->input->post('id_karyawan'),
            'status' => $this->input->post('status')
        );
        
        $list = $this->m_hr->get_payroll_staff($filters);
        $data = array();
        
        foreach ($list as $row) {
            $status_badge = '';
            switch ($row->status) {
                case 'draft': $status_badge = '<span class="badge badge-secondary">Draft</span>'; break;
                case 'approved': $status_badge = '<span class="badge badge-success">Approved</span>'; break;
                case 'paid': $status_badge = '<span class="badge badge-primary">Paid</span>'; break;
            }
            
            $data[] = array(
                $row->id,
                $row->nama_karyawan,
                $row->posisi ?: '-',
                'Rp ' . number_format($row->gaji_pokok),
                'Rp ' . number_format($row->tunjangan),
                'Rp ' . number_format($row->potongan),
                'Rp ' . number_format($row->total_potongan ?: 0),
                'Rp ' . number_format($row->pph21_nominal),
                'Rp ' . number_format($row->total_gaji_bersih),
                $status_badge,
                $row->status
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_payroll_staff()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        
        $gaji_pokok = floatval($i->post('gaji_pokok'));
        $tunjangan = floatval($i->post('tunjangan'));
        $potongan = floatval($i->post('potongan'));
        $subtotal = $gaji_pokok + $tunjangan - $potongan;
        
        // Calculate PPh21
        $pph_result = $this->m_hr->calculate_pph21($subtotal * 12);
        $pph21_persen = $pph_result['persen'];
        $pph21_nominal = ($subtotal * $pph_result['persen']) / 100;
        $gaji_bersih = $subtotal - $pph21_nominal;
        
        $data = array(
            'id_karyawan' => $i->post('id_karyawan'),
            'bulan' => $i->post('bulan'),
            'tahun' => $i->post('tahun'),
            'gaji_pokok' => $gaji_pokok,
            'tunjangan' => $tunjangan,
            'potongan' => $potongan,
            'subtotal' => $subtotal,
            'pph21_persen' => $pph21_persen,
            'pph21_nominal' => $pph21_nominal,
            'total_gaji_bersih' => $gaji_bersih,
            'keterangan' => $i->post('keterangan'),
            'status' => 'draft'
        );
        
        if ($tipe_form == 'add') {
            // Check duplicate
            $existing = $this->m_hr->check_payroll_staff_exists($i->post('id_karyawan'), $i->post('bulan'), $i->post('tahun'));
            if ($existing) {
                echo json_encode(['status' => 'error', 'message' => 'Payroll untuk karyawan ini di bulan tersebut sudah ada']);
                return;
            }
            
            $result = $this->m_hr->insert_payroll_staff($data);
        } else {
            $id = $i->post('id');
            $result = $this->m_hr->update_payroll_staff($id, $data);
        }
        
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error']);
    }
    
    public function get_payroll_staff()
    {
        $id = $this->input->post('id');
        $payroll = $this->m_hr->get_payroll_staff_by_id($id);
        echo json_encode($payroll);
    }
    
    public function approve_payroll_staff()
    {
        $id = $this->input->post('id');
        $result = $this->m_hr->approve_payroll_staff($id, $this->session->userdata('id_user'));
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error']);
    }
    
    public function pay_payroll_staff()
    {
        $id = $this->input->post('id');
        $payroll = $this->m_hr->get_payroll_staff_by_id($id);
        
        if ($payroll->status != 'approved') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya payroll yang sudah approved yang bisa dibayar']);
            return;
        }
        
        $id_pengeluaran = $this->m_hr->insert_pengeluaran_from_payroll('staff', $payroll);
        $result = $this->m_hr->pay_payroll_staff($id, $id_pengeluaran);
        
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error']);
    }
    
    public function payslip_staff($id)
    {
        $data['payroll'] = $this->m_hr->get_payroll_staff_by_id($id);
        $data['apk'] = $this->apk;
        
        $this->load->library('pdf');
        $pdf = new Pdf();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        $p = $data['payroll'];
        $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'SLIP GAJI', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Periode: ' . $bulan_nama[$p->bulan] . ' ' . $p->tahun, 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nama', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $p->nama_karyawan, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Posisi', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $p->posisi ?: '-', 0, 1);
        
        if ($p->no_ktp) {
            $pdf->Cell(40, 6, 'NIK', 0, 0);
            $pdf->Cell(5, 6, ':', 0, 0);
            $pdf->Cell(0, 6, $p->no_ktp, 0, 1);
        }
        
        $pdf->Ln(5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Penghasilan
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PENGHASILAN', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(100, 6, 'Gaji Pokok', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->gaji_pokok), 0, 1, 'R');
        
        $pdf->Cell(100, 6, 'Tunjangan', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->tunjangan), 0, 1, 'R');
        
        $pdf->Ln(3);
        
        // Potongan
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'POTONGAN', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(100, 6, 'Potongan Tetap', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->potongan), 0, 1, 'R');
        
        $pdf->Cell(100, 6, 'PPh21 (' . $p->pph21_persen . '%)', 0, 0);
        $pdf->Cell(0, 6, 'Rp ' . number_format($p->pph21_nominal), 0, 1, 'R');
        
        // Get itemized potongan tambahan
        $potongan_list = $this->m_hr->get_potongan('staff', $id);
        foreach ($potongan_list as $pot) {
            $pdf->Cell(100, 6, $pot->keterangan, 0, 0);
            $pdf->Cell(0, 6, 'Rp ' . number_format($pot->nominal), 0, 1, 'R');
        }
        
        // Show subtotal if there are additional deductions
        if (count($potongan_list) > 0) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(100, 6, 'Subtotal Potongan Tambahan', 0, 0);
            $pdf->Cell(0, 6, 'Rp ' . number_format($p->total_potongan), 0, 1, 'R');
            $pdf->SetFont('helvetica', '', 10);
        }
        
        $pdf->Ln(5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        // Total
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(100, 10, 'GAJI BERSIH', 1, 0, 'L', true);
        $pdf->Cell(0, 10, 'Rp ' . number_format($p->total_gaji_bersih), 1, 1, 'R', true);
        
        $filename = 'Payslip_' . preg_replace('/[^A-Za-z0-9]/', '_', $p->nama_karyawan) . '_' . $p->bulan . '_' . $p->tahun . '.pdf';
        $pdf->Output($filename, 'D');
    }

    // =============================================
    // PPH21 KOMPONEN
    // =============================================
    
    public function pph21_komponen()
    {
        $data = array('isi' => 'hr/pph21_komponen');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function pph21_komponen_page()
    {
        $list = $this->m_hr->get_pph21_komponen();
        $data = array();
        
        foreach ($list as $row) {
            $data[] = array(
                $row->id,
                $row->nama_komponen,
                'Rp ' . number_format($row->batas_bawah),
                'Rp ' . number_format($row->batas_atas),
                $row->persentase . '%',
                '<span class="badge badge-' . ($row->status == 'aktif' ? 'success' : 'secondary') . '">' . $row->status . '</span>',
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_pph21()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        
        $data = array(
            'nama_komponen' => $i->post('nama_komponen'),
            'batas_bawah' => $i->post('batas_bawah'),
            'batas_atas' => $i->post('batas_atas'),
            'persentase' => $i->post('persentase'),
            'status' => $i->post('status')
        );
        
        if ($tipe_form == 'add') {
            $result = $this->m_hr->insert_pph21($data);
        } else {
            $result = $this->m_hr->update_pph21($i->post('id'), $data);
        }
        
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function get_pph21()
    {
        $id = $this->input->post('id');
        $pph21 = $this->m_hr->get_pph21_by_id($id);
        echo json_encode($pph21);
    }

    // =============================================
    // LAPORAN PAYROLL
    // =============================================
    
    public function laporan_payroll()
    {
        $data = array('isi' => 'hr/laporan_payroll');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function generate_laporan()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        
        $data = $this->m_hr->get_laporan_payroll($bulan, $tahun);
        echo json_encode($data);
    }
    
    public function export_laporan_pdf()
    {
        $bulan = $this->input->get('bulan');
        $tahun = $this->input->get('tahun');
        
        $data = $this->m_hr->get_laporan_payroll($bulan, $tahun);
        
        $this->load->library('pdf');
        $pdf = new Pdf();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage('L'); // Landscape
        
        $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'LAPORAN PAYROLL', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Periode: ' . $bulan_nama[$bulan] . ' ' . $tahun, 0, 1, 'C');
        $pdf->Ln(10);
        
        // Payroll Guru
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'PAYROLL GURU', 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 7, 'Nama', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Jam Ajar', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Honor', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Transport', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'PPh21', 1, 0, 'C', true);
        $pdf->Cell(45, 7, 'Gaji Bersih', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Status', 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($data['guru'] as $g) {
            $pdf->Cell(50, 6, $g->nama_guru, 1, 0, 'L');
            $pdf->Cell(30, 6, ($g->total_jam_ajar_anak + $g->total_jam_ajar_dewasa) . ' jam', 1, 0, 'C');
            $pdf->Cell(40, 6, number_format($g->total_honor), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($g->total_transport), 1, 0, 'R');
            $pdf->Cell(35, 6, number_format($g->pph21_nominal), 1, 0, 'R');
            $pdf->Cell(45, 6, number_format($g->total_gaji_bersih), 1, 0, 'R');
            $pdf->Cell(25, 6, $g->status, 1, 1, 'C');
        }
        
        $pdf->Ln(10);
        
        // Payroll Staff
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'PAYROLL STAFF', 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 7, 'Nama', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Gaji Pokok', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Tunjangan', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Potongan', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'PPh21', 1, 0, 'C', true);
        $pdf->Cell(45, 7, 'Gaji Bersih', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'Status', 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($data['staff'] as $s) {
            $pdf->Cell(50, 6, $s->nama_karyawan, 1, 0, 'L');
            $pdf->Cell(40, 6, number_format($s->gaji_pokok), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($s->tunjangan), 1, 0, 'R');
            $pdf->Cell(40, 6, number_format($s->potongan), 1, 0, 'R');
            $pdf->Cell(35, 6, number_format($s->pph21_nominal), 1, 0, 'R');
            $pdf->Cell(45, 6, number_format($s->total_gaji_bersih), 1, 0, 'R');
            $pdf->Cell(15, 6, $s->status, 1, 1, 'C');
        }
        
        $pdf->Ln(10);
        
        // Summary
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(150, 8, 'TOTAL PAYROLL GURU:', 0, 0, 'R');
        $pdf->Cell(0, 8, 'Rp ' . number_format($data['summary']['total_guru']), 0, 1, 'R');
        
        $pdf->Cell(150, 8, 'TOTAL PAYROLL STAFF:', 0, 0, 'R');
        $pdf->Cell(0, 8, 'Rp ' . number_format($data['summary']['total_staff']), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(150, 10, 'GRAND TOTAL:', 0, 0, 'R');
        $pdf->Cell(0, 10, 'Rp ' . number_format($data['summary']['grand_total']), 0, 1, 'R');
        
        $filename = 'Laporan_Payroll_' . $bulan_nama[$bulan] . '_' . $tahun . '.pdf';
        $pdf->Output($filename, 'D');
    }

    // =============================================
    // PAYROLL POTONGAN
    // =============================================
    
    public function add_potongan()
    {
        $i = $this->input;
        $payroll_type = $i->post('payroll_type');
        $id_payroll = $i->post('id_payroll');
        
        $data = array(
            'payroll_type' => $payroll_type,
            'id_payroll' => $id_payroll,
            'nominal' => $i->post('nominal'),
            'keterangan' => $i->post('keterangan'),
            'created_by' => $this->session->userdata('id_user')
        );
        
        $result = $this->m_hr->insert_potongan($data);
        
        if ($result) {
            // Recalculate payroll totals
            $this->m_hr->recalculate_payroll_potongan($payroll_type, $id_payroll);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambah potongan']);
        }
    }
    
    public function get_potongan()
    {
        $payroll_type = $this->input->post('payroll_type');
        $id_payroll = $this->input->post('id_payroll');
        
        $data = $this->m_hr->get_potongan($payroll_type, $id_payroll);
        echo json_encode($data);
    }
    
    public function hapus_potongan()
    {
        $id = $this->input->post('id');
        $payroll_type = $this->input->post('payroll_type');
        $id_payroll = $this->input->post('id_payroll');
        
        $result = $this->m_hr->delete_potongan($id);
        
        if ($result) {
            // Recalculate payroll totals
            $this->m_hr->recalculate_payroll_potongan($payroll_type, $id_payroll);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }
    
    public function hapus_payroll_guru()
    {
        $id = $this->input->post('id');
        $payroll = $this->m_hr->get_payroll_guru_by_id($id);
        
        if (!$payroll || $payroll->status != 'draft') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya payroll draft yang bisa dihapus']);
            return;
        }
        
        // Delete associated potongan first
        $this->m_hr->delete_potongan_by_payroll('guru', $id);
        
        // Delete payroll
        $result = $this->m_hr->delete_payroll_guru($id);
        $this->m_hr->log('payroll_guru', $id, 'DELETE', $payroll, null, $this->session->userdata('id_user'));
        
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error', 'message' => 'Gagal menghapus']);
    }
    
    public function hapus_payroll_staff()
    {
        $id = $this->input->post('id');
        $payroll = $this->m_hr->get_payroll_staff_by_id($id);
        
        if (!$payroll || $payroll->status != 'draft') {
            echo json_encode(['status' => 'error', 'message' => 'Hanya payroll draft yang bisa dihapus']);
            return;
        }
        
        // Delete associated potongan first
        $this->m_hr->delete_potongan_by_payroll('staff', $id);
        
        // Delete payroll
        $result = $this->m_hr->delete_payroll_staff($id);
        
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error', 'message' => 'Gagal menghapus']);
    }
}

