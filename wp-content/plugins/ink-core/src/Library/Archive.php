<?php
/**
 * Biblioteek archive server block — Story 10.1 (FR-52).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Library;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\ArchiveRender;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/biblioteek-argief` block: the Biblioteek archive.
 *
 * Lists published `biblioteek_item` posts (the curated/reference library),
 * newest-first, paginated, with a featured strip, a `genre` category filter and a
 * keyword search. Reads stay SERVER-RENDERED via `WP_Query` (AD-7 — no REST for
 * listings), mirroring the {@see \Ink\Discovery\WorksArchive} house style: pure
 * {@see self::queryArgs()} + pure {@see self::toHtml()} + a thin {@see self::render()}.
 *
 * Conflation-clean: references only `Ink\Content` (the migration-load-bearing CPT
 * + genre taxonomy slugs) + the `Terms` registry + WP core — zero `Ink\Tiers`/
 * `Ink\Entitlement`. Browsing published library work is open (never gated).
 *
 * @package Ink\Core
 */
final class Archive {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/biblioteek-argief';

	/**
	 * Items per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 12;

	/**
	 * Featured-strip size (most-recent items).
	 *
	 * @var int
	 */
	public const FEATURED = 3;

	/**
	 * Custom paged query var — avoids colliding with WP page pagination.
	 *
	 * @var string
	 */
	public const PAGED_VAR = 'biblioteek_bladsy';

	/**
	 * Genre-filter query var (a `genre` term slug, or absent for "Alles").
	 *
	 * @var string
	 */
	public const GENRE_VAR = 'biblioteek_genre';

