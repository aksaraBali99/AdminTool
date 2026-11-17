# Health page and maintenance mode

Both are used by the automated deploy (Solava task EHU-0023). They work on
every site (DEV and both PROD sites) without any extra setup.

## Health page

`https://<site>/index.php/Health` (also `https://<site>/Health`)

Public, no login, read-only. It answers JSON:

```json
{"status":"ok","version":"1.1.1","php":"8.3","database":"ok",
 "migrations":"ok","db_version":"20261028000000","maintenance":false}
```

| Field | Meaning |
| --- | --- |
| `status` | `ok` (HTTP 200) when the database answers and no migration is pending, otherwise `error` (HTTP 503) |
| `version` | The `VERSION` file of the code that is live |
| `php` | PHP version the site runs (all sites should show `8.3`) |
| `database` | `ok` if the database answers, otherwise `error` |
| `migrations` | `ok`, or `pending` when the code has a migration the database has not run yet |
| `db_version` | The newest migration the database has run |
| `maintenance` | `true` while maintenance mode is on |

It never shows passwords or error details. The deploy checks it before
taking the maintenance page down. An uptime monitor can also watch it for
the word `"status":"ok"`.

## Maintenance mode

While a file named `maintenance.on` exists next to `index.php`, every page
answers HTTP 503 with a short "Sedang dalam pemeliharaan / Under maintenance"
page, so nobody can save data. Two things keep working:

- command-line runs, so `php index.php migrate` still works, and
- the Health page, so the deploy can check the new code first.

The deploy creates and removes the file itself. To switch it by hand over
SSH, from the site's folder:

```sh
touch maintenance.on   # on
rm maintenance.on      # off
```

The file is in `.gitignore`. Never commit it, or the site goes down on the
next deploy.
