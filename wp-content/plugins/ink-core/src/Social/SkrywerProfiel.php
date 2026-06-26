<?php
/**
 * Public Skrywerprofiel server block — Story 9.4 (FR-40).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\I18n\Terms;
use Ink\Tiers\Api as TiersApi;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/skrywerprofiel` block: the PUBLIC author profile.
 *
 * Resolves the *queried* skrywer at render time (`get_queried_object_id()` on
 * the author template) — this MUST be a server-rendered block, not pattern PHP,
 * because a pattern's code runs at registration/`init`, before the main query
 * resolves. Renders the public card: name, avatar, bio, the Gradering badge
 * (Story 5.4), the volgeling count (Story 9.2), the Volg / Volg tans toggle
 * (Story 9.2), a reserved pinned-works slot (filled by Story 9.5) and an
 * accomplishments area.
 *
 * PUBLIC data only — it renders NO read counts and NO "wins needed" subtext
 * (those are private My Profiel surfaces, Stories 9.12 / 5.9). That separation
 * is the load-bearing FR-40 guarantee.
 *
 * The Gradering badge reads `Tiers\Api::gradingView()` for DISPLAY only (never a
 * gate — the same conflation-clean display read as Discovery→Tiers in 8.5);
 * follow data comes from `Social\Api`. Renders its own escaped HTML (the
 * Discovery/Engagement block house style).
 *
 * @package Ink\Core
 */
