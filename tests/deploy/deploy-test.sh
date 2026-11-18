#!/usr/bin/env bash
# Integration test for deploy/server/deploy.sh: real deploys, rollbacks and
# refusals against a scratch database and a fake site served by `php -S`.
# Linux only (GNU tools, flock); runs in CI (job "deploy-scripts" in
# .github/workflows/test.yml):
#
#   TEST_DB_PASS=... bash tests/deploy/deploy-test.sh
#
# Needs php, mysql, mysqldump, curl, flock and a MySQL account that may
# create databases and users (TEST_DB_HOST/PORT/USER/PASS, as for
# tests/run-tests.sh). It creates and drops its own database
# (admintool_deploy_test) and user (deploy_test); nothing else is touched.
set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
TEST_DB_HOST="${TEST_DB_HOST:-127.0.0.1}"
TEST_DB_PORT="${TEST_DB_PORT:-3306}"
TEST_DB_USER="${TEST_DB_USER:-root}"
: "${TEST_DB_PASS:?TEST_DB_PASS is not set}"

DB=admintool_deploy_test
DB_USER=deploy_test
# Spaces, #, $ and a backslash: checks the option file's quoting.
DB_PASS='Dep$loy p@ss#1\x'
PORT=8095
SITE_URL="http://127.0.0.1:$PORT"

WORK="$(mktemp -d)"
ROOT="$WORK/home"
# Not "admintool" on purpose: nothing in deploy.sh may depend on the app's name.
APP_DIR="$ROOT/apps/someapp"
BIN="$APP_DIR/bin"
SITE_CONF="$BIN/sites/dev.conf"
BACKUP_DIR="$APP_DIR/backups/dev"
SITE_DIR="$ROOT/domains/dev.test/deploy"
PUBLIC_HTML="$ROOT/domains/dev.test/public_html"
MAINT="$SITE_DIR/shared/maintenance.on"
SERVER_PID=""

admin_sql() {
    MYSQL_PWD="$TEST_DB_PASS" mysql -h"$TEST_DB_HOST" -P"$TEST_DB_PORT" -u"$TEST_DB_USER" -N -B "$@"
}
sql() { admin_sql "$DB" -e "$1"; }

cleanup() {
    [ -n "$SERVER_PID" ] && kill "$SERVER_PID" 2>/dev/null
    admin_sql -e "DROP DATABASE IF EXISTS $DB; DROP USER IF EXISTS '$DB_USER'@'%'" 2>/dev/null
    rm -rf "$WORK"
}
trap cleanup EXIT

pass=0
fail=0
check() { # <description> '<shell expression>'
    local description="$1"
    if eval "$2"; then
        pass=$((pass + 1)); echo "ok   - $description"
    else
        fail=$((fail + 1)); echo "FAIL - $description"
    fi
}

# --------------------------------------------------------------- the fake server
echo "Setting up a fake site in $ROOT"
mkdir -p "$SITE_DIR"/{releases,shared/uploads/logo,shared/uploads/karyawan,logs} \
    "$BIN/sites" "$BACKUP_DIR" "$PUBLIC_HTML"
# Installed the way the README says: deploy.sh finds the app's folder from
# where it is, so it is run from the fake bin/, not from the repository.
cp "$REPO/deploy/server/deploy.sh" "$BIN/"
chmod 700 "$BIN/deploy.sh"
echo '<?php echo "OLD SITE";' > "$PUBLIC_HTML/index.php"
printf 'domain=dev.test\nhealth_url=%s/index.php/Health\n' "$SITE_URL" > "$SITE_CONF"
cat > "$SITE_DIR/shared/.htaccess" <<EOF
# Like a real site's .htaccess: settings inside a block, a commented-out line.
<IfModule mod_env.c>
    SetEnv DB_HOST $TEST_DB_HOST
    SetEnv DB_PORT $TEST_DB_PORT
    SetEnv DB_USER $DB_USER
    # SetEnv DB_PASS not-this-one
    SetEnv DB_PASS "$DB_PASS"
    SetEnv DB_NAME $DB
    SetEnv CI_ENV testing
    SetEnv MAINTENANCE_FILE $MAINT
</IfModule>
EOF

