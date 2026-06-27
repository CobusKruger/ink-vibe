<?php
/**
 * Opleiding hub server block — Story 11.1 (FR-54).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Training;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\ArchiveRender;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/opleiding-argief` block: the Opleiding resource hub.
 *
 * Lists published `opleiding_artikel` posts (writing-craft guidance), newest-first,
 * paginated, with a featured strip and a keyword search — the Library-layout
 * archetype applied to training. A RESOURCE HUB, not an LMS: no course/lesson/
 * progress mechanics. Reads stay SERVER-RENDERED via `WP_Query` (AD-7 — no REST
 * for listings), mirroring the {@see \Ink\Library\Archive} house style: pure
 * {@see self::queryArgs()} + pure {@see self::toHtml()} + a thin {@see self::render()}.
 *
 * The shared `pill`/`pagination`/request-read primitives come from
 * {@see ArchiveRender} (the Epic-10 carry-forward extraction). The `vaardigheid`
 * faceted filter is Story 11.2 — a clearly-named seam is left here, never queried.
 *
 * Conflation-clean: references only `Ink\Content` (the migration-load-bearing CPT
 * slug) + the `Terms` registry + Kernel + WP core — zero `Ink\Tiers`/
 * `Ink\Entitlement`. Browsing published training is open (never gated).
 *
 * @package Ink\Core
 */
final class Hub {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/opleiding-argief';

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
	public const PAGED_VAR = 'opleiding_bladsy';

	/**
	 * Keyword-search query var.
	 *
	 * @var string
	 */
	public const SEARCH_VAR = 'opleiding_soek';

	/**
	 * Vaardigheid facet query var (a `vaardigheid` term slug, or absent for "Alles").
	 *
	 * @var string
	 */
	public const VAARDIGHEID_VAR = 'opleiding_vaardigheid';

