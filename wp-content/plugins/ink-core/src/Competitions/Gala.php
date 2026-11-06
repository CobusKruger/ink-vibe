<?php
/**
 * The `/kompetisies/` gala and "Evenings past" — WP5 (`05-kompetisies` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * `ink/kompetisies-gala`, from `Competitions.tsx:167`. The two columns hide separately, and
 * the band hides when both are empty (§4).
 *
 * DIVERGENCE (accepted, owner 2026-11-01, `owner-rulings.md`): all gala years show, in a chip
 * row that slides sideways and never wraps; Lovable has three years. Real gala photos only
 * (permitted #4). Reserve has no target and ticket prices are fictional (D3, D4).
 *
 * @package Ink\Core
 */
final class Gala {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-gala';

	/**
	 * Photos a year shows here; the rest are on its {@see GalaPage} (owner ruling 2026-11-02).
	 *
	 * @var int
	 */
	public const PREVIEW = 4;

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$photos = array();
		$galas  = Data::pastGalas();

		foreach ( $galas as $gala ) {
			foreach ( array_slice( $gala->photoIds, 0, self::PREVIEW ) as $id ) {
				$url = wp_get_attachment_image_url( $id, 'large' );

				if ( is_string( $url ) ) {
					$photos[ $gala->year ][] = array(
						'url'     => $url,
						'caption' => (string) wp_get_attachment_caption( $id ),
					);
				}
			}
		}

