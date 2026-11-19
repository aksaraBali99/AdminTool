# SSH deploy: server side

The GitHub Actions workflow `.github/workflows/deploy.yml` deploys a site by
sending a release tarball over SSH. On the server, two scripts do the work:

| File | Installed as | Role |
| --- | --- | --- |
| `gate.sh` | `~/apps/admintool/bin/gate.sh` | The only command AdminTool's GitHub key may run. Accepts exactly `deploy <site> <release-id>`, for sites listed in `sites.allow` next to it. Logs anything else to `gate.log` next to it and refuses it. |
| `deploy.sh` | `~/apps/admintool/bin/deploy.sh` | Unpacks the release and deploys it, rolling back by itself if anything fails. |

Only DEV (`dev`, admin-dev.englishhubbali.com) uses this for now. The PROD
sites still use Hostinger's Git deploy.

## Where things are on the server

Two kinds of folders:

- **Per site**, inside the site's own hPanel folder, next to `public_html`.
  It shows in that site's File Manager and is deleted with the site.
- **Per app**, things that must outlive a site: the scripts, the list of
  sites, and the database backups.

```
~/apps/admintool/                     one folder per app
    bin/                              gate.sh, deploy.sh (mode 700), sites.allow, gate.log
        sites/dev.conf                domain=admin-dev.englishhubbali.com
    backups/dev/                      pre-deploy-<UTC time>-<id>.sql.gz
                                      (mode 600; the newest 5 are kept)

~/domains/admin-dev.englishhubbali.com/
    public_html -> ~/domains/admin-dev.englishhubbali.com/deploy/current
    deploy/
        releases/<id>/                one folder per release (the newest 3 are kept)
            .htaccess -> ../../shared/.htaccess
            uploads   -> ../../shared/uploads
        current -> releases/<id>
        shared/.htaccess              the site's settings: DB_*, CI_ENV, MAINTENANCE_FILE
        shared/uploads/               uploaded files
        shared/maintenance.on         only exists while maintenance is on
        logs/deploy.log               every step of every deploy, with UTC times
        logs/history.log              one line per deploy
        public_html.pre-deploy/       the old public_html folder, kept after the first deploy
                                      until the drills pass, then deleted (DEV: 19 Nov 2026)
    DO_NOT_UPLOAD_HERE, .builds/      Hostinger's own; leave them alone
```

Nothing in the scripts names the app: `deploy.sh` takes its app folder from
where it is installed (the folder above its `bin/`). Another app gets its
own `~/apps/<app>/`, with its own copy of the scripts and **its own deploy
key**, forced to that app's `gate.sh`. One app's key can never deploy
another app.

The web server only serves `public_html`, so nothing in `deploy/` outside
the live release can be reached from the web. Inside the release, the
`.htaccess` keeps itself from being served.

The links inside a release are relative, so the whole `deploy/` folder can
be copied or moved without fixing them. Only `public_html` holds an absolute
path.

The release id is the first 12 characters of the commit sha. Each release
also has a `REVISION` file with the full sha.

## What a deploy does

`deploy.sh <site> <release-id>` reads the tarball from stdin. It reads the
site's domain from `bin/sites/<site>.conf` and takes a lock, so two deploys
of one site never overlap. Then:

1. **Checks** the layout and the settings in `shared/.htaccess`. It refuses
   if maintenance is already on, if `MAINTENANCE_FILE` isn't in `shared/`,
   if the live release's `.htaccess` or `uploads` doesn't lead to this
   site's `shared/` (e.g. a release copied from another folder with its old
   links), or if the release id already exists. If that release is the live one (a
   re-run), it changes nothing and ends with result `already-live`.
2. **Unpacks** into `releases/<id>/` and links in the shared `.htaccess` and
   `uploads/`.
3. **Turns maintenance on**: creates the `MAINTENANCE_FILE`. On the very
   first deploy it also replaces the `public_html` folder with a link to
   `deploy/current`, keeping the folder as `deploy/public_html.pre-deploy`
   (deleted by hand once the drills pass; see "Drills").
