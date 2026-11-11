# Releasing to PROD

`main` deploys automatically to DEV. `production` deploys automatically to
both PROD sites. A release is a pull request from `main` into `production`.

## Steps

1. Make sure everything you want to release is merged into `main` and has
   been checked on DEV.
2. Bump `VERSION` in `main` (see below). The easiest way is to include the
   change in the last feature PR before the release; otherwise open a
   one-line PR into `main`. DEV's footer then shows the upcoming version.
   Then open a PR **from `main` into `production`**, titled after the
   version, for example `Release 1.2.0`.
3. CI runs the usual Tests and Lint checks, plus **Release / version-check**,
   which fails unless `VERSION` is higher than the last release tag.
4. Merge the PR with "Create a merge commit", never squash or rebase, so
   `main` and `production` keep the same history (pick it from the merge
   button's dropdown; GitHub remembers the last choice). Hostinger pulls the new code to
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

## Why the bump is not part of the release PR

A release PR's source branch is `main`, so adding a commit to it means
pushing directly to `main`, which the branch rules block. A separate release
branch would avoid that, but then `production` would have to be merged back
into `main` after every release, which is an easy step to forget.

## Expected oddities

- After each release GitHub shows "production had recent pushes" with a
  **Compare & pull request** button. Ignore it: it would open a PR from
  `production` back into `main`, which is never needed.
- `production` is always one commit ahead of `main`: the release merge
  commit. It holds no code changes and does not affect the next release.

## GitHub repository settings

| Where | Setting |
| --- | --- |
| Settings → General → Pull Requests | Merge commits, squash and rebase all allowed; "Automatically delete head branches" on (it never deletes `main` or protected branches) |
| Ruleset "Protect main and production" (targets `main` and `production`) | Restrict deletions; block force pushes; require a pull request with **0** approvals (a solo developer cannot approve their own PR); require status checks `phpunit`, `e2e`, `php-lint`; "Require linear history" off, because releases need merge commits |
| Ruleset "Production release check" (targets `production` only) | Require status check `version-check`. Kept separate because the check never runs on PRs into `main`, where it would wait forever |
