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
        show_last_deploy
    fi
}

# On the first failed check after a deploy: what that deploy printed and what
# the fake web server logged meanwhile, so a failure explains itself in CI.
# (Never holds secrets: section 8 checks the password is in no output.)
LAST_DEPLOY="" SHOWN=1 SERVER_LOG_FROM=1
show_last_deploy() {
    [ "$SHOWN" = 0 ] || return 0
    SHOWN=1
    echo "    --- output of deploy $LAST_DEPLOY (exit $rc)"
    printf '%s\n' "$out" | sed 's/^/    | /'
    echo "    --- web server log during that deploy (last 40 lines)"
    tail -n +"$SERVER_LOG_FROM" "$WORK/server.log" 2>/dev/null | tail -n 40 | sed 's/^/    | /'
    echo "    ---"
}

# Setup steps between sections: a failure is counted and shown, but doesn't
# stop the script (set -e), so the later sections still run and report.
prep() { # <description> <command>...
    local description="$1"
    shift
    if ! "$@"; then
        fail=$((fail + 1)); echo "FAIL - setup: $description"
        show_last_deploy
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
# site_conf [health_url] [ci_env] : writes the site config (defaults: the test
# site's Health, ci_env=testing).
site_conf() {
    printf 'domain=dev.test\nhealth_url=%s\nci_env=%s\n' "${1:-$SITE_URL/index.php/Health}" "${2:-testing}" > "$SITE_CONF"
}
site_conf
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
# A WhatsApp token, as a DEV database copied from PROD would have one. Deploys
# to a site whose ci_env isn't production must overwrite it.
WA_TOKEN='wa-token-from-prod-must-not-survive-7x9q'
sql "INSERT INTO data_konfig (id_konfig, nama_apk, token_wa, logo) VALUES (0, 'Deploy Test', '$WA_TOKEN', '')"
token_wa() { sql 'SELECT token_wa FROM data_konfig WHERE id_konfig = 0'; }
FORCE_CI_ENV="$WORK/force-ci-env"

# The site as the web server would run it: settings from the environment
# (SetEnv), docroot = whatever public_html points to now.
# No caches that keep serving the old files for a moment after a link
# changes: realpath_cache_size=0, and opcache off. opcache.enable_cli=0 is not
# enough: it only covers plain CLI, and php -S (SAPI cli-server) follows
# opcache.enable. With opcache on, the router's "require public_html/index.php"
# got the previous site's compiled file for up to opcache.revalidate_freq
# (2 s), so the deploy's 3 quick Health tries saw "OLD SITE" and rolled back.
# Real deploys handle the same effect with a PHP restart, about 3 minutes of
# tries, and by checking Health reports the new release's revision.
DB_HOST="$TEST_DB_HOST" DB_PORT="$TEST_DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" DB_NAME="$DB" \
    CI_ENV=testing MAINTENANCE_FILE="$MAINT" DEPLOY_TEST_DOCROOT="$PUBLIC_HTML" \
    DEPLOY_TEST_FORCE_CI_ENV_FILE="$FORCE_CI_ENV" \
    "$PHP_BIN" -d realpath_cache_size=0 -d opcache.enable=0 \
    -S "127.0.0.1:$PORT" -t "$WORK" "$REPO/tests/deploy/router.php" \
    > "$WORK/server.log" 2>&1 &
SERVER_PID=$!
for _ in $(seq 1 50); do
    curl -s -o /dev/null "$SITE_URL/" && break
    sleep 0.1
done
echo "Test site: $(grep -m1 -o 'sapi=[^]]*' "$WORK/server.log" || echo "no router line")"
check "the test site runs without opcache (else it may serve the previous release)" 'grep -m1 "router: " "$WORK/server.log" | grep -q "active=no"'

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

# A broken migration that does NOT check its query results (like most real
# migrations), followed by a valid one that must then never run. With
# CI_ENV=production, config/database.php turns db_debug off; migrate must
# still stop at the failed query.
cat > "$WORK/29991231000003_prod_broken.php" <<'EOF'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_Prod_broken extends CI_Migration
{
    public function up()
    {
        // Two new tables: the rollback must drop both, not just the first.
        $this->db->query("CREATE TABLE deploy_prod_tmp (id int)");
        $this->db->query("CREATE TABLE deploy_prod_tmp2 (id int)");
        $this->db->query("SELECT * FROM no_such_table");
    }
    public function down() {}
}
EOF
cat > "$WORK/29991231000004_after_broken.php" <<'EOF'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_After_broken extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE deploy_after_tmp (id int)");
    }
    public function down() {}
}
EOF
variant prod-migrate 29991231000003_prod_broken.php 29991231000004_after_broken.php
variant prod-broken 29991231000000_deploy_probe.php 29991231000003_prod_broken.php 29991231000004_after_broken.php

