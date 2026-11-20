# SSH deploy: server side

The GitHub Actions workflow `.github/workflows/deploy.yml` deploys a site by
sending a release tarball over SSH; `.github/workflows/rollback.yml` rolls
it back to an earlier release by hand. On the server, these scripts do the
work:

| File | Installed as | Role |
| --- | --- | --- |
| `gate.sh` | `~/apps/admintool/bin/gate.sh` | The only command AdminTool's GitHub keys may run. Accepts exactly `deploy <site> <release-id>` or `rollback <site> <release-id\|previous> <code\|code-db>`: with the DEV key (no profile) for sites in `sites.allow` next to it, never one whose `ci_env` is `production`; with the PROD key (`gate.sh prod`) only for sites in `sites.allow.prod` whose `ci_env` is `production`. Logs anything else to `gate.log` next to it and refuses it. |
| `deploy.sh` | `~/apps/admintool/bin/deploy.sh` | Unpacks the release and deploys it, rolling back by itself if anything fails. |
| `rollback.sh` | `~/apps/admintool/bin/rollback.sh` | Rolls the live release back to a kept one, by hand (see "Rolling back a live release"). |
| `deploy-lib.sh` | `~/apps/admintool/bin/deploy-lib.sh` | Helpers both scripts load: logging and masking, the site checks, backups and restore, the switch, the Health check. Never run on its own. |

DEV (`dev`, admin-dev.englishhubbali.com) and both PROD sites (`pemogan`,
adminpemogan.englishhubbali.com, and `citarum`,
admincitarum.englishhubbali.com) use this. The PROD sites switched from
Hostinger's Git deploy on 20 Nov 2026 (see "Record: PROD switch, 20 Nov
2026"); Hostinger's Git auto-deploy is off on both.

## Where things are on the server

Two kinds of folders:

- **Per site**, inside the site's own hPanel folder, next to `public_html`.
  It shows in that site's File Manager and is deleted with the site.
- **Per app**, things that must outlive a site: the scripts, the list of
  sites, and the database backups.

