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
export TEST_DB_USER="${TEST_DB_USER:-root}"
if [ -z "${TEST_DB_PASS:-}" ]; then
    echo "TEST_DB_PASS is not set. Export it (your local MySQL root password) before running this script." >&2
    exit 1
fi
export ADMINTOOL_BASE_URL="http://$HOST:$PORT"

# These are what index.php/database.php actually read via getenv() —
# deliberately pointed at the test DB, matching TEST_DB_* above.
export DB_HOST="$TEST_DB_HOST"
export DB_USER="$TEST_DB_USER"
export DB_PASS="$TEST_DB_PASS"
export DB_NAME="$TEST_DB_NAME"
export CI_ENV=testing

echo "Reseeding admintool_test with synthetic fixture data..."
mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" < tests/fixtures/seed_test_data.sql

echo "Starting throwaway PHP server at $ADMINTOOL_BASE_URL ..."
"$PHP_BIN" -S "$HOST:$PORT" -t . tests/router.php >/tmp/admintool-test-server.log 2>&1 &
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