final class SkrywerProfiel {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/skrywerprofiel';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/skrywerprofiel` dynamic block.
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
	 * Block render callback — only on an author (skrywer) archive context.
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! ( function_exists( 'is_author' ) && is_author() ) ) {
			return '';
		}

		$author_id = (int) get_queried_object_id();

		if ( $author_id <= 0 ) {
			return '';
		}

		$profile = array(
			'name'      => (string) get_the_author_meta( 'display_name', $author_id ),
			'bio'       => (string) get_the_author_meta( 'description', $author_id ),
			'avatar'    => function_exists( 'get_avatar' ) ? (string) get_avatar( $author_id, 96 ) : '',
			'badge'     => self::graderingBadge( $author_id ),
			'volgeling' => Api::volgelingLabel( Api::followerCount( $author_id ) ),
			'volg'      => FollowToggle::render( array( 'skrywerId' => $author_id ) ),
			'pinned'    => self::pinnedCards( $author_id ),
		);

		return self::toHtml( $profile );
	}

	/**
	 * The token-only Gradering badge for a skrywer (display only, never a gate).
	 *
	 * Reads the typed display view from the Tiers facade (the same source the
	 * theme bridge uses). The grade LABEL is always rendered as text (a11y); the
	 * mark is decorative. Empty when ink-core Tiers is unavailable.
	 *
	 * @param int $author_id The skrywer.
	 * @return string
	 */
	private static function graderingBadge( int $author_id ): string {
		if ( ! class_exists( TiersApi::class ) ) {
			return '';
		}

		$view = TiersApi::gradingView( $author_id );

		return sprintf(
			'<span class="ink-gradering ink-gradering--%1$s"><span class="ink-gradering__mark" aria-hidden="true">&#9733;</span><span class="ink-gradering__label">%2$s</span></span>',
			esc_attr( $view->cssModifier() ),
			esc_html( $view->label )
		);
	}

	/**
	 * The queried author's pinned works, resolved to cards (Story 9.5).
	 *
	 * Reads {@see PinnedWorks::forUser()} (in pin = display order) and resolves
	 * each id to a card, skipping any that is no longer a published bydrae (a
	 * stale pin never renders a broken card).
	 *
	 * @param int $author_id The skrywer.
	 * @return list<array{title:string, permalink:string, type:string}>
	 */
	private static function pinnedCards( int $author_id ): array {
		$cards = array();

		foreach ( PinnedWorks::forUser( $author_id ) as $post_id ) {
			if ( 'publish' !== get_post_status( $post_id ) ) {
				continue;
			}

			$cards[] = array(
				'title'     => get_the_title( $post_id ),
				'permalink' => (string) get_permalink( $post_id ),
				'type'      => (string) get_post_type( $post_id ),
			);
		}

		return $cards;
	}

	/**
	 * Build the public profile card HTML. Pure — escaping only.
	 *
	 * Renders ONLY public data (name/bio/avatar/gradering/volgeling/volg + the
	 * pinned-works + accomplishments). It deliberately renders NO read-count and
	 * NO wins-needed subtext — those are private My Profiel surfaces (the FR-40
	 * separation).
	 *
	 * @param array{name:string, bio:string, avatar:string, badge:string, volgeling:string, volg:string, pinned?:list<array{title:string, permalink:string, type:string}>} $profile The public profile data.
	 * @return string
	 */
	public static function toHtml( array $profile ): string {
		$name      = isset( $profile['name'] ) ? (string) $profile['name'] : '';
		$bio       = isset( $profile['bio'] ) ? (string) $profile['bio'] : '';
		$avatar    = isset( $profile['avatar'] ) ? (string) $profile['avatar'] : '';
		$badge     = isset( $profile['badge'] ) ? (string) $profile['badge'] : '';
		$volgeling = isset( $profile['volgeling'] ) ? (string) $profile['volgeling'] : '';
		$volg      = isset( $profile['volg'] ) ? (string) $profile['volg'] : '';
		$pinned    = isset( $profile['pinned'] ) && is_array( $profile['pinned'] ) ? $profile['pinned'] : array();

		$html = '<section class="ink-skrywerprofiel">';

		// Header: avatar + name + gradering + volgeling count + volg toggle.
		$html .= '<header class="ink-skrywerprofiel__kop">';

		if ( '' !== $avatar ) {
			// Avatar is core-generated, already-escaped <img> markup.
			$html .= '<div class="ink-skrywerprofiel__foto">' . $avatar . '</div>';
		}

		$html .= '<div class="ink-skrywerprofiel__identiteit">'
			. '<h1 class="ink-skrywerprofiel__naam">' . esc_html( $name ) . '</h1>';

		if ( '' !== $badge ) {
			// Badge is self-built escaped markup from graderingBadge().
			$html .= '<p class="ink-skrywerprofiel__gradering">' . $badge . '</p>';
		}

		$html .= '<p class="ink-skrywerprofiel__volgelinge">' . esc_html( $volgeling ) . '</p>';

		if ( '' !== $volg ) {
			// FollowToggle::render() returns escaped button markup (or '').
			$html .= '<div class="ink-skrywerprofiel__volg">' . $volg . '</div>';
		}

		$html .= '</div></header>';

		if ( '' !== trim( $bio ) ) {
			$html .= '<div class="ink-skrywerprofiel__bio">' . esc_html( $bio ) . '</div>';
		}

		// Pinned / selected works — "best work first" (Story 9.5). Heading only
		// when there is at least one pin; nothing when empty.
		if ( array() !== $pinned ) {
			$html .= '<div class="ink-skrywerprofiel__vasgespel" data-ink-slot="vasgespelde-werke">'
				. '<h2 class="ink-skrywerprofiel__vasgespel-titel">' . esc_html__( 'Vasgespelde werke', 'ink-core' ) . '</h2>'
				. '<ul class="ink-skrywerprofiel__vasgespel-lys">';

			foreach ( $pinned as $card ) {
				$html .= '<li class="ink-skrywerprofiel__vasgespel-item is-style-card">'
					. '<span class="ink-skrywerprofiel__vasgespel-tipe">' . esc_html( Terms::label( (string) $card['type'] ) ) . '</span>'
					. '<a class="ink-skrywerprofiel__vasgespel-titel-skakel" href="' . esc_url( (string) $card['permalink'] ) . '">' . esc_html( (string) $card['title'] ) . '</a>'
					. '</li>';
			}

			$html .= '</ul></div>';
		}

		// Accomplishments area (writer achievements / placements surface).
		$html .= '<section class="ink-skrywerprofiel__prestasies">'
			. '<h2 class="ink-skrywerprofiel__prestasies-titel">' . esc_html__( 'Prestasies', 'ink-core' ) . '</h2>'
			. '</section>';

		$html .= '</section>';

		return $html;
	}
}
