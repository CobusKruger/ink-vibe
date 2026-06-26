<?php
/**
 * Single-source reaction-count formatter — Story 7.8 (FR-28).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\Reaction;

defined( 'ABSPATH' ) || exit;

/**
 * Formats a reaction count VERB-LESS, with locale-correct `_n()` plurals.
 *
 * The ONE place reaction counts become text, so every count surface reads
 * identically (FR-28). Resonance is shown without vanity framing: "342 hartjies",
 * never "342 mense het gehou" — the icon does the verb. `_n()` gives the correct
 * singular/plural ("1 hartjie" / "342 hartjies"); n=0 renders the plural
 * ("0 hartjies").
 *
 * Copy provenance: the hartjie singular/plural is the glossary form
 * (afrikaans-terms.md line 156; ui-copy 681/724). The "duim op" count phrase is
 * invariant and "wow"/"wows" is the natural plural of the authored reaction term —
 * flagged as copy-debt to ratify into the glossary on the next pass, not invented
 * marketing prose.
 *
 * @package Ink\Core
 */
final class ReactionCounts {

	/**
	 * The verb-less, plural-correct count label for one reaction.
	 *
	 * @param Reaction $reaction The reaction.
	 * @param int      $n        The count (n=0 → plural form).
	 * @return string e.g. "342 hartjies", "1 hartjie", "0 hartjies".
	 */
	public static function label( Reaction $reaction, int $n ): string {
		switch ( $reaction ) {
			case Reaction::Hartjie:
				$format = _n( '%d hartjie', '%d hartjies', $n, 'ink-core' );
				break;
			case Reaction::DuimOp:
				$format = _n( '%d duim op', '%d duim op', $n, 'ink-core' );
				break;
			case Reaction::Wow:
			default:
				$format = _n( '%d wow', '%d wows', $n, 'ink-core' );
				break;
		}

		return sprintf( $format, $n );
	}
}
