<?php
/**
 * Small render helpers for the `/kompetisies/` blocks — WP5 (N4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * Audit-id attributes, icons and dates. Pure except {@see date()}'s guarded `wp_date`.
 *
 * @package Ink\Core
 */
final class Markup {

	public const ARROW_RIGHT = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';
	public const BOOK        = '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>';
	public const CALENDAR    = '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>';
	public const CLOCK       = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
	public const EXTERNAL    = '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>';
	public const FEATHER     = '<path d="M12.67 19a2 2 0 0 0 1.416-.588l6.154-6.172a6 6 0 0 0-8.49-8.49L5.586 9.914A2 2 0 0 0 5 11.328V18a1 1 0 0 0 1 1z"/><path d="M16 8 2 22"/><path d="M17.5 15H9"/>';
	public const MAIL        = '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>';
	public const MAP_PIN     = '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>';
	public const PEN         = '<path d="M15.707 21.293a1 1 0 0 1-1.414 0l-1.586-1.586a1 1 0 0 1 0-1.414l5.586-5.586a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 1 0 1.414z"/><path d="m18 13-1.375-6.874a1 1 0 0 0-.746-.776L3.235 2.028a1 1 0 0 0-1.207 1.207L5.35 15.879a1 1 0 0 0 .776.746L13 18"/><path d="m2.3 2.3 7.286 7.286"/><circle cx="11" cy="11" r="2"/>';
	public const SEARCH      = '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>';
	public const SHIRT       = '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>';
	public const SPARKLES    = '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>';
	public const TICKET      = '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>';
	public const TROPHY      = '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>';

	/**
	 * A ` data-audit-id` attribute. Pure.
	 *
	 * @param string $id The id.
	 * @return string
	 */
	public static function aid( string $id ): string {
		return ' data-audit-id="' . esc_attr( $id ) . '"';
	}

	/**
	 * A stashed audit id, for an element that starts hidden: `kompetisies.js` turns it into
	 * the real attribute when the element shows. A hidden node must not carry the id, or the
	 * capture pairs it. Pure.
	 *
	 * @param string $id The id.
	 * @return string
	 */
	public static function stashedAid( string $id ): string {
		return ' data-ink-aid="' . esc_attr( $id ) . '"';
	}

	/**
	 * A decorative inline Lucide icon: currentColor, aria-hidden. `$paths` is one of this
	 * class's literals, never user input. Pure.
	 *
	 * @param string $paths The inner SVG markup.
	 * @param string $audit The audit id; '' stamps none.
	 * @param string $extra Extra class, a trusted literal: a margin option from primitives.css.
	 * @return string
	 */
	public static function icon( string $paths, string $audit = '', string $extra = '' ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon' . ( '' === $extra ? '' : ' ' . $extra ) . '" aria-hidden="true" focusable="false"' . ( '' === $audit ? '' : self::aid( $audit ) ) . '>' . $paths . '</svg>';
	}

	/**
	 * A stored `Y-m-d` date for display, or the text as stored when it isn't one.
	 *
	 * @param string $date The stored date.
	 * @return string
	 */
	public static function date( string $date ): string {
		$timestamp = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? strtotime( $date . ' 12:00:00 UTC' ) : false;

		if ( false === $timestamp ) {
			return $date;
		}

		$formatted = function_exists( 'wp_date' ) ? wp_date( 'j F Y', $timestamp ) : false;

		return is_string( $formatted ) ? $formatted : gmdate( 'Y-m-d', $timestamp );
	}
}
