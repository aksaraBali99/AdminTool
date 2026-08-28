<?php
class M_reminder_tagihan extends CI_Model
{
    public function get_datatables($bulan, $tahun, $status)
    {
        $this->db->select('lw.id_log, p.nama_anak as nama, lw.no_hp, lw.bulan, lw.tahun, lw.pesan, lw.response, lw.keterangan, lw.waktu_kirim');
        $this->db->from('reminder_tagihan lw');
        $this->db->join('peserta p', 'p.id_peserta = lw.id_peserta', 'left');

        if ($bulan) {
            $this->db->where('lw.bulan', $bulan);
        }
        if ($tahun) {
            $this->db->where('lw.tahun', $tahun);
        }
        if ($status) {
            // Misalnya filter berdasarkan status tertentu
            $this->db->where('lw.status', $status);
        }

        $query = $this->db->get();
        return $query->result();
    }

    public function count_all($bulan, $tahun, $status)
    {
        $this->db->from('reminder_tagihan lw');
        $this->db->join('peserta p', 'p.id_peserta = lw.id_peserta', 'left');

        if ($bulan) {
            $this->db->where('lw.bulan', $bulan);
        }
        if ($tahun) {
            $this->db->where('lw.tahun', $tahun);
        }
        if ($status) {
            $this->db->where('lw.status', $status);
        }

        return $this->db->count_all_results();
    }

    public function count_filtered($bulan, $tahun, $status)
    {
        $this->db->from('reminder_tagihan lw');
        $this->db->join('peserta p', 'p.id_peserta = lw.id_peserta', 'left');

        if ($bulan) {
            $this->db->where('lw.bulan', $bulan);
        }
        if ($tahun) {
            $this->db->where('lw.tahun', $tahun);
        }
        if ($status) {
            $this->db->where('lw.status', $status);
        }

        $query = $this->db->get();
        return $query->num_rows();
    }
}
