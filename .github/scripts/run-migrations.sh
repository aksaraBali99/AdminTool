#!/usr/bin/env bash
# Runs pending database migrations on a deployed site by calling its
# Migrate endpoint (application/controllers/Migrate.php), and waits until
# the site reports the newest migration in this checkout.
#
# Hostinger pulls the new code on its own schedule after a push, so the
# first calls may still hit the previous code (which reports an older
# version). Those are retried until the expected version shows up or
# MAX_WAIT_SECONDS runs out.
#
# Required env: SITE_URL (e.g. https://dev.example.com), MIGRATE_TOKEN.
# Optional env: MAX_WAIT_SECONDS (default 600), RETRY_SECONDS (default 20).
set -euo pipefail

: "${SITE_URL:?SITE_URL is not set (environment secret SITE_URL)}"
: "${MIGRATE_TOKEN:?MIGRATE_TOKEN is not set (environment secret MIGRATE_TOKEN)}"
MAX_WAIT_SECONDS="${MAX_WAIT_SECONDS:-600}"
RETRY_SECONDS="${RETRY_SECONDS:-20}"

# Newest migration in this checkout, e.g. 20261116030000.
expected="$(ls application/migrations/ | grep -E '^[0-9]{14}_.*\.php$' | sort | tail -n1 | cut -c1-14)"
if [ -z "$expected" ]; then
    echo "::error::No migrations found in application/migrations/."
    exit 1
fi
echo "Expecting ${SITE_URL%/} to reach migration version $expected."

url="${SITE_URL%/}/index.php/Migrate"
deadline=$(( $(date +%s) + MAX_WAIT_SECONDS ))
attempt=0

while :; do
    attempt=$(( attempt + 1 ))
    body_file="$(mktemp)"
    # The token goes in the query string (that's what Migrate reads); curl
    # never echoes it, and GitHub masks the secret in logs anyway.
    status="$(curl -sS -o "$body_file" -w '%{http_code}' --max-time 120 \
        --get --data-urlencode "token=$MIGRATE_TOKEN" "$url" 2>/dev/null)" || status="000"
    # Error pages come back as HTML; keep just their text, on one line.
    body="$(sed -e '/<style/,/<\/style>/d' -e '/<title>/d' -e 's/<[^>]*>/ /g' "$body_file" \
        | tr -s ' \t\r\n' ' ' | cut -c1-500)"
    body="${body# }"
    rm -f "$body_file"
    echo "Attempt $attempt: HTTP $status - ${body:-<empty>}"

    case "$status" in
        200)
            # "Already up to date at version X." or "Migrated from version A to X."
            version="$(printf '%s' "$body" | grep -oE '[0-9]{14}' | tail -n1 || true)"
            if [ -n "$version" ] && [ "$version" -ge "$expected" ]; then
                echo "Database is at version $version."
                exit 0
            fi
            echo "Site reports version ${version:-unknown}; it is probably still running the previous code."
            ;;
        404)
            echo "::error::Migrate answered 404: MIGRATE_TOKEN is not set in the site's .htaccess, or the MIGRATE_TOKEN secret doesn't match it."
            exit 1
            ;;
        500)
            echo "::error::Migration failed on the site: $body"
            exit 1
            ;;
        *)
            # 503 (another run holds the lock), timeouts, deploy in progress.
            echo "Not ready yet."
            ;;
    esac

    if [ "$(date +%s)" -ge "$deadline" ]; then
        echo "::error::Gave up after ${MAX_WAIT_SECONDS}s: the site never reported version $expected. Check that the deployment finished, then re-run this workflow."
        exit 1
    fi
    sleep "$RETRY_SECONDS"
done