	/**
	 * Keyword-search query var.
	 *
	 * @var string
	 */
	public const SEARCH_VAR = 'biblioteek_soek';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/biblioteek-argief` dynamic block.
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
	 * Build the `WP_Query` args for the newest-first library archive. Pure.
	 *
	 * A `genre` `tax_query` is added only for a non-empty term slug, and the `s`
	 * keyword only for a non-empty trimmed term — so a hostile/garbage query string
	 * degrades to the unfiltered newest-first listing rather than a broken query.
	 *
	 * @param int         $paged    The requested page (clamped to >= 1).
	 * @param int         $per_page Items per page.
	 * @param string|null $genre    Optional `genre` term slug.
	 * @param string      $search   Optional keyword (native `s`).
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page, ?string $genre = null, string $search = '' ): array {
		$args = array(
			'post_type'           => PostTypes::BIBLIOTEEK_ITEM,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);

		if ( null !== $genre && '' !== $genre ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a single bounded genre-slug facet on a curated CPT; the AD-7 server-rendered archive filter, no search plugin.
			$args['tax_query'] = array(
				array(
					'taxonomy' => Taxonomies::GENRE,
					'field'    => 'slug',
					'terms'    => $genre,
				),
			);
		}

		$search = trim( $search );

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		return $args;
	}

	/**
	 * `WP_Query` args for the featured strip — the most-recent published items. Pure.
	 *
	 * @param int $count How many items to feature.
	 * @return array<string, mixed>
	 */
	public static function featuredArgs( int $count ): array {
		return array(
			'post_type'           => PostTypes::BIBLIOTEEK_ITEM,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, $count ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
	}

	/**
	 * Block render callback. Reads the browse inputs defensively, queries, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$paged  = ArchiveRender::requestInt( self::PAGED_VAR, 1 );
		$genre  = ArchiveRender::requestKey( self::GENRE_VAR );
		$search = ArchiveRender::requestText( self::SEARCH_VAR );

		$active_genre = '' !== $genre ? $genre : null;

		$query = new \WP_Query( self::queryArgs( $paged, self::PER_PAGE, $active_genre, $search ) );

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$cards[] = self::card( $post );
		}

		// Featured strip only on the unfiltered first page (no genre/search/paging).
		$featured = array();

		if ( 1 === max( 1, $paged ) && null === $active_genre && '' === $search ) {
			$featured_query = new \WP_Query( self::featuredArgs( self::FEATURED ) );

			foreach ( $featured_query->posts as $post ) {
				if ( $post instanceof \WP_Post ) {
					$featured[] = self::card( $post );
				}
			}
		}

		return self::toHtml(
			$cards,
			$featured,
			self::genreTerms(),
			array(
				'paged'     => max( 1, $paged ),
				'max_pages' => (int) $query->max_num_pages,
				'genre'     => $active_genre,
				'search'    => $search,
			)
		);
	}

	/**
	 * Map a post to a card row (incl. its primary genre badge). Given the post.
	 *
	 * @param \WP_Post $post The library item.
	 * @return array{title:string, permalink:string, author:string, genre:string}
	 */
	private static function card( \WP_Post $post ): array {
		return array(
			'title'     => get_the_title( $post ),
			'permalink' => (string) get_permalink( $post ),
			'author'    => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			'genre'     => self::primaryGenre( $post ),
		);
	}

	/**
	 * The name of the item's first `genre` term, for the card badge. '' when none.
	 *
	 * @param \WP_Post $post The library item.
	 * @return string
	 */
	private static function primaryGenre( \WP_Post $post ): string {
		$terms = get_the_terms( $post, Taxonomies::GENRE );

		if ( ! is_array( $terms ) ) {
			return '';
		}

		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				return $term->name;
			}
		}

		return '';
	}

	/**
	 * The `genre` terms in use, as `{slug,name}` rows for the filter. Side-effecting
	 * (queries terms) — kept out of the pure render so {@see self::toHtml()} stays testable.
	 *
	 * @return list<array{slug:string, name:string}>
	 */
	private static function genreTerms(): array {
		if ( ! function_exists( 'get_terms' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => Taxonomies::GENRE,
				'hide_empty' => true,
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$rows = array();

		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$rows[] = array(
					'slug' => $term->slug,
					'name' => $term->name,
				);
			}
		}

		return $rows;
	}

	/**
	 * Build the archive HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, author:string, genre?:string}> $cards    The items.
	 * @param list<array{title:string, permalink:string, author:string, genre?:string}> $featured The featured strip items.
	 * @param list<array{slug:string, name:string}>                                     $genres   The genre filter terms.
	 * @param array{paged:int, max_pages:int, genre?:string|null, search?:string}       $nav Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $featured, array $genres, array $nav ): string {
		$heading  = '<h1 class="ink-biblioteek__heading">' . esc_html( Terms::label( 'biblioteek' ) ) . '</h1>';
		$controls = self::featuredHtml( $featured )
			. self::searchHtml( isset( $nav['search'] ) ? (string) $nav['search'] : '', $nav['genre'] ?? null )
			. self::filterHtml( $genres, $nav['genre'] ?? null );

		if ( array() === $cards ) {
			/* translators: %s: the Biblioteek label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'biblioteek' ) );

			return '<section class="ink-biblioteek">' . $heading . $controls
				. '<p class="ink-biblioteek__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-biblioteek">' . $heading . $controls . '<ul class="ink-biblioteek__list">';

		foreach ( $cards as $card ) {
			$html .= self::cardHtml( $card );
		}

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		$html .= '</ul>' . ArchiveRender::pagination( $paged, $max_pages, 'ink-biblioteek', self::PAGED_VAR ) . '</section>';

		return $html;
	}

	/**
	 * The featured strip — an "Uitgelig" lead-in + a card per featured item. Pure.
	 *
	 * Renders nothing without featured items (filtered/paged views pass none).
	 *
	 * @param list<array{title:string, permalink:string, author:string, genre?:string}> $featured The featured items.
	 * @return string
	 */
	public static function featuredHtml( array $featured ): string {
		if ( array() === $featured ) {
			return '';
		}

		$html = '<div class="ink-biblioteek__uitgelig">'
			. '<h2 class="ink-biblioteek__uitgelig-titel">' . esc_html__( 'Uitgelig', 'ink-core' ) . '</h2>'
			. '<ul class="ink-biblioteek__uitgelig-lys">';

		foreach ( $featured as $card ) {
			$html .= self::cardHtml( $card, 'ink-biblioteek__uitgelig-item' );
		}

		return $html . '</ul></div>';
	}

	/**
	 * The keyword-search form. Pure — escaping only.
	 *
	 * A `method="get"` form replaces the whole query string on submit, so the
	 * active genre is carried forward in a hidden field (otherwise searching while
	 * filtered to a genre would silently reset to "Alles").
	 *
	 * @param string      $term         The current search term, for the input value.
	 * @param string|null $active_genre The active genre slug to preserve, or null.
	 * @return string
	 */
	public static function searchHtml( string $term, ?string $active_genre = null ): string {
		$hidden = ( null !== $active_genre && '' !== $active_genre )
			? '<input type="hidden" name="' . esc_attr( self::GENRE_VAR ) . '" value="' . esc_attr( $active_genre ) . '" />'
			: '';

		return '<form class="ink-biblioteek__soek" role="search" method="get">'
			. $hidden
			. '<input type="search" class="ink-biblioteek__soek-veld" name="' . esc_attr( self::SEARCH_VAR ) . '"'
			. ' value="' . esc_attr( $term ) . '"'
			. ' placeholder="' . esc_attr__( 'Soek in die biblioteek…', 'ink-core' ) . '"'
			. ' aria-label="' . esc_attr__( 'Soek in die biblioteek…', 'ink-core' ) . '" />'
			. '<button type="submit" class="ink-biblioteek__soek-knoppie">' . esc_html__( 'Soek', 'ink-core' ) . '</button>'
			. '</form>';
	}

	/**
	 * The genre category filter — "Alles" + a pill per genre term in use. Pure.
	 *
	 * Each link sets/clears the `genre` query var (resetting the page); the active
	 * genre is marked. Renders nothing without terms (so an empty library shows no
	 * filter row).
	 *
	 * @param list<array{slug:string, name:string}> $genres       The genre terms.
	 * @param string|null                           $active_genre The active term slug, or null for "Alles".
	 * @return string
	 */
	public static function filterHtml( array $genres, ?string $active_genre ): string {
		if ( array() === $genres ) {
			return '';
		}

		$html = '<div class="ink-biblioteek__filter">';

		$html .= ArchiveRender::pill(
			(string) remove_query_arg( array( self::GENRE_VAR, self::PAGED_VAR ) ),
			__( 'Alles', 'ink-core' ),
			( null === $active_genre ),
			'ink-biblioteek__filter-knoppie'
		);

		foreach ( $genres as $genre ) {
			$url   = (string) add_query_arg( self::GENRE_VAR, $genre['slug'], remove_query_arg( self::PAGED_VAR ) );
			$html .= ArchiveRender::pill(
				$url,
				$genre['name'],
				$genre['slug'] === $active_genre,
				'ink-biblioteek__filter-knoppie'
			);
		}

		return $html . '</div>';
	}

	/**
	 * One library card. Pure — escaping only.
	 *
	 * Renders the item's `genre` term as the card badge (AC: title → permalink,
	 * genre badge, author); the badge is omitted for an item with no genre term.
	 *
	 * @param array{title:string, permalink:string, author:string, genre?:string} $card  The item.
	 * @param string                                                              $extra Optional extra CSS class.
	 * @return string
	 */
	private static function cardHtml( array $card, string $extra = '' ): string {
		$class = 'ink-biblioteek__item is-style-card' . ( '' !== $extra ? ' ' . $extra : '' );
		$genre = isset( $card['genre'] ) ? (string) $card['genre'] : '';

		$badge = '' !== $genre
			? '<span class="ink-biblioteek__genre">' . esc_html( $genre ) . '</span>'
			: '';

		return '<li class="' . esc_attr( $class ) . '">'
			. $badge
			. '<a class="ink-biblioteek__titel" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
			. '<span class="ink-biblioteek__outeur">' . esc_html( $card['author'] ) . '</span>'
			. '</li>';
	}
}