sql_escaped_pass="$(printf '%s' "$DB_PASS" | sed -e 's/\\/\\\\/g' -e "s/'/\\\\'/g")"
admin_sql -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB;
    DROP USER IF EXISTS '$DB_USER'@'%';
    CREATE USER '$DB_USER'@'%' IDENTIFIED WITH mysql_native_password BY '$sql_escaped_pass';
    GRANT ALL ON $DB.* TO '$DB_USER'@'%';"

echo "Building the live database with the current migrations"
(cd "$REPO" && DB_HOST="$TEST_DB_HOST" DB_PORT="$TEST_DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" \
    DB_NAME="$DB" CI_ENV=testing "$PHP_BIN" index.php migrate)
BASE_VERSION="$(sql 'SELECT version FROM migrations')"

# The site as the web server would run it: settings from the environment
# (SetEnv), docroot = whatever public_html points to now.
DB_HOST="$TEST_DB_HOST" DB_PORT="$TEST_DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" DB_NAME="$DB" \
    CI_ENV=testing MAINTENANCE_FILE="$MAINT" DEPLOY_TEST_DOCROOT="$PUBLIC_HTML" \
    "$PHP_BIN" -d realpath_cache_size=0 -S "127.0.0.1:$PORT" -t "$WORK" "$REPO/tests/deploy/router.php" \
    > "$WORK/server.log" 2>&1 &
SERVER_PID=$!
for _ in $(seq 1 50); do
    curl -s -o /dev/null "$SITE_URL/" && break
    sleep 0.1
done

# --------------------------------------------------------------- releases
echo "Building release tarballs"
bash "$REPO/deploy/build-release.sh" HEAD "$WORK/base.tar.gz" > /dev/null

# variant <name> <migration-file>... : base release plus extra migrations
variant() {
    local name="$1" dir="$WORK/variant-$1"
    shift
    mkdir -p "$dir"
    tar -xzf "$WORK/base.tar.gz" -C "$dir"
    for m in "$@"; do cp "$WORK/$m" "$dir/application/migrations/$m"; done
    tar -czf "$WORK/$name.tar.gz" -C "$dir" .
}

cat > "$WORK/29991231000000_deploy_probe.php" <<'EOF'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_Deploy_probe extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE deploy_probe (id int PRIMARY KEY, note varchar(20))");
        $this->db->query("INSERT INTO deploy_probe VALUES (1, 'v2')");
    }
    public function down() {}
}
EOF
cat > "$WORK/29991231000001_deploy_broken.php" <<'EOF'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_Deploy_broken extends CI_Migration
{
    public function up()
    {
        // Changes data and adds a table, then fails: the rollback must undo both.
        $this->db->query("CREATE TABLE deploy_broken_tmp (id int)");
        $this->db->query("UPDATE deploy_probe SET note = 'broken'");
        $this->db->query("SELECT * FROM no_such_table");
    }
    public function down() {}
}
EOF
cat > "$WORK/29991231000002_first_broken.php" <<'EOF'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_First_broken extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE deploy_broken_tmp (id int)");
        $this->db->query("SELECT * FROM no_such_table");
    }
    public function down() {}
}
EOF
variant first-broken 29991231000002_first_broken.php
variant probe 29991231000000_deploy_probe.php
variant broken 29991231000000_deploy_probe.php 29991231000001_deploy_broken.php
# Same code as "probe", but its Health reports another commit than the one
# deployed, as if the web server still served another release.
variant wrong-revision 29991231000000_deploy_probe.php
sed -i "s/'revision' => \$this->app_revision(),/'revision' => str_repeat('0', 40),/" \
    "$WORK/variant-wrong-revision/application/controllers/Health.php"
grep -q "str_repeat('0', 40)" "$WORK/variant-wrong-revision/application/controllers/Health.php"
tar -czf "$WORK/wrong-revision.tar.gz" -C "$WORK/variant-wrong-revision" .

