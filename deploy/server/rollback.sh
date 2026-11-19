#!/bin/bash
# Rolls a site back to an earlier release that is still on the server:
#
#   rollback.sh <site> <release-id|previous> <code|code-db>
#
# Normally started by gate.sh from the GitHub "Rollback" workflow; can also be
# run by hand over SSH in an emergency. Installed as
# ~/apps/<app>/bin/rollback.sh, next to deploy.sh and deploy-lib.sh. Shares
# the site's deploy lock, so it never runs at the same time as a deploy. See
# "Rolling back a live release" in deploy/server/README.md.
#
#   code      switch the code back to <release> (any kept release; "previous"
#             is the release that was live when the live one was deployed).
#             Refused when the database has migrations <release> doesn't know.
#   code-db   switch the code back to the previous release AND put the
#             database back as it was before the live release was deployed
#             (its pre-deploy backup). Everything written to the database
#             since then is lost from the live database; it is kept in the
#             pre-rollback backup made first. Only allowed when the live
#             release became live through a successful deploy.
#
# Steps (logged to the site's deploy.log, like a deploy):
#   a. checks (nothing changes if one fails)
#   b. maintenance on
#   c. code-db only: dump the database to
#      ~/apps/<app>/backups/<site>/pre-rollback-<UTC time>-<live>.sql.gz
#      (checked like a deploy's backup; never deleted automatically), then
#      restore the live release's pre-deploy backup
#   d. "current" -> the target release, PHP restart
#   e. Health must show the target's VERSION and REVISION and the right
#      "outbound"
#   f. maintenance off; history.log line with result rolled-back-manual
# If anything after (b) fails: the code is switched back, and for code-db the
# pre-rollback backup is restored; maintenance off, result rollback-failed.
# If that restore fails too: maintenance stays ON, result rollback-incomplete.
set -Eeuo pipefail
umask 022

SITE="${1-}"
TARGET_ARG="${2-}"
TYPE="${3-}"
if ! [[ "$SITE" =~ ^[a-z0-9][a-z0-9-]{0,31}$ && "$TARGET_ARG" =~ ^[0-9A-Za-z._-]{1,64}$ ]] \
    || [[ "$TARGET_ARG" == .* || "$TARGET_ARG" == -* ]] \
    || ! [[ "$TYPE" == code || "$TYPE" == code-db ]]; then
    echo "usage: rollback.sh <site> <release-id|previous> <code|code-db>" >&2
    exit 2
fi

# DEPLOY_* overrides exist for tests/deploy/; gate.sh clears the environment.
ROOT="${DEPLOY_ROOT:-$HOME}"
BIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
APP_DIR="$(dirname "$BIN_DIR")"
PHP="${DEPLOY_PHP:-php}"
HEALTH_TRIES="${DEPLOY_HEALTH_TRIES:-18}"
HEALTH_WAIT="${DEPLOY_HEALTH_WAIT:-10}"
SETTLE_SECONDS="${DEPLOY_SETTLE_SECONDS:-3}"

if [ ! -f "$BIN_DIR/deploy-lib.sh" ]; then
    echo "rollback.sh: $BIN_DIR/deploy-lib.sh is missing; install it next to rollback.sh (see deploy/server/README.md)" >&2
    echo "RESULT site=$SITE action=rollback type=$TYPE from=- to=- sha=- version=- backup= result=failed"
    exit 1
fi
# shellcheck source=deploy-lib.sh
. "$BIN_DIR/deploy-lib.sh"

# Nothing is read from stdin.
exec < /dev/null
trap '' HUP PIPE

LOG_TAG="$SITE rollback"
SITE_CONF="$BIN_DIR/sites/$SITE.conf"
BACKUPS="$APP_DIR/backups/$SITE"
SITE_DIR="" RELEASES="" SHARED="" LOGS="" CURRENT="" PRE_DEPLOY="" LOG="" HISTORY=""
DOMAIN="" PUBLIC_HTML="" HEALTH_URL=""
DB_HOST="" DB_PORT="" DB_USER="" DB_PASS="" DB_NAME="" CI_ENV_VALUE=""
SITE_CI_ENV="" EXPECTED_OUTBOUND=""
LIVE="" LIVE_LINK=""

# State, used to undo a failed rollback.
STEP=preflight
RESULT=failed
MAINT=""
MAINT_ON=0
SWITCHED=0
DB_CHANGED=0
FROM="-"
TO="-"
SHA="-"
VERSION="-"
PRE_ROLLBACK=""
PRE_DEPLOY_BACKUP=""
OPT=""
ERRF=""
RESTORE_WORK=""
TMP_BODY=""

# Value of <key>=... in one history.log line (empty when missing).
hist_field() { # <line> <key>
    printf '%s\n' "$1" | tr ' ' '\n' | sed -n "s/^$2=//p" | head -n 1
}

