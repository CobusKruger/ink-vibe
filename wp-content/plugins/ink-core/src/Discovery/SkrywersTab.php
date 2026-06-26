<?php
/**
 * Ontdek skrywers-tab server block — Story 8.3 (FR-34, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\I18n\Terms;
use Ink\Tiers\Api as TiersApi;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/ontdek-skrywers` block: the Ontdek skrywers tab.
 *
 * A SERVER-RENDERED `WP_User_Query` (AD-7 — core Query Loop cannot query users)
 * over the writers (members with a first-publication), filterable by genre
 * (Digkuns/Prosa/Artikels — the FORM of their published work, via the
 * {@see SkrywerIndex} denormalized flags) and sortable by Meeste gelees (read
 * total) / Nuwe stemme (first-publish recency). Each card shows the writer's
 * Gradering (read via the {@see TiersApi} facade — display only).
 *
 * Conflation-clean: reads the Tiers Api for DISPLAY, never gates discovery on a
 * tier, and carries zero `Ink\Entitlement`. Not entitlement-gated.
 *
 * @package Ink\Core
 */
final class SkrywersTab {

	public const BLOCK = 'ink/ontdek-skrywers';

	public const PER_PAGE = 12;

	public const GENRE_VAR = 'skrywer_genre';
	public const SORT_VAR  = 'skrywer_sorteer';
	public const PAGED_VAR = 'skrywer_bladsy';

	/**
	 * Sort: most-read writers (denormalized read total, descending).
	 *
	 * @var string
	 */
	public const SORT_GELEES = 'meeste_gelees';

