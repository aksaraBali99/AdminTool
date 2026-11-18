# SSH deploy: server side

The GitHub Actions workflow `.github/workflows/deploy.yml` deploys a site by
sending a release tarball over SSH. On the server, two scripts do the work:

| File | Installed as | Role |
| --- | --- | --- |
| `gate.sh` | `~/sites/bin/gate.sh` | The only command the GitHub key may run. Accepts exactly `deploy <site> <release-id>`, for sites listed in `~/sites/bin/sites.allow`. Logs anything else to `~/sites/bin/gate.log` and refuses it. |
| `deploy.sh` | `~/sites/bin/deploy.sh` | Unpacks the release and deploys it, rolling back by itself if anything fails. |

Only DEV (`dev`, admin-dev.englishhubbali.com) uses this for now. The PROD
sites still use Hostinger's Git deploy.

## Layout on the server

```
~/sites/bin/                gate.sh, deploy.sh (mode 700), sites.allow, gate.log
~/sites/dev/
    site.conf               domain=admin-dev.englishhubbali.com
    releases/<id>/          one folder per release (the newest 3 are kept)
        .htaccess  -> ~/sites/dev/shared/.htaccess
        uploads    -> ~/sites/dev/shared/uploads
    current -> releases/<id>
    shared/.htaccess        the site's settings: DB_*, CI_ENV, MIGRATE_TOKEN, MAINTENANCE_FILE
    shared/uploads/         uploaded files
    shared/maintenance.on   only exists while maintenance is on
    logs/deploy.log         every step of every deploy, with UTC times
    logs/history.log        one line per deploy
    public_html.pre-deploy/ the old public_html folder, kept after the first deploy
~/domains/admin-dev.englishhubbali.com/public_html -> ~/sites/dev/current
~/backups/dev/pre-deploy-<UTC time>-<id>.sql.gz   (mode 600; the newest 5 are kept)
```

The release id is the first 12 characters of the commit sha. Each release
also has a `REVISION` file with the full sha.

## What a deploy does

`deploy.sh <site> <release-id>` reads the tarball from stdin, takes a lock
(two deploys of one site never overlap), then:

1. **Checks** the layout and the settings in `shared/.htaccess`. It refuses
   if maintenance is already on, or if the release id already exists. If
   that release is the live one (a re-run), it changes nothing and ends
   with result `already-live`.
2. **Unpacks** into `releases/<id>/` and links in the shared `.htaccess` and
   `uploads/`.
3. **Turns maintenance on**: creates the `MAINTENANCE_FILE`. On the very
   first deploy it also replaces the `public_html` folder with a link to
   `current`, keeping the folder as `~/sites/<site>/public_html.pre-deploy`.
4. **Backs up** the database (`mysqldump --single-transaction --routines
   --triggers`, gzipped). The backup must pass `gzip -t`, be at least 1 KB
   and end with mysqldump's "Dump completed" line.