```
~/apps/admintool/                     one folder per app
    bin/                              gate.sh, deploy.sh, rollback.sh (mode 700),
                                      deploy-lib.sh (mode 600), gate.log,
                                      sites.allow (DEV key), sites.allow.prod (PROD key)
        sites/dev.conf                domain=admin-dev.englishhubbali.com
                                      ci_env=testing
        sites/pemogan.conf            domain=adminpemogan.englishhubbali.com
                                      ci_env=production   (citarum.conf the same way)
    backups/<site>/                   pre-deploy-<UTC time>-<id>.sql.gz
                                      (mode 600; the newest 5 are kept)
                                      pre-rollback-<UTC time>-<id>.sql.gz
                                      (mode 600; never deleted automatically)

~/domains/admin-dev.englishhubbali.com/
    public_html -> ~/domains/admin-dev.englishhubbali.com/deploy/current
    deploy/
        releases/<id>/                one folder per release (the newest 3 are kept, plus
                                      the live one and its recorded previous release)
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

## Switching a PROD site

PROD deploys run from the `production` branch (a release PR from `main`
into `production`, see `docs/RELEASING.md`). Pemogan switched first,
then Citarum the same evening (20 Nov 2026); the steps below are kept for
a new PROD site or a switch back and forth. Never print `.htaccess` values or keys while
doing this: names and set/not set only.

How the PROD part of `.github/workflows/deploy.yml` works:

- After **Tests** passes for a push to `production`, the job `prod-relay`
  starts **Deploy** again on `production` with the tested commit (a run
  started by Tests always carries `main` as its branch, which the PROD
  environments refuse). It only does so for the sites whose repository
  variable `DEPLOY_PROD_PEMOGAN_ENABLED` / `DEPLOY_PROD_CITARUM_ENABLED` is
  `true`; the others are listed as skipped in its summary.
- By hand: Actions > Deploy > Run workflow, "Use workflow from"
  `production`, `prod_sites` = `pemogan`, `citarum` or
  `pemogan then citarum`, `sha` empty (the tip of `production`). The
  variables don't matter for manual runs.
- Each site's job (`deploy-site.yml`) runs in its environment
  (`prod-pemogan`, `prod-citarum`): it waits for approval, then **stops
  during office hours** (08:00–17:59 WITA, refused from 07:30 so a deploy
  can't run into them) with "Office hours: re-run this after 18:00 WITA",
  before connecting. It checks out and deploys exactly the commit it was
  given, and only while that commit is still the tip of `production`: an
  older commit (a Tests run that finished late or was re-run) never
  replaces a newer release. Citarum runs only after Pemogan succeeded when
  both are chosen.
- A manual run from `main` with `prod_sites` other than `none (DEV run)`,
  or with `sha` set, stops instead of deploying DEV.
- Concurrency: one deploy per site at a time (`deploy-<site>`), one
  rollback per site at a time (`rollback-<site>`); a running one is never
  cancelled. Rollbacks have their own group because GitHub keeps only one
  waiting run per group, so a newly queued deploy would cancel a waiting
  emergency rollback. If a deploy and a rollback of one site reach the
  server at the same time, the server's lock refuses the second ("Another
  deploy of <site> is running") without changing anything; re-run it.

### Once, before the first PROD site

1. **`gate.sh` with profiles** installed on the server (see "Updating the
   server scripts"), refusal tests re-run.
2. **The PROD key.** A new ed25519 key pair with no passphrase, only for
   AdminTool's PROD deploys (e.g. `admintool_github_actions_deploy_prod`),
   added to `~/.ssh/authorized_keys` by hand on one line:

   ```
   command="/home/<user>/apps/admintool/bin/gate.sh prod",restrict ssh-ed25519 AAAA... admintool-github-actions-deploy-prod
   ```

   It reads `sites.allow.prod`, and only reaches sites whose
   `bin/sites/<site>.conf` says `ci_env=production`; the DEV key never
   reaches those, even if one is added to `sites.allow` by mistake.
   Create an empty `sites.allow.prod` (mode 600) for now. Refusal tests
   with both keys: DEV key `deploy pemogan x` and `deploy citarum x`
   refused; PROD key `deploy dev x`, `bash` and `deploy pemogan x`
   refused (not in `sites.allow.prod` yet); each in `gate.log`.
3. **GitHub environments** `prod-pemogan` and `prod-citarum` (Settings >
   Environments), each with:
   - Required reviewers: `aksaraBali99`. Leave "Prevent self-review" off:
     the person who starts a run must be able to approve it.
   - Deployment branches and tags: selected branches, `production` only.
   - Secrets: `DEPLOY_SSH_KEY` (the PROD private key, whole file),
     `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_KNOWN_HOSTS` (same
     values as in `dev`).
4. **Repository variables** `DEPLOY_PROD_PEMOGAN_ENABLED` and
   `DEPLOY_PROD_CITARUM_ENABLED`: leave them unset until a site is
   accepted (below).
5. **Turn off Hostinger's Git auto-deploy on both PROD sites** (hPanel >
   the site > Git) **before the release merge** from `main` into
   `production`, even for the site that isn't switched that evening.
   Otherwise Hostinger pulls the new code into the other site's
   `public_html` without running its migrations, and into the switched
   site's release folder through the `public_html` link. Until its own
   switch, the other site simply stays on the code it has.
6. **The release merge** (`docs/RELEASING.md`). Its Tests run starts
   `prod-relay`, which starts nothing while both variables are unset.

### Each site, on its evening (after 18:00 WITA)

Example for Pemogan; for Citarum use `admincitarum.englishhubbali.com` and
`citarum`.

1. **App folder:**

   ```sh
   echo pemogan >> ~/apps/admintool/bin/sites.allow.prod
   printf 'domain=adminpemogan.englishhubbali.com\nci_env=production\n' > ~/apps/admintool/bin/sites/pemogan.conf
   chmod 600 ~/apps/admintool/bin/sites/pemogan.conf
   D=~/domains/adminpemogan.englishhubbali.com
   mkdir -p ~/apps/admintool/backups/pemogan "$D/deploy/releases" "$D/deploy/shared" "$D/deploy/logs"
   chmod 700 ~/apps/admintool/backups/pemogan "$D/deploy/logs"
   ```

2. **Shared `.htaccess`**, from the site's current one:

   ```sh
   D=~/domains/adminpemogan.englishhubbali.com
   H="$D/deploy/shared/.htaccess"
   cp -p "$D/public_html/.htaccess" "$H"
   chmod 644 "$H"
   # Drop MIGRATE_TOKEN (the deploy runs migrations; without it the
   # Migrate URL is closed) and Pemogan's stray "htaccess:" line.
   sed -i -e '/^[[:space:]]*SetEnv[[:space:]]\{1,\}MIGRATE_TOKEN\([[:space:]]\|$\)/d' \
          -e '/^[[:space:]]*htaccess:/d' "$H"
   echo "SetEnv MAINTENANCE_FILE $D/deploy/shared/maintenance.on" >> "$H"
   # Check, names only:
   awk 'tolower($1)=="setenv" {print $2}' "$H" | sort | tr '\n' ' '; echo
   awk 'tolower($1)=="setenv" && $2=="CI_ENV" {v=$3} END {print (v=="production" ? "CI_ENV is production" : "CI_ENV is NOT production")}' "$H"
   grep -c '^[[:space:]]*htaccess:' "$H"
   ```

   Expect the names `CI_ENV DB_HOST DB_NAME DB_PASS DB_USER MAINTENANCE_FILE`
   (no `MIGRATE_TOKEN`), "CI_ENV is production" and `0`.
   **`SetEnv CI_ENV production` is already there on both sites**
   (preflight, 20 Nov 2026): check it, don't add it. It must match
   `ci_env=production` in `bin/sites/<site>.conf`, or the deploy refuses.

3. **Uploads:** make sure nobody is entering data, then **copy the uploads
   right before the deploy**, untracked files
   included (Pemogan has files in `uploads/` that aren't in Git; `cp -a`
   copies everything):

   ```sh
   D=~/domains/adminpemogan.englishhubbali.com
   cp -a "$D/public_html/uploads" "$D/deploy/shared/uploads"
   find "$D/public_html/uploads" -type f | wc -l; find "$D/deploy/shared/uploads" -type f | wc -l
   ```

   The two counts must be equal (preflight: Pemogan 4 files, Citarum 2).

4. **First deploy:** Actions > Deploy > Run workflow, "Use workflow from"
   `production`, `prod_sites` = `pemogan`, `sha` empty; approve the
   `prod-pemogan` job. `public_html` (a real folder with Hostinger's Git
   checkout) is moved to `deploy/public_html.pre-deploy` and replaced by a
   link; if anything fails, the old folder comes back and the database is
   restored (tested in `deploy-test.sh` sections 1 and 1b).
   Afterwards, `diff -rq "$D/deploy/public_html.pre-deploy/uploads" "$D/deploy/shared/uploads"`
   must print nothing (no upload landed between the copy and the deploy).

### Accepting a PROD site

A PROD site counts as switched when all of these pass:

| Check | How | Must show |
| --- | --- | --- |
| First deploy | the run above | green, `success` in the summary |
| Re-run | run the same Deploy again (same commit) | green, `already-live`, nothing changed |
| Health | `https://<domain>/index.php/Health` | `"status":"ok"`, `"version"` = `VERSION` of the release, `"revision"` = its full commit sha, `"migrations":"ok"`, `"outbound":"live"` |
| Backup | on the server: `ls -l ~/apps/admintool/backups/<site>/` and `gunzip -t <the pre-deploy file>` | a `pre-deploy-…-<release>.sql.gz`, mode 600, `gunzip -t` silent with exit 0 |

