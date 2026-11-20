# Health page and maintenance mode

Both are used by the automated deploy (Solava task EHU-0023). They work on
every site (DEV and both PROD sites) without any extra setup.

## Health page

`https://<site>/index.php/Health` (also `https://<site>/Health`)

Public, no login, read-only. It answers JSON:

```json
{"status":"ok","version":"1.1.1","revision":"9b970e3...(40 characters)","php":"8.3",
 "database":"ok","migrations":"ok","db_version":"20261028000000","maintenance":false,
 "outbound":"blocked"}
```

| Field | Meaning |
| --- | --- |
| `status` | `ok` (HTTP 200) when the database answers and no migration is pending, otherwise `error` (HTTP 503) |
| `version` | The `VERSION` file of the code that is live |
| `revision` | The full commit sha of the code that is live, from the `REVISION` file the deploy's build writes next to `index.php`; `unknown` where there is no such file (local and CI runs) |
| `php` | PHP version the site runs (all sites should show `8.3`) |
| `database` | `ok` if the database answers, otherwise `error` |
| `migrations` | `ok`, or `pending` when the code has a migration the database has not run yet |
| `db_version` | The newest migration the database has run |
| `maintenance` | `true` while maintenance mode is on |
| `outbound` | `live` when the site really sends WhatsApp/email, which only happens with `CI_ENV=production` exactly; `blocked` everywhere else (DEV, local, CI). See [OUTBOUND_MESSAGES.md](OUTBOUND_MESSAGES.md). A PROD site showing `blocked` is missing `SetEnv CI_ENV production`. It doesn't affect `status` |

It never shows passwords or error details, and it answers JSON even when the
database is down, in every `CI_ENV` (it switches off CodeIgniter's database
error page for its own request). Anything that checks it should look for
`"status":"ok"` in the body, not only HTTP 200: with `CI_ENV=development`
on PHP 8.2+, CodeIgniter 3's own deprecation notices are printed before
the JSON, which stops the 503 status from being sent. The deploy checks it before
taking the maintenance page down, and requires `version` and `revision` to
be the new release's, so it knows the new release is what the site serves. An uptime monitor can also watch it for
the word `"status":"ok"`.

## Maintenance mode

While the **maintenance file** exists, every page answers HTTP 503 with a
short "Sedang dalam pemeliharaan / Under maintenance" page, so nobody can
save data. Two things keep working:

- command-line runs, so `php index.php migrate` still works, and
- the Health page, so the deploy can check the new code first.

### Where the maintenance file is

Each site sets it in its shared `.htaccess`, as an absolute path outside the
release folders:

```apache
SetEnv MAINTENANCE_FILE /home/<user>/domains/<domain>/deploy/shared/maintenance.on
```

That way there is one switch per site, whichever release the live link
points to: it stays on while the deploy moves the link to the new release,
and a rollback to an older release doesn't find a leftover switch in that
release's folder. The file's folder must exist; the file itself must not,
except during maintenance.

If `MAINTENANCE_FILE` is not set, the file is `maintenance.on` next to
`index.php` (the site's web root).

### Switching it by hand

The deploy creates and removes the file itself. To switch it by hand over
SSH (use the path from the site's `MAINTENANCE_FILE`):

```sh
touch /home/<user>/domains/<domain>/deploy/shared/maintenance.on   # on
rm /home/<user>/domains/<domain>/deploy/shared/maintenance.on      # off
```

Check it worked: `https://<site>/Health` shows `"maintenance":true` or
`false`.

`maintenance.on` is in `.gitignore`. Never commit it, or the site goes down
on the next deploy.
