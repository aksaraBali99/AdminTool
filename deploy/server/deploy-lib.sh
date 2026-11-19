# Shared by deploy.sh and rollback.sh (loaded with ".", never run on its own).
# Installed next to them as ~/apps/<app>/bin/deploy-lib.sh. See
# deploy/server/README.md.
#
# The functions use the calling script's variables: SITE, LOG_TAG, ROOT,
# BIN_DIR, APP_DIR, SITE_CONF, BACKUPS, the site paths (SITE_DIR, RELEASES,
# SHARED, LOGS, CURRENT, PUBLIC_HTML, LOG, HISTORY), the database settings
# (DB_*), and the temporary files (OPT, ERRF, RESTORE_WORK, TMP_BODY) the
# caller removes on exit.
#
# Rules for this file and both scripts: no bash process substitution (the
# server has no /dev/fd), nothing secret on a command line or in a log, and
# everything logged goes through say(), which masks database names.

now() { date -u +%Y-%m-%dT%H:%M:%SZ; }

# Replaces the database password, name and user with placeholders: database
# errors (from migrate, mysqldump or mysql) can name them, and the log also
# ends up in the GitHub Actions log. The longer of name and user goes first,
# in case one contains the other. Values under 4 characters are left alone
# rather than blanking unrelated text.
mask() {
    local s="$1" a="$DB_NAME" ra="<db>" b="$DB_USER" rb="<db-user>"
    if [ "${#b}" -gt "${#a}" ]; then a="$DB_USER" ra="<db-user>" b="$DB_NAME" rb="<db>"; fi
    if [ "${#DB_PASS}" -ge 4 ]; then s="${s//"$DB_PASS"/<db-password>}"; fi
    if [ "${#a}" -ge 4 ]; then s="${s//"$a"/$ra}"; fi
    if [ "${#b}" -ge 4 ]; then s="${s//"$b"/$rb}"; fi
    printf '%s' "$s"
}

say() {
    local line
    line="$(now) [$LOG_TAG] $(mask "$*")"
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

# ---------------------------------------------------------------- the site

# The site's domain and folders, from bin/sites/<site>.conf; dies if
# anything is missing.
site_paths() {
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
    LOG="$LOGS/deploy.log"
    HISTORY="$LOGS/history.log"

    local d
    for d in "$SITE_DIR" "$RELEASES" "$SHARED" "$LOGS" "$BACKUPS"; do
        [ -d "$d" ] || die "Missing folder $d (see deploy/server/README.md)"
    done
}

# One deploy or rollback of a site at a time (file descriptor 9, held until
# the script exits).
take_lock() {
    exec 9> "$SITE_DIR/.deploy.lock"
    flock -n 9 || die "Another deploy of $SITE is running"
}

# Commands, the shared files, MAINTENANCE_FILE, the database settings and
# ci_env, checked before anything changes. Sets MAINT, DB_*, CI_ENV_VALUE,
# SITE_CI_ENV and EXPECTED_OUTBOUND.
site_settings() { # <command>...
    local c shown
    for c in "$@"; do
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
    [ ! -e "$MAINT" ] || die "Maintenance is already on ($MAINT exists); refusing to go on"

    DB_HOST="$(htaccess_get DB_HOST)"
    DB_PORT="$(htaccess_get DB_PORT)"
    DB_USER="$(htaccess_get DB_USER)"
    DB_PASS="$(htaccess_get DB_PASS)"
    DB_NAME="$(htaccess_get DB_NAME)"
    CI_ENV_VALUE="$(htaccess_get CI_ENV)"
    [ -n "$DB_HOST" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ] && [ -n "$DB_NAME" ] \
        || die "shared/.htaccess must set DB_HOST, DB_USER, DB_PASS and DB_NAME"
    [ -z "$DB_PORT" ] || [[ "$DB_PORT" =~ ^[0-9]+$ ]] || die "DB_PORT must be a number"

    # What kind of site this is. The site config says what it must be; the
    # site's .htaccess says what the app actually runs as. They must agree, so a
    # copied or half-edited .htaccess can't turn a DEV site into one that sends
    # real WhatsApp messages, or a PROD site into one that silently doesn't.
    SITE_CI_ENV="$(conf_get ci_env)"
    case "$SITE_CI_ENV" in
        production|testing|development) ;;
        "") die "$SITE_CONF has no ci_env=; add ci_env=production, ci_env=testing or ci_env=development (see deploy/server/README.md)" ;;
        *) die "$SITE_CONF has ci_env=$SITE_CI_ENV; it must be exactly production, testing or development" ;;
    esac
    if [ "$CI_ENV_VALUE" != "$SITE_CI_ENV" ]; then
        # Only a known value is named: nothing else from .htaccess is ever logged.
        case "$CI_ENV_VALUE" in
            production|testing|development) shown="'$CI_ENV_VALUE'" ;;
            "") shown="not set" ;;
            *) shown="another value" ;;
        esac
        die "CI_ENV in shared/.htaccess is $shown but $SITE_CONF says ci_env=$SITE_CI_ENV; make them the same (SetEnv CI_ENV $SITE_CI_ENV in shared/.htaccess), or fix ci_env if the site config is wrong"
    fi
    if [ "$SITE_CI_ENV" = production ]; then EXPECTED_OUTBOUND=live; else EXPECTED_OUTBOUND=blocked; fi
}

