<?php
/**
 * Ontdek search server block — Story 8.4 (FR-35, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Migration\TextKey;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/ontdek-soek` block: ranked search of works + skrywers.
 *
 * The query and the indexes kept by {@see SearchIndex} are both {@see TextKey::text()}
 * keys, so case, accents, punctuation, spacing and line endings never matter. Matches on
 * a writer's display name / nice name / user name, a work's title, and a work's body.
 * Ranking (best first): exact author name, exact title, partial author name, partial
 * title, body. A work counts as an author match when its author's name matches.
 * Server-rendered, direct `$wpdb`, no REST. Not gated.
 *
 * Conflation-clean: reads the Tiers Api for the skrywer Gradering label (display
 * only); zero `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Search {

	public const BLOCK = 'ink/ontdek-soek';

	public const QUERY_VAR = 'soek';

	private const LIMIT = 48;

	private const WORKS_LIMIT = 120;

	/**
	 * Shortest key that also searches work bodies. One or two letters sit in nearly every
	 * poem; names and titles are still matched at any length.
	 */
	private const MIN_BODY_KEY = 3;

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
	 * Register the `ink/ontdek-soek` dynamic block.
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
	 * The readable bydrae types searched (skryfwerk bucket excluded).
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( \Ink\Content\PostTypes::GEDIG, \Ink\Content\PostTypes::STORIE, \Ink\Content\PostTypes::ARTIKEL );
	}

	/**
	 * SQL for the ranked work ids. Pure given a `$wpdb`-like object (prefix/table names, `prepare`, `esc_like`).
	 *
	 * Tier per work: 0 exact author name, 1 exact title, 2 partial author name, 3 partial title,
	 * 4 body. Newest first within a tier.
	 *
	 * @param object $db    The `$wpdb`.
	 * @param string $key   The query key ({@see TextKey::spaced()}); non-empty.
	 * @param int    $limit Max results.
	 * @return string
	 */
	public static function worksSql( $db, string $key, int $limit ): string {
		$part   = '%' . $db->esc_like( $key ) . '%';
		$exact  = '%|' . $db->esc_like( $key ) . '|%';
		$types  = self::readableTypes();
		$in     = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$body   = mb_strlen( $key ) >= self::MIN_BODY_KEY ? ' OR b.meta_value LIKE %s' : '';
		$params = array_merge(
			array( $exact, $key, $part, $part, SearchIndex::SKRYWER_META, SearchIndex::TITLE_META, SearchIndex::BODY_META ),
			$types,
			array( $part, $part ),
			'' !== $body ? array( $part ) : array(),
			array( $limit )
		);

		return $db->prepare(
			"SELECT p.ID, CASE
					WHEN a.meta_value LIKE %s THEN 0
					WHEN t.meta_value = %s THEN 1
					WHEN a.meta_value LIKE %s THEN 2
					WHEN t.meta_value LIKE %s THEN 3
					ELSE 4 END AS tier
				FROM {$db->posts} p
				LEFT JOIN {$db->usermeta} a ON a.user_id = p.post_author AND a.meta_key = %s
				LEFT JOIN {$db->postmeta} t ON t.post_id = p.ID AND t.meta_key = %s
				LEFT JOIN {$db->postmeta} b ON b.post_id = p.ID AND b.meta_key = %s
				WHERE p.post_status = 'publish' AND p.post_type IN ({$in})
					AND (a.meta_value LIKE %s OR t.meta_value LIKE %s{$body})
				ORDER BY tier ASC, p.post_date DESC, p.ID DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table names and placeholder list only; every value is a placeholder.
			$params
		);
	}

	/**
	 * SQL for the ranked writer ids: exact name first, then partial, by display name. Pure given a `$wpdb`-like object.
	 *
	 * @param object $db    The `$wpdb`.
	 * @param string $key   The query key; non-empty.
	 * @param int    $limit Max results.
	 * @return string
	 */
	public static function skrywersSql( $db, string $key, int $limit ): string {
		return $db->prepare(
			"SELECT u.ID, CASE WHEN m.meta_value LIKE %s THEN 0 ELSE 1 END AS tier
				FROM {$db->users} u
				INNER JOIN {$db->usermeta} m ON m.user_id = u.ID AND m.meta_key = %s
				WHERE m.meta_value LIKE %s
				ORDER BY tier ASC, u.display_name ASC, u.ID ASC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names only.
			'%|' . $db->esc_like( $key ) . '|%',
			SearchIndex::SKRYWER_META,
			'%' . $db->esc_like( $key ) . '%',
			$limit
		);
	}

	/**
	 * The comparison key for a typed query: the same key the indexes hold. Pure.
	 *
	 * @param string $raw The typed query.
	 * @return string Empty when nothing searchable was typed.
	 */
	public static function queryKey( string $raw ): string {
		return str_replace( '|', '', TextKey::spaced( $raw ) );
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$raw = self::requestQuery();

		return self::toHtml( $raw );
	}

	/**
	 * Whether a searchable query is active (the Ontdek page is then in search mode:
	 * the Bydraes and Skrywers panels show only the matches).
	 *
	 * @return bool
	 */
	public static function isActive(): bool {
		return '' !== self::queryKey( self::requestQuery() );
	}

	/**
	 * Ids of the bydraes matching the active query, best match first. Empty without
	 * a query. Impure (DB). Memoised per request; the Bydraes panel re-sorts/pages them.
	 *
	 * @return list<int>
	 */
	public static function matchingWorkIds(): array {
		static $memo = array();

		$key = self::queryKey( self::requestQuery() );

		if ( '' === $key ) {
			return array();
		}

		if ( ! isset( $memo[ $key ] ) ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- ranked LIKE over the search index; bounded by LIMIT.
			$memo[ $key ] = array_values( array_filter( array_map( 'intval', (array) $wpdb->get_col( self::worksSql( $wpdb, $key, self::WORKS_LIMIT ) ) ) ) );
		}

		return $memo[ $key ];
	}

	/**
	 * Ids of the writers matching the active query, best match first. Empty without
	 * a query. Impure (DB). Memoised per request.
	 *
	 * @return list<int>
	 */
	public static function matchingSkrywerIds(): array {
		static $memo = array();

		$key = self::queryKey( self::requestQuery() );

		if ( '' === $key ) {
			return array();
		}

		if ( ! isset( $memo[ $key ] ) ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- as above.
			$memo[ $key ] = array_values( array_filter( array_map( 'intval', (array) $wpdb->get_col( self::skrywersSql( $wpdb, $key, self::LIMIT ) ) ) ) );
		}

		return $memo[ $key ];
	}

	/**
	 * Read the (sanitised) raw search query from the query var / GET.
	 *
	 * @return string
	 */
	private static function requestQuery(): string {
		$value = get_query_var( self::QUERY_VAR, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, self::QUERY_VAR );
		}

		return ( is_string( $value ) && '' !== $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Build the search form. Pure — escaping only. While a query is active it also carries the
	 * "Keer terug" link that clears the search; the results themselves are the Bydraes and
	 * Skrywers panels, filtered to the matches ({@see self::matchingWorkIds()}).
	 *
	 * @param string $raw_query The raw (unfolded) query, for the input value.
	 * @return string
	 */
	public static function toHtml( string $raw_query ): string {
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-ontdek-soek__ikoon" data-audit-id="search-icon" aria-hidden="true" focusable="false">'
			. '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

		// An explicit action: a form without one keeps the page's #bydraes/#skrywers hash
		// (set by the tab toggle), which scrolls the results out of view on submit.
		$action = ( function_exists( 'get_queried_object_id' ) && function_exists( 'get_permalink' ) && (int) get_queried_object_id() > 0 )
			? (string) get_permalink( (int) get_queried_object_id() )
			: '';

		$html = '<section class="ink-ontdek-soek" data-audit-id="search"><form class="ink-ontdek-soek__form" data-audit-id="search-form" role="search" method="get"'
			. ( '' !== $action ? ' action="' . esc_url( $action ) . '"' : '' ) . '>'
			. '<div class="ink-ontdek-soek__veld-houer" data-audit-id="search-field-wrap">' . $icon
			. '<input type="search" class="ink-ontdek-soek__veld ink-search-field ink-search-field--with-button" data-audit-id="search-input" name="' . esc_attr( self::QUERY_VAR ) . '"'
			. ' value="' . esc_attr( $raw_query ) . '"'
			. ' placeholder="' . esc_attr__( 'Vind stories, gedigte of skrywers...', 'ink-core' ) . '"'
			. ' aria-label="' . esc_attr__( 'Vind stories, gedigte of skrywers...', 'ink-core' ) . '" />'
			. '</div>'
			. '<button type="submit" class="ink-ontdek-soek__knoppie ink-btn ink-btn--primary" data-audit-id="search-btn">' . esc_html__( 'Soek', 'ink-core' ) . '</button>';

		if ( '' !== $raw_query ) {
			$html .= '<a class="ink-ontdek-soek__terug ink-btn ink-btn--outline" href="' . esc_url( '' !== $action ? $action : '?' ) . '">' . esc_html__( 'Keer terug', 'ink-core' ) . '</a>';
		}

		return $html . '</form></section>';
	}
}
