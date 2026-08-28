# Local Development Setup

_AdminTool — CodeIgniter 3 / PHP local environment guide_

This guide walks through running AdminTool (CodeIgniter 3, PHP) locally with
a Laragon-style stack (Apache + PHP + MySQL) on Windows. The same steps apply
to any Apache/PHP/MySQL local dev environment (XAMPP, WAMP, MAMP, a manual
install) — just adjust the paths.

It exists because this codebase has a few quirks inherited from its
production host (Hostinger) that don't work out of the box on a local Apache
install. Every fix below is a **local-only environment change** — nothing in
this document requires editing a tracked file in the repo.

## Why local setup isn't just "point Apache at the folder"

The repo's root `.htaccess` and `application/config/config.php` assume a
production (Hostinger) environment:

1. **Forced HTTPS.** Root `.htaccess` unconditionally redirects every request
   to `https://`. `config.php`'s `base_url` is also hardcoded to `https://`.
   Without HTTPS configured locally, the app either redirect-loops or renders
   pages whose links/AJAX calls point at a `https://` origin that doesn't
   exist yet.
2. **A LiteSpeed-only PHP handler.** The same `.htaccess` sets
   `SetHandler application/x-lsphp74` for `.php` files — a handler name that
   only exists on Hostinger's LiteSpeed servers. On local Apache (mod_php or
   FastCGI), this breaks PHP execution entirely (files get downloaded/served
   as text instead of executed).
3. **No database credentials, no schema.** `application/config/database.php`
   reads `DB_HOST` / `DB_USER` / `DB_PASS` / `DB_NAME` from environment
   variables and defaults to empty strings. No `.sql` dump ships in the repo.
