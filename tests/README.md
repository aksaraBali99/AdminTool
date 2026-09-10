# Automated tests

Two test suites live here, both PHPUnit + Guzzle functional tests driven
over real HTTP against a throwaway server — CI3 controllers/models are too
tightly coupled to the framework's superobject to unit-test in isolation,
so this suite tests observable behavior instead.

- **`tests/Security/*`** — encodes the findings from the 2026-09-10
  security review. Most of these **fail today, on purpose**: they document
  known vulnerabilities so that when each one is fixed, its test flips to
  green and stays that way, instead of the fix depending on someone
  remembering to re-check it by hand.
- **`tests/Financial/*`** — regression coverage for the payroll/tax
  calculation in `M_hr::generate_payroll_guru()` +
  `M_hr::calculate_pph21()`, and the profit/loss figure in
  `M_dashboard::get_laba_rugi()`. These are real-money calculations, so
  they're asserted against known, hand-verified expected values from
  seeded fixture data — not just "did it run without erroring". Both
  currently pass.

## Running

```
composer test              # everything
composer test:security     # just tests/Security
composer test:financial    # just tests/Financial
```

Each runs `tests/run-tests.sh`, which:
1. Reseeds the `admintool_test` database from `tests/fixtures/seed_test_data.sql`
2. Starts a throwaway `php -S` server wired to that database via env vars
3. Runs PHPUnit against it (passing through any `--testsuite ...` args)
4. Kills the throwaway server

No Apache/Laragon vhost is involved — this is self-contained and is exactly
what a CI workflow would do too. Set `TEST_DB_PASS` (and `PHP_BIN`/
`TEST_DB_USER`/`TEST_DB_HOST` if they differ from the defaults) in your
shell before running — see the comments at the top of `run-tests.sh`.

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
- `tests/Security/*Test.php` — access control, CSRF, session, upload, and unauthenticated-endpoint checks
- `tests/Financial/*Test.php` — payroll/PPh21 and profit-loss calculation checks

## A note on test isolation

`AccessControlTest` deliberately attacks the seeded accounts' credentials
(that's the vulnerability it's proving). Its `tearDown()` unconditionally
calls `TestDb::resetSeededAccountCredentials()` to restore them — this has
to be in `tearDown()`, not at the end of the test method, because a
PHPUnit assertion failure throws and skips any code after it. An earlier
version of this test put the cleanup after the assertion and it never ran
once the (expected, currently-true) vulnerability triggered, which
permanently corrupted `test_superadmin`'s password for the rest of that
suite run and broke `PayrollCalculationTest`/`LabaRugiTest` with
confusing 302/303 "not logged in" failures instead of their real
assertions. If you add a test that mutates seeded account state, put its
cleanup in `tearDown()`.
