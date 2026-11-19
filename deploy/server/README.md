# SSH deploy: server side

The GitHub Actions workflow `.github/workflows/deploy.yml` deploys a site by
sending a release tarball over SSH; `.github/workflows/rollback.yml` rolls
it back to an earlier release by hand. On the server, these scripts do the
work:

| File | Installed as | Role |
| --- | --- | --- |
| `gate.sh` | `~/apps/admintool/bin/gate.sh` | The only command AdminTool's GitHub key may run. Accepts exactly `deploy <site> <release-id>` or `rollback <site> <release-id\|previous> <code\|code-db>`, for sites listed in `sites.allow` next to it. Logs anything else to `gate.log` next to it and refuses it. |
| `deploy.sh` | `~/apps/admintool/bin/deploy.sh` | Unpacks the release and deploys it, rolling back by itself if anything fails. |
| `rollback.sh` | `~/apps/admintool/bin/rollback.sh` | Rolls the live release back to a kept one, by hand (see "Rolling back a live release"). |
| `deploy-lib.sh` | `~/apps/admintool/bin/deploy-lib.sh` | Helpers both scripts load: logging and masking, the site checks, backups and restore, the switch, the Health check. Never run on its own. |

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
    bin/                              gate.sh, deploy.sh, rollback.sh (mode 700),
                                      deploy-lib.sh (mode 600), sites.allow, gate.log
        sites/dev.conf                domain=admin-dev.englishhubbali.com
                                      ci_env=testing
    backups/dev/                      pre-deploy-<UTC time>-<id>.sql.gz
                                      (mode 600; the newest 5 are kept)
                                      pre-rollback-<UTC time>-<id>.sql.gz
                                      (mode 600; never deleted automatically)

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
site's `domain` and `ci_env` from `bin/sites/<site>.conf` and takes a lock,
so two deploys of one site never overlap. Then:

1. **Checks** the layout and the settings in `shared/.htaccess`. It refuses
   if maintenance is already on, if `MAINTENANCE_FILE` isn't in `shared/`,
   if the live release's `.htaccess` or `uploads` doesn't lead to this
   site's `shared/` (e.g. a release copied from another folder with its old
   links), or if the release id already exists. If that release is the live one (a
   re-run), it changes nothing and ends with result `already-live`.

   It also refuses, before changing anything, unless the site config has
   `ci_env=` set to exactly `production`, `testing` or `development`, **and**
   `SetEnv CI_ENV` in `shared/.htaccess` says the same. The site config says
   what the site must be; the `.htaccess` says what the app actually runs
   as. A copied or half-edited `.htaccess` can't turn DEV into a site that
   sends real WhatsApp messages (only `production` sends, see
   `docs/OUTBOUND_MESSAGES.md`), nor PROD into one that silently doesn't.
   The message says what to fix. From the `.htaccess` it only ever names
   one of those three values, never anything else.

   **What this can't catch:** both set to `production` on a site that isn't
   PROD. Then the deploy can't tell it apart from a real PROD site: it keeps
   the WhatsApp token and expects `"outbound":"live"`, and the app sends
   real messages, to real students if the database is a copy of PROD's.
   Never set `ci_env=production` (or `SetEnv CI_ENV production`) on a site
   whose database isn't the real one, and treat changing a site's `ci_env`
   like a change on PROD.
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
5. **Migrates**: `php index.php migrate` in the new release. Then, **only
   where `ci_env` isn't `production`**, it overwrites the WhatsApp token:
   `UPDATE data_konfig SET token_wa = 'DISABLED-NON-PROD'`, logged as
   "WhatsApp token in data_konfig set to DISABLED-NON-PROD". A DEV database
   is often a copy of PROD's, with PROD's real token in it; the app never
   sends outside production anyway, and this makes the token unusable from
   that database too. A failure here fails the deploy and rolls back like
   any other step. If the deploy rolls back later, the restore puts the
   database back as it was before the deploy, token included. If email/SMTP
   settings are ever stored in the database, `deploy.sh` must clear them in
   the same place.

   The backups still have the token for a while: the deploy backs up the
   database (step 4) **before** it overwrites the token, so that a rollback
   can put everything back exactly. The first backup taken by a deploy
   with this step, and every backup from before, still holds the real
   token. They are mode 600 in `~/apps/<app>/backups/<site>/` and only
   reachable with the SSH login, and they age out as the newest 5 are kept.
   To have the real token gone from the server right away, delete the
   site's older backups once the first deploy with this step has worked.
