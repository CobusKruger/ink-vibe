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
 * `ink/kompetisies-gala`, from `Competitions.tsx:167`. Details on the left, "Evenings past" on
 * the right half (owner, 2026-11-08). The band shows whenever there is a gala to talk about:
 *
 *  - **A gala is planned** (the current edition has a gala name and no photos yet): its details,
 *    tickets and Reserve button.
 *  - **Otherwise** the latest past gala's details, labelled "Die vorige gala" with its year, and
 *    no tickets or Reserve button. So the section stays up with no open competition and no new
 *    gala booked.
 *
 * The band hides only when there is neither (§4), and always when the current edition is a
 * `vennoot` competition, which has no gala (K3).
 *
 * DIVERGENCE (accepted, owner 2026-11-01, `owner-rulings.md`): all gala years show, in a chip
 * row that slides sideways and never wraps; Lovable has three years. Real gala photos only
 * (permitted #4). Reserve has no target and ticket prices are fictional (D3, D4).
 *
 * DIVERGENCE (owner, 2026-11-08): the grid is one wide photo and four small ones (five, picked
 * and captioned by an editor); Lovable's three small ones leave a gap.
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
	 * Photos a year shows here: one wide and four small, the editor's five feature photos. The
	 * rest are on its {@see GalaPage} (owner ruling 2026-11-02).
	 *
	 * @var int
	 */
	public const PREVIEW = 5;

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$editions = Data::editions();
		$galas    = Data::pickPastGalas( $editions );
		$photos   = array();

		foreach ( $galas as $gala ) {
			foreach ( array_slice( $gala->featured, 0, self::PREVIEW ) as $pick ) {
				$url = wp_get_attachment_image_url( $pick['id'], 'large' );

				if ( is_string( $url ) ) {
					$photos[ $gala->year ][] = array(
						'url'     => $url,
						'caption' => '' !== $pick['caption'] ? $pick['caption'] : (string) wp_get_attachment_caption( $pick['id'] ),
					);
				}
			}
		}

		return self::toHtml( Data::pickCurrent( $editions ), $galas, $photos, home_url( '/' ) );
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
		// A `vennoot` competition has no gala (K3): no band, no planned gala, no evenings past.
		if ( null !== $edition && ! $edition->type()->hasGala() ) {
			return '';
		}

		// Any state: a closed annual edition keeps its planned gala (tickets, Reserve) until its photos exist.
		if ( null !== $edition && $edition->hasGala() && ! $edition->hasPastGala() ) {
			$details = self::details( $edition, 0 );
		} elseif ( array() !== $galas ) {
			$details = self::details( $galas[0]->edition ?? new Edition( 0, '', array() ), $galas[0]->year );
		} else {
			$details = '';
		}

		$past = array() === $galas ? '' : self::past( $galas, $photos, $home );

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
	 * @param Edition $edition  The edition the gala belongs to.
	 * @param int     $pastYear The year of a gala that already happened, or 0 for a planned one:
	 *                          a past gala is labelled as such and has no tickets or Reserve button.
	 * @return string
	 */
	private static function details( Edition $edition, int $pastYear ): string {
		$name  = $edition->field( 'gala_naam' );
		$name  = '' !== $name ? $name : ( '' !== $edition->title ? $edition->title : sprintf(
			/* translators: %d: the gala year. */
			__( 'Ink se Presige Gala-aand %d', 'ink-core' ),
			$pastYear
		) );
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

		// A gala that already happened sells nothing.
		foreach ( $pastYear > 0 ? array() : $edition->tickets() as $n => $ticket ) {
			$id       = "competitions-gala-ticket-{$n}";
			$tickets .= '<span class="ink-pill ink-pill--hint ink-pill--ticket"' . Markup::aid( $id ) . '>'
				. Markup::icon( Markup::TICKET, "{$id}-icon" )
				. esc_html( $ticket['label'] ) . ':&nbsp;'
				. '<span class="ink-komp-gala__prys"' . Markup::aid( "{$id}-price" ) . '>' . esc_html( $ticket['price'] ) . '</span>'
				. '</span>';
		}

		$kicker = $pastYear > 0
			/* translators: %d: the year of the gala that already happened. */
			? sprintf( __( 'Die vorige gala · %d', 'ink-core' ), $pastYear )
			: __( 'Een spesiale aand per jaar', 'ink-core' );
		$about  = $edition->field( 'gala_beskrywing' );

		return '<div' . Markup::aid( 'competitions-gala-details' ) . '>'
			. '<span class="ink-komp-gala__kicker ink-eyebrow ink-eyebrow--kicker"' . Markup::aid( 'competitions-gala-eyebrow' ) . '>'
			. Markup::icon( Markup::TICKET, 'competitions-gala-eyebrow-icon' )
			. '<span' . Markup::aid( 'competitions-gala-eyebrow-text' ) . '>' . esc_html( $kicker ) . '</span>'
			. '</span>'
			. '<h2 class="ink-komp-gala__titel ink-heading-h2-section"' . Markup::aid( 'competitions-gala-h2' ) . '>' . esc_html( $name ) . '</h2>'
			. ( '' === $about ? '' : '<p class="ink-komp-gala__teks"' . Markup::aid( 'competitions-gala-text' ) . '>' . esc_html( $about ) . '</p>' )
			. ( '' === $info ? '' : '<div class="ink-komp-gala__info"' . Markup::aid( 'competitions-gala-info' ) . '>' . $info . '</div>' )
			. ( '' === $programme ? '' : '<div class="ink-komp-gala__program ink-card--feature ink-card--static"' . Markup::aid( 'competitions-gala-programme' ) . '>'
				. '<p class="ink-komp-gala__program-etiket ink-eyebrow ink-eyebrow--caps ink-eyebrow--muted"' . Markup::aid( 'competitions-gala-programme-label' ) . '>' . esc_html__( 'Die Program', 'ink-core' ) . '</p>'
				. '<ul class="ink-komp-gala__program-lys"' . Markup::aid( 'competitions-gala-programme-list' ) . '>' . $programme . '</ul>'
				. '</div>' )
			. ( '' === $tickets ? '' : '<div class="ink-komp-gala__kaartjies"' . Markup::aid( 'competitions-gala-tickets' ) . '>' . $tickets . '</div>' )
			. ( $pastYear > 0 ? '' : '<button type="button" class="ink-btn ink-btn--lg ink-btn--primary"' . Markup::aid( 'competitions-gala-btn-reserve' ) . '>'
				. Markup::icon( Markup::TICKET, 'competitions-gala-btn-reserve-icon', 'ink-btn__icon--start' )
				. esc_html__( 'Bespreek nou', 'ink-core' )
				. '</button>' )
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
	 * The link from a year to its results (K5), only for a year with published results. Standard
	 * link recipe like {@see self::seeAll()}; PROVISIONAL (undesigned, no audit id): see
	 * {@see UitslaePage}.
	 *
	 * @param int    $year The year.
	 * @param string $home The site's home URL.
	 * @return string
	 */
	private static function resultsLink( int $year, string $home ): string {
		/* translators: %d: the competition year. */
		$label = sprintf( __( 'Uitslae %d', 'ink-core' ), $year );

		return '<a class="ink-komp-gala__alles ink-link--ink" href="' . esc_url( rtrim( $home, '/' ) . UitslaePage::path( $year ) ) . '">'
			. esc_html( $label )
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
			$shown  = array_slice( $photos[ $gala->year ] ?? array(), 0, self::PREVIEW );

			$chips .= '<button type="button" class="ink-chip ink-chip--plain" aria-pressed="' . ( $active ? 'true' : 'false' ) . '" data-ink-gala-jaar="' . esc_attr( (string) $gala->year ) . '"' . Markup::aid( "competitions-gala-past-year-{$gala->year}" ) . '>' . esc_html( (string) $gala->year ) . '</button>';

			foreach ( $shown as $n => $photo ) {
				$id    = "competitions-gala-past-photo-{$n}";
				$figs .= '<figure class="ink-komp-gala__foto' . ( 0 === $n ? ' ink-komp-gala__foto--breed' : '' ) . '"' . $stamp( $id ) . '>'
					. '<img class="ink-photo"' . $stamp( "{$id}-img" ) . ' src="' . esc_url( $photo['url'] ) . '" alt="' . esc_attr( $photo['caption'] ) . '" loading="lazy" />'
					. ( '' === $photo['caption'] ? '' : '<figcaption class="ink-komp-gala__onderskrif"' . $stamp( "{$id}-caption" ) . '>' . esc_html( $photo['caption'] ) . '</figcaption>' )
					. '</figure>';
			}

			$panels .= '<div class="ink-komp-gala__jaar" data-ink-gala-paneel="' . esc_attr( (string) $gala->year ) . '"' . ( $active ? '' : ' hidden' ) . '>'
				. ( '' === $gala->highlight ? '' : '<p class="ink-komp-gala__hoogtepunt"' . $stamp( 'competitions-gala-past-highlight' ) . '>' . esc_html( '"' . $gala->highlight . '"' ) . '</p>' )
				. ( '' === $figs ? '' : '<div class="ink-komp-gala__fotos"' . $stamp( 'competitions-gala-past-grid' ) . '>' . $figs . '</div>' )
				. ( count( $gala->photoIds ) > count( $shown ) ? self::seeAll( $gala->year, $home, $stamp( 'competitions-gala-past-all' ) ) : '' )
				. ( $gala->hasResults() ? self::resultsLink( $gala->year, $home ) : '' )
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
