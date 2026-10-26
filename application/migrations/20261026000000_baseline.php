<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Baseline: creates every table that existed when migrations were
 * introduced. Safe on existing databases (CREATE TABLE IF NOT EXISTS), so on
 * DEV/PROD it only records that the baseline is applied.
 */
class Migration_Baseline extends CI_Migration
{
    public function up()
    {
        $sql = file_get_contents(APPPATH . 'migrations/sql/20261026000000_baseline.sql');

        // Drop comment lines, then run one statement at a time - mysqli via
        // the query builder can't take a multi-statement string.
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql)));

        // Tables reference each other via foreign keys; creation order in the
        // dump is alphabetical, not dependency order.
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($statements as $statement) {
            $this->db->query($statement);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down()
    {
        // Intentionally empty: rolling back the baseline would drop every
        // table, including live data.
    }
}