# A migration that works but renames the column the deploy then overwrites:
# the token step fails after migrations, and the deploy must roll back.
cat > "$WORK/29991231000005_rename_token.php" <<'EOF'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_Rename_token extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE data_konfig CHANGE token_wa token_wa_renamed text NOT NULL");
    }
    public function down() {}
}
EOF
variant token-renamed 29991231000000_deploy_probe.php 29991231000005_rename_token.php

ALL_OUTPUT="$WORK/all-output.txt"
deploy() { # <release-id> <tarball> -> $rc, $out
    LAST_DEPLOY="$1" SHOWN=0
    SERVER_LOG_FROM="$(( $(wc -l < "$WORK/server.log") + 1 ))"
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

# --------------------------------------------------------------- 0. migrate in production
echo
echo "0. Migrate stops at a database error in production too (db_debug off)"
check "the production config has db_debug off (so this test means something)" \
    '[ "$(cd "$REPO" && "$PHP_BIN" -r '\''define("BASEPATH", 1); define("ENVIRONMENT", "production"); include "application/config/database.php"; var_export($db["default"]["db_debug"]);'\'')" = false ]'
set +e
prod_out="$(cd "$WORK/variant-prod-migrate" && DB_HOST="$TEST_DB_HOST" DB_PORT="$TEST_DB_PORT" DB_USER="$DB_USER" \
    DB_PASS="$DB_PASS" DB_NAME="$DB" CI_ENV=production "$PHP_BIN" index.php migrate 2>&1)"
prod_rc=$?
set -e
check "migrate exits non-zero (got $prod_rc)" '[ "$prod_rc" -ne 0 ]'
check "...because of the failed query" 'grep -q "no_such_table" <<< "$prod_out"'
check "the migration version is not bumped" '[ "$(sql '\''SELECT version FROM migrations'\'')" = "$BASE_VERSION" ]'
check "the migration after the broken one did not run" '! table_exists deploy_after_tmp'
check "(the broken one ran up to its bad query)" 'table_exists deploy_prod_tmp && table_exists deploy_prod_tmp2'
prep "drop the tables the broken migration left" sql "DROP TABLE IF EXISTS deploy_prod_tmp, deploy_prod_tmp2"

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
check "Health was checked for outbound 'blocked' (ci_env=testing)" 'grep -q "f. Health ok .*outbound='\''blocked'\''" "$SITE_DIR/logs/deploy.log"'
check "the WhatsApp token is overwritten (ci_env=testing)" '[ "$(token_wa)" = DISABLED-NON-PROD ]'
check "...and the log says so" 'grep -q "\[dev r1\] d. WhatsApp token in data_konfig set to DISABLED-NON-PROD (ci_env=testing; 1 row(s) changed)" "$SITE_DIR/logs/deploy.log"'

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
prep "set deploy_probe.note to 'before'" sql "UPDATE deploy_probe SET note = 'before' WHERE id = 1"
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
check "the database name and user in the migration error are masked in the log" 'grep -q "<db>\.no_such_table" "$SITE_DIR/logs/deploy.log"'

