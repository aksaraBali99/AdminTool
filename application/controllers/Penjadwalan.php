<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penjadwalan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_penjadwalan');
        $this->load->model('m_masterdata');
        $this->apk = $this->m_masterdata->get_konfig(0);

        if ($this->session->userdata('is_login') !== true  ) {
            redirect(site_url("Login"));
        }
    }

    // =============================================
    // ABSENSI GURU
    // =============================================
    
    public function absensi_guru()
    {
        $data = array('isi' => 'penjadwalan/absensi_guru');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $data['branch'] = $this->m_penjadwalan->get_all_branch();
        $data['jadwal_kelas'] = $this->m_penjadwalan->get_all_jadwal_kelas(['is_aktif' => 1]);
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function absensi_guru_page()
    {
        $filters = array(
            'tanggal_dari' => $this->input->post('tanggal_dari'),
            'tanggal_sampai' => $this->input->post('tanggal_sampai'),
            'id_guru' => $this->input->post('id_guru'),
            'id_branch' => $this->input->post('id_branch')
        );
        
        $list = $this->m_penjadwalan->get_absensi_guru($filters);
        $data = array();
        
        foreach ($list as $row) {
            $status_badge = '';
            switch ($row->status_hadir) {
                case 'Hadir': $status_badge = '<span class="badge badge-success">Hadir</span>'; break;
                case 'Izin': $status_badge = '<span class="badge badge-warning">Izin</span>'; break;
                case 'Alpha': $status_badge = '<span class="badge badge-danger">Alpha</span>'; break;
            }
            
            $data[] = array(
                $row->id,
                date('d/m/Y', strtotime($row->tanggal)),
                $row->nama_pengajar,
                $row->jam_mulai . ' - ' . $row->jam_selesai,
                $row->total_jam . ' jam',
                $row->jumlah_kedatangan,
                $row->nama_kelas ?: '-',
                $row->nama_branch,
                $row->tipe_kelas,
                $status_badge,
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_absensi_guru()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        
        // Calculate total hours
        $jam_mulai = strtotime($i->post('jam_mulai'));
        $jam_selesai = strtotime($i->post('jam_selesai'));
        $total_jam = round(($jam_selesai - $jam_mulai) / 3600, 2);
        
        // Get tarif from pengajar
        $pengajar = $this->m_masterdata->get_pengajar($i->post('id_guru'));
        $jumlah_kedatangan = $i->post('jumlah_kedatangan') ?: 1;
        $tipe_kelas = $i->post('tipe_kelas') ?: 'anak';
        $tarif = $tipe_kelas == 'dewasa' ? $pengajar->tarif_per_jam_dewasa : $pengajar->tarif_per_jam_anak;
        
        $data = array(
            'id_guru' => $i->post('id_guru'),
            'tanggal' => $i->post('tanggal'),
            'jam_mulai' => $i->post('jam_mulai'),
            'jam_selesai' => $i->post('jam_selesai'),
            'total_jam' => $total_jam,
            'status_hadir' => $i->post('status_hadir'),
            'id_jadwal_kelas' => null,
            'id_branch' => $i->post('id_branch') ?: 1,
            'tipe_kelas' => $tipe_kelas,
            'tarif_per_jam' => $tarif,
            'biaya_transport' => $pengajar->biaya_transport * $jumlah_kedatangan,
            'jumlah_kedatangan' => $jumlah_kedatangan,
            'keterangan' => ''
        );
        
        if ($tipe_form == 'add') {
            $result = $this->m_penjadwalan->insert_absensi($data);
        } else {
            $id = $i->post('id');
            $result = $this->m_penjadwalan->update_absensi($id, $data);
        }
        
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function get_absensi_guru_by_id()
    {
        $id = $this->input->post('id');
        $absensi = $this->m_penjadwalan->get_absensi_by_id($id);
        echo json_encode($absensi);
    }
    
    public function hapus_absensi_guru()
    {
        $id = $this->input->post('id');
        $result = $this->m_penjadwalan->soft_delete_absensi($id);
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function get_jadwal_guru()
    {
        $id_guru = $this->input->post('id_guru');
        $jadwal = $this->m_penjadwalan->get_jadwal_guru_hari_ini($id_guru);
        echo json_encode($jadwal);
    }

    // =============================================
    // LAPORAN ABSENSI GURU
    // =============================================
    
    public function laporan_absensi()
    {
        $data = array('isi' => 'penjadwalan/laporan_absensi');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_laporan_absensi()
    {
        $filters = array(
            'tanggal_dari' => $this->input->post('tanggal_dari'),
            'tanggal_sampai' => $this->input->post('tanggal_sampai'),
            'id_guru' => $this->input->post('id_guru')
        );
        
        $list = $this->m_penjadwalan->get_absensi_guru($filters);
        $data = array();
        
        foreach ($list as $row) {
            $data[] = array(
                'id' => $row->id,
                'tanggal' => $row->tanggal,
                'tanggal_format' => date('d/m/Y', strtotime($row->tanggal)),
                'nama_pengajar' => $row->nama_pengajar,
                'jam_mulai' => $row->jam_mulai,
                'jam_selesai' => $row->jam_selesai,
                'total_jam' => $row->total_jam,
                'jumlah_kedatangan' => $row->jumlah_kedatangan ?: 1,
                'tipe_kelas' => $row->tipe_kelas,
                'status_hadir' => $row->status_hadir
            );
        }
        
        echo json_encode($data);
    }
    
    public function export_absensi_excel()
    {
        $filters = array(
            'tanggal_dari' => $this->input->get('dari'),
            'tanggal_sampai' => $this->input->get('sampai'),
            'id_guru' => $this->input->get('id_guru')
        );
        
        $list = $this->m_penjadwalan->get_absensi_guru($filters);
        
        // Load XLSX Writer library
        $this->load->library('xlsx_writer');
        
        // Set filename
        $filename = 'Laporan_Absensi_Guru_' . date('Ymd_His') . '.xlsx';
        
        // Add title row
        $periode = ($filters['tanggal_dari'] ? date('d/m/Y', strtotime($filters['tanggal_dari'])) : '-') . 
                   ' s/d ' . 
                   ($filters['tanggal_sampai'] ? date('d/m/Y', strtotime($filters['tanggal_sampai'])) : '-');
        
        $this->xlsx_writer->addRow(['LAPORAN ABSENSI GURU'], 'header');
        $this->xlsx_writer->addRow(['Periode: ' . $periode], 'center');
        $this->xlsx_writer->addRow(['']); // Empty row
        
        // Add header row
        $this->xlsx_writer->addRow([
            'No', 'Tanggal', 'Nama Guru', 'Jam Mulai', 'Jam Selesai', 
            'Total Jam', 'Kedatangan', 'Tipe Kelas', 'Status'
        ], 'header');
        
        // Add data rows
        $no = 1;
        $totalJam = 0;
        $totalKedatangan = 0;
        
        foreach ($list as $data) {
            $totalJam += $data->total_jam;
            $totalKedatangan += $data->jumlah_kedatangan ?: 1;
            
            $this->xlsx_writer->addRow([
                $no++,
                date('d/m/Y', strtotime($data->tanggal)),
                $data->nama_pengajar,
                $data->jam_mulai,
                $data->jam_selesai,
                $data->total_jam . ' jam',
                ($data->jumlah_kedatangan ?: 1) . 'x',
                $data->tipe_kelas == 'dewasa' ? 'Dewasa' : 'Anak',
                $data->status_hadir
            ], 'center');
        }
        
        // Add total row
        $this->xlsx_writer->addRow([
            '', '', '', '', 'TOTAL:',
            $totalJam . ' jam',
            $totalKedatangan . 'x',
            '', ''
        ], 'header');
        
        // Download
        $this->xlsx_writer->download($filename);
    }
    
    public function generate_payroll()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $id_guru = $this->input->post('id_guru');
        $id_branch = $this->input->post('id_branch');
        
        $data = $this->m_penjadwalan->get_payroll_data($bulan, $tahun, $id_guru, $id_branch);
        
        // Calculate transport for each guru (based on total kedatangan)
        foreach ($data as &$row) {
            $row->biaya_transport_total = $row->total_kedatangan * $row->tarif_transport;
            $row->total_honor = $row->honor_anak + $row->honor_dewasa + $row->biaya_transport_total;
        }
        
        echo json_encode($data);
    }
    
    public function export_payroll_pdf()
    {
        $bulan = $this->input->get('bulan');
        $tahun = $this->input->get('tahun');
        $id_guru = $this->input->get('id_guru');
        $id_branch = $this->input->get('id_branch');
        
        $data['payroll'] = $this->m_penjadwalan->get_payroll_data($bulan, $tahun, $id_guru, $id_branch);
        $data['bulan'] = $bulan;
        $data['tahun'] = $tahun;
        $data['apk'] = $this->apk;
        
        // Calculate transport (based on total kedatangan)
        foreach ($data['payroll'] as &$row) {
            $row->biaya_transport_total = $row->total_kedatangan * $row->tarif_transport;
            $row->total_honor = $row->honor_anak + $row->honor_dewasa + $row->biaya_transport_total;
        }
        
        $this->load->library('pdf');
        $pdf = new Pdf();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'LAPORAN PAYROLL GURU', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Periode: ' . $bulan_nama[$bulan] . ' ' . $tahun, 0, 1, 'C');
        $pdf->Ln(10);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 8, 'Nama Guru', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Jam Anak', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Jam Dewasa', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Honor', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Hari', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Transport', 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $grand_total = 0;
        
        foreach ($data['payroll'] as $row) {
            $pdf->Cell(50, 7, $row->nama_pengajar, 1, 0, 'L');
            $pdf->Cell(25, 7, $row->total_jam_anak . ' jam', 1, 0, 'C');
            $pdf->Cell(25, 7, $row->total_jam_dewasa . ' jam', 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($row->honor_anak + $row->honor_dewasa), 1, 0, 'R');
            $pdf->Cell(20, 7, $row->total_hari_hadir, 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($row->biaya_transport_total), 1, 1, 'R');
            $grand_total += $row->total_honor;
        }
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(150, 8, 'GRAND TOTAL', 1, 0, 'R');
        $pdf->Cell(30, 8, 'Rp ' . number_format($grand_total), 1, 1, 'R');
        
        $pdf->Output('Payroll_' . $bulan_nama[$bulan] . '_' . $tahun . '.pdf', 'D');
    }

    // =============================================
    // JADWAL KELAS
    // =============================================
    
    public function jadwal_kelas()
    {
        $data = array('isi' => 'penjadwalan/jadwal_kelas');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $data['jenis_kelas'] = $this->m_masterdata->get_allKelas();
        $data['branch'] = $this->m_penjadwalan->get_all_branch();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function jadwal_kelas_page()
    {
        $filters = array(
            'id_branch' => $this->input->post('id_branch'),
            'id_guru' => $this->input->post('id_guru'),
            'is_aktif' => 1
        );
        
        $list = $this->m_penjadwalan->get_all_jadwal_kelas($filters);
        $hari = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $data = array();
        
        foreach ($list as $row) {
            $tipe_badge = $row->tipe_kelas == 'dewasa' 
                ? '<span class="badge badge-info">Dewasa</span>' 
                : '<span class="badge badge-primary">Anak</span>';
            
            $jenis_badge = '';
            switch ($row->jenis_jadwal) {
                case 'Trial Class': $jenis_badge = '<span class="badge badge-warning">Trial</span>'; break;
                case 'Placement Test': $jenis_badge = '<span class="badge badge-success">Placement</span>'; break;
                default: $jenis_badge = '<span class="badge badge-secondary">Regular</span>'; break;
            }
            
            $data[] = array(
                $row->id,
                $hari[$row->hari],
                substr($row->jam_mulai, 0, 5) . ' - ' . substr($row->jam_selesai, 0, 5),
                $row->nama_kelas . ($row->keterangan ? '<br><small class="text-muted">' . $row->keterangan . '</small>' : ''),
                $row->nama_pengajar, 
                $row->ruangan ?: '-',
                $tipe_badge,
                $jenis_badge,
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_jadwal_kelas()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        $exclude_id = $tipe_form == 'edit' ? $i->post('id') : null;
        
        // Check bentrok guru
        if ($this->m_penjadwalan->check_bentrok_guru(
            $i->post('id_guru'),
            $i->post('hari'),
            $i->post('jam_mulai'),
            $i->post('jam_selesai'),
            $exclude_id
        )) {
            echo json_encode(['status' => 'error', 'message' => 'Guru sudah memiliki jadwal di jam tersebut']);
            return;
        }
        
        // Check bentrok ruangan
        if ($this->m_penjadwalan->check_bentrok_ruangan(
            $i->post('ruangan'),
            $i->post('id_branch'),
            $i->post('hari'),
            $i->post('jam_mulai'),
            $i->post('jam_selesai'),
            $exclude_id
        )) {
            echo json_encode(['status' => 'error', 'message' => 'Ruangan sudah digunakan di jam tersebut']);
            return;
        }
        
        $data = array(
            'id_kelas' => $i->post('id_kelas'),
            'id_guru' => $i->post('id_guru'),
            'hari' => $i->post('hari'),
            'jam_mulai' => $i->post('jam_mulai'),
            'jam_selesai' => $i->post('jam_selesai'),
            'id_branch' => $i->post('id_branch') ?: 1,
            'tipe_kelas' => $i->post('tipe_kelas'),
            'ruangan' => $i->post('ruangan'),
            'jenis_jadwal' => $i->post('jenis_jadwal') ?: 'Regular',
            'keterangan' => $i->post('keterangan'),
            'max_pertemuan_bulan' => $i->post('max_pertemuan_bulan') ?: 8
        );
        
        if ($tipe_form == 'add') {
            $result = $this->m_penjadwalan->insert_jadwal_kelas($data);
        } else {
            $result = $this->m_penjadwalan->update_jadwal_kelas($i->post('id'), $data);
        }
        
        echo json_encode($result ? ['status' => 'success'] : ['status' => 'error', 'message' => 'Gagal menyimpan']);
    }
    
    public function get_jadwal_kelas_by_id()
    {
        $id = $this->input->post('id');
        $jadwal = $this->m_penjadwalan->get_jadwal_kelas_by_id($id);
        echo json_encode($jadwal);
    }
    
    public function hapus_jadwal_kelas()
    {
        $id = $this->input->post('id');
        $result = $this->m_penjadwalan->delete_jadwal_kelas($id);
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function check_bentrok()
    {
        $i = $this->input;
        $exclude_id = $i->post('id') ?: null;
        
        $bentrok_guru = $this->m_penjadwalan->check_bentrok_guru(
            $i->post('id_guru'),
            $i->post('hari'),
            $i->post('jam_mulai'),
            $i->post('jam_selesai'),
            $exclude_id
        );
        
        $bentrok_ruangan = $this->m_penjadwalan->check_bentrok_ruangan(
            $i->post('ruangan'),
            $i->post('id_branch'),
            $i->post('hari'),
            $i->post('jam_mulai'),
            $i->post('jam_selesai'),
            $exclude_id
        );
        
        echo json_encode([
            'bentrok_guru' => $bentrok_guru,
            'bentrok_ruangan' => $bentrok_ruangan
        ]);
    }

    // =============================================
    // CALENDAR VIEW
    // =============================================
    
    public function calendar()
    {
        $data = array('isi' => 'penjadwalan/calendar');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $data['branch'] = $this->m_penjadwalan->get_all_branch();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function get_events()
    {
        $start = $this->input->get('start');
        $end = $this->input->get('end');
        $id_branch = $this->input->get('id_branch');
        $id_guru = $this->input->get('id_guru');
        
        $filters = array(
            'id_branch' => $id_branch,
            'id_guru' => $id_guru
        );
        
        $jadwal = $this->m_penjadwalan->get_events($start, $end, $filters);
        $libur = $this->m_penjadwalan->get_libur_between($start, $end);
        
        $events = array();
        $hari_map = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];
        $colors = ['#3498db', '#e74c3c', '#2ecc71', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22'];
        
        // Generate recurring events for jadwal
        foreach ($jadwal as $j) {
            // Determine color based on jenis_jadwal first, then tipe_kelas
            $color = '#3498db'; // Default: Kelas Anak (blue)
            
            if ($j->jenis_jadwal == 'Trial Class') {
                $color = '#f39c12'; // Orange for Trial Class
            } else if ($j->jenis_jadwal == 'Placement Test') {
                $color = '#27ae60'; // Green for Placement Test
            } else if ($j->tipe_kelas == 'dewasa') {
                $color = '#8e44ad'; // Purple for Kelas Dewasa
            }
            
            // Get all dates for this day of week within range
            $current = new DateTime($start);
            $end_date = new DateTime($end);
            
            while ($current <= $end_date) {
                if ($current->format('N') == $j->hari) {
                    $tanggal = $current->format('Y-m-d');
                    
                    // Check if this date is holiday
                    $is_libur = false;
                    foreach ($libur as $l) {
                        if ($l->tanggal == $tanggal) {
                            $is_libur = true;
                            break;
                        }
                    }
                    
                    if (!$is_libur) {
                        $events[] = array(
                            'id' => $j->id . '_' . $tanggal,
                            'title' => $j->nama_kelas . ' - ' . $j->nama_pengajar,
                            'start' => $tanggal . 'T' . $j->jam_mulai,
                            'end' => $tanggal . 'T' . $j->jam_selesai,
                            'color' => $color,
                            'extendedProps' => [
                                'guru' => $j->nama_pengajar,
                                'kelas' => $j->nama_kelas,
                                'branch' => $j->nama_branch,
                                'ruangan' => $j->ruangan,
                                'tipe' => $j->tipe_kelas,
                                'jenis_jadwal' => $j->jenis_jadwal ?: 'Regular',
                                'keterangan' => $j->keterangan ?: ''
                            ]
                        );
                    }
                }
                $current->modify('+1 day');
            }
        }
        
        // Add holidays
        foreach ($libur as $l) {
            $events[] = array(
                'id' => 'libur_' . $l->id,
                'title' => '🏖️ ' . $l->keterangan,
                'start' => $l->tanggal,
                'allDay' => true,
                'color' => '#e74c3c',
                'textColor' => '#ffffff',
                'display' => 'block'
            );
        }
        
        // Add reschedule events (approved only)
        $reschedule = $this->m_penjadwalan->get_reschedule(['status' => 'approved']);
        foreach ($reschedule as $r) {
            // Only show if within current date range
            if ($r->tanggal_baru >= $start && $r->tanggal_baru <= $end) {
                // Filter by guru if specified
                if (!empty($id_guru)) {
                    $jadwal = $this->m_penjadwalan->get_jadwal_kelas_by_id($r->id_jadwal_kelas);
                    if ($jadwal && $jadwal->id_guru != $id_guru) continue;
                }
                
                $events[] = array(
                    'id' => 'reschedule_' . $r->id,
                    'title' => '🔄 ' . $r->nama_kelas . ' - ' . $r->nama_pengajar . ' (Reschedule)',
                    'start' => $r->tanggal_baru . 'T' . $r->jam_baru_mulai,
                    'end' => $r->tanggal_baru . 'T' . $r->jam_baru_selesai,
                    'color' => '#e91e63', // Pink for reschedule
                    'extendedProps' => [
                        'guru' => $r->nama_pengajar,
                        'kelas' => $r->nama_kelas,
                        'ruangan' => '-',
                        'tipe' => 'reschedule',
                        'jenis_jadwal' => 'Reschedule',
                        'keterangan' => 'Dari: ' . date('d-m-Y', strtotime($r->tanggal_lama)) . ' Jam ' . date('H:i', strtotime($r->jam_lama_mulai)) . ' | Alasan: ' . ($r->alasan ?: '-')
                    ]
                );
            }
        }
        
        echo json_encode($events);
    }
    
    public function export_weekly_schedule()
    {
        $start_date = $this->input->get('start'); // Format: 2025-01-06
        $end_date = $this->input->get('end');     // Format: 2025-01-12
        $id_guru = $this->input->get('id_guru');
        
        // Fallback to current week if not provided
        if (!$start_date || !$end_date) {
            $dto = new DateTime();
            $dto->modify('monday this week');
            $start_date = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $end_date = $dto->format('Y-m-d');
        }
        
        // Get pengajar list
        $pengajar = $this->m_masterdata->get_allPengajar();
        
        // Build pengajar colors map (warna-warna menarik)
        $guru_colors = [];
        $colors = [
            'FFC0CB', // Pink - Miss Febra style
            'CCCCFF', // Lavender - Miss Jessi style 
            '90EE90', // Light Green
            'FFFF00', // Yellow
            'ADD8E6', // Light Blue
            'FFB6C1', // Light Pink
            'F0E68C', // Khaki
            'E6E6FA', // Lavender
            'FFDAB9', // Peach
            '98FB98', // Pale Green
        ];
        foreach ($pengajar as $idx => $p) {
            $guru_colors[$p->id_pengajar] = $colors[$idx % count($colors)];
        }
        
        // Get jadwal for this date range
        $filters = array('id_guru' => $id_guru);
        $jadwal_list = $this->m_penjadwalan->get_events($start_date, $end_date, $filters);
        
        // Get jadwal kelas with siswa for each
        $jadwal_with_siswa = [];
        foreach ($jadwal_list as $j) {
            // Get siswa terdaftar di jadwal ini (semua siswa aktif)
            $siswa = $this->db->select('p.nama_anak')
                              ->from('peserta_jadwal pj')
                              ->join('peserta p', 'p.id_peserta = pj.id_peserta')
                              ->where('pj.id_jadwal_kelas', $j->id)
                              ->where('p.status_siswa', 'Aktif')
                              ->order_by('p.nama_anak', 'ASC')
                              ->get()
                              ->result();
            
            $j->siswa = $siswa;
            $jadwal_with_siswa[] = $j;
        }
        
        // Time slots from 10:00 to 16:00 with 30 min intervals
        $time_slots = [];
        for ($h = 10; $h <= 16; $h++) {
            $time_slots[] = sprintf('%02d:00', $h);
            if ($h < 16) {
                $time_slots[] = sprintf('%02d:30', $h);
            }
        }
        
        // Days mapping (Monday=1 to Saturday=6)
        $days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
        
        // Build schedule matrix - each cell can have multiple items
        $schedule = [];
        foreach ($time_slots as $slot) {
            $schedule[$slot] = array_fill(0, 6, []);
        }
        
        // Get all dates in this range
        $dto = new DateTime($start_date);
        $dates_by_day = [];
        for ($d = 0; $d < 6; $d++) {
            $dates_by_day[$d] = $dto->format('Y-m-d');
            $dto->modify('+1 day');
        }
        
        // Map jadwal to schedule
        foreach ($jadwal_with_siswa as $j) {
            $day_idx = $j->hari - 1; // Convert 1-6 to 0-5
            if ($day_idx >= 0 && $day_idx < 6) {
                $start_hour = substr($j->jam_mulai, 0, 5);
                
                // Find matching time slot
                foreach ($time_slots as $slot) {
                    if ($slot == $start_hour) {
                        $siswa_names = [];
                        foreach ($j->siswa as $s) {
                            $siswa_names[] = strtoupper($s->nama_anak);
                        }
                        
                        $schedule[$slot][$day_idx][] = [
                            'kelas' => $j->nama_kelas,
                            'guru' => $j->nama_pengajar,
                            'id_guru' => $j->id_guru,
                            'color' => $guru_colors[$j->id_guru] ?? 'FFFFFF',
                            'siswa' => $siswa_names,
                            'jenis' => $j->jenis_jadwal ?? 'Regular'
                        ];
                        break;
                    }
                }
            }
        }
        
        // === Generate Excel using HTML Table (PHP 8 compatible) ===
        $week_num = date('W', strtotime($start_date));
        $year = date('Y', strtotime($start_date));
        $filename = 'Jadwal_Mingguan_Week' . $week_num . '_' . $year . '.xls';
        
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Cache-Control: max-age=0");
        header("Pragma: public");
        
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Styles>';
        echo '<Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#FFFF00" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="time"><Font ss:Bold="1"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        echo '<Style ss:ID="normal"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        
        // Generate color styles for each guru
        foreach ($pengajar as $p) {
            $color = '#' . $guru_colors[$p->id_pengajar];
            echo '<Style ss:ID="color' . $p->id_pengajar . '"><Interior ss:Color="' . $color . '" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
            echo '<Style ss:ID="colorBold' . $p->id_pengajar . '"><Font ss:Bold="1"/><Interior ss:Color="' . $color . '" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>';
        }
        echo '</Styles>';
        
        echo '<Worksheet ss:Name="Jadwal Mingguan">';
        echo '<Table>';
        
        // Column widths
        echo '<Column ss:Width="50"/>';
        for ($i = 0; $i < 6; $i++) {
            echo '<Column ss:Width="130"/>';
        }
        
        // === LEGEND ROW ===
        echo '<Row>';
        $col_count = 0;
        foreach ($pengajar as $p) {
            $color = $guru_colors[$p->id_pengajar];
            echo '<Cell ss:StyleID="colorBold' . $p->id_pengajar . '"><Data ss:Type="String"></Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($p->nama) . '</Data></Cell>';
            $col_count++;
        }
        echo '</Row>';
        
        // Empty row
        echo '<Row></Row>';
        
        // === HEADER ROW ===
        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String"></Data></Cell>';
        foreach ($days as $day) {
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">' . $day . '</Data></Cell>';
        }
        echo '</Row>';
        
        // === DATA ROWS ===
        foreach ($time_slots as $slot) {
            // Find max siswa count for this row
            $max_rows = 1;
            for ($d = 0; $d < 6; $d++) {
                foreach ($schedule[$slot][$d] as $item) {
                    $needed = 1 + count($item['siswa']);
                    if ($needed > $max_rows) $max_rows = $needed;
                }
            }
            
            // First row with time and class names
            echo '<Row>';
            if ($max_rows > 1) {
                echo '<Cell ss:StyleID="time" ss:MergeDown="' . ($max_rows - 1) . '"><Data ss:Type="String">' . $slot . '</Data></Cell>';
            } else {
                echo '<Cell ss:StyleID="time"><Data ss:Type="String">' . $slot . '</Data></Cell>';
            }
            
            for ($d = 0; $d < 6; $d++) {
                $items = $schedule[$slot][$d];
                if (empty($items)) {
                    if ($max_rows > 1) {
                        echo '<Cell ss:StyleID="normal" ss:MergeDown="' . ($max_rows - 1) . '"><Data ss:Type="String"></Data></Cell>';
                    } else {
                        echo '<Cell ss:StyleID="normal"><Data ss:Type="String"></Data></Cell>';
                    }
                } else {
                    foreach ($items as $item) {
                        $kelas = htmlspecialchars($item['kelas']);
                        if ($item['jenis'] == 'Trial Class') {
                            $kelas .= ' (Trial)';
                        }
                        $styleId = 'colorBold' . $item['id_guru'];
                        echo '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String">' . $kelas . '</Data></Cell>';
                    }
                }
            }
            echo '</Row>';
            
            // Siswa rows
            for ($r = 0; $r < $max_rows - 1; $r++) {
                echo '<Row>';
                // Skip time cell (merged)
                for ($d = 0; $d < 6; $d++) {
                    $items = $schedule[$slot][$d];
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $siswa_name = isset($item['siswa'][$r]) ? htmlspecialchars($item['siswa'][$r]) : '';
                            $styleId = 'color' . $item['id_guru'];
                            echo '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String">' . $siswa_name . '</Data></Cell>';
                        }
                    }
                }
                echo '</Row>';
            }
        }
        
        echo '</Table>';
        echo '</Worksheet>';
        echo '</Workbook>';
        exit;
    }

    // =============================================
    // PENEMPATAN GURU
    // =============================================
    
    public function penempatan_guru()
    {
        $data = array('isi' => 'penjadwalan/penempatan_guru');
        $data['pengajar'] = $this->m_masterdata->get_allPengajar();
        $data['jenis_kelas'] = $this->m_masterdata->get_allKelas();
        $data['branch'] = $this->m_penjadwalan->get_all_branch();
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function penempatan_guru_page()
    {
        $list = $this->m_penjadwalan->get_penempatan(['status' => 'aktif']);
        $data = array();
        $badge='';
        foreach ($list as $row) {
            if ($row->status == 'Aktif') {
                $badge = 'badge-success';
            } elseif ($row->status == 'Mengundurkan Diri') {
                $badge = 'badge-warning';
             } elseif ($row->status == 'Diberhentikan') {
                $badge = 'badge-danger';
            } 
            $data[] = array(
                $row->id,
                $row->nama_pengajar,
                $row->nama_kelas, 
                date('d/m/Y', strtotime($row->tanggal_mulai)),
                $row->tanggal_selesai ? date('d/m/Y', strtotime($row->tanggal_selesai)) : '-',
                '<span class="badge '.$badge.'">' . $row->status . '</span>',
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_penempatan()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        
        $data = array(
            'id_guru' => $i->post('id_guru'),
            'id_kelas' => $i->post('id_kelas'),
            'id_branch' => $i->post('id_branch') ?: 1,
            'tanggal_mulai' => $i->post('tanggal_mulai'),
            'tanggal_selesai' => $i->post('tanggal_selesai') ?: null,
            'status' => $i->post('status'),
            'catatan' => $i->post('catatan')
        );
        
        if ($tipe_form == 'add') {
            $result = $this->m_penjadwalan->insert_penempatan($data);
        } else {
            $result = $this->m_penjadwalan->update_penempatan($i->post('id'), $data);
        }
        
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function get_penempatan()
    {
        $id = $this->input->post('id');
        $penempatan = $this->m_penjadwalan->get_penempatan_by_id($id);
        echo json_encode($penempatan);
    }

    public function hapus_penempatan()
    {
        $id = $this->input->post('id');
        $result = $this->m_penjadwalan->delete_penempatan($id);
        echo json_encode($result ? 'sukses' : 'gagal');
    }

    // =============================================
    // RESCHEDULE KELAS
    // =============================================
    
    public function reschedule()
    {
        $data = array('isi' => 'penjadwalan/reschedule');
        $data['jadwal_kelas'] = $this->m_penjadwalan->get_all_jadwal_kelas(['is_aktif' => 1]);
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function reschedule_page()
    {
        $list = $this->m_penjadwalan->get_reschedule([]);
        $data = array();
        
        foreach ($list as $row) {
            $data[] = array(
                $row->id,
                $row->nama_kelas . ' - ' . $row->nama_pengajar,
                date('d/m/Y', strtotime($row->tanggal_lama)) . ' ' . substr($row->jam_lama_mulai, 0, 5),
                date('d/m/Y', strtotime($row->tanggal_baru)) . ' ' . substr($row->jam_baru_mulai, 0, 5),
                $row->alasan . ($row->keterangan ? '<br><small class="text-muted">' . $row->keterangan . '</small>' : ''),
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_reschedule()
    {
        $i = $this->input;
        
        $jadwal = $this->m_penjadwalan->get_jadwal_kelas_by_id($i->post('id_jadwal_kelas'));
        
        $data = array(
            'id_jadwal_kelas' => $i->post('id_jadwal_kelas'),
            'tanggal_lama' => $i->post('tanggal_lama'),
            'jam_lama_mulai' => $jadwal->jam_mulai,
            'jam_lama_selesai' => $jadwal->jam_selesai,
            'tanggal_baru' => $i->post('tanggal_baru'),
            'jam_baru_mulai' => $i->post('jam_baru_mulai'),
            'jam_baru_selesai' => $i->post('jam_baru_selesai'),
            'alasan' => $i->post('alasan'),
            'jenis_jadwal' => $i->post('jenis_jadwal'),
            'keterangan' => $i->post('keterangan'),
            'status' => 'approved',
            'approved_by' => $this->session->userdata('id_user'),
            'approved_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->session->userdata('id_user')
        );
        
        $result = $this->m_penjadwalan->insert_reschedule($data);
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function hapus_reschedule()
    {
        $id = $this->input->post('id');
        $result = $this->m_penjadwalan->delete_reschedule($id);
        echo json_encode($result ? 'sukses' : 'gagal');
    }

    // =============================================
    // LIBUR NASIONAL
    // =============================================
    
    public function libur_nasional()
    {
        $data = array('isi' => 'penjadwalan/libur_nasional');
        $this->load->view('layouts/wrapper', $data);
    }
    
    public function libur_nasional_page()
    {
        $tahun = $this->input->post('tahun') ?: date('Y');
        $list = $this->m_penjadwalan->get_all_libur($tahun);
        $data = array();
        
        foreach ($list as $row) {
            $jenis_badge = '';
            switch ($row->jenis_libur) {
                case 'Nasional': $jenis_badge = '<span class="badge badge-danger">Nasional</span>'; break;
                case 'Cuti Bersama': $jenis_badge = '<span class="badge badge-warning">Cuti Bersama</span>'; break;
                case 'Libur Khusus': $jenis_badge = '<span class="badge badge-info">Libur Khusus</span>'; break;
            }
            
            $data[] = array(
                $row->id,
                date('d/m/Y', strtotime($row->tanggal)),
                $row->keterangan,
                $jenis_badge,
                null
            );
        }
        
        echo json_encode(array("data" => $data));
    }
    
    public function add_libur()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');
        
        $data = array(
            'tanggal' => $i->post('tanggal'),
            'keterangan' => $i->post('keterangan'),
            'jenis_libur' => $i->post('jenis_libur')
        );
        
        if ($tipe_form == 'add') {
            $result = $this->m_penjadwalan->insert_libur($data);
        } else {
            $result = $this->m_penjadwalan->update_libur($i->post('id'), $data);
        }
        
        echo json_encode($result ? 'sukses' : 'gagal');
    }
    
    public function get_libur_by_id()
    {
        $id = $this->input->post('id');
        $libur = $this->m_penjadwalan->get_libur_by_id($id);
        echo json_encode($libur);
    }
    
    public function hapus_libur()
    {
        $id = $this->input->post('id');
        $result = $this->m_penjadwalan->delete_libur($id);
        echo json_encode($result ? 'sukses' : 'gagal');
    }
}
