<?php
/**
 * Translation-loading seam guardrails (Story 17.2, NFR-1/NFR-7).
 *
 * The surviving third-party plugin `.po/.mo/.json` are authored on staging and
 * committed to `wp-content/languages/` (the committed-translations home); production
 * loads them WITHOUT Loco. The actual translation content is a pre-launch staging +
 * human-translator gate (no AI Afrikaans) — but the LOADING WIRING is in-repo and
 * must not silently regress, or committed translations would stop loading and English
 * would leak. These tests pin that wiring.
 *
 * Targets: {@see \Ink\InkPols\Viewer::registerScriptTranslations} (Real3D Flipbook JS
 * `.json` — the §12 plugin-JavaScript leak vector) and {@see \Ink\Kernel\I18n::load}
 * (the `ink-core` text domain). Both resolve against the committed languages home.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\I18n;

use Ink\InkPols\Viewer;
use Ink\Kernel\I18n;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'Viewer::registerScriptTranslations wires the flipbook JS to the ink-core domain at the committed languages home', function (): void {
	// The script IS registered, so the seam must fire (non-vacuous: the no-fire
	// case below proves the guard is real).
	Functions\when( 'wp_script_is' )->justReturn( true );
	Functions\expect( 'wp_set_script_translations' )
		->once()
		->with( Viewer::SCRIPT_HANDLE, 'ink-core', WP_LANG_DIR );

	Viewer::registerScriptTranslations();
} );

test( 'Viewer::registerScriptTranslations does NOTHING when the flipbook script is not registered (no-op-safe)', function (): void {
	// Plugin not assembled in-repo → script not registered → no translation load.
	Functions\when( 'wp_script_is' )->justReturn( false );
	Functions\expect( 'wp_set_script_translations' )->never();

	Viewer::registerScriptTranslations();
} );

test( 'I18n::load loads the ink-core text domain from its committed /languages directory', function (): void {
	Functions\when( 'plugin_basename' )->justReturn( 'ink-core/ink-core.php' );
	Functions\expect( 'load_plugin_textdomain' )
		->once()
		->with( 'ink-core', false, 'ink-core/languages' );

	I18n::load();
} );
