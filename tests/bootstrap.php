<?php
/**
 * Unit-suite bootstrap for ink-vibe (NFR-9, Story 1.11).
 *
 * Loads Composer's autoloader (which provides the two PSR-4 maps from the
 * repo-root composer.json: `Ink\` -> wp-content/plugins/ink-core/src and
 * `Ink\Tests\` -> tests/) and satisfies the runtime assumptions the
 * already-authored unit tests declare in their headers:
 *
 *   - Brain\Monkey is available and each test manages its own setUp/tearDown.
 *   - `apply_filters` returns its second argument by default (Brain Monkey's
 *     default), so an un-filtered `ink_comment_open_exception` resolves to its
 *     `false` default in CommentsTest.
 *   - A `WP_User` symbol exists for AdminLanguageTest (which builds
 *     `Mockery::mock( \WP_User::class )`). WordPress is NOT loaded for unit
 *     tests, so we register a minimal `WP_User` double when the real class is
 *     absent.
 *
 * Runs under Pest/PHPUnit, NOT WordPress — so there is intentionally no
 * `ABSPATH` guard here.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

$ink_autoload = __DIR__ . '/../vendor/autoload.php';

if ( ! is_readable( $ink_autoload ) ) {
	fwrite(
		STDERR,
		"\n[ink-vibe] vendor/autoload.php not found. Run `composer install` before the test suite.\n\n"
	);
	exit( 1 );
}

require_once $ink_autoload;

// Provide a minimal WP_User test double for the unit suite (WordPress is mocked,
// not loaded). php-stubs/wordpress-stubs is a static-analysis package (signatures
// only, not autoloadable at runtime), so the unit harness defines its own light
// double when the real class is unavailable.
if ( ! class_exists( 'WP_User' ) ) {
	require_once __DIR__ . '/stubs/class-wp-user.php';
}