4. **PHP version.** The app was built for PHP 7.x. Modern local stacks
   (Laragon, current XAMPP) usually only ship PHP 8.x. CodeIgniter 3.1.13's
   core is PHP 8-compatible — see [Known issues](#known-issues) below for the
   specifics.

The setup below neutralizes points 1–3 entirely through **web-server
configuration**, so the repo itself never needs to change for local dev.

## Prerequisites

If you've never set up a local PHP dev environment before, follow parts A–E
below in order before moving on to "1. Get the code and a database dump."
Each part ends with a way to check it actually worked — don't move to the
next part until that check passes.

If you already have a working Apache/PHP/MySQL stack, skip to
[1. Get the code and a database dump](#1-get-the-code-and-a-database-dump).

### A. Install Laragon

Laragon is a free all-in-one package for Windows that bundles Apache, PHP,
MySQL, and Git — effectively everything this guide needs except one extra
PHP version (part B).

1. Go to [laragon.org/download](https://laragon.org/download) and download
   the **Full** edition (not Lite) — Lite leaves out things like Git that we
   need. It's about 230 MB.
2. Run the downloaded installer. Accept the defaults, including the install
   path `C:\laragon`. There's no reason to change anything during install for
   this guide.
3. When install finishes, launch Laragon. A small window opens with three
   buttons along the top: **Start All**, **Stop All**, **Menu**.
4. Click **Start All**. In the list below, both **Apache** and **MySQL**
   should turn green within a few seconds.
5. If either one turns red instead: expand the black "Terminal" panel on the
   right side of the window and read the error. By far the most common cause
   is another program already using the same port — Apache needs port 80
   (Skype and IIS are frequent culprits) and MySQL needs port 3306 (a
   previously-installed MySQL/XAMPP is the usual culprit). Close the
   conflicting program and click **Start All** again.

**Check it worked:**
- Open a browser to http://localhost — you should see Laragon's default
  welcome page (not a "can't reach this page" error).
- Right-click the Laragon icon in your Windows system tray (bottom-right,
  near the clock) → **Database**. This should open HeidiSQL, a database
  viewer bundled with Laragon, already connected to your local MySQL as
  `root` with no password.
- Open Laragon's terminal (**Menu → Terminal** in the main window) and type
  `git --version`. It should print something like `git version 2.4x.x`, not
  an error.

### B. Install PHP 7.4 alongside Laragon's PHP

Laragon currently only bundles PHP 8.x versions, but this app was built for
PHP 7.4 (see "Why local setup isn't just..." above). Laragon supports
multiple PHP versions side by side and lets you pick which one is active, so
you don't need to remove anything — you're adding PHP 7.4 as an extra
option.

> This step is optional in practice — the core app and every export/import
> feature have been verified working on Laragon's stock PHP 8.3 too (see
> [Known issues](#known-issues)). Install PHP 7.4 if you want the closest
> possible parity with production, or skip straight to
> [part C](#c-enable-apaches-mod_env-and-mod_ssl-modules) if PHP 8.x is fine
> for your work.

1. Go to the official PHP Windows binaries archive:
   [windows.php.net/downloads/releases/archives](https://windows.php.net/downloads/releases/archives).
2. Find and download `php-7.4.33-nts-Win32-vc15-x64.zip`. (7.4.33 is the last
   7.4 release. "nts" = Non-Thread-Safe, which is the correct build when PHP
   runs under Apache the way Laragon runs it — don't pick a "ts"/thread-safe
   build.)
3. Right-click the downloaded zip → **Extract All**. Rename the extracted
   folder to exactly `php-7.4.33`.
4. Move that whole folder into `C:\laragon\bin\php\`, so the result is
   `C:\laragon\bin\php\php-7.4.33\php.exe`. That folder already contains one
   subfolder per PHP version Laragon ships with — you're just adding a
   sibling folder next to those.
5. Right-click the Laragon tray icon → **Reload** (or fully close and reopen
   Laragon). Then right-click the tray icon again → **PHP → Version** —
   `7.4.33` should now appear in the list alongside the 8.x versions. Click
   it to select it.

**Check it worked:** open Laragon's terminal and run `php -v`. The first
line should read `PHP 7.4.33 ...` — if it still shows an 8.x version, the
version wasn't actually switched; repeat the Reload + PHP → Version step.

> **NOTE** Switching PHP version this way is global to Laragon — every site
> Laragon serves uses whichever version is currently selected. If you have
> other local projects that need PHP 8.x, you'll switch back to that via the
> same menu when you go work on them.

### C. Enable Apache's mod_env and mod_ssl modules

The vhost configuration used later in this guide (step 3) needs two Apache
modules active: `mod_env`, which lets `SetEnv DB_HOST` and friends reach
PHP's `getenv()`, and `mod_ssl`, which is what lets Apache serve HTTPS at
all.

1. Right-click the Laragon tray icon → **Apache → httpd.conf**. This opens
   Apache's main configuration file in your default text editor (Notepad is
   fine).
2. Press Ctrl+F and search for `mod_env.so`. You're looking for this line:
   ```
   LoadModule env_module modules/mod_env.so
   ```
   If that line starts with a `#` (e.g. `#LoadModule env_module
   modules/mod_env.so`), delete the `#` and the single space after it, so
   the line becomes active exactly as shown above.
3. Search again for `mod_ssl.so` and confirm this line also has no leading
   `#`:
   ```
   LoadModule ssl_module modules/mod_ssl.so
   ```
4. Save the file (Ctrl+S) and close it.
5. Restart Apache so the change takes effect: right-click the Laragon tray
   icon → **Apache → Restart** (or click **Stop All** then **Start All** in
   the main Laragon window).

> **NOTE** A stock Laragon install usually already has both modules enabled
> — this step is mostly a safety check. If Apache refuses to restart after
> editing the file, reopen `httpd.conf` and make sure you only removed a `#`
> character and didn't change anything else on those lines.

### D. Confirm OpenSSL is available

Laragon's Full edition bundles Git for Windows, which itself bundles
OpenSSL — so this is almost always already available with nothing extra to
install.

Open Laragon's terminal (**Menu → Terminal**) and run:
```
openssl version
```

You should see something like `OpenSSL 3.x.x ...`. If instead you get
`'openssl' is not recognized...`, Git for Windows isn't on your PATH —
reinstall Laragon's Full edition (not Lite), or separately install Git for
Windows and keep its default PATH option selected during install.

### E. Sanity check before continuing

Before moving on to "1. Get the code and a database dump", open Laragon's
terminal and confirm all four of these succeed:

```
git --version
php -v
openssl version
mysql --version
```

`git --version` should print a Git version, `php -v` should show PHP 7.4.33
(if you did part B) or an 8.x version (if you skipped it), `openssl version`
should show an OpenSSL version, and `mysql --version` confirms the
MySQL/MariaDB client is reachable too. If all four print sensibly instead of
an error, you're ready to continue.

## 1. Get the code and a database dump

```
git clone https://github.com/aksaraBali99/AdminTool.git
```

There is **no database dump in the repo** (see README.md — it was stripped
before the initial push). Get a current `.sql` export from whoever manages
the production database, or from another developer.

## 2. Create the database

Using your local MySQL/MariaDB (adjust host/port/credentials to your setup):

```
mysql -uroot -p -e "CREATE DATABASE admintool_local CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

Use whatever database name you like locally — it's supplied to the app via
an environment variable, not hardcoded anywhere.

### Importing a dump from a MariaDB export

If your dump was generated from a MariaDB server (check the dump's header
comment — `phpMyAdmin`/`mysqldump` write the source server version there) and
you're importing into **MySQL** (not MariaDB) locally, you may hit:

```
ERROR 1273 (HY000): Unknown collation: 'utf8mb4_uca1400_ai_ci'
```

This is a MariaDB 10.10+/11.x collation that MySQL doesn't recognize. Fix a
**copy** of the dump before importing (never edit the original export):

```
sed 's/utf8mb4_uca1400_ai_ci/utf8mb4_general_ci/g' original_dump.sql > dump.local.sql
mysql -uroot -p admintool_local < dump.local.sql
```

If you're importing into MariaDB locally instead, this isn't an issue and you
can import the dump as-is:

```
mysql -uroot -p admintool_local < original_dump.sql
```

## 3. Create the vhost

Add a new site to your local Apache config pointing straight at the cloned
repo folder — **no need to copy or symlink it into `www/`.**

If you're using Laragon: Laragon's own "Auto Virtual Hosts" feature manages
`etc/apache2/sites-enabled/*.conf` itself and will silently overwrite/remove
files you drop there on restart. Instead, add your vhost file to
**`<laragon>/etc/apache2/alias/`** (e.g. `zz-admintool.conf`) — that folder is
also `Include`d by Laragon's `httpd.conf` but isn't managed/regenerated by the
Auto Virtual Hosts feature, so your file survives restarts.

Template (copy this, then fill in the placeholders — `<REPO_PATH>`, a DB
name/user/password, and paths to the cert files from step 4):

```apache
<VirtualHost *:80>
    DocumentRoot "<REPO_PATH>"
    ServerName admintool.test
    ServerAlias *.admintool.test

    # DB credentials for this vhost only, read via getenv() in
    # application/config/database.php. Never commit real credentials.
    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_USER root
    SetEnv DB_PASS <your-local-mysql-password>
    SetEnv DB_NAME admintool_local

    # index.php reads $_SERVER['CI_ENV'] to choose its ENVIRONMENT branch.
    # The default ('development') calls error_reporting(-1) unconditionally,
    # which floods every page with PHP 8.x "deprecated dynamic property"
    # notices from this PHP 7-era codebase. 'testing' uses the same reduced
    # error_reporting as 'production' while db_debug (keyed off
    # ENVIRONMENT !== 'production' in database.php) stays on, and CI3's own
    # error handler still displays real warnings/fatals regardless of
    # display_errors. Omit this SetEnv if you'd rather see every notice.
    SetEnv CI_ENV testing

    <Directory "<REPO_PATH>">
        # AllowOverride None: the app's root .htaccess forces an HTTPS
        # redirect and sets a LiteSpeed-only PHP handler (see "Why local
        # setup isn't just..." above). Apache hard-errors (500) on any
        # .htaccess directive not permitted by AllowOverride rather than
        # skipping it — so a partial AllowOverride list doesn't work here.
        # The only clean local fix is to stop Apache reading .htaccess in
        # this directory at all.
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:443>
    DocumentRoot "<REPO_PATH>"
    ServerName admintool.test
    ServerAlias *.admintool.test

    SSLEngine on
    SSLCertificateFile "<path-to-admintool.test.crt-from-step-4>"
    SSLCertificateKeyFile "<path-to-admintool.test.key-from-step-4>"

    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_USER root
    SetEnv DB_PASS <your-local-mysql-password>
    SetEnv DB_NAME admintool_local
    SetEnv CI_ENV testing

    <Directory "<REPO_PATH>">
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

Add the hostname to your hosts file (`C:\Windows\System32\drivers\etc\hosts`
on Windows, `/etc/hosts` on macOS/Linux — requires admin/root):

```
127.0.0.1 admintool.test
```

## 4. Set up HTTPS (self-signed certificate)

Required because `base_url` and every generated link in the app are
hardcoded to `https://` (see point 1 above) — plain HTTP alone won't get you
a fully working app, just a login page whose form POSTs to a `https://` URL
that doesn't resolve.

Generate a self-signed cert (adjust the output path to wherever you want to
keep it — e.g. your Apache install's `etc/ssl/` folder):

```
openssl req -x509 -newkey rsa:2048 -keyout admintool.test.key -out admintool.test.crt \
  -days 825 -nodes -subj "/CN=admintool.test" \
  -addext "subjectAltName=DNS:admintool.test,DNS:*.admintool.test"
```

> **NOTE** On Windows with Git Bash/MSYS, prefix the command with
> `MSYS_NO_PATHCONV=1` — otherwise MSYS mangles the `-subj "/CN=..."`
> argument by trying to treat it as a filesystem path.

Reference the resulting `.crt`/`.key` paths in the `:443` vhost block above.
Your browser will show a "not secure" / self-signed-certificate warning the
first time you visit — that's expected for local dev; proceed past it.

## 5. Restart Apache and verify

Restart Apache through your local stack's own control (e.g. Laragon's tray
icon → **Apache → Restart**) — don't kill/restart the process directly if
other local sites share the same Apache instance, since a hard restart
affects all of them (briefly) rather than none.

Check the config parses before restarting:

```
httpd -t
```

Then visit **https://admintool.test/** — you should land on the login page
with no PHP notices above it. Log in with a user from the `user` table in
your imported database.

## Known issues

- **PHPExcel / TCPDF exports — verified working on PHP 8.3.** The app
  vendors an old copy of PHPExcel and TCPDF 6.6.2 (`application/libraries/`),
  which predate PHP 8. In practice, though, only one controller
  (`Import.php`) actually uses PHPExcel, and only to *read* an uploaded
  spreadsheet — nothing writes/exports through it. `Crm.php`, `MasterData.php`,
  `Pengeluaran.php`, `Peserta.php`, `Report.php`, and `Trans.php` load the
  `excel` library in their constructors but never call it (dead code left
  over from the template this app was built on). All PDF exports
  (payslips, payroll reports, invoices, ujian/absensi/sertifikat) go through
  TCPDF, which runs cleanly on PHP 8.3 with zero errors or warnings. The one
  Excel *export* feature (absensi guru) uses a small custom `Xlsx_writer`
  library, not PHPExcel, and also runs cleanly. The weekly-schedule export
  was already rewritten by a previous developer to build a plain HTML table
  instead of using PHPExcel (see the "PHP 8 compatible" comment in
  `Penjadwalan::export_weekly_schedule()`). The only PHP 8 fallout is
  cosmetic: PHPExcel's read path throws ~10 "deprecated dynamic property"/
  "return type" notices per import, which are already suppressed by the
  `SetEnv CI_ENV testing` fix in step 3 — the imported data itself has been
  verified byte-for-byte correct. If you ever *do* hit a hard error on an
  export/import feature, it needs a proper fix in the library/controller
  code, not a local-config workaround — flag it rather than silently
  disabling the feature.
- **PHP version mismatch.** The framework core and every export/import path
  run fine on PHP 8.x (verified above), but the README recommends PHP 7.x,
  matching production. If you followed part B and have both available
  locally, prefer 7.4 for the closest parity with prod; PHP 8.x is a
  verified-safe fallback if you'd rather not install a second PHP version.

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Laragon's default landing page loads instead of the app | Vhost file was placed in `sites-enabled/` and got wiped by Laragon's Auto Virtual Hosts regeneration | Move the vhost file to the `alias/` folder instead (step 3) |
| `500 Internal Server Error`, error log says `.htaccess: RewriteEngine not allowed here` | `AllowOverride` includes some but not all override classes; Apache hard-errors on any .htaccess directive outside the allowed set instead of skipping it | Set `AllowOverride None` for the app's `<Directory>` block |
| Page loads over HTTP but login/AJAX calls silently fail | `base_url` is hardcoded `https://`; forms/AJAX POST to a `https://` origin that isn't configured | Set up the `:443` vhost with a cert (step 4) and always browse via `https://` |
| `ERROR 1045 Access denied for user 'root'` on import | Wrong MySQL credentials, or more than one MySQL instance running on the machine (common if both a standalone MySQL service and a bundled Laragon MySQL are installed) | Confirm which MySQL instance/port your local stack's PHP will actually connect to, and use its credentials |
| `ERROR 1273: Unknown collation 'utf8mb4_uca1400_ai_ci'` on import | Dump was exported from MariaDB 10.10+/11.x; importing into MySQL, which doesn't know that collation | See the `sed` fix in step 2 |
| Page floods with "Creation of dynamic property ... is deprecated" notices | `ENVIRONMENT=development` (the default) calls `error_reporting(-1)`, showing every PHP 8.x deprecation notice from this PHP 7-era code | Add `SetEnv CI_ENV testing` to the vhost (step 3) |
