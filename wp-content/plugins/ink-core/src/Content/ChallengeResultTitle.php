<?php
/**
 * Pure title rules for challenge result posts — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * How to recognise and date a moderator's challenge result post from its title alone.
 * Lives in Content so both the Migration module (which must not reclassify these as
 * submitted writing) and the Challenges module (which links them to their round) can
 * use it without depending on each other. Pure, zero WordPress.
 *
 * @package Ink\Core
 */
final class ChallengeResultTitle {

	/**
	 * Whether a title looks like a challenge result post. Pure.
	 *
	 * @param string $title The post title.
	 */
	public static function isResultTitle( string $title ): bool {
		$plain = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$has_result    = 1 === preg_match( '/\b(uitsla[eg]|wenners?)\b/iu', $plain );
		$has_challenge = 1 === preg_match( '/(projek|kompetisie|kom+entaa?[rl]e?)/iu', $plain );

		return $has_result && $has_challenge;
	}

	/**
	 * The (month, year) a challenge title or result-post title refers to, or null. Pure.
	 *
	 * The first Afrikaans month name and the first 20xx year in the text, so
	 * "November & Desember 2025 - OOP projek" gives November 2025 and a title with no year
	 * ("APRIL PROJEK: Kommentare en uitslae") gives null (left for a human).
	 *
	 * @param string $title A challenge name or result-post title.
	 * @return array{0:int,1:int}|null [month 1-12, year].
	 */
	public static function monthYear( string $title ): ?array {
		$months = array(
			'januarie'  => 1,
			'februarie' => 2,
			'maart'     => 3,
			'april'     => 4,
			'mei'       => 5,
			'junie'     => 6,
			'julie'     => 7,
			'augustus'  => 8,
			'september' => 9,
			'oktober'   => 10,
			'november'  => 11,
			'desember'  => 12,
		);

		$plain = mb_strtolower( html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

		if ( 1 !== preg_match( '/\b(20\d\d)\b/', $plain, $year ) ) {
			return null;
		}

		$best = null;

		foreach ( $months as $name => $number ) {
			$pos = mb_strpos( $plain, $name );

			if ( false !== $pos && ( null === $best || $pos < $best[0] ) ) {
				$best = array( $pos, $number );
			}
		}

		return null === $best ? null : array( $best[1], (int) $year[1] );
	}

	/**
	 * The single uitdaging for a (month, year), or null when none or ambiguous. Pure.
	 *
	 * @param array{0:int,1:int}|null $month_year The result post's (month, year).
	 * @param array<int, string>      $uitdagings uitdaging id → title.
	 */
	public static function matchUitdaging( ?array $month_year, array $uitdagings ): ?int {
		if ( null === $month_year ) {
			return null;
		}

		$hits = array();

		foreach ( $uitdagings as $id => $title ) {
			if ( self::monthYear( $title ) === $month_year ) {
				$hits[] = (int) $id;
			}
		}

		return 1 === count( $hits ) ? $hits[0] : null;
	}

	/**
	 * The grade named in a title marker — "(Brons)", "(SILWER)", "(Goud)" — or null. Pure.
	 *
	 * Used only for the cross-check report ("title says Goud, no result post confirms").
	 *
	 * @param string $title The post title.
	 */
	public static function titleMarker( string $title ): ?string {
		if ( 1 === preg_match( '/\(\s*(brons|silwer|goud)\s*\)/iu', $title, $m ) ) {
			return strtolower( $m[1] );
		}

		return null;
	}
}