undo() {
    local ok=1
    if [ -n "$CURRENT" ]; then rm -f "$CURRENT.deploy-new"; fi
    if [ -n "$PRE_ROLLBACK" ]; then rm -f "$PRE_ROLLBACK.partial"; fi

    if [ "$SWITCHED" = 1 ]; then
        if relink "$CURRENT" "releases/$FROM"; then
            say "current points back to releases/$FROM"
            restart_php "  "
        else
            say "Could not point current back to releases/$FROM"
            ok=0
        fi
    fi
    if [ "$DB_CHANGED" = 1 ]; then
        if restore_database "$PRE_ROLLBACK"; then
            say "The database is back as it was before the rollback"
        else
            ok=0
        fi
    fi

    if [ "$ok" = 1 ]; then
        if [ "$MAINT_ON" = 1 ]; then
            rm -f "$MAINT" && say "Maintenance off"
        fi
        if [ "$SWITCHED" = 1 ] || [ "$DB_CHANGED" = 1 ]; then
            RESULT=rollback-failed
            say "Rollback undone: $FROM is live again and the database is as before the rollback"
        else
            RESULT=failed
            say "Nothing was changed: the live release and the database are as before"
        fi
    else
        RESULT=rollback-incomplete
        say "ROLLBACK INCOMPLETE: maintenance is left ON on purpose."
        say "Fix by hand: see 'When a rollback is incomplete' in deploy/server/README.md. The pre-rollback backup is $PRE_ROLLBACK"
    fi
}

on_exit() {
    local rc=$? backup_name=""
    set +eu
    trap - EXIT
    trap '' TERM INT
    if [ "$RESULT" != rolled-back-manual ]; then
        say "FAILED during step '$STEP' (exit $rc)"
        undo
    fi
    [ -n "$OPT" ] && rm -f "$OPT"
    [ -n "$TMP_BODY" ] && rm -f "$TMP_BODY"
    [ -n "$ERRF" ] && rm -f "$ERRF"
    [ -n "$RESTORE_WORK" ] && rm -rf "$RESTORE_WORK"
    if [ -n "$PRE_ROLLBACK" ] && [ -f "$PRE_ROLLBACK" ]; then backup_name="$(basename "$PRE_ROLLBACK")"; fi
    if [ -n "$LOGS" ] && [ -d "$LOGS" ]; then
        printf '%s site=%s action=rollback type=%s from=%s to=%s sha=%s version=%s backup=%s result=%s\n' \
            "$(now)" "$SITE" "$TYPE" "$FROM" "$TO" "$SHA" "$VERSION" "$backup_name" "$RESULT" \
            >> "$HISTORY" 2>/dev/null
    fi
    # Read by the Rollback workflow for its job summary.
    printf 'RESULT site=%s action=rollback type=%s from=%s to=%s sha=%s version=%s backup=%s result=%s\n' \
        "$SITE" "$TYPE" "$FROM" "$TO" "$SHA" "$VERSION" "$backup_name" "$RESULT" 2>/dev/null
    if [ "$RESULT" = rolled-back-manual ]; then exit 0; fi
    [ "$rc" -ne 0 ] || rc=1
    exit "$rc"
}
trap on_exit EXIT
trap 'exit 143' TERM INT

# ---------------------------------------------------------------- a. checks
site_paths
take_lock
say "=== Rollback ($TYPE) to $TARGET_ARG started"
site_settings "$PHP" mysqldump mysql gzip curl flock

[ -L "$PUBLIC_HTML" ] || die "public_html is not a link to deploy/current: nothing has been deployed here to roll back"
live_release
FROM="$LIVE"
write_option_file

# How the live release became live, from history.log. A deploy line has no
# action=; a rollback line has action=rollback. The last line that changed
# the live release ends in result=success (deploy) or rolled-back-manual.
deploy_line=""
last_change=""
if [ -f "$HISTORY" ]; then
    deploy_line="$(awk -v r="$LIVE" 'index($0, " action=") == 0 && index($0, " release=" r " ") > 0 && / result=success$/ { l = $0 } END { print l }' "$HISTORY")"
    last_change="$(awk '/ result=(success|rolled-back-manual)$/ { l = $0 } END { print l }' "$HISTORY")"
fi
recorded_previous="$(hist_field "$deploy_line" previous)"
if [ "$recorded_previous" = "-" ]; then recorded_previous=""; fi

kept="$(find "$RELEASES" -mindepth 1 -maxdepth 1 -type d ! -name '.*' -printf '%f\n' | LC_ALL=C sort | tr '\n' ' ')"

if [ "$TARGET_ARG" = previous ]; then
    [ -n "$recorded_previous" ] \
        || die "The previous release isn't recorded for live release $LIVE (no deploy line with previous= in history.log); name the release to roll back to (kept: $kept)"
    TO="$recorded_previous"
