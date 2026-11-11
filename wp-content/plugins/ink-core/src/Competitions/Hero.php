<?php
/**
 * The `/kompetisies/` dark hero — WP5 (`05-kompetisies` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Kernel\Sast;

defined( 'ABSPATH' ) || exit;

/**
 * `ink/kompetisies-hero`, from `Competitions.tsx:73`. Dark is the only difference from the
 * standard elements (owner). Always a full hero (owner, 2026-11-08): with no competition open it
 * shows the default announcement (an editor-written `kompetisie` post marked `standaard`, else
 * {@see Edition::builtIn()}), with no pills and no buttons, because there is nothing to enter
 * yet. A real edition replaces it. The Enter button goes to `/skryf/` (ruled); the guidelines
 * button has no target, as Lovable's.
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
		$editions = Data::editions();
		$now      = Sast::now();
		$results  = Data::pickResultsHero( $editions, $now );

		return self::toHtml(
			Data::pickShown( $editions, $now ),
			(string) home_url( '/skryf/' ),
			null === $results ? '' : UitslaePage::urlFor( $results->year() ),
			null === $results ? 0 : $results->year()
		);
	}

	/**
	 * The hero. Pure.
	 *
	 * @param Edition $edition The edition to show: the current one, or the default announcement.
	 * @param string  $enter   The Enter button's URL.
	 * @param string  $results The results route, when the latest edition has published results; else ''.
	 * @param int     $year    That edition's year, for the "Nou bekend" line.
	 * @return string
	 */
	public static function toHtml( Edition $edition, string $enter, string $results = '', int $year = 0 ): string {
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

		// DIVERGENCE (owner, 2026-11-08): Lovable's badge is all caps; INK writes sentence case
		// everywhere, so `ink-pill--gold` has no text-transform.
		$badge_html = '<span class="ink-komp-held__kenteken ink-pill ink-pill--tint-lg ink-pill--gold"' . Markup::aid( 'competitions-hero-badge' ) . '>'
			. Markup::icon( Markup::TROPHY, 'competitions-hero-badge-icon' )
			. esc_html( ( '' === $uit ? '' : $uit . ' · ' ) . $badge )
			. '</span>';

		// The default announcement has no rules and no way to enter yet: no buttons.
		$actions = ! $edition->acceptsEntries() ? '' : '<div class="ink-komp-held__aksies"' . Markup::aid( 'competitions-hero-actions' ) . '>'
			. '<a class="ink-btn ink-btn--lg ink-btn--primary"' . Markup::aid( 'competitions-hero-btn-primary' ) . ' href="' . esc_url( $enter ) . '">'
			. esc_html__( 'Skryf nou in', 'ink-core' )
			. Markup::icon( Markup::ARROW_RIGHT, 'competitions-hero-btn-primary-icon', 'ink-btn__icon--end' )
			. '</a>'
			. '<button type="button" class="ink-btn ink-btn--lg ink-btn--ghost-on-ink"' . Markup::aid( 'competitions-hero-btn-guidelines' ) . '>'
			. esc_html__( 'Lees die reëls', 'ink-core' )
			. '</button>'
			. '</div>';

		// PROVISIONAL (K3, 2026-11-11): Lovable has no "results" hero state. The owner's line is shown
		// as one existing primary hero button in the existing actions row (no new CSS, no audit-id)
		// below the generic announcement. The owner must ratify a design before any fidelity claim.
		if ( '' !== $results && $year > 0 ) {
			$actions .= '<div class="ink-komp-held__aksies">'
				. '<a class="ink-btn ink-btn--lg ink-btn--primary" href="' . esc_url( $results ) . '">'
				/* translators: %d: the competition year. */
				. esc_html( sprintf( __( 'Nou bekend: Die wenners van die %d Ink Kompetisie', 'ink-core' ), $year ) )
				. Markup::icon( Markup::ARROW_RIGHT, '', 'ink-btn__icon--end' )
				. '</a>'
				. '</div>';
		}

		$rest = '<p class="ink-komp-held__teks ink-text--on-ink"' . Markup::aid( 'competitions-hero-text' ) . '>' . esc_html( $edition->field( 'beskrywing' ) ) . '</p>'
			. ( '' === $pills ? '' : '<div class="ink-komp-held__meta"' . Markup::aid( 'competitions-hero-meta' ) . '>' . $pills . '</div>' )
			. $actions;

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