	/**
	 * Sort: new voices (most-recent first publication, descending).
	 *
	 * @var string
	 */
	public const SORT_NUWE = 'nuwe_stemme';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/ontdek-skrywers` dynamic block.
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
	 * The valid genre filters (anything else → unfiltered).
	 *
	 * @return list<string>
	 */
	public static function allowedGenres(): array {
		return array( 'digkuns', 'prosa', 'artikels' );
	}

	/**
	 * The valid sort keys (anything else → {@see self::SORT_NUWE}).
	 *
	 * @return list<string>
	 */
	public static function allowedSorts(): array {
		return array( self::SORT_NUWE, self::SORT_GELEES );
	}

	/**
	 * Build the `WP_User_Query` args. Pure — unit-testable without WordPress.
	 *
	 * Always carries a `writer` clause (EXISTS on the first-publication meta) so
	 * only writers appear; a genre filter adds the form-flag clause; the sort is a
	 * named NUMERIC clause ordered DESC (Nuwe stemme = first-publish recency,
	 * Meeste gelees = read total).
	 *
	 * @param string|null $genre    A genre filter, or null for all writers.
	 * @param string      $sort     One of {@see self::allowedSorts()}.
	 * @param int         $paged    The page (clamped to >= 1).
	 * @param int         $per_page Writers per page.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( ?string $genre, string $sort, int $paged, int $per_page ): array {
		$sort = in_array( $sort, self::allowedSorts(), true ) ? $sort : self::SORT_NUWE;

		$meta_query = array(
			'relation' => 'AND',
			'writer'   => array(
				'key'     => SkrywerIndex::FIRST_PUBLISH_META,
				'compare' => 'EXISTS',
			),
		);

		$type = ( null !== $genre ) ? SkrywerIndex::genreToType( $genre ) : null;

		if ( null !== $type ) {
			$meta_query['vorm'] = array(
				'key'   => SkrywerIndex::formFlagKey( $type ),
				'value' => '1',
			);
		}

		$sort_key = ( self::SORT_GELEES === $sort )
			? SkrywerIndex::READ_TOTAL_META
			: SkrywerIndex::FIRST_PUBLISH_META;

		$meta_query['sorteer'] = array(
			'key'     => $sort_key,
			'type'    => 'NUMERIC',
			'compare' => 'EXISTS',
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- discovery-scoped, paged WP_User_Query on indexed denorm meta (AD-7).
			'meta_query' => $meta_query,
			'orderby'    => array( 'sorteer' => 'DESC' ),
			'number'     => $per_page,
			'paged'      => max( 1, $paged ),
			'fields'     => 'ID',
		);
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$genre_raw = self::requestKey( self::GENRE_VAR );
		$sort_raw  = self::requestKey( self::SORT_VAR );
		$paged     = self::requestInt( self::PAGED_VAR, 1 );

		$active_genre = in_array( $genre_raw, self::allowedGenres(), true ) ? $genre_raw : null;
		$active_sort  = in_array( $sort_raw, self::allowedSorts(), true ) ? $sort_raw : self::SORT_NUWE;

		$query = new \WP_User_Query( self::queryArgs( $active_genre, $active_sort, $paged, self::PER_PAGE ) );

		$cards = array();

		foreach ( array_map( 'intval', (array) $query->get_results() ) as $uid ) {
			if ( $uid <= 0 ) {
				continue;
			}

			$cards[] = array(
				'name'        => (string) get_the_author_meta( 'display_name', $uid ),
				'profile_url' => (string) get_author_posts_url( $uid ),
				'gradering'   => Terms::label( TiersApi::forUser( $uid )->value ),
				'bio'         => (string) get_the_author_meta( 'description', $uid ),
			);
		}

		$total     = (int) $query->get_total();
		$max_pages = (int) ceil( $total / self::PER_PAGE );

		return self::toHtml(
			$cards,
			array(
				'paged'     => max( 1, $paged ),
				'max_pages' => $max_pages,
				'genre'     => $active_genre,
				'sort'      => $active_sort,
			)
		);
	}

	/**
	 * Read an absint browse input (query var, falling back to GET).
	 *
	 * @param string $key      The key.
	 * @param int    $fallback Returned when absent.
	 * @return int
	 */
	private static function requestInt( string $key, int $fallback ): int {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_NUMBER_INT );
		}

		return ( null === $value || false === $value || '' === $value ) ? $fallback : absint( $value );
	}

	/**
	 * Read a sanitised key-style browse input (query var, falling back to GET).
	 *
	 * @param string $key The key.
	 * @return string
	 */
	private static function requestKey( string $key ): string {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key );
		}

		return ( is_string( $value ) && '' !== $value ) ? sanitize_key( $value ) : '';
	}

	/**
	 * Build the skrywers HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{name:string, profile_url:string, gradering:string, bio:string}> $cards The writers.
	 * @param array{paged:int, max_pages:int, genre?:string|null, sort?:string}          $nav   Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav ): string {
		$heading  = '<h2 class="ink-ontdek-skrywers__heading">' . esc_html( Terms::label( 'skrywer_plural' ) ) . '</h2>';
		$controls = self::controlsHtml(
			$nav['genre'] ?? null,
			isset( $nav['sort'] ) ? (string) $nav['sort'] : self::SORT_NUWE
		);

		if ( array() === $cards ) {
			/* translators: %s: the plural skrywers label (e.g. Skrywers). */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'skrywer_plural' ) );

			return '<section class="ink-ontdek-skrywers">' . $heading . $controls
				. '<p class="ink-ontdek-skrywers__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-ontdek-skrywers">' . $heading . $controls . '<ul class="ink-ontdek-skrywers__list">';

		foreach ( $cards as $card ) {
			$html .= '<li class="ink-ontdek-skrywers__item is-style-card">'
				. '<a class="ink-ontdek-skrywers__naam" href="' . esc_url( $card['profile_url'] ) . '">' . esc_html( $card['name'] ) . '</a>'
				. '<span class="ink-ontdek-skrywers__gradering">' . esc_html( $card['gradering'] ) . '</span>'
				. '<p class="ink-ontdek-skrywers__bio">' . esc_html( $card['bio'] ) . '</p>'
				. '</li>';
		}

		$html .= '</ul>' . self::paginationHtml( $nav ) . '</section>';

		return $html;
	}

	/**
	 * The genre pills + sort control. Pure — escaping + URL builders only.
	 *
	 * @param string|null $active_genre The active genre, or null for "Almal".
	 * @param string      $active_sort  The active sort key.
	 * @return string
	 */
	public static function controlsHtml( ?string $active_genre, string $active_sort ): string {
		$html = '<div class="ink-ontdek-skrywers__kontroles"><div class="ink-ontdek-skrywers__filter">';

		$html .= self::pill(
			esc_url( remove_query_arg( array( self::GENRE_VAR, self::PAGED_VAR ) ) ),
			__( 'Almal', 'ink-core' ),
			( null === $active_genre ),
			'ink-ontdek-skrywers__filter-knoppie'
		);

		foreach ( self::allowedGenres() as $genre ) {
			$url   = esc_url( add_query_arg( self::GENRE_VAR, $genre, remove_query_arg( self::PAGED_VAR ) ) );
			$html .= self::pill(
				$url,
				Terms::label( 'skrywer_genre_' . $genre ),
				$genre === $active_genre,
				'ink-ontdek-skrywers__filter-knoppie'
			);
		}

		$html .= '</div><div class="ink-ontdek-skrywers__sorteer">';

		foreach ( self::allowedSorts() as $sort ) {
			$url   = esc_url( add_query_arg( self::SORT_VAR, $sort, remove_query_arg( self::PAGED_VAR ) ) );
			$html .= self::pill(
				$url,
				self::sortLabel( $sort ),
				$sort === $active_sort,
				'ink-ontdek-skrywers__sorteer-knoppie'
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
	 * The Afrikaans label for a sort key (authored source copy — copy-debt to ratify).
	 *
	 * @param string $sort A sort key.
	 * @return string
	 */
	private static function sortLabel( string $sort ): string {
		return ( self::SORT_GELEES === $sort )
			? __( 'Meeste gelees', 'ink-core' )
			: __( 'Nuwe stemme', 'ink-core' );
	}

	/**
	 * Prev/next pagination — only when more than one page. Pure.
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

		$html = '<nav class="ink-ontdek-skrywers__blaai">';

		if ( $paged > 1 ) {
			$html .= '<a class="ink-ontdek-skrywers__vorige" href="' . esc_url( add_query_arg( self::PAGED_VAR, $paged - 1 ) ) . '">'
				. esc_html__( 'Vorige', 'ink-core' ) . '</a>';
		}

		if ( $paged < $max ) {
			$html .= '<a class="ink-ontdek-skrywers__volgende" href="' . esc_url( add_query_arg( self::PAGED_VAR, $paged + 1 ) ) . '">'
				. esc_html__( 'Volgende', 'ink-core' ) . '</a>';
		}

		$html .= '</nav>';

		return $html;
	}
}