There is no separate WhatsApp test: Health's `"outbound":"live"` (which the
deploy itself also requires on a `ci_env=production` site) shows the app
runs as production, where WhatsApp and email are sent.

**Why these checks and not C1–C3 / R1–R4 on PROD:** PROD runs the same
`gate.sh`, `deploy.sh`, `rollback.sh` and `deploy-lib.sh` as DEV, where C1–C3
and R1–R4 passed (see "Record: deploy drills, DEV, 19 Nov 2026" and
"Record: rollback drills, DEV, 19 Nov 2026"), and the CI tests cover the
first deploy onto a real folder failing and rolling back. The drills
themselves need broken commits (a failing migration, a wrong revision) to
be deployed, which on PROD means putting them on `production`, the branch
that should only ever hold released code.

Once a site passes: set its repository variable
(`DEPLOY_PROD_PEMOGAN_ENABLED` or `DEPLOY_PROD_CITARUM_ENABLED`) to `true`.
From then on, each release merge deploys it automatically, after approval
and outside office hours.

**At the next real release, on a quiet evening, once per site:** drill R1
on PROD: Rollback, site `<site>`, `code only`, release empty (back to the
previous release), check Health shows the previous commit; then Rollback
again, `code only`, release = the release id just rolled back from (forward
again), check Health. After that, delete the site's
`deploy/public_html.pre-deploy`.