ALL_OUTPUT="$WORK/all-output.txt"
deploy() { # <release-id> <tarball> -> $rc, $out
    set +e
    out="$(DEPLOY_ROOT="$ROOT" DEPLOY_PHP="$PHP_BIN" DEPLOY_HEALTH_TRIES="${TRIES:-3}" DEPLOY_HEALTH_WAIT=1 \
        DEPLOY_SETTLE_SECONDS=0 "$BIN/deploy.sh" dev "$1" < "$2" 2>&1)"
    rc=$?
    set -e
    printf '%s\n' "$out" >> "$ALL_OUTPUT"
}
result_is() { printf '%s\n' "$out" | grep -q "^RESULT site=dev release=$1 .* result=$2\$"; }
live_release() { readlink "$SITE_DIR/current"; }
health() { curl -s "$SITE_URL/index.php/Health"; }
health_ok() { health | grep -q '"status":"ok"'; }
table_exists() { [ -n "$(sql "SHOW TABLES LIKE '$1'")" ]; }
backup_count() { find "$BACKUP_DIR" -name 'pre-deploy-*.sql.gz' | wc -l; }

# --------------------------------------------------------------- 1. first deploy fails
echo
echo "1. First deploy with a broken migration"
deploy first-broken "$WORK/first-broken.tar.gz"
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rolled-back" 'result_is first-broken rolled-back'
check "public_html is the original folder again" '[ -d "$PUBLIC_HTML" ] && [ ! -L "$PUBLIC_HTML" ]'
check "the old site still answers" '[ "$(curl -s "$SITE_URL/")" = "OLD SITE" ]'
check "no current link, no pre-deploy folder left" '[ ! -e "$SITE_DIR/current" ] && [ ! -e "$SITE_DIR/public_html.pre-deploy" ]'
check "the failed release folder is removed" '[ ! -e "$SITE_DIR/releases/first-broken" ]'
check "the table the migration added is gone" '! table_exists deploy_broken_tmp'
check "the migration version is unchanged" '[ "$(sql '\''SELECT version FROM migrations'\'')" = "$BASE_VERSION" ]'
check "maintenance is off" '[ ! -e "$MAINT" ]'

# --------------------------------------------------------------- 2. first deploy
echo
echo "2. First deploy"
deploy r1 "$WORK/base.tar.gz"
check "exits 0" '[ "$rc" -eq 0 ]'
check "reports success" 'result_is r1 success'
check "public_html is a link to current" '[ -L "$PUBLIC_HTML" ] && [ "$(readlink "$PUBLIC_HTML")" = "$SITE_DIR/current" ]'
check "current points to releases/r1" '[ "$(live_release)" = releases/r1 ]'
check "the old folder is kept as public_html.pre-deploy" '[ "$(cat "$SITE_DIR/public_html.pre-deploy/index.php")" = '\''<?php echo "OLD SITE";'\'' ]'
check "the release links the shared .htaccess and uploads (relative links)" '[ "$(readlink "$SITE_DIR/releases/r1/.htaccess")" = ../../shared/.htaccess ] && [ "$(readlink "$SITE_DIR/releases/r1/uploads")" = ../../shared/uploads ]'
check "...which resolve to the shared files" '[ "$(readlink -f "$SITE_DIR/releases/r1/.htaccess")" = "$(readlink -f "$SITE_DIR/shared/.htaccess")" ] && [ "$(readlink -f "$PUBLIC_HTML/uploads")" = "$(readlink -f "$SITE_DIR/shared/uploads")" ]'
check "the backup is in the app's backups folder" '[ -n "$(find "$BACKUP_DIR" -name '\''pre-deploy-*-r1.sql.gz'\'')" ]'
check "REVISION holds the commit" '[ "$(cat "$SITE_DIR/releases/r1/REVISION")" = "$(git -C "$REPO" rev-parse HEAD)" ]'
check "Health is ok with the release's version" 'health_ok'
check "Health shows the deployed commit as revision" 'health | grep -q "\"revision\":\"$(git -C "$REPO" rev-parse HEAD)\""'
check "the log shows the revision Health answered" 'grep -q "f. Health ok .*revision=.$(git -C "$REPO" rev-parse HEAD)" "$SITE_DIR/logs/deploy.log"'
check "PHP restart was requested after the switch" '[ -f "$ROOT/.lsphp_restart.txt" ] && grep -q "e. Asked LiteSpeed to restart PHP" "$SITE_DIR/logs/deploy.log"'
check "Health was checked while maintenance was on" 'grep -q '\''f. Health ok .*maintenance=true'\'' "$SITE_DIR/logs/deploy.log"'
check "the site answers normally (not 503)" '[ "$(curl -s -o /dev/null -w '\''%{http_code}'\'' "$SITE_URL/index.php/Login")" = 200 ]'
check "maintenance is off" '[ ! -e "$MAINT" ]'
backup="$(find "$BACKUP_DIR" -name 'pre-deploy-*-r1.sql.gz')"
check "a pre-deploy backup exists, mode 600" '[ -n "$backup" ] && [ "$(stat -c %a "$backup")" = 600 ]'
check "the backup is a complete dump" 'gzip -dc "$backup" | tail -n1 | grep -q "Dump completed"'
check "history.log has a success line" 'grep -q " site=dev release=r1 sha=$(git -C "$REPO" rev-parse HEAD) version=.* backup=pre-deploy-.*-r1.sql.gz result=success\$" "$SITE_DIR/logs/history.log"'

