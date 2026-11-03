# Migration tools

Read-only checks to run with WP-CLI from the WordPress root of a migrated copy:

  wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/<script>.php

- check-slug-collisions.php predicts slug clashes the migration would create. Run it before migrate-all (the legacy content is still there) and after.
- post-counts.php prints the counts to compare with docs/migration-runbook.md after migrate-all.
- validate-gradering.php runs the migration's own TierDeriver (read-only) and prints the derived grades, unresolved by-lines and fuzzy matches.

WP-CLI against the Local database needs the sandbox off in agent sessions.
