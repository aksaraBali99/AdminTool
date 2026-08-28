<?php

class M_masterdata extends CI_Model
{

    public function __construct()
    {
    }

    //dttables
    function get_jenis_kelass()
    {
        $this->db->select('*');
        $this->db->from('data_jenis_kelas');
        $query = $this->db->get();
        return $query;
    }

    function get_jenis_kelas($id_jenis_kelas)
    {
        $query = "SELECT * FROM data_jenis_kelas WHERE id_jenis_kelas = '" . $id_jenis_kelas . "'";
        $sql = $this->db->query($query);
        return $sql->result();
    }


    function update_jenis_kelas($id, $data)
    {
        $this->db->where('id_jenis_kelas', $id);
        $this->db->update('data_jenis_kelas', $data);
    }

    function get_allKelas()
    {
        $query = "SELECT * FROM data_jenis_kelas";
        $sql = $this->db->query($query);
        return $sql->result();
    }

    function get_allJadwal()
    {
        // Mengambil jadwal dari tabel jadwal_kelas (Penjadwalan/jadwal_kelas)
        $query = "SELECT 
            jk.id as id_jadwal_kelas,
            jk.id_guru as id_pengajar,
            jk.id_kelas as id_jenis_kelas,
            p.nama AS nama_pengajar,
            djk.nama_kelas,
            jk.ruangan,
            jk.tipe_kelas,
            jk.jenis_jadwal,
            CASE jk.hari
                WHEN '1' THEN 'Senin'
                WHEN '2' THEN 'Selasa'
                WHEN '3' THEN 'Rabu'
                WHEN '4' THEN 'Kamis'
                WHEN '5' THEN 'Jumat'
                WHEN '6' THEN 'Sabtu'
                WHEN '7' THEN 'Minggu'
                ELSE 'Tidak Diketahui'
            END AS hari,
            jk.jam_mulai,
            jk.jam_selesai
        FROM jadwal_kelas jk
        JOIN pengajar p ON p.id_pengajar = jk.id_guru
        JOIN data_jenis_kelas djk ON djk.id_jenis_kelas = jk.id_kelas
        WHERE jk.is_aktif = 1
        ORDER BY djk.nama_kelas, jk.hari, jk.jam_mulai;
        ";
        $sql = $this->db->query($query);
        return $sql->result();
    }

    // Get jadwal non-regular (Trial Class & Placement Test only)
    function get_jadwal_non_regular()
    {
        $query = "SELECT 
            jk.id as id_jadwal_kelas,
            jk.id_guru as id_pengajar,
            jk.id_kelas as id_jenis_kelas,
            p.nama AS nama_pengajar,
            djk.nama_kelas,
            jk.ruangan,
            jk.tipe_kelas,
            jk.jenis_jadwal,
            CASE jk.hari
                WHEN '1' THEN 'Senin'
                WHEN '2' THEN 'Selasa'
                WHEN '3' THEN 'Rabu'
                WHEN '4' THEN 'Kamis'
                WHEN '5' THEN 'Jumat'
                WHEN '6' THEN 'Sabtu'
                WHEN '7' THEN 'Minggu'
                ELSE 'Tidak Diketahui'
            END AS hari,
            jk.jam_mulai,
            jk.jam_selesai
        FROM jadwal_kelas jk
        JOIN pengajar p ON p.id_pengajar = jk.id_guru
        JOIN data_jenis_kelas djk ON djk.id_jenis_kelas = jk.id_kelas
        WHERE jk.is_aktif = 1 AND jk.jenis_jadwal IN ('Trial Class', 'Placement Test')
        ORDER BY jk.jenis_jadwal, djk.nama_kelas, jk.hari, jk.jam_mulai;
        ";
        $sql = $this->db->query($query);
        return $sql->result();
    }

    public function get_pengajars()
    {
        return $this->db->get('pengajar');
    }

    public function get_pengajar($id)
    {
        return $this->db->get_where('pengajar', ['id_pengajar' => $id])->row();
    }

    public function get_pengajar_jadwal($id)
    {
        return $this->db->get_where('pengajar_jadwal', ['id_pengajar' => $id])->result();
    }