# --------------------------------------------------------------- 3. deploy with a migration
echo
echo "3. Deploy with a new migration"
deploy r2 "$WORK/probe.tar.gz"
check "exits 0" '[ "$rc" -eq 0 ]'
check "current points to releases/r2" '[ "$(live_release)" = releases/r2 ]'
check "the migration ran" '[ "$(sql '\''SELECT note FROM deploy_probe WHERE id = 1'\'')" = v2 ]'
check "Health is ok" 'health_ok'

# --------------------------------------------------------------- 4. broken migration
echo
echo "4. Deploy with a broken migration"
sql "UPDATE deploy_probe SET note = 'before' WHERE id = 1"
deploy r3 "$WORK/broken.tar.gz"
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rolled-back" 'result_is r3 rolled-back'
check "says the migration failed" 'grep -q '\''ERROR: Migrations failed'\'' <<< "$out"'
check "current points back to releases/r2" '[ "$(live_release)" = releases/r2 ]'
check "the failed release folder is removed" '[ ! -e "$SITE_DIR/releases/r3" ]'
check "the data the migration changed is restored" '[ "$(sql '\''SELECT note FROM deploy_probe WHERE id = 1'\'')" = before ]'
check "the table the migration added is gone" '! table_exists deploy_broken_tmp'
check "the migration version is back to r2's" '[ "$(sql '\''SELECT version FROM migrations'\'')" = 29991231000000 ]'
check "maintenance is off" '[ ! -e "$MAINT" ]'
check "Health is ok" 'health_ok'
check "history.log has a rolled-back line" 'grep -q '\'' release=r3 .* result=rolled-back$'\'' "$SITE_DIR/logs/history.log"'

# --------------------------------------------------------------- 5. failed health check
echo
echo "5. Deploy whose health check fails"
cp "$SITE_CONF" "$WORK/site.conf.good"
printf 'domain=dev.test\nhealth_url=http://127.0.0.1:9/index.php/Health\n' > "$SITE_CONF"
sql "UPDATE deploy_probe SET note = 'kept' WHERE id = 1"
TRIES=2 deploy r4 "$WORK/probe.tar.gz"
cp "$WORK/site.conf.good" "$SITE_CONF"
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rolled-back" 'result_is r4 rolled-back'
check "says the health check failed" 'grep -q '\''ERROR: Health check failed'\'' <<< "$out"'
check "current points back to releases/r2" '[ "$(live_release)" = releases/r2 ]'
check "the failed release folder is removed" '[ ! -e "$SITE_DIR/releases/r4" ]'
check "the data is as before" '[ "$(sql '\''SELECT note FROM deploy_probe WHERE id = 1'\'')" = kept ]'
check "maintenance is off" '[ ! -e "$MAINT" ]'

echo
echo "5b. Deploy whose Health reports another revision"
TRIES=2 deploy r4b "$WORK/wrong-revision.tar.gz"
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rolled-back" 'result_is r4b rolled-back'
check "says which revision it wanted" 'grep -q "want version .*, revision .$(git -C "$REPO" rev-parse HEAD)" <<< "$out"'
check "current points back to releases/r2" '[ "$(live_release)" = releases/r2 ]'
check "the failed release folder is removed" '[ ! -e "$SITE_DIR/releases/r4b" ]'
check "maintenance is off" '[ ! -e "$MAINT" ]'
check "Health is ok again" 'health_ok'

# --------------------------------------------------------------- 6. refusals
echo
echo "6. Refusals that change nothing"
backups_before="$(backup_count)"

