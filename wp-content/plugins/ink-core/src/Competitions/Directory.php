<?php
/**
 * The `/kompetisies/` directory and submit box — WP5 (`05-kompetisies` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * `ink/kompetisies-gids`, from `Competitions.tsx:280`. Search and the chips run client-side
 * in `kompetisies.js`; without JS every listing shows.
 *
 * The card title has no hover recipe here. Lovable turns it primary on card hover
 * (`group-hover:text-terracotta`), which is a reported open item, not a definition to invent.
 *
 * @package Ink\Core
 */
final class Directory {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-gids';

	/**
	 * How many listings carry audit ids: Lovable has nine.
	 *
	 * @var int
	 */
	private const STAMPED_LISTINGS = 9;

	/**
	 * Genre chips: the stored value, its audit-id word.
	 *
	 * @var array<string, string>
	 */
	private const GENRE_IDS = array(
		'gedigte'   => 'poetry',
		'fiksie'    => 'fiction',
		'niefiksie' => 'non-fiction',
		'gemeng'    => 'mixed',
	);

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		return self::toHtml( Data::listings() );
	}

	/**
	 * A genre's copy-debt label. Pure.
	 *
	 * @param string $genre The stored genre.
	 * @return string
	 */
	private static function genreLabel( string $genre ): string {
		$labels = array(
			'gedigte'   => __( 'Poësie', 'ink-core' ),
			'fiksie'    => __( 'Fiksie', 'ink-core' ),
			'niefiksie' => __( 'Nie-fiksie', 'ink-core' ),
			'gemeng'    => __( 'Gemeng', 'ink-core' ),
		);

		return $labels[ $genre ] ?? '';
	}

	/**
	 * The section. Pure.
	 *
	 * @param list<Listing> $listings The published listings.
	 * @return string
	 */
	public static function toHtml( array $listings ): string {
		$cards = '';

		foreach ( $listings as $i => $listing ) {
			$cards .= self::card( $listing, $i );
		}

		$one  = __( 'kompetisies', 'ink-core' );
		$none = array() === $listings;

		return '<section class="ink-container ink-band--poem" data-ink-komp-gids' . Markup::aid( 'competitions-directory' ) . '>'
			. '<div class="ink-komp-gids__kop"' . Markup::aid( 'competitions-directory-head' ) . '>'
			. '<span class="ink-komp-gids__kicker ink-eyebrow ink-eyebrow--kicker"' . Markup::aid( 'competitions-directory-eyebrow' ) . '>'
			. Markup::icon( Markup::SPARKLES, 'competitions-directory-eyebrow-icon' )
			. '<span' . Markup::aid( 'competitions-directory-eyebrow-text' ) . '>' . esc_html__( 'Skryf in Afrikaans', 'ink-core' ) . '</span>'
			. '</span>'
			. '<h2 class="ink-komp-gids__titel ink-heading-h2-section"' . Markup::aid( 'competitions-directory-h2' ) . '>' . esc_html__( 'Ander Kompetisies', 'ink-core' ) . '</h2>'
			. '<p class="ink-komp-gids__teks"' . Markup::aid( 'competitions-directory-text' ) . '>' . esc_html__( 'Ink onderhou hierdie lys van ander skryfkompetisies, maar is nie self met hierdie kompetisies geassosieer nie. Kontak die instansies wat die onderskeie kompetisies aanbied direk vir die reëls en enige ander navrae.', 'ink-core' ) . '</p>'
			. '</div>'
			. self::controls()
			. self::emptyState( $none )
			. '<p class="ink-komp-gids__telling" data-ink-telling="' . esc_attr( $one ) . '"' . ( $none ? ' hidden' . Markup::stashedAid( 'competitions-external-count' ) : Markup::aid( 'competitions-external-count' ) ) . '>' . esc_html( count( $listings ) . ' ' . $one ) . '</p>'
			. '<div class="ink-komp-gids__rooster"' . ( $none ? ' hidden' . Markup::stashedAid( 'competitions-external-grid' ) : Markup::aid( 'competitions-external-grid' ) ) . '>' . $cards . '</div>'
			. '</section>';
	}

	/**
	 * Search and the chips. Pure.
	 *
	 * @return string
	 */
	private static function controls(): string {
		$placeholder = __( 'Soek vir kompetisies', 'ink-core' );
		$genres      = self::chip( 'genre', 'alles', __( 'Alles', 'ink-core' ), 'competitions-directory-genre-all', true );

		foreach ( self::GENRE_IDS as $genre => $word ) {
			$genres .= self::chip( 'genre', $genre, self::genreLabel( $genre ), "competitions-directory-genre-{$word}", false );
		}

		return '<div class="ink-komp-gids__kontroles"' . Markup::aid( 'competitions-directory-controls' ) . '>'
			. '<div class="ink-komp-gids__soek"' . Markup::aid( 'competitions-directory-search' ) . '>'
			. Markup::icon( Markup::SEARCH, 'competitions-directory-search-icon' )
			. '<input type="search" class="ink-search-field ink-search-field--card" data-ink-soek placeholder="' . esc_attr( $placeholder ) . '" aria-label="' . esc_attr( $placeholder ) . '"' . Markup::aid( 'competitions-directory-search-input' ) . ' />'
			. '</div>'
			. '<div class="ink-komp-gids__chips"' . Markup::aid( 'competitions-directory-filters-genre' ) . '>' . $genres . '</div>'
			. '</div>';
	}

	/**
	 * One filter chip. Pure.
	 *
	 * @param string $group  `genre` or `fooi`.
	 * @param string $value  The chip's value.
	 * @param string $label  Its label.
	 * @param string $audit  Its audit id.
	 * @param bool   $active Whether it starts pressed.
	 * @return string
	 */
	private static function chip( string $group, string $value, string $label, string $audit, bool $active ): string {
		return '<button type="button" class="ink-chip ink-chip--plain" aria-pressed="' . ( $active ? 'true' : 'false' ) . '" data-ink-filter="' . esc_attr( $group ) . '" data-ink-waarde="' . esc_attr( $value ) . '"' . Markup::aid( $audit ) . '>' . esc_html( $label ) . '</button>';
	}

	/**
	 * The empty state: shown when nothing matches; with no listing at all, no clear button
	 * (§4). Pure.
	 *
	 * @param bool $none Whether there are no listings at all.
	 * @return string
	 */
	private static function emptyState( bool $none ): string {
		$stamp = static fn ( string $id ): string => $none ? Markup::aid( $id ) : Markup::stashedAid( $id );

		return '<div class="ink-komp-gids__leeg ink-card--empty ink-card--empty-lg" data-ink-leeg' . ( $none ? '' : ' hidden' ) . $stamp( 'competitions-external-empty' ) . '>'
			. '<p class="ink-komp-gids__leeg-titel"' . $stamp( 'competitions-external-empty-title' ) . '>' . esc_html__( 'Geen kompetisies gevind nie.', 'ink-core' ) . '</p>'
			. '<p class="ink-komp-gids__leeg-teks"' . $stamp( 'competitions-external-empty-text' ) . '>' . esc_html__( 'Vee die filters uit om vir iets meer algemeen te soek.', 'ink-core' ) . '</p>'
			. ( $none ? '' : '<button type="button" class="ink-komp-gids__leeg-knop ink-btn ink-btn--outline" data-ink-vee-uit' . $stamp( 'competitions-external-empty-btn' ) . '>' . esc_html__( 'Vee filters uit', 'ink-core' ) . '</button>' )
			. '</div>';
	}

	/**
	 * One listing. Ids follow Lovable's first nine; later cards are real data and carry none. Pure.
	 *
	 * @param Listing $listing The listing.
	 * @param int     $i       Its position.
	 * @return string
	 */
	private static function card( Listing $listing, int $i ): string {
		$id   = "competitions-external-{$i}";
		$aid  = static fn ( string $suffix ): string => $i < self::STAMPED_LISTINGS ? Markup::aid( $id . $suffix ) : '';
		$icon = static fn ( string $paths, string $suffix ): string => Markup::icon( $paths, $i < self::STAMPED_LISTINGS ? $id . $suffix : '' );

		$org    = trim( $listing->field( 'organiseerder' ) . ' · ' . $listing->field( 'streek' ), ' ·' );
		$note   = $listing->field( 'nota' );
		$search = strtolower( implode( ' ', array( $listing->title, $listing->field( 'organiseerder' ), $listing->field( 'streek' ), $note ) ) );

		return '<div class="ink-card ink-card--flush ink-card--lift" data-genre="' . esc_attr( $listing->genre() ) . '" data-soek="' . esc_attr( $search ) . '"'
			. ( $i < self::STAMPED_LISTINGS ? Markup::aid( 0 === $i ? 'competitions-external' : $id ) : '' ) . '>'
			. '<div class="ink-card__body ink-komp-kaart__lyf"' . $aid( '-body' ) . '>'
			. '<div class="ink-komp-gids__kaart-kop"' . $aid( '-head' ) . '>'
			. '<div class="ink-pill ink-pill--outline"' . $aid( '-genre' ) . '>' . esc_html( self::genreLabel( $listing->genre() ) ) . '</div>'
			. '</div>'
			. '<h3 class="ink-komp-gids__kaart-titel ink-heading-h3-card"' . $aid( '-title' ) . '>' . esc_html( $listing->title ) . '</h3>'
			. '<p class="ink-komp-gids__org"' . $aid( '-org' ) . '>' . esc_html( $org ) . '</p>'
			. ( '' === $note ? '' : '<p class="ink-komp-gids__nota"' . $aid( '-note' ) . '>' . esc_html( $note ) . '</p>' )
			. '<div class="ink-komp-gids__meta"' . $aid( '-meta' ) . '>'
			. '<div class="ink-meta ink-meta--sm ink-meta--sm-wide"' . $aid( '-deadline' ) . '>' . $icon( Markup::CLOCK, '-deadline-icon' ) . esc_html( __( 'Sluit', 'ink-core' ) . ' ' . $listing->field( 'sluitingsdatum' ) ) . '</div>'
			. '<div class="ink-meta ink-meta--sm ink-meta--sm-wide"' . $aid( '-prize' ) . '>' . $icon( Markup::TROPHY, '-prize-icon' ) . esc_html( $listing->field( 'prys' ) ) . '</div>'
			. '</div>'
			. ( '' === $listing->field( 'url' ) ? '' : '<a class="ink-komp-gids__skakel ink-link--medium"' . $aid( '-link' ) . ' href="' . esc_url( $listing->field( 'url' ) ) . '" target="_blank" rel="noopener noreferrer">'
				. esc_html__( 'Besoek die organiseerder', 'ink-core' ) . $icon( Markup::EXTERNAL, '-link-icon' ) . '</a>' )
			. '</div></div>';
	}
}
