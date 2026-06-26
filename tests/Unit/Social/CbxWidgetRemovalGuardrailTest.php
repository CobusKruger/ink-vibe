<?php
/**
 * CBX member-online widget removal guardrail (Story 9.10, FL 9.10).
 *
 * FL 9.10 retires the CBX "who's online" member widget. This greenfield repo
 * never carried it (a legacy-site artifact), so the story is satisfied by a
 * standing regression guardrail: no CODE in the theme or `ink-core` may
 * reference CBX / online-widget tokens. Comments are stripped (a docblock may
 * legitimately NAME CBX as the retired thing); the scan is non-vacuous — it
 * asserts it read real code, including the theme half. Mirrors the Story 6.9
 * LegacyRoutingGuardrailTest.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Tests\Support\CodeScan;

test( 'no CBX member-online widget code remains in theme or ink-core', function (): void {
	$root    = dirname( __DIR__, 3 );
	$targets = array( $root . '/wp-content/themes/ink-foundation/functions.php' );

	foreach ( (array) glob( $root . '/wp-content/themes/ink-foundation/patterns/*.php' ) as $pattern ) {
		$targets[] = $pattern;
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator(
			$root . '/wp-content/plugins/ink-core/src',
			\FilesystemIterator::SKIP_DOTS
		)
	);

	foreach ( $iterator as $entry ) {
		if ( $entry->isFile() && 'php' === strtolower( $entry->getExtension() ) ) {
			$targets[] = $entry->getPathname();
		}
	}

	$banned        = array( 'cbx', 'whos-online', 'whos_online', 'member-online', 'online-widget', 'wie is aanlyn' );
	$offenders     = array();
	$scanned       = 0;
	$theme_scanned = false;
	$functions_php = $root . '/wp-content/themes/ink-foundation/functions.php';

	foreach ( $targets as $file ) {
		if ( ! is_string( $file ) || ! file_exists( $file ) ) {
			continue;
		}

		$code = strtolower( CodeScan::withoutComments( $file ) );

		if ( str_contains( $code, 'function ' ) || str_contains( $code, 'class ' ) ) {
			++$scanned;
		}

		if ( $file === $functions_php && str_contains( $code, 'function ' ) ) {
			$theme_scanned = true;
		}

		foreach ( $banned as $needle ) {
			if ( str_contains( $code, $needle ) ) {
				$offenders[] = basename( $file ) . ':' . $needle;
			}
		}
	}

	expect( $scanned )->toBeGreaterThan( 0 );   // non-vacuous: real code was scanned
	expect( $theme_scanned )->toBeTrue();        // non-vacuous: the theme half was scanned
	expect( $offenders )->toBe( array() );        // no CBX / online-widget token anywhere
} );
