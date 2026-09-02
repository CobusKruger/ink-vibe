<?php
/**
 * The reading-page author card server block — lees-gedig fidelity pass
 * (docs/theme-fidelity-audit-handoff.md §6, finding #15).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a compact "who wrote this" card between a work's body and its
 * Gemeenskapsreaksies — a bigger avatar than the reading-header byline, a bio
 * (when the author has one), a Follow toggle (reusing {@see FollowToggle}'s
 * exact markup/classes so both surfaces share one visual + behavioural
 * definition), and a link to the skrywer's public profile.
 *
 * Presentation only: this owns the structure, the theme owns the CSS. No bio
 * fallback sentence is invented for an empty bio — the paragraph is simply
 * omitted, the same convention {@see SkrywerProfiel} already uses.
 *
 * @package Ink\Core
 */
final class ReadingAuthorCard {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/outeur-kaart';

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
	 * Register the `ink/outeur-kaart` dynamic block.
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
	 * Block render callback: the current post's author, resolved from the loop.
	 *
	 * @return string
	 */
	public static function render(): string {
		$author_id = (int) get_the_author_meta( 'ID' );

		if ( $author_id <= 0 ) {
			return '';
		}

		$viewer_id   = is_user_logged_in() ? get_current_user_id() : 0;
		$show_follow = $viewer_id > 0 && $viewer_id !== $author_id;

		return self::toHtml(
			$author_id,
			$show_follow ? FollowStore::isFollowing( $viewer_id, $author_id ) : null
		);
	}

	/**
	 * Build the author card HTML. Pure — WP author-data reads + escaping only.
	 *
	 * @param int       $author_id The skrywer.
	 * @param bool|null $following Whether the viewer follows them (`null` = don't
	 *                             render a Follow toggle at all — logged out, or
	 *                             viewing your own work).
	 * @return string
	 */
	public static function toHtml( int $author_id, ?bool $following ): string {
		$name    = (string) get_the_author_meta( 'display_name', $author_id );
		$bio     = (string) get_the_author_meta( 'description', $author_id );
		$avatar  = get_avatar( $author_id, 96, '', $name, array( 'class' => 'ink-outeur-kaart__foto' ) );
		$profile = (string) get_author_posts_url( $author_id );

		$html  = '<section class="ink-outeur-kaart">';
		$html .= '<div class="ink-outeur-kaart__foto-omhulsel">' . (string) $avatar . '</div>';
		$html .= '<div class="ink-outeur-kaart__inhoud">';
		$html .= '<h3 class="ink-outeur-kaart__naam">' . esc_html( $name ) . '</h3>';

		if ( '' !== trim( $bio ) ) {
			$html .= '<p class="ink-outeur-kaart__bio">' . esc_html( $bio ) . '</p>';
		}

		$html .= '<div class="ink-outeur-kaart__aksies">';
		if ( null !== $following ) {
			$html .= FollowToggle::toHtml( $author_id, $following );
		}
		$html .= '<a class="ink-outeur-kaart__sien-alle" href="' . esc_url( $profile ) . '">'
			. esc_html( Terms::label( 'sien_alle_werke' ) ) . '</a>';
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</section>';

		return $html;
	}
}
