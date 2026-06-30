<?php
/**
 * Challenge deadline parse/format helper — Story 12.2 (shared by 12.1/12.2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Kernel\Sast;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for reading + displaying a stored `uitdaging` deadline.
 *
 * The deadline is stored (Story 6.6 {@see \Ink\Submission\ChallengeLinking}, Epic 2
 * {@see \Ink\Content\FieldSets}) as a DATE-ONLY `Y-m-d` string in SAST — the time of
 * day never matters because the boundary supplies it: an entry is accepted any time
 * through 23:59:59 SAST on the deadline's calendar day ({@see Sast::endOfDay()}). The
 * parser still tolerates a legacy `Y-m-d[ T]H:i(:s)` instant so old data reads back,
 * but {@see \Ink\Content\FieldSets::sanitizeDate()} now persists only the date part.
 * Both the single-page ({@see SinglePage}) and the list page ({@see Archive}) read it;
 * this helper keeps the parse shape and the localised display format in one place
 * rather than duplicated per surface. The comparison boundary itself lives in
 * {@see Sast} (the real single source for end-of-day-SAST); this helper only converts
 * the stored string and formats for display.
 *
 * @package Ink\Core
 */
final class Deadline {

	/**
	 * Parse a stored deadline string into a SAST instant. Pure.
	 *
	 * @param string $raw The stored `Y-m-d[ T]H:i(:s)` deadline string.
	 * @return \DateTimeImmutable|null The parsed instant, or null when empty/invalid.
	 */
	public static function parse( string $raw ): ?\DateTimeImmutable {
		if ( '' === $raw ) {
			return null;
		}

		try {
			return new \DateTimeImmutable( $raw, new \DateTimeZone( Sast::TIMEZONE ) );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Format a deadline for display, localised to the site (Afrikaans) when WordPress
	 * is loaded; a stable `Y-m-d` fallback keeps the unit path deterministic.
	 *
	 * @param \DateTimeImmutable $deadline The deadline instant.
	 * @return string
	 */
	public static function format( \DateTimeImmutable $deadline ): string {
		$sast = $deadline->setTimezone( new \DateTimeZone( Sast::TIMEZONE ) );

		if ( function_exists( 'wp_date' ) ) {
			return (string) wp_date( 'j F Y', $sast->getTimestamp() );
		}

		return $sast->format( 'Y-m-d' );
	}
}