# --------------------------------------------------------------- 4b. broken migration, production
echo
echo "4b. Deploy with a broken migration and CI_ENV=production (db_debug off)"
cp "$SITE_DIR/shared/.htaccess" "$WORK/htaccess.testing"
sed -i 's/SetEnv CI_ENV testing/SetEnv CI_ENV production/' "$SITE_DIR/shared/.htaccess"
prep "the shared .htaccess says CI_ENV production" grep -q "SetEnv CI_ENV production" "$SITE_DIR/shared/.htaccess"
site_conf "" production
deploy r3p "$WORK/prod-broken.tar.gz"
cp "$WORK/htaccess.testing" "$SITE_DIR/shared/.htaccess"
site_conf
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rolled-back" 'result_is r3p rolled-back'
check "says the migration failed" 'grep -q '\''ERROR: Migrations failed'\'' <<< "$out"'
check "drops both tables the broken migration added" 'grep -q "Dropping table deploy_prod_tmp (not in the backup)" <<< "$out" && grep -q "Dropping table deploy_prod_tmp2 (not in the backup)" <<< "$out" && ! table_exists deploy_prod_tmp && ! table_exists deploy_prod_tmp2'
check "the migration after the broken one did not run" '! table_exists deploy_after_tmp'
check "the migration version is back to r2'\''s" '[ "$(sql '\''SELECT version FROM migrations'\'')" = 29991231000000 ]'
check "current points back to releases/r2" '[ "$(live_release)" = releases/r2 ]'
check "maintenance is off" '[ ! -e "$MAINT" ]'
check "Health is ok" 'health_ok'

# --------------------------------------------------------------- 4c. restore can't drop
echo
echo "4c. Rollback that can't drop the new tables: rollback-incomplete, maintenance left on"
# Without DROP, the restore can't remove what the failed migration added. It
# must say so and keep maintenance on, never claim "Database restored".
prep "take DROP away from the site's database user" admin_sql -e "REVOKE DROP ON $DB.* FROM '$DB_USER'@'%'"
deploy r3q "$WORK/prod-broken.tar.gz"
prep "give DROP back" admin_sql -e "GRANT DROP ON $DB.* TO '$DB_USER'@'%'"
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rollback-incomplete" 'result_is r3q rollback-incomplete'
check "says it could not drop the table" 'grep -q "Could not drop table deploy_prod_tmp" <<< "$out"'
check "...with the database error, masked" 'grep -q "mysql: .*<db-user>" <<< "$out"'
check "never claims the database was restored" '! grep -q "Database restored" <<< "$out"'
check "leaves maintenance on" '[ -e "$MAINT" ] && grep -q "maintenance is left ON" <<< "$out"'
check "the site shows the maintenance page (503)" '[ "$(curl -s -o /dev/null -w '\''%{http_code}'\'' "$SITE_URL/index.php/Login")" = 503 ]'
check "current still points to releases/r2" '[ "$(live_release)" = releases/r2 ]'
# Fixed by hand, as in the README ("When a rollback is incomplete"): here
# only the added tables need to go; the migration changed no data.
prep "drop the added tables by hand" sql "DROP TABLE IF EXISTS deploy_prod_tmp, deploy_prod_tmp2"
rm -f "$MAINT"
check "after the fix by hand: version is r2's, maintenance off, Health ok" \
    '[ "$(sql '\''SELECT version FROM migrations'\'')" = 29991231000000 ] && [ ! -e "$MAINT" ] && health_ok'

