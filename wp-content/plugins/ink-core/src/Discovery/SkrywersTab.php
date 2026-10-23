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
				'tier'        => TiersApi::forUser( $uid )->value,
				'bio'         => (string) get_the_author_meta( 'description', $uid ),
				'avatar_url'  => (string) get_avatar_url( $uid, array( 'size' => 128 ) ),
				'genres'      => self::genreLabelsFor( $uid ),
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
	 * @param list<array{name:string, profile_url:string, gradering:string, tier?:string, bio:string, avatar_url:string, genres:list<string>}> $cards The writers.
	 * @param array{paged:int, max_pages:int, genre?:string|null, sort?:string}          $nav   Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav ): string {
		// Fidelity Stage 4 (rows 03/18, spine S10): Lovable's writers panel has no
		// visible heading (Browse.tsx:514-516); the name stays for assistive tech.
		$heading  = '<h2 class="ink-ontdek-skrywers__heading ink-heading-h2-block screen-reader-text" data-audit-id="writers-heading">' . esc_html( Terms::label( 'skrywer_plural' ) ) . '</h2>';
		$controls = self::controlsHtml(
			$nav['genre'] ?? null,
			isset( $nav['sort'] ) ? (string) $nav['sort'] : self::SORT_NUWE
		);

		if ( array() === $cards ) {
			/* translators: %s: the plural skrywers label (e.g. Skrywers). */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'skrywer_plural' ) );

			return '<section class="ink-ontdek-skrywers" data-audit-id="writers-archive">' . $heading . $controls
				. '<p class="ink-ontdek-skrywers__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-ontdek-skrywers" data-audit-id="writers-archive">' . $heading . $controls . '<ul class="ink-ontdek-skrywers__list" data-audit-id="writers-grid">';

		$first = true;

		foreach ( $cards as $card ) {
			$html .= self::cardHtml( $card, $first );
			$first = false;
		}

		$html .= '</ul>' . self::paginationHtml( $nav ) . '</section>';

		return $html;
	}

	/**
	 * A writer's genre labels — the Afrikaans labels of their published forms.
	 * Impure (user-meta reads). Mirrors {@see \Ink\Discovery\SearchIndex::rebuildSkrywer()}'s
	 * type→genre reverse-map.
	 *
	 * @param int $user_id The writer.
	 * @return list<string>
	 */
	private static function genreLabelsFor( int $user_id ): array {
		$type_to_genre = array(
			\Ink\Content\PostTypes::GEDIG   => 'digkuns',
			\Ink\Content\PostTypes::STORIE  => 'prosa',
			\Ink\Content\PostTypes::ARTIKEL => 'artikels',
		);

		$labels = array();

		foreach ( $type_to_genre as $type => $genre ) {
			if ( '1' === (string) get_user_meta( $user_id, SkrywerIndex::formFlagKey( $type ), true ) ) {
				$labels[] = Terms::label( 'skrywer_genre_' . $genre );
			}
		}

		return $labels;
	}

	/**
	 * One skrywer card. Pure — escaping only.
	 *
	 * @param array{name:string, profile_url:string, gradering:string, tier?:string, bio:string, avatar_url?:string, genres?:list<string>} $card  The card row.
	 * @param bool                                                                                                          $audit Stamp the fidelity-audit join keys (first card only).
	 * @return string
	 */
	private static function cardHtml( array $card, bool $audit = false ): string {
		$avatar = (string) ( $card['avatar_url'] ?? '' );
		$genres = isset( $card['genres'] ) && is_array( $card['genres'] ) ? $card['genres'] : array();
		$aid    = static fn ( string $id, bool $on = true ): string => ( $audit && $on ) ? ' data-audit-id="' . esc_attr( $id ) . '"' : '';

		$html = '<li class="ink-ontdek-skrywers__item ink-card"' . $aid( 'writers-card' ) . '>'
			. '<div class="ink-ontdek-skrywers__kop"' . $aid( 'writers-card-head' ) . '>';

		if ( '' !== $avatar ) {
			$html .= '<img class="ink-ontdek-skrywers__foto ink-avatar ink-avatar--64 ink-avatar--bordered"' . $aid( 'writers-card-avatar' ) . ' src="' . esc_url( $avatar ) . '" '
				. 'alt="' . esc_attr( $card['name'] ) . '" width="56" height="56" loading="lazy" decoding="async" />';
		}

		// Fidelity Stage 4 (row 03, spine S21): the name is Lovable's `font-serif
		// text-lg` (18/28, regular — Browse.tsx:724) and the genre badges sit inside
		// the head text column under the name (Browse.tsx:723-738), not below the head.
		$html .= '<div class="ink-ontdek-skrywers__kop-teks"' . $aid( 'writers-card-head-text' ) . '>'
			. '<a class="ink-ontdek-skrywers__naam ink-heading-h4 ink-heading-h4-regular"' . $aid( 'writers-card-name' ) . ' href="' . esc_url( $card['profile_url'] ) . '">' . esc_html( $card['name'] ) . '</a>'
			. '<span class="' . esc_attr( 'ink-ontdek-skrywers__gradering ' . self::tierLabelClasses( (string) ( $card['tier'] ?? '' ) ) ) . '"' . $aid( 'writers-card-grade' ) . '>' . esc_html( $card['gradering'] ) . '</span>';

		if ( array() !== $genres ) {
			$html .= '<div class="ink-ontdek-skrywers__genres"' . $aid( 'writers-card-genres' ) . '>';

			$first_genre = true;

			foreach ( $genres as $genre_label ) {
				$html       .= '<span class="ink-ontdek-skrywers__genre-pil ink-pill ink-pill--outline"' . $aid( 'writers-card-genre', $first_genre ) . '>' . esc_html( $genre_label ) . '</span>';
				$first_genre = false;
			}

			$html .= '</div>';
		}

		$html .= '</div></div>';

		if ( '' !== $card['bio'] ) {
			$html .= '<p class="ink-ontdek-skrywers__bio"' . $aid( 'writers-card-bio' ) . '>' . esc_html( $card['bio'] ) . '</p>';
		}

		return $html . '</li>';
	}

	/**
	 * The shared tier-label classes (primitives.css `ink-tier-label`, NR-1) for a
	 * Gradering value — `ink-tier-label ink-tier-label--{tier}`. Pure. A value outside
	 * the known tiers (or '') gets the bare base label. Shared by the Ontdek writer
	 * card, the search results and the discovery surfaces.
	 *
	 * @param string $tier The Gradering value (`brons`/`silwer`/`goud`/`meester`), or ''.
	 * @return string
	 */
	public static function tierLabelClasses( string $tier ): string {
		$tier = (string) preg_replace( '/[^a-z0-9_-]/', '', strtolower( $tier ) );

		return 'ink-tier-label' . ( '' !== $tier ? ' ink-tier-label--' . $tier : '' );
	}

	/**
	 * The genre pills + sort control. Pure — escaping + URL builders only.
	 *
	 * @param string|null $active_genre The active genre, or null for "Almal".
	 * @param string      $active_sort  The active sort key.
	 * @return string
	 */
	public static function controlsHtml( ?string $active_genre, string $active_sort ): string {
		$html = '<div class="ink-ontdek-skrywers__kontroles" data-audit-id="writers-controls"><div class="ink-ontdek-skrywers__filter" data-audit-id="writers-filters">';

		// Fidelity join keys by position (maps/ontdek.csv): pill 0 (active in the
		// default unfiltered state) and pill 1 (first inactive).
		$html .= self::pill(
			esc_url( remove_query_arg( array( self::GENRE_VAR, self::PAGED_VAR ) ) ),
			__( 'Almal', 'ink-core' ),
			( null === $active_genre ),
			'ink-ontdek-skrywers__filter-knoppie ink-chip',
			'writers-filter-active'
		);

		$genre_index = 0;

		foreach ( self::allowedGenres() as $genre ) {
			$url   = esc_url( add_query_arg( self::GENRE_VAR, $genre, remove_query_arg( self::PAGED_VAR ) ) );
			$html .= self::pill(
				$url,
				Terms::label( 'skrywer_genre_' . $genre ),
				$genre === $active_genre,
				'ink-ontdek-skrywers__filter-knoppie ink-chip',
				0 === $genre_index++ ? 'writers-filter' : ''
			);
		}

		$html .= '</div><div class="ink-ontdek-skrywers__sorteer" data-audit-id="writers-sort">';

		$sort_ids = array( 'writers-sort-active', 'writers-sort-pill' );

		foreach ( self::allowedSorts() as $sort_index => $sort ) {
			$url   = esc_url( add_query_arg( self::SORT_VAR, $sort, remove_query_arg( self::PAGED_VAR ) ) );
			$html .= self::pill(
				$url,
				self::sortLabel( $sort ),
				$sort === $active_sort,
				'ink-ontdek-skrywers__sorteer-knoppie ink-chip ink-chip--sort',
				$sort_ids[ $sort_index ] ?? ''
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
	 * @param string $audit_id  Optional fidelity-audit join key ('' = none).
	 * @return string
	 */
	private static function pill( string $url, string $label, bool $is_active, string $base, string $audit_id = '' ): string {
		$class = $base . ( $is_active ? ' is-active' : '' );

		return '<a class="' . esc_attr( $class ) . '"'
			. ( '' !== $audit_id ? ' data-audit-id="' . esc_attr( $audit_id ) . '"' : '' )
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

		$html = '<nav class="ink-ontdek-skrywers__blaai ink-pager">';

		if ( $paged > 1 ) {
			$html .= '<a class="ink-ontdek-skrywers__vorige ink-pager__link" href="' . esc_url( add_query_arg( self::PAGED_VAR, $paged - 1 ) ) . '">'
				. esc_html__( 'Vorige', 'ink-core' ) . '</a>';
		}

		if ( $paged < $max ) {
			$html .= '<a class="ink-ontdek-skrywers__volgende ink-pager__link" href="' . esc_url( add_query_arg( self::PAGED_VAR, $paged + 1 ) ) . '">'
				. esc_html__( 'Volgende', 'ink-core' ) . '</a>';
		}

		$html .= '</nav>';

		return $html;
	}
}
