<?php
/**
 * "Wie ek volg" (following-list) server block — §5.6 My Profiel rebuild.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

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

		$writers = array();

		foreach ( $followee_ids as $followee_id ) {
			$user = get_userdata( $followee_id );

			if ( ! $user instanceof \WP_User ) {
				continue;
			}

			$writers[] = array(
				'id'     => $followee_id,
				'name'   => (string) get_the_author_meta( 'display_name', $followee_id ),
				// Same bio source as the public Skrywerprofiel card (SkrywerProfiel::render()),
				// for consistency between the two writer-card renderings.
				'bio'    => (string) get_the_author_meta( 'description', $followee_id ),
				'avatar' => function_exists( 'get_avatar' ) ? (string) get_avatar( $followee_id, 56 ) : '',
			);
		}

		return self::toHtml( $writers );
	}

	/**
	 * Build the following-list HTML. Pure — escaping only.
	 *
	 * @param list<array{id:int, name:string, bio:string, avatar:string}> $writers The followed writers.
	 * @return string
	 */
	public static function toHtml( array $writers ): string {
		// Heading + intro + empty-state copy are human-authored, approved
		// Afrikaans from ui-copy-translations.md (My Profiel — Volg/Aktiwiteit,
		// lines 757/758/759/760/761). Zero AI Afrikaans.
		$heading = '<h2 class="ink-volg-lys__heading">' . esc_html__( 'Skrywers wat jy volg', 'ink-core' ) . '</h2>';
		$intro   = '<p class="ink-volg-lys__intro">' . esc_html__( 'Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer.', 'ink-core' ) . '</p>';

		if ( array() === $writers ) {
			$empty_title = __( "Jy volg nog niemand nie", 'ink-core' );
			$empty_body  = __( "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien.", 'ink-core' );
			$cta         = __( 'Ontdek skrywers', 'ink-core' );

			return '<section class="ink-volg-lys">' . $heading . $intro
				. '<div class="ink-volg-lys__leeg">'
				. '<p class="ink-volg-lys__leeg-titel">' . esc_html( $empty_title ) . '</p>'
				. '<p class="ink-volg-lys__leeg-teks">' . esc_html( $empty_body ) . '</p>'
				. '<a class="ink-volg-lys__ontdek" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html( $cta ) . '</a>'
				. '</div></section>';
		}

		$html = '<section class="ink-volg-lys">' . $heading . $intro . '<ul class="ink-volg-lys__grid">';

		foreach ( $writers as $writer ) {
			$html .= '<li class="ink-volg-lys__item is-style-card" data-ink-remove-on-unfollow>'
				. '<div class="ink-volg-lys__foto">' . (string) $writer['avatar'] . '</div>'
				. '<span class="ink-volg-lys__naam">' . esc_html( (string) $writer['name'] ) . '</span>';

			if ( '' !== trim( (string) $writer['bio'] ) ) {
				$html .= '<p class="ink-volg-lys__bio"><em>' . esc_html( (string) $writer['bio'] ) . '</em></p>';
			}

			$html .= FollowToggle::toHtml( (int) $writer['id'], true )
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
