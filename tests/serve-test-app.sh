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
# Empty by default — see the matching comment in tests/run-tests.sh.
export TEST_DB_PORT="${TEST_DB_PORT:-}"
export TEST_DB_USER="${TEST_DB_USER:-root}"
if [ -z "${TEST_DB_PASS:-}" ]; then
    echo "TEST_DB_PASS is not set. Export it (your local MySQL root password) before running this script." >&2
    exit 1
fi

# These are what index.php/database.php actually read via getenv() -
# deliberately pointed at the test DB, matching TEST_DB_* above.
export DB_HOST="$TEST_DB_HOST"
export DB_PORT="$TEST_DB_PORT"
export DB_USER="$TEST_DB_USER"
export DB_PASS="$TEST_DB_PASS"
export DB_NAME="$TEST_DB_NAME"
export CI_ENV=testing

echo "Reseeding admintool_test with synthetic fixture data..." >&2
mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" ${DB_PORT:+-P"$DB_PORT"} "$DB_NAME" < tests/fixtures/seed_test_data.sql

# See the matching comment in tests/run-tests.sh: some local php.ini's have
# an empty session.save_path, which silently breaks every login session -
# and this directory is wiped (not just created) each run since leftover
# files from a prior run were observed to cause the same silent failure.
SESSION_SAVE_PATH="$(pwd)/tests/.session-tmp"
rm -rf "$SESSION_SAVE_PATH"
mkdir -p "$SESSION_SAVE_PATH"
if command -v cygpath >/dev/null 2>&1; then
    SESSION_SAVE_PATH="$(cygpath -w "$SESSION_SAVE_PATH")"
fi

echo "Starting throwaway PHP server at http://$HOST:$PORT ..." >&2
exec "$PHP_BIN" -d session.save_path="$SESSION_SAVE_PATH" -S "$HOST:$PORT" -t . tests/router.php
