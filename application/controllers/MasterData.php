<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MasterData extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_masterdata');
        $this->load->model('m_user');
        $this->load->library('excel');
        $this->apk = $this->m_masterdata->get_konfig(0);

        if ($this->session->userdata('is_login') !== true) {
            redirect(site_url("Login"));
        }
    }

    public function jenis_kelas()
    {
        $data = array('isi' => 'masterdata/data_jenis_kelas');
        $this->load->view('layouts/wrapper', $data);
    }

    public function jenis_kelas_page()
    {
        // Datatables Variables
        $draw = intval($this->input->get("draw"));
        $start = intval($this->input->get("start"));
        $length = intval($this->input->get("length"));

        $books = $this->m_masterdata->get_jenis_kelass();

        $data = array();

        foreach ($books->result() as $r) {
            // Partnership badge
            $is_partnership = isset($r->is_partnership) && $r->is_partnership == '1';
            $partnership_badge = $is_partnership
                ? '<span class="badge badge-success">Ya</span>'
                : '<span class="badge badge-secondary">Tidak</span>';

            $data[] = array(
                $r->id_jenis_kelas,
                $r->nama_kelas,
                $r->gender,
                $r->usia,
                $r->tipe,
                $r->biaya,
                $r->biaya_regis,
                $r->biaya_buku,
                $r->nama_buku,
                $partnership_badge,
                null,
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $books->num_rows(),
            "recordsFiltered" => $books->num_rows(),
            "data" => $data,
        );
        echo json_encode($output);
        exit();
    }

    public function add_jenis_kelas()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');

        if ($tipe_form == "add") {
            $data = array(
                'nama_kelas' => $i->post('nama_kelas'),
                'gender' => $i->post('gender'),
                'usia' => $i->post('usia'),
                'tipe' => $i->post('tipe'),
                'biaya' => $i->post('biaya'),
                'biaya_regis' => $i->post('biaya_regis'),
                'biaya_buku' => $i->post('biaya_buku'),
                'nama_buku' => $i->post('nama_buku'),
                'is_partnership' => $i->post('is_partnership') ? '1' : '0',
                'nama_organisasi' => $i->post('nama_organisasi'),
                'kontak_partnership' => $i->post('kontak_partnership'),
                'alamat_partnership' => $i->post('alamat_partnership'),
                'no_telp_partnership' => $i->post('no_telp_partnership'),
                'jumlah_siswa_partnership' => $i->post('jumlah_siswa_partnership'),
            );
            $cek = $this->db->insert('data_jenis_kelas', $data);
        } else {
            $data = array(
                'nama_kelas' => $i->post('nama_kelas'),
                'gender' => $i->post('gender'),
                'usia' => $i->post('usia'),
                'tipe' => $i->post('tipe'),
                'biaya' => $i->post('biaya'),
                'biaya_regis' => $i->post('biaya_regis'),
                'biaya_buku' => $i->post('biaya_buku'),
                'nama_buku' => $i->post('nama_buku'),
                'is_partnership' => $i->post('is_partnership') ? '1' : '0',
                'nama_organisasi' => $i->post('nama_organisasi'),
                'kontak_partnership' => $i->post('kontak_partnership'),
                'alamat_partnership' => $i->post('alamat_partnership'),
                'no_telp_partnership' => $i->post('no_telp_partnership'),
                'jumlah_siswa_partnership' => $i->post('jumlah_siswa_partnership'),
            );
            $cek = $this->m_masterdata->update_jenis_kelas($i->post('id_jenis_kelas'), $data);
        }

        echo json_encode('sukses');
    }

    public function get_jenis_kelas()
    {
        $i = $this->input;
        $data = $this->m_masterdata->get_jenis_kelas($i->post('id_jenis_kelas'));
        echo json_encode($data);
    }

    public function hapus_jenis_kelas()
    {
        $i = $this->input;
        $id_jenis_kelas = $i->post('id_jenis_kelas');
        $hapus_data = $this->db->delete('data_jenis_kelas', array('id_jenis_kelas' => $id_jenis_kelas));
        if ($hapus_data) {
            echo json_encode('suceess');
        }
    }


    public function pengajar()
    {
        $data = array('isi' => 'masterdata/data_pengajar');
        $this->load->view('layouts/wrapper', $data);
    }

    public function pengajar_page()
    {
        $list = $this->db->select("p.id_pengajar, p.no_rek, p.nama, p.jk, p.no_hp, p.alamat,
    GROUP_CONCAT(DISTINCT CONCAT(
        djk.nama_kelas, ' - ',
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
        ' ', jk.jam_mulai, '-', jk.jam_selesai
    ) SEPARATOR '@') as jadwal")
            ->from('pengajar p')
            ->join('jadwal_kelas jk', 'jk.id_guru = p.id_pengajar AND jk.is_aktif = 1', 'left')
            ->join('data_jenis_kelas djk', 'djk.id_jenis_kelas = jk.id_kelas', 'left')
            ->group_by('p.id_pengajar')
            ->get()
            ->result();
        $data = array();
        foreach ($list as $row) {
            $jadwal =  $row->jadwal ?  $row->jadwal : 'Tidak ada Jadwal';
            $data[] = array(
                $row->id_pengajar,
                $row->nama,
                $row->jk,
                $row->no_hp,
                $row->alamat,
                $row->no_rek,
                '<button class="badge badge-info btn-jadwal-detail" data-jadwal="' . htmlspecialchars($jadwal, ENT_QUOTES) . '" style="cursor: pointer;">Detail Jadwal</button>',
                '<center>
                <button class="btn btn-primary btn-sm btn-edit" id="' . $row->id_pengajar . '"><i class="fa fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="hapus(' . $row->id_pengajar . ')"><i class="fa fa-trash"></i></button>
            </center>'
            );
        }

        echo json_encode(array("data" => $data));
    }

    public function add_pengajar()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');

        $data = array(
            'nama' => $i->post('nama'),
            'jk' => $i->post('jk'),
            'no_hp' => $i->post('no_hp'),
            'alamat' => $i->post('alamat'),
            'no_rek' => $i->post('no_rek'),
            'tarif_per_jam_anak' => $i->post('tarif_per_jam_anak'),
            'tarif_per_jam_dewasa' => $i->post('tarif_per_jam_dewasa'),
            'biaya_transport' => $i->post('biaya_transport'),
        );

        $id_jadwal_pengajar = $i->post('id_jadwal_pengajar');
        $hari = $i->post('hari');
        $jam_mulai = $i->post('jam_mulai');
        $jam_selesai = $i->post('jam_selesai');

        if ($tipe_form == "add") {
            $this->db->insert('pengajar', $data);
            $id = $this->db->insert_id();
        } else {
            $id = $i->post('id_pengajar');
            $this->db->where('id_pengajar', $id)->update('pengajar', $data);
        }

        // Simpan jadwal
        if ($hari && is_array($hari)) {
            foreach ($hari as $index => $h) {
                if (strlen($id_jadwal_pengajar[$index]) < 1) {
                    $this->db->insert('pengajar_jadwal', array(
                        'id_pengajar' => $id,
                        'hari' => $h,
                        'jam_mulai' => $jam_mulai[$index],
                        'jam_selesai' => $jam_selesai[$index]
                    ));
                } else {
                    $data = array(
                        'hari' => $h,
                        'jam_mulai' => $jam_mulai[$index],
                        'jam_selesai' => $jam_selesai[$index]
                    );

                    $this->db->where('id_jadwal_pengajar', $id_jadwal_pengajar[$index])->update('pengajar_jadwal', $data);
                }
            }
        }



        echo json_encode('sukses');
    }

    public function update_jadwal()
    {
        $i = $this->input;

        $data = array(
            'hari' => $i->post('hari'),
            'jam_mulai' => $i->post('jam_mulai'),
            'jam_selesai' => $i->post('jam_selesai')
        );

        $id = $i->post('id_jadwal_pengajar');
        $this->db->where('id_jadwal_pengajar', $id)->update('pengajar_jadwal', $data);

        echo json_encode('sukses');
    }

    public function hapus_jadwal()
    {
        $i = $this->input;
        $id_jadwal_pengajar = $i->post('id_jadwal_pengajar');
        $hapus_data = $this->db->delete('pengajar_jadwal', array('id_jadwal_pengajar' => $id_jadwal_pengajar));
        if ($hapus_data) {
            echo json_encode('sukses');
        }
    }


    public function get_pengajar()
    {
        $id = $this->input->post('id_pengajar');
        $pengajar = $this->m_masterdata->get_pengajar($id); // detail
        $jadwal = $this->m_masterdata->get_pengajar_jadwal($id); // array

        echo json_encode([
            'pengajar' => $pengajar,
            'jadwal' => $jadwal
        ]);
    }

    public function hapus_pengajar()
    {
        $id = $this->input->post('id_pengajar');
        $hapus = $this->db->delete('pengajar', ['id_pengajar' => $id]);
        $hapus = $this->db->delete('pengajar_jadwal', ['id_pengajar' => $id]);
        if ($hapus) {
            echo json_encode('success');
        }
    }


    public function konfig()
    {
        if ($this->session->userdata('jabatan') == 'superadmin') {
            $data = array('isi' => 'masterdata/data_konfig');
            $data['konfig'] = $this->m_masterdata->get_konfig(0);

            $this->load->view('layouts/wrapper', $data);
        } else {
            $script = "<script>
            alert('Akses Ditolak');window.location.href = '" . site_url('Login/index') . "';</script>";
            echo $script;
        }
    }

    public function add_konfig()
    {
        $i = $this->input;
        $nm_dokumen = rand();

        if (!empty($_FILES["dokumen"]["name"])) {
            $config['upload_path'] = './uploads/logo';
            $config['allowed_types'] = 'jpg|png|jpeg';
            $config['max_size'] = 30000;
            $config['file_name'] = $nm_dokumen;
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('dokumen')) {
                echo $this->upload->display_errors();
            } else {
                $data = $this->upload->data();
                $nm_dokumen = $nm_dokumen . '.' . pathinfo($_FILES["dokumen"]["name"], PATHINFO_EXTENSION);
                $data = array(
                    'nama_apk'    => $i->post('nama_apk'),
                    'token_wa'    => $i->post('token_wa'),
                    'logo'        => $nm_dokumen
                );
                $this->m_masterdata->update_konfig($i->post('id_konfig'), $data);

                if (!empty($this->apk[0]->logo)) {
                    $old_file = FCPATH . 'uploads/logo/' . $this->apk[0]->logo;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            }
        } else {
            $data = array(
                'nama_apk'    => $i->post('nama_apk'),
                'token_wa'    => $i->post('token_wa'),
            );
            $this->m_masterdata->update_konfig($i->post('id_konfig'), $data);
        }

        echo json_encode('sukses');
    }

    public function change_skin()
    {
        $i = $this->input;
        $data = array(
            'warna_tema' => $i->post('warna_tema'),
        );
        $this->m_masterdata->update_konfig(0, $data);
        echo json_encode('sukses');
    }

    public function pengguna()
    {
        if ($this->session->userdata('jabatan') == 'superadmin' || $this->session->userdata('jabatan') == 'koordinator') {
            $data = array('isi' => 'masterdata/data_pengguna');

            $this->load->view('layouts/wrapper', $data);
        } else {
            $script = "<script>
            alert('Akses Ditolak');window.location.href = '" . site_url('Login/index') . "';</script>";
            echo $script;
        }
    }
    public function pengguna_page()
    {
        // Datatables Variables
        $draw = intval($this->input->get("draw"));
        $start = intval($this->input->get("start"));
        $length = intval($this->input->get("length"));

        $books = $this->m_masterdata->get_penggunas();

        $data = array();

        foreach ($books->result() as $r) {
            $data[] = array(
                $r->id_user,
                $r->username,
                $r->password,
                $r->jabatan,
                $r->nama,
                $r->jk,
                $r->no_hp,
                null,
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $books->num_rows(),
            "recordsFiltered" => $books->num_rows(),
            "data" => $data,
        );
        echo json_encode($output);
        exit();
    }


    public function add_pengguna()
    {
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');

        if ($tipe_form == "add") {
            $data = array(
                'username' => $i->post('username'),
                'nama' => $i->post('nama'),
                'id_tps' => $i->post('id_tps'),
                'id_kel' => $i->post('id_kel'),
                'id_kec' => $i->post('id_kec'),
                'jk' => $i->post('jk'),
                'no_hp' => $i->post('no_hp'),
                'password' => password_hash($i->post('password'), PASSWORD_DEFAULT),
                'jabatan' => $i->post('jabatan'),
                'id_relawan' => $i->post('id_relawan'),

            );
            $cek = $this->db->insert('user', $data);
        } else {
            if (!empty($i->post('password'))) {
                $data = array(
                    'username' => $i->post('username'),
                    'nama' => $i->post('nama'),
                    'id_tps' => $i->post('id_tps'),
                    'id_kel' => $i->post('id_kel'),
                    'id_kec' => $i->post('id_kec'),
                    'jk' => $i->post('jk'),
                    'no_hp' => $i->post('no_hp'),
                    'password' => password_hash($i->post('password'), PASSWORD_DEFAULT),
                    'jabatan' => $i->post('jabatan'),
                    'id_relawan' => $i->post('id_relawan'),
                );
            } else {
                $data = array(
                    'username' => $i->post('username'),
                    'nama' => $i->post('nama'),
                    'id_tps' => $i->post('id_tps'),
                    'id_kel' => $i->post('id_kel'),
                    'id_kec' => $i->post('id_kec'),
                    'jk' => $i->post('jk'),
                    'no_hp' => $i->post('no_hp'),
                    'jabatan' => $i->post('jabatan'),
                    'id_relawan' => $i->post('id_relawan'),
                );
            }

            $cek = $this->m_masterdata->update_pengguna($i->post('id_user'), $data);
        }

        echo json_encode('sukses');
    }

    public function get_pengguna()
    {
        $i = $this->input;
        $data = $this->m_masterdata->get_pengguna($i->post('id_pengguna'));
        echo json_encode($data);
    }

    public function hapus_pengguna()
    {
        $i = $this->input;
        $id_user = $i->post('id_user');
        $hapus_data = $this->db->delete('user', array('id_user' => $id_user));
        if ($hapus_data) {
            echo json_encode('suceess');
        }
    }

    // =============================================
    // MASTER LEVEL SISWA CRUD
    // =============================================

    public function level_siswa()
    {
        $data = array('isi' => 'masterdata/data_level_siswa');
        $this->load->view('layouts/wrapper', $data);
    }

    public function level_siswa_page()
    {
        $this->load->model('m_siswa');
        $list = $this->m_siswa->get_all_level();

        $data = array();
        foreach ($list as $r) {
            $status_badge = $r->status == 'aktif'
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-danger">Non Aktif</span>';

            $data[] = array(
                $r->id_level,
                $r->nama_level,
                $r->deskripsi,
                $r->urutan_level,
                $status_badge,
                null
            );
        }

        echo json_encode(array("data" => $data));
    }

    public function add_level_siswa()
    {
        $this->load->model('m_siswa');
        $i = $this->input;
        $tipe_form = $i->post('tipe_form');

        $data = array(
            'nama_level' => $i->post('nama_level'),
            'deskripsi' => $i->post('deskripsi'),
            'urutan_level' => $i->post('urutan_level'),
            'status' => $i->post('status')
        );

        if ($tipe_form == "add") {
            $result = $this->m_siswa->insert_level($data);
        } else {
            $id_level = $i->post('id_level');
            $result = $this->m_siswa->update_level($id_level, $data);
        }

        echo json_encode($result ? 'sukses' : 'gagal');
    }

    public function get_level_siswa()
    {
        $this->load->model('m_siswa');
        $id_level = $this->input->post('id_level');
        $level = $this->m_siswa->get_level_by_id($id_level);
        echo json_encode($level);
    }

    public function hapus_level_siswa()
    {
        $this->load->model('m_siswa');
        $id_level = $this->input->post('id_level');

        // Check if level is being used
        $used = $this->db->where('id_level', $id_level)->get('riwayat_level_siswa')->num_rows();

        if ($used > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Level tidak bisa dihapus karena sudah digunakan']);
            return;
        }

        $result = $this->m_siswa->delete_level($id_level);
        echo json_encode(['status' => $result ? 'success' : 'error']);
    }
}
