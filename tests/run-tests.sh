#!/usr/bin/env bash
# Runs the PHPUnit suite against a throwaway PHP built-in server, wired to
# the isolated `admintool_test` database — never the real one. This avoids
# touching any Laragon/Apache vhost config; it's also exactly what CI does.
set -euo pipefail

# PHP_BIN, TEST_DB_USER and TEST_DB_PASS are machine-specific — set them in
# your shell (or a local, git-ignored .env you source before running this)
# rather than hardcoding real credentials here. This script intentionally
# has no password default.
PHP_BIN="${PHP_BIN:-php}"
HOST=127.0.0.1
PORT=8089

export TEST_DB_NAME=admintool_test
export TEST_DB_HOST="${TEST_DB_HOST:-127.0.0.1}"
# Empty by default — the mysql CLI and mysqli both fall back to the
# standard port (3306) when this is unset. Set it when your local MySQL
# (e.g. Laragon) listens elsewhere.
export TEST_DB_PORT="${TEST_DB_PORT:-}"
export TEST_DB_USER="${TEST_DB_USER:-root}"
if [ -z "${TEST_DB_PASS:-}" ]; then
    echo "TEST_DB_PASS is not set. Export it (your local MySQL root password) before running this script." >&2
    exit 1
fi
export ADMINTOOL_BASE_URL="http://$HOST:$PORT"

# These are what index.php/database.php actually read via getenv() —
# deliberately pointed at the test DB, matching TEST_DB_* above.
export DB_HOST="$TEST_DB_HOST"
export DB_PORT="$TEST_DB_PORT"
export DB_USER="$TEST_DB_USER"
export DB_PASS="$TEST_DB_PASS"
export DB_NAME="$TEST_DB_NAME"
export CI_ENV=testing

echo "Reseeding admintool_test with synthetic fixture data..."
mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" ${DB_PORT:+-P"$DB_PORT"} "$DB_NAME" < tests/fixtures/seed_test_data.sql

# Some local php.ini's (notably Herd's on Windows) ship with an empty
# session.save_path. CI3's Session_files_driver falls back to that ini
# value, silently fails to open/create it, and never sets a session
# cookie — every authenticated test then looks logged-out (redirected
# back to Login) with no error surfaced anywhere. Force a real,
# guaranteed-writable path so login sessions actually persist.
#
# Wiped (not just created) on every run: this directory is shared with
# tests/serve-test-app.sh, and leftover session files from a prior run
# were observed to make the files driver silently stop issuing new
# session cookies on the next run — same symptom as the empty-ini-path
# case above, just from stale state instead of missing config.
SESSION_SAVE_PATH="$(pwd)/tests/.session-tmp"
rm -rf "$SESSION_SAVE_PATH"
mkdir -p "$SESSION_SAVE_PATH"
if command -v cygpath >/dev/null 2>&1; then
    SESSION_SAVE_PATH="$(cygpath -w "$SESSION_SAVE_PATH")"
fi

echo "Starting throwaway PHP server at $ADMINTOOL_BASE_URL ..."
"$PHP_BIN" -d session.save_path="$SESSION_SAVE_PATH" -S "$HOST:$PORT" -t . tests/router.php >/tmp/admintool-test-server.log 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null || true' EXIT

for i in $(seq 1 20); do
    if curl -s -o /dev/null "$ADMINTOOL_BASE_URL/index.php/Login"; then
        break
    fi
    sleep 0.5
done

echo "Running PHPUnit..."
"$PHP_BIN" vendor/bin/phpunit --colors=always "$@"