4. **Backs up** the database (`mysqldump --single-transaction --routines
   --triggers`, gzipped) into `~/apps/<app>/backups/<site>/`. The backup
   must pass `gzip -t`, be at least 1 KB and end with mysqldump's "Dump
   completed" line.
5. **Migrates**: `php index.php migrate` in the new release.
6. **Switches** `current` to the new release, atomically (`ln -s` + `mv -T`).
7. **Checks Health**: `https://<domain>/index.php/Health` must say
   `"status":"ok"`, the release's `VERSION` and its `REVISION` (full commit
   sha). That shows the new release is what the site actually serves, not an
   older folder or a cached page. It tries for about 3 minutes (18 times,
   10 s apart), because PHP workers may keep running the old release for a
   while: PHP's realpath cache can resolve the old folder for up to 2
   minutes, and opcache can reuse the old compiled files until it next
   checks them (`opcache.revalidate_freq`). To shorten that, the deploy
   touches `~/.lsphp_restart.txt` after every link change, which asks
   LiteSpeed to restart the account's PHP workers.
8. **Turns maintenance off**, then removes old releases and backups.

If any step after 3 fails, it **rolls back**:

- it points `current` back to the previous release (or, on the first
  deploy, puts the original `public_html` folder back);
- if migrations had started, it restores the database from the backup and
  drops tables the failed migration added;
- it removes the failed release folder;
- it turns maintenance off;
- it exits non-zero, so the Actions run goes red.

If it can't restore the database, it **leaves maintenance on** so nobody
works on a half-restored database. That includes not being able to list or
drop the tables the failed migration added. The run then reports
`rollback-incomplete` (see below).

Database settings come from the `SetEnv` lines in `shared/.htaccess`, so
there is a single source of truth. They are never printed or logged, and
never put on a command line. `mysqldump` and `mysql` read them from a
temporary option file (mode 600, deleted on exit). `php` gets them as
environment variables of that one process. Database errors can name the
database or its user, so before anything is logged the deploy replaces the
database name, user and password with `<db>`, `<db-user>` and
`<db-password>` (the log also ends up in the Actions log).

Migrations stop at the first failed query in every environment, production
included: the `Migrate` controller turns `db_debug` on for its run, so a
failed query ends `php index.php migrate` with a non-zero exit before the
migration is recorded as applied. That covers queries on the app's default
database connection (`$this->db`); a migration that opens a second
connection must turn `db_debug` on for it too.

The server has no `/dev/fd`, so the scripts must not use bash process
substitution (`<(...)`); `tests/deploy/deploy-test.sh` checks this.

If the SSH connection drops mid-deploy, the deploy still finishes (or rolls
back) on the server. Its log is in `deploy.log`.

## One-time setup

Example for AdminTool's `dev` site. `<user>` is the SSH user
(`u738067934`).

1. The app's folder (once per app). Copy `gate.sh` and `deploy.sh` from
   this folder with LF line endings:

   ```sh
   mkdir -p ~/apps/admintool/bin/sites ~/apps/admintool/backups
   chmod 700 ~/apps/admintool ~/apps/admintool/bin ~/apps/admintool/backups
   # copy gate.sh and deploy.sh into ~/apps/admintool/bin, then:
   chmod 700 ~/apps/admintool/bin/gate.sh ~/apps/admintool/bin/deploy.sh
   ```

2. The site (once per site):

   ```sh
   D=~/domains/admin-dev.englishhubbali.com
   echo dev >> ~/apps/admintool/bin/sites.allow
   echo domain=admin-dev.englishhubbali.com > ~/apps/admintool/bin/sites/dev.conf
   mkdir -p ~/apps/admintool/backups/dev "$D/deploy/releases" "$D/deploy/shared" "$D/deploy/logs"
   chmod 700 ~/apps/admintool/backups/dev "$D/deploy/logs"
   ```