6. **Switches** `current` to the new release, atomically (`ln -s` + `mv -T`).
7. **Checks Health**: `https://<domain>/index.php/Health` must say
   `"status":"ok"`, the release's `VERSION` and its `REVISION` (full commit
   sha), and `"outbound":"live"` where `ci_env` is `production`,
   `"outbound":"blocked"` everywhere else. That shows the new release is
   what the site actually serves, not an older folder or a cached page, and
   that it runs as the kind of site it should: a PROD site whose web server
   ignores its `SetEnv CI_ENV production` would answer `"blocked"` and
   silently stop sending, so the deploy rolls back instead.

   Code from before PR #29 (`be64b3d`) has no `outbound` field, so it can't
   be deployed any more: a manual run with such an old `ref` fails Health
   (`outbound='missing'` in the log) and rolls back. Normal deploys of
   `main` aren't affected, and neither is the rollback after a failed
   deploy, which goes back to the previous release folder without a Health
   check. To go back to older behaviour, revert the change in `main` and
   deploy that. It tries for about 3 minutes (18 times,
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
   printf 'domain=admin-dev.englishhubbali.com\nci_env=testing\n' > ~/apps/admintool/bin/sites/dev.conf
   chmod 600 ~/apps/admintool/bin/sites/dev.conf
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
   must be an absolute path in `deploy/shared/`. `SetEnv CI_ENV` must match
   `ci_env` in `bin/sites/<site>.conf` (step 2): `testing` for DEV,
   `production` for a PROD site.

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
  `2026-11-18T05:05:02Z [dev 80bc554e1a2b] c. Backup pre-deploy-...sql.gz (48213 bytes)`,
  and on a site that isn't production
  `... d. WhatsApp token in data_konfig set to DISABLED-NON-PROD (ci_env=testing; 1 row(s) changed)`
  (`0 row(s)` once it already was).
- `~/domains/<domain>/deploy/logs/history.log`: one line per deploy, e.g.
  `2026-11-18T05:05:40Z site=dev release=80bc554e1a2b sha=80bc554... version=1.1.1 backup=pre-deploy-20261118T050505Z-80bc554e1a2b.sql.gz previous=7ee63bb490ea result=success`.
  `previous=` is the release that was live before (`-` on the first
  deploy); deploys from before rollbacks existed don't have it. The result
  is `success`, `already-live` (re-run, nothing changed), `rolled-back`,
  `failed` (refused before anything changed) or `rollback-incomplete`.
  A rollback writes one line too, with `action=rollback`, e.g.
  `... site=dev action=rollback type=code from=80bc554e1a2b to=7ee63bb490ea sha=7ee63bb... version=1.1.1 backup= result=rolled-back-manual`.
- `~/apps/<app>/bin/gate.log`: refused SSH commands.
- In GitHub: the Deploy and Rollback runs' logs show the same steps, and
  their summaries show the result.

A deploy refused before it knows the site's folder (no `sites/<site>.conf`,
or an invalid domain in it) is only in the Actions log.

## Rolling back a live release

When a release is live but turns out to be broken, roll back to the release
before it: **Actions > Rollback > Run workflow**, "Use workflow from"
`main`:

| Input | What to enter |
| --- | --- |
| site | `dev` |
| type | `code only`, or `code and database` (see below) |
| release | leave empty for the previous release, or a release id from a Deploy summary (any release still on the server, `code only`) |
| confirm | `dev` again; anything else stops the run before it connects |

It runs `rollback.sh` on the server, which shares the deploy lock (a
rollback and a deploy never overlap, and the workflow waits for a running
deploy). The steps: checks, maintenance on, (database), switch `current`,
PHP restart, Health with the target's `VERSION`, `REVISION` and `outbound`,
maintenance off.

**Which type:**

- **`code only`**: only the code goes back; the database stays as it is,
  with everything entered since. Use it when the broken release didn't
  change the database. It is refused when the database has migrations the
  target release doesn't know ("use code and database, or ask a
  developer"); old code on a newer database can break in ways Health
  doesn't see. It can also go forward again to a newer release that is
  still on the server.
