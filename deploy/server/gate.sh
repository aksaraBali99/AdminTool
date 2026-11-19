#!/bin/bash
# The only command an app's GitHub deploy key may run. It is installed as
#   ~/apps/<app>/bin/gate.sh
# and forced for that app's key in ~/.ssh/authorized_keys, e.g. for AdminTool:
#   command="/home/<user>/apps/admintool/bin/gate.sh",restrict ssh-ed25519 AAAA... admintool-github-actions-deploy-dev
# Each app has its own bin/ folder and its own key, so one app's key can
# never deploy another app.
#
# sshd puts what the client asked to run in $SSH_ORIGINAL_COMMAND. The only
# accepted forms are exactly
#   deploy <site> <release-id>
#   rollback <site> <release-id|previous> <code|code-db>
# where <site> is a line of sites.allow (next to this script) and
# <release-id> matches ^[0-9A-Za-z._-]{1,64}$ (and isn't "." or ".." or
# starting with "." or "-"). For deploy, the release tarball arrives on
# stdin and is passed straight to deploy.sh; rollback.sh reads nothing from
# stdin. Anything else is logged to gate.log (next to this script) and
# refused with exit 1.
#
# Everything is found next to this script, never from the environment, and
# deploy.sh/rollback.sh run with a clean environment, so the SSH client can't
# steer them. See deploy/server/README.md.
set -uo pipefail

BIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
ALLOW_FILE="$BIN_DIR/sites.allow"
DEPLOY="$BIN_DIR/deploy.sh"
ROLLBACK="$BIN_DIR/rollback.sh"
GATE_LOG="$BIN_DIR/gate.log"

refuse() {
    # Log a printable, shortened copy of what was asked: never trust it.
    local asked
    local asked from="${SSH_CLIENT-unknown}"
    asked="$(printf '%s' "${SSH_ORIGINAL_COMMAND-<none>}" | tr -c '[:print:]' '?' | cut -c1-200)"
    printf '%s refused (%s) from %s: %s\n' \
        "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$1" "${from%% *}" "$asked" >> "$GATE_LOG" 2>/dev/null
    echo "gate: refused ($1)" >&2
    exit 1
}

cmd="${SSH_ORIGINAL_COMMAND-}"
[ -n "$cmd" ] || refuse "no command"

# Fields separated by single spaces; no tabs, newlines or other whitespace
# anywhere.
deploy_re='^deploy ([a-z0-9][a-z0-9-]{0,31}) ([0-9A-Za-z._-]{1,64})$'
rollback_re='^rollback ([a-z0-9][a-z0-9-]{0,31}) ([0-9A-Za-z._-]{1,64}) (code|code-db)$'
if [[ "$cmd" =~ $deploy_re ]]; then
    action=deploy
    site="${BASH_REMATCH[1]}"
    release="${BASH_REMATCH[2]}"
    type=""
elif [[ "$cmd" =~ $rollback_re ]]; then
    action=rollback
    site="${BASH_REMATCH[1]}"
    release="${BASH_REMATCH[2]}"
    type="${BASH_REMATCH[3]}"
else
    refuse "bad command"
fi

case "$release" in
    .*|-*) refuse "bad release id" ;;
esac

[ -f "$ALLOW_FILE" ] || refuse "no sites.allow"
# Whole-line match, ignoring comments and surrounding spaces.
if ! sed -e 's/#.*//' -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' "$ALLOW_FILE" | grep -Fxq -- "$site"; then
    refuse "site not allowed"
fi

if [ "$action" = deploy ]; then
    [ -x "$DEPLOY" ] || refuse "deploy.sh missing"
    exec env -i HOME="$HOME" USER="${USER-}" LOGNAME="${LOGNAME-}" \
        PATH=/usr/local/bin:/usr/bin:/bin LANG=C.UTF-8 \
        "$DEPLOY" "$site" "$release"
fi

[ -x "$ROLLBACK" ] || refuse "rollback.sh missing"
exec env -i HOME="$HOME" USER="${USER-}" LOGNAME="${LOGNAME-}" \
    PATH=/usr/local/bin:/usr/bin:/bin LANG=C.UTF-8 \
    "$ROLLBACK" "$site" "$release" "$type" < /dev/null
