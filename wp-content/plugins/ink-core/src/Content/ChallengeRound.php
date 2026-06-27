<?php
/**
 * Round-term ↔ uitdaging slug convention — single source (Stories 6.6 / 10.5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for the `uitdagingsrondte` round-term ↔ `uitdaging` join key.
 *
 * A bydrae/biblioteek_item linked to a challenge carries an `uitdagingsrondte` term
 * whose slug encodes the producing `uitdaging` post id as `uitdaging-{id}` (Story
 * 6.6 {@see \Ink\Submission\ChallengeLinking::resolveRoundTerm()}). This class owns
 * that convention in both directions so the writer (Submission, which creates the
 * term) and the reader (Library winner→challenge linkage, Story 10.5) share one
 * definition rather than duplicating the literal. Content is the natural home — it
 * registers both the {@see Taxonomies::UITDAGINGSRONDTE} taxonomy and the
 * {@see PostTypes::UITDAGING} CPT this key joins.
 *
 * The authoritative entry record (`ink_entries`) and the definitive round model are
 * Epic 12/12A; this slug convention is the launch-grade relationship.
 *
 * @package Ink\Core
 */
final class ChallengeRound {

	/**
	 * The round-term slug prefix; the uitdaging id follows.
	 *
	 * @var string
	 */
	public const SLUG_PREFIX = 'uitdaging-';

	/**
	 * The `uitdagingsrondte` round-term slug for a uitdaging.
	 *
	 * @param int $uitdaging_id The producing uitdaging post id.
	 * @return string The stable round-term slug (e.g. `uitdaging-7`).
	 */
	public static function slugFor( int $uitdaging_id ): string {
		return self::SLUG_PREFIX . $uitdaging_id;
	}

	/**
	 * Parse a round-term slug back to its producing uitdaging id. Pure.
	 *
	 * Returns null for any slug that is not `uitdaging-<positive-int>` — so a
	 * hand-made / migrated / malformed round term resolves to no challenge rather
	 * than a spurious id.
	 *
	 * @param string $slug The round-term slug.
	 * @return int|null The uitdaging id, or null when the slug doesn't encode one.
	 */
	public static function uitdagingIdFromSlug( string $slug ): ?int {
		if ( 1 !== preg_match( '/^' . preg_quote( self::SLUG_PREFIX, '/' ) . '([1-9][0-9]*)$/', $slug, $matches ) ) {
			return null;
		}

		return (int) $matches[1];
	}
}