5. **Migrates**: `php index.php migrate` in the new release.
6. **Switches** `current` to the new release, atomically (`ln -s` + `mv -T`).
7. **Checks Health**: `https://<domain>/index.php/Health` must say
   `"status":"ok"`, the release's `VERSION` and its `REVISION` (full commit
   sha). That shows the new release is what the site actually serves, not an
   older folder or a cached page. It tries for about 3 minutes (18 times,
   10 s apart), because PHP workers may keep resolving the old release for
   up to 2 minutes (PHP's realpath cache). To shorten that, the deploy
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
works on a half-restored database. The run then reports
`rollback-incomplete` (see below).

Database settings come from the `SetEnv` lines in `shared/.htaccess`, so
there is a single source of truth. They are never printed or logged, and
never put on a command line. `mysqldump` and `mysql` read them from a
temporary option file (mode 600, deleted on exit). `php` gets them as
environment variables of that one process.

If the SSH connection drops mid-deploy, the deploy still finishes (or rolls
back) on the server. Its log is in `deploy.log`.

## One-time setup for a site

Example for `dev`. `<user>` is the SSH user (`u738067934`).

1. Folders and scripts. Copy `gate.sh` and `deploy.sh` from this folder
   with LF line endings:

   ```sh
   mkdir -p ~/sites/bin ~/sites/dev/releases ~/sites/dev/shared ~/sites/dev/logs ~/backups/dev
   chmod 700 ~/sites/bin ~/sites/dev/logs ~/backups/dev
   # copy gate.sh and deploy.sh into ~/sites/bin, then:
   chmod 700 ~/sites/bin/gate.sh ~/sites/bin/deploy.sh
   echo dev > ~/sites/bin/sites.allow
   echo domain=admin-dev.englishhubbali.com > ~/sites/dev/site.conf
   ```

2. Shared files. Use the site's current `.htaccess`, plus one line:

   ```sh
   cp -p ~/domains/admin-dev.englishhubbali.com/public_html/.htaccess ~/sites/dev/shared/.htaccess
   chmod 644 ~/sites/dev/shared/.htaccess
   echo 'SetEnv MAINTENANCE_FILE /home/<user>/sites/dev/shared/maintenance.on' >> ~/sites/dev/shared/.htaccess
   cp -a ~/domains/admin-dev.englishhubbali.com/public_html/uploads ~/sites/dev/shared/uploads
   ```

   The web server must be able to read `.htaccess`, so it is 644, not 600.
   The deploy reads `DB_HOST`, `DB_PORT` (optional), `DB_USER`, `DB_PASS`,
   `DB_NAME`, `CI_ENV` and `MAINTENANCE_FILE` from it.

3. The GitHub key. Make a new ed25519 key pair with no passphrase, used only
   for this. Add its public key to `~/.ssh/authorized_keys` by hand (not
   through hPanel), on one line:

   ```
   command="/home/<user>/sites/bin/gate.sh",restrict ssh-ed25519 AAAA... github-deploy-dev
   ```

   `restrict` turns off port forwarding, agent forwarding, X11 and the TTY.
   `command=` means the key can only ever run `gate.sh`. Even
   `ssh ... bash` just runs `gate.sh`, which refuses it.

4. GitHub (Settings > Environments > `dev`), these secrets:

   | Secret | Value |
   | --- | --- |
   | `DEPLOY_SSH_KEY` | the private key, whole file |
   | `DEPLOY_HOST` | `145.79.14.215` |
   | `DEPLOY_PORT` | `65002` |
   | `DEPLOY_USER` | `<user>` |
   | `DEPLOY_KNOWN_HOSTS` | the server's host key line, e.g. from `ssh-keygen -F '[145.79.14.215]:65002'` on a machine that already trusts it |

   Then Settings > Secrets and variables > Actions > Variables:
   `DEPLOY_DEV_ENABLED` = `true`. The workflow does nothing until then, and
   setting it to anything else switches the deploy off.

5. Turn off Hostinger's Git auto-deploy for the site **before** the first
   deploy. Otherwise Hostinger would write into the release folder through
   the `public_html` link.

6. First deploy: Actions > Deploy > Run workflow, ref `main`.

## Logs

- `~/sites/<site>/logs/deploy.log`: every step, e.g.
  `2026-11-18T05:05:02Z [dev 80bc554e1a2b] c. Backup pre-deploy-...sql.gz (48213 bytes)`.
- `~/sites/<site>/logs/history.log`: one line per deploy, e.g.
  `2026-11-18T05:05:40Z site=dev release=80bc554e1a2b sha=80bc554... version=1.1.1 backup=pre-deploy-20261118T050505Z-80bc554e1a2b.sql.gz result=success`.
  The result is `success`, `already-live` (re-run, nothing changed),
  `rolled-back`, `failed` (refused before anything changed) or
  `rollback-incomplete`.
- `~/sites/bin/gate.log`: refused SSH commands.
- In GitHub: the Deploy run's log shows the same steps, and its summary
  shows site, version, commit, backup and result.

## When a rollback is incomplete

The run says `rollback-incomplete` and the site shows the maintenance page.
Over SSH:

1. Read the end of `~/sites/<site>/logs/deploy.log` to see what failed.
2. Check `current` points to the release you want:
   `readlink ~/sites/<site>/current`. If not:
   `ln -sfn releases/<id> ~/sites/<site>/current.new && mv -T ~/sites/<site>/current.new ~/sites/<site>/current`.
3. Restore the database from the backup named in the log. `mysql` reads the
   login from an option file you create (mode 600) with the `DB_*` values
   from `shared/.htaccess`:
   `gzip -dc ~/backups/<site>/<backup> | mysql --defaults-extra-file=<that file> <DB_NAME>`.
   Delete the option file afterwards.
4. Check `https://<domain>/index.php/Health` says `"status":"ok"`.
5. Turn maintenance off: `rm ~/sites/<site>/shared/maintenance.on`.

## Going back to Hostinger's Git deploy

```sh
cd ~/domains/admin-dev.englishhubbali.com
rm public_html && mv ~/sites/dev/public_html.pre-deploy public_html
```

Then turn Hostinger's auto-deploy back on and remove the key's line from
`~/.ssh/authorized_keys`.

## Failure drills (DEV)

Run each from Actions > Deploy > Run workflow with a throwaway branch as
`ref`, then delete the branch. Each must end with a red run and result
`rolled-back` in the summary, the previous release live
(`readlink ~/sites/dev/current`), Health `"status":"ok"` and maintenance off.

| Drill | Throwaway branch | Also expect |
| --- | --- | --- |
| Broken migration | adds a migration that changes data, then runs invalid SQL | the database as before the deploy (data and migration version) |
| Failed health check | changes `Health.php` so `revision` is wrong, e.g. `'revision' => str_repeat('0', 40),` | deploy.log shows Health answered with the wrong revision |

A branch that only writes a different `REVISION` file doesn't make the
health check fail: Health reads that same file from the release it serves,
so the two always agree. Use a broken Health instead, as above.

## Tests

- `bash tests/deploy/gate-test.sh`: which SSH commands `gate.sh` accepts.
  Plain bash, runs anywhere, including Git Bash on Windows.
- `bash tests/deploy/deploy-test.sh`: real deploys, rollbacks and refusals
  against a scratch database and a `php -S` site. Linux only. Runs in CI as
  the `deploy-scripts` job of the Tests workflow.
