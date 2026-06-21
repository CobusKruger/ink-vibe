<?php
/**
 * Integration-suite bootstrap (NFR-9, Story 1.11) — wp-env WP test library.
 *
 * Integration tests run INSIDE the wp-env Docker stack
 * (`npx @wordpress/env start`) against a real WordPress + database, exercising
 * the load-bearing theme<->plugin and plugin<->platform seams the architecture
 * names (active membership => can submit, expired => denied, tier write =>
 * meta + log). They are NOT mocked.
 *
 * The full buildout — loading the WP PHPUnit test library
 * (`{WP_TESTS_DIR}/includes/functions.php`, `tests_add_filter()` to mount
 * ink-core before WordPress boots, then `{WP_TESTS_DIR}/includes/bootstrap.php`)
 * and a dedicated `phpunit` invocation via `wp-env run tests-cli` — lands with
 * the first real integration test in Story 18.8 (the pyramid buildout).
 *
 * This file documents the seam and keeps the Integration testsuite home in
 * place so a P0-rule story (5.x / 6.8 / 9.2) can drop in its integration test
 * against an existing harness.
 *
 * Runs under PHPUnit inside wp-env, NOT as a mocked unit test — no ABSPATH guard.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

// Buildout deferred to Story 18.8. Intentionally a no-op today: the scaffolded
// integration test is skipped, so it never reaches a real-WP bootstrap.