- **`code and database`**: the code goes back to the **previous** release
  (the one that was live when the broken one was deployed) and the
  database goes back to how it was then: the broken release's pre-deploy
  backup. **Everything written to the database since that deploy is lost
  from the live database** (invoices, attendance, anything). It is saved
  first in `~/apps/<app>/backups/<site>/pre-rollback-<UTC time>-<id>.sql.gz`
  (mode 600, checked like a deploy's backup), and tables the broken
  release's migrations added are dropped. Only allowed when the live
  release became live through a successful deploy, not through a rollback
  (otherwise "the live release came from a rollback; its backup is too old
  to restore safely; ask a developer").

**It refuses, before changing anything,** a release that isn't on the server,
the live release, `code and database` to anything but the previous release
or without a valid pre-deploy backup, `code only` with a database that is
ahead of the target, "previous" when the live release's deploy didn't record
one (deploys from before rollbacks existed), a release from before the
WhatsApp/email guard (no `outbound_is_live()` in
`application/helpers/outbound_helper.php`: it could send real messages
again), and anything the deploy also refuses (`ci_env`, maintenance already
on, a deploy running).

**Results** (in the run's summary and `history.log`):

| Result | Meaning |
| --- | --- |
| `rolled-back-manual` | Done: the target is live. |
| `rollback-failed` | Something failed after maintenance went on (e.g. Health): the code is back on the release that was live, and for `code and database` the pre-rollback backup was restored. Maintenance is off. The run is red. |
| `rollback-incomplete` | As above, but putting things back failed too (e.g. restoring the pre-rollback backup). **Maintenance is left on**; fix it as in "When a rollback is incomplete". |
| `failed` | Refused; nothing changed. The log says why. |

**Afterwards:**

- **Stop the broken code coming back.** While `DEPLOY_DEV_ENABLED` is on,
  the next merge into `main` deploys whatever `main` holds. Revert the
  broken change in `main` (GitHub's "Revert" button on its PR), or switch
  automatic deploys off first.
- **The same commit can't simply be deployed again:** the deploy refuses a
  release id that is on the server but not live. Go forward with a new
  commit in `main`, or with a `code only` rollback to the newer release.
- **Pre-rollback backups are never deleted automatically** (pre-deploy
  backups are: the newest 5 are kept). Delete them by hand once you're sure
  you won't need the data in them; they contain everything that was in the
  database, so keep them out of sight like the other backups.
- Releases from before the outbound gate can't be rolled back to at all,
  and on DEV the releases from before this rollback was installed have no
  `previous=` recorded: name the release, `code only`.

**In an emergency, without GitHub** (over SSH with your own login):

```sh
~/apps/admintool/bin/rollback.sh dev previous code       # or <release-id>, and/or code-db
```

It does exactly what the workflow does, logs to the same `deploy.log` and
`history.log`, and prints the same `RESULT` line.

## When a rollback is incomplete

The run says `rollback-incomplete` and the site shows the maintenance page.
This can come from a failed deploy whose own rollback failed, or from a
rollback (`rollback.sh`) whose undo failed. Over SSH, with
`S=~/domains/<domain>/deploy`:

1. Read the end of `$S/logs/deploy.log` to see what failed.
2. Check `current` points to the release you want: `readlink $S/current`.
   If not:
   `ln -sfn releases/<id> $S/current.new && mv -T $S/current.new $S/current`.
3. Restore the database from the backup named in the log (after a failed
   deploy: its pre-deploy backup; after a failed rollback: the
   pre-rollback backup, i.e. the database as it was just before the
   rollback). `mysql` reads the
   login from an option file you create (mode 600) with the `DB_*` values
   from `$S/shared/.htaccess`:
   `gzip -dc ~/apps/<app>/backups/<site>/<backup> | mysql --defaults-extra-file=<that file> <DB_NAME>`.
   Delete the option file afterwards.
4. Check `https://<domain>/index.php/Health` says `"status":"ok"`.
5. Turn maintenance off: `rm $S/shared/maintenance.on`.

## Updating the server scripts

A deploy never updates `deploy.sh`, `deploy-lib.sh`, `rollback.sh` or
`gate.sh` themselves: it only ships the app.

**The change that added rollbacks (EHU-0023-5)** changes all four: after it
is merged, install `deploy-lib.sh` (mode 600) and `rollback.sh` (mode 700)
first, then `deploy.sh`, then `gate.sh`, each as below, and re-run the
refusal tests. `deploy.sh` needs `deploy-lib.sh` next to it (without it, a
deploy is refused before anything changes). Then run drill C1 again on DEV,
because `deploy.sh` itself changed, and the rollback drills R1–R3 (see
"Drills").

**The change that added `ci_env` (EHU-0023-6):** after it
is merged, `bin/deploy.sh` must be copied to the server by hand as below,
and **first** each site's `bin/sites/<site>.conf` needs its `ci_env=` line
(`ci_env=testing` in `dev.conf`). In that order nothing breaks: the old
`deploy.sh` ignores the new line, and the new one refuses (changing nothing)
on a site without it. After any merge that changes `deploy/server/deploy.sh` or
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
command, `bash`, `deploy <site> ../x`, `deploy <a site not in sites.allow> x`)
with the deploy key and check they appear in `bin/gate.log`.

To see whether the installed scripts are current, compare their sha256 with
the files on `main`.

## Going back to Hostinger's Git deploy

The old `public_html` folder (`deploy/public_html.pre-deploy`) is deleted
once the drills pass, so going back takes two parts: first make the live
release a plain folder (a working fallback that needs neither `deploy/` nor
this deploy), then let Hostinger's Git deploy fill `public_html` again.
Example for DEV.

**Part 1: the live release as a plain folder.** Make sure no deploy is
running first.

```sh
cd ~/domains/admin-dev.englishhubbali.com
# A self-contained copy of the live release: -L turns the .htaccess and
# uploads links into real files.
cp -aL deploy/current public_html.copy
# Without REVISION, Health shows "revision":"unknown" instead of a commit
# that nothing deploys any more.
rm public_html.copy/REVISION
# MAINTENANCE_FILE points into deploy/, which is going away.
sed -i '/SetEnv[[:space:]]\+MAINTENANCE_FILE/d' public_html.copy/.htaccess
# The site is unavailable for the moment between these two commands.
rm public_html && cp -a public_html.copy public_html
```

Check `https://<domain>/index.php/Health` says `"status":"ok"` and the login
page loads. The site now runs from a plain folder; `public_html.copy` stays
as the copy to come back to.

**Part 2: Hostinger's Git deploy.** The copy has no `.git` folder (releases
never contain one), so Hostinger can't just pull into it, and as far as we
know hPanel only sets up a Git deployment into an **empty** folder. Check
that in hPanel before you start. If it's so, the site is down from step 1
until step 4:

1. `rm -rf public_html && mkdir public_html` (the copy is in
   `public_html.copy`).
2. In hPanel > Git, set up the site's repository again (branch `main`, into
   `public_html`) and deploy.
3. Put the site's own files back from the copy:
   `cp -p public_html.copy/.htaccess public_html/.htaccess` and
   `rsync -a public_html.copy/uploads/ public_html/uploads/`.
4. Check Health and the login page, then turn auto-deploy back on.

If the Hostinger deploy fails, go back to the copy:
`rm -rf public_html && cp -a public_html.copy public_html`.

Afterwards:

1. Remove the deploy key's line from `~/.ssh/authorized_keys` and the site
   from `~/apps/<app>/bin/sites.allow` and `bin/sites/<site>.conf`.
2. Delete `public_html.copy`, `deploy/` and `~/apps/<app>/backups/<site>/`
   once nothing in them is needed.

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

The rollback drills, from Actions > Rollback > Run workflow (see "Rolling
back a live release"), right after C1 so the live release has a recorded
previous release:

| Drill | How | Must end with |
| --- | --- | --- |
| R1 code only | after C1: type `code only`, release empty | green, `rolled-back-manual`; Health shows the previous release's commit; database as the baseline |
| R2 code and database | deploy a throwaway branch whose migration adds a table and changes one row of an unused table (and succeeds), then type `code and database`, release empty | green, `rolled-back-manual`; a `pre-rollback-…` backup (mode 600); the table gone, the row and the migration version as before the deploy |
| R3 refusals | `confirm` other than the site; `code only` after a deploy like R2's; `code and database` right after R1 (the live release came from a rollback) | each stopped or refused (`failed`) with its message, nothing changed |

If people use the site during the drills, a checksum can differ because of
their changes; compare with the backup the drill's deploy took instead. See
also "Known issues" for `tagihan`.

**Every PROD site must pass C1, C2, C3 and R1–R3 before it switches from
Hostinger's Git deploy to this one.** On PROD the drills touch real users
and real data, so:

- run them at a quiet time and announce them: C2 and C3 show the
  maintenance page, C3 for about 3 minutes;
- for C2, change one row of a table that site doesn't use, and look up
  which one on that site's own database first;
- take the baseline right before each run;
- C2 and C3 restore the site's database, which also changes how some
  `tagihan.tipe` values are stored (see "Known issues"); expect that in the
  comparison.

After all three drills pass on a site, delete its
`deploy/public_html.pre-deploy`; from then on, going back works as described
in "Going back to Hostinger's Git deploy".

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
  "Biaya Kelas, Biaya Buku"), which isn't one of the options; DEV's database
  runs without strict SQL mode (PROD's probably too; not checked), and the
  app sets `stricton => FALSE`, so it stores MySQL's error value (index 0,
  shown as `''`). A dump writes it as `''`, so after a restore it is the
  real `''` option (index 4). The page looks the same and `WHERE tipe = ''`
  matches both, but `tipe+0`, sorting and grouping by `tipe`, and
  `CHECKSUM TABLE` change. Compare row data, not checksums, around a
  restore of `tagihan`. Not fixed yet: parked until the deploy work
  (EHU-0023) is done, then to get its own Solava task.

## Tests

- `bash tests/deploy/gate-test.sh`: which SSH commands `gate.sh` accepts.
  Plain bash, runs anywhere, including Git Bash on Windows.
- `bash tests/deploy/deploy-test.sh`: real deploys, automatic and manual (`rollback.sh`) rollbacks, and refusals
  against a scratch database and a `php -S` site, laid out as above. Linux
  only. Runs in CI as the `deploy-scripts` job of the Tests workflow.
