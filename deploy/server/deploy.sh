#!/bin/bash
# Deploys one release of AdminTool to one site, reading the release tarball
# (.tar.gz, made by .github/workflows/deploy.yml) from stdin:
#
#   deploy.sh <site> <release-id> < release.tar.gz
#
# Normally started by gate.sh (the GitHub key's forced command), never by
# hand. Installed as ~/apps/<app>/bin/deploy.sh. See deploy/server/README.md
# for the layout and the one-time setup.
#
# Where things are (<app> is the folder above this script's bin/, so
# nothing here names a particular app):
#   ~/apps/<app>/bin/sites/<site>.conf    domain=<the site's domain>
#   ~/apps/<app>/backups/<site>/          database backups
#   ~/domains/<domain>/deploy/            releases/, shared/, logs/, current
#   ~/domains/<domain>/public_html     -> ~/domains/<domain>/deploy/current
#
# Steps (each logged with a UTC time to ~/domains/<domain>/deploy/logs/deploy.log):
#   a. unpack into releases/<id>/, link in shared/.htaccess and shared/uploads
#      (relative links, so the deploy folder can be moved as a whole)
#   b. maintenance on (the MAINTENANCE_FILE from shared/.htaccess, which must
#      be in shared/); on the very first deploy, public_html (a real folder)
#      is moved to deploy/public_html.pre-deploy and replaced by a link to
#      deploy/current
#   c. database backup to ~/apps/<app>/backups/<site>/pre-deploy-<UTC time>-<id>.sql.gz
#   d. php index.php migrate in the new release
#   e. "current" -> releases/<id>, switched atomically
#   f. Health must answer "status":"ok", the release's VERSION and its
#      REVISION (full commit sha), i.e. the new release is what is served;
#      retried for about 3 minutes
#   g. maintenance off; keep the newest 3 releases and 5 pre-deploy backups
#   h. on any failure after (b): restore the database if migrations started,
#      put the previous release back, maintenance off, exit non-zero
#
# Deploying the release that is already live changes nothing and succeeds
# (result "already-live"), so re-running a deploy is harmless.
#
# Database settings are read from the SetEnv lines of shared/.htaccess. They
# are never printed or logged, and never put on a command line: mysqldump and
# mysql get them from a temporary option file (mode 600, deleted on exit),
# php gets them as environment variables of that one process.
set -Eeuo pipefail
umask 022

SITE="${1-}"
RELEASE="${2-}"
if ! [[ "$SITE" =~ ^[a-z0-9][a-z0-9-]{0,31}$ && "$RELEASE" =~ ^[0-9A-Za-z._-]{1,64}$ ]] \
    || [[ "$RELEASE" == .* || "$RELEASE" == -* ]]; then
    echo "usage: deploy.sh <site> <release-id> < release.tar.gz" >&2
    exit 2
fi

# DEPLOY_* overrides exist for tests/deploy/; gate.sh clears the environment,
# so they can't be set over SSH.
# ROOT is the hosting account's home: it holds domains/ and .lsphp_restart.txt.
ROOT="${DEPLOY_ROOT:-$HOME}"
# This app's folder: the parent of the bin/ folder this script is in.
BIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
APP_DIR="$(dirname "$BIN_DIR")"
PHP="${DEPLOY_PHP:-php}"
# About 3 minutes: PHP workers may keep resolving the old release for up to
# realpath_cache_ttl (120 s by default) after "current" moves.
HEALTH_TRIES="${DEPLOY_HEALTH_TRIES:-18}"
HEALTH_WAIT="${DEPLOY_HEALTH_WAIT:-10}"
SETTLE_SECONDS="${DEPLOY_SETTLE_SECONDS:-3}"
KEEP_RELEASES=3
KEEP_BACKUPS=5

SITE_CONF="$BIN_DIR/sites/$SITE.conf"
BACKUPS="$APP_DIR/backups/$SITE"
# The rest depends on the site's domain (from SITE_CONF) and is set in the
# preflight.
SITE_DIR="" RELEASES="" SHARED="" LOGS="" CURRENT="" PRE_DEPLOY=""
REL="" LOG="" HISTORY=""

