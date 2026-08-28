# Admin Panel

Internal administration app for [adminpemogan.englishhubbali.com](https://adminpemogan.englishhubbali.com/), built on **CodeIgniter 3** (PHP).

This codebase was pulled from a Hostinger hosting export. It appears to be based on an existing open-source/template CodeIgniter admin app (an election/campaign-management template) that was repurposed for this project, so some internal naming and library choices reflect that origin rather than this app's actual purpose.

## Stack

- PHP (CodeIgniter 3 framework, in `system/`)
- MySQL/MariaDB
- Bootstrap-based admin theme in `assets/`
- Third-party PHP libraries vendored under `application/libraries/` (e.g. PHPExcel for spreadsheet export, TCPDF for PDF export)

## Setup

For a full walkthrough of running this locally (vhost config, HTTPS, database
import, and fixes for the production-only `.htaccess` quirks below), see
[docs/LOCAL_SETUP.md](docs/LOCAL_SETUP.md).

1. Point a PHP 7.x host (Apache/Nginx + `mod_rewrite`) at this directory, with `index.php` as the front controller.
2. Create a MySQL database and set credentials via environment variables (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) or by editing `application/config/database.php` directly for local dev. **Do not commit real credentials** — that file is meant to stay generic in this repo.
3. This repo does **not** include a database dump/schema — none was present in the export this repo was built from. You'll need to obtain the schema (and any seed data) separately from the current hosting environment and import it before the app will run.
4. Set `application/config/config.php`'s `encryption_key` to a real random value for production use (it ships empty).
5. `uploads/` is kept as an empty runtime directory (see `.gitignore`) — actual uploaded files (logos, documents, etc.) are not tracked in version control, both to keep the repo small and because they may contain personal data.

## Notes

- `application/logs/` and `application/cache/` are runtime-only and ignored via `.gitignore`.
- Legacy setup docs and packaged zip re-copies of folders already in this repo were removed during cleanup, since they were leftovers from the original template/export rather than anything specific to this app.
