<?php
/**
 * Which post won, and which round it was: the matching rules of the winner importer.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Pure decisions behind {@see WinnerImporter}. Titles are compared only through {@see TextKey}.
 *
 * A result post lists a writer and a title. The winning post is that writer's own post with that
 * title: exactly, or with something added to it ("Die onderhoud (Geskiedenisfiksie)") or
 * held in it ("Villanelle: Na jou asem weg is"). A post filed under the round (an entry) is
 * preferred, because winning posts are filed under their round; then one written shortly before
 * the result post (a result comes after its round closes, within about three months).
 *
 * People also mistype a title, and the result post copies the typo or the post has it ("Kuntsugi",
 * "Kintsugi"). A title that is nearly the same counts as a match only for a post filed under the
 * round, and never when the numbers in the two titles differ ("Tanka 1", "Tanka 2").
 *
 * A result post with no stored round link gets its round from its winners: the round that holds
 * most of the winning posts, if one round clearly leads.
 */
final class WinnerMatcher {

	/**
	 * The shortest key that can stand for a title by containment, so a one-word title does not match everything.
	 *
	 * @var int
	 */
	public const MIN_CONTAINS = 6;

	/**
	 * How many days before the result post a winning post is expected to have been written.
	 *
	 * @var int
	 */
	public const WINDOW_DAYS = 150;

	/**
	 * How many days after a round starts its result post may appear: the month, then about three more.
	 *
	 * @var int
	 */
	public const ROUND_SPAN_DAYS = 200;

	/**
	 * Score of an exact match.
	 *
	 * @var int
	 */
	public const EXACT = 3;

	/**
	 * Score of a match where one title holds the other.
	 *
	 * @var int
	 */
	public const CONTAINED = 2;

	/**
	 * Score of a near match: a typo. Counts only for a post filed under the round.
	 *
	 * @var int
	 */
	public const CLOSE = 1;

	/**
	 * How alike two title keys must be, in percent, to be a near match.
	 *
	 * @var float
	 */
	public const CLOSE_PERCENT = 85.0;

	/**
	 * The writer's posts that carry a title, best kind of match first. Pure.
	 *
	 * @param string                                                           $title The typed title.
	 * @param list<array{id:int, title:string, date:string, rounds:list<int>}> $posts The writer's posts.
	 * @return list<array{id:int, title:string, date:string, rounds:list<int>, score:int}>
	 */
	public static function matches( string $title, array $posts ): array {
		$key = TextKey::title( $title );

		if ( '' === $key ) {
			return array();
		}

		$out = array();

		foreach ( $posts as $post ) {
			$candidate = TextKey::title( $post['title'] );

			if ( '' === $candidate ) {
				continue;
			}

			if ( $candidate === $key ) {
				$out[] = $post + array( 'score' => self::EXACT );
			} elseif ( min( strlen( $candidate ), strlen( $key ) ) >= self::MIN_CONTAINS && ( str_contains( $candidate, $key ) || str_contains( $key, $candidate ) ) ) {
				$out[] = $post + array( 'score' => self::CONTAINED );
			} elseif ( self::isClose( $candidate, $key ) ) {
				$out[] = $post + array( 'score' => self::CLOSE );
			}
		}

		return $out;
	}

