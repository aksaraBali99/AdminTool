#!/usr/bin/env bash
# Builds the release tarball that deploy/server/deploy.sh unpacks:
#
#   bash deploy/build-release.sh <git-ref> <out.tar.gz>
#
# Used by .github/workflows/deploy.yml and tests/deploy/deploy-test.sh.
# The tarball holds the committed files of <git-ref> (never local changes),
# minus what a live site doesn't need, plus REVISION (the full commit sha).
# It never holds .git, .htaccess or uploads/: each site keeps its own
# .htaccess (database settings) and uploads/ in ~/sites/<site>/shared/.
set -euo pipefail

ref="${1:?usage: build-release.sh <git-ref> <out.tar.gz>}"
out="${2:?usage: build-release.sh <git-ref> <out.tar.gz>}"

repo="$(git rev-parse --show-toplevel)"
sha="$(git -C "$repo" rev-parse --verify "$ref^{commit}")"
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT

git -C "$repo" archive --format=tar "$sha" | tar -x -C "$stage"

# Development-only files and folders, and anything a site keeps itself.
exclude=(
    .github .gitignore .gitattributes .editorconfig
    tests e2e docs deploy database
    composer.json composer.lock phpunit.xml
    README.md readme.rst
    uploads .htaccess maintenance.on
    graphify-out .claude .vscode .idea
)

# Production code only needs vendor/ if CodeIgniter loads Composer's
# autoloader (application/config/config.php). Today it doesn't.
if grep -Eq "^[[:space:]]*\\\$config\\['composer_autoload'\\][[:space:]]*=[[:space:]]*FALSE[[:space:]]*;" \
    "$stage/application/config/config.php"; then
    echo "composer_autoload is FALSE: no vendor/ in the release."
else
    echo "composer_autoload is on: installing production Composer packages."
    composer install --working-dir="$stage" --no-dev --no-interaction --prefer-dist \
        --no-scripts --optimize-autoloader
fi

for path in "${exclude[@]}"; do
    rm -rf "${stage:?}/$path"
done
find "$stage" -name 'maintenance.on' -delete

printf '%s\n' "$sha" > "$stage/REVISION"

for required in index.php VERSION application/config/config.php system/core/CodeIgniter.php; do
    [ -f "$stage/$required" ] || { echo "The release has no $required" >&2; exit 1; }
done

tar -czf "$out" -C "$stage" .
echo "Built $out: commit $sha, version $(tr -d '[:space:]' < "$stage/VERSION"), $(wc -c < "$out" | tr -d ' ') bytes."