### Clean-up later (not on the switch evening)

Each only after asking, and after checking nothing in it is needed:

- `~/domains/<domain>/old_site_20261030/` on both sites;
- `~/domains/admincitarum.englishhubbali.com/public_html.zip` (outside
  `public_html`, not reachable from the web);
- `~/domains/<domain>/.trash` on both sites;
- `~/apps/admintool/bin/sites/dev.conf.bak-20261119`;
- `deploy/public_html.pre-deploy` after R1 (above).

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
  `... site=dev action=rollback type=code from=80bc554e1a2b to=7ee63bb490ea sha=7ee63bb... version=1.1.1 backup= restored= result=rolled-back-manual`
  (`backup=` the pre-rollback backup made, `restored=` the pre-deploy backup
  the database went back to; both only for `code and database`).
- `~/apps/<app>/bin/gate.log`: refused SSH commands.
- In GitHub: the Deploy and Rollback runs' logs show the same steps, and
  their summaries show the result.

A deploy refused before it knows the site's folder (no `sites/<site>.conf`,
or an invalid domain in it) is only in the Actions log.

## Rolling back a live release

When a release is live but turns out to be broken, roll back to the release
before it: **Actions > Rollback > Run workflow**, "Use workflow from"
`main` for `dev`, `production` for `pemogan` or `citarum`:

| Input | What to enter |
| --- | --- |
| site | `dev`, `pemogan` or `citarum` |
| type | `code only`, or `code and database` (see below) |
| release | leave empty for the previous release, or a release id from a Deploy summary (any release still on the server, `code only`) |
| confirm | the site name again; anything else stops the run before it connects |

A PROD rollback runs in the site's `prod-<site>` environment: it waits for
the required reviewer's approval and uses the PROD key. It has no
office-hours block (a rollback is an emergency fix).

Rollbacks have their own concurrency group (`rollback-<site>`), not
Deploy's, so a deploy queued later can never cancel a waiting rollback.
`rollback.sh` shares the deploy lock on the server: a rollback and a deploy
never overlap, and whichever comes second is refused ("Another deploy of
<site> is running") without changing anything. If a deploy is running,
wait for it to finish (or check it in Actions), then run the rollback. The
steps: checks, maintenance on, (database), switch `current`,
PHP restart, Health with the target's `VERSION`, `REVISION` and `outbound`,
maintenance off.

**Which type:**

- **`code only`**: only the code goes back; the database stays as it is,
  with everything entered since. Use it when the broken release didn't
  change the database. It is refused when the database has migrations the
  target release doesn't know ("use code and database, or ask a
  developer"); old code on a newer database can break in ways Health
  doesn't see. It can also go forward again to a newer release that is
  still on the server, as long as the database already has that
  release's migrations (a rollback never runs migrations; otherwise it is
  refused: "deploy a new commit instead").