    function get_allPengajar()
    {
        $query = "SELECT * FROM pengajar";
        $sql = $this->db->query($query);
        return $sql->result();
    }
    public function get_pesertas()
    {
        return $this->db->get('peserta');
    }

    public function get_peserta($id)
    {
        return $this->db->get_where('peserta', ['id_peserta' => $id])->row();
    }

    public function get_peserta_jadwal($id)
    {
        return $this->db->get_where('peserta_jadwal', ['id_peserta' => $id])->result();
    }

    public function get_peserta_aktif()
    {
        $this->db->select('*');
        $this->db->from('peserta');
        $this->db->where('status_siswa', 'Aktif');
        $this->db->where('status', 'Registrasi Kelas');
        $query = $this->db->get();

        return $query->result();
    }

    //ambil dari peserta yang status aktif dan sudah ada pembayaran
    public function get_peserta_aktif_dengan_jadwal_dan_pembayaran()
    {
        $hari_ini = date('l');
        $bulan = date('n');
        $tahun = date('Y');

        // Map hari Inggris ke Indonesia
        $hari_map = [
            'Monday'    => '1',
            'Tuesday'   => '2',
            'Wednesday' => '3',
            'Thursday'  => '4',
            'Friday'    => '5',
            'Saturday'  => '6',
            'Sunday'    => '7',
        ];

        $hari = $hari_map[$hari_ini];

        $this->db->select('peserta.id_peserta, peserta.nama_anak, peserta.no_hp, pengajar_jadwal.hari, pengajar_jadwal.jam_mulai, pengajar_jadwal.jam_selesai,pengajar.nama as nm_pengajar,b.nama_kelas');
        $this->db->from('peserta');
        $this->db->join('peserta_jadwal', 'peserta_jadwal.id_peserta = peserta.id_peserta');
        $this->db->join('pengajar_jadwal', 'pengajar_jadwal.id_jadwal_pengajar = peserta_jadwal.id_jadwal_pengajar');
        $this->db->join('pengajar', 'pengajar.id_pengajar = pengajar_jadwal.id_pengajar');
        $this->db->join('data_jenis_kelas b', 'b.id_jenis_kelas=peserta.id_jenis_kelas');
        $this->db->join('tagihan', 'tagihan.id_peserta = peserta.id_peserta');
        $this->db->where('peserta.status_siswa', 'Aktif');
        $this->db->where('pengajar_jadwal.hari', $hari);
        $this->db->where('tagihan.bulan', $bulan);
        $this->db->where('tagihan.tahun', $tahun);
        $this->db->where('tagihan.status_bayar', 1);
        $this->db->group_by('peserta.id_peserta');

        return $this->db->get()->result();
    }

    public function get_peserta_aktif_by_id($id_peserta)
    {

        $this->db->select('peserta.*,b.*,b.tipe as tipe_biaya');
        $this->db->from('peserta');
        $this->db->join('data_jenis_kelas b', 'b.id_jenis_kelas=peserta.id_jenis_kelas');
        $this->db->where('id_peserta', $id_peserta);
        $query = $this->db->get();

        return $query->result();
    }


    //dttables
    function get_penggunas()
    {
        $this->db->select('ul.*');
        $this->db->from('user ul');

        $query = $this->db->get();
        return $query;
    }

    function get_pengguna($id_user)
    {
        $query = "SELECT * FROM user ul   WHERE  id_user = '" . $id_user . "'";
        $sql = $this->db->query($query);
        return $sql->result();
    }

    function update_pengguna($id, $data)
    {
        $this->db->where('id_user', $id);
        $this->db->update('user', $data);
    }


    function get_konfig($id_konfig)
    {
        $query = "SELECT * FROM data_konfig WHERE  id_konfig = '" . $id_konfig . "'";
        $sql = $this->db->query($query);
        return $sql->result();
    }

    function update_konfig($id, $data)
    {
        $this->db->where('id_konfig', $id);
        $this->db->update('data_konfig', $data);
    }

    function get_allpengguna()
    {
        $query = "SELECT * FROM user";
        $sql = $this->db->query($query);
        return $sql->result();
    }

    function cek_data($table, $where)
    {
        return $this->db->get_where($table, $where);
    }
}
