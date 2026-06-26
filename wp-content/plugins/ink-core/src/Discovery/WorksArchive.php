<?php
/**
 * Ontdek works-archive server block — Story 8.1 (FR-32).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;
use Ink\Engagement\Api as EngagementApi;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/ontdek-werke` block: the Ontdek works archive.
 *
 * Lists published bydraes (`gedig`/`storie`/`artikel` — the `skryfwerk` migration
 * bucket is never reader-facing), newest-first, paginated, with optional
 * year/month date-archive browse. Reads stay SERVER-RENDERED via `WP_Query`
 * (AD-7 — no REST for discovery listings), mirroring the {@see \Ink\Engagement\SuggestedReads}
 * house style: pure {@see self::queryArgs()} + pure {@see self::toHtml()} + a thin
 * {@see self::render()}.
 *
 * The type filter + the Nuut/Opspraakwekkend/Mees geliefd sorts are Story 8.2;
 * this story is the hub shell + default newest-first listing. Conflation-clean:
 * references only `Ink\Content\PostTypes` (the migration-load-bearing slug source)
 * + the `Terms` registry + WP core — zero `Ink\Tiers`/`Ink\Entitlement`. Browsing
 * published work is open (never entitlement-gated).
 *
 * @package Ink\Core
 */
final class WorksArchive {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/ontdek-werke';

	/**
	 * Works per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 12;

	/**
	 * Custom paged query var — avoids colliding with WP page pagination on the
	 * host page.
	 *
	 * @var string
	 */
	public const PAGED_VAR = 'werke_bladsy';

	/**
	 * Year archive-browse query var.
	 *
	 * @var string
	 */
	public const YEAR_VAR = 'werke_jaar';

	/**
	 * Month archive-browse query var.
	 *
	 * @var string
	 */
	public const MONTH_VAR = 'werke_maand';

	/**
	 * Type-filter query var (a readable bydrae slug, or absent for "Alles").
	 *
	 * @var string
	 */
	public const TYPE_VAR = 'werke_tipe';

	/**
	 * Sort query var.
	 *
	 * @var string
	 */
	public const SORT_VAR = 'werke_sorteer';

	/**
	 * Sort: newest first (default).
	 *
	 * @var string
	 */
	public const SORT_NUUT = 'nuut';

	/**
	 * Sort: trending (stored `ink_trending_score`, descending).
	 *
	 * @var string
	 */
	public const SORT_OPSPRAAK = 'opspraakwekkend';

	/**
	 * Sort: most reactions (denormalized `ink_reaksie_telling`, descending).
	 *
	 * @var string
	 */
	public const SORT_GELIEFD = 'mees_geliefd';

