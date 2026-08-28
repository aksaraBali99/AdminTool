<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_user');
        $this->load->model('m_masterdata');
        $this->apk = $this->m_masterdata->get_konfig(0);
    }

    public function index()
    {
        $this->load->view('index');
    }
    public function lp()
    {
        $data = array();
        $data['paslon'] = $this->m_masterdata->get_allCalonResult();
        $data['jml_tps'] =   $this->m_masterdata->total_tps();
        $data['jml_tps_terisi'] =   $this->m_masterdata->total_tps_sudah_terisi();
        $data['suara_real'] = (int)$this->m_masterdata->suara_pemilu()[0]->tot_suara;
        $this->load->view('hasil', $data);
    }

    public function aksi_login()
    {
        $i = $this->input;
        $username = $i->post('username');
        $password = $i->post('password');

        $where = array(
            'username' => $username
        );
        $cek = $this->m_user->cek_login("user", $where)->num_rows();
        $user = $this->m_user->cek_login("user", $where)->row();

        if ($cek > 0) {
            if (password_verify($password, $user->password)) {
                $data_session = array(
                    'username' => $username,
                    'nama' => $user->nama,
                    'id_user' => $user->id_user,
                    'is_login' => true,
                    'jabatan' => $user->jabatan,
                    'id_tps' => !empty($user->id_tps) ? $user->id_tps : null,
                    'id_kel' => !empty($user->id_kel)  ? $user->id_kel : null,
                    'id_kec' => !empty($user->id_kec)  ? $user->id_kec : null,
                    'id_relawan' => !empty($user->id_relawan)  ? $user->id_relawan : null,
                );
                $this->session->set_userdata($data_session);

                return $this->output->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode(array(
                        'role' => $user->jabatan,
                        'message' => 'success',
                        'type' => 'AUTHORIZED',
                    )));
            } else {
                return $this->output->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode(array(
                        'message' => 'Wrong Username or Password',
                        'type' => 'UNAUTHORIZED',
                    )));
            }
        } else {
            return $this->output->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(array(
                    'message' => 'User tidak ditemukan',
                    'type' => 'UNAUTHORIZED',
                )));
        }
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect(site_url('Login/index'));
    }
}
