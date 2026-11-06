<?php
/**
 * The `/kompetisies/` dark hero — WP5 (`05-kompetisies` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * `ink/kompetisies-hero`, from `Competitions.tsx:73`. Dark is the only difference from the
 * standard elements (owner). Always shown: with no current edition (owner, 2026-11-06) it is the
 * band with the page's name as its heading, and the badge, text, pills and buttons, which need a
 * competition, are left out. The Enter button goes to
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
		return self::toHtml( Data::current(), (string) home_url( '/skryf/' ) );
	}

	/**
	 * The hero. Pure.
	 *
	 * @param Edition|null $edition The current edition, or null.
	 * @param string       $enter   The Enter button's URL.
	 * @return string
	 */
	public static function toHtml( ?Edition $edition, string $enter ): string {
		if ( null === $edition ) {
			return self::band( esc_html( Terms::label( 'kompetisie_plural' ) ), '' );
		}

		$badge = __( 'Ons flagskip-kompetisie', 'ink-core' );
		$uit   = $edition->field( 'uitgawe' );
		$close = $edition->field( 'sluitingsdatum' );
		$note  = $edition->field( 'inskrywing_nota' );

		$pills = '';

		if ( '' !== $close ) {
			$pills .= '<span class="ink-pill ink-pill--hint ink-pill--on-ink"' . Markup::aid( 'competitions-hero-meta-close' ) . '>'
				. Markup::icon( Markup::CALENDAR, 'competitions-hero-meta-close-icon' )
				. esc_html( __( 'Sluit', 'ink-core' ) . ' ' . Markup::date( $close ) )
				. '</span>';
		}

		if ( '' !== $note ) {
			$pills .= '<span class="ink-pill ink-pill--hint ink-pill--on-ink"' . Markup::aid( 'competitions-hero-meta-entry' ) . '>'
				. Markup::icon( Markup::FEATHER, 'competitions-hero-meta-entry-icon' )
				. esc_html( $note )
				. '</span>';
		}

		$badge_html = '<span class="ink-komp-held__kenteken ink-pill ink-pill--tint-lg ink-pill--gold-caps"' . Markup::aid( 'competitions-hero-badge' ) . '>'
			. Markup::icon( Markup::TROPHY, 'competitions-hero-badge-icon' )
			. esc_html( ( '' === $uit ? '' : $uit . ' · ' ) . $badge )
			. '</span>';

		$rest = '<p class="ink-komp-held__teks ink-text--on-ink"' . Markup::aid( 'competitions-hero-text' ) . '>' . esc_html( $edition->field( 'beskrywing' ) ) . '</p>'
			. '<div class="ink-komp-held__meta"' . Markup::aid( 'competitions-hero-meta' ) . '>' . $pills . '</div>'
			. '<div class="ink-komp-held__aksies"' . Markup::aid( 'competitions-hero-actions' ) . '>'
			. '<a class="ink-btn ink-btn--lg ink-btn--primary"' . Markup::aid( 'competitions-hero-btn-primary' ) . ' href="' . esc_url( $enter ) . '">'
			. esc_html__( 'Skryf nou in', 'ink-core' )
			. Markup::icon( Markup::ARROW_RIGHT, 'competitions-hero-btn-primary-icon', 'ink-btn__icon--end' )
			. '</a>'
			. '<button type="button" class="ink-btn ink-btn--lg ink-btn--ghost-on-ink"' . Markup::aid( 'competitions-hero-btn-guidelines' ) . '>'
			. esc_html__( 'Lees die reëls', 'ink-core' )
			. '</button>'
			. '</div>';

		return self::band( esc_html( $edition->title ), $badge_html, $rest );
	}

	/**
	 * The dark band around the hero's content. Pure.
	 *
	 * @param string $title Escaped heading.
	 * @param string $badge The badge markup, or ''.
	 * @param string $rest  The text, pills and buttons markup, or ''.
	 * @return string
	 */
	private static function band( string $title, string $badge, string $rest = '' ): string {
		return '<section class="ink-band ink-band--dark ink-band--flush ink-band--no-border"' . Markup::aid( 'competitions-hero' ) . '>'
			. '<div class="ink-texture ink-texture--on-ink"' . Markup::aid( 'competitions-hero-texture' ) . '></div>'
			. '<div class="ink-container ink-band--step"' . Markup::aid( 'competitions-hero-container' ) . '>'
			. '<div class="ink-komp-held"' . Markup::aid( 'competitions-hero-content' ) . '>'
			. $badge
			. '<h1 class="ink-komp-held__titel ink-heading-h1-index ink-heading--on-ink"' . Markup::aid( 'competitions-hero-h1' ) . '>' . $title . '</h1>'
			. $rest
			. '</div></div></section>';
	}
}
