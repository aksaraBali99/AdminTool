<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crm extends CI_Controller
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

    public function lead()
    {
        $data = array('isi' => 'crm/lead');
        $data['jenis_kelas'] = $this->m_masterdata->get_allKelas();
        $data['all_jadwal'] = $this->m_masterdata->get_jadwal_non_regular();
        $this->load->view('layouts/wrapper', $data);
    }

    public function lead_page()
    {
        $list = $this->db->select("p.id_peserta, p.nama_ortu, p.no_hp, p.alamat_ortu,
            p.nama_anak, p.tgl_lahir_anak, p.alamat_anak, p.email,
            p.tgl_terakhir_dihubungi, p.src, p.status, p.catatan,
            p.jk, p.id_jenis_kelas, p.is_aktif, p.tgl_non_aktif, p.input_at, p.jenis_siswa,
            jk.nama_kelas,
            GROUP_CONCAT(
                CONCAT(
                    CASE pj.hari
                        WHEN '1' THEN 'Senin'
                        WHEN '2' THEN 'Selasa'
                        WHEN '3' THEN 'Rabu'
                        WHEN '4' THEN 'Kamis'
                        WHEN '5' THEN 'Jumat'
                        WHEN '6' THEN 'Sabtu'
                        WHEN '7' THEN 'Minggu'
                        ELSE 'Tidak Diketahui'
                    END, 
                    ', ', pj.jam_mulai, '-', pj.jam_selesai
                )
                SEPARATOR '@'
            ) as jadwal")
            ->from('peserta p')
            ->join('peserta_jadwal j', 'j.id_peserta = p.id_peserta', 'left')
            ->join('pengajar_jadwal pj', 'j.id_jadwal_pengajar = pj.id_jadwal_pengajar', 'left')
            ->join('data_jenis_kelas jk', 'jk.id_jenis_kelas = p.id_jenis_kelas', 'left')
            ->where('status !=', 'Registrasi Kelas')
            ->group_by('p.id_peserta')
            ->order_by('p.id_peserta', 'DESC')
            ->get()
            ->result();

        $data = array();
        foreach ($list as $row) {
            $jadwal = $row->jadwal ? $row->jadwal : 'Tidak ada Jadwal';

            // Format tanggal lahir
            $tgl_lahir = $row->tgl_lahir_anak ? date('d-m-Y', strtotime($row->tgl_lahir_anak)) : '-';

            // Jenis siswa badge
            $jenis_siswa = $row->jenis_siswa ?: 'regular';
            $jenis_badge = ($jenis_siswa == 'partnership') 
                ? '<span class="badge badge-warning">Partnership</span>' 
                : '<span class="badge badge-secondary">Regular</span>';

            $data[] = array(
                $row->id_peserta,       // 0
                $row->nama_ortu,        // 1
                $row->no_hp,            // 2
                $row->nama_anak,        // 3
                $tgl_lahir,             // 4
                $row->email,            // 5
                $row->src,              // 6
                $row->nama_kelas,       // 7
                '<button class="badge badge-info btn-jadwal-detail" data-jadwal="' . htmlspecialchars($jadwal, ENT_QUOTES) . '" style="cursor: pointer;">Detail Jadwal</button>', // 8
                $jenis_badge,           // 9 - Jenis Siswa
                '<span class="badge badge-warning">'.$row->status.'</span>',  // 10
                $row->is_aktif,         // 11
            );
        }

        echo json_encode(array("data" => $data));
    }

    public function convert_lead()
    {
        $id_peserta = $this->input->post('id_peserta');
        
        $this->db->where('id_peserta', $id_peserta)
                 ->update('peserta', ['status' => 'Registrasi Kelas', 'tgl_konversi_siswa' => Date('Y-m-d H:i:s')]);
        
        echo json_encode('sukses');
    }
    
    public function search_siswa_referral()
    {
        $query = $this->input->post('query');
        
        // Search from peserta (siswa) where status = Registrasi Kelas (aktif siswa)
        $result = $this->db->select('id_peserta, nama_anak, nama_ortu, no_hp')
                           ->from('peserta')
                           ->where('status', 'Registrasi Kelas')
                           ->where('is_aktif', 1)
                           ->group_start()
                               ->like('nama_anak', $query)
                               ->or_like('nama_ortu', $query)
                           ->group_end()
                           ->limit(10)
                           ->get()
                           ->result();
        
        echo json_encode($result);
    }
    
    // Get lead history (status + kontak)
    public function get_lead_history()
    {
        $id_peserta = $this->input->post('id_peserta');
        
        // Get status history
        $status_history = $this->db->select('h.*, u.nama as updated_by_name')
            ->from('lead_status_history h')
            ->join('user u', 'u.id_user = h.updated_by', 'left')
            ->where('h.id_peserta', $id_peserta)
            ->order_by('h.tgl_update', 'DESC')
            ->get()
            ->result();
        
        // Get kontak history
        $kontak_history = $this->db->select('h.*, u.nama as updated_by_name')
            ->from('lead_kontak_history h')
            ->join('user u', 'u.id_user = h.updated_by', 'left')
            ->where('h.id_peserta', $id_peserta)
            ->order_by('h.tgl_update', 'DESC')
            ->get()
            ->result();
        
        echo json_encode([
            'status_history' => $status_history,
            'kontak_history' => $kontak_history
        ]);
    }
    
    // Log status change
    private function log_status_change($id_peserta, $status_lama, $status_baru)
    {
        $this->db->insert('lead_status_history', [
            'id_peserta' => $id_peserta,
            'status_lama' => $status_lama,
            'status_baru' => $status_baru,
            'tgl_update' => date('Y-m-d H:i:s'),
            'updated_by' => $this->session->userdata('id_user')
        ]);
    }
    
    // Log kontak change
    private function log_kontak_change($id_peserta, $tgl_kontak, $catatan = null)
    {
        $this->db->insert('lead_kontak_history', [
            'id_peserta' => $id_peserta,
            'tgl_kontak' => $tgl_kontak,
            'catatan' => $catatan,
            'tgl_update' => date('Y-m-d H:i:s'),
            'updated_by' => $this->session->userdata('id_user')
        ]);
    }
    
    // Update lead with history tracking
    public function update_lead()
    {
        $i = $this->input;
        $id_peserta = $i->post('id_peserta');
        
        // Get current data for comparison
        $current = $this->db->where('id_peserta', $id_peserta)->get('peserta')->row();
        
        // Track status change
        $new_status = $i->post('status');
        if ($current && $current->status != $new_status) {
            $this->log_status_change($id_peserta, $current->status, $new_status);
        }
        
        // Track tgl_terakhir_dihubungi change
        $new_tgl_kontak = $i->post('tgl_terakhir_dihubungi');
        if ($new_tgl_kontak && $current && $current->tgl_terakhir_dihubungi != $new_tgl_kontak) {
            $this->log_kontak_change($id_peserta, $new_tgl_kontak, $i->post('catatan'));
        }
        
        // Update peserta data
        $data = array(
            'nama_ortu' => $i->post('nama_ortu'),
            'no_hp' => $i->post('no_hp'),
            'alamat_ortu' => $i->post('alamat_ortu'),
            'nama_anak' => $i->post('nama_anak'),
            'tgl_lahir_anak' => $i->post('tgl_lahir_anak') ?: null,
            'alamat_anak' => $i->post('alamat_anak') ?: '',
            'email' => $i->post('email'),
            'tgl_terakhir_dihubungi' => $new_tgl_kontak ?: null,
            'src' => $i->post('src'),
            'status' => $new_status,
            'catatan' => $i->post('catatan'),
            'jk' => $i->post('jk'),
            'level_sekolah' => $i->post('level_sekolah') ?: null,
            'nama_sekolah' => $i->post('nama_sekolah'),
            'id_jenis_kelas' => $i->post('id_jenis_kelas'),
            'jenis_siswa' => $i->post('jenis_siswa') ?: 'regular',
            'id_referral' => $i->post('id_referral') ?: null,
            'referral_name' => $i->post('referral_name') ?: null
        );
        
        $this->db->where('id_peserta', $id_peserta)->update('peserta', $data);
        
        // Update jadwal
        $id_jadwal_kelas = $i->post('id_jadwal_kelas');
        $this->db->delete('peserta_jadwal', ['id_peserta' => $id_peserta]);
        
        if ($id_jadwal_kelas && is_array($id_jadwal_kelas)) {
            foreach ($id_jadwal_kelas as $r) {
                if (!empty($r)) {
                    $this->db->insert('peserta_jadwal', [
                        'id_peserta' => $id_peserta,
                        'id_jadwal_kelas' => $r
                    ]);
                }
            }
        }
        
        echo json_encode('sukses');
    }
    
    // Add new lead with initial history
    public function add_lead()
    {
        $i = $this->input;
        
        $data = array(
            'nama_ortu' => $i->post('nama_ortu'),
            'no_hp' => $i->post('no_hp'),
            'alamat_ortu' => $i->post('alamat_ortu'),
            'nama_anak' => $i->post('nama_anak'),
            'tgl_lahir_anak' => $i->post('tgl_lahir_anak') ?: null,
            'alamat_anak' => $i->post('alamat_anak') ?: '',
            'email' => $i->post('email'),
            'tgl_terakhir_dihubungi' => $i->post('tgl_terakhir_dihubungi') ?: null,
            'src' => $i->post('src'),
            'status' => $i->post('status'),
            'catatan' => $i->post('catatan'),
            'jk' => $i->post('jk'),
            'level_sekolah' => $i->post('level_sekolah') ?: null,
            'nama_sekolah' => $i->post('nama_sekolah'),
            'id_jenis_kelas' => $i->post('id_jenis_kelas'),
            'jenis_siswa' => $i->post('jenis_siswa') ?: 'regular',
            'id_referral' => $i->post('id_referral') ?: null,
            'referral_name' => $i->post('referral_name') ?: null
        );
        
        $this->db->insert('peserta', $data);
        $id_peserta = $this->db->insert_id();
        
        // Log initial status
        $this->log_status_change($id_peserta, null, $i->post('status'));
        
        // Log initial contact if provided
        if ($i->post('tgl_terakhir_dihubungi')) {
            $this->log_kontak_change($id_peserta, $i->post('tgl_terakhir_dihubungi'), $i->post('catatan'));
        }
        
        // Save jadwal
        $id_jadwal_kelas = $i->post('id_jadwal_kelas');
        if ($id_jadwal_kelas && is_array($id_jadwal_kelas)) {
            foreach ($id_jadwal_kelas as $r) {
                if (!empty($r)) {
                    $this->db->insert('peserta_jadwal', [
                        'id_peserta' => $id_peserta,
                        'id_jadwal_kelas' => $r
                    ]);
                }
            }
        }
        
        echo json_encode('sukses');
    }
}