deploy r2 "$WORK/probe.tar.gz"
check "re-deploying the live release succeeds without changes" '[ "$rc" -eq 0 ] && result_is r2 already-live && grep -q "already live; nothing changed" <<< "$out"'
check "...and reports its commit" 'grep -q "^RESULT site=dev release=r2 sha=$(git -C "$REPO" rev-parse HEAD) " <<< "$out"'
check "...and r2 is still live and complete" '[ "$(live_release)" = releases/r2 ] && [ -f "$SITE_DIR/releases/r2/index.php" ]'

deploy r1 "$WORK/base.tar.gz"
check "an existing release that isn't live is refused" '[ "$rc" -ne 0 ] && result_is r1 failed && grep -q "already exists" <<< "$out"'
check "...and r2 is still live" '[ "$(live_release)" = releases/r2 ]'

touch "$MAINT"
check "maintenance on by hand gives 503" '[ "$(curl -s -o /dev/null -w '\''%{http_code}'\'' "$SITE_URL/index.php/Login")" = 503 ]'
deploy r5 "$WORK/probe.tar.gz"
check "a deploy during maintenance is refused" '[ "$rc" -ne 0 ] && result_is r5 failed && grep -q '\''Maintenance is already on'\'' <<< "$out"'
check "...and maintenance is left on" '[ -e "$MAINT" ]'
rm -f "$MAINT"

flock "$SITE_DIR/.deploy.lock" sleep 5 &
lock_pid=$!
sleep 0.5
deploy r5 "$WORK/probe.tar.gz"
check "a second deploy at the same time is refused" '[ "$rc" -ne 0 ] && grep -q '\''Another deploy of dev is running'\'' <<< "$out"'
wait "$lock_pid"

# A MAINTENANCE_FILE outside shared/, e.g. still pointing at an old layout.
cp "$SITE_DIR/shared/.htaccess" "$WORK/htaccess.good"
mkdir -p "$WORK/old-layout"
sed -i "s|SetEnv MAINTENANCE_FILE .*|SetEnv MAINTENANCE_FILE $WORK/old-layout/maintenance.on|" "$SITE_DIR/shared/.htaccess"
deploy r5 "$WORK/probe.tar.gz"
cp "$WORK/htaccess.good" "$SITE_DIR/shared/.htaccess"
check "a MAINTENANCE_FILE outside shared/ is refused" '[ "$rc" -ne 0 ] && result_is r5 failed && grep -q "MAINTENANCE_FILE must be in" <<< "$out" && [ ! -e "$WORK/old-layout/maintenance.on" ]'

cp "$SITE_CONF" "$WORK/site.conf.good"
printf 'domain=../dev.test\n' > "$SITE_CONF"
deploy r5 "$WORK/probe.tar.gz"
check "a domain with .. in the site config is refused" '[ "$rc" -ne 0 ] && result_is r5 failed && grep -q "has no valid domain=" <<< "$out"'
rm "$SITE_CONF"
deploy r5 "$WORK/probe.tar.gz"
check "a site without a site config is refused" '[ "$rc" -ne 0 ] && result_is r5 failed && grep -q "Missing .*sites/dev.conf" <<< "$out"'
cp "$WORK/site.conf.good" "$SITE_CONF"
check "...and r2 is still live" '[ "$(live_release)" = releases/r2 ] && [ ! -e "$SITE_DIR/releases/r5" ]'

# The live release still linked to the shared files of the folder it was
# copied from (an old layout): a rollback would land on those.
mkdir -p "$WORK/old-shared/uploads" && cp "$SITE_DIR/shared/.htaccess" "$WORK/old-shared/.htaccess"
for f in .htaccess uploads; do
    ln -sfn "$WORK/old-shared/$f" "$SITE_DIR/releases/r2/$f.new" && mv -T "$SITE_DIR/releases/r2/$f.new" "$SITE_DIR/releases/r2/$f"
    deploy r5 "$WORK/probe.tar.gz"
    check "a live release whose $f leads elsewhere is refused" '[ "$rc" -ne 0 ] && result_is r5 failed && grep -q "live release.s $f doesn.t lead to" <<< "$out"'
    check "...says nothing was changed" 'grep -q "Nothing was changed" <<< "$out" && ! grep -q "Rolled back:" <<< "$out"'
    check "...and leaves maintenance off" '[ ! -e "$MAINT" ]'
    ln -sfn "../../shared/$f" "$SITE_DIR/releases/r2/$f.new" && mv -T "$SITE_DIR/releases/r2/$f.new" "$SITE_DIR/releases/r2/$f"