		return self::toHtml( Data::current(), $galas, $photos, home_url( '/' ) );
	}

	/**
	 * The band, or '' with nothing to show. Pure.
	 *
	 * @param Edition|null                                        $edition The current edition.
	 * @param list<GalaYear>                                      $galas   The past galas, newest first.
	 * @param array<int, list<array{url:string, caption:string}>> $photos  Resolved photos per year.
	 * @param string                                              $home    The site's home URL, for "Sien alles".
	 * @return string
	 */
	public static function toHtml( ?Edition $edition, array $galas, array $photos, string $home = '/' ): string {
		$details = null !== $edition && '' !== $edition->field( 'gala_naam' ) ? self::details( $edition ) : '';
		$past    = array() === $galas ? '' : self::past( $galas, $photos, $home );

		if ( '' === $details && '' === $past ) {
			return '';
		}

		return '<section class="ink-band ink-band--strip ink-band--flush"' . Markup::aid( 'competitions-gala' ) . '>'
			. '<div class="ink-container ink-band--poem"' . Markup::aid( 'competitions-gala-container' ) . '>'
			. '<div class="ink-komp-gala"' . Markup::aid( 'competitions-gala-grid' ) . '>' . $details . $past . '</div>'
			. '</div></section>';
	}

	/**
	 * The details column. Pure.
	 *
	 * @param Edition $edition The current edition.
	 * @return string
	 */
	private static function details( Edition $edition ): string {
		$info  = self::infoRow( 'date', Markup::CALENDAR, Markup::date( $edition->field( 'gala_datum' ) ), $edition->field( 'gala_deure' ) );
		$info .= self::infoRow( 'venue', Markup::MAP_PIN, $edition->field( 'gala_plek' ) );
		$info .= self::infoRow( 'dress', Markup::SHIRT, $edition->field( 'gala_drag' ) );

		$programme = '';

		foreach ( $edition->programme() as $n => $line ) {
			$id         = "competitions-gala-programme-item-{$n}";
			$programme .= '<li class="ink-komp-gala__program-ry"' . Markup::aid( $id ) . '>'
				. '<span class="ink-komp-gala__nommer"' . Markup::aid( "{$id}-num" ) . '>' . esc_html( ( $n + 1 ) . '.' ) . '</span>'
				. '<span class="ink-komp-gala__program-teks"' . Markup::aid( "{$id}-text" ) . '>' . esc_html( $line ) . '</span>'
				. '</li>';
		}

		$tickets = '';

		foreach ( $edition->tickets() as $n => $ticket ) {
			$id       = "competitions-gala-ticket-{$n}";
			$tickets .= '<span class="ink-pill ink-pill--hint ink-pill--ticket"' . Markup::aid( $id ) . '>'
				. Markup::icon( Markup::TICKET, "{$id}-icon" )
				. esc_html( $ticket['label'] ) . ':&nbsp;'
				. '<span class="ink-komp-gala__prys"' . Markup::aid( "{$id}-price" ) . '>' . esc_html( $ticket['price'] ) . '</span>'
				. '</span>';
		}

		return '<div' . Markup::aid( 'competitions-gala-details' ) . '>'
			. '<span class="ink-komp-gala__kicker ink-eyebrow ink-eyebrow--kicker"' . Markup::aid( 'competitions-gala-eyebrow' ) . '>'
			. Markup::icon( Markup::TICKET, 'competitions-gala-eyebrow-icon' )
			. '<span' . Markup::aid( 'competitions-gala-eyebrow-text' ) . '>' . esc_html__( 'Een spesiale aand per jaar', 'ink-core' ) . '</span>'
			. '</span>'
			. '<h2 class="ink-komp-gala__titel ink-heading-h2-section"' . Markup::aid( 'competitions-gala-h2' ) . '>' . esc_html( $edition->field( 'gala_naam' ) ) . '</h2>'
			. '<p class="ink-komp-gala__teks"' . Markup::aid( 'competitions-gala-text' ) . '>' . esc_html( $edition->field( 'gala_beskrywing' ) ) . '</p>'
			. '<div class="ink-komp-gala__info"' . Markup::aid( 'competitions-gala-info' ) . '>' . $info . '</div>'
			. ( '' === $programme ? '' : '<div class="ink-komp-gala__program ink-card--feature ink-card--static"' . Markup::aid( 'competitions-gala-programme' ) . '>'
				. '<p class="ink-komp-gala__program-etiket ink-eyebrow ink-eyebrow--caps ink-eyebrow--muted"' . Markup::aid( 'competitions-gala-programme-label' ) . '>' . esc_html__( 'Die Program', 'ink-core' ) . '</p>'
				. '<ul class="ink-komp-gala__program-lys"' . Markup::aid( 'competitions-gala-programme-list' ) . '>' . $programme . '</ul>'
				. '</div>' )
			. ( '' === $tickets ? '' : '<div class="ink-komp-gala__kaartjies"' . Markup::aid( 'competitions-gala-tickets' ) . '>' . $tickets . '</div>' )
			. '<button type="button" class="ink-btn ink-btn--lg ink-btn--primary"' . Markup::aid( 'competitions-gala-btn-reserve' ) . '>'
			. Markup::icon( Markup::TICKET, 'competitions-gala-btn-reserve-icon', 'ink-btn__icon--start' )
			. esc_html__( 'Bespreek nou', 'ink-core' )
			. '</button>'
			. '</div>';
	}

	/**
	 * One info row; '' when blank. Only the date row carries doors. Pure.
	 *
	 * @param string $key   `date`, `venue` or `dress`.
	 * @param string $icon  The icon.
	 * @param string $text  The row's text.
	 * @param string $doors The doors note, on the date row.
	 * @return string
	 */
	private static function infoRow( string $key, string $icon, string $text, string $doors = '' ): string {
		if ( '' === $text ) {
			return '';
		}

		$id = "competitions-gala-info-{$key}";

		return '<div class="ink-meta ink-meta--sm ink-meta--ink ink-meta--gap-12"' . Markup::aid( $id ) . '>'
			. Markup::icon( $icon, "{$id}-icon" )
			. '<span class="ink-komp-gala__info-teks"' . Markup::aid( "{$id}-text" ) . '>' . esc_html( $text ) . '</span>'
			. ( '' === $doors ? '' : '<span class="ink-komp-gala__deure"' . Markup::aid( 'competitions-gala-info-doors' ) . '>' . esc_html( '· ' . __( 'deure open', 'ink-core' ) . ' ' . $doors ) . '</span>' )
			. '</div>';
	}

	/**
	 * "Sien alles" (owner's copy) to the year's page of every photo.
	 *
	 * APPROVED ADDITION (owner, 2026-11-02, `owner-rulings.md` "Gala photos"): the design has
	 * no such link. It is the standard link recipe `ink-link--ink` (GAP-03-06, as InkPols'
	 * "Alle uitgawes"), with its arrow; no new primitive.
	 *
	 * @param int    $year  The year.
	 * @param string $home  The site's home URL.
	 * @param string $audit The audit-id attribute, live or stashed.
	 * @return string
	 */
	private static function seeAll( int $year, string $home, string $audit ): string {
		$url = rtrim( $home, '/' ) . GalaPage::path( $year );

		return '<a class="ink-komp-gala__alles ink-link--ink" href="' . esc_url( $url ) . '"' . $audit . '>'
			. esc_html__( 'Sien alles', 'ink-core' )
			. Markup::icon( Markup::ARROW_RIGHT, '', 'ink-link__icon--end' )
			. '</a>';
	}

	/**
	 * The "Evenings past" column. Only the active year's nodes carry audit ids, so they stay
	 * unique; `kompetisies.js` moves them with the year. Pure.
	 *
	 * @param list<GalaYear>                                      $galas  The past galas, newest first.
	 * @param array<int, list<array{url:string, caption:string}>> $photos Resolved photos per year.
	 * @param string                                              $home   The site's home URL.
	 * @return string
	 */
	private static function past( array $galas, array $photos, string $home ): string {
		$chips  = '';
		$panels = '';

		foreach ( $galas as $index => $gala ) {
			$active = 0 === $index;
			$stamp  = static fn ( string $id ): string => $active ? Markup::aid( $id ) : Markup::stashedAid( $id );
			$figs   = '';

			$chips .= '<button type="button" class="ink-chip ink-chip--plain" aria-pressed="' . ( $active ? 'true' : 'false' ) . '" data-ink-gala-jaar="' . esc_attr( (string) $gala->year ) . '"' . Markup::aid( "competitions-gala-past-year-{$gala->year}" ) . '>' . esc_html( (string) $gala->year ) . '</button>';

			foreach ( array_slice( $photos[ $gala->year ] ?? array(), 0, self::PREVIEW ) as $n => $photo ) {
				$id    = "competitions-gala-past-photo-{$n}";
				$figs .= '<figure class="ink-komp-gala__foto' . ( 0 === $n ? ' ink-komp-gala__foto--breed' : '' ) . '"' . $stamp( $id ) . '>'
					. '<img class="ink-photo"' . $stamp( "{$id}-img" ) . ' src="' . esc_url( $photo['url'] ) . '" alt="' . esc_attr( $photo['caption'] ) . '" loading="lazy" />'
					. ( '' === $photo['caption'] ? '' : '<figcaption class="ink-komp-gala__onderskrif"' . $stamp( "{$id}-caption" ) . '>' . esc_html( $photo['caption'] ) . '</figcaption>' )
					. '</figure>';
			}

			$panels .= '<div class="ink-komp-gala__jaar" data-ink-gala-paneel="' . esc_attr( (string) $gala->year ) . '"' . ( $active ? '' : ' hidden' ) . '>'
				. ( '' === $gala->highlight ? '' : '<p class="ink-komp-gala__hoogtepunt"' . $stamp( 'competitions-gala-past-highlight' ) . '>' . esc_html( '"' . $gala->highlight . '"' ) . '</p>' )
				. ( '' === $figs ? '' : '<div class="ink-komp-gala__fotos"' . $stamp( 'competitions-gala-past-grid' ) . '>' . $figs . '</div>' )
				. ( count( $gala->photoIds ) > self::PREVIEW ? self::seeAll( $gala->year, $home, $stamp( 'competitions-gala-past-all' ) ) : '' )
				. '</div>';
		}

		return '<div' . Markup::aid( 'competitions-gala-past' ) . '>'
			. '<div class="ink-card--feature-hero ink-card--static" data-ink-gala' . Markup::aid( 'competitions-gala-past-card' ) . '>'
			. '<h3 class="ink-komp-gala__verlede-titel ink-heading-h2-block"' . Markup::aid( 'competitions-gala-past-h3' ) . '>' . esc_html__( 'Vorige Gala-aande', 'ink-core' ) . '</h3>'
			. '<p class="ink-komp-gala__verlede-teks"' . Markup::aid( 'competitions-gala-past-text' ) . '>' . esc_html__( '\'n Blik terug na vorige gala-aande', 'ink-core' ) . '</p>'
			. '<div class="ink-komp-gala__jare"' . Markup::aid( 'competitions-gala-past-years' ) . '>' . $chips . '</div>'
			. $panels
			. '</div></div>';
	}
}