3. Shared files. Use the site's current `.htaccess`, plus one line:

   ```sh
   D=~/domains/admin-dev.englishhubbali.com
   cp -p "$D/public_html/.htaccess" "$D/deploy/shared/.htaccess"
   chmod 644 "$D/deploy/shared/.htaccess"
   echo "SetEnv MAINTENANCE_FILE $D/deploy/shared/maintenance.on" >> "$D/deploy/shared/.htaccess"
   cp -a "$D/public_html/uploads" "$D/deploy/shared/uploads"
   ```

   The web server must be able to read `.htaccess`, so it is 644, not 600.
   The deploy reads `DB_HOST`, `DB_PORT` (optional), `DB_USER`, `DB_PASS`,
   `DB_NAME`, `CI_ENV` and `MAINTENANCE_FILE` from it. `MAINTENANCE_FILE`
   must be an absolute path in `deploy/shared/`.

4. The app's GitHub key (once per app). Make a new ed25519 key pair with no
   passphrase, used only for this app. Add its public key to
   `~/.ssh/authorized_keys` by hand (not through hPanel), on one line:

   ```
   command="/home/<user>/apps/admintool/bin/gate.sh",restrict ssh-ed25519 AAAA... admintool-github-actions-deploy-dev
   ```

   `restrict` turns off port forwarding, agent forwarding, X11 and the TTY.
   `command=` means the key can only ever run that app's `gate.sh`. Even
   `ssh ... bash` just runs `gate.sh`, which refuses it.

5. GitHub (Settings > Environments > `dev`), these secrets:

   | Secret | Value |
   | --- | --- |
   | `DEPLOY_SSH_KEY` | the private key, whole file |
   | `DEPLOY_HOST` | `145.79.14.215` |
   | `DEPLOY_PORT` | `65002` |
   | `DEPLOY_USER` | `<user>` |
   | `DEPLOY_KNOWN_HOSTS` | the server's host key line, e.g. from `ssh-keygen -F '[145.79.14.215]:65002'` on a machine that already trusts it |

   In the environment's **Deployment branches and tags**, allow only `main`.
   Manual runs then always use the workflow file from `main`; the code to
   deploy is chosen with the `ref` input.

6. Turn off Hostinger's Git auto-deploy for the site **before** the first
   deploy. Otherwise Hostinger would write into the release folder through
   the `public_html` link.

7. First deploy: Actions > Deploy > Run workflow, "Use workflow from"
   `main`, ref `main`. Manual runs work without any further setting.

8. Automatic deploys (after Tests passes for a push to `main`) stay off
   until Settings > Secrets and variables > Actions > Variables has
   `DEPLOY_DEV_ENABLED` = `true`. Set it only after the first deploy and
   the failure drills pass. Setting it to anything other than `true` (GitHub
   ignores upper/lower case, so `True` counts as `true`), or deleting it,
   switches automatic deploys off again; manual runs keep working.

   To stop all deploys, manual ones included: Actions > Deploy > ⋯ >
   Disable workflow. Or, on the server, remove the key's line from
   `~/.ssh/authorized_keys`.

## Logs

- `~/domains/<domain>/deploy/logs/deploy.log`: every step, e.g.
  `2026-11-18T05:05:02Z [dev 80bc554e1a2b] c. Backup pre-deploy-...sql.gz (48213 bytes)`.
- `~/domains/<domain>/deploy/logs/history.log`: one line per deploy, e.g.
  `2026-11-18T05:05:40Z site=dev release=80bc554e1a2b sha=80bc554... version=1.1.1 backup=pre-deploy-20261118T050505Z-80bc554e1a2b.sql.gz result=success`.
  The result is `success`, `already-live` (re-run, nothing changed),
  `rolled-back`, `failed` (refused before anything changed) or
  `rollback-incomplete`.
- `~/apps/<app>/bin/gate.log`: refused SSH commands.
- In GitHub: the Deploy run's log shows the same steps, and its summary
  shows site, version, commit, backup and result.

A deploy refused before it knows the site's folder (no `sites/<site>.conf`,
or an invalid domain in it) is only in the Actions log.

## When a rollback is incomplete

