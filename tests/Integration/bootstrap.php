<?php
/**
 * Integration-suite bootstrap (NFR-9, Story 18.8) — wp-env WP test library.
 *
 * Integration tests run INSIDE the wp-env Docker stack
 * (`npx @wordpress/env start`) against a real WordPress + database, exercising the
 * load-bearing theme<->plugin and plugin<->platform seams the architecture names
 * (active membership => can submit, expired => denied, tier write => meta + log).
 * They are NOT mocked.
 *
 * This loads the WordPress PHPUnit test library: `functions.php`, then a
 * `muplugins_loaded` filter that mounts ink-core before WordPress boots, then the
 * test-library `bootstrap.php`. `WP_TESTS_DIR` is provided by the wp-env tests
 * container (defaults to the conventional path when run via `wp-env run tests-cli`).
 *
 * Runs under PHPUnit inside wp-env, NOT as a mocked unit test — no ABSPATH guard.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

$ink_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( false === $ink_tests_dir || '' === $ink_tests_dir ) {
	$ink_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$ink_tests_functions = $ink_tests_dir . '/includes/functions.php';

// Guard: when the WP test library is not present (e.g. someone runs the Integration
// suite outside wp-env), fail loudly with a clear message rather than fataling deep
// in WordPress. The suite is CI/wp-env-only by design.
if ( ! is_readable( $ink_tests_functions ) ) {
	fwrite(
		STDERR,
		"Integration suite requires the WordPress test library (wp-env).\n" .
		"Run inside wp-env: `npx @wordpress/env start` then `composer test:integration`\n" .
		"or set WP_TESTS_DIR to a checkout of the WP PHPUnit test library.\n"
	);
	exit( 1 );
}

require_once $ink_tests_functions;

/**
 * Mount the ink-core plugin before WordPress finishes loading, so its CPTs,
 * taxonomies, custom tables and module bootstraps are active for the seam tests.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/ink-core.php';
	}
);

require $ink_tests_dir . '/includes/bootstrap.php';
