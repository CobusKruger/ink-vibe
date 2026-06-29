<?php
/**
 * Content-report reason enum — Story 18.4 (§8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Report reason — the fixed value set for a content report (Story 18.4).
 *
 * The backing string is the persisted DB value (lowercase Afrikaans); never
 * duplicate these literals across the codebase. A value type only — no logic.
 *
 * @package Ink\Core
 */
enum ReportReason: string {

	case Kwetsend = 'kwetsend';
	case Spam     = 'spam';
	case Plagiaat = 'plagiaat';
	case Ander    = 'ander';

	/**
	 * The backing values, in declaration order — the single source for the form
	 * `<select>` options + write-path validation.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array_map( static fn ( self $reason ): string => $reason->value, self::cases() );
	}

	/**
	 * The Afrikaans label for a reason (translatable).
	 */
	public function label(): string {
		return match ( $this ) {
			self::Kwetsend => __( 'Kwetsend of aanstootlik', 'ink-core' ),
			self::Spam     => __( 'Strooipos', 'ink-core' ),
			self::Plagiaat => __( 'Plagiaat', 'ink-core' ),
			self::Ander    => __( 'Ander', 'ink-core' ),
		};
	}
}
