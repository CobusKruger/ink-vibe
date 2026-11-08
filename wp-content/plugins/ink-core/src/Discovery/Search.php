<?php
/**
 * Ontdek search server block — Story 8.4 (FR-35, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;
use Ink\Migration\TextKey;
use Ink\Tiers\Api as TiersApi;

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

	private const LIMIT = 12;

	private const WORKS_LIMIT = 24;

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

		if ( '' === $raw ) {
			return self::toHtml( '', array(), array() );
		}

		$key = self::queryKey( $raw );

		if ( '' === $key ) {
			return self::toHtml( $raw, array(), array() );
		}

		global $wpdb;

		$works = array();

		foreach ( array_map( 'intval', (array) $wpdb->get_col( self::worksSql( $wpdb, $key, self::WORKS_LIMIT ) ) ) as $post_id ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- ranked LIKE over the search index; bounded by LIMIT.
			$post = get_post( $post_id );

			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			// Skip seeded `QA FIXTURE — ` titled works (Epic-19 theme-fidelity
			// rework finding — a real match would otherwise surface fixture
			// content in real search results). Filtered post-query (not at the
			// SQL layer) — search has a flat LIMIT and no pagination, so an
			// occasionally-short result list is the correct trade-off here.
			if ( QaFixture::isFixtureTitle( get_the_title( $post ) ) ) {
				continue;
			}

			$works[] = array(
				'title'     => get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
				'type'      => $post->post_type,
				'author'    => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			);
		}

		$skrywers = array();

		foreach ( array_map( 'intval', (array) $wpdb->get_col( self::skrywersSql( $wpdb, $key, self::LIMIT ) ) ) as $uid ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- as above.
			if ( $uid <= 0 ) {
				continue;
			}

			$skrywers[] = array(
				'name'        => (string) get_the_author_meta( 'display_name', $uid ),
				'profile_url' => (string) get_author_posts_url( $uid ),
				'gradering'   => Terms::label( TiersApi::forUser( $uid )->value ),
				'tier'        => TiersApi::forUser( $uid )->value,
			);
		}

		return self::toHtml( $raw, $works, $skrywers );
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
	 * Build the search form + result groups. Pure — escaping + Terms only.
	 *
	 * @param string                                                                       $raw_query The raw (unfolded) query, for the input value + heading.
	 * @param list<array{title:string, permalink:string, type:string, author:string}>      $works     Work results.
	 * @param list<array{name:string, profile_url:string, gradering:string, tier?:string}> $skrywers  Skrywer results.
	 * @return string
	 */
	public static function toHtml( string $raw_query, array $works, array $skrywers ): string {
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
			. '<button type="submit" class="ink-ontdek-soek__knoppie ink-btn ink-btn--primary" data-audit-id="search-btn">' . esc_html__( 'Soek', 'ink-core' ) . '</button>'
			. '</form>';

		if ( '' === $raw_query ) {
			return $html . '</section>';
		}

		if ( array() === $works && array() === $skrywers ) {
			$html .= '<p class="ink-ontdek-soek__leeg">' . esc_html__( "Probeer 'n ander soekterm of blaai deur alle artikels.", 'ink-core' ) . '</p>';

			return $html . '</section>';
		}

		// Author matches rank first, so the Skrywers group comes before the Bydraes.
		if ( array() !== $skrywers ) {
			$html .= '<h2 class="ink-ontdek-soek__groep ink-heading-h3">' . esc_html( Terms::label( 'skrywer_plural' ) ) . '</h2><ul class="ink-ontdek-soek__skrywers">';

			foreach ( $skrywers as $skrywer ) {
				$html .= '<li class="ink-ontdek-soek__skrywer ink-card">'
					. '<a class="ink-ontdek-soek__naam ink-heading-h3-card" href="' . esc_url( $skrywer['profile_url'] ) . '">' . esc_html( $skrywer['name'] ) . '</a>'
					. '<span class="' . esc_attr( 'ink-ontdek-soek__gradering ' . SkrywersTab::tierLabelClasses( (string) ( $skrywer['tier'] ?? '' ) ) ) . '">' . esc_html( $skrywer['gradering'] ) . '</span>'
					. '</li>';
			}

			$html .= '</ul>';
		}

		if ( array() !== $works ) {
			$html .= '<h2 class="ink-ontdek-soek__groep ink-heading-h3">' . esc_html( Terms::label( 'bydrae_plural' ) ) . '</h2><ul class="ink-ontdek-soek__werke">';

			foreach ( $works as $work ) {
				$html .= '<li class="ink-ontdek-soek__werk ink-card">'
					. '<span class="' . esc_attr( 'ink-ontdek-soek__tipe ink-pill ink-pill--tint-sm ' . self::typeTone( (string) $work['type'] ) ) . '">' . esc_html( Terms::label( $work['type'] ) ) . '</span>'
					. '<a class="ink-ontdek-soek__titel ink-heading-h3-card" href="' . esc_url( $work['permalink'] ) . '">' . esc_html( $work['title'] ) . '</a>'
					. '<span class="ink-ontdek-soek__outeur ink-meta ink-meta--sm">' . esc_html( $work['author'] ) . '</span>'
					. '</li>';
			}

			$html .= '</ul>';
		}

		return $html . '</section>';
	}

	/**
	 * The pill tone for a work type — the same per-type tones as the Ontdek works
	 * card (sage gedig / brand storie / muted artikel). Pure.
	 *
	 * @param string $type The bydrae post type.
	 * @return string
	 */
	private static function typeTone( string $type ): string {
		switch ( $type ) {
			case \Ink\Content\PostTypes::GEDIG:
				return 'ink-tone-accent';
			case \Ink\Content\PostTypes::STORIE:
				return 'ink-tone-primary';
			default:
				return 'ink-tone-muted';
		}
	}
}