# --------------------------------------------------------------- 4d. token step fails
echo
echo "4d. Deploy whose WhatsApp-token step fails (ci_env=testing)"
prep "put a WhatsApp token back" sql "UPDATE data_konfig SET token_wa = '$WA_TOKEN' WHERE id_konfig = 0"
deploy r3t "$WORK/token-renamed.tar.gz"
check "exits non-zero" '[ "$rc" -ne 0 ]'
check "reports rolled-back" 'result_is r3t rolled-back'
check "fails at the token step, after migrations" 'grep -q "d. Migrations done" <<< "$out" && grep -q "ERROR: Could not disable the WhatsApp token in data_konfig" <<< "$out" && grep -q "FAILED during step '\''disable-messaging'\''" <<< "$out"'
check "...logging the database error, masked" 'grep -q "mysql: .*token_wa" <<< "$out" && ! grep -qF "$DB" <<< "$out"'
check "the restore undoes the migration: the column is back, with the token as before" '[ "$(token_wa)" = "$WA_TOKEN" ] && [ -z "$(sql "SHOW COLUMNS FROM data_konfig LIKE '\''token_wa_renamed'\''")" ]'
check "the migration version is back to r2'\''s" '[ "$(sql '\''SELECT version FROM migrations'\'')" = 29991231000000 ]'
check "current points back to releases/r2, maintenance off, Health ok" '[ "$(live_release)" = releases/r2 ] && [ ! -e "$MAINT" ] && [ ! -e "$SITE_DIR/releases/r3t" ] && health_ok'

# --------------------------------------------------------------- 5. failed health check
echo
echo "5. Deploy whose health check fails"
cp "$SITE_CONF" "$WORK/site.conf.good"
site_conf http://127.0.0.1:9/index.php/Health
prep "set deploy_probe.note to 'kept'" sql "UPDATE deploy_probe SET note = 'kept' WHERE id = 1"
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

# ci_env in the site config: required, exactly one of three values, and the
# same as CI_ENV in shared/.htaccess. Refused before anything changes.
token_before="$(token_wa)"
refused_unchanged() { [ "$rc" -ne 0 ] && result_is r5 failed && [ ! -e "$SITE_DIR/releases/r5" ] && [ ! -e "$MAINT" ] && [ "$(live_release)" = releases/r2 ]; }
printf 'domain=dev.test\nhealth_url=%s/index.php/Health\n' "$SITE_URL" > "$SITE_CONF"
deploy r5 "$WORK/probe.tar.gz"
check "a site config without ci_env is refused, nothing changed" 'refused_unchanged && grep -q "has no ci_env=; add ci_env=production, ci_env=testing or ci_env=development" <<< "$out"'
site_conf "" staging
deploy r5 "$WORK/probe.tar.gz"
check "ci_env=staging is refused" 'refused_unchanged && grep -q "has ci_env=staging; it must be exactly production, testing or development" <<< "$out"'
site_conf "" Production
deploy r5 "$WORK/probe.tar.gz"
check "ci_env=Production (wrong case) is refused" 'refused_unchanged && grep -q "has ci_env=Production; it must be exactly" <<< "$out"'
site_conf "" production
deploy r5 "$WORK/probe.tar.gz"
check "ci_env=production while .htaccess says testing is refused" 'refused_unchanged && grep -q "CI_ENV in shared/.htaccess is '\''testing'\'' but .*sites/dev.conf says ci_env=production; make them the same" <<< "$out"'
site_conf
cp "$SITE_DIR/shared/.htaccess" "$WORK/htaccess.good"
sed -i 's/SetEnv CI_ENV testing/SetEnv CI_ENV staging-secret-ish/' "$SITE_DIR/shared/.htaccess"
deploy r5 "$WORK/probe.tar.gz"
cp "$WORK/htaccess.good" "$SITE_DIR/shared/.htaccess"
check "an unknown CI_ENV in .htaccess is refused" 'refused_unchanged && grep -q "CI_ENV in shared/.htaccess is another value but" <<< "$out"'
check "...without logging that value" '! grep -q "staging-secret-ish" <<< "$out" && ! grep -q "staging-secret-ish" "$SITE_DIR/logs/deploy.log"'
check "the refused deploys left the database alone" '[ "$(token_wa)" = "$token_before" ]'

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

