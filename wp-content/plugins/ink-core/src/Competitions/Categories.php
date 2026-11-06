<?php
/**
 * The `/kompetisies/` categories — WP5 (`05-kompetisies` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * `ink/kompetisies-kategoriee`, from `Competitions.tsx:115`. Hidden with no current edition;
 * a blank slot simply doesn't come back from {@see Edition::categories()}.
 *
 * @package Ink\Core
 */
final class Categories {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-kategoriee';

	private const ICONS = array(
		'feather' => Markup::FEATHER,
		'book'    => Markup::BOOK,
		'pen'     => Markup::PEN,
	);

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$edition = Data::current();

		return null === $edition ? '' : self::toHtml( $edition, (string) home_url( '/skryf/' ) );
	}

	/**
	 * The categories. Pure.
	 *
	 * @param Edition $edition The current edition.
	 * @param string  $enter   The Enter buttons' URL.
	 * @return string
	 */
	public static function toHtml( Edition $edition, string $enter ): string {
		$intro = trim( __( 'Ons kompetisie word anoniem beoordeel.', 'ink-core' ) . ' ' . $edition->field( 'naaswenner_nota' ) );
		$cards = '';

		foreach ( $edition->categories() as $i => $category ) {
			$cards .= self::card( $i, $category, $enter );
		}

		return '<section class="ink-container ink-band--poem"' . Markup::aid( 'competitions-categories' ) . '>'
			. '<div class="ink-komp-kat__kop"' . Markup::aid( 'competitions-categories-head' ) . '>'
			. '<div' . Markup::aid( 'competitions-categories-head-text' ) . '>'
			. '<h2 class="ink-heading-h2-section"' . Markup::aid( 'competitions-categories-h2' ) . '>' . esc_html__( 'Gedigte en kortverhale, vir volwassenes en skoliere', 'ink-core' ) . '</h2>'
			. '<p class="ink-komp-kat__inlei"' . Markup::aid( 'competitions-categories-intro' ) . '>' . esc_html( $intro ) . '</p>'
			. '</div></div>'
			. '<div class="ink-komp-kat__rooster"' . Markup::aid( 'competitions-categories-grid' ) . '>' . $cards . '</div>'
			. '</section>';
	}

	/**
	 * One category card. Pure.
	 *
	 * @param int                                                                                                        $i        Its position.
	 * @param array{naam:string, ikoon:string, tema:string, beskrywing:string, woordlimiet:string, riglyne:list<string>} $category The category.
	 * @param string                                                                                                     $enter    The Enter button's URL.
	 * @return string
	 */
	private static function card( int $i, array $category, string $enter ): string {
		$id    = "competitions-category-{$i}";
		$lines = '';

		foreach ( $category['riglyne'] as $g => $line ) {
			$lines .= '<li class="ink-komp-kat__riglyn"' . Markup::aid( "{$id}-guideline-{$g}" ) . '>'
				. '<span class="ink-komp-kat__stippel"' . Markup::aid( "{$id}-guideline-{$g}-dot" ) . '>·</span>'
				. esc_html( $line )
				. '</li>';
		}

		return '<div class="ink-card ink-card--lg ink-card--flush ink-card--lift"' . Markup::aid( 0 === $i ? 'competitions-category' : $id ) . '>'
			. '<div class="ink-card__body ink-komp-kaart__lyf"' . Markup::aid( "{$id}-body" ) . '>'
			. '<div class="ink-komp-kat__ikoon"' . Markup::aid( "{$id}-icon-wrap" ) . '>'
			. '<span class="ink-icon-tile ink-icon-tile--circle-md"' . Markup::aid( "{$id}-icon-tile" ) . '>'
			. Markup::icon( self::ICONS[ $category['ikoon'] ] ?? Markup::FEATHER, "{$id}-icon" )
			. '</span></div>'
			. '<h3 class="ink-komp-kat__titel ink-heading-h2-block"' . Markup::aid( "{$id}-title" ) . '>' . esc_html( $category['naam'] ) . '</h3>'
			. '<p class="ink-komp-kat__tema ink-eyebrow ink-eyebrow--theme"' . Markup::aid( "{$id}-theme" ) . '>' . esc_html( $category['tema'] ) . '</p>'
			. '<p class="ink-komp-kat__teks"' . Markup::aid( "{$id}-desc" ) . '>' . esc_html( $category['beskrywing'] ) . '</p>'
			. ( '' === $category['woordlimiet'] ? '' : '<div class="ink-komp-kat__limiet ink-meta ink-meta--sm ink-meta--ink"' . Markup::aid( "{$id}-limit" ) . '>'
				. Markup::icon( Markup::BOOK, "{$id}-limit-icon" ) . esc_html( $category['woordlimiet'] ) . '</div>' )
			. ( '' === $lines ? '' : '<ul class="ink-komp-kat__riglyne"' . Markup::aid( "{$id}-guidelines" ) . '>' . $lines . '</ul>' )
			. '<a class="ink-btn ink-btn--outline ink-btn--block"' . Markup::aid( "{$id}-btn" ) . ' href="' . esc_url( $enter ) . '">'
			. esc_html__( 'Skryf in', 'ink-core' )
			. '</a></div></div>';
	}
}
