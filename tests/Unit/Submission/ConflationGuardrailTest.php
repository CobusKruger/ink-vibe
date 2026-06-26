<?php
/**
 * Conflation-rule guardrail for the Submission module (Story 6.8, FR-13/FR-19).
 *
 * THE conflation rule: publishing tracks lidmaatskap (Entitlement), NEVER the
 * writer Gradering (Tiers). Story 6.8 is the enforcement point, so this is a
 * structural guardrail: NO CODE under `src/Submission/` may reference `Ink\Tiers`
 * or the `ink_writer_tier` meta key. Comments are stripped before scanning (the
 * docblocks legitimately NAME the rule — "Conflation-clean: no Ink\Tiers"); only
 * real code tokens are checked. The test is non-vacuous — it asserts it actually
 * scanned the module's files AND that comment-stripping left real code behind, so
 * neither an empty glob nor an all-comment file can pass it vacuously (the Epic-5
 * guardrail lesson).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

/**
 * Return a file's PHP source with all comments / doc-comments stripped.
 */
function ink_submission_code_only( string $file ): string {
	$code = '';

	foreach ( token_get_all( (string) file_get_contents( $file ) ) as $token ) {
		if ( is_array( $token ) ) {
			if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
				continue;
			}
			$code .= $token[1];
		} else {
			$code .= $token;
		}
	}

	return $code;
}

test( 'no Submission CODE references Ink\\Tiers or ink_writer_tier', function (): void {
	$dir   = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Submission';
	$files = glob( $dir . '/*.php' );

	expect( $files )->toBeArray();
	expect( count( (array) $files ) )->toBeGreaterThan( 0 ); // non-vacuous: the scan found files

	$offenders   = array();
	$scanned_code = false;

	foreach ( (array) $files as $file ) {
		$code = ink_submission_code_only( $file );

		// Prove comment-stripping left real code (every module file has a class).
		if ( str_contains( $code, 'class ' ) ) {
			$scanned_code = true;
		}

		if ( str_contains( $code, 'Ink\\Tiers' ) || str_contains( $code, 'ink_writer_tier' ) ) {
			$offenders[] = basename( $file );
		}
	}

	expect( $scanned_code )->toBeTrue(); // non-vacuous: code (not just comments) was scanned
	expect( $offenders )->toBe( array() );
} );
