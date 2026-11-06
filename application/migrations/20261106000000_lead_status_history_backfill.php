<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * The dashboard's Murid Trial / Placement Test cards count lead_status_history
 * rows. Leads whose status was set before history logging existed, or through
 * Masterdata > Data Peserta (which didn't log until now), have no row, so they
 * would drop off the cards.
 *
 * Backfill: for every peserta currently at 'Jadwal Trial' or 'Placement Test'
 * with no history row for that status, add one dated input_at (the only
 * timestamp available; exact for leads created directly at that status).
 * status_lama and updated_by stay NULL to mark these as backfilled.
 *
 * Also index (status_baru, tgl_update) so the dashboard's month query is a
 * range lookup instead of a full table scan.
 */
class Migration_Lead_status_history_backfill extends CI_Migration
{
    const INDEX = 'idx_status_baru_tgl_update';

    public function up()
    {
        $sql = file_get_contents(APPPATH . 'migrations/sql/20261106000000_lead_status_history_backfill.sql');
        $this->db->query(trim(preg_replace('/^--.*$/m', '', $sql)));

        if (! $this->index_exists()) {
            $this->db->query('ALTER TABLE lead_status_history ADD INDEX ' . self::INDEX . ' (status_baru, tgl_update)');
        }
    }

    public function down()
    {
        // The backfilled rows are left in place: they can't be told apart
        // reliably from rows logged with no session user.
        if ($this->index_exists()) {
            $this->db->query('ALTER TABLE lead_status_history DROP INDEX ' . self::INDEX);
        }
    }

    private function index_exists()
    {
        return $this->db->query(
            'SELECT 1 FROM information_schema.statistics
              WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['lead_status_history', self::INDEX]
        )->num_rows() > 0;
    }
}