# With public_html a link: the live release, as "releases/<id>" in LIVE_LINK
# and "<id>" in LIVE, after checking it's sound (also what a rollback goes
# back to, so it must use this site's shared files, not e.g. those of a
# folder it was copied from).
live_release() {
    local f
    [ "$(readlink "$PUBLIC_HTML")" = "$CURRENT" ] || die "$PUBLIC_HTML is a link, but not to $CURRENT"
    [ -L "$CURRENT" ] || die "$CURRENT is not a link"
    LIVE_LINK="$(readlink "$CURRENT")"
    [ -d "$SITE_DIR/$LIVE_LINK" ] || die "current points to a missing release ($LIVE_LINK)"
    LIVE="${LIVE_LINK#releases/}"
    say "Live release: $LIVE"
    for f in .htaccess uploads; do
        [ "$(readlink -f "$SITE_DIR/$LIVE_LINK/$f")" = "$(readlink -f "$SHARED/$f")" ] \
            || die "The live release's $f doesn't lead to $SHARED/$f; fix its link first (see deploy/server/README.md)"
    done
}

# VERSION and REVISION of a release folder ("-" when missing).
release_version() { if [ -f "$1/VERSION" ]; then tr -d '[:space:]' < "$1/VERSION"; else printf -- '-'; fi; }
release_revision() { if [ -f "$1/REVISION" ]; then tr -d '[:space:]' < "$1/REVISION"; else printf -- '-'; fi; }

# ---------------------------------------------------------------- the database

