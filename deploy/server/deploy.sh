#!/bin/bash
# Deploys one release of AdminTool to one site, reading the release tarball
# (.tar.gz, made by .github/workflows/deploy.yml) from stdin:
#
#   deploy.sh <site> <release-id> < release.tar.gz
#
# Normally started by gate.sh (the GitHub key's forced command), never by
# hand. Installed as ~/apps/<app>/bin/deploy.sh, next to deploy-lib.sh (the
# helpers it shares with rollback.sh). See deploy/server/README.md for the
# layout and the one-time setup.
#
# Where things are (<app> is the folder above this script's bin/, so
# nothing here names a particular app):
#   ~/apps/<app>/bin/sites/<site>.conf    domain=<the site's domain>
#                                         ci_env=<production|testing|development>
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
#   d. php index.php migrate in the new release; on a site whose ci_env isn't
#      production, then disable the WhatsApp token in data_konfig
#   e. "current" -> releases/<id>, switched atomically
#   f. Health must answer "status":"ok", the release's VERSION and its
#      REVISION (full commit sha), i.e. the new release is what is served,
#      and "outbound" "live" on production, "blocked" anywhere else;
#      retried for about 3 minutes
#   g. maintenance off; keep the newest 3 releases (plus the live one and the
#      one live before it) and 5 pre-deploy backups
#   h. on any failure after (b): restore the database if migrations started,
#      put the previous release back, maintenance off, exit non-zero
#
# history.log and the RESULT line record previous=<the release that was live
# before>, which rollback.sh uses as "the previous release".
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
# About 3 minutes: after "current" moves, PHP workers may keep running the
# old release for a while: the realpath cache can resolve the old folder for
# up to realpath_cache_ttl (120 s by default), and opcache can reuse the old
# compiled files until it next checks them (opcache.revalidate_freq).
HEALTH_TRIES="${DEPLOY_HEALTH_TRIES:-18}"
HEALTH_WAIT="${DEPLOY_HEALTH_WAIT:-10}"
SETTLE_SECONDS="${DEPLOY_SETTLE_SECONDS:-3}"
KEEP_RELEASES=3
KEEP_BACKUPS=5

if [ ! -f "$BIN_DIR/deploy-lib.sh" ]; then
    echo "deploy.sh: $BIN_DIR/deploy-lib.sh is missing; install it next to deploy.sh (see deploy/server/README.md)" >&2
    echo "RESULT site=$SITE release=$RELEASE sha=- version=- backup= previous=- result=failed"
    exit 1
fi
# shellcheck source=deploy-lib.sh
. "$BIN_DIR/deploy-lib.sh"

LOG_TAG="$SITE $RELEASE"
SITE_CONF="$BIN_DIR/sites/$SITE.conf"
BACKUPS="$APP_DIR/backups/$SITE"
# The rest depends on the site's domain (from SITE_CONF) and is set by
# site_paths().
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
LIVE="" LIVE_LINK=""
MIGRATION_STARTED=0
BACKUP=""
OPT=""
INCOMING=""
TMP_BODY=""
ERRF=""
RESTORE_WORK=""
SHA="-"
VERSION="-"
DB_HOST="" DB_PORT="" DB_USER="" DB_PASS="" DB_NAME="" CI_ENV_VALUE=""
SITE_CI_ENV="" EXPECTED_OUTBOUND=""
DOMAIN="" PUBLIC_HTML="" HEALTH_URL=""

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
        restore_database "$BACKUP" || db_ok=0
    fi

    if [ "$UNPACKED" = 1 ] && [ "$(readlink "$CURRENT" 2>/dev/null)" != "releases/$RELEASE" ]; then
        rm -rf "$REL" && say "Removed the failed release folder"
    fi

    if [ "$db_ok" = 1 ]; then
        if [ "$MAINT_ON" = 1 ]; then
            rm -f "$MAINT" && say "Maintenance off"
        fi
        if [ "$MAINT_ON" = 1 ]; then
            RESULT=rolled-back
            say "Rolled back: the previous release is live and the database is as before"
        else
            RESULT=failed
            say "Nothing was changed: the live release and the database are as before"
        fi
    else
        RESULT=rollback-incomplete
        say "ROLLBACK INCOMPLETE: maintenance is left ON on purpose."
        say "Fix by hand: see 'When a rollback is incomplete' in deploy/server/README.md. The backup is $BACKUP"
    fi
}