# Keep going (and roll back if needed) even if the SSH connection drops:
# output to a closed connection must not kill the script mid-migration.
trap '' HUP PIPE

# State, used by the rollback.
STEP=preflight
RESULT=failed
MAINT=""
MAINT_ON=0
UNPACKED=0
FIRST_RUN=0
CURRENT_CREATED=0
PUBLIC_HTML_SWAPPED=0
SWITCHED=0
PREV=""
MIGRATION_STARTED=0
BACKUP=""
OPT=""
INCOMING=""
TMP_BODY=""
SHA="-"
VERSION="-"
DB_HOST="" DB_PORT="" DB_USER="" DB_PASS="" DB_NAME="" CI_ENV_VALUE=""
DOMAIN="" PUBLIC_HTML="" HEALTH_URL="" verdict=""

now() { date -u +%Y-%m-%dT%H:%M:%SZ; }

say() {
    local line
    line="$(now) [$SITE $RELEASE] $*"
    { [ -d "$LOGS" ] && printf '%s\n' "$line" >> "$LOG"; } 2>/dev/null || true
    printf '%s\n' "$line" 2>/dev/null || true
}

die() {
    say "ERROR: $*"
    exit 1
}

# Value of "SetEnv <name> <value>" in shared/.htaccess (the last one wins,
# as in Apache); empty when missing. Surrounding double quotes are removed.
htaccess_get() {
    awk -v k="$1" '
        { sub(/\r$/, "") }
        tolower($1) == "setenv" && $2 == k {
            v = $0
            sub(/^[ \t]*[^ \t]+[ \t]+[^ \t]+[ \t]*/, "", v)
            sub(/[ \t]+$/, "", v)
            if (v ~ /^".*"$/) v = substr(v, 2, length(v) - 2)
            found = v
        }
        END { printf "%s", found }
    ' "$SHARED/.htaccess"
}

# Value of "<key>=<value>" in the site's config; empty when missing.
conf_get() {
    sed -n "s/^[[:space:]]*$1[[:space:]]*=[[:space:]]*\\(.*[^[:space:]]\\)[[:space:]]*\$/\\1/p" "$SITE_CONF" | tail -n1
}

opt_escape() {
    local s="${1//\\/\\\\}"
    printf '%s' "${s//\"/\\\"}"
}

write_option_file() {
    OPT="$(mktemp "$SITE_DIR/.db-options.XXXXXX")"
    chmod 600 "$OPT"
    {
        printf '[client]\n'
        printf 'host="%s"\n' "$(opt_escape "$DB_HOST")"
        printf 'user="%s"\n' "$(opt_escape "$DB_USER")"
        printf 'password="%s"\n' "$(opt_escape "$DB_PASS")"
        if [ -n "$DB_PORT" ]; then printf 'port=%s\n' "$DB_PORT"; fi
    } > "$OPT"
}

# Asks LiteSpeed to restart this account's PHP workers, so none keeps serving
# the old release from PHP's realpath cache after a link changed. Harmless
# where LiteSpeed isn't used.
restart_php() {
    if touch "$ROOT/.lsphp_restart.txt"; then
        say "$1 Asked LiteSpeed to restart PHP (~/.lsphp_restart.txt)"
    else
        say "$1 Warning: could not touch ~/.lsphp_restart.txt"
    fi
}

# Points $1 (a link) at $2, atomically.
relink() {
    ln -sfn "$2" "$1.deploy-new"
    mv -Tf "$1.deploy-new" "$1"
}

