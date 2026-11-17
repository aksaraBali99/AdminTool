# Changing the database

Every change to the database structure (new table, new column, changed
index, renamed column) ships as a **migration** in the same PR as the code
that needs it. Never change DEV or PROD by hand in phpMyAdmin: the change
would be missing on the other environments and in CI.

## How it works

- Migrations live in `application/migrations/`, named
  `YYYYMMDDHHMMSS_short_name.php` (CodeIgniter 3 timestamp migrations).
- Each database records the last migration it has run in the `migrations`
  table. Running migrations applies every newer file, in order.
- `20261026000000_baseline.php` is the starting point: the structure as it
  stood when migrations were introduced. It uses `CREATE TABLE IF NOT EXISTS`,
  so on an existing database it changes nothing and only records the version.
  Never edit it.
- CI builds its test database from an empty database by running all
  migrations, so a structure change made without a migration fails the tests.

## Adding a migration

1. Create `application/migrations/<timestamp>_<what_it_does>.php`, using the
   current UTC time, for example `20261103091500_add_alamat_to_peserta.php`:

   ```php
   <?php
   defined('BASEPATH') or exit('No direct script access allowed');

   class Migration_Add_alamat_to_peserta extends CI_Migration
   {
       public function up()
       {
           $this->dbforge->add_column('peserta', array(
               'alamat' => array('type' => 'TEXT', 'null' => TRUE),
           ));
       }

       public function down()
       {
           $this->dbforge->drop_column('peserta', 'alamat');
       }
   }
   ```

   The class name is `Migration_` plus the file name after the timestamp,
   with the first letter capitalised. Plain SQL via `$this->db->query(...)`
   is fine too.
2. Keep migrations **additive** where possible (add columns or tables, don't
   drop or rename in the same release), so the previous code version still
   works if a release has to be rolled back.
3. Run it locally: `php index.php migrate` with your `DB_*` variables set,
   and again against `admintool_test` before `composer test`.
4. Commit the migration with the code that uses it.

## Running migrations on DEV and PROD

The **Migrate** GitHub Actions workflow (`.github/workflows/migrate.yml`)
runs pending migrations after each deploy:

| Push to | Sites migrated | GitHub Environment(s) |
| --- | --- | --- |
| `main` | DEV | `dev` |
| `production` | both PROD sites | `prod-pemogan`, `prod-citarum` |

It calls each site's Migrate URL and keeps retrying for up to 10 minutes
until the site reports the newest migration in the pushed code, because
Hostinger may still be pulling the code when the workflow starts. It fails,
and GitHub emails whoever pushed, if the token is wrong (404), a migration
fails (500), or the site never catches up. To retry, or to run it without
a push, open Actions → Migrate → **Run workflow** and pick `dev` or
`production`.

**PROD waits for approval.** The `prod-*` environments have required
reviewers, so after a release the PROD jobs pause on "Waiting for review".
Take each PROD site's database backup, then approve that site's job.

### Setup (once per site)

1. In the site's `.htaccess` on Hostinger, add a long random token:
   `SetEnv MIGRATE_TOKEN <random string>`. Without it, or with the wrong
   token, the Migrate page answers 404. Keep the token out of Git and chat
   messages.
2. In GitHub, Settings → Environments → New environment, named `dev`,
   `prod-pemogan` or `prod-citarum`. Add two environment secrets:
   `SITE_URL` (for example `https://adminpemogan.englishhubbali.com`, no
   trailing `/index.php`) and `MIGRATE_TOKEN` (the same token as in
   `.htaccess`).
3. For the two `prod-*` environments, tick **Required reviewers** and add
   the people who take the backups. Under "Deployment branches and tags",
   allow only `production` (and `main` for `dev`).

### Running a migration by hand

If the workflow can't reach a site, either:

- **Browser:** open `https://<site>/index.php/Migrate?token=<MIGRATE_TOKEN>`
- **SSH:** `php index.php migrate` from the site folder, with the site's
  `DB_*` variables set

The page replies in plain text: `Already up to date at version ...`,
`Migrated from version ... to ...`, or `Migration failed: ...` (HTTP 500)
with the version the database stopped at. Take a database backup before
releasing any change that includes a migration.

### First run on an existing database

The first run on DEV and each PROD applies only the baseline, which creates
the `migrations` table and any of the baseline's tables that are missing,
and changes nothing in tables that already exist. It does not fix columns
that differ from the baseline; the PROD audit compares each PROD's structure
with `application/migrations/sql/20261026000000_baseline.sql` and turns any
differences into new migrations.
