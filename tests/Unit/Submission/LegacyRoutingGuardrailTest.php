<?php
/**
 * Legacy submission-routing guardrail (Story 6.9, FL 6.9).
 *
 * FL 6.9 retires the Youzify-era `/plaas-nuwe-publikasie` edit-link override. This
 * greenfield repo never carried it (it is a legacy-site artifact), so the story is
 * satisfied by a standing regression guardrail: no CODE in the theme or `ink-core`
 * may reference `youzify` / `plaas-nuwe-publikasie` / `plaas_nuwe`, and the only
 * submission route is the new Skryf `admin-post` flow. Comments are stripped (the
 * Story-6.1/6.3 docblocks legitimately NAME Youzify as the thing replaced); the
 * scan is non-vacuous — it asserts it actually read real code.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\SubmissionForm;
use Ink\Tests\Support\CodeScan;

test( 'no legacy Youzify submission override remains in theme or ink-core code', function (): void {
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

	$banned        = array( 'youzify', 'plaas-nuwe-publikasie', 'plaas_nuwe' );
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

		// Prove the THEME half (not just ink-core) was actually scanned — the
		// theme functions.php is where the legacy override would have lived, so a
		// path-layout change that silently drops it must fail this guardrail.
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
	expect( $offenders )->toBe( array() );
} );

test( 'submission routing uses only the new Skryf admin-post flow', function (): void {
	expect( SubmissionForm::postAction() )->toBe( 'ink_submission_plaas' );
	expect( SubmissionForm::postAction() )->not->toBe( '' );
} );
