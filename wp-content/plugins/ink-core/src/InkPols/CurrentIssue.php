<?php
/**
 * The InkPols current-issue feature, shared by the archive and the home section (`04-inkpols` §3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Returns only the grid, never a section, container or empty shell: the caller owns the
 * band. Other modules and the theme reach it through {@see Api}'s facade and this class's
 * `toHtml()` contract (`04-inkpols` §3). Pure: Terms and escaping only.
 *
 * DIVERGENCE (accepted, `inkpols.md` §2, permitted #6): covers keep the A4 ratio of the real
 * issues (recipe `ink-cover`, disclosed in `primitives.css`), where Lovable uses 3:4; and no
 * download button is ever emitted (`magazine-btn-pdf`, `magazine-current-btn-pdf`).
 *
 * @package Ink\Core
 */
final class CurrentIssue {

	public const CONTEXT_ARCHIVE = 'archive';
	public const CONTEXT_HOME    = 'home';

	public const LIST_DEFAULT = 'default';
	public const LIST_HOME    = 'home';
	public const LIST_COMPACT = 'compact';

	private const ICON_BOOK_OPEN   = '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>';
	private const ICON_ARROW_RIGHT = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * The current-issue grid.
	 *
	 * @param Issue  $issue   The issue.
	 * @param string $context {@see CONTEXT_ARCHIVE} or {@see CONTEXT_HOME}.
	 * @return string
	 */
	public static function toHtml( Issue $issue, string $context ): string {
		$home      = self::CONTEXT_HOME === $context;
		$prefix    = $home ? 'magazine' : 'magazine-current';
		$permalink = $issue->permalink();
		$cover     = self::coverImg( $issue, $home ? 'magazine-cover' : 'magazine-current-cover', 'ink-cover ink-cover--tilted' . ( $home ? ' ink-cover--home' : '' ), $home );

		$cover_col = $home
			? '<div class="ink-inkpols__omslag-kol" data-audit-id="magazine-cover-col">' . $cover . '</div>'
			: '<a class="ink-inkpols__omslag-kol" data-audit-id="magazine-current-cover-link" href="' . esc_url( $permalink ) . '">' . $cover . '</a>';

		$eyebrow = $home
			? Terms::label( 'inkpols' )
			: __( '[NEEDS HUMAN AFRIKAANS] INKPOLS-HUIDIGE', 'ink-core' );

		$list = $home
			? '<p class="ink-inkpols__binne-etiket ink-eyebrow ink-eyebrow--caps ink-eyebrow--muted" data-audit-id="magazine-inside-label">' . esc_html( Terms::label( 'in_hierdie_uitgawe' ) ) . '</p>'
				. self::listHtml( $issue, 'magazine-inside', self::LIST_HOME )
			: '<div class="ink-inkpols__lys-hou" data-audit-id="magazine-current-highlights-wrap">' . self::listHtml( $issue, 'magazine-current', self::LIST_DEFAULT ) . '</div>';

		$past = $home
			? '<a class="ink-inkpols__alle ink-link--ink" data-audit-id="magazine-past-link" href="' . esc_url( self::archiveUrl() ) . '">' . esc_html( Terms::label( 'alle_uitgawes' ) ) . self::icon( self::ICON_ARROW_RIGHT, 'magazine-past-link-icon', 'ink-link__icon--end' ) . '</a>'
			: '';

		return '<div class="ink-inkpols__huidig' . ( $home ? '' : ' ink-inkpols__huidig--argief' ) . '" data-audit-id="' . esc_attr( $home ? 'magazine-grid' : 'magazine-current' ) . '">'
			. $cover_col
			. '<div class="ink-inkpols__huidig-lyf" data-audit-id="' . esc_attr( $prefix . '-body' ) . '">'
			. '<span class="ink-eyebrow ink-eyebrow--card" data-audit-id="' . esc_attr( $prefix . '-eyebrow' ) . '">' . esc_html( $eyebrow . ' · ' . $issue->title ) . '</span>'
			. '<h2 class="ink-inkpols__huidig-titel ink-heading-h2-section" data-audit-id="' . esc_attr( $prefix . '-h2' ) . '">' . esc_html( $issue->titel ) . '</h2>'
			. '<p class="ink-inkpols__huidig-teks" data-audit-id="' . esc_attr( $prefix . '-text' ) . '">' . esc_html( $issue->teaser ) . '</p>'
			. $list
			. '<div class="ink-inkpols__aksies" data-audit-id="' . esc_attr( $home ? 'magazine-actions' : 'magazine-current-actions' ) . '">'
			. self::readButton( $permalink, 'ink-btn ink-btn--lg ink-btn--primary', $home ? 'magazine-btn-read' : 'magazine-current-btn-read' )
			. $past
			. '</div></div></div>';
	}

