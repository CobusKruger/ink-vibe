<?php
/**
 * Matches the entries named in the judges' results to the round's stored entries.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Migration\TextKey;

defined( 'ABSPATH' ) || exit;

/**
 * Pairs each entry the judges wrote about with an entry of the round. Pure: no WP.
 *
 * **The title is the key, the number is the fallback.** The numbers in the judges' document are
 * only as good as the email that carried them: a round judged from an older list, or an email
 * edited by hand, numbers its entries differently from the site. A title survives that. The
 * judges' category headings (Brons, Silwer, Goud, Prosa, Verhale) narrow the candidates to one
 * pool, which is why a title with a small slip ("Que Vadis" for "Quo Vadis") still finds its
 * entry.
 *
 * Order of attempts, each on the entries not yet claimed:
 *  1. the same title ({@see TextKey::title()}) in the same type and pool: `titel`;
 *  2. the same title in the same type, another pool: `titel_ander_poel` (the pool is doubtful);
 *  3. no title in the document (a winner with no comment): the same number: `nommer`;
 *  4. the closest title above {@see self::MIN_SCORE}, best pairs first: `ooreenkoms`.
 * `ooreenkoms` and `titel_ander_poel` are matches to check, and the screen says so.
 *
 * @package Ink\Core
 */
final class EntryMatcher {

	/**
	 * The least similarity (0–1) at which a different title still counts as the same entry.
	 *
	 * @var float
	 */
	public const MIN_SCORE = 0.62;

	/**
	 * Match the document's entries to the round's.
	 *
	 * @param list<array{type:string, grade:string, number:int, title:string}>                    $wanted  What the judges wrote about; `grade` '' when the document gives none.
	 * @param list<array{id:int, type:string, pool:string, number:int, title:string}>            $entries The round's entries; `type`/`pool` '' when unknown, `number` 0 when unnumbered.
	 * @return array<int, array{post_id:int, how:string}> Index in `$wanted` → the match; absent when none was found.
	 */
	public static function match( array $wanted, array $entries ): array {
		$found   = array();
		$claimed = array();

		// 1 and 2: the same title.
		foreach ( array( true, false ) as $same_pool ) {
			foreach ( $wanted as $i => $want ) {
				if ( isset( $found[ $i ] ) || '' === trim( $want['title'] ) ) {
					continue;
				}

				$key   = TextKey::title( $want['title'] );
				$hits  = array();

				foreach ( self::candidates( $want, $entries, $claimed, $same_pool ) as $entry ) {
					if ( TextKey::title( $entry['title'] ) === $key ) {
						$hits[] = $entry;
					}
				}

				$entry = self::only( $hits, $want['number'] );

				if ( null !== $entry ) {
					$found[ $i ]              = array(
						'post_id' => $entry['id'],
						'how'     => $same_pool ? 'titel' : 'titel_ander_poel',
					);
					$claimed[ $entry['id'] ] = true;
				}
			}
		}

		// 3: nothing but a number to go on.
		foreach ( $wanted as $i => $want ) {
			if ( isset( $found[ $i ] ) || '' !== trim( $want['title'] ) || $want['number'] <= 0 ) {
				continue;
			}

			$hits = array();

			foreach ( self::candidates( $want, $entries, $claimed, false ) as $entry ) {
				if ( $entry['number'] === $want['number'] ) {
					$hits[] = $entry;
				}
			}

			if ( 1 === count( $hits ) ) {
				$found[ $i ]              = array(
					'post_id' => $hits[0]['id'],
					'how'     => 'nommer',
				);
				$claimed[ $hits[0]['id'] ] = true;
			}
		}

		// 4: the closest titles, best pair first.
		$pairs = array();

		foreach ( $wanted as $i => $want ) {
			if ( isset( $found[ $i ] ) || '' === trim( $want['title'] ) ) {
				continue;
			}

			foreach ( self::candidates( $want, $entries, $claimed, true ) as $entry ) {
				$score = self::similarity( $want['title'], $entry['title'] );

				if ( $score >= self::MIN_SCORE ) {
					$pairs[] = array( $score, $i, $entry['id'] );
				}
			}
		}

		usort( $pairs, static fn( array $a, array $b ): int => $b[0] <=> $a[0] );

		foreach ( $pairs as $pair ) {
			if ( isset( $found[ $pair[1] ] ) || isset( $claimed[ $pair[2] ] ) ) {
				continue;
			}

			$found[ $pair[1] ]  = array(
				'post_id' => $pair[2],
				'how'     => 'ooreenkoms',
			);
			$claimed[ $pair[2] ] = true;
		}

		return $found;
	}

