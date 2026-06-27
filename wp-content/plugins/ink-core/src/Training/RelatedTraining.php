<?php
/**
 * Auto cross-surfacing of training beside works — Story 11.4 (FR-55).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Training;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/opleiding-verwant` block: published `opleiding_artikel` that
 * share a `genre`/`vaardigheid` term with the work being read (FR-55).
 *
 * The whole point is **shared-taxonomy surfacing, never manual linking** (Principle
 * 8): there is no editorial "related training" picker anywhere. A work surfaces
 * training **solely** because they carry an overlapping `genre` or `vaardigheid`
 * term — a work that shares no such term surfaces **nothing** (the block renders an
 * empty string, no heading, no shell). Server-rendered (AD-7), fail-safe.
 *
 * Mirrors {@see \Ink\Library\WinnerLinkage}'s relationship-resolver shape: a pure
 * {@see self::queryArgs()} + a side-effecting {@see self::relatedFor()} + a pure
 * {@see self::toHtml()}. Conflation-clean: reads only `Ink\Content` slugs + WP core,
 * zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class RelatedTraining {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/opleiding-verwant';

	/**
	 * How many related training items to surface (bounded).
	 *
	 * @var int
	 */
	public const LIMIT = 3;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/opleiding-verwant` dynamic block.
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
	 * Build the `WP_Query` args for the related-training query. Pure.
	 *
	 * Matches published `opleiding_artikel` sharing ANY supplied `genre` or
	 * `vaardigheid` term id. An empty term-id list contributes no clause (an empty
	 * `terms` array is an invalid `tax_query` clause); `relation => OR` is added only
	 * when BOTH taxonomies contribute. The current post is excluded so the same
	 * block embedded on an `opleiding_artikel` (or future challenge) view never
	 * self-lists.
	 *
	 * @param int       $exclude_id      The post to exclude (the work being read).
	 * @param list<int> $genre_ids       The work's `genre` term ids.
	 * @param list<int> $vaardigheid_ids The work's `vaardigheid` term ids.
	 * @param int       $limit           Max items.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $exclude_id, array $genre_ids, array $vaardigheid_ids, int $limit ): array {
		$clauses = array();

		if ( array() !== $genre_ids ) {
			$clauses[] = array(
				'taxonomy' => Taxonomies::GENRE,
				'field'    => 'term_id',
				'terms'    => array_values( $genre_ids ),
			);
		}

		if ( array() !== $vaardigheid_ids ) {
			$clauses[] = array(
				'taxonomy' => Taxonomies::VAARDIGHEID,
				'field'    => 'term_id',
				'terms'    => array_values( $vaardigheid_ids ),
			);
		}

		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'OR';
		}

		$args = array(
			'post_type'           => PostTypes::OPLEIDING_ARTIKEL,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, $limit ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( $exclude_id > 0 ) {
			$args['post__not_in'] = array( $exclude_id );
		}

		if ( array() !== $clauses ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- bounded shared-term surfacing (FR-55); the AD-7 server-rendered cross-surface, no search plugin.
			$args['tax_query'] = $clauses;
		}

		return $args;
	}

	/**
	 * Resolve the published training that shares a term with the given post.
	 *
	 * Collects the post's `genre` + `vaardigheid` term ids; with none, returns `[]`
	 * WITHOUT querying (a no-shared-term work surfaces nothing — the FR-55
	 * invariant). Otherwise runs the bounded query and maps to link rows.
	 *
	 * @param int $post_id The work being read.
	 * @param int $limit   Max items.
	 * @return list<array{title:string, permalink:string}>
	 */
	public static function relatedFor( int $post_id, int $limit = self::LIMIT ): array {
		if ( $post_id <= 0 ) {
			return array();
		}

		$genre_ids       = self::termIds( $post_id, Taxonomies::GENRE );
		$vaardigheid_ids = self::termIds( $post_id, Taxonomies::VAARDIGHEID );

		if ( array() === $genre_ids && array() === $vaardigheid_ids ) {
			return array();
		}

		$query = new \WP_Query( self::queryArgs( $post_id, $genre_ids, $vaardigheid_ids, $limit ) );

		$links = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$links[] = array(
				'title'     => (string) get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
			);
		}

		return $links;
	}

	/**
	 * The term ids a post carries in a taxonomy. '' / no-terms → []. Given the post.
	 *
	 * @param int    $post_id  The post.
	 * @param string $taxonomy The taxonomy slug.
	 * @return list<int>
	 */
	private static function termIds( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$ids = array();

		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return $ids;
	}

	/**
	 * Block render callback. Resolves the current post's related training, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_id = (int) get_the_ID();

		return self::toHtml( self::relatedFor( $post_id ) );
	}

	/**
	 * Build the cross-surface HTML. Pure — escaping only. Empty without links.
	 *
	 * @param list<array{title:string, permalink:string}> $links The related training.
	 * @return string
	 */
	public static function toHtml( array $links ): string {
		if ( array() === $links ) {
			return '';
		}

		$html = '<aside class="ink-opleiding-verwant">'
			. '<h2 class="ink-opleiding-verwant__titel">' . esc_html__( 'Verwante leerhulpbronne', 'ink-core' ) . '</h2>'
			. '<ul class="ink-opleiding-verwant__lys">';

		foreach ( $links as $link ) {
			$html .= '<li class="ink-opleiding-verwant__item">'
				. '<a class="ink-opleiding-verwant__skakel" href="' . esc_url( $link['permalink'] ) . '">'
				. esc_html( $link['title'] ) . '</a></li>';
		}

		return $html . '</ul></aside>';
	}
}