	/**
	 * CSS prefix shared by the block markup + the {@see ArchiveRender} pager.
	 *
	 * @var string
	 */
	private const CSS = 'ink-opleiding';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/opleiding-argief` dynamic block.
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
	 * Build the `WP_Query` args for the newest-first hub listing. Pure.
	 *
	 * A `vaardigheid` `tax_query` is added only for a non-empty term slug, and the
	 * `s` keyword only for a non-empty trimmed term — so a hostile/garbage query
	 * string degrades to the unfiltered newest-first listing rather than a broken
	 * query (the 10.1 genre-filter idiom).
	 *
	 * @param int         $paged       The requested page (clamped to >= 1).
	 * @param int         $per_page    Items per page.
	 * @param string|null $vaardigheid Optional `vaardigheid` term slug (the facet).
	 * @param string      $search      Optional keyword (native `s`).
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page, ?string $vaardigheid = null, string $search = '' ): array {
		$args = array(
			'post_type'           => PostTypes::OPLEIDING_ARTIKEL,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);

		if ( null !== $vaardigheid && '' !== $vaardigheid ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a single bounded vaardigheid-slug facet on a curated CPT; the AD-7 server-rendered hub filter, no search plugin.
			$args['tax_query'] = array(
				array(
					'taxonomy' => Taxonomies::VAARDIGHEID,
					'field'    => 'slug',
					'terms'    => $vaardigheid,
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
			'post_type'           => PostTypes::OPLEIDING_ARTIKEL,
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
		$paged       = ArchiveRender::requestInt( self::PAGED_VAR, 1 );
		$search      = ArchiveRender::requestText( self::SEARCH_VAR );
		$vaardigheid = ArchiveRender::requestKey( self::VAARDIGHEID_VAR );

		$active_facet = '' !== $vaardigheid ? $vaardigheid : null;

		$query = new \WP_Query( self::queryArgs( $paged, self::PER_PAGE, $active_facet, $search ) );

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$cards[] = self::card( $post );
		}

		// Featured strip only on the unfiltered first page (no facet/search/paging).
		$featured = array();

		if ( 1 === max( 1, $paged ) && null === $active_facet && '' === $search ) {
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
			self::vaardigheidTerms(),
			array(
				'paged'       => max( 1, $paged ),
				'max_pages'   => (int) $query->max_num_pages,
				'vaardigheid' => $active_facet,
				'search'      => $search,
			)
		);
	}

	/**
	 * The `vaardigheid` terms in use, as `{slug,name}` rows for the facet filter.
	 * Side-effecting (queries terms) — kept out of the pure render so
	 * {@see self::toHtml()} stays testable. (Mirrors `Library\Archive::genreTerms`.)
	 *
	 * @return list<array{slug:string, name:string}>
	 */
	private static function vaardigheidTerms(): array {
		if ( ! function_exists( 'get_terms' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => Taxonomies::VAARDIGHEID,
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
	 * Map a post to a card row. Given the post.
	 *
	 * @param \WP_Post $post The training item.
	 * @return array{title:string, permalink:string, author:string}
	 */
	private static function card( \WP_Post $post ): array {
		return array(
			'title'     => get_the_title( $post ),
			'permalink' => (string) get_permalink( $post ),
			'author'    => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
		);
	}

	/**
	 * Build the hub HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, author:string}>                $cards    The items.
	 * @param list<array{title:string, permalink:string, author:string}>                $featured The featured strip items.
	 * @param list<array{slug:string, name:string}>                                     $facets   The vaardigheid facet terms.
	 * @param array{paged:int, max_pages:int, vaardigheid?:string|null, search?:string} $nav Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $featured, array $facets, array $nav ): string {
		$heading  = '<h1 class="ink-opleiding__heading">' . esc_html( Terms::label( 'opleiding' ) ) . '</h1>';
		$controls = self::featuredHtml( $featured )
			. self::searchHtml( isset( $nav['search'] ) ? (string) $nav['search'] : '', $nav['vaardigheid'] ?? null )
			. self::filterHtml( $facets, $nav['vaardigheid'] ?? null );

		if ( array() === $cards ) {
			/* translators: %s: the Opleiding label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'opleiding' ) );

			return '<section class="ink-opleiding">' . $heading . $controls
				. '<p class="ink-opleiding__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-opleiding">' . $heading . $controls . '<ul class="ink-opleiding__list">';

		foreach ( $cards as $card ) {
			$html .= self::cardHtml( $card );
		}

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		$html .= '</ul>' . ArchiveRender::pagination( $paged, $max_pages, self::CSS, self::PAGED_VAR ) . '</section>';

		return $html;
	}

	/**
	 * The featured strip — an "Uitgelig" lead-in + a card per featured item. Pure.
	 *
	 * Renders nothing without featured items (filtered/paged views pass none).
	 *
	 * @param list<array{title:string, permalink:string, author:string}> $featured The featured items.
	 * @return string
	 */
	public static function featuredHtml( array $featured ): string {
		if ( array() === $featured ) {
			return '';
		}

		$html = '<div class="ink-opleiding__uitgelig">'
			. '<h2 class="ink-opleiding__uitgelig-titel">' . esc_html__( 'Uitgelig', 'ink-core' ) . '</h2>'
			. '<ul class="ink-opleiding__uitgelig-lys">';

		foreach ( $featured as $card ) {
			$html .= self::cardHtml( $card, 'ink-opleiding__uitgelig-item' );
		}

		return $html . '</ul></div>';
	}

	/**
	 * The keyword-search form. Pure — escaping only.
	 *
	 * A `method="get"` form replaces the whole query string on submit, so the
	 * active vaardigheid facet is carried forward in a hidden field (otherwise
	 * searching while filtered to a facet would silently reset to "Alles").
	 *
	 * @param string      $term         The current search term, for the input value.
	 * @param string|null $active_facet The active vaardigheid slug to preserve, or null.
	 * @return string
	 */
	public static function searchHtml( string $term, ?string $active_facet = null ): string {
		$hidden = ( null !== $active_facet && '' !== $active_facet )
			? '<input type="hidden" name="' . esc_attr( self::VAARDIGHEID_VAR ) . '" value="' . esc_attr( $active_facet ) . '" />'
			: '';

		return '<form class="ink-opleiding__soek" role="search" method="get">'
			. $hidden
			. '<input type="search" class="ink-opleiding__soek-veld" name="' . esc_attr( self::SEARCH_VAR ) . '"'
			. ' value="' . esc_attr( $term ) . '"'
			. ' placeholder="' . esc_attr__( 'Soek in opleiding…', 'ink-core' ) . '"'
			. ' aria-label="' . esc_attr__( 'Soek in opleiding…', 'ink-core' ) . '" />'
			. '<button type="submit" class="ink-opleiding__soek-knoppie">' . esc_html__( 'Soek', 'ink-core' ) . '</button>'
			. '</form>';
	}

	/**
	 * The vaardigheid faceted filter — "Alles" + a pill per term in use. Pure.
	 *
	 * Each link sets/clears the `vaardigheid` facet query var (resetting the page);
	 * the active facet is marked. Renders nothing without terms (so an empty hub
	 * shows no filter row). Mirrors `Library\Archive::filterHtml`.
	 *
	 * @param list<array{slug:string, name:string}> $facets       The vaardigheid terms.
	 * @param string|null                           $active_facet The active term slug, or null for "Alles".
	 * @return string
	 */
	public static function filterHtml( array $facets, ?string $active_facet ): string {
		if ( array() === $facets ) {
			return '';
		}

		$html = '<div class="ink-opleiding__filter">';

		$html .= ArchiveRender::pill(
			(string) remove_query_arg( array( self::VAARDIGHEID_VAR, self::PAGED_VAR ) ),
			__( 'Alles', 'ink-core' ),
			( null === $active_facet ),
			'ink-opleiding__filter-knoppie'
		);

		foreach ( $facets as $facet ) {
			$url   = (string) add_query_arg( self::VAARDIGHEID_VAR, $facet['slug'], remove_query_arg( self::PAGED_VAR ) );
			$html .= ArchiveRender::pill(
				$url,
				$facet['name'],
				$facet['slug'] === $active_facet,
				'ink-opleiding__filter-knoppie'
			);
		}

		return $html . '</div>';
	}

	/**
	 * One training card. Pure — escaping only.
	 *
	 * @param array{title:string, permalink:string, author:string} $card  The item.
	 * @param string                                               $extra Optional extra CSS class.
	 * @return string
	 */
	private static function cardHtml( array $card, string $extra = '' ): string {
		$class = 'ink-opleiding__item is-style-card' . ( '' !== $extra ? ' ' . $extra : '' );

		return '<li class="' . esc_attr( $class ) . '">'
			. '<a class="ink-opleiding__titel" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
			. '<span class="ink-opleiding__outeur">' . esc_html( $card['author'] ) . '</span>'
			. '</li>';
	}
}