# A fresh, empty file for a command's error output, which is then logged
# (masked) instead of going straight to the SSH output unmasked.
new_errfile() {
    if [ -z "$ERRF" ]; then ERRF="$(mktemp "$SITE_DIR/.stderr.XXXXXX")" || { ERRF=""; return 1; }; fi
    : > "$ERRF"
}
log_errfile() { # <command name>
    local l
    if [ -n "$ERRF" ] && [ -s "$ERRF" ]; then
        while IFS= read -r l; do say "   $1: $l"; done < "$ERRF"
        : > "$ERRF"
    fi
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

# The newest migration the database has run (prints nothing on failure).
db_migration_version() {
    new_errfile || return 1
    local v
    v="$(mysql --defaults-extra-file="$OPT" -N -B -e "SELECT version FROM migrations" "$DB_NAME" < /dev/null 2> "$ERRF")" \
        || { log_errfile mysql; return 1; }
    printf '%s' "$v"
}

# The newest migration a release folder contains ("0" when it has none).
release_migration_version() {
    local f latest=0 name
    for f in "$1"/application/migrations/*.php; do
        [ -f "$f" ] || continue
        name="$(basename "$f")"
        if [[ "$name" =~ ^([0-9]{14})_ ]] && [[ "${BASH_REMATCH[1]}" > "$latest" ]]; then
            latest="${BASH_REMATCH[1]}"
        fi
    done
    printf '%s' "$latest"
}

# Checks a backup file is usable: valid gzip, at least 1 KB, and ending with
# mysqldump's "Dump completed" line. Sets CHECK_ERROR on failure.
check_backup_file() { # <file>
    local size
    CHECK_ERROR=""
    [ -f "$1" ] || { CHECK_ERROR="missing"; return 1; }
    gzip -t "$1" 2>/dev/null || { CHECK_ERROR="not a valid gzip file"; return 1; }
    size="$(stat -c %s "$1")"
    [ "$size" -ge 1024 ] || { CHECK_ERROR="suspiciously small ($size bytes)"; return 1; }
    gzip -dc "$1" | tail -n 1 | grep -q 'Dump completed' || { CHECK_ERROR="incomplete (no \"Dump completed\" line)"; return 1; }
}

# Dumps the database to <file> (via <file>.partial, mode 600, checked with
# check_backup_file). Sets DUMP_ERROR and leaves no file on failure.
dump_database() { # <file>
    local rc=0
    DUMP_ERROR=""
    new_errfile || { DUMP_ERROR="Could not create a file for error output"; return 1; }
    (
        umask 077
        mysqldump --defaults-extra-file="$OPT" --single-transaction --routines --triggers \
            --no-tablespaces "$DB_NAME" 2> "$ERRF" | gzip -c > "$1.partial"
    ) || rc=$?
    log_errfile mysqldump
    if [ "$rc" -ne 0 ]; then
        DUMP_ERROR="The backup failed (exit $rc)"
    elif ! check_backup_file "$1.partial"; then
        DUMP_ERROR="The backup is $CHECK_ERROR"
    fi
    if [ -n "$DUMP_ERROR" ]; then
        rm -f "$1.partial"
        return 1
    fi
    mv -f "$1.partial" "$1"
    chmod 600 "$1"
}

# Puts the database back exactly as in <backup>: drops the tables that
# aren't in it (e.g. added by a migration since), then loads it. Temporary
# files, not process substitution: the server has no /dev/fd. Any failure
# returns non-zero, never a silent "restored".
restore_database() { # <backup>
    local backup="$1" t
    say "Restoring the database from $(basename "$backup")"
    [ -s "$backup" ] && [ -n "$OPT" ] || { say "No backup or option file to restore from"; return 1; }

    # A rollback can restore twice (the target, then its undo): one work
    # folder at a time, so none is left behind.
    if [ -n "$RESTORE_WORK" ]; then rm -rf "$RESTORE_WORK"; RESTORE_WORK=""; fi
    RESTORE_WORK="$(mktemp -d "$SITE_DIR/.restore.XXXXXX")" || { say "Could not create a work folder for the restore"; return 1; }
    new_errfile || { say "Could not create a file for error output"; return 1; }
    if ! gzip -dc "$backup" | sed -n 's/^CREATE TABLE `\([^`]*\)`.*/\1/p' | LC_ALL=C sort -u > "$RESTORE_WORK/in-backup" \
        || ! [ -s "$RESTORE_WORK/in-backup" ]; then
        say "Could not list the tables in the backup"; return 1
    fi
    if ! mysql --defaults-extra-file="$OPT" -N -B -e "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'" "$DB_NAME" < /dev/null 2> "$ERRF" \
        | cut -f1 | LC_ALL=C sort -u > "$RESTORE_WORK/current"; then
        log_errfile mysql; say "Could not list the tables in the database"; return 1
    fi
    if ! LC_ALL=C comm -13 "$RESTORE_WORK/in-backup" "$RESTORE_WORK/current" > "$RESTORE_WORK/extra"; then
        say "Could not compare the table lists"; return 1
    fi
    while IFS= read -r t; do
        if ! [[ "$t" =~ ^[A-Za-z0-9_]+$ ]]; then
            say "A table with an unusual name isn't in the backup; drop it by hand"; return 1
        fi
        say "Dropping table $t (not in the backup)"
        # stdin from /dev/null: the loop reads the table list on stdin, and
        # mysql must not swallow the rest of it.
        if ! mysql --defaults-extra-file="$OPT" -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE \`$t\`" "$DB_NAME" < /dev/null 2> "$ERRF"; then
            log_errfile mysql; say "Could not drop table $t"; return 1
        fi
    done < "$RESTORE_WORK/extra"

    if ! gzip -dc "$backup" | mysql --defaults-extra-file="$OPT" "$DB_NAME" 2> "$ERRF"; then
        log_errfile mysql; say "Loading the backup failed"; return 1
    fi
    say "Database restored"
}

# ---------------------------------------------------------------- the switch

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

# Health must answer "status":"ok", <version>, <revision> (full commit sha)
# and "outbound" <expected>: the release is what the site serves, and it runs
# as the kind of site it should. Retried HEALTH_TRIES times, HEALTH_WAIT s
# apart. Logs each try with <step>. Returns non-zero if it never passes.
health_check() { # <step> <version> <revision> <expected outbound> <query tag>
    local step="$1" want_version="$2" want_revision="$3" want_outbound="$4" tag="$5" i code verdict
    [ -n "$TMP_BODY" ] || TMP_BODY="$(mktemp "$SITE_DIR/.health.XXXXXX")"
    for ((i = 1; i <= HEALTH_TRIES; i++)); do
        code="$(curl -sS -o "$TMP_BODY" -w '%{http_code}' --max-time 10 \
            -H 'Cache-Control: no-cache' "$HEALTH_URL?$tag" 2>/dev/null)" || code=000
        # Look at the JSON, not just the HTTP status: development mode can
        # answer 200 with "status":"error" (docs/HEALTH_AND_MAINTENANCE.md).
        if verdict="$("$PHP" -r '
            $body = (string) stream_get_contents(STDIN);
            $j = preg_match("/\\{[^{}]*\\}\\s*\$/", $body, $m) ? json_decode($m[0], true) : null;
            if (!is_array($j)) { echo "no Health JSON in the answer"; exit(1); }
            $s = function ($k) use ($j) { return isset($j[$k]) ? var_export($j[$k], true) : "missing"; };
            echo "status=", $s("status"), " version=", $s("version"), " database=", $s("database"),
                 " migrations=", $s("migrations"), " maintenance=", $s("maintenance"),
                 " revision=", $s("revision"), " outbound=", $s("outbound");
            exit(($j["status"] ?? null) === "ok" && ($j["version"] ?? null) === $argv[1]
                && ($j["revision"] ?? null) === $argv[2] && ($j["outbound"] ?? null) === $argv[3] ? 0 : 1);
        ' -- "$want_version" "$want_revision" "$want_outbound" < "$TMP_BODY" 2>/dev/null)"; then
            say "$step Health ok (HTTP $code, $verdict)"
            return 0
        fi
        say "$step Health attempt $i/$HEALTH_TRIES not ok (HTTP $code, ${verdict:-no answer}; want version '$want_version', revision '$want_revision', outbound '$want_outbound')"
        if [ "$i" -lt "$HEALTH_TRIES" ]; then sleep "$HEALTH_WAIT"; fi
    done
    return 1
}
