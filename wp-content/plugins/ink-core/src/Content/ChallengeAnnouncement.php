<?php
/**
 * Finding the post that announced a challenge round.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * Nobody can enter a round before it is announced, so the announcement is the
 * first post a moderator filed under the round's category. It is editorial, not
 * submitted writing: it stays a core `post` (like a result post) and is not an
 * entry of the round it announces. The category's description already carries the
 * announcement text, so the post's only other use is its date, which dates the
 * Uitdaging.
 *
 * Titles are not reliable ("Julle 2026 DIS NOG NIKS..." for a category called
 * "Julie 2026 - Dis nog niks..."), so position decides, and a similarity floor only
 * rejects later news posts ("LEES dringend asb - ... het gesluit") that happen to
 * be the first moderator post of a round that had no announcement.
 *
 * @package Ink\Core
 */
final class ChallengeAnnouncement {

	/**
	 * The least title/category similarity (percent) for the first moderator post to count.
	 */
	public const MIN_SIMILARITY = 50.0;

	/**
	 * Post meta marking a kept announcement.
	 */
	public const META = 'ink_challenge_announcement';

	/**
	 * Lower-case, accent-free-of-markup, punctuation-free text for comparison. Pure.
	 *
	 * @param string $text A title or category name.
	 */
	public static function normalise( string $text ): string {
		$plain = mb_strtolower( strip_tags( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), 'UTF-8' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- pure helper, runs without WordPress loaded.

		return trim( (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $plain ) );
	}

	/**
	 * Title/category similarity as a percentage. Pure.
	 *
	 * @param string $title    The post title.
	 * @param string $category The category (round) name.
	 */
	public static function similarity( string $title, string $category ): float {
		$percent = 0.0;
		similar_text( self::normalise( $title ), self::normalise( $category ), $percent );

		return $percent;
	}

	/**
	 * The announcement among a round's candidate posts, or null. Pure.
	 *
	 * @param list<array{id:int, date:string, title:string}> $candidates Moderator-authored, non-poem, non-result posts filed under the category.
	 * @param string                                         $category   The category (round) name.
	 * @return int|null The earliest candidate's id when its title resembles the round.
	 */
	public static function pick( array $candidates, string $category ): ?int {
		if ( array() === $candidates ) {
			return null;
		}

		usort(
			$candidates,
			static fn ( array $a, array $b ): int => array( $a['date'], $a['id'] ) <=> array( $b['date'], $b['id'] )
		);

		$first = $candidates[0];

		return self::similarity( $first['title'], $category ) >= self::MIN_SIMILARITY ? $first['id'] : null;
	}
}