	/**
	 * The one post a win names, or null. Pure.
	 *
	 * Order: filed under the round, then the better match, then written inside the window before
	 * the result post, then nearest that date, then the lowest id.
	 *
	 * @param list<array{id:int, title:string, date:string, rounds:list<int>, score:int}> $matches     {@see self::matches()}.
	 * @param int                                                                         $round       The win's round, or 0.
	 * @param string                                                                      $result_date The result post's date.
	 * @return int|null The post id.
	 */
	public static function pick( array $matches, int $round, string $result_date ): ?int {
		if ( array() === $matches ) {
			return null;
		}

		// A near match is trusted only inside the round: a title alone is too weak.
		$matches = array_values(
			array_filter(
				$matches,
				static fn ( array $match ): bool => self::CLOSE !== $match['score'] || ( $round > 0 && in_array( $round, $match['rounds'], true ) )
			)
		);

		if ( array() === $matches ) {
			return null;
		}

		$result = strtotime( $result_date );
		$result = false === $result ? 0 : $result;

		usort(
			$matches,
			static function ( array $a, array $b ) use ( $round, $result ): int {
				$in_a = $round > 0 && in_array( $round, $a['rounds'], true ) ? 1 : 0;
				$in_b = $round > 0 && in_array( $round, $b['rounds'], true ) ? 1 : 0;

				if ( $in_a !== $in_b ) {
					return $in_b <=> $in_a;
				}

				if ( $a['score'] !== $b['score'] ) {
					return $b['score'] <=> $a['score'];
				}

				$win_a = self::inWindow( $a['date'], $result ) ? 1 : 0;
				$win_b = self::inWindow( $b['date'], $result ) ? 1 : 0;

				if ( $win_a !== $win_b ) {
					return $win_b <=> $win_a;
				}

				$gap_a = abs( $result - (int) strtotime( $a['date'] ) );
				$gap_b = abs( $result - (int) strtotime( $b['date'] ) );

				return array( $gap_a, $a['id'] ) <=> array( $gap_b, $b['id'] );
			}
		);

		return $matches[0]['id'];
	}

	/**
	 * The round a result post belongs to, from how many of its winning posts each round holds. Pure.
	 *
	 * A round counts only if it started before the result post and no more than
	 * {@see self::ROUND_SPAN_DAYS} before it. The leading round must lead alone; a tie gives 0.
	 *
	 * @param array<int, int>    $votes       Round id => number of winning posts it holds.
	 * @param array<int, string> $round_dates Round id => the date it started.
	 * @param string             $result_date The result post's date.
	 * @return int The round id, or 0 when none clearly leads.
	 */
	public static function chooseRound( array $votes, array $round_dates, string $result_date ): int {
		$result = strtotime( $result_date );

		if ( false === $result ) {
			return 0;
		}

		$best = 0;
		$top  = 0;
		$tied = false;

		foreach ( $votes as $round => $count ) {
			$started = strtotime( $round_dates[ $round ] ?? '' );

			if ( false === $started || $started > $result || ( $result - $started ) > self::ROUND_SPAN_DAYS * 86400 ) {
				continue;
			}

			if ( $count > $top ) {
				$best = (int) $round;
				$top  = $count;
				$tied = false;
			} elseif ( $count === $top ) {
				$tied = true;
			}
		}

		return $tied || $top < 1 ? 0 : $best;
	}

	/**
	 * Whether two title keys are nearly the same: alike by {@see self::CLOSE_PERCENT} and with the same numbers. Pure.
	 *
	 * @param string $a A title key.
	 * @param string $b A title key.
	 * @return bool
	 */
	public static function isClose( string $a, string $b ): bool {
		$longest = max( strlen( $a ), strlen( $b ) );

		if ( min( strlen( $a ), strlen( $b ) ) < self::MIN_CONTAINS || abs( strlen( $a ) - strlen( $b ) ) > $longest * 0.3 ) {
			return false;
		}

		if ( preg_replace( '/\D+/', '', $a ) !== preg_replace( '/\D+/', '', $b ) ) {
			return false;
		}

		similar_text( $a, $b, $percent );

		return $percent >= self::CLOSE_PERCENT;
	}

	/**
	 * Whether a date lies inside the window before the result post. Pure.
	 *
	 * @param string $date   The post's date.
	 * @param int    $result The result post's timestamp.
	 * @return bool
	 */
	private static function inWindow( string $date, int $result ): bool {
		$time = strtotime( $date );

		return false !== $time && $time <= $result && ( $result - $time ) <= self::WINDOW_DAYS * 86400;
	}
}
