# Releasing to PROD

`main` deploys automatically to DEV. `production` deploys automatically to
both PROD sites. A release is a pull request from `main` into `production`.

## Steps

1. Make sure everything you want to release is merged into `main` and has
   been checked on DEV.
2. Bump `VERSION` in `main` (see below) with a one-line PR into `main`.
   Then open a PR **from `main` into `production`**, titled after the
   version, for example `Release 1.2.0`.
3. CI runs the usual Tests and Lint checks, plus **Release / version-check**,
   which fails unless `VERSION` is higher than the last release tag.
4. Merge the PR (use "Create a merge commit", not squash, so `main` and
   `production` keep the same history). Hostinger pulls the new code to
   both PROD sites, and the **Release / publish** workflow creates the tag
   `v<VERSION>` and a GitHub Release listing the PRs included.
5. If the release includes a migration (any new file in
   `application/migrations/`), take a database backup of each PROD site
   first, then open its Migrate URL once (see
   [DATABASE_CHANGES.md](DATABASE_CHANGES.md)).
6. Check the footer of each PROD site shows the new version, and click
   through the main pages.

## Which number to bump

`VERSION` uses `MAJOR.MINOR.PATCH`:

| Change in the release | Bump | Example |
| --- | --- | --- |
| Bug fixes only | PATCH | 1.2.0 to 1.2.1 |
| New features or screens, any migration | MINOR | 1.2.1 to 1.3.0 |
| Something users or the other organization must prepare for (removed feature, changed workflow, manual data step) | MAJOR | 1.3.0 to 2.0.0 |

## Hotfixes

Fix on a branch from `main`, merge it into `main`, then release as usual
with a PATCH bump. Don't commit directly to `production`; it should only
ever change through release PRs, so it never contains code DEV hasn't run.

## Rolling back

Open a release PR into `production` that reverts the bad change (use
GitHub's "Revert" button on the original PR, merge that into `main`, then
release it with a PATCH bump). Migrations are not reversed automatically,
which is why they should be additive (see DATABASE_CHANGES.md).
