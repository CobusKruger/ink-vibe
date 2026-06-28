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
 * Runs under Pest/PHPUnit, NOT WordPress. We do, however, define a sentinel
 * `ABSPATH` constant below: every ink-core source file opens with
 * `defined( 'ABSPATH' ) || exit;`, so without it the first autoloaded SUT class
 * would silently `exit(0)` and abort the whole run. Defining the constant
 * satisfies that guard without loading WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

// Satisfy the `defined( 'ABSPATH' ) || exit;` guard that opens every ink-core
// source file. WordPress is not loaded for the unit suite; this sentinel simply
// stops autoloading a guarded class from terminating the process.
defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . '/' );

// WordPress `$wpdb` output-format constants. Mocked stores (e.g. RatingStore)
// pass these to `$wpdb->get_row()/get_results()`; WordPress is not loaded for
// the unit suite, so define the same string values core uses.
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'ARRAY_N' ) || define( 'ARRAY_N', 'ARRAY_N' );
defined( 'OBJECT' ) || define( 'OBJECT', 'OBJECT' );

// Translation-loading seams (Story 17.2). `WP_LANG_DIR` is core's committed
// `wp-content/languages/` home that the third-party plugin `.po/.mo/.json` load
// from; `INK_CORE_FILE` is the plugin main-file path `plugin_basename()` resolves
// against in `Ink\Kernel\I18n::load()`. Sentinel values for the mocked unit suite.
defined( 'WP_LANG_DIR' ) || define( 'WP_LANG_DIR', dirname( __DIR__ ) . '/wp-content/languages' );
defined( 'INK_CORE_FILE' ) || define( 'INK_CORE_FILE', dirname( __DIR__ ) . '/wp-content/plugins/ink-core/ink-core.php' );

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

// Likewise a minimal WP_Error double: the Accounts approval backstop (Story 3.6)
// returns `new \WP_Error( … )` from its `wp_authenticate_user` login gate, so the
// symbol must exist for the unit tests to instantiate and inspect it.
if ( ! class_exists( 'WP_Error' ) ) {
	require_once __DIR__ . '/stubs/class-wp-error.php';
}

// Likewise a minimal WP_Term double: the Library winner→challenge linkage (Story
// 10.5) iterates `get_the_terms()` results and checks `instanceof \WP_Term` before
// reading `$term->slug`, so the symbol must exist for its unit tests.
if ( ! class_exists( 'WP_Term' ) ) {
	require_once __DIR__ . '/stubs/class-wp-term.php';
}

// Likewise a minimal WP_Post double: Story 12.3's Content\FieldSets::save() type-hints
// WP_Post and reads $post->post_type, so the symbol must exist for its unit tests.
if ( ! class_exists( 'WP_Post' ) ) {
	require_once __DIR__ . '/stubs/class-wp-post.php';
}

// Likewise a minimal WP_Query double: Story 14.2's Sponsors\Campaign::activeSponsors()
// constructs `new WP_Query( … )` and iterates `$query->posts`, so the symbol must exist
// for the thin WP wrapper (and the Api delegation through it) to be unit-exercised.
if ( ! class_exists( 'WP_Query' ) ) {
	require_once __DIR__ . '/stubs/class-wp-query.php';
}
