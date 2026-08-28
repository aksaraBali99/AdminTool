<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Siswa extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_siswa');
        $this->load->model('m_masterdata');
        $this->apk = $this->m_masterdata->get_konfig(0);

        if ($this->session->userdata('is_login') !== true ) {
            if($this->session->userdata('jabatan') !== 'admin' && $this->session->userdata('jabatan') !== 'superadmin'){
            redirect(site_url("Login"));
            }
        }
    }

    // =============================================
    // DETAIL SISWA
    // =============================================
    
    public function detail($id_siswa)
    {
        $data = array('isi' => 'siswa/detail');
        $data['siswa'] = $this->m_siswa->get_siswa_detail($id_siswa);
        $data['level_aktif'] = $this->m_siswa->get_level_aktif_siswa($id_siswa);
        $data['riwayat_level'] = $this->m_siswa->get_riwayat_level($id_siswa);
        $data['riwayat_ujian'] = $this->m_siswa->get_riwayat_ujian($id_siswa);
        $data['sertifikat'] = $this->m_siswa->get_sertifikat($id_siswa);
        $data['all_level'] = $this->m_siswa->get_all_level_aktif();
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_detail_ajax()
    {
        $id_siswa = $this->input->post('id_siswa');
        
        $data = array(
            'siswa' => $this->m_siswa->get_siswa_detail($id_siswa),
            'level_aktif' => $this->m_siswa->get_level_aktif_siswa($id_siswa),
            'riwayat_level' => $this->m_siswa->get_riwayat_level($id_siswa),
            'riwayat_ujian' => $this->m_siswa->get_riwayat_ujian($id_siswa),
            'sertifikat' => $this->m_siswa->get_sertifikat($id_siswa)
        );
        
        echo json_encode($data);
    }

    // =============================================
    // LEVEL SISWA OPERATIONS
    // =============================================
    
    public function update_level()
    {
        $id_siswa = $this->input->post('id_siswa');
        $id_level = $this->input->post('id_level');
        $catatan = $this->input->post('catatan');
        
        // Check if siswa already has level
        $level_aktif = $this->m_siswa->get_level_aktif_siswa($id_siswa);
        
        if ($level_aktif) {
            // Update level (close old, insert new)
            $result = $this->m_siswa->update_level_siswa($id_siswa, $id_level, $catatan);
        } else {
            // Set initial level
            $result = $this->m_siswa->set_initial_level($id_siswa, $id_level, $catatan);
        }
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Level berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal update level']);
        }
    }
    
    public function get_riwayat_level()
    {
        $id_siswa = $this->input->post('id_siswa');
        $riwayat = $this->m_siswa->get_riwayat_level($id_siswa);
        echo json_encode($riwayat);
    }

    // =============================================
    // UJIAN SISWA OPERATIONS
    // =============================================
    
    public function input_ujian()
    {
        $data = array(
            'id_siswa' => $this->input->post('id_siswa'),
            'tanggal_ujian' => $this->input->post('tanggal_ujian'),
            'jenis_ujian' => $this->input->post('jenis_ujian'),
            'nilai_vocabulary' => $this->input->post('nilai_vocabulary'),
            'nilai_grammar' => $this->input->post('nilai_grammar'),
            'nilai_speaking' => $this->input->post('nilai_speaking'),
            'nilai_writing' => $this->input->post('nilai_writing'),
            'nilai_listening' => $this->input->post('nilai_listening'),
            'nilai_ujian' => $this->input->post('nilai_ujian'),
            'catatan_ujian' => $this->input->post('catatan_ujian')
        );
        
        $result = $this->m_siswa->insert_ujian($data);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Data ujian berhasil disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ujian']);
        }
    }
    
    public function get_riwayat_ujian()
    {
        $id_siswa = $this->input->post('id_siswa');
        $riwayat = $this->m_siswa->get_riwayat_ujian($id_siswa);
        echo json_encode($riwayat);
    }
    
    public function get_ujian_by_id()
    {
        $id = $this->input->post('id');
        $ujian = $this->m_siswa->get_ujian_by_id($id);
        echo json_encode($ujian);
    }
    
    public function update_ujian()
    {
        $id = $this->input->post('id');
        
        $data = array(
            'tanggal_ujian' => $this->input->post('tanggal_ujian'),
            'jenis_ujian' => $this->input->post('jenis_ujian'),
            'nilai_vocabulary' => $this->input->post('nilai_vocabulary'),
            'nilai_grammar' => $this->input->post('nilai_grammar'),
            'nilai_speaking' => $this->input->post('nilai_speaking'),
            'nilai_writing' => $this->input->post('nilai_writing'),
            'nilai_listening' => $this->input->post('nilai_listening'),
            'nilai_ujian' => $this->input->post('nilai_ujian'),
            'catatan_ujian' => $this->input->post('catatan_ujian')
        );
        
        $result = $this->m_siswa->update_ujian($id, $data);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Data ujian berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate data ujian']);
        }
    }
    
    public function hapus_ujian()
    {
        $id = $this->input->post('id');
        $result = $this->m_siswa->delete_ujian($id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Data ujian berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data ujian']);
        }
    }
    
    public function export_ujian_pdf($id_siswa)
    {
        $siswa = $this->m_siswa->get_siswa_detail($id_siswa);
        $riwayat_ujian = $this->m_siswa->get_riwayat_ujian($id_siswa);
        $level_aktif = $this->m_siswa->get_level_aktif_siswa($id_siswa);
        $apk = $this->apk;
        
        // Load TCPDF
        $this->load->library('pdf');
        
        // Create PDF
        $pdf = new Pdf();
        $pdf->SetCreator($apk[0]->nama_apk);
        $pdf->SetAuthor($apk[0]->nama_apk);
        $pdf->SetTitle('Laporan Nilai Ujian - ' . $siswa->nama_anak);
        $pdf->SetSubject('Laporan Nilai Ujian Siswa');
        
        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add page
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, strtoupper($apk[0]->nama_apk), 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, "Student's Performance Report", 0, 1, 'C');
        
        $pdf->Ln(3);
        $pdf->SetDrawColor(44, 62, 80);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(8);
        
        // Info siswa
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nama Siswa', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $siswa->nama_anak, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Jenis Kelamin', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $siswa->jk == 'L' ? 'Laki-laki' : 'Perempuan', 0, 1);
        
        $pdf->Cell(40, 6, 'Nama Orang Tua', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $siswa->nama_ortu, 0, 1);
        
        $pdf->Cell(40, 6, 'Level Saat Ini', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $level_aktif ? $level_aktif->nama_level : '-', 0, 1);
        
        $pdf->Ln(8);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Tanggal', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Jenis Ujian', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Nilai', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Grade', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Catatan', 1, 1, 'C', true);
        
        // Table body
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        
        if (empty($riwayat_ujian)) {
            $pdf->SetFillColor(249, 249, 249);
            $pdf->Cell(180, 10, 'Belum ada data ujian', 1, 1, 'C', true);
        } else {
            $no = 1;
            $fill = false;
            foreach ($riwayat_ujian as $u) {
                // Calculate grade
                $nilai = floatval($u->nilai_ujian);
                if ($nilai >= 90) {
                    $grade = 'A';
                } elseif ($nilai >= 80) {
                    $grade = 'B';
                } elseif ($nilai >= 70) {
                    $grade = 'C';
                } elseif ($nilai >= 60) {
                    $grade = 'D';
                } else {
                    $grade = 'E';
                }
                
                $pdf->SetFillColor($fill ? 249 : 255, $fill ? 249 : 255, $fill ? 249 : 255);
                $pdf->Cell(10, 7, $no++, 1, 0, 'C', true);
                $pdf->Cell(30, 7, date('d/m/Y', strtotime($u->tanggal_ujian)), 1, 0, 'C', true);
                $pdf->Cell(45, 7, $u->jenis_ujian, 1, 0, 'L', true);
                $pdf->Cell(25, 7, $u->nilai_ujian, 1, 0, 'C', true);
                $pdf->Cell(20, 7, $grade, 1, 0, 'C', true);
                $pdf->Cell(50, 7, $u->catatan_ujian ?: '-', 1, 1, 'L', true);
                $fill = !$fill;
            }
        }
        
        // Grading legend
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 6, 'Keterangan Grade:', 0, 1);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'A = Excellent (90-100) | B = Very Good (80-89) | C = Good (70-79) | D = Fair (60-69) | E = Poor (< 60)', 0, 1);
        
        $pdf->Ln(10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        
        // Output PDF
        $filename = 'Nilai_Ujian_' . preg_replace('/[^A-Za-z0-9]/', '_', $siswa->nama_anak) . '_' . date('Ymd') . '.pdf';
        $pdf->Output($filename, 'D');
    }
    
    public function print_ujian($id_ujian)
    {
        $ujian = $this->m_siswa->get_ujian_by_id($id_ujian);
        if (!$ujian) {
            show_error('Data ujian tidak ditemukan', 404);
            return;
        }
        
        $siswa = $this->m_siswa->get_siswa_detail($ujian->id_siswa);
        $level_aktif = $this->m_siswa->get_level_aktif_siswa($ujian->id_siswa);
        $apk = $this->apk;
        
        // Helper function to get grade label
        $getGrade = function($nilai) {
            if ($nilai >= 90) return ['grade' => 'A', 'label' => 'Excellent'];
            if ($nilai >= 80) return ['grade' => 'B', 'label' => 'Very Good'];
            if ($nilai >= 70) return ['grade' => 'C', 'label' => 'Good'];
            if ($nilai >= 60) return ['grade' => 'D', 'label' => 'Fair'];
            return ['grade' => 'E', 'label' => 'Poor'];
        };
        
        // Load TCPDF
        $this->load->library('pdf');
        
        // Create PDF
        $pdf = new Pdf();
        $pdf->SetCreator($apk[0]->nama_apk);
        $pdf->SetAuthor($apk[0]->nama_apk);
        $pdf->SetTitle("Student's Performance - " . $siswa->nama_anak);
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, strtoupper($apk[0]->nama_apk), 0, 1, 'C');
        
        $pdf->Ln(3);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(8);
        
        // Student info
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nama Siswa', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $siswa->nama_anak, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Jenis Ujian', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $ujian->jenis_ujian, 0, 1);
        
        $pdf->Cell(40, 6, 'Tanggal Ujian', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, date('d F Y', strtotime($ujian->tanggal_ujian)), 0, 1);
        
        $pdf->Cell(40, 6, 'Level', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $level_aktif ? $level_aktif->nama_level : '-', 0, 1);
        
        $pdf->Ln(10);
        
        // Title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, "STUDENT'S PERFORMANCE", 0, 1, 'C');
        $pdf->Ln(3);
        
        // Start X position for centered table
        $tableWidth = 130;
        $startX = (210 - $tableWidth) / 2;
        $pdf->SetX($startX);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(50, 8, 'SKILL', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'SCORE', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'GRADE', 1, 1, 'C', true);
        
        // Skills data
        $skills = [
            ['name' => 'Vocabulary', 'value' => $ujian->nilai_vocabulary],
            ['name' => 'Grammar', 'value' => $ujian->nilai_grammar],
            ['name' => 'Speaking', 'value' => $ujian->nilai_speaking],
            ['name' => 'Writing', 'value' => $ujian->nilai_writing],
            ['name' => 'Listening', 'value' => $ujian->nilai_listening]
        ];
        
        $pdf->SetFont('helvetica', '', 10);
        foreach ($skills as $skill) {
            $nilai = floatval($skill['value'] ?: 0);
            $gradeInfo = $getGrade($nilai);
            
            $pdf->SetX($startX);
            $pdf->Cell(50, 8, $skill['name'], 1, 0, 'L');
            $pdf->Cell(40, 8, $skill['value'] ?: '-', 1, 0, 'C');
            $pdf->Cell(40, 8, $skill['value'] ? $gradeInfo['label'] : '-', 1, 1, 'C');
        }
        
        $pdf->Ln(8);
        
        // Average
        $avgGrade = $getGrade(floatval($ujian->nilai_ujian));
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'AVERAGE SCORE: ' . $ujian->nilai_ujian . ' (' . $avgGrade['label'] . ')', 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Grading note
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 6, 'Note:', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'A = Excellent (90-100)', 0, 1);
        $pdf->Cell(0, 5, 'B = Very Good (80-89)', 0, 1);
        $pdf->Cell(0, 5, 'C = Good (70-79)', 0, 1);
        $pdf->Cell(0, 5, 'D = Fair (60-69)', 0, 1);
        $pdf->Cell(0, 5, 'E = Poor (below 60)', 0, 1);
        
        if ($ujian->catatan_ujian) {
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, 'Catatan:', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 5, $ujian->catatan_ujian, 0, 'L');
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        
        // Output
        $filename = 'Performance_' . preg_replace('/[^A-Za-z0-9]/', '_', $siswa->nama_anak) . '_' . date('Ymd') . '.pdf';
        $pdf->Output($filename, 'D');
    }

    // =============================================
    // ABSENSI SISWA OPERATIONS
    // =============================================
    
    public function input_absensi()
    {
        $id_siswa = $this->input->post('id_siswa');
        $tanggal = $this->input->post('tanggal');
        
        // Check if already exists
        if ($this->m_siswa->check_absensi_exists($id_siswa, $tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Absensi untuk tanggal ini sudah ada']);
            return;
        }
        
        $data = array(
            'id_siswa' => $id_siswa,
            'tanggal' => $tanggal,
            'status_hadir' => $this->input->post('status_hadir'),
            'keterangan' => $this->input->post('keterangan')
        );
        
        $result = $this->m_siswa->insert_absensi($data);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Absensi berhasil disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan absensi']);
        }
    }
    
    public function get_absensi()
    {
        $id_siswa = $this->input->post('id_siswa');
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        
        $absensi = $this->m_siswa->get_absensi($id_siswa, $date_from, $date_to);
        $rekap = $this->m_siswa->get_rekap_absensi($id_siswa, $date_from, $date_to);
        
        echo json_encode([
            'absensi' => $absensi,
            'rekap' => $rekap
        ]);
    }
    
    public function download_absensi($id_siswa)
    {
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        
        $siswa = $this->m_siswa->get_siswa_detail($id_siswa);
        $absensi = $this->m_siswa->get_absensi($id_siswa, $date_from, $date_to);
        $rekap = $this->m_siswa->get_rekap_absensi($id_siswa, $date_from, $date_to);
        $apk = $this->apk;
        
        // Load TCPDF
        $this->load->library('pdf');
        
        // Create PDF
        $pdf = new Pdf();
        $pdf->SetCreator($apk[0]->nama_apk);
        $pdf->SetAuthor($apk[0]->nama_apk);
        $pdf->SetTitle('Rekap Absensi - ' . $siswa->nama_anak);
        $pdf->SetSubject('Rekap Absensi Siswa');
        
        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, strtoupper($apk[0]->nama_apk), 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Rekap Kehadiran Siswa', 0, 1, 'C');
        
        $pdf->Ln(5);
        $pdf->SetDrawColor(44, 62, 80);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(8);
        
        // Info siswa
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nama Siswa', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $siswa->nama_anak, 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nama Orang Tua', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $siswa->nama_ortu, 0, 1);
        
        $pdf->Cell(40, 6, 'Periode', 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to)), 0, 1);
        
        $pdf->Ln(5);
        
        // Rekap boxes
        $pdf->SetFont('helvetica', 'B', 10);
        $box_width = 42;
        $box_height = 20;
        $start_x = 15;
        
        // Hadir - Green
        $pdf->SetFillColor(39, 174, 96);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($start_x, $pdf->GetY());
        $pdf->Cell($box_width, $box_height, '', 1, 0, 'C', true);
        $pdf->SetXY($start_x, $pdf->GetY() + 3);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell($box_width, 8, $rekap->total_hadir ?: 0, 0, 0, 'C');
        $pdf->SetXY($start_x, $pdf->GetY() + 8);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($box_width, 5, 'HADIR', 0, 0, 'C');
        
        // Izin - Orange
        $pdf->SetFillColor(243, 156, 18);
        $pdf->SetXY($start_x + $box_width + 3, $pdf->GetY() - 11);
        $pdf->Cell($box_width, $box_height, '', 1, 0, 'C', true);
        $pdf->SetXY($start_x + $box_width + 3, $pdf->GetY() + 3);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell($box_width, 8, $rekap->total_izin ?: 0, 0, 0, 'C');
        $pdf->SetXY($start_x + $box_width + 3, $pdf->GetY() + 8);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($box_width, 5, 'IZIN', 0, 0, 'C');
        
        // Alpha - Red
        $pdf->SetFillColor(231, 76, 60);
        $pdf->SetXY($start_x + ($box_width + 3) * 2, $pdf->GetY() - 11);
        $pdf->Cell($box_width, $box_height, '', 1, 0, 'C', true);
        $pdf->SetXY($start_x + ($box_width + 3) * 2, $pdf->GetY() + 3);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell($box_width, 8, $rekap->total_alpha ?: 0, 0, 0, 'C');
        $pdf->SetXY($start_x + ($box_width + 3) * 2, $pdf->GetY() + 8);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($box_width, 5, 'ALPHA', 0, 0, 'C');
        
        // Total - Blue
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetXY($start_x + ($box_width + 3) * 3, $pdf->GetY() - 11);
        $pdf->Cell($box_width, $box_height, '', 1, 0, 'C', true);
        $pdf->SetXY($start_x + ($box_width + 3) * 3, $pdf->GetY() + 3);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell($box_width, 8, $rekap->total_hari ?: 0, 0, 0, 'C');
        $pdf->SetXY($start_x + ($box_width + 3) * 3, $pdf->GetY() + 8);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($box_width, 5, 'TOTAL', 0, 0, 'C');
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(15);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(15, 8, 'No', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Tanggal', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Hari', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Status', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Keterangan', 1, 1, 'C', true);
        
        // Table body
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        
        if (empty($absensi)) {
            $pdf->SetFillColor(249, 249, 249);
            $pdf->Cell(180, 10, 'Tidak ada data absensi untuk periode ini', 1, 1, 'C', true);
        } else {
            $no = 1;
            $fill = false;
            foreach ($absensi as $a) {
                $day_num = date('w', strtotime($a->tanggal));
                $pdf->SetFillColor($fill ? 249 : 255, $fill ? 249 : 255, $fill ? 249 : 255);
                $pdf->Cell(15, 7, $no++, 1, 0, 'C', true);
                $pdf->Cell(35, 7, date('d/m/Y', strtotime($a->tanggal)), 1, 0, 'C', true);
                $pdf->Cell(30, 7, $hari[$day_num], 1, 0, 'C', true);
                $pdf->Cell(30, 7, $a->status_hadir, 1, 0, 'C', true);
                $pdf->Cell(70, 7, $a->keterangan ?: '-', 1, 1, 'L', true);
                $fill = !$fill;
            }
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        
        // Output PDF
        $filename = 'Absensi_' . preg_replace('/[^A-Za-z0-9]/', '_', $siswa->nama_anak) . '_' . date('Ymd') . '.pdf';
        $pdf->Output($filename, 'D');
    }

    // =============================================
    // SERTIFIKAT OPERATIONS
    // =============================================
    
    public function generate_sertifikat()
    {
        $id_siswa = $this->input->post('id_siswa');
        $id_level = $this->input->post('id_level');
        $id_guru = $this->input->post('id_guru');
        $tanggal_terbit = $this->input->post('tanggal_terbit');
        
        $nomor = $this->m_siswa->generate_nomor_sertifikat();
        
        $data = array(
            'id_siswa' => $id_siswa,
            'id_level' => $id_level,
            'id_guru' => $id_guru,
            'nomor_sertifikat' => $nomor,
            'tanggal_terbit' => $tanggal_terbit ?: date('Y-m-d')
        );
        
        $result = $this->m_siswa->insert_sertifikat($data);
        
        if ($result) {
            $id = $this->db->insert_id();
            echo json_encode([
                'status' => 'success', 
                'message' => 'Sertifikat berhasil digenerate',
                'id' => $id,
                'nomor' => $nomor
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal generate sertifikat']);
        }
    }
    
    public function print_sertifikat($id)
    {
        $data['sertifikat'] = $this->m_siswa->get_sertifikat_by_id($id);
        $data['apk'] = $this->apk;
        
        $this->load->view('siswa/print_sertifikat', $data);
    }

    // =============================================
    // STATUS SISWA OPERATIONS
    // =============================================
    
    public function update_status()
    {
        $id_siswa = $this->input->post('id_siswa');
        $status_siswa = $this->input->post('status_siswa');
        
        $data = array(
            'status_siswa' => $status_siswa
        );
        
        if ($status_siswa == 'Non Aktif') {
            $data['alasan_nonaktif'] = $this->input->post('alasan_nonaktif');
            $data['tanggal_nonaktif'] = date('Y-m-d');
            
            if ($this->input->post('alasan_nonaktif') == 'Lain-lain') {
                $data['alasan_lainnya'] = $this->input->post('alasan_lainnya');
            }
        } else {
            // Clear nonaktif fields if status changed to aktif/cuti
            $data['alasan_nonaktif'] = null;
            $data['alasan_lainnya'] = null;
            $data['tanggal_nonaktif'] = null;
        }
        
        $result = $this->m_siswa->update_status_siswa($id_siswa, $data);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Status siswa berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal update status siswa']);
        }
    }

    public function hapus_absensi()
    {
        $id = $this->input->post('id');
        $result = $this->m_siswa->delete_absensi($id);
        echo json_encode($result ? ['status' => 'success', 'message' => 'Absensi berhasil dihapus'] : ['status' => 'error', 'message' => 'Gagal menghapus absensi']);
    }

    public function hapus_sertifikat()
    {
        $id = $this->input->post('id');
        $result = $this->m_siswa->delete_sertifikat($id);
        echo json_encode($result ? ['status' => 'success', 'message' => 'Sertifikat berhasil dihapus'] : ['status' => 'error', 'message' => 'Gagal menghapus sertifikat']);
    }

    public function hapus_level()
    {
        $id = $this->input->post('id');
        $id_siswa = $this->input->post('id_siswa');
        $result = $this->m_siswa->delete_riwayat_level($id, $id_siswa);
        echo json_encode($result ? ['status' => 'success', 'message' => 'Riwayat level berhasil dihapus'] : ['status' => 'error', 'message' => 'Gagal menghapus riwayat level']);
    }
}