	/**
	 * How alike two titles are, 0–1, through {@see TextKey::title()}: so accents, case, a leading 'n,
	 * punctuation and a grade or word count added to a title all count for nothing. Pure.
	 *
	 * @param string $a One title.
	 * @param string $b The other.
	 * @return float
	 */
	public static function similarity( string $a, string $b ): float {
		$ka = mb_substr( TextKey::title( $a ), 0, 120 );
		$kb = mb_substr( TextKey::title( $b ), 0, 120 );

		if ( '' === $ka || '' === $kb ) {
			return 0.0;
		}

		if ( $ka === $kb ) {
			return 1.0;
		}

		// levenshtein() counts bytes; a folded key is plain ASCII bar a few letters, which is close enough.
		$longest = max( strlen( $ka ), strlen( $kb ) );
		$score   = 1 - levenshtein( $ka, $kb ) / $longest;

		// One title inside the other ("Tussen die lyne" and "Tussen die lyne en iets"): the same piece.
		if ( min( strlen( $ka ), strlen( $kb ) ) >= 6 && ( str_contains( $ka, $kb ) || str_contains( $kb, $ka ) ) ) {
			$score = max( $score, 0.9 );
		}

		return $score;
	}

	/**
	 * The unclaimed entries of the wanted entry's type, and of its pool when `$same_pool`. Pure.
	 *
	 * An entry whose type or pool is unknown is never ruled out.
	 *
	 * @param array{type:string, grade:string, number:int, title:string}                   $want      The wanted entry.
	 * @param list<array{id:int, type:string, pool:string, number:int, title:string}>      $entries   The round's entries.
	 * @param array<int, bool>                                                             $claimed   Post ids already matched.
	 * @param bool                                                                         $same_pool Whether to keep to the wanted pool.
	 * @return list<array{id:int, type:string, pool:string, number:int, title:string}>
	 */
	private static function candidates( array $want, array $entries, array $claimed, bool $same_pool ): array {
		$kept = array();

		foreach ( $entries as $entry ) {
			if ( isset( $claimed[ $entry['id'] ] ) ) {
				continue;
			}

			if ( '' !== $want['type'] && '' !== $entry['type'] && $want['type'] !== $entry['type'] ) {
				continue;
			}

			if ( $same_pool && '' !== $want['grade'] && '' !== $entry['pool'] && $want['grade'] !== $entry['pool'] ) {
				continue;
			}

			$kept[] = $entry;
		}

		return $kept;
	}

	/**
	 * The one entry among equal-title hits: the lone hit, or of several the one carrying the document's number. Pure.
	 *
	 * @param list<array{id:int, type:string, pool:string, number:int, title:string}> $hits   The hits.
	 * @param int                                                                     $number The number in the document.
	 * @return array{id:int, type:string, pool:string, number:int, title:string}|null
	 */
	private static function only( array $hits, int $number ): ?array {
		if ( 1 === count( $hits ) ) {
			return $hits[0];
		}

		$numbered = array_values( array_filter( $hits, static fn( array $e ): bool => $e['number'] === $number && $number > 0 ) );

		return 1 === count( $numbered ) ? $numbered[0] : null;
	}
}
