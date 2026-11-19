# Outbound messages (WhatsApp, email)

Solava task EHU-0023-6.

The app sends messages to real people in one way today: **WhatsApp through
Fonnte** (`sendWa()` in `application/helpers/custom_helper.php`), when an
invoice is sent (`Peserta/kirim_invoice`) or from the billing cron
(`Cron::index()`). Email (`MY_Email`) isn't used yet. There is no SMS or
webhook.

## Only production sends

Messages are only sent when `ENVIRONMENT` is exactly `production`
(`outbound_is_live()` in `application/helpers/outbound_helper.php`):
case-sensitive, no trimming. `ENVIRONMENT` comes from `CI_ENV` and is
`development` when `CI_ENV` isn't set. Anywhere else (DEV, local, CI):

- `sendWa()` makes no request at all. It returns
  `{"status":false,"reason":"NOT SENT (<environment>): WhatsApp is blocked outside production",...}`,
  which the callers log in `reminder_tagihan` like a failed send. That row
  (`keterangan` starting with "NOT SENT") is the record of what wasn't sent.
- `MY_Email::send()` sends nothing and returns `TRUE`.
- Both also call `log_message('info', ...)` (target or recipient count and
  message length, never the token or addresses), but `log_threshold` is `0`
  in `application/config/config.php`, so that line is only written to
  `application/logs/` when the threshold is raised to 3 or more.
- `https://<site>/Health` shows `"outbound":"blocked"` (production: `"live"`).

Nothing else can switch sending on: not the WhatsApp token in
`data_konfig.token_wa`, not any setting on the server. There is no test-number
or redirect mode: outside production nothing is sent to anyone.

For tests only, outside production the Fonnte URL can be pointed at a local
stub with the environment variable `OUTBOUND_FONNTE_URL`
(`tests/run-tests.sh` does this), so a broken gate would show up as a hit on
the stub instead of a real message. Production ignores that variable.

## PROD prerequisites

So that PROD keeps sending:

The release steps in [RELEASING.md](RELEASING.md) include both checks.

1. **Each PROD site's `.htaccess`** must contain
   `SetEnv CI_ENV production`. Check: that site's
   `https://<site>/Health` shows `"outbound":"live"`. A PROD site showing
   `blocked` sends nothing.
2. **Any PROD cron job** (none is set up today) must start its command with
   `CI_ENV=production`, e.g.
   `CI_ENV=production php /path/to/index.php Cron` (the controller name is case-sensitive on Linux). Cron runs from the
   command line, where `.htaccess` `SetEnv` doesn't apply, so without it the
   cron runs as `development` and sends nothing.

DEV must **not** have `CI_ENV production`: it runs with `testing` and shows
`"outbound":"blocked"`.

## Tests

`tests/Outbound/` (suite `outbound`, run by `tests/run-tests.sh`):

- `OutboundGateTest`: `outbound_is_live()` is true only for `'production'`
  (not `'Production'`, `'production '`, `''`, `null`, ...), and production
  ignores `OUTBOUND_FONNTE_URL`.
- `KirimInvoiceNotSentTest`: sending an invoice on the test server logs
  "NOT SENT" in `reminder_tagihan`, the Fonnte stub gets zero hits, and
  `Peserta/kirim_reminder_tagihan` is not reachable as a URL.
- `MyEmailTest`: outside production `MY_Email` hands nothing over to
  mail/sendmail/SMTP; in production it does.
- `NoOutboundBypassTest`: no `curl_init`, `curl_exec`, `mail(`, `fsockopen`,
  `stream_socket_client`, `stream_context_create`, `SoapClient`, Guzzle, or `file_get_contents`/`fopen`/`copy`/`file`/`get_headers`/`readfile` with an `http` URL in `application/`
  except the Fonnte call inside `sendWa()`, after its gate
  (`application/libraries/tcpdf` is skipped).

`tests/Health` also checks `"outbound":"blocked"` on the test server.

## The SSH deploy

`deploy/server/deploy.sh` (used for DEV) adds three checks of its own; see
`deploy/server/README.md`:

- it refuses to deploy unless `ci_env=` in the site's
  `bin/sites/<site>.conf` is `production`, `testing` or `development` and
  matches `SetEnv CI_ENV` in the site's `.htaccess`;
- where `ci_env` isn't `production`, it sets `data_konfig.token_wa` to
  `DISABLED-NON-PROD` after migrations;
- Health must show `"outbound":"live"` on a production site and `"blocked"`
  anywhere else, or the deploy rolls back.
