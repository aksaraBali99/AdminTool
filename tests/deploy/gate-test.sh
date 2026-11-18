#!/usr/bin/env bash
# Tests for deploy/server/gate.sh, the forced command of the GitHub deploy key:
# which SSH commands it accepts and passes on to deploy.sh, and which it
# refuses and logs. Plain bash, no server, no database:
#
#   bash tests/deploy/gate-test.sh
#
# gate.sh is copied into a temporary bin folder next to a stub deploy.sh
# that records its arguments and stdin.
set -uo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

BIN="$WORK/bin"
mkdir -p "$BIN"
cp "$REPO/deploy/server/gate.sh" "$BIN/gate.sh"
cat > "$BIN/deploy.sh" <<'EOF'
#!/bin/bash
printf '%s\n' "$@" > "$(dirname "$0")/called-with"
cat > "$(dirname "$0")/stdin-received"
env > "$(dirname "$0")/env-received"
EOF
chmod +x "$BIN/gate.sh" "$BIN/deploy.sh"
printf '# sites the GitHub key may deploy\ndev\n  staging  \n' > "$BIN/sites.allow"

pass=0
fail=0

run_gate() { # <ssh command or __unset__> -> sets $status
    rm -f "$BIN/called-with" "$BIN/stdin-received" "$BIN/env-received"
    if [ "$1" = __unset__ ]; then
        (unset SSH_ORIGINAL_COMMAND; printf 'TARBALL' | "$BIN/gate.sh") > /dev/null 2>&1
    else
        printf 'TARBALL' | SSH_ORIGINAL_COMMAND="$1" SSH_CLIENT="203.0.113.9 50000 65002" \
            DEPLOY_ROOT=/should/not/pass "$BIN/gate.sh" > /dev/null 2>&1
    fi
    status=$?
}

ok() { pass=$((pass + 1)); echo "ok   - $1"; }
not_ok() { fail=$((fail + 1)); echo "FAIL - $1"; }

expect_accepted() { # <command> <site> <release>
    run_gate "$1"
    if [ "$status" -eq 0 ] && [ -f "$BIN/called-with" ] \
        && [ "$(cat "$BIN/called-with")" = "$(printf '%s\n%s' "$2" "$3")" ] \
        && [ "$(cat "$BIN/stdin-received")" = TARBALL ]; then
        ok "accepts: $1"
    else
        not_ok "accepts: $1 (exit $status)"
    fi
}

expect_refused() { # <command> <description>
    local lines_before lines_after
    lines_before="$({ wc -l < "$BIN/gate.log"; } 2>/dev/null || echo 0)"
    run_gate "$1"
    lines_after="$({ wc -l < "$BIN/gate.log"; } 2>/dev/null || echo 0)"
    if [ "$status" -eq 1 ] && [ ! -f "$BIN/called-with" ] && [ "$lines_after" -gt "$lines_before" ]; then
        ok "refuses $2"
    else
        not_ok "refuses $2 (exit $status, deploy.sh called: $([ -f "$BIN/called-with" ] && echo yes || echo no))"
    fi
}

# Accepted
expect_accepted "deploy dev abc1234" dev abc1234
expect_accepted "deploy dev 0123456789ab" dev 0123456789ab
expect_accepted "deploy dev v1.2.3_rc-1" dev v1.2.3_rc-1
expect_accepted "deploy dev $(printf 'a%.0s' {1..64})" dev "$(printf 'a%.0s' {1..64})"
expect_accepted "deploy staging abc1234" staging abc1234

# Refused
expect_refused __unset__ "no command (interactive login)"
expect_refused "" "an empty command"
expect_refused "bash" "a shell"
expect_refused "deploy dev" "a missing release id"
expect_refused "deploy" "a missing site"
expect_refused "deploy prod abc1234" "a site not in sites.allow"
expect_refused "deploy Dev abc1234" "a site in the wrong case"
expect_refused "deploy de abc1234" "a partial site name"
expect_refused "deploy # abc1234" "a comment as site"
expect_refused "deploy dev abc1234 extra" "an extra argument"
expect_refused "deploy  dev abc1234" "a double space"
expect_refused " deploy dev abc1234" "a leading space"
expect_refused "deploy dev abc1234 " "a trailing space"
expect_refused "$(printf 'deploy\tdev\tabc1234')" "tabs"
expect_refused "$(printf 'deploy dev abc1234\nrm -rf ~')" "a second line"
expect_refused "deploy dev abc1234; rm -rf ~" "a ; command"
expect_refused 'deploy dev $(id)' "command substitution"
expect_refused "deploy dev ../../etc" "a path in the release id"
expect_refused "deploy dev .." "'..' as release id"
expect_refused "deploy dev ." "'.' as release id"
expect_refused "deploy dev .hidden" "a release id starting with a dot"
expect_refused "deploy dev -rf" "a release id starting with a dash"
expect_refused "deploy dev a/b" "a slash in the release id"
expect_refused "deploy dev $(printf 'a%.0s' {1..65})" "a 65-character release id"
expect_refused "deploy ../dev abc1234" "a path as site"
expect_refused "rollback dev abc1234" "another verb"
expect_refused "scp -t /home" "scp"
expect_refused "rsync --server ." "rsync"

# deploy.sh gets a clean environment: nothing the client sent passes through.
run_gate "deploy dev abc1234"
if grep -q '^DEPLOY_ROOT=' "$BIN/env-received" || grep -q '^SSH_ORIGINAL_COMMAND=' "$BIN/env-received"; then
    not_ok "deploy.sh gets a clean environment"
else
    ok "deploy.sh gets a clean environment"
fi

# The log keeps refused commands printable and short.
if grep -q 'refused (bad command) from 203.0.113.9: deploy dev abc1234?rm -rf ~' "$BIN/gate.log" \
    && [ "$(awk '{ if (length > m) m = length } END { print m }' "$BIN/gate.log")" -le 300 ]; then
    ok "gate.log records refusals on one printable line"
else
    not_ok "gate.log records refusals on one printable line"
fi

# Missing allow-list: everything is refused.
mv "$BIN/sites.allow" "$BIN/sites.allow.off"
expect_refused "deploy dev abc1234" "everything when sites.allow is missing"
mv "$BIN/sites.allow.off" "$BIN/sites.allow"

echo
echo "$pass passed, $fail failed"
[ "$fail" -eq 0 ]
