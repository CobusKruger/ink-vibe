# Migration tools

Read-only checks to run with WP-CLI from the WordPress root of a migrated copy:

  wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/<script>.php

- check-slug-collisions.php predicts slug clashes the migration would create. Run it before migrate-all (the legacy content is still there) and after.
- post-counts.php prints the counts to compare with docs/migration-runbook.md after migrate-all.
- validate-parser.php scores LegacyResultsParser against the hand-verified July list (originals/winner-list.csv).
- validate-gradering.php runs the migration's own TierDeriver (read-only) and, if tier-import.csv is on this machine, diffs against it.

validate-parser.php needs the winners data in docs/data-cleanup/winners/ (gitignored: it holds member emails), so it only works on a machine that has it. validate-gradering.php works without it (it just skips the comparison).

WP-CLI against the Local database needs the sandbox off in agent sessions.
