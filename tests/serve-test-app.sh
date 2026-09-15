#!/usr/bin/env bash
# Reseeds the admintool_test database and starts a throwaway PHP built-in
# server - the browser-driven counterpart to tests/run-tests.sh's server
# startup, extracted into its own script so Playwright's `webServer` option
# can manage the process lifecycle itself (it runs this command, waits for
# the URL to respond, and kills it - along with this whole process, since
# the server is `exec`'d into this script's own PID - when the run ends).
#
# Same safety contract as tests/run-tests.sh: only ever points at the
# disposable, synthetic-data `admintool_test` database. See tests/README.md.
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
HOST=127.0.0.1
PORT="${ADMINTOOL_E2E_PORT:-8090}"

export TEST_DB_NAME=admintool_test
export TEST_DB_HOST="${TEST_DB_HOST:-127.0.0.1}"
export TEST_DB_USER="${TEST_DB_USER:-root}"
if [ -z "${TEST_DB_PASS:-}" ]; then
    echo "TEST_DB_PASS is not set. Export it (your local MySQL root password) before running this script." >&2
    exit 1
fi

# These are what index.php/database.php actually read via getenv() -
# deliberately pointed at the test DB, matching TEST_DB_* above.
export DB_HOST="$TEST_DB_HOST"
export DB_USER="$TEST_DB_USER"
export DB_PASS="$TEST_DB_PASS"
export DB_NAME="$TEST_DB_NAME"
export CI_ENV=testing

echo "Reseeding admintool_test with synthetic fixture data..." >&2
mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" < tests/fixtures/seed_test_data.sql

echo "Starting throwaway PHP server at http://$HOST:$PORT ..." >&2
exec "$PHP_BIN" -S "$HOST:$PORT" -t . tests/router.php