- **`code and database`**: the code goes back to the **previous** release
  (the one that was live when the broken one was deployed) and the
  database goes back to how it was then: the broken release's pre-deploy
  backup. **Everything written to the database since that deploy is lost
  from the live database** (invoices, attendance, anything). It is saved
  first in `~/apps/<app>/backups/<site>/pre-rollback-<UTC time>-<id>.sql.gz`
  (mode 600, checked like a deploy's backup), and tables the broken
  release's migrations added are dropped. The log, the `RESULT` line
  (`restored=`) and the run's summary name the pre-deploy backup the
  database went back to; its name carries its UTC time, so you can see how
  much was discarded. On a site whose `ci_env` isn't production, the
  WhatsApp token is overwritten again right after the restore, as a deploy
  does: that backup was taken before its deploy overwrote the token, so it
  can hold a real one. Only allowed when the live
  release became live through a successful deploy, not through a rollback
  (otherwise "the live release came from a rollback; its backup is too old
  to restore safely; ask a developer").

**It refuses, before changing anything,** a release that isn't on the server,
the live release, `code and database` to anything but the previous release
or without a valid pre-deploy backup, `code only` with a database that is
ahead of the target or behind it (the target needs migrations), "previous"
when the live release's deploy didn't record
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
because `deploy.sh` itself changed, and the rollback drills R1–R4 (see
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

`gate.sh` is what the GitHub keys run, so a change to it takes effect on the
next SSH connection. After replacing it, re-run the refusal tests (no
command, `bash`, `deploy <site> ../x`, `deploy <a site not in sites.allow> x`)
with the deploy key and check they appear in `bin/gate.log`. Once the PROD
key exists, also: the DEV key refused for `deploy pemogan x` and
`deploy citarum x`, the PROD key refused for `deploy dev x` and `bash`.

**The change that added the PROD key profile (EHU-0023-7)** changes
`gate.sh` only. Install it as above **before** adding the PROD key to
`authorized_keys`: the old `gate.sh` ignores the `prod` argument and would
read `sites.allow`. With the new one and no `sites.allow.prod` yet, the
PROD key reaches nothing. The DEV key keeps working unchanged.

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

1. Remove the site from `~/apps/<app>/bin/sites.allow` (`sites.allow.prod`
   for a PROD site) and `bin/sites/<site>.conf`; remove the deploy key's
   line from `~/.ssh/authorized_keys` once no site uses that key. For a
   PROD site, also set its `DEPLOY_PROD_*_ENABLED` variable off and turn
   Hostinger's Git auto-deploy back on.
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
| R3 refusals | `confirm` other than the site; `code and database` right after R1 (the live release came from a rollback); `code only` forward to a kept release that needs migrations; `code only` after a deploy like R2's (the database is ahead of the previous release); a release from before the WhatsApp/email guard, if one is still kept | each stopped or refused (`failed`) with its message, nothing changed |
| R4 Health fails | after a deploy like R2's: turn automatic deploys off (`DEPLOY_DEV_ENABLED=false`), back up `bin/sites/<site>.conf` and add `health_url=https://<domain>/index.php/<a page that doesn't exist>`, then type `code and database`, release empty; afterwards restore the site config, check Health with the real URL, turn automatic deploys back on, and roll back again (`code and database`) to clean up | red, `rollback-failed` after the Health tries (about 3 minutes of maintenance page): the code switched back and the pre-rollback backup restored automatically; maintenance off; the database as after the deploy. The clean-up rollback ends `rolled-back-manual` with the database as before the deploy |

If people use the site during the drills, a checksum can differ because of
their changes; compare with the backup the drill's deploy took instead. See
also "Known issues" for `tagihan`.

**PROD sites don't run C1–C3 or R2–R4.** They switch after the acceptance
checks in "Accepting a PROD site" (same scripts as DEV, where all drills
passed; the drills would put broken commits on `production`), plus R1 once
per site at the next real release. If a drill is ever needed on PROD
anyway, the drills touch real users and real data, so:

- run them at a quiet time and announce them: C2, C3 and the rollback
  drills show the maintenance page, C3 and R4 for about 3 minutes;
- for C2, R2 and R4, change one row of a table that site doesn't use, and
  look up which one on that site's own database first;
- **R2 and R4 throw away real data:** their `code and database` rollback
  puts the database back to the drill deploy's pre-deploy backup, so
  everything people enter between that deploy and the rollback is gone from
  the live database (it is only in the pre-rollback backup). Run them only
  when nobody is using the site, and keep the time between the drill deploy
  and the rollback as short as possible;
- on PROD, delete the drills' pre-rollback backups only after checking that
  nothing in them is needed (on DEV they were simply deleted);
- take the baseline right before each run;
- C2, C3, R2 and R4 restore the site's database, which also changes how
  some `tagihan.tipe` values are stored (see "Known issues"); expect that in
  the comparison.

After all drills (C1–C3 and R1–R4) pass on DEV, or the acceptance checks
and R1 on a PROD site, delete its `deploy/public_html.pre-deploy`; from then
on, going back works as described in "Going back to Hostinger's Git deploy".

### Record: deploy drills, DEV, 19 Nov 2026

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

### Record: rollback drills, DEV, 19 Nov 2026

After PR #31 was merged: `deploy-lib.sh`, `deploy.sh`, `rollback.sh` and
`gate.sh` (last) installed by hand, each checked against `main` (sha256, no
CR, `bash -n`); 9 refusal tests with the deploy key, 4 of them rollback
forms (an unknown type, a path as target, an extra argument, a site not in
`sites.allow`), all refused and logged. Every drill compared the database
(table list, migration version, one row, a checksum of every table) before
and after.

| Drill | Releases | Result |
| --- | --- | --- |
| C1 again (`deploy.sh` changed) | `984635c6709d` | ✅ `success`, `previous=d0b6bba3091b` recorded, token step "0 row(s) changed", the previous release kept, about 4 s of maintenance; database as the baseline |
| R1 code only, back | `984635c6709d` → `d0b6bba3091b` | ✅ `rolled-back-manual`, Health ok on the first try with the target's revision, about 4 s of maintenance, no pre-rollback backup; database as the baseline |
| R1 code only, forward | `d0b6bba3091b` → `984635c6709d` | ✅ `rolled-back-manual`; database as the baseline |
| R2 code and database | deploy `fbc518e1e68a` (adds `drill_r2_tmp`, changes `libur_nasional` row 18), then `fbc518e1e68a` → `984635c6709d` | ✅ `rolled-back-manual`; the database was named up front as going back to `pre-deploy-20261119T130811Z-fbc518e1e68a.sql.gz`; a pre-rollback backup (mode 600, complete, holding `drill_r2_tmp`); "Dropping table drill_r2_tmp"; the token step after the restore; about 4 s of maintenance; **database identical to before the deploy** (34 tables, row 18, version, all checksums) |
| R3a wrong confirm | — | ✅ stopped at "Check the inputs" before connecting (the SSH step skipped); nothing on the server |
| R3b code and database after a rollback | live `984635c6709d` (from R2's rollback) | ✅ refused: "The live release came from a rollback; its backup is too old to restore safely; ask a developer"; nothing changed |
| R3c code only forward over a migration | `984635c6709d` → `fbc518e1e68a` | ✅ refused: "needs migrations the database hasn't run …; deploy a new commit instead"; nothing changed |
| R3d code only with the database ahead | live `edf249eb7233` (R4's deploy) → `984635c6709d` | ✅ refused: "The database has migrations release 984635c6709d doesn't know …; use code and database, or ask a developer"; nothing changed |
| R3e a release from before the guard | — | ⏭️ not possible: none was kept any more (`2029ecc1a95f` was cleaned up by the deploy of PR #31's merge). Covered by `tests/deploy/deploy-test.sh` |
| R4 Health fails | deploy `edf249eb7233` (adds `drill_r4_tmp`, changes row 18), then `edf249eb7233` → `984635c6709d` with a `health_url` that answers no Health JSON, automatic deploys off | ✅ `rollback-failed` after 18 failed tries (about 3 minutes of maintenance): "current points back to releases/edf249eb7233", the pre-rollback backup restored automatically ("The database is back as it was before the rollback"), maintenance off, `restored=` empty; **database identical to after the deploy**. Then the site config restored, Health with the real URL ok (revision `edf249eb7233…`, maintenance false), automatic deploys back on |
| R4 clean-up | `edf249eb7233` → `984635c6709d` | ✅ `rolled-back-manual`, "Dropping table drill_r4_tmp"; **database identical to before R4's deploy** |

Afterwards the drill branches, the drills' pre-rollback backups and the
script backups in `bin/` were deleted. DEV was left on `984635c6709d` (live
through a rollback, so the next `code and database` needs a fresh deploy
first).

### Record: PROD switch, 20 Nov 2026

Release 1.2.0 (PR #35, merge commit `48975370c7df` on `production`), the
first release over the SSH deploy. Both sites switched the same evening,
following "Switching a PROD site", after the read-only preflight that
afternoon (both on Hostinger's checkout of `c9f878a`, version 1.1.1;
`SetEnv CI_ENV production` already present; no cron jobs for the app).

Before the release merge: `gate.sh` with profiles installed (sha256 checked
against `main`, 7 refusal tests with the DEV key), the PROD key added
(`gate.sh prod`, `restrict`; 8 refusal tests with it, 3 more with the DEV
key, all in `gate.log`), `prod-pemogan` / `prod-citarum` set up (the
required reviewer needed a second save: check the protection rules after
saving), Hostinger's Git auto-deploy turned off on both sites. The release
merge started no PROD deploy (both switches off) and neither site's
`public_html` changed, which confirmed the auto-deploy was off; the repo
has no webhooks, so GitHub can't show that setting.

| Step | Pemogan | Citarum |
| --- | --- | --- |
| Shared `.htaccess` | from `public_html/.htaccess`: `MIGRATE_TOKEN` and the stray `htaccess:` line dropped, `MAINTENANCE_FILE` added; `CI_ENV` = `production` | the same (no stray line) |
| Uploads copied | 4/4 files incl. 2 untracked, identical | 2/2, identical |
| First deploy | ✅ 21:25 WITA, `success`: migrated 20261028000000 → 20261116030000, Health ok on the first try with `outbound` `live`, about a minute of maintenance; backup 92,879 bytes | ❌ 1st attempt: "Connection closed by host" (exit 255) before `gate.sh` ran, nothing changed (see "Known issues"). ✅ re-run at 21:52 WITA, `success`, same steps; backup 16,403 bytes |
| Re-run | ✅ `already-live`, nothing changed | ✅ `already-live`, nothing changed |
| Health | ok, 1.2.0, revision `48975370c7df…`, `"outbound":"live"` | the same |
| Backup | mode 600, `gunzip -t` ok, "Dump completed" | the same |
| Uploads after the deploy | `public_html.pre-deploy/uploads` = `shared/uploads` | the same |
| Automatic deploys | `DEPLOY_PROD_PEMOGAN_ENABLED=true` | `DEPLOY_PROD_CITARUM_ENABLED=true` |

The migration changed no existing rows (checked beforehand: `status_hadir`
held only `Hadir` and `Izin`, no empty values).

Approving a run: in "Review deployments", **tick the environment's
checkbox** before "Approve and deploy"; without it nothing is recorded and
the job keeps waiting.

Still to do: R1 on each site at the next real release (see "Accepting a
PROD site"), then delete each site's `deploy/public_html.pre-deploy`; the
clean-up in "Clean-up later".

## Known issues

- **Restoring a backup changes how some `tagihan.tipe` values are stored.**
  `tagihan.tipe` is `enum('Biaya Kelas','Biaya Registrasi','Biaya Buku','')`.
  For invoices with mixed item types the app writes a combined value (e.g.
  "Biaya Kelas, Biaya Buku"), which isn't one of the options; DEV's database
  runs without strict SQL mode (PROD's too: `NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION`, checked 20 Nov 2026), and the
  app sets `stricton => FALSE`, so it stores MySQL's error value (index 0,
  shown as `''`). A dump writes it as `''`, so after a restore it is the
  real `''` option (index 4). The page looks the same and `WHERE tipe = ''`
  matches both, but `tipe+0`, sorting and grouping by `tipe`, and
  `CHECKSUM TABLE` change. Compare row data, not checksums, around a
  restore of `tagihan`. Not fixed yet: parked until the deploy work
  (EHU-0023) is done, then to get its own Solava task.
- **The server sometimes refuses SSH for a minute.** After many SSH logins
  in a short time (seen on 20 Nov 2026, during the PROD switch), the
  server closed new connections before login ("Connection closed by
  <host> port 65002", exit 255), from GitHub and from a developer's
  machine alike. Nothing reaches `gate.sh` then, so nothing changes and
  `gate.log` gets no line. Re-run the job (Actions > Re-run failed jobs)
  a minute later. Avoid bursts of SSH logins right before a deploy.

## Tests

- `bash tests/deploy/gate-test.sh`: which SSH commands `gate.sh` accepts,
  with the DEV key and with the PROD key (`gate.sh prod`).
  Plain bash, runs anywhere, including Git Bash on Windows.
- `bash tests/deploy/deploy-test.sh`: real deploys, automatic and manual (`rollback.sh`) rollbacks, and refusals
  against a scratch database and a `php -S` site, laid out as above. Linux
  only. Runs in CI as the `deploy-scripts` job of the Tests workflow.
