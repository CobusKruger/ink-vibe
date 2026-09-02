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
use Ink\Kernel\QaFixture;
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
	 * Overridable seam: when a filter callback returns true, QA-fixture-titled
	 * `opleiding_artikel` posts (see {@see QaFixture}) are INCLUDED in both the
	 * featured shelf and the main listing instead of excluded (the default). This
	 * block has no `*_FILTER` data seam to hook (unlike {@see \Ink\Discovery\FeaturedStream}'s
	 * memoised array) — it reads a live `WP_Query` directly, so a fixture-titled post
	 * leaks onto the real `/opleiding/` page exactly like {@see \Ink\Sponsors\HomepageStrip}'s
	 * sponsor strip did before its own Epic-19 theme-fidelity fix. The theme gates its
	 * own override to the QA/component gallery page only, mirroring
	 * {@see \Ink\Sponsors\HomepageStrip::INCLUDE_FIXTURES_FILTER}.
	 *
	 * @var string
	 */
	public const INCLUDE_FIXTURES_FILTER = 'ink_opleiding_argief_include_fixtures';

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
	 * Assumed reading pace (words per minute) for the card read-time estimate.
	 *
	 * A LOCAL copy of {@see \Ink\Discovery\ReadingTime}'s rule, not a cross-module
	 * import — `deptrac.yaml` allows `Training` only `Kernel` + `Content`, so a
	 * `Training -> Discovery` edge is not available. Mirrors the precedent
	 * `ReadingTime`'s own docblock documents (its Submission-counters word-count
	 * regex is likewise a dependency-free local copy, not a cross-module import).
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
	 * The decorative clock-icon path (read-time, mirrors Discovery\FeaturedStream).
	 *
	 * @var string
	 */
	private const ICON_CLOCK = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';

	/**
	 * The decorative arrow-icon path (card "Lees" affordance, mirrors
	 * Challenges\CurrentChallenge's ICON_ARROW).
	 *
	 * @var string
	 */
	private const ICON_ARROW = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * The decorative library-icon path (intro eyebrow badge; Lucide "library" glyph,
	 * §0.9 icon convention — matches Lovable's Library.tsx eyebrow exactly).
	 *
	 * @var string
	 */
	private const ICON_LIBRARY = '<path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/>';

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

		$query = self::runQuery( self::queryArgs( $paged, self::PER_PAGE, $active_facet, $search ) );

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
			$featured_query = self::runQuery( self::featuredArgs( self::FEATURED ) );

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
				'total'       => (int) $query->found_posts,
				'vaardigheid' => $active_facet,
				'search'      => $search,
			)
		);
	}

	/**
	 * Run a `WP_Query`, excluding QA-fixture-titled posts by default (Epic-19
	 * theme-fidelity rework finding — see {@see INCLUDE_FIXTURES_FILTER}). The
	 * exclusion is applied at the SQL layer (a scoped `posts_where` filter, removed
	 * immediately after) rather than by filtering `$query->posts` in PHP, so
	 * `found_posts`/`max_num_pages` stay accurate for pagination even when fixtures
	 * are excluded. Impure (WP_Query + filter).
	 *
	 * @param array<string, mixed> $args The `WP_Query` args.
	 * @return \WP_Query
	 */
	private static function runQuery( array $args ): \WP_Query {
		if ( (bool) apply_filters( self::INCLUDE_FIXTURES_FILTER, false ) ) {
			return new \WP_Query( $args );
		}

		$exclude_fixtures = static function ( string $where, \WP_Query $wp_query ): string {
			global $wpdb;

			return $where . $wpdb->prepare(
				" AND {$wpdb->posts}.post_title NOT LIKE %s",
				$wpdb->esc_like( QaFixture::TITLE_PREFIX ) . '%'
			);
		};

		add_filter( 'posts_where', $exclude_fixtures, 10, 2 );
		$query = new \WP_Query( $args );
		remove_filter( 'posts_where', $exclude_fixtures, 10 );

		return $query;
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
	 * `excerpt`/`category`/`read_minutes` mirror the {@see \Ink\Discovery\FeaturedStream}
	 * card shape (Library-layout archetype parity, §11.1 docblock) — a trimmed excerpt,
	 * the first `vaardigheid` term name (falling back to no pill when the item carries
	 * none), and a read-time estimate from the body word count.
	 *
	 * @param \WP_Post $post The training item.
	 * @return array{title:string, permalink:string, author:string, excerpt:string, category:string, read_minutes:int}
	 */
	private static function card( \WP_Post $post ): array {
		return array(
			'title'        => get_the_title( $post ),
			'permalink'    => (string) get_permalink( $post ),
			'author'       => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			'excerpt'      => self::excerptFor( $post ),
			'category'     => self::categoryLabel( $post ),
			'read_minutes' => self::readMinutes( wp_strip_all_tags( (string) $post->post_content ) ),
		);
	}

	/**
	 * A trimmed excerpt for the card body. Impure (WP excerpt helpers).
	 *
	 * @param \WP_Post $post The training item.
	 * @return string
	 */
	private static function excerptFor( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			return (string) get_the_excerpt( $post );
		}

		return (string) wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 24, '…' );
	}

	/**
	 * The card's category pill label — the first `vaardigheid` term name, or ''
	 * when the item carries none (the pill is then simply omitted). Impure (term
	 * read).
	 *
	 * @param \WP_Post $post The training item.
	 * @return string
	 */
	private static function categoryLabel( \WP_Post $post ): string {
		$terms = get_the_terms( $post, Taxonomies::VAARDIGHEID );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term && '' !== $term->name ) {
					return $term->name;
				}
			}
		}

		return '';
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
	 * only) — the invariant abbreviation doesn't inflect, mirroring
	 * {@see \Ink\Discovery\ReadingTime::label()}.
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
	 * {@see \Ink\Discovery\FeaturedStream::icon()} (each module keeps its own copy —
	 * no shared Kernel icon helper exists yet).
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
	 * Build the hub HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, author:string, excerpt?:string, category?:string, read_minutes?:int}> $cards    The items.
	 * @param list<array{title:string, permalink:string, author:string, excerpt?:string, category?:string, read_minutes?:int}> $featured The featured strip items.
	 * @param list<array{slug:string, name:string}>                                                                           $facets   The vaardigheid facet terms.
	 * @param array{paged:int, max_pages:int, total?:int, vaardigheid?:string|null, search?:string}                           $nav Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $featured, array $facets, array $nav ): string {
		$search_term  = isset( $nav['search'] ) ? (string) $nav['search'] : '';
		$active_facet = $nav['vaardigheid'] ?? null;

		// The eyebrow + heading + intro + search sit together in an intro band
		// (Library-layout parity — mirrors Lovable's Library.tsx header section); the
		// featured shelf, then the facet filter, follow below it. The eyebrow reuses
		// the 'opleiding' section-name label (matches the curated
		// docs/ui-copy-translations.md mapping: "The Learning Library" -> "Opleiding");
		// the H1 and intro paragraph are distinct authored copy ('opleiding_h1'/
		// 'opleiding_intro').
		$intro = '<div class="ink-opleiding__intro">'
			. '<p class="ink-opleiding__eyebrow">' . self::icon( self::ICON_LIBRARY )
			. '<span>' . esc_html( Terms::label( 'opleiding' ) ) . '</span></p>'
			. '<h1 class="ink-opleiding__heading">' . esc_html( Terms::label( 'opleiding_h1' ) ) . '</h1>'
			. '<p class="ink-opleiding__intro-teks">' . esc_html( Terms::label( 'opleiding_intro' ) ) . '</p>'
			. self::searchHtml( $search_term, $active_facet )
			. '</div>';

		$controls = self::featuredHtml( $featured ) . self::filterHtml( $facets, $active_facet );

		if ( array() === $cards ) {
			$is_filtered = ( null !== $active_facet ) || ( '' !== $search_term );

			return '<section class="ink-opleiding alignwide">' . $intro . $controls
				. self::emptyStateHtml( $is_filtered ) . '</section>';
		}

		$total        = isset( $nav['total'] ) ? (int) $nav['total'] : count( $cards );
		$facet_name   = self::facetName( $facets, $active_facet );
		$result_count = '<p class="ink-opleiding__telling">'
			. esc_html( self::resultCountLabel( $total, $facet_name, $search_term ) ) . '</p>';

		$html = '<section class="ink-opleiding alignwide">' . $intro . $controls
			. $result_count . '<ul class="ink-opleiding__list">';

		foreach ( $cards as $card ) {
			$html .= self::cardHtml( $card );
		}

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		$html .= '</ul>' . ArchiveRender::pagination( $paged, $max_pages, self::CSS, self::PAGED_VAR ) . '</section>';

		return $html;
	}

	/**
	 * The active facet's display name, or '' when none is active or it isn't found
	 * among the supplied facets. Pure.
	 *
	 * @param list<array{slug:string, name:string}> $facets       The vaardigheid facet terms.
	 * @param string|null                           $active_facet The active term slug, or null.
	 * @return string
	 */
	private static function facetName( array $facets, ?string $active_facet ): string {
		if ( null === $active_facet || '' === $active_facet ) {
			return '';
		}

		foreach ( $facets as $facet ) {
			if ( $facet['slug'] === $active_facet ) {
				return $facet['name'];
			}
		}

		return '';
	}

	/**
	 * The result-count line above the grid ("N artikel(s)[ in Kategorie][ wat
	 * ooreenstem met "soekterm"]") — Library-layout parity (Lovable's
	 * `{filtered.length} article(s)...` line, docs/ui-copy-translations.md
	 * "Redakteur se rak en leë toestande" row). Pure — formatting + escaping only.
	 *
	 * @param int    $total      The matching item count (across all pages).
	 * @param string $facet_name The active facet's display name, or '' for none.
	 * @param string $search     The active search term, or '' for none.
	 * @return string
	 */
	private static function resultCountLabel( int $total, string $facet_name, string $search ): string {
		/* translators: %d: the number of matching opleiding articles. */
		$label = sprintf( _n( '%d artikel', '%d artikels', $total, 'ink-core' ), $total );

		if ( '' !== $facet_name ) {
			/* translators: %s: the active vaardigheid facet's display name. */
			$label .= ' ' . sprintf( __( 'in %s', 'ink-core' ), $facet_name );
		}

		if ( '' !== $search ) {
			/* translators: %s: the active search term. */
			$label .= ' ' . sprintf( __( 'wat ooreenstem met "%s"', 'ink-core' ), $search );
		}

		return $label;
	}

	/**
	 * "Die redakteur se rak" — the curated entry-point shelf. Pure.
	 *
	 * The recency-driven 3-piece shelf ("Drie stukke om mee te begin.") — guided
	 * starting places, curated automatically by recency (NEVER per-item manual
	 * editorial linking, Principle 8). Renders nothing without items (filtered/paged
	 * views pass none — the shelf shows only on the unfiltered first page).
	 *
	 * @param list<array{title:string, permalink:string, author:string, excerpt?:string, category?:string, read_minutes?:int}> $featured The shelf items.
	 * @return string
	 */
	public static function featuredHtml( array $featured ): string {
		if ( array() === $featured ) {
			return '';
		}

		$html = '<div class="ink-opleiding__rak">'
			. '<h2 class="ink-opleiding__rak-titel">' . esc_html__( 'Die redakteur se rak', 'ink-core' ) . '</h2>'
			. '<p class="ink-opleiding__rak-onderskrif">' . esc_html__( 'Drie stukke om mee te begin.', 'ink-core' ) . '</p>'
			. '<ul class="ink-opleiding__rak-lys">';

		foreach ( $featured as $card ) {
			$html .= self::cardHtml( $card, 'ink-opleiding__rak-item' );
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
			. '<span class="ink-opleiding__soek-ikoon">' . self::icon( self::ICON_SEARCH ) . '</span>'
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
	 * The context-aware empty state. Pure — escaping + URL builder only.
	 *
	 * A filtered view (active facet or search) that matches nothing invites the
	 * reader to broaden — "Probeer 'n ander soekterm of blaai deur alle artikels."
	 * plus a "Vee filters uit" link that clears every browse var back to the clean
	 * hub. An unfiltered hub with no content shows "Nog niks op hierdie rak nie."
	 *
	 * @param bool $is_filtered Whether a facet or search is active.
	 * @return string
	 */
	private static function emptyStateHtml( bool $is_filtered ): string {
		if ( ! $is_filtered ) {
			return '<p class="ink-opleiding__leeg">' . esc_html__( 'Nog niks op hierdie rak nie.', 'ink-core' ) . '</p>';
		}

		$clear_url = (string) remove_query_arg( array( self::VAARDIGHEID_VAR, self::SEARCH_VAR, self::PAGED_VAR ) );

		return '<div class="ink-opleiding__leeg">'
			. '<p class="ink-opleiding__leeg-teks">' . esc_html__( 'Probeer \'n ander soekterm of blaai deur alle artikels.', 'ink-core' ) . '</p>'
			. '<a class="ink-opleiding__leeg-skoon" href="' . esc_url( $clear_url ) . '">' . esc_html__( 'Vee filters uit', 'ink-core' ) . '</a>'
			. '</div>';
	}

	/**
	 * One training card. Pure — formatters + escaping only.
	 *
	 * `excerpt`/`category`/`read_minutes` are optional (a caller — e.g. the unit
	 * tests, or a future data seam — may pass a bare `title`/`permalink`/`author`
	 * row); each is simply omitted from the markup when absent. The "Lees" link is
	 * a decorative, `aria-hidden`/`tabindex="-1"` duplicate of the title's own href
	 * (a hover-reveal affordance only — the title link is the one real, focusable
	 * path to the item, so a screen-reader/keyboard user never meets two identical
	 * links).
	 *
	 * @param array{title?:string, permalink?:string, author?:string, excerpt?:string, category?:string, read_minutes?:int} $card  The item.
	 * @param string                                                                                                        $extra Optional extra CSS class (e.g. the "rak" shelf modifier).
	 * @return string
	 */
	private static function cardHtml( array $card, string $extra = '' ): string {
		$class = 'ink-opleiding__item' . ( '' !== $extra ? ' ' . $extra : '' );

		$title     = (string) ( $card['title'] ?? '' );
		$permalink = (string) ( $card['permalink'] ?? '' );
		$author    = (string) ( $card['author'] ?? '' );
		$excerpt   = (string) ( $card['excerpt'] ?? '' );
		$category  = (string) ( $card['category'] ?? '' );
		$read_time = self::readTimeLabel( (int) ( $card['read_minutes'] ?? 0 ) );

		$meta = '';

		if ( '' !== $category || '' !== $read_time ) {
			$meta = '<div class="ink-opleiding__item-meta">';

			if ( '' !== $category ) {
				$meta .= '<span class="ink-opleiding__item-pil">' . esc_html( $category ) . '</span>';
			}

			if ( '' !== $read_time ) {
				$meta .= '<span class="ink-opleiding__item-leestyd">' . self::icon( self::ICON_CLOCK )
					. '<span>' . esc_html( $read_time ) . '</span></span>';
			}

			$meta .= '</div>';
		}

		$excerpt_html = '' !== $excerpt
			? '<p class="ink-opleiding__item-uittreksel">' . esc_html( $excerpt ) . '</p>'
			: '';

		return '<li class="' . esc_attr( $class ) . '">'
			. $meta
			. '<a class="ink-opleiding__titel" href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a>'
			. $excerpt_html
			. '<div class="ink-opleiding__item-voet">'
			. '<span class="ink-opleiding__outeur">' . esc_html( $author ) . '</span>'
			. '<a class="ink-opleiding__lees" href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">'
			. esc_html__( 'Lees', 'ink-core' ) . self::icon( self::ICON_ARROW ) . '</a>'
			. '</div></li>';
	}
}