The run says `rollback-incomplete` and the site shows the maintenance page.
Over SSH, with `S=~/domains/<domain>/deploy`:

1. Read the end of `$S/logs/deploy.log` to see what failed.
2. Check `current` points to the release you want: `readlink $S/current`.
   If not:
   `ln -sfn releases/<id> $S/current.new && mv -T $S/current.new $S/current`.
3. Restore the database from the backup named in the log. `mysql` reads the
   login from an option file you create (mode 600) with the `DB_*` values
   from `$S/shared/.htaccess`:
   `gzip -dc ~/apps/<app>/backups/<site>/<backup> | mysql --defaults-extra-file=<that file> <DB_NAME>`.
   Delete the option file afterwards.
4. Check `https://<domain>/index.php/Health` says `"status":"ok"`.
5. Turn maintenance off: `rm $S/shared/maintenance.on`.

## Updating the server scripts

A deploy never updates `deploy.sh` or `gate.sh` themselves: it only ships the
app. After any merge that changes `deploy/server/deploy.sh` or
`deploy/server/gate.sh`, install them by hand into `~/apps/<app>/bin/`
(example for `deploy.sh`; `gate.sh` the same way):

1. On your machine, on an up-to-date `main`: note the file's sha256
   (`sha256sum deploy/server/deploy.sh`) and check it has no CR line endings
   (`tr -cd '\r' < deploy/server/deploy.sh | wc -c` must print `0`).
2. On the server, make sure no deploy is running and maintenance is off:
   `flock -n ~/domains/<domain>/deploy/.deploy.lock true` must succeed for
   every site of the app, and no `deploy/shared/maintenance.on` exists.
3. Back up the installed file: `cp -p deploy.sh deploy.sh.bak-<date>` (in
   `~/apps/<app>/bin/`, then `chmod 600` it so it can't be run).
4. Upload to a temporary name (`.deploy.sh.new`) and check before replacing
   anything: its sha256 equals the one from step 1, it has no CR characters,
   and `bash -n .deploy.sh.new` passes.
5. `chmod 700 .deploy.sh.new && mv -f .deploy.sh.new deploy.sh`, then check
   the installed sha256 once more.
6. Delete the backup once the next deploy has worked.

`gate.sh` is what the GitHub key runs, so a change to it takes effect on the
next SSH connection. After replacing it, re-run the refusal tests (no
command, `bash`, `deploy dev ../x`, `deploy prod x`) with the deploy key and
check they appear in `bin/gate.log`.

To see whether the installed scripts are current, compare their sha256 with
the files on `main`.

## Going back to Hostinger's Git deploy

The old `public_html` folder (`deploy/public_html.pre-deploy`) is deleted
once the drills pass, so going back means turning the live release into a
plain folder and letting Hostinger's Git deploy take over again. Example
for DEV:

```sh
cd ~/domains/admin-dev.englishhubbali.com
# A self-contained copy of the live release: -L turns the .htaccess and
# uploads links into real files.
cp -aL deploy/current public_html.git
# Without REVISION, Health shows "revision":"unknown" instead of a commit
# that Hostinger's deploys would no longer match.
rm public_html.git/REVISION
# MAINTENANCE_FILE points into deploy/, which is going away.
sed -i '/SetEnv[[:space:]]\+MAINTENANCE_FILE/d' public_html.git/.htaccess
# The site is unavailable for the moment between these two commands.
rm public_html && mv public_html.git public_html
```

Then:

1. Check `https://<domain>/index.php/Health` says `"status":"ok"` and the
   login page loads.
2. In hPanel, connect the site's Git deployment again (repository, branch
   `main`, into `public_html`), deploy once and turn auto-deploy back on.
   Check Health again afterwards.
3. Remove the deploy key's line from `~/.ssh/authorized_keys` and the site
   from `~/apps/<app>/bin/sites.allow` and `bin/sites/<site>.conf`.
4. Delete `deploy/` and `~/apps/<app>/backups/<site>/` once nothing in them
   is needed. Uploads made since the switch are in the copied `uploads/`.

## Drills

