<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Health page: https://<site>/index.php/Health
 *
 * Public, no login, read-only. Used by the deploy (to check the new code
 * before taking the maintenance page down) and by uptime monitoring.
 * It still answers while maintenance mode is on (see index.php).
 *
 * Answers JSON, HTTP 200 when everything is fine, 503 otherwise:
 *   {"status":"ok","version":"1.1.1","php":"8.3","database":"ok",
 *    "migrations":"ok","db_version":"20261028000000","maintenance":false}
 *
 * "migrations" is "pending" when the code has a migration the database
 * hasn't run yet. Nothing here reveals credentials or error details.
 */
class Health extends CI_Controller
{
    public function __construct()
    {
        // The database is autoloaded and connects inside parent::__construct().
        // Outside production, db_debug would turn a failed connection into an
        // HTML error page before index() runs; config/database.php switches
        // db_debug off when this is defined, so the outage is reported as JSON.
        defined('HEALTH_CHECK') or define('HEALTH_CHECK', TRUE);
        parent::__construct();
    }

    public function index()
    {
        $result = array(
            'status' => 'ok',
            'version' => $this->app_version(),
            'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'database' => 'error',
            'migrations' => 'unknown',
            'db_version' => null,
            'maintenance' => file_exists(MAINTENANCE_FILE),
        );

        // Never let a database problem turn into an error page here:
        // report it in the JSON instead.
        $this->db->db_debug = FALSE;

        if ($this->db->conn_id && $this->db->query('SELECT 1')) {
            $result['database'] = 'ok';

            $this->config->load('migration', TRUE);
            $table = $this->config->item('migration_table', 'migration');
            $query = $this->db->select('version')->get($table);
            $row = $query ? $query->row() : null;
            if ($row) {
                $result['db_version'] = (string) $row->version;
                $latest = $this->latest_migration();
                $result['migrations'] = ($latest === null || strcmp($result['db_version'], $latest) >= 0)
                    ? 'ok'
                    : 'pending';
            }
        }

        if ($result['database'] !== 'ok' || $result['migrations'] !== 'ok') {
            $result['status'] = 'error';
        }

        $this->output
            ->set_status_header($result['status'] === 'ok' ? 200 : 503)
            ->set_header('Cache-Control: no-store')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($result) . "\n");
    }

    private function app_version()
    {
        $file = FCPATH . 'VERSION';
        return is_file($file) ? trim((string) file_get_contents($file)) : 'unknown';
    }

    /* Newest migration timestamp in the code, e.g. "20261028000000". */
    private function latest_migration()
    {
        $latest = null;
        foreach ((array) glob(APPPATH . 'migrations/*.php') as $file) {
            if (preg_match('/^(\d{14})_/', basename($file), $m) && ($latest === null || strcmp($m[1], $latest) > 0)) {
                $latest = $m[1];
            }
        }
        return $latest;
    }
}
