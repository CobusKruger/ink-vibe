<?php
/**
 * "Wie ek volg" (following-list) server block — §5.6 My Profiel rebuild.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Discovery\SkrywersTab;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/volg-lys` block: the card grid of writers the current
 * member follows (the My Profiel "Wie ek volg" tab).
 *
 * Distinct from {@see FollowingFeed} — that block lists the followed writers'
 * *works*; this one lists the followed *writers themselves* (avatar, name,
 * italic bio, unfollow button). Reads followee ids through
 * {@see Api::followeeIdsFor()} (the 9.2 facade), resolves each to a
 * `WP_User`, and reuses {@see FollowToggle::toHtml()} verbatim for the
 * unfollow control — this block does NOT register a second follow/unfollow
 * REST endpoint; the existing `ink/v1/volg` route (via `FollowController` +
 * `volg.js`) handles the click.
 *
 * Each card is wrapped with `data-ink-remove-on-unfollow` so `volg.js`'s
 * existing (previously dead) unfollow-row-removal logic runs against real
 * DOM: on a successful unfollow the row is dropped from view entirely, since
 * a following-only list must never leave a stale "not following" row behind.
 *
 * Conflation-clean: reads `Ink\Social\Api` (own module) + WP core only — zero
 * `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class FollowingList {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/volg-lys';

	/**
	 * Register the server-rendered block.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so `registerBlock()` is called DIRECTLY here rather than nesting
	 * a second `add_action( 'init', … )` from within the running `init` hook.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/volg-lys` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Block render callback (logged-in members only).
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$followee_ids = Api::followeeIdsFor( get_current_user_id() );

		$cards = array();

		foreach ( $followee_ids as $followee_id ) {
			if ( ! get_userdata( $followee_id ) instanceof \WP_User ) {
				continue;
			}

			// The shared writer card (same data + markup as the Ontdek tab).
			$cards[] = SkrywersTab::cardFor( (int) $followee_id );
		}

		return self::toHtml( $cards );
	}

	/**
	 * Build the following-list HTML. Pure — escaping only.
	 *
	 * @param list<array<string, mixed>> $writers Writer-card rows ({@see SkrywersTab::cardFor()}).
	 * @return string
	 */
	public static function toHtml( array $writers ): string {
		// Heading + intro + empty-state copy are human-authored, approved
		// Afrikaans from ui-copy-translations.md (My Profiel — Volg/Aktiwiteit,
		// lines 757/758/759/760/761). Zero AI Afrikaans.
		$heading = '<h2 class="ink-heading-h2-block ink-volg-lys__heading" data-audit-id="profiel-following-title">' . esc_html__( 'Skrywers wat jy volg', 'ink-core' ) . '</h2>';
		$intro   = '<p class="ink-volg-lys__intro" data-audit-id="profiel-following-sub">' . esc_html__( 'Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer.', 'ink-core' ) . '</p>';

		if ( array() === $writers ) {
			$empty_title = __( 'Jy volg nog niemand nie', 'ink-core' );
			$empty_body  = __( "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien.", 'ink-core' );
			$cta         = __( 'Ontdek skrywers', 'ink-core' );

			return '<section class="ink-volg-lys"><div class="ink-volg-lys__kop-teks" data-audit-id="profiel-following-head-text">' . $heading . $intro . '</div>'
				. '<div class="ink-card ink-card--empty ink-volg-lys__leeg" data-audit-id="profiel-following-empty">'
				. '<p class="ink-heading-h3 ink-volg-lys__leeg-titel" data-audit-id="profiel-following-empty-title">' . esc_html( $empty_title ) . '</p>'
				. '<p class="ink-volg-lys__leeg-teks" data-audit-id="profiel-following-empty-body">' . esc_html( $empty_body ) . '</p>'
				. '<a class="ink-btn ink-btn--outline ink-volg-lys__ontdek" data-audit-id="profiel-following-empty-btn" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html( $cta ) . '</a>'
				. '</div></section>';
		}

		// A persistent header CTA (Tier-0 structural match to Profile.tsx's
		// always-visible "Discover writers" button, not only the empty state's) —
		// reuses the SAME "Ontdek skrywers" copy/URL the empty state above already
		// carries; no new string, no new destination.
		$cta_persistent = '<a class="ink-btn ink-btn--sm ink-btn--subtle ink-volg-lys__ontdek-kop" data-audit-id="profiel-following-discover-btn" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html__( 'Ontdek skrywers', 'ink-core' ) . '</a>';

		$html = '<section class="ink-volg-lys"><div class="ink-volg-lys__kop" data-audit-id="profiel-following-head"><div class="ink-volg-lys__kop-teks" data-audit-id="profiel-following-head-text">' . $heading . $intro . '</div>' . $cta_persistent . '</div><ul class="ink-ontdek-skrywers__list" data-audit-id="profiel-following-grid">';

		// Cards are the shared Ontdek writer card; its Volg / Volg tans toggle (volg.js) stays
		// on the card, so an unfollowed writer remains listed and can be followed again.
		foreach ( array_values( $writers ) as $writer ) {
			$html .= SkrywersTab::cardHtml( $writer );
		}

		$html .= '</ul></section>';

		return $html;
	}
}