Before a site relies on this deploy, run these drills on it from
Actions > Deploy > Run workflow, each with a throwaway branch as `ref`
(never merged, deleted afterwards). Take a baseline of the database just
before each run (the list of tables, the migration version, a checksum of
every table) and compare it afterwards.

| Drill | Throwaway branch | Must end with |
| --- | --- | --- |
| C1 normal deploy | an empty commit (same code, new commit sha) | green, `success`; Health shows the new commit; the previous release kept |
| C2 broken migration | a migration (timestamp newer than the latest) that adds a table, changes one row of an unused table, then runs invalid SQL | red, `rolled-back`; "Dropping table … (not in the backup)" in `deploy.log`; the same tables, migration version, row and checksums as the baseline |
| C3 failed health check | `Health.php` reports a wrong revision: `'revision' => str_repeat('0', 40),` | red, `rolled-back` after the Health tries (about 3 minutes of maintenance page); `deploy.log` shows each try with the wrong revision; the database as the baseline |

A branch that only writes a different `REVISION` file doesn't make the
health check fail: Health reads that same file from the release it serves,
so the two always agree. Use a broken Health instead, as above.

If people use the site during the drills, a checksum can differ because of
their changes; compare with the backup the drill's deploy took instead. See
also "Known issues" for `tagihan`.

**Every PROD site must pass C1, C2 and C3 before it switches from
Hostinger's Git deploy to this one.**

### Record: DEV, 19 Nov 2026

All on `admin-dev.englishhubbali.com`, in the layout described above.

| Drill | Release | Result |
| --- | --- | --- |
| C1 normal deploy | `7ee63bb490ea` | ✅ `success`. Health ok on the first try, about 4 s of maintenance |
| C2 broken migration, 1st run | `3803aed2fc23` | ❌ `rolled-back` and the changed row restored, but **the added table was not dropped**, while the log said "Database restored". Cause: the restore used bash process substitution (`<(...)`), which needs `/dev/fd`; the server has none, so the list of tables to drop came back empty. CI has `/dev/fd`, so the tests passed. The table was dropped by hand. |
| | | Fixed in PR #26: temporary files instead of process substitution, a restore that can't list or drop tables fails (`rollback-incomplete`, maintenance on), database names masked in the logs, and a test that the server scripts use no process substitution. |
| C2 re-run, fixed `deploy.sh` | `c619a13d68f3` | ✅ `rolled-back`, "Dropping table drill_c2_tmp (not in the backup)", database identical to the baseline (34 tables, all checksums) |
| C3 failed health check | `445aff03f898` | ✅ `rolled-back` after 18 tries with the wrong revision, about 3 minutes of maintenance; database identical to the baseline except the `tagihan` effect in "Known issues" |

Also seen during the drills: re-running the live release ends `already-live`
without changes, and two manual runs queue instead of overlapping.

## Known issues

- **Restoring a backup changes how some `tagihan.tipe` values are stored.**
  `tagihan.tipe` is `enum('Biaya Kelas','Biaya Registrasi','Biaya Buku','')`.
  For invoices with mixed item types the app writes a combined value (e.g.
  "Biaya Kelas, Biaya Buku"), which isn't one of the options; the database
  runs without strict SQL mode, so it stores MySQL's error value (index 0,
  shown as `''`). A dump writes it as `''`, so after a restore it is the
  real `''` option (index 4). The page looks the same and `WHERE tipe = ''`
  matches both, but `tipe+0`, sorting and grouping by `tipe`, and
  `CHECKSUM TABLE` change. Compare row data, not checksums, around a
  restore of `tagihan`. Tracked separately.

## Tests

- `bash tests/deploy/gate-test.sh`: which SSH commands `gate.sh` accepts.
  Plain bash, runs anywhere, including Git Bash on Windows.
- `bash tests/deploy/deploy-test.sh`: real deploys, rollbacks and refusals
  against a scratch database and a `php -S` site, laid out as above. Linux
  only. Runs in CI as the `deploy-scripts` job of the Tests workflow.
