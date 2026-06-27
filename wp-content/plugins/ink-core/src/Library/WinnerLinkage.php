<?php
/**
 * Biblioteek winner ↔ challenge linkage — Story 10.5 (FR-53).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Library;

use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/biblioteek-uitdaging-skakel` block: a link from a Biblioteek
 * item back to the `uitdaging` that produced it (FR-53).
 *
 * The modelled relationship is the Story 6.6 round-term slug convention
 * (`uitdaging-{id}`, owned by {@see ChallengeRound}): a winning piece carries an
 * `uitdagingsrondte` term whose slug encodes the producing challenge. This block
 * reads that key in reverse and surfaces a link on the single reading view, so a
 * reader can trace the piece's competition context. Server-rendered, fail-safe
 * (a term that doesn't resolve to a published `uitdaging` shows nothing).
 *
 * Conflation-clean: content-relationship display only — zero `Ink\Tiers`/
 * `Ink\Entitlement`. "Winner" here is the challenge-context trace, never a gate.
 *
 * @package Ink\Core
 */
final class WinnerLinkage {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/biblioteek-uitdaging-skakel';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/biblioteek-uitdaging-skakel` dynamic block.
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
	 * Resolve a post's producing-challenge links via its `uitdagingsrondte` terms.
	 *
	 * Each round term whose slug parses to a published `uitdaging` (via
	 * {@see ChallengeRound::uitdagingIdFromSlug()}) yields a link; everything else
	 * (unparseable slug, deleted / non-uitdaging / unpublished post) is silently
	 * skipped — never a broken or draft-exposing link.
	 *
	 * @param int $post_id The Biblioteek item (or any post) id.
	 * @return list<array{title:string, permalink:string}> The producing-challenge links.
	 */
	public static function linksFor( int $post_id ): array {
		if ( $post_id <= 0 ) {
			return array();
		}

		$terms = get_the_terms( $post_id, Taxonomies::UITDAGINGSRONDTE );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$links = array();
		$seen  = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$uitdaging_id = ChallengeRound::uitdagingIdFromSlug( $term->slug );

			if ( null === $uitdaging_id || isset( $seen[ $uitdaging_id ] ) ) {
				continue;
			}

			if ( PostTypes::UITDAGING !== get_post_type( $uitdaging_id ) ) {
				continue;
			}

			if ( 'publish' !== get_post_status( $uitdaging_id ) ) {
				continue;
			}

			$seen[ $uitdaging_id ] = true;
			$links[]               = array(
				'title'     => (string) get_the_title( $uitdaging_id ),
				'permalink' => (string) get_permalink( $uitdaging_id ),
			);
		}

		return $links;
	}

	/**
	 * Block render callback. Resolves the current post's challenge links, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_id = (int) get_the_ID();

		return self::toHtml( self::linksFor( $post_id ) );
	}

	/**
	 * Build the linkage HTML. Pure — escaping only. Renders nothing without links.
	 *
	 * @param list<array{title:string, permalink:string}> $links The producing-challenge links.
	 * @return string
	 */
	public static function toHtml( array $links ): string {
		if ( array() === $links ) {
			return '';
		}

		$html = '<aside class="ink-biblioteek-uitdaging">'
			. '<span class="ink-biblioteek-uitdaging__etiket">' . esc_html__( 'Uit die uitdaging:', 'ink-core' ) . '</span>'
			. '<ul class="ink-biblioteek-uitdaging__lys">';

		foreach ( $links as $link ) {
			$html .= '<li class="ink-biblioteek-uitdaging__item">'
				. '<a class="ink-biblioteek-uitdaging__skakel" href="' . esc_url( $link['permalink'] ) . '">'
				. esc_html( $link['title'] ) . '</a></li>';
		}

		return $html . '</ul></aside>';
	}
}
