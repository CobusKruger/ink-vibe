<?php
/**
 * Suggested next reads server block — Story 7.6 (FR-31).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/verwante-stukke` block: reads related by shared taxonomy terms.
 *
 * Suggestions are surfaced AUTOMATICALLY (Principle 8 — never per-item editorial
 * linking): the current post's terms across all its taxonomies (genre = tone /
 * topic, `ster_gradering` = Gradering) drive an OR `tax_query` for other published
 * bydraes (the CPT = form). The `skryfwerk` migration bucket is excluded. When
 * nothing shares a term, the area renders nothing.
 *
 * Reads stay server-rendered (AD-7) — `WP_Query`, not REST. Conflation-clean:
 * references only `Ink\Content\PostTypes` (the migration-load-bearing slug source)
 * + the Terms registry + WP core; zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class SuggestedReads {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/verwante-stukke';

	/**
	 * How many suggestions to show.
	 *
	 * @var int
	 */
	private const LIMIT = 4;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/verwante-stukke` dynamic block.
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
	 * The readable bydrae types for suggestions (skryfwerk excluded).
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * Build the `WP_Query` args for "shares a term with this post". Pure.
	 *
	 * One `tax_query` clause per taxonomy that has term ids, OR-combined — so a
	 * suggestion need only share a term in ANY of the post's taxonomies.
	 *
	 * @param int                      $post_id        The current post (excluded).
	 * @param array<string, list<int>> $term_ids_by_tax Taxonomy slug → term ids.
	 * @param list<string>             $types          The bydrae types to query.
	 * @param int                      $limit          Max suggestions.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $post_id, array $term_ids_by_tax, array $types, int $limit ): array {
		$tax_query = array( 'relation' => 'OR' );

		foreach ( $term_ids_by_tax as $taxonomy => $term_ids ) {
			if ( array() === $term_ids ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array_values( $term_ids ),
			);
		}

		return array(
			'post_type'           => $types,
			'post__not_in'        => array( $post_id ),
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- bounded shared-term related-reads surfacing; the AD-7 server-rendered cross-surface (LIMIT-capped, no_found_rows, no search plugin). The tax_query is intrinsic to "related by shared term".
			'tax_query'           => $tax_query,
		);
	}

	/**
	 * Block render callback for the current work.
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		if ( $post_id <= 0 ) {
			return '';
		}

		$term_ids_by_tax = self::collectTermIds( $post_id );

		if ( array() === $term_ids_by_tax ) {
			return '';
		}

		$query = new \WP_Query( self::queryArgs( $post_id, $term_ids_by_tax, self::readableTypes(), self::LIMIT ) );
		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$cards[] = array(
				'title'     => get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
				'type'      => $post->post_type,
				'author'    => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			);
		}

		return self::toHtml( $cards );
	}

	/**
	 * Collect the current post's term ids, keyed by taxonomy (only non-empty).
	 *
	 * @param int $post_id The post.
	 * @return array<string, list<int>>
	 */
	private static function collectTermIds( int $post_id ): array {
		$by_tax = array();

		foreach ( (array) get_object_taxonomies( get_post_type( $post_id ) ) as $taxonomy ) {
			$terms = wp_get_post_terms( $post_id, (string) $taxonomy, array( 'fields' => 'ids' ) );

			if ( is_array( $terms ) && array() !== $terms ) {
				$by_tax[ (string) $taxonomy ] = array_map( 'intval', $terms );
			}
		}

		return $by_tax;
	}

	/**
	 * Build the suggestions HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, type:string, author:string}> $cards The suggestions.
	 * @return string Empty when there are no suggestions (no empty section).
	 */
	public static function toHtml( array $cards ): string {
		if ( array() === $cards ) {
			return '';
		}

		$html = '<section class="ink-verwante"><h2 class="ink-verwante__heading">' . esc_html__( 'Verwante stukke', 'ink-core' ) . '</h2><ul class="ink-verwante__list">';

		foreach ( $cards as $card ) {
			$html .= '<li class="ink-verwante__item">'
				. '<span class="ink-verwante__type">' . esc_html( Terms::label( $card['type'] ) ) . '</span>'
				. '<a class="ink-verwante__title" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
				. '<span class="ink-verwante__author">' . esc_html( $card['author'] ) . '</span>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
