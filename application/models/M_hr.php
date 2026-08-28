<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_hr extends CI_Model
{
    // =============================================
    // KARYAWAN OPERATIONS
    // =============================================
    
    public function get_all_karyawan($filters = array())
    {
        $this->db->select('k.*, 
            (SELECT COUNT(*) FROM dokumen_karyawan WHERE id_karyawan = k.id_karyawan) as total_dokumen,
            (SELECT nama FROM pengajar WHERE id_karyawan = k.id_karyawan LIMIT 1) as nama_pengajar')
                 ->from('karyawan k');
        
        if (!empty($filters['status_karyawan'])) {
            $this->db->where('k.status_karyawan', $filters['status_karyawan']);
        }
        if (!empty($filters['jenis_karyawan'])) {
            $this->db->where('k.jenis_karyawan', $filters['jenis_karyawan']);
        }
        if (!empty($filters['posisi'])) {
            $this->db->like('k.posisi', $filters['posisi']);
        }
        
        return $this->db->order_by('k.nama', 'ASC')->get()->result();
    }
    
    public function get_karyawan($id)
    {
        return $this->db->where('id_karyawan', $id)->get('karyawan')->row();
    }
    
    public function insert_karyawan($data)
    {
        $this->db->insert('karyawan', $data);
        return $this->db->insert_id();
    }
    
    public function update_karyawan($id, $data)
    {
        return $this->db->where('id_karyawan', $id)->update('karyawan', $data);
    }
    
    public function delete_karyawan($id)
    {
        return $this->db->where('id_karyawan', $id)->update('karyawan', ['status_karyawan' => 'Mengundurkan Diri', 'tanggal_keluar' => date('Y-m-d')]);
    }
    
    public function get_karyawan_aktif()
    {
        return $this->db->where('status_karyawan', 'Aktif')
                        ->order_by('nama', 'ASC')
                        ->get('karyawan')
                        ->result();
    }
    
    public function link_pengajar_to_karyawan($id_pengajar, $id_karyawan)
    {
        return $this->db->where('id_pengajar', $id_pengajar)
                        ->update('pengajar', ['id_karyawan' => $id_karyawan]);
    }

    // =============================================
    // DOKUMEN KARYAWAN OPERATIONS
    // =============================================
    
    public function get_dokumen($id_karyawan)
    {
        return $this->db->where('id_karyawan', $id_karyawan)
                        ->order_by('uploaded_at', 'DESC')
                        ->get('dokumen_karyawan')
                        ->result();
    }
    
    public function get_dokumen_by_id($id)
    {
        return $this->db->where('id', $id)->get('dokumen_karyawan')->row();
    }
    
    public function insert_dokumen($data)
    {
        return $this->db->insert('dokumen_karyawan', $data);
    }
    
    public function delete_dokumen($id)
    {
        return $this->db->where('id', $id)->delete('dokumen_karyawan');
    }

    // =============================================
    // PAYROLL GURU OPERATIONS
    // =============================================
    
    public function get_payroll_guru($filters = array())
    {
        $this->db->select('pg.*, p.nama as nama_guru')
                 ->from('payroll_guru pg')
                 ->join('pengajar p', 'p.id_pengajar = pg.id_guru');
        
        if (!empty($filters['bulan'])) {
            $this->db->where('pg.bulan', $filters['bulan']);
        }
        if (!empty($filters['tahun'])) {
            $this->db->where('pg.tahun', $filters['tahun']);
        }
        if (!empty($filters['id_guru'])) {
            $this->db->where('pg.id_guru', $filters['id_guru']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('pg.status', $filters['status']);
        }
        
        return $this->db->order_by('p.nama', 'ASC')->get()->result();
    }
    
    public function get_payroll_guru_by_id($id)
    {
        return $this->db->select('pg.*, p.nama as nama_guru')
                        ->from('payroll_guru pg')
                        ->join('pengajar p', 'p.id_pengajar = pg.id_guru')
                        ->where('pg.id', $id)
                        ->get()
                        ->row();
    }
    
    public function check_payroll_guru_exists($id_guru, $bulan, $tahun)
    {
        return $this->db->where('id_guru', $id_guru)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->get('payroll_guru')
                        ->row();
    }
    
    public function generate_payroll_guru($id_guru, $bulan, $tahun)
    {
        // Get pengajar data for tarif
        $pengajar = $this->db->where('id_pengajar', $id_guru)->get('pengajar')->row();
        
        if (!$pengajar) return false;
        
        // Get libur dates for the month
        $libur_dates = $this->db->select('tanggal')
                                ->where('MONTH(tanggal)', $bulan)
                                ->where('YEAR(tanggal)', $tahun)
                                ->get('libur_nasional')
                                ->result_array();
        $libur_array = array_column($libur_dates, 'tanggal');
        
        // Get absensi data with total_kedatangan
        $this->db->select("
            SUM(CASE WHEN tipe_kelas = 'anak' AND status_hadir = 'Hadir' THEN total_jam ELSE 0 END) as total_jam_anak,
            SUM(CASE WHEN tipe_kelas = 'dewasa' AND status_hadir = 'Hadir' THEN total_jam ELSE 0 END) as total_jam_dewasa,
            COUNT(DISTINCT CASE WHEN status_hadir = 'Hadir' THEN tanggal END) as total_hari_hadir,
            SUM(CASE WHEN status_hadir = 'Hadir' THEN COALESCE(jumlah_kedatangan, 1) ELSE 0 END) as total_kedatangan
        ");
        $this->db->from('absensi_guru');
        $this->db->where('id_guru', $id_guru);
        $this->db->where('MONTH(tanggal)', $bulan);
        $this->db->where('YEAR(tanggal)', $tahun);
        $this->db->where('is_deleted', 0);
        
        // Exclude libur
        if (!empty($libur_array)) {
            $this->db->where_not_in('tanggal', $libur_array);
        }
        
        $absensi = $this->db->get()->row();
        
        // Calculate
        $total_jam_anak = $absensi->total_jam_anak ?: 0;
        $total_jam_dewasa = $absensi->total_jam_dewasa ?: 0;
        $total_hari_hadir = $absensi->total_hari_hadir ?: 0;
        $total_kedatangan = $absensi->total_kedatangan ?: 0;
        
        $tarif_anak = $pengajar->tarif_per_jam_anak ?: 50000;
        $tarif_dewasa = $pengajar->tarif_per_jam_dewasa ?: 75000;
        $transport_per_kali = $pengajar->biaya_transport ?: 25000;
        
        $honor_anak = $total_jam_anak * $tarif_anak;
        $honor_dewasa = $total_jam_dewasa * $tarif_dewasa;
        $total_honor = $honor_anak + $honor_dewasa;
        // Transport dihitung dari total kedatangan bukan hari hadir
        $total_transport = $total_kedatangan * $transport_per_kali;
        $subtotal = $total_honor + $total_transport;
        
        // Calculate PPh21
        $pph_result = $this->calculate_pph21($subtotal * 12); // Annualized
        $pph21_persen = $pph_result['persen'];
        $pph21_nominal = ($subtotal * $pph_result['persen']) / 100;
        
        $gaji_bersih = $subtotal - $pph21_nominal;
        
        $data = array(
            'id_guru' => $id_guru,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_jam_ajar_anak' => $total_jam_anak,
            'total_jam_ajar_dewasa' => $total_jam_dewasa,
            'tarif_per_jam_anak' => $tarif_anak,
            'tarif_per_jam_dewasa' => $tarif_dewasa,
            'total_honor_anak' => $honor_anak,
            'total_honor_dewasa' => $honor_dewasa,
            'total_honor' => $total_honor,
            'total_hari_hadir' => $total_hari_hadir,
            'total_kedatangan' => $total_kedatangan,
            'biaya_transport_per_hari' => $transport_per_kali,
            'total_transport' => $total_transport,
            'subtotal' => $subtotal,
            'pph21_persen' => $pph21_persen,
            'pph21_nominal' => $pph21_nominal,
            'total_gaji_bersih' => $gaji_bersih,
            'status' => 'draft'
        );
        
        // Check if exists
        $existing = $this->check_payroll_guru_exists($id_guru, $bulan, $tahun);
        if ($existing) {
            // Update existing (only if still draft)
            if ($existing->status == 'draft') {
                $this->db->where('id', $existing->id)->update('payroll_guru', $data);
                return $existing->id;
            }
            return false; // Cannot regenerate approved/paid
        } else {
            $this->db->insert('payroll_guru', $data);
            return $this->db->insert_id();
        }
    }
    
    public function update_payroll_guru($id, $data)
    {
        return $this->db->where('id', $id)->update('payroll_guru', $data);
    }
    
    public function approve_payroll_guru($id, $user_id)
    {
        return $this->db->where('id', $id)->update('payroll_guru', [
            'status' => 'approved',
            'approved_by' => $user_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function pay_payroll_guru($id, $id_pengeluaran)
    {
        return $this->db->where('id', $id)->update('payroll_guru', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'id_pengeluaran' => $id_pengeluaran
        ]);
    }

    // =============================================
    // PAYROLL STAFF OPERATIONS
    // =============================================
    
    public function get_payroll_staff($filters = array())
    {
        $this->db->select('ps.*, k.nama as nama_karyawan, k.posisi')
                 ->from('payroll_staff ps')
                 ->join('karyawan k', 'k.id_karyawan = ps.id_karyawan');
        
        if (!empty($filters['bulan'])) {
            $this->db->where('ps.bulan', $filters['bulan']);
        }
        if (!empty($filters['tahun'])) {
            $this->db->where('ps.tahun', $filters['tahun']);
        }
        if (!empty($filters['id_karyawan'])) {
            $this->db->where('ps.id_karyawan', $filters['id_karyawan']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('ps.status', $filters['status']);
        }
        
        return $this->db->order_by('k.nama', 'ASC')->get()->result();
    }
    
    public function get_payroll_staff_by_id($id)
    {
        return $this->db->select('ps.*, k.nama as nama_karyawan, k.posisi, k.no_ktp, k.npwp')
                        ->from('payroll_staff ps')
                        ->join('karyawan k', 'k.id_karyawan = ps.id_karyawan')
                        ->where('ps.id', $id)
                        ->get()
                        ->row();
    }
    
    public function check_payroll_staff_exists($id_karyawan, $bulan, $tahun)
    {
        return $this->db->where('id_karyawan', $id_karyawan)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->get('payroll_staff')
                        ->row();
    }
    
    public function insert_payroll_staff($data)
    {
        return $this->db->insert('payroll_staff', $data);
    }
    
    public function update_payroll_staff($id, $data)
    {
        return $this->db->where('id', $id)->update('payroll_staff', $data);
    }
    
    public function approve_payroll_staff($id, $user_id)
    {
        return $this->db->where('id', $id)->update('payroll_staff', [
            'status' => 'approved',
            'approved_by' => $user_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function pay_payroll_staff($id, $id_pengeluaran)
    {
        return $this->db->where('id', $id)->update('payroll_staff', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'id_pengeluaran' => $id_pengeluaran
        ]);
    }

    // =============================================
    // PPH21 OPERATIONS
    // =============================================
    
    public function get_pph21_komponen()
    {
        return $this->db->where('status', 'aktif')
                        ->order_by('batas_bawah', 'ASC')
                        ->get('pph21_komponen')
                        ->result();
    }
    
    public function get_pph21_by_id($id)
    {
        return $this->db->where('id', $id)->get('pph21_komponen')->row();
    }
    
    public function insert_pph21($data)
    {
        return $this->db->insert('pph21_komponen', $data);
    }
    
    public function update_pph21($id, $data)
    {
        return $this->db->where('id', $id)->update('pph21_komponen', $data);
    }
    
    public function calculate_pph21($penghasilan_tahunan)
    {
        // Get applicable bracket
        $komponen = $this->db->where('status', 'aktif')
                             ->where('batas_bawah <=', $penghasilan_tahunan)
                             ->order_by('batas_bawah', 'DESC')
                             ->limit(1)
                             ->get('pph21_komponen')
                             ->row();
        
        if ($komponen) {
            return [
                'persen' => $komponen->persentase,
                'nama' => $komponen->nama_komponen
            ];
        }
        
        return ['persen' => 0, 'nama' => 'Tidak Kena Pajak'];
    }

    // =============================================
    // PENGELUARAN INTEGRATION
    // =============================================
    
    public function insert_pengeluaran_from_payroll($type, $payroll)
    {
        $keterangan = '';
        if ($type == 'guru') {
            $keterangan = 'Gaji Guru: ' . $payroll->nama_guru . ' - Periode ' . $payroll->bulan . '/' . $payroll->tahun;
        } else {
            $keterangan = 'Gaji Staff: ' . $payroll->nama_karyawan . ' - Periode ' . $payroll->bulan . '/' . $payroll->tahun;
        }
        
        $data = array(
            'tanggal' => date('Y-m-d'),
            'kategori' => 'Gaji',
            'jumlah' => $payroll->total_gaji_bersih,
            'keterangan' => $keterangan,
            'referensi_tabel' => $type == 'guru' ? 'payroll_guru' : 'payroll_staff',
            'referensi_id' => $payroll->id
        );
        
        $this->db->insert('pengeluaran', $data);
        return $this->db->insert_id();
    }

    // =============================================
    // AUDIT LOG
    // =============================================
    
    public function log($tabel, $id_record, $aksi, $data_lama, $data_baru, $id_user)
    {
        $data = array(
            'tabel' => $tabel,
            'id_record' => $id_record,
            'aksi' => $aksi,
            'data_lama' => $data_lama ? json_encode($data_lama) : null,
            'data_baru' => $data_baru ? json_encode($data_baru) : null,
            'id_user' => $id_user
        );
        return $this->db->insert('audit_log', $data);
    }
    
    public function get_audit_log($tabel = null, $id_record = null, $limit = 50)
    {
        if ($tabel) $this->db->where('tabel', $tabel);
        if ($id_record) $this->db->where('id_record', $id_record);
        
        return $this->db->order_by('created_at', 'DESC')
                        ->limit($limit)
                        ->get('audit_log')
                        ->result();
    }

    // =============================================
    // LAPORAN
    // =============================================
    
    public function get_laporan_payroll($bulan, $tahun)
    {
        $result = array(
            'guru' => array(),
            'staff' => array(),
            'summary' => array()
        );
        
        // Payroll Guru
        $result['guru'] = $this->db->select('pg.*, p.nama as nama_guru')
                                   ->from('payroll_guru pg')
                                   ->join('pengajar p', 'p.id_pengajar = pg.id_guru')
                                   ->where('pg.bulan', $bulan)
                                   ->where('pg.tahun', $tahun)
                                   ->get()
                                   ->result();
        
        // Payroll Staff
        $result['staff'] = $this->db->select('ps.*, k.nama as nama_karyawan')
                                    ->from('payroll_staff ps')
                                    ->join('karyawan k', 'k.id_karyawan = ps.id_karyawan')
                                    ->where('ps.bulan', $bulan)
                                    ->where('ps.tahun', $tahun)
                                    ->get()
                                    ->result();
        
        // Summary
        $total_guru = 0;
        $total_staff = 0;
        foreach ($result['guru'] as $g) $total_guru += $g->total_gaji_bersih;
        foreach ($result['staff'] as $s) $total_staff += $s->total_gaji_bersih;
        
        $result['summary'] = array(
            'total_guru' => $total_guru,
            'total_staff' => $total_staff,
            'grand_total' => $total_guru + $total_staff
        );
        
        return $result;
    }

    // =============================================
    // PAYROLL POTONGAN OPERATIONS
    // =============================================
    
    public function insert_potongan($data)
    {
        $this->db->insert('payroll_potongan', $data);
        return $this->db->insert_id();
    }
    
    public function get_potongan($payroll_type, $id_payroll)
    {
        return $this->db->where('payroll_type', $payroll_type)
                        ->where('id_payroll', $id_payroll)
                        ->order_by('id', 'ASC')
                        ->get('payroll_potongan')
                        ->result();
    }
    
    public function delete_potongan($id)
    {
        return $this->db->where('id', $id)->delete('payroll_potongan');
    }
    
    public function delete_potongan_by_payroll($payroll_type, $id_payroll)
    {
        return $this->db->where('payroll_type', $payroll_type)
                        ->where('id_payroll', $id_payroll)
                        ->delete('payroll_potongan');
    }
    
    public function recalculate_payroll_potongan($payroll_type, $id_payroll)
    {
        // Get total potongan
        $total = $this->db->select_sum('nominal')
                          ->where('payroll_type', $payroll_type)
                          ->where('id_payroll', $id_payroll)
                          ->get('payroll_potongan')
                          ->row();
        
        $total_potongan = $total->nominal ?: 0;
        
        if ($payroll_type == 'guru') {
            // Get current payroll
            $payroll = $this->get_payroll_guru_by_id($id_payroll);
            if ($payroll && $payroll->status == 'draft') {
                // Recalculate gaji bersih: subtotal - pph21 - potongan
                $gaji_bersih = $payroll->subtotal - $payroll->pph21_nominal - $total_potongan;
                
                $this->db->where('id', $id_payroll)->update('payroll_guru', [
                    'total_potongan' => $total_potongan,
                    'total_gaji_bersih' => $gaji_bersih
                ]);
            }
        } else {
            // Staff
            $payroll = $this->get_payroll_staff_by_id($id_payroll);
            if ($payroll && $payroll->status == 'draft') {
                // Recalculate gaji bersih
                $gaji_bersih = $payroll->subtotal - $payroll->pph21_nominal - $total_potongan;
                
                $this->db->where('id', $id_payroll)->update('payroll_staff', [
                    'total_potongan' => $total_potongan,
                    'total_gaji_bersih' => $gaji_bersih
                ]);
            }
        }
        
        return true;
    }
    
    public function delete_payroll_guru($id)
    {
        return $this->db->where('id', $id)->delete('payroll_guru');
    }
    
    public function delete_payroll_staff($id)
    {
        return $this->db->where('id', $id)->delete('payroll_staff');
    }
}

