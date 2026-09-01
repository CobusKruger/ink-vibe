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
 * Post-Epic-19 fidelity pass (biblioteek workstream): the card now also carries an
 * `excerpt`/`read_minutes` (the "Library-layout archetype" shape already used by
 * {@see \Ink\Training\Hub}) plus a cover `image` — `biblioteek_item` is the one
 * sibling of `opleiding_artikel` whose page-map responsive note singles out "image
 * crop parity" as a specific risk, so unlike `Hub::card()` this one resolves a
 * `get_the_post_thumbnail()` string too (trusted WP-generated markup, same trust
 * class as {@see self::icon()}'s hand-authored SVG — never re-escaped downstream).
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
	 * Assumed reading pace (words per minute) for the card read-time estimate.
	 *
	 * A LOCAL copy of {@see \Ink\Discovery\ReadingTime}'s rule, not a cross-module
	 * import — `deptrac.yaml` allows `Library` only `Kernel` + `Content`, so a
	 * `Library -> Discovery` edge is not available. Mirrors the precedent already
	 * documented on {@see \Ink\Training\Hub::WORDS_PER_MINUTE}.
	 *
	 * @var int
	 */
	private const WORDS_PER_MINUTE = 200;

	/**
	 * The decorative search-icon path (Lucide "search" glyph, §0.9 icon convention).
	 *
	 * @var string
	 */
	private const ICON_SEARCH = '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>';

	/**
	 * The decorative clock-icon path (read-time, mirrors Training\Hub).
	 *
	 * @var string
	 */
	private const ICON_CLOCK = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';

	/**
	 * The decorative arrow-icon path (card "Lees" hover affordance, mirrors
	 * Training\Hub's ICON_ARROW).
	 *
	 * @var string
	 */
	private const ICON_ARROW = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

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
	 * `excerpt`/`read_minutes`/`image` mirror the {@see \Ink\Training\Hub}
	 * "Library-layout archetype" card shape — a trimmed excerpt, a read-time
	 * estimate from the body word count, and (biblioteek-specific — see class
	 * docblock) a resolved cover-image `<img>` string, empty when the item carries
	 * no featured image.
	 *
	 * @param \WP_Post $post The library item.
	 * @return array{title:string, permalink:string, author:string, genre:string, excerpt:string, read_minutes:int, image:string}
	 */
	private static function card( \WP_Post $post ): array {
		return array(
			'title'        => get_the_title( $post ),
			'permalink'    => (string) get_permalink( $post ),
			'author'       => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			'genre'        => self::primaryGenre( $post ),
			'excerpt'      => self::excerptFor( $post ),
			'read_minutes' => self::readMinutes( wp_strip_all_tags( (string) $post->post_content ) ),
			'image'        => self::imageHtml( $post ),
		);
	}

	/**
	 * A trimmed excerpt for the card body. Impure (WP excerpt helpers). Mirrors
	 * {@see \Ink\Training\Hub::excerptFor()}.
	 *
	 * @param \WP_Post $post The library item.
	 * @return string
	 */
	private static function excerptFor( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			return (string) get_the_excerpt( $post );
		}

		return (string) wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 24, '…' );
	}

	/**
	 * The item's cover-image markup, resolved via WP core (already-escaped,
	 * responsive `srcset`/`sizes` included) — '' when the item has no featured
	 * image. Impure. See class docblock for why this is biblioteek-specific
	 * (`Hub::card()` has no equivalent — no image-crop-parity note on that page).
	 *
	 * @param \WP_Post $post The library item.
	 * @return string
	 */
	private static function imageHtml( \WP_Post $post ): string {
		if ( ! has_post_thumbnail( $post ) ) {
			return '';
		}

		return (string) get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy' ) );
	}

	/**
	 * Reading-time whole minutes from a body's word count. Pure — see
	 * {@see self::WORDS_PER_MINUTE}'s docblock for why this is a local copy.
	 *
	 * @param string $text The body text (markup already stripped by the caller).
	 * @return int Whole minutes, floored at 1 for any non-empty body, 0 for none.
	 */
	private static function readMinutes( string $text ): int {
		$words = (int) preg_match_all( '/\S+/u', $text );

		if ( $words <= 0 ) {
			return 0;
		}

		return max( 1, (int) ceil( $words / self::WORDS_PER_MINUTE ) );
	}

	/**
	 * The Afrikaans read-time label for a minute count ("8 min"). Pure (formatter
	 * only) — the invariant abbreviation doesn't inflect, mirrors
	 * {@see \Ink\Training\Hub::readTimeLabel()}.
	 *
	 * @param int $minutes The whole-minute estimate.
	 * @return string e.g. "8 min". Empty string when there is no read-time.
	 */
	private static function readTimeLabel( int $minutes ): string {
		if ( $minutes <= 0 ) {
			return '';
		}

		/* translators: %d: the estimated reading time in whole minutes. */
		return sprintf( _n( '%d min', '%d min', $minutes, 'ink-core' ), $minutes );
	}

	/**
	 * A decorative inline Lucide icon (§0.9): 16px, currentColor, aria-hidden. Pure.
	 * `$paths` is a trusted class-internal SVG literal (never user input). Mirrors
	 * {@see \Ink\Training\Hub::icon()} (each module keeps its own copy — no shared
	 * Kernel icon helper exists yet).
	 *
	 * @param string $paths The inner SVG markup.
	 * @return string
	 */
	private static function icon( string $paths ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
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
		$search_term  = isset( $nav['search'] ) ? (string) $nav['search'] : '';
		$active_genre = $nav['genre'] ?? null;

		// The heading + search sit together in an intro band (Library-layout parity —
		// mirrors Lovable's Library.tsx header section, and Training\Hub::toHtml()'s
		// established order); the featured shelf, then the genre filter, follow below.
		$intro = '<div class="ink-biblioteek__intro">'
			. '<h1 class="ink-biblioteek__heading">' . esc_html( Terms::label( 'biblioteek' ) ) . '</h1>'
			. self::searchHtml( $search_term, $active_genre )
			. '</div>';

		$controls = self::featuredHtml( $featured ) . self::filterHtml( $genres, $active_genre );

		if ( array() === $cards ) {
			/* translators: %s: the Biblioteek label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'biblioteek' ) );

			$is_filtered = ( null !== $active_genre ) || ( '' !== $search_term );
			$clear       = '';

			if ( $is_filtered ) {
				$clear_url = (string) remove_query_arg( array( self::GENRE_VAR, self::SEARCH_VAR, self::PAGED_VAR ) );
				$clear     = '<a class="ink-biblioteek__leeg-skoon" href="' . esc_url( $clear_url ) . '">'
					. esc_html__( 'Vee filters uit', 'ink-core' ) . '</a>';
			}

			return '<section class="ink-biblioteek alignwide">' . $intro . $controls
				. '<div class="ink-biblioteek__leeg"><p class="ink-biblioteek__leeg-teks">' . esc_html( $empty ) . '</p>'
				. $clear . '</div></section>';
		}

		$html = '<section class="ink-biblioteek alignwide">' . $intro . $controls . '<ul class="ink-biblioteek__list">';

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
			. '<span class="ink-biblioteek__soek-ikoon">' . self::icon( self::ICON_SEARCH ) . '</span>'
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
	 * One library card. Pure — escaping only (the `image` value is pre-resolved,
	 * already-escaped WP core markup — see {@see self::imageHtml()} — echoed
	 * verbatim, the same trust class as {@see self::icon()}'s SVG).
	 *
	 * Renders the item's `genre` term as the card badge (AC: title → permalink,
	 * genre badge, author) plus, when present, a cover image, an excerpt and a
	 * read-time (mirrors {@see \Ink\Training\Hub::cardHtml()}'s shape); each is
	 * simply omitted when the item carries none. The "Lees" link is a decorative,
	 * `aria-hidden`/`tabindex="-1"` duplicate of the title's own href (a
	 * hover-reveal affordance only — the title link is the one real, focusable
	 * path, so a screen-reader/keyboard user never meets two identical links).
	 *
	 * Dropped the dead `is-style-card` class from `<li>` markup (that WP core block
	 * style targets `.wp-block-group`, never matched a bare `<li>` — mirrors the
	 * same no-op fix already made in {@see \Ink\Training\Hub::cardHtml()}).
	 *
	 * @param array{title?:string, permalink?:string, author?:string, genre?:string, excerpt?:string, read_minutes?:int, image?:string} $card  The item.
	 * @param string                                                                                                                     $extra Optional extra CSS class.
	 * @return string
	 */
	private static function cardHtml( array $card, string $extra = '' ): string {
		$class = 'ink-biblioteek__item' . ( '' !== $extra ? ' ' . $extra : '' );

		$title     = (string) ( $card['title'] ?? '' );
		$permalink = (string) ( $card['permalink'] ?? '' );
		$author    = (string) ( $card['author'] ?? '' );
		$genre     = (string) ( $card['genre'] ?? '' );
		$excerpt   = (string) ( $card['excerpt'] ?? '' );
		$image     = (string) ( $card['image'] ?? '' );
		$read_time = self::readTimeLabel( (int) ( $card['read_minutes'] ?? 0 ) );

		$image_html = '' !== $image
			? '<div class="ink-biblioteek__item-beeld">' . $image . '</div>'
			: '';

		$meta = '';

		if ( '' !== $genre || '' !== $read_time ) {
			$meta = '<div class="ink-biblioteek__item-meta">';

			if ( '' !== $genre ) {
				$meta .= '<span class="ink-biblioteek__genre">' . esc_html( $genre ) . '</span>';
			}

			if ( '' !== $read_time ) {
				$meta .= '<span class="ink-biblioteek__item-leestyd">' . self::icon( self::ICON_CLOCK )
					. '<span>' . esc_html( $read_time ) . '</span></span>';
			}

			$meta .= '</div>';
		}

		$excerpt_html = '' !== $excerpt
			? '<p class="ink-biblioteek__item-uittreksel">' . esc_html( $excerpt ) . '</p>'
			: '';

		return '<li class="' . esc_attr( $class ) . '">'
			. $image_html
			. '<div class="ink-biblioteek__item-inhoud">'
			. $meta
			. '<a class="ink-biblioteek__titel" href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a>'
			. $excerpt_html
			. '<div class="ink-biblioteek__item-voet">'
			. '<span class="ink-biblioteek__outeur">' . esc_html( $author ) . '</span>'
			. '<a class="ink-biblioteek__lees" href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">'
			. esc_html__( 'Lees', 'ink-core' ) . self::icon( self::ICON_ARROW ) . '</a>'
			. '</div></div></li>';
	}
}