# --------------------------------------------------------------- 7b. production site
echo
echo "7b. A production site: the token stays, Health must say outbound 'live'"
prep "put a WhatsApp token back" sql "UPDATE data_konfig SET token_wa = '$WA_TOKEN' WHERE id_konfig = 0"
cp "$SITE_DIR/shared/.htaccess" "$WORK/htaccess.testing"
sed -i 's/SetEnv CI_ENV testing/SetEnv CI_ENV production/' "$SITE_DIR/shared/.htaccess"
site_conf "" production
deploy r8 "$WORK/probe.tar.gz"
check "deploys" '[ "$rc" -eq 0 ] && result_is r8 success && [ "$(live_release)" = releases/r8 ]'
check "Health was checked for outbound 'live'" 'grep -q "\[dev r8\] f. Health ok .*outbound='\''live'\''" "$SITE_DIR/logs/deploy.log"'
check "the WhatsApp token is left alone" '[ "$(token_wa)" = "$WA_TOKEN" ] && ! grep -q "\[dev r8\] d. WhatsApp token" "$SITE_DIR/logs/deploy.log"'

# The web server ignores the site's SetEnv CI_ENV, so the app runs as
# 'testing' and blocks sending: on a production site that must fail Health.
echo testing > "$FORCE_CI_ENV"
TRIES=2 deploy r9 "$WORK/probe.tar.gz"
rm -f "$FORCE_CI_ENV"
check "a production site whose Health says outbound 'blocked' rolls back" '[ "$rc" -ne 0 ] && result_is r9 rolled-back && grep -q "ERROR: Health check failed" <<< "$out"'
check "...saying which outbound it wanted" 'grep -q "outbound='\''blocked'\''; want version .*, outbound '\''live'\''" <<< "$out"'
check "...with r8 live again and maintenance off" '[ "$(live_release)" = releases/r8 ] && [ ! -e "$MAINT" ] && [ ! -e "$SITE_DIR/releases/r9" ]'
check "...and the token as before" '[ "$(token_wa)" = "$WA_TOKEN" ]'

# And the other way round: a testing site that comes up as production.
cp "$WORK/htaccess.testing" "$SITE_DIR/shared/.htaccess"
site_conf
echo production > "$FORCE_CI_ENV"
TRIES=2 deploy r10 "$WORK/probe.tar.gz"
rm -f "$FORCE_CI_ENV"
check "a testing site whose Health says outbound 'live' rolls back" '[ "$rc" -ne 0 ] && result_is r10 rolled-back && grep -q "outbound='\''live'\''; want version .*, outbound '\''blocked'\''" <<< "$out"'
check "...and the restore puts the token back as it was before that deploy" '[ "$(token_wa)" = "$WA_TOKEN" ] && grep -q "\[dev r10\] d. WhatsApp token in data_konfig set to DISABLED-NON-PROD" "$SITE_DIR/logs/deploy.log"'
check "...with r8 live and Health ok" '[ "$(live_release)" = releases/r8 ] && health_ok'

# --------------------------------------------------------------- 8. secrets
echo
echo "8. No secrets left or logged"
check "the password is in no log or output, the web server's log included" '! grep -rqF "$DB_PASS" "$SITE_DIR/logs" "$ALL_OUTPUT" "$WORK/server.log"'
check "the WhatsApp token is in no log or output" '! grep -rqF "$WA_TOKEN" "$SITE_DIR/logs" "$ALL_OUTPUT" "$WORK/server.log"'
check "no temporary option, health, error-output, restore or incoming files left" '[ -z "$(find "$SITE_DIR" -maxdepth 2 \( -name '\''.db-options.*'\'' -o -name '\''.health.*'\'' -o -name '\''.stderr.*'\'' -o -name '\''.restore.*'\'' -o -name '\''.incoming-*'\'' \))" ]'
check "the database name and user are in no log or output (masked)" '! grep -rqF -e "$DB" -e "$DB_USER" "$SITE_DIR/logs" "$ALL_OUTPUT"'
# The server has no /dev/fd, so process substitution fails there (it made the
# restore skip dropping new tables, found in DEV drill C2). CI has /dev/fd,
# so only this check can catch it.
check "the server scripts use no process substitution (no /dev/fd on the server)" \
    '! grep -nE "<\(|>\(" "$REPO/deploy/server/deploy.sh" "$REPO/deploy/server/gate.sh"'
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
