<?php
/**
 * Verb-less follower-count labels — Story 9.2 (FR-38).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * The verb-less, plural-correct volgeling-count label.
 *
 * Mirrors the Story 7.8 reaction-count house style: a noun phrase ("12
 * volgelinge"), never a verb phrase ("12 mense volg"). Uses `_n()` over the
 * glossary's `volgeling` / `volgelinge` (afrikaans-terms.md — approved,
 * human-authored; NEVER "volger"). `number_format_i18n` localises the digits.
 *
 * @package Ink\Core
 */
final class FollowCounts {

	/**
	 * The volgeling-count label for a skrywer.
	 *
	 * @param int $n The follower count (n=0 → plural form).
	 * @return string e.g. "1 volgeling", "0 volgelinge", "12 volgelinge".
	 */
	public static function volgelingLabel( int $n ): string {
		/* translators: %s: the number of volgelinge (followers). */
		$format = _n( '%s volgeling', '%s volgelinge', $n, 'ink-core' );

		return sprintf( $format, number_format_i18n( $n ) );
	}
}
