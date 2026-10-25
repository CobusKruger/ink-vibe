# Migration tools

Read-only checks to run with WP-CLI from the WordPress root of a migrated copy:

  wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/<script>.php

- check-slug-collisions.php predicts slug clashes the migration would create. Run it before migrate-all (the legacy content is still there) and after.
- post-counts.php prints the counts to compare with docs/migration-runbook.md after migrate-all.
- validate-parser.php scores LegacyResultsParser against the hand-verified July list (originals/winner-list.csv).
- validate-gradering.php derives gradering from the result posts and diffs it against tier-import.csv.

The two validate scripts need the winners data in docs/data-cleanup/winners/, which is gitignored (it holds member emails), so they only work on a machine that has it.

WP-CLI against the Local database needs the sandbox off in agent sessions.
