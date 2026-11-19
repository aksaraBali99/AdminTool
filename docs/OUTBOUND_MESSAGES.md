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
  which the callers log in `reminder_tagihan` like a failed send.
- `MY_Email::send()` sends nothing and returns `TRUE`.
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
  `stream_socket_client` or `file_get_contents('http...')` in `application/`
  except the Fonnte call inside `sendWa()`, after its gate
  (`application/libraries/tcpdf` is skipped).

`tests/Health` also checks `"outbound":"blocked"` on the test server.