done
check "...and r2 is still live" '[ "$(live_release)" = releases/r2 ] && [ ! -e "$SITE_DIR/releases/r5" ] && health_ok'

printf 'not a tarball' > "$WORK/junk.tar.gz"
deploy r5 "$WORK/junk.tar.gz"
check "a broken tarball is refused" '[ "$rc" -ne 0 ] && result_is r5 failed'
mkdir -p "$WORK/no-revision" && tar -xzf "$WORK/base.tar.gz" -C "$WORK/no-revision" && rm "$WORK/no-revision/REVISION"
tar -czf "$WORK/no-revision.tar.gz" -C "$WORK/no-revision" .
deploy r5 "$WORK/no-revision.tar.gz"
check "a release without REVISION is refused" '[ "$rc" -ne 0 ] && result_is r5 failed && grep -q "The release has no REVISION" <<< "$out"'
check "...and nothing was left behind" '[ ! -e "$SITE_DIR/releases/r5" ] && [ -z "$(find "$SITE_DIR/releases" -maxdepth 1 -name '\''.incoming-*'\'')" ]'
# A backup that fails halfway: the partial file must not stay behind.
mkdir -p "$WORK/fake-bin"
printf '#!/bin/sh\necho "-- partial dump"\nexit 2\n' > "$WORK/fake-bin/mysqldump"
chmod +x "$WORK/fake-bin/mysqldump"
PATH="$WORK/fake-bin:$PATH" deploy r5 "$WORK/probe.tar.gz"
check "a failed backup rolls back" '[ "$rc" -ne 0 ] && result_is r5 rolled-back'
check "...without a backup name in the result" 'grep -q "^RESULT .* backup= result=rolled-back$" <<< "$out"'
check "...leaves no partial backup" '[ -z "$(find "$BACKUP_DIR" -name "*.partial")" ]'
check "...and turns maintenance off" '[ ! -e "$MAINT" ]'
check "...and the database is untouched" '[ "$(sql "SELECT note FROM deploy_probe WHERE id = 1")" = kept ]'

check "no backups were made by refused deploys" '[ "$(backup_count)" = "$backups_before" ]'
check "r2 is still live" '[ "$(live_release)" = releases/r2 ] && health_ok'

# --------------------------------------------------------------- 7. cleanup
echo
echo "7. Old releases and backups are cleaned up"
for id in r5 r6 r7; do deploy "$id" "$WORK/probe.tar.gz"; done
check "the last deploys succeeded" '[ "$rc" -eq 0 ] && [ "$(live_release)" = releases/r7 ]'
check "the newest 3 releases are kept" '[ "$(cd "$SITE_DIR/releases" && ls | sort | tr '\''\n'\'' '\'' '\'')" = "r5 r6 r7 " ]'
check "the newest 5 backups are kept" '[ "$(backup_count)" = 5 ]'
check "the newest backup is r7's" '[ "$(cd "$BACKUP_DIR" && ls | sort | tail -n1)" = "$(cd "$BACKUP_DIR" && ls *-r7.sql.gz)" ]'

# --------------------------------------------------------------- 8. secrets
echo
echo "8. No secrets left or logged"
check "the password is in no log or output" '! grep -rqF "$DB_PASS" "$SITE_DIR/logs" "$ALL_OUTPUT"'
check "no temporary option, health or incoming files left" '[ -z "$(find "$SITE_DIR" -maxdepth 2 -name '\''.db-options.*'\'' -o -maxdepth 2 -name '\''.health.*'\'' -o -maxdepth 2 -name '\''.incoming-*'\'')" ]'
check "every deploy wrote one history line" '[ "$(wc -l < "$SITE_DIR/logs/history.log")" -ge 15 ]'

echo
echo "$pass passed, $fail failed"
if [ "$fail" -ne 0 ]; then
    echo
    echo "--- deploy.log"
    cat "$SITE_DIR/logs/deploy.log"
    echo "--- server.log (last 30 lines)"
    tail -n 30 "$WORK/server.log"
    exit 1
fi