	/**
	 * The valid sort keys (anything else degrades to {@see self::SORT_NUUT}).
	 *
	 * @return list<string>
	 */
	public static function allowedSorts(): array {
		return array( self::SORT_NUUT, self::SORT_OPSPRAAK, self::SORT_GELIEFD );
	}

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/ontdek-werke` dynamic block.
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
	 * The readable bydrae types for the archive (skryfwerk bucket excluded).
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * Build the `WP_Query` args for the newest-first works archive. Pure.
	 *
	 * A `date_query` is added only for a sane 4-digit year (and only carries the
	 * month for 1–12), so a hostile/garbage query string degrades to the
	 * unfiltered newest-first listing rather than an empty or broken query.
	 *
	 * @param int         $paged    The requested page (clamped to >= 1).
	 * @param int         $per_page Works per page.
	 * @param int|null    $year     Optional archive year.
	 * @param int|null    $month    Optional archive month (1–12; ignored without a year).
	 * @param string|null $type     Optional single readable type (else all three).
	 * @param string      $sort     One of {@see self::allowedSorts()} (else nuut).
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page, ?int $year, ?int $month, ?string $type = null, string $sort = self::SORT_NUUT ): array {
		$types = ( null !== $type && in_array( $type, self::readableTypes(), true ) )
			? array( $type )
			: self::readableTypes();

		$sort = in_array( $sort, self::allowedSorts(), true ) ? $sort : self::SORT_NUUT;

		$args = array(
			'post_type'           => $types,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
		);

		$args = array_merge( $args, self::sortArgs( $sort ) );

		$clause = self::dateClause( $year, $month );

		if ( array() !== $clause ) {
			$args['date_query'] = array( $clause );
		}

		return $args;
	}

	/**
	 * The `orderby`/`meta_key` args for a (validated) sort key. Pure.
	 *
	 * The count sorts order by indexed post-meta (AD-7 — denormalized, never a
	 * live COUNT join) with date as the stable tiebreaker; `nuut` is plain date.
	 *
	 * @param string $sort A validated sort key.
	 * @return array<string, mixed>
	 */
	private static function sortArgs( string $sort ): array {
		switch ( $sort ) {
			case self::SORT_GELIEFD:
				return array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- denormalized, indexed sort meta (AD-7); not a live COUNT.
					'meta_key' => EngagementApi::reactionTotalMetaKey(),
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'date'           => 'DESC',
					),
				);
			case self::SORT_OPSPRAAK:
				return array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- stored, indexed trending score (AD-7); not computed live.
					'meta_key' => TrendingScore::META_KEY,
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'date'           => 'DESC',
					),
				);
			case self::SORT_NUUT:
			default:
				return array(
					'orderby' => 'date',
					'order'   => 'DESC',
				);
		}
	}

	/**
	 * One `date_query` clause for a valid year (+ optional 1–12 month), else [].
	 *
	 * @param int|null $year  The archive year.
	 * @param int|null $month The archive month.
	 * @return array<string, int>
	 */
	private static function dateClause( ?int $year, ?int $month ): array {
		if ( null === $year || $year < 1000 || $year > 9999 ) {
			return array();
		}

		$clause = array( 'year' => $year );

		if ( null !== $month && $month >= 1 && $month <= 12 ) {
			$clause['month'] = $month;
		}

		return $clause;
	}

	/**
	 * Block render callback. Reads the browse inputs defensively, queries, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$paged    = self::requestInt( self::PAGED_VAR, 1 );
		$year     = self::requestInt( self::YEAR_VAR, 0 );
		$month    = self::requestInt( self::MONTH_VAR, 0 );
		$type_raw = self::requestKey( self::TYPE_VAR );
		$sort_raw = self::requestKey( self::SORT_VAR );

		// Normalise to the active values the controls highlight (and the query uses).
		$active_type = in_array( $type_raw, self::readableTypes(), true ) ? $type_raw : null;
		$active_sort = in_array( $sort_raw, self::allowedSorts(), true ) ? $sort_raw : self::SORT_NUUT;

		$query = new \WP_Query(
			self::queryArgs(
				$paged,
				self::PER_PAGE,
				$year > 0 ? $year : null,
				$month > 0 ? $month : null,
				$active_type,
				$active_sort
			)
		);

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

		return self::toHtml(
			$cards,
			array(
				'paged'     => max( 1, $paged ),
				'max_pages' => (int) $query->max_num_pages,
				'type'      => $active_type,
				'sort'      => $active_sort,
			)
		);
	}

	/**
	 * Read an absint browse input (custom query var, falling back to GET).
	 *
	 * Read-only navigation (idempotent GET — the listing never mutates state), so
	 * no nonce applies; `filter_input()` reads GET without touching the superglobal
	 * and sanitises to digits before `absint()`.
	 *
	 * @param string $key      The query-var / GET key.
	 * @param int    $fallback Returned when the input is absent.
	 * @return int
	 */
	private static function requestInt( string $key, int $fallback ): int {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_NUMBER_INT );
		}

		if ( null === $value || false === $value || '' === $value ) {
			return $fallback;
		}

		return absint( $value );
	}

	/**
	 * Read a sanitised key-style browse input (query var, falling back to GET).
	 *
	 * Read-only navigation; `sanitize_key` reduces to `[a-z0-9_]` and the caller
	 * validates against an allowlist (type / sort), so an unknown value degrades
	 * to the default rather than reaching the query.
	 *
	 * @param string $key The query-var / GET key.
	 * @return string The sanitised value, or '' when absent.
	 */
	private static function requestKey( string $key ): string {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key );
		}

		return ( is_string( $value ) && '' !== $value ) ? sanitize_key( $value ) : '';
	}

	/**
	 * Build the archive HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, type:string, author:string}> $cards The works.
	 * @param array{paged:int, max_pages:int, type?:string|null, sort?:string}        $nav   Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav ): string {
		$heading  = '<h1 class="ink-ontdek-werke__heading">' . esc_html( Terms::label( 'bydrae_plural' ) ) . '</h1>';
		$controls = self::controlsHtml(
			$nav['type'] ?? null,
			isset( $nav['sort'] ) ? (string) $nav['sort'] : self::SORT_NUUT
		);

		if ( array() === $cards ) {
			/* translators: %s: the plural bydraes label (e.g. Bydraes). */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'bydrae_plural' ) );

			return '<section class="ink-ontdek-werke">' . $heading . $controls
				. '<p class="ink-ontdek-werke__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-ontdek-werke">' . $heading . $controls . '<ul class="ink-ontdek-werke__list">';

		foreach ( $cards as $card ) {
			$html .= '<li class="ink-ontdek-werke__item is-style-card">'
				. '<span class="ink-ontdek-werke__type">' . esc_html( Terms::label( $card['type'] ) ) . '</span>'
				. '<a class="ink-ontdek-werke__title" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
				. '<span class="ink-ontdek-werke__author">' . esc_html( $card['author'] ) . '</span>'
				. '</li>';
		}

		$html .= '</ul>' . self::paginationHtml( $nav ) . '</section>';

		return $html;
	}

	/**
	 * The type-filter pills + sort control. Pure — escaping + URL builders only.
	 *
	 * Each control is a GET link that preserves the OTHER dimension (sort is kept
	 * when changing type, and vice-versa) and resets the page; the active type and
	 * sort are visually marked.
	 *
	 * @param string|null $active_type The active single type, or null for "Alles".
	 * @param string      $active_sort The active sort key.
	 * @return string
	 */
	public static function controlsHtml( ?string $active_type, string $active_sort ): string {
		$html = '<div class="ink-ontdek-werke__kontroles">';

		// Type filter: Alles + one pill per readable type.
		$html .= '<div class="ink-ontdek-werke__filter">';

		$alles_active = ( null === $active_type );
		$html        .= self::pill(
			esc_url( remove_query_arg( array( self::TYPE_VAR, self::PAGED_VAR ) ) ),
			__( 'Alles', 'ink-core' ),
			$alles_active,
			'ink-ontdek-werke__filter-knoppie'
		);

		foreach ( self::readableTypes() as $type ) {
			$url   = esc_url( add_query_arg( self::TYPE_VAR, $type, remove_query_arg( self::PAGED_VAR ) ) );
			$html .= self::pill(
				$url,
				Terms::label( $type . '_plural' ),
				$type === $active_type,
				'ink-ontdek-werke__filter-knoppie'
			);
		}

		$html .= '</div>';

		// Sort control: the three options.
		$html .= '<div class="ink-ontdek-werke__sorteer">';

		foreach ( self::allowedSorts() as $sort ) {
			$url   = esc_url( add_query_arg( self::SORT_VAR, $sort, remove_query_arg( self::PAGED_VAR ) ) );
			$html .= self::pill(
				$url,
				self::sortLabel( $sort ),
				$sort === $active_sort,
				'ink-ontdek-werke__sorteer-knoppie'
			);
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * One control link, marked active when selected. Pure.
	 *
	 * @param string $url       The escaped href.
	 * @param string $label     The (unescaped) label.
	 * @param bool   $is_active Whether this is the active option.
	 * @param string $base      The base CSS class.
	 * @return string
	 */
	private static function pill( string $url, string $label, bool $is_active, string $base ): string {
		$class = $base . ( $is_active ? ' is-active' : '' );

		return '<a class="' . esc_attr( $class ) . '"'
			. ( $is_active ? ' aria-current="true"' : '' )
			. ' href="' . $url . '">' . esc_html( $label ) . '</a>';
	}

	/**
	 * The Afrikaans label for a sort key.
	 *
	 * Authored source copy (ui-copy-translations.md) — Nuut / Opspraakwekkend /
	 * Mees geliefd; copy-debt to ratify into the glossary on the next pass.
	 *
	 * @param string $sort A sort key.
	 * @return string
	 */
	private static function sortLabel( string $sort ): string {
		switch ( $sort ) {
			case self::SORT_OPSPRAAK:
				return __( 'Opspraakwekkend', 'ink-core' );
			case self::SORT_GELIEFD:
				return __( 'Mees geliefd', 'ink-core' );
			case self::SORT_NUUT:
			default:
				return __( 'Nuut', 'ink-core' );
		}
	}

	/**
	 * Prev/next archive-browse links — only when more than one page. Pure.
	 *
	 * @param array{paged:int, max_pages:int} $nav Pagination context.
	 * @return string
	 */
	private static function paginationHtml( array $nav ): string {
		$max   = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;
		$paged = isset( $nav['paged'] ) ? max( 1, (int) $nav['paged'] ) : 1;

		if ( $max <= 1 ) {
			return '';
		}

		$html = '<nav class="ink-ontdek-werke__blaai">';

		if ( $paged > 1 ) {
			$html .= '<a class="ink-ontdek-werke__vorige" href="' . esc_url( self::pageUrl( $paged - 1 ) ) . '">'
				. esc_html__( 'Vorige', 'ink-core' ) . '</a>';
		}

		if ( $paged < $max ) {
			$html .= '<a class="ink-ontdek-werke__volgende" href="' . esc_url( self::pageUrl( $paged + 1 ) ) . '">'
				. esc_html__( 'Volgende', 'ink-core' ) . '</a>';
		}

		$html .= '</nav>';

		return $html;
	}

	/**
	 * Build the URL for a given page, preserving the rest of the query string.
	 *
	 * @param int $page The target page.
	 * @return string
	 */
	private static function pageUrl( int $page ): string {
		return (string) add_query_arg( self::PAGED_VAR, max( 1, $page ) );
	}
}
