<?php
/**
 * Content-report target-kind enum — Story 18.4 (§8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Report target — what kind of thing a report points at (Story 18.4).
 *
 * `werk` = a published work (gedig/storie/artikel); `resensie` = a reader review
 * (Story 9.6); `reaksie` = a community response (Gemeenskapsreaksie, Story 7.4).
 * The backing string is the persisted DB value; never duplicate these literals.
 *
 * @package Ink\Core
 */
enum ReportTarget: string {

	case Werk     = 'werk';
	case Resensie = 'resensie';
	case Reaksie  = 'reaksie';

	/**
	 * The backing values, in declaration order.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array_map( static fn ( self $target ): string => $target->value, self::cases() );
	}
}
