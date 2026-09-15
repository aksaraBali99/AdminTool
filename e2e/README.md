# Browser smoke tests

Playwright suite covering what the HTTP-level PHPUnit suites in `tests/`
can't: real rendering in a real browser. Today that's a single smoke test -
log in as each seeded role (superadmin, admin, finance) through the actual
login form and confirm the right dashboard renders - but this is also where
the stored-XSS finding from the 2026-09-10 security review
(`crm/lead.php`) belongs once it's covered, since proving that needs a real
DOM, not an HTTP response body.

## Running

```
cd e2e
npm install
npx playwright install --with-deps chromium   # first time only
TEST_DB_PASS=yourpassword npm test
```

This uses the same `admintool_test` database and fixture data as the
PHPUnit suites (see `../tests/README.md`) - `TEST_DB_PASS`, and optionally
`PHP_BIN`/`TEST_DB_USER`/`TEST_DB_HOST`, are the same environment variables
`tests/run-tests.sh` reads. Playwright's `webServer` option runs
`../tests/serve-test-app.sh` for you (reseed + throwaway `php -S`) and
tears it down when the run ends - **never point this at a real database**,
for the same reasons documented in `../tests/README.md`.

## Layout

- `playwright.config.ts` - starts/stops the test server, points at it
- `tests/login-dashboard.spec.ts` - the smoke test itself