	/**
	 * The featured-items list, in all three variants. Pure.
	 *
	 * @param Issue  $issue    The issue.
	 * @param string $id_prefix Audit-id prefix (`magazine-current`, `magazine-past-0`, `magazine-inside`); '' stamps none.
	 * @param string $variant  One of the `LIST_*` constants.
	 * @return string
	 */
	public static function listHtml( Issue $issue, string $id_prefix, string $variant ): string {
		$home = self::LIST_HOME === $variant;
		$list = $home ? "{$id_prefix}-list" : "{$id_prefix}-highlights";
		$item = $home ? "{$id_prefix}-item" : "{$id_prefix}-highlights";
		$mod  = self::LIST_DEFAULT === $variant ? '' : ' ink-inkpols__lys--' . $variant;
		$rows = '';

		foreach ( $issue->items() as $n => $row ) {
			$rows .= '<li class="ink-inkpols__ry"' . self::aid( '' === $id_prefix ? '' : "{$item}-{$n}" ) . '>'
				. '<span class="ink-inkpols__soort ink-eyebrow ink-eyebrow--card-xs"' . self::aid( '' === $id_prefix ? '' : "{$item}-{$n}-kind" ) . '>' . esc_html( $row['tipe'] ) . '</span>'
				. '<span class="ink-inkpols__ry-titel"' . self::aid( '' === $id_prefix ? '' : "{$item}-{$n}-title" ) . '>' . esc_html( $row['title'] ) . '</span>'
				. '<span class="ink-inkpols__ry-skrywer"' . self::aid( '' === $id_prefix ? '' : "{$item}-{$n}-author" ) . '>' . esc_html( $row['writer'] ) . '</span>'
				. '</li>';
		}

		return '<ul class="ink-inkpols__lys' . esc_attr( $mod ) . '"' . self::aid( '' === $id_prefix ? '' : $list ) . '>' . $rows . '</ul>';
	}

	/**
	 * The read button, with its icon. Pure.
	 *
	 * @param string $url     The issue's URL.
	 * @param string $classes The button recipe.
	 * @param string $audit   The button's audit id; '' stamps none.
	 * @return string
	 */
	public static function readButton( string $url, string $classes, string $audit ): string {
		return '<a class="' . esc_attr( $classes ) . '"' . self::aid( $audit ) . ' href="' . esc_url( $url ) . '">'
			. self::icon( self::ICON_BOOK_OPEN, '' === $audit ? '' : $audit . '-icon', str_contains( $classes, 'ink-btn--sm' ) ? 'ink-btn__icon--start-sm' : 'ink-btn__icon--start' )
			. esc_html( Terms::label( 'lees_aanlyn' ) )
			. '</a>';
	}

	/**
	 * A cover image, or '' with no cover. Alt is the naam. Pure.
	 *
	 * @param Issue  $issue   The issue.
	 * @param string $audit   The audit id; '' stamps none.
	 * @param string $classes The cover recipe.
	 * @param bool   $lazy    Load lazily.
	 * @return string
	 */
	public static function coverImg( Issue $issue, string $audit, string $classes, bool $lazy ): string {
		$url = $issue->coverUrl();

		if ( '' === $url ) {
			return '';
		}

		return '<img class="' . esc_attr( $classes ) . '"' . self::aid( $audit ) . ' src="' . esc_url( $url ) . '" alt="' . esc_attr( $issue->title ) . '"' . ( $lazy ? ' loading="lazy"' : '' ) . ' />';
	}

	/**
	 * A decorative inline Lucide icon: 16px, currentColor, aria-hidden. `$paths` is a trusted
	 * class-internal literal, never user input. Pure.
	 *
	 * @param string $paths The inner SVG markup.
	 * @param string $audit The audit id; '' stamps none.
	 * @param string $extra Extra class, a trusted literal: a margin option from primitives.css.
	 * @return string
	 */
	public static function icon( string $paths, string $audit = '', string $extra = '' ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon' . ( '' === $extra ? '' : ' ' . $extra ) . '" aria-hidden="true" focusable="false"' . self::aid( $audit ) . '>' . $paths . '</svg>';
	}

	/**
	 * A ` data-audit-id` attribute, or '' for no id. Pure.
	 *
	 * @param string $id The id.
	 * @return string
	 */
	public static function aid( string $id ): string {
		return '' === $id ? '' : ' data-audit-id="' . esc_attr( $id ) . '"';
	}

	/**
	 * The archive's URL.
	 *
	 * @return string
	 */
	public static function archiveUrl(): string {
		return function_exists( 'home_url' ) ? (string) home_url( '/inkpols/' ) : '/inkpols/';
	}
}
