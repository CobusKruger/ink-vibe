<?php
/**
 * Leeslys profile list server block — Story 7.7 (FR-29).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/leeslys` block: the member's saved works, surfaced on profile.
 *
 * Lists the current member's leeslys (the dedicated My Profiel page is Epic 9;
 * v1 lists the logged-in member's own saves). Reads stay server-rendered (AD-7).
 * Logged-out → nothing.
 *
 * @package Ink\Core
 */
final class ReadingList {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/leeslys';

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
	 * Register the `ink/leeslys` dynamic block.
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
	 * Block render callback: the current member's saved works.
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$cards = array();

		foreach ( ReadingListStore::forUser( get_current_user_id() ) as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
				continue;
			}

			$title = get_the_title( $post );

			// Same fixture-leak bug class fixed on every other page this rework —
			// a member's own leeslys must never show seeded QA content.
			if ( QaFixture::isFixtureTitle( $title ) ) {
				continue;
			}

			$cards[] = array(
				'title'     => $title,
				'permalink' => (string) get_permalink( $post ),
				'type'      => $post->post_type,
			);
		}

		return self::toHtml( $cards );
	}

	/**
	 * Build the leeslys list HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, type:string}> $cards The saved works.
	 * @return string
	 */
	public static function toHtml( array $cards ): string {
		$html = '<section class="ink-leeslys"><h2 class="ink-leeslys__heading">' . esc_html( Terms::label( 'leeslys' ) ) . '</h2>';

		if ( array() === $cards ) {
			// Empty state — matches every sibling My Profiel section
			// (PinnedWorksManager / ReadCountSurface / FollowingFeed all render
			// an authored message instead of a bare empty list). Heading is
			// ratified copy (product owner, 2026-09-06). The body line
			// (Lovable: "Tap the bookmark on any story or poem and it will
			// land here for later.") has NO ratified Afrikaans yet — per the
			// standing unauthored-copy workflow it is NOT invented inline, it
			// carries the standard human-copy-pending marker (below) and is
			// tracked in docs/afrikaans-translation-sheet.md (ID
			// LEESLYS-LEEG-BODY) / docs/afrikaans-copy-worklist.md until a
			// human ratifies it; `composer copy:scan` tracks it as debt.
			$html .= '<div class="ink-leeslys__leeg">'
				. '<p class="ink-leeslys__leeg-titel">' . esc_html__( 'Nog niks gestoor nie.', 'ink-core' ) . '</p>'
				. '<p class="ink-leeslys__leeg-beskrywing">' . esc_html__( '[NEEDS HUMAN AFRIKAANS] — Leeslys empty-state body copy not yet authored in ui-copy-translations.md.', 'ink-core' ) . '</p>'
				. '</div></section>';

			return $html;
		}

		$html .= '<ul class="ink-leeslys__list">';
		foreach ( $cards as $card ) {
			$html .= '<li class="ink-leeslys__item">'
				. '<span class="ink-leeslys__type">' . esc_html( Terms::label( $card['type'] ) ) . '</span>'
				. '<a class="ink-leeslys__title" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
				. '</li>';
		}
		$html .= '</ul></section>';

		return $html;
	}
}