on_exit() {
    local rc=$? backup_name="" previous
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
    [ -n "$ERRF" ] && rm -f "$ERRF"
    [ -n "$RESTORE_WORK" ] && rm -rf "$RESTORE_WORK"
    [ -n "$INCOMING" ] && rm -rf "$INCOMING"
    if [ -n "$BACKUP" ] && [ -f "$BACKUP" ]; then backup_name="$(basename "$BACKUP")"; fi
    # The release that was live before this deploy ("-" on the first one).
    previous="${PREV#releases/}"
    previous="${previous:--}"
    if [ -d "$LOGS" ]; then
        printf '%s site=%s release=%s sha=%s version=%s backup=%s previous=%s result=%s\n' \
            "$(now)" "$SITE" "$RELEASE" "$SHA" "$VERSION" "$backup_name" "$previous" "$RESULT" \
            >> "$HISTORY" 2>/dev/null
    fi
    # Read by the workflow for its job summary.
    printf 'RESULT site=%s release=%s sha=%s version=%s backup=%s previous=%s result=%s\n' \
        "$SITE" "$RELEASE" "$SHA" "$VERSION" "$backup_name" "$previous" "$RESULT" 2>/dev/null
    if [ "$RESULT" = success ] || [ "$RESULT" = already-live ]; then exit 0; fi
    [ "$rc" -ne 0 ] || rc=1
    exit "$rc"
}
trap on_exit EXIT
trap 'exit 143' TERM INT

# ---------------------------------------------------------------- preflight
site_paths
REL="$RELEASES/$RELEASE"
take_lock

say "=== Deploy of release $RELEASE started"

site_settings "$PHP" mysqldump mysql gzip curl tar flock

if [ -L "$PUBLIC_HTML" ]; then
    live_release
    PREV="$LIVE_LINK"
    if [ "$PREV" = "releases/$RELEASE" ]; then
        # Re-run of a deploy that already succeeded: nothing to do.
        cat > /dev/null
        SHA="$(release_revision "$SITE_DIR/$PREV")"
        VERSION="$(release_version "$SITE_DIR/$PREV")"
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
VERSION="$(release_version "$REL")"
SHA="$(release_revision "$REL")"
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
dump_database "$BACKUP" || die "$DUMP_ERROR"
say "c. Backup $(basename "$BACKUP") ($(stat -c %s "$BACKUP") bytes)"

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

# Outside production: overwrite the WhatsApp token (disable_messaging() in
# deploy-lib.sh). After migrations, so it also covers a token a migration
# might add; before the switch, so a failure here rolls back like any other
# step (the restore then puts the database, token included, back as it was
# before the deploy).
if [ "$SITE_CI_ENV" != production ]; then
    STEP=disable-messaging
    disable_messaging "d." || die "Could not disable the WhatsApp token in data_konfig"
fi

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
health_check "f." "$VERSION" "$SHA" "$EXPECTED_OUTBOUND" "deploy=$RELEASE" || die "Health check failed"

# ---------------------------------------------------------------- g. maintenance off, cleanup
STEP=maintenance-off
rm -f "$MAINT"
MAINT_ON=0
RESULT=success
say "g. Maintenance off"

STEP=cleanup
live="$(readlink "$CURRENT")"
live="${live#releases/}"
# Keep the newest 3, the live release, and the release that was live before
# this deploy (its recorded previous=, what "roll back to previous" goes to):
# after a rollback that one can be older than the newest 3.
previous="${PREV#releases/}"
find "$RELEASES" -mindepth 1 -maxdepth 1 -type d ! -name '.*' -printf '%T@ %f\n' \
    | sort -rn | awk -v keep="$KEEP_RELEASES" 'NR > keep { print $2 }' \
    | while IFS= read -r old; do
        if [ -n "$old" ] && [ "$old" != "$live" ] && [ "$old" != "$previous" ]; then
            rm -rf "${RELEASES:?}/$old" && say "g. Removed old release $old"
        fi
    done || say "g. Warning: could not clean up old releases"
# Only pre-deploy backups: pre-rollback backups (rollback.sh) are never
# deleted automatically.
find "$BACKUPS" -maxdepth 1 -type f -name 'pre-deploy-*.sql.gz' -printf '%f\n' \
    | sort -r | tail -n +"$((KEEP_BACKUPS + 1))" \
    | while IFS= read -r old; do
        rm -f "${BACKUPS:?}/$old" && say "g. Removed old backup $old"
    done || say "g. Warning: could not clean up old backups"

say "=== Deploy of release $RELEASE done: version $VERSION is live"
