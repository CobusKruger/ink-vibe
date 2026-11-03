<?php
/**
 * The `/kompetisies/` dark hero — WP5 (`05-kompetisies` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * `ink/kompetisies-hero`, from `Competitions.tsx:73`. Dark is the only difference from the
 * standard elements (owner). Hidden with no current edition. The Enter button goes to
 * `/skryf/` (ruled); the guidelines button has no target, as Lovable's.
 *
 * @package Ink\Core
 */
final class Hero {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-hero';

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
	 * The hero. Pure.
	 *
	 * @param Edition $edition The current edition.
	 * @param string  $enter   The Enter button's URL.
	 * @return string
	 */
	public static function toHtml( Edition $edition, string $enter ): string {
		$badge = __( '[NEEDS HUMAN AFRIKAANS] KOMP-HERO-BADGE', 'ink-core' );
		$uit   = $edition->field( 'uitgawe' );
		$close = $edition->field( 'sluitingsdatum' );
		$note  = $edition->field( 'inskrywing_nota' );

		$pills = '';

		if ( '' !== $close ) {
			$pills .= '<span class="ink-pill ink-pill--hint ink-pill--on-ink"' . Markup::aid( 'competitions-hero-meta-close' ) . '>'
				. Markup::icon( Markup::CALENDAR, 'competitions-hero-meta-close-icon' )
				. esc_html( __( '[NEEDS HUMAN AFRIKAANS] KOMP-HERO-SLUIT', 'ink-core' ) . ' ' . Markup::date( $close ) )
				. '</span>';
		}

		if ( '' !== $note ) {
			$pills .= '<span class="ink-pill ink-pill--hint ink-pill--on-ink"' . Markup::aid( 'competitions-hero-meta-entry' ) . '>'
				. Markup::icon( Markup::FEATHER, 'competitions-hero-meta-entry-icon' )
				. esc_html( $note )
				. '</span>';
		}

		return '<section class="ink-band ink-band--dark ink-band--flush ink-band--no-border"' . Markup::aid( 'competitions-hero' ) . '>'
			. '<div class="ink-texture ink-texture--on-ink"' . Markup::aid( 'competitions-hero-texture' ) . '></div>'
			. '<div class="ink-container ink-band--step"' . Markup::aid( 'competitions-hero-container' ) . '>'
			. '<div class="ink-komp-held"' . Markup::aid( 'competitions-hero-content' ) . '>'
			. '<span class="ink-komp-held__kenteken ink-pill ink-pill--tint-lg ink-pill--gold-caps"' . Markup::aid( 'competitions-hero-badge' ) . '>'
			. Markup::icon( Markup::TROPHY, 'competitions-hero-badge-icon' )
			. esc_html( ( '' === $uit ? '' : $uit . ' · ' ) . $badge )
			. '</span>'
			. '<h1 class="ink-komp-held__titel ink-heading-h1-index ink-heading--on-ink"' . Markup::aid( 'competitions-hero-h1' ) . '>' . esc_html( $edition->title ) . '</h1>'
			. '<p class="ink-komp-held__teks ink-text--on-ink"' . Markup::aid( 'competitions-hero-text' ) . '>' . esc_html( $edition->field( 'beskrywing' ) ) . '</p>'
			. '<div class="ink-komp-held__meta"' . Markup::aid( 'competitions-hero-meta' ) . '>' . $pills . '</div>'
			. '<div class="ink-komp-held__aksies"' . Markup::aid( 'competitions-hero-actions' ) . '>'
			. '<a class="ink-btn ink-btn--lg ink-btn--primary"' . Markup::aid( 'competitions-hero-btn-primary' ) . ' href="' . esc_url( $enter ) . '">'
			. esc_html__( '[NEEDS HUMAN AFRIKAANS] KOMP-HERO-INSKRYF', 'ink-core' )
			. Markup::icon( Markup::ARROW_RIGHT, 'competitions-hero-btn-primary-icon', 'ink-btn__icon--end' )
			. '</a>'
			. '<button type="button" class="ink-btn ink-btn--lg ink-btn--ghost-on-ink"' . Markup::aid( 'competitions-hero-btn-guidelines' ) . '>'
			. esc_html__( '[NEEDS HUMAN AFRIKAANS] KOMP-HERO-RIGLYNE', 'ink-core' )
			. '</button>'
			. '</div></div></div></section>';
	}
}
