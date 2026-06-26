<?php
/**
 * Shared code-only scanning helper for structural guardrail tests.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Support;

/**
 * Strips comments from PHP source so structural guardrails (the conflation rule,
 * the legacy-routing ban) scan real CODE — not the docblocks that legitimately
 * NAME the thing they forbid ("Conflation-clean: no Ink\Tiers", "replaces the
 * Youzify form"). One source, reused by every guardrail (single-source discipline).
 */
final class CodeScan {

	/**
	 * Return a PHP file's source with all comments / doc-comments removed.
	 *
	 * @param string $file Absolute path to a PHP file.
	 * @return string The source with comment tokens stripped.
	 */
	public static function withoutComments( string $file ): string {
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
}
