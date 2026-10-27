<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Runs pending database migrations (application/migrations/).
 *
 *   CLI: php index.php migrate
 *   Web: https://<site>/index.php/Migrate?token=<MIGRATE_TOKEN>
 *
 * Web access needs MIGRATE_TOKEN set on the server (SetEnv in .htaccess);
 * without it, or with a wrong token, the request is refused with a 404.
 */
class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! is_cli()) {
            $expected = (string) getenv('MIGRATE_TOKEN');
            $given = (string) $this->input->get('token');
            if ($expected === '' || ! hash_equals($expected, $given)) {
                show_404();
            }
        }

        // PROD runs with db_debug off, where a failed query just returns
        // FALSE and the migration would be recorded as applied anyway.
        $this->db->db_debug = TRUE;

        $this->load->library('migration');
        $this->config->load('migration', TRUE);
    }

    public function index()
    {
        $this->output->set_content_type('text/plain');

        $before = $this->current_version();

        // Two requests running migrations at once could apply the same one
        // twice; serialise them with a MySQL named lock.
        $locked = $this->db->query("SELECT GET_LOCK('admintool_migrate', 30) AS ok")->row()->ok;
        if (! $locked) {
            $this->finish(503, 'Another migration run is in progress. Try again shortly.');
            return;
        }

        $result = $this->migration->latest();
        $this->db->query("SELECT RELEASE_LOCK('admintool_migrate')");

        if ($result === FALSE) {
            $this->finish(500, "Migration failed: " . strip_tags($this->migration->error_string())
                . "\nDatabase is at version " . $this->current_version() . '.');
            return;
        }

        $after = $this->current_version();
        $this->finish(200, $before === $after
            ? "Already up to date at version $after."
            : "Migrated from version $before to $after.");
    }

    private function current_version()
    {
        return (string) $this->db->select('version')->get($this->config->item('migration_table', 'migration'))->row()->version;
    }

    private function finish($status, $message)
    {
        if (is_cli()) {
            echo $message . PHP_EOL;
            if ($status !== 200) {
                exit(1);
            }
            return;
        }

        $this->output->set_status_header($status)->set_output($message . "\n");
    }
}