restore_database() {
    say "Restoring the database from $(basename "$BACKUP")"
    [ -s "$BACKUP" ] && [ -n "$OPT" ] || { say "No backup or option file to restore from"; return 1; }

    # Tables the failed deploy added aren't in the backup; drop them so the
    # database is exactly as before and the migration can run again later.
    local in_backup current t
    in_backup="$(gzip -dc "$BACKUP" | sed -n 's/^CREATE TABLE `\([^`]*\)`.*/\1/p' | sort -u)" || return 1
    current="$(mysql --defaults-extra-file="$OPT" -N -B -e "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'" "$DB_NAME" | cut -f1 | sort -u)" || return 1
    for t in $(comm -13 <(printf '%s\n' "$in_backup") <(printf '%s\n' "$current")); do
        if [[ "$t" =~ ^[A-Za-z0-9_]+$ ]]; then
            say "Dropping table $t (not in the backup)"
            mysql --defaults-extra-file="$OPT" -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE \`$t\`" "$DB_NAME" || return 1
        else
            say "Not dropping table with an unusual name; check it by hand"
        fi
    done

    gzip -dc "$BACKUP" | mysql --defaults-extra-file="$OPT" "$DB_NAME" || return 1
    say "Database restored"
}

rollback() {
    local db_ok=1

    if [ -n "$CURRENT" ]; then rm -f "$CURRENT.deploy-new"; fi
    if [ -n "$PUBLIC_HTML" ]; then rm -f "$PUBLIC_HTML.deploy-new"; fi

    if [ -n "$BACKUP" ]; then rm -f "$BACKUP.partial"; fi

    if [ "$SWITCHED" = 1 ] && [ -n "$PREV" ]; then
        if relink "$CURRENT" "$PREV"; then
            say "current points back to $PREV"
            restart_php "  "
        else
            say "Could not point current back to $PREV"
            db_ok=0
        fi
    fi
    if [ "$PUBLIC_HTML_SWAPPED" = 1 ]; then
        if [ -L "$PUBLIC_HTML" ]; then rm -f "$PUBLIC_HTML"; fi
        if [ ! -e "$PUBLIC_HTML" ] && mv -T "$PRE_DEPLOY" "$PUBLIC_HTML"; then
            say "public_html is the original folder again"
        else
            say "Could not move $PRE_DEPLOY back to $PUBLIC_HTML"
            db_ok=0
        fi
    fi
    if [ "$CURRENT_CREATED" = 1 ] && [ "$PUBLIC_HTML_SWAPPED" = 1 ] && [ -d "$PUBLIC_HTML" ] && [ ! -L "$PUBLIC_HTML" ]; then
        rm -f "$CURRENT"
    fi

    if [ "$MIGRATION_STARTED" = 1 ]; then
        restore_database || db_ok=0
    fi

    if [ "$UNPACKED" = 1 ] && [ "$(readlink "$CURRENT" 2>/dev/null)" != "releases/$RELEASE" ]; then
        rm -rf "$REL" && say "Removed the failed release folder"
    fi

    if [ "$db_ok" = 1 ]; then
        if [ "$MAINT_ON" = 1 ]; then
            rm -f "$MAINT" && say "Maintenance off"
        fi
        RESULT=rolled-back
        [ "$MAINT_ON" = 1 ] || RESULT=failed
        say "Rolled back: the previous release is live and the database is as before"
    else
        RESULT=rollback-incomplete
        say "ROLLBACK INCOMPLETE: maintenance is left ON on purpose."
        say "Fix by hand: see 'When a rollback is incomplete' in deploy/server/README.md. The backup is $BACKUP"
    fi
}

on_exit() {
    local rc=$? backup_name=""
    set +eu
    trap - EXIT
    # Nothing may interrupt the rollback.
    trap '' TERM INT
    if [ "$RESULT" != success ] && [ "$RESULT" != already-live ]; then
        say "FAILED during step '$STEP' (exit $rc)"
        rollback
    fi
    [ -n "$OPT" ] && rm -f "$OPT"
    [ -n "$TMP_BODY" ] && rm -f "$TMP_BODY"
    [ -n "$INCOMING" ] && rm -rf "$INCOMING"
    if [ -n "$BACKUP" ] && [ -f "$BACKUP" ]; then backup_name="$(basename "$BACKUP")"; fi
    if [ -d "$LOGS" ]; then
        printf '%s site=%s release=%s sha=%s version=%s backup=%s result=%s\n' \
            "$(now)" "$SITE" "$RELEASE" "$SHA" "$VERSION" "$backup_name" "$RESULT" \
            >> "$HISTORY" 2>/dev/null
    fi
    # Read by the workflow for its job summary.
    printf 'RESULT site=%s release=%s sha=%s version=%s backup=%s result=%s\n' \
        "$SITE" "$RELEASE" "$SHA" "$VERSION" "$backup_name" "$RESULT" 2>/dev/null
    if [ "$RESULT" = success ] || [ "$RESULT" = already-live ]; then exit 0; fi
    [ "$rc" -ne 0 ] || rc=1
    exit "$rc"
}
trap on_exit EXIT
trap 'exit 143' TERM INT

# ---------------------------------------------------------------- preflight
[ -f "$SITE_CONF" ] || die "Missing $SITE_CONF (see deploy/server/README.md)"
DOMAIN="$(conf_get domain)"
[[ "$DOMAIN" =~ ^[a-z0-9][a-z0-9.-]*$ && "$DOMAIN" != *..* ]] || die "$SITE_CONF has no valid domain="
HEALTH_URL="$(conf_get health_url)"
HEALTH_URL="${HEALTH_URL:-https://$DOMAIN/index.php/Health}"
PUBLIC_HTML="$ROOT/domains/$DOMAIN/public_html"
SITE_DIR="$ROOT/domains/$DOMAIN/deploy"
RELEASES="$SITE_DIR/releases"
SHARED="$SITE_DIR/shared"
LOGS="$SITE_DIR/logs"
CURRENT="$SITE_DIR/current"
PRE_DEPLOY="$SITE_DIR/public_html.pre-deploy"
REL="$RELEASES/$RELEASE"
LOG="$LOGS/deploy.log"
HISTORY="$LOGS/history.log"

for d in "$SITE_DIR" "$RELEASES" "$SHARED" "$LOGS" "$BACKUPS"; do
    [ -d "$d" ] || die "Missing folder $d (see deploy/server/README.md)"
done

exec 9> "$SITE_DIR/.deploy.lock"
flock -n 9 || die "Another deploy of $SITE is running"

say "=== Deploy of release $RELEASE started"

for c in "$PHP" mysqldump mysql gzip curl tar flock; do
    command -v "$c" > /dev/null || die "Command not found: $c"
done

[ -f "$SHARED/.htaccess" ] || die "Missing $SHARED/.htaccess"
[ -d "$SHARED/uploads" ] || die "Missing $SHARED/uploads"

MAINT="$(htaccess_get MAINTENANCE_FILE)"
[[ "$MAINT" == /* ]] || die "shared/.htaccess has no absolute SetEnv MAINTENANCE_FILE"
[ -d "$(dirname "$MAINT")" ] || die "The folder of MAINTENANCE_FILE doesn't exist"
# Catches a MAINTENANCE_FILE left pointing at an old layout.
[ "$(cd "$(dirname "$MAINT")" && pwd -P)" = "$(cd "$SHARED" && pwd -P)" ] \
    || die "MAINTENANCE_FILE must be in $SHARED"
[ ! -e "$MAINT" ] || die "Maintenance is already on ($MAINT exists); refusing to deploy over it"

DB_HOST="$(htaccess_get DB_HOST)"
DB_PORT="$(htaccess_get DB_PORT)"
DB_USER="$(htaccess_get DB_USER)"
DB_PASS="$(htaccess_get DB_PASS)"
DB_NAME="$(htaccess_get DB_NAME)"
CI_ENV_VALUE="$(htaccess_get CI_ENV)"
[ -n "$DB_HOST" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ] && [ -n "$DB_NAME" ] \
    || die "shared/.htaccess must set DB_HOST, DB_USER, DB_PASS and DB_NAME"
[ -z "$DB_PORT" ] || [[ "$DB_PORT" =~ ^[0-9]+$ ]] || die "DB_PORT must be a number"

if [ -L "$PUBLIC_HTML" ]; then
    [ "$(readlink "$PUBLIC_HTML")" = "$CURRENT" ] || die "$PUBLIC_HTML is a link, but not to $CURRENT"
    [ -L "$CURRENT" ] || die "$CURRENT is not a link"
    PREV="$(readlink "$CURRENT")"
    [ -d "$SITE_DIR/$PREV" ] || die "current points to a missing release ($PREV)"
    say "Live release: ${PREV#releases/}"
    if [ "$PREV" = "releases/$RELEASE" ]; then
        # Re-run of a deploy that already succeeded: nothing to do.
        cat > /dev/null
        if [ -f "$SITE_DIR/$PREV/REVISION" ]; then SHA="$(tr -d '[:space:]' < "$SITE_DIR/$PREV/REVISION")"; fi
        if [ -f "$SITE_DIR/$PREV/VERSION" ]; then VERSION="$(tr -d '[:space:]' < "$SITE_DIR/$PREV/VERSION")"; fi
        RESULT=already-live
        say "=== Release $RELEASE is already live; nothing changed"
        exit 0
    fi
elif [ -d "$PUBLIC_HTML" ]; then
    FIRST_RUN=1
    [ ! -e "$PRE_DEPLOY" ] && [ ! -L "$PRE_DEPLOY" ] || die "$PRE_DEPLOY already exists"
    [ ! -e "$CURRENT" ] && [ ! -L "$CURRENT" ] || die "$CURRENT exists but public_html is a folder"
    [ "$(stat -c %d "$PUBLIC_HTML")" = "$(stat -c %d "$SITE_DIR")" ] \
        || die "public_html and $SITE_DIR are on different file systems"
    say "First deploy: public_html is a folder and will be replaced by a link to $CURRENT"
else
    die "$PUBLIC_HTML doesn't exist"
fi

# ---------------------------------------------------------------- a. unpack
STEP=unpack
[ ! -e "$REL" ] && [ ! -L "$REL" ] || die "Release $RELEASE already exists"
INCOMING="$(mktemp -d "$RELEASES/.incoming-XXXXXX")"
tar -xzf - -C "$INCOMING" --no-same-owner
chmod 755 "$INCOMING"
for f in index.php VERSION REVISION application/config/config.php system/core/CodeIgniter.php; do
    [ -f "$INCOMING/$f" ] || die "The release has no $f"
done
for f in .htaccess uploads maintenance.on .git; do
    [ ! -e "$INCOMING/$f" ] || die "The release must not contain $f"
done
mv -T "$INCOMING" "$REL"
INCOMING=""
UNPACKED=1
touch "$REL"
ln -s ../../shared/.htaccess "$REL/.htaccess"
ln -s ../../shared/uploads "$REL/uploads"
VERSION="$(tr -d '[:space:]' < "$REL/VERSION")"
SHA="$(tr -d '[:space:]' < "$REL/REVISION")"
[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { SHA="-"; die "REVISION is not a full commit sha"; }
say "a. Unpacked release $RELEASE (version $VERSION, commit $SHA)"

# ---------------------------------------------------------------- b. maintenance on
STEP=maintenance-on
touch "$MAINT"
MAINT_ON=1
say "b. Maintenance on"
if [ "$FIRST_RUN" = 1 ]; then
    ln -s "releases/$RELEASE" "$CURRENT"
    CURRENT_CREATED=1
    ln -s "$CURRENT" "$PUBLIC_HTML.deploy-new"
    mv -T "$PUBLIC_HTML" "$PRE_DEPLOY"
    PUBLIC_HTML_SWAPPED=1
    mv -T "$PUBLIC_HTML.deploy-new" "$PUBLIC_HTML"
    say "b. public_html moved to $PRE_DEPLOY and replaced by a link to current"
    restart_php "b."
fi
# Let requests that started before maintenance finish.
sleep "$SETTLE_SECONDS"

# ---------------------------------------------------------------- c. backup
STEP=backup
write_option_file
BACKUP="$BACKUPS/pre-deploy-$(date -u +%Y%m%dT%H%M%SZ)-$RELEASE.sql.gz"
(
    umask 077
    mysqldump --defaults-extra-file="$OPT" --single-transaction --routines --triggers \
        --no-tablespaces "$DB_NAME" | gzip -c > "$BACKUP.partial"
)
gzip -t "$BACKUP.partial" || die "The backup is not a valid gzip file"
size="$(stat -c %s "$BACKUP.partial")"
[ "$size" -ge 1024 ] || die "The backup is suspiciously small ($size bytes)"
gzip -dc "$BACKUP.partial" | tail -n 1 | grep -q 'Dump completed' || die "The backup is incomplete"
mv -f "$BACKUP.partial" "$BACKUP"
chmod 600 "$BACKUP"
say "c. Backup $(basename "$BACKUP") ($size bytes)"

# ---------------------------------------------------------------- d. migrate
STEP=migrate
MIGRATION_STARTED=1
set +e
migrate_out="$(
    cd "$REL" || exit 1
    export DB_HOST DB_PORT DB_USER DB_PASS DB_NAME
    if [ -n "$CI_ENV_VALUE" ]; then export CI_ENV="$CI_ENV_VALUE"; fi
    exec "$PHP" index.php migrate 2>&1
)"
migrate_rc=$?
set -e
printf '%s\n' "$migrate_out" | tail -n 40 | while IFS= read -r line; do say "   migrate: $line"; done
[ "$migrate_rc" -eq 0 ] || die "Migrations failed (exit $migrate_rc)"
say "d. Migrations done"

# ---------------------------------------------------------------- e. switch
STEP=switch
if [ "$FIRST_RUN" = 0 ]; then
    relink "$CURRENT" "releases/$RELEASE"
    SWITCHED=1
fi
say "e. current -> releases/$RELEASE"
restart_php "e."

# ---------------------------------------------------------------- f. health
STEP=health
TMP_BODY="$(mktemp "$SITE_DIR/.health.XXXXXX")"
healthy=0
for ((i = 1; i <= HEALTH_TRIES; i++)); do
    code="$(curl -sS -o "$TMP_BODY" -w '%{http_code}' --max-time 10 \
        -H 'Cache-Control: no-cache' "$HEALTH_URL?deploy=$RELEASE" 2>/dev/null)" || code=000
    # Look at the JSON, not just the HTTP status: development mode can
    # answer 200 with "status":"error" (docs/HEALTH_AND_MAINTENANCE.md).
    if verdict="$("$PHP" -r '
        $body = (string) stream_get_contents(STDIN);
        $j = preg_match("/\\{[^{}]*\\}\\s*\$/", $body, $m) ? json_decode($m[0], true) : null;
        if (!is_array($j)) { echo "no Health JSON in the answer"; exit(1); }
        $s = function ($k) use ($j) { return isset($j[$k]) ? var_export($j[$k], true) : "missing"; };
        echo "status=", $s("status"), " version=", $s("version"), " database=", $s("database"),
             " migrations=", $s("migrations"), " maintenance=", $s("maintenance"),
             " revision=", $s("revision");
        exit(($j["status"] ?? null) === "ok" && ($j["version"] ?? null) === $argv[1]
            && ($j["revision"] ?? null) === $argv[2] ? 0 : 1);
    ' -- "$VERSION" "$SHA" < "$TMP_BODY" 2>/dev/null)"; then
        healthy=1
        say "f. Health ok (HTTP $code, $verdict)"
        break
    fi
    say "f. Health attempt $i/$HEALTH_TRIES not ok (HTTP $code, ${verdict:-no answer}; want version '$VERSION', revision '$SHA')"
    if [ "$i" -lt "$HEALTH_TRIES" ]; then sleep "$HEALTH_WAIT"; fi
done
[ "$healthy" = 1 ] || die "Health check failed"

# ---------------------------------------------------------------- g. maintenance off, cleanup
STEP=maintenance-off
rm -f "$MAINT"
MAINT_ON=0
RESULT=success
say "g. Maintenance off"

STEP=cleanup
live="$(readlink "$CURRENT")"
live="${live#releases/}"
find "$RELEASES" -mindepth 1 -maxdepth 1 -type d ! -name '.*' -printf '%T@ %f\n' \
    | sort -rn | awk -v keep="$KEEP_RELEASES" 'NR > keep { print $2 }' \
    | while IFS= read -r old; do
        if [ -n "$old" ] && [ "$old" != "$live" ]; then
            rm -rf "${RELEASES:?}/$old" && say "g. Removed old release $old"
        fi
    done || say "g. Warning: could not clean up old releases"
find "$BACKUPS" -maxdepth 1 -type f -name 'pre-deploy-*.sql.gz' -printf '%f\n' \
    | sort -r | tail -n +"$((KEEP_BACKUPS + 1))" \
    | while IFS= read -r old; do
        rm -f "${BACKUPS:?}/$old" && say "g. Removed old backup $old"
    done || say "g. Warning: could not clean up old backups"

say "=== Deploy of release $RELEASE done: version $VERSION is live"
