<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Writes lead_status_history. The dashboard's Murid Trial / Placement Test
 * cards and the Murid Trial report read from this table, so every code path
 * that sets peserta.status (CRM and Masterdata > Data Peserta) must log here.
 */
class M_lead_history extends CI_Model
{
    public function log_status_change($id_peserta, $status_lama, $status_baru)
    {
        if ($status_baru === null || $status_baru === '' || $status_lama === $status_baru) {
            return;
        }

        $this->db->insert('lead_status_history', [
            'id_peserta'  => $id_peserta,
            'status_lama' => $status_lama,
            'status_baru' => $status_baru,
            // Set from PHP so it matches the PHP-computed month range in
            // M_dashboard::get_lead_status_counts(), whatever MySQL's timezone.
            'tgl_update'  => date('Y-m-d H:i:s'),
            'updated_by'  => $this->session->userdata('id_user'),
        ]);
    }
}
