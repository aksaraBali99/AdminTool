# Security regression tests

This suite encodes the findings from the 2026-09-10 security review as
executable checks. Most of them **fail today, on purpose** — they document
known vulnerabilities so that when each one is fixed, its test flips to
green and stays that way (regression protection), instead of the fix
depending on someone remembering to re-check it by hand.

## Running

```
composer test:security
```

This runs `tests/run-tests.sh`, which:
1. Reseeds the `admintool_test` database from `tests/fixtures/seed_test_data.sql`
2. Starts a throwaway `php -S` server wired to that database via env vars
3. Runs PHPUnit against it
4. Kills the throwaway server

No Apache/Laragon vhost is involved — this is self-contained and is exactly
what a CI workflow would do too.

## Why a dedicated database, and why it matters

**Never point this suite at the production database, or any database with
real user data.** Several tests deliberately exercise
endpoints that are currently missing authentication or authorization
checks (that's the point — the test would be meaningless otherwise), and
some of those endpoints have real side effects: `Cron::index()` inserts
billing records and sends outbound WhatsApp messages via a third-party API
for every row matching its active-student filter.

`tests/bootstrap.php` refuses to run at all unless `TEST_DB_NAME` is
exactly `admintool_test`, and the seed fixture is built so that even a
successful (i.e. vulnerable) hit on `Cron` finds zero matching rows —
see the comment in `tests/fixtures/seed_test_data.sql` and
`tests/Security/UnauthenticatedEndpointsTest.php` before changing either.

## What's deliberately NOT covered here

- **`DbCont::resetData()`** (the CSRF-triggerable full-database-wipe
  finding) has no dedicated test. Proving it fully would mean actually
  calling it as a superadmin, which drops every table in whatever database
  it's pointed at — not something to do routinely, even against a
  disposable test DB. Its CSRF exposure is covered indirectly by
  `CsrfProtectionTest`, since it inherits the global `csrf_protection`
  setting. Once the endpoint itself is fixed (require POST + CSRF token +
  an explicit confirmation step), add a narrow test that asserts a bare
  GET is rejected — safe to do then, because it'll never reach
  `dropAllTables()`.
- **The stored-XSS finding** in `crm/lead.php` isn't covered by this
  HTTP-level suite — proving it requires rendering the page in a real
  browser and observing script execution, which belongs in the Playwright
  UI suite, not here.
- **PHPExcel XXE specifically** — `FileUploadTest` proves the endpoint has
  no file-type allowlist (the actual root cause), without constructing a
  working XXE payload.

## Layout

- `tests/bootstrap.php` — safety rail + constants, loaded by PHPUnit
- `tests/Support/ApiClient.php` — Guzzle wrapper (login-as-role, cookie jar)
- `tests/Support/TestDb.php` — direct PDO access for assertions/cleanup
- `tests/fixtures/seed_test_data.sql` — synthetic-only fixture data
- `tests/Security/*Test.php` — the checks themselves
