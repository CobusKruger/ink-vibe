# Migration tools

Read-only checks to run with WP-CLI from the WordPress root of a migrated copy. They need the winners data in docs/data-cleanup/winners/, which is gitignored (it holds member emails), so they only work on a machine that has it.

- validate-parser.php scores LegacyResultsParser against the hand-verified July list (originals/winner-list.csv).
- validate-gradering.php derives gradering from the result posts and diffs it against tier-import.csv.

Run: wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/<script>.php