else
    TO="$TARGET_ARG"
fi

[ "$TO" != "$LIVE" ] || die "Release $TO is already live"
[ -d "$RELEASES/$TO" ] && [ ! -L "$RELEASES/$TO" ] \
    || die "Release $TO isn't on the server (kept: $kept)"
TARGET="$RELEASES/$TO"
for f in index.php VERSION REVISION; do
    [ -f "$TARGET/$f" ] || die "Release $TO has no $f"
done
for f in .htaccess uploads; do
    [ "$(readlink -f "$TARGET/$f")" = "$(readlink -f "$SHARED/$f")" ] \
        || die "Release $TO's $f doesn't lead to $SHARED/$f"
done
# A release from before the WhatsApp/email guard (EHU-0023-6) would let the
# site send real messages again, whatever ci_env says.
grep -qs 'function outbound_is_live' "$TARGET/application/helpers/outbound_helper.php" \
    || die "Release $TO is from before the WhatsApp/email guard (no outbound_is_live() in application/helpers/outbound_helper.php); rolling back to it isn't allowed; ask a developer"
VERSION="$(release_version "$TARGET")"
SHA="$(release_revision "$TARGET")"
[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { SHA="-"; die "Release $TO's REVISION is not a full commit sha"; }

new_errfile || die "Could not create a file for error output"
db_version="$(db_migration_version)" && [ -n "$db_version" ] \
    || die "Could not read the database's migration version"
target_migration="$(release_migration_version "$TARGET")"

if [ "$TYPE" = code ]; then
    if [[ "$db_version" > "$target_migration" ]]; then
        die "The database has migrations release $TO doesn't know (database at $db_version, $TO's newest is $target_migration); use code and database, or ask a developer"
    fi
else
    [ -n "$recorded_previous" ] \
        || die "Code and database needs the previous release of $LIVE, which isn't recorded in history.log; ask a developer"
    [ "$TO" = "$recorded_previous" ] \
        || die "Code and database only goes back to the previous release ($recorded_previous), not to $TO"
    if [ -n "$last_change" ] && [ -n "$(hist_field "$last_change" action)" ]; then
        die "The live release came from a rollback; its backup is too old to restore safely; ask a developer"
    fi
    [ -n "$deploy_line" ] && [ "$last_change" = "$deploy_line" ] \
        || die "history.log doesn't show $LIVE becoming live through a successful deploy; its backup can't be trusted; ask a developer"
    name="$(hist_field "$deploy_line" backup)"
    [[ "$name" == pre-deploy-*-"$LIVE".sql.gz ]] \
        || die "No pre-deploy backup of $LIVE is recorded in history.log; ask a developer"
    PRE_DEPLOY_BACKUP="$BACKUPS/$name"
    check_backup_file "$PRE_DEPLOY_BACKUP" \
        || die "The pre-deploy backup of $LIVE ($name) is $CHECK_ERROR; ask a developer"
fi
say "a. Rolling back from $LIVE to $TO (version $VERSION, commit $SHA; database at $db_version, $TO's newest migration $target_migration)"

# ---------------------------------------------------------------- b. maintenance on
STEP=maintenance-on
touch "$MAINT"
MAINT_ON=1
say "b. Maintenance on"
sleep "$SETTLE_SECONDS"

# ---------------------------------------------------------------- c. database (code-db)
if [ "$TYPE" = code-db ]; then
    STEP=backup
    PRE_ROLLBACK="$BACKUPS/pre-rollback-$(date -u +%Y%m%dT%H%M%SZ)-$LIVE.sql.gz"
    dump_database "$PRE_ROLLBACK" || { PRE_ROLLBACK=""; die "$DUMP_ERROR"; }
    say "c. Pre-rollback backup $(basename "$PRE_ROLLBACK") ($(stat -c %s "$PRE_ROLLBACK") bytes)"

    STEP=restore
    DB_CHANGED=1
    restore_database "$PRE_DEPLOY_BACKUP" || die "Could not restore the database from $(basename "$PRE_DEPLOY_BACKUP")"
    say "c. Database as it was before $LIVE was deployed ($(basename "$PRE_DEPLOY_BACKUP"))"
fi

# ---------------------------------------------------------------- d. switch
STEP=switch
relink "$CURRENT" "releases/$TO"
SWITCHED=1
say "d. current -> releases/$TO"
restart_php "d."

# ---------------------------------------------------------------- e. health
STEP=health
health_check "e." "$VERSION" "$SHA" "$EXPECTED_OUTBOUND" "rollback=$TO" || die "Health check failed"

# ---------------------------------------------------------------- f. maintenance off
STEP=maintenance-off
rm -f "$MAINT"
MAINT_ON=0
RESULT=rolled-back-manual
say "f. Maintenance off"
say "=== Rolled back from $FROM to $TO ($TYPE): version $VERSION is live"
