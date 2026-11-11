<?php
/**
 * Committed redirect decisions that are not 1:1 post moves — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Human decisions about URLs, kept in the repo so a re-clone needs no manual step.
 *
 *  - {@see PREFIXES}: whole old sections that now live on one page. The Ontdek tabs are
 *    client-side panels, so the target carries a fragment (`#bydraes`, `#wenners`).
 *  - {@see DUPLICATES}: prod posts that were published twice (dropped post id → kept post
 *    id). The dropped one is trashed and its old URL redirects to the kept one.
 *
 * Pure, zero WordPress.
 *
 * @package Ink\Core
 */
final class StaticRedirects {

	/**
	 * Old path prefix → new path (with fragment), relative to the site root.
	 *
	 * @var array<string, string>
	 */
	public const PREFIXES = array(
		'/kategorie/lede-bydraes/'    => '/ontdek/#bydraes',
		'/biblioteek/projek-wenners/' => '/ontdek/#wenners',
	);

	/**
	 * Exact old paths → a new path. Dropped Opleiding section placeholders (empty pages that
	 * only gave a menu item a page) → the main Opleiding page: exact paths only, so their moved
	 * children, which share the `/opleiding/gedigte/` prefix, are still redirected via the
	 * stored map. The gala index Page → Kompetisies (the year Pages: {@see oldPageTarget()}).
	 *
	 * @var array<string, string>
	 */
	public const EXACT = array(
		'/opleiding/digkuns'        => '/opleiding/',
		'/opleiding/skryfkuns'      => '/opleiding/',
		'/opleiding/taalgidse'      => '/opleiding/',
		'/opleiding/woordeboeke'    => '/opleiding/',
		'/opleiding/algemene-wenke' => '/opleiding/',
		'/opleiding/gedigte'        => '/opleiding/',
		'/opleiding/opleiding'      => '/opleiding/',
		// The gala index has no year, so it goes to Kompetisies. The year Pages are in OPTION_OLD_PAGES (K7).
		'/ink-se-gala-aande'        => '/kompetisies/',
	);

	/**
	 * The option holding which retired Page maps to which year and target: a list of
	 * `{id: int, path: string, jaar: int, soort: 'fotos'|'uitslae'}` written by
	 * `wp ink migrate-kompetisies` (K6) for the redirects (K7). `fotos` goes to
	 * `/kompetisies/gala/<jaar>/`, `uitslae` to `/kompetisies/<jaar>/uitslae/`. `path` has no
	 * trailing slash and may be '' when it could not be read; `id` serves `?p=<id>`.
	 *
	 * @var string
	 */
	public const OPTION_OLD_PAGES = 'ink_kompetisie_old_pages';

	/**
	 * Dropped duplicate post id → the post that is kept.
	 *
	 * @var array<int, int>
	 */
	public const DUPLICATES = array(
		35127 => 21602, // skryfwerk "Plagiaat/Letterdiefstal" duplicates opleiding 21602.
	);

	/**
	 * The new path for an old request path: a dropped placeholder (exact) or a moved section (prefix), or null. Pure.
	 *
	 * @param string $request_path The incoming path or URL.
	 */
	public static function prefixTarget( string $request_path ): ?string {
		$key = RedirectGenerator::normalisePath( $request_path );

		if ( isset( self::EXACT[ $key ] ) ) {
			return self::EXACT[ $key ];
		}

		foreach ( self::PREFIXES as $prefix => $target ) {
			if ( str_starts_with( $key . '/', $prefix ) ) {
				return $target;
			}
		}

		return null;
	}

	/**
	 * The new path for a request to a retired results or Fotos Page (story K7), or null. Pure.
	 *
	 * Matches `?p=<id>` or `?page_id=<id>` against the entry's id (any path), and the normalised
	 * request path (case-insensitive, trailing slash and query ignored) against the entry's path.
	 * An entry with an empty path matches by id only. `fotos` gives `/kompetisies/gala/<jaar>/`,
	 * `uitslae` gives `/kompetisies/<jaar>/uitslae/` (the same routes as `GalaPage::path()` and
	 * `UitslaePage::path()`, which a test keeps in step; Migration may not depend on Competitions).
	 * The map holds only migrated years, so the year route exists. Announcements and other kept
	 * Pages are not in the map, so they are never redirected.
	 *
	 * @param string       $request The incoming path or URL, with its query string.
	 * @param array<mixed> $pages   The {@see OPTION_OLD_PAGES} value.
	 */
	public static function oldPageTarget( string $request, array $pages ): ?string {
		$key = self::pathKey( $request );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper: parses a path/URL string, no HTTP; kept WP-free so it is unit-testable without WordPress.
		$query = (string) ( parse_url( $request, PHP_URL_QUERY ) ?? '' );
		$args  = array();
		parse_str( $query, $args );
		$wanted = 0;

		foreach ( array( 'p', 'page_id' ) as $name ) {
			if ( isset( $args[ $name ] ) && is_string( $args[ $name ] ) && ctype_digit( $args[ $name ] ) ) {
				$wanted = (int) $args[ $name ];
				break;
			}
		}

		foreach ( $pages as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$jaar  = (int) ( $entry['jaar'] ?? 0 );
			$soort = $entry['soort'] ?? '';
			$id    = (int) ( $entry['id'] ?? 0 );
			$path  = is_string( $entry['path'] ?? null ) ? $entry['path'] : '';

			if ( $jaar <= 0 || ( 'fotos' !== $soort && 'uitslae' !== $soort ) ) {
				continue;
			}

			$by_id   = $wanted > 0 && $id === $wanted;
			$by_path = '' !== trim( $path, '/' ) && self::pathKey( $path ) === $key;

			if ( $by_id || $by_path ) {
				return 'fotos' === $soort ? '/kompetisies/gala/' . $jaar . '/' : '/kompetisies/' . $jaar . '/uitslae/';
			}
		}

		return null;
	}

	/**
	 * A path or URL as a decoded, lower-case, slash-trimmed key. Pure.
	 *
	 * @param string $url A URL or path.
	 */
	private static function pathKey( string $url ): string {
		return mb_strtolower( rawurldecode( RedirectGenerator::normalisePath( $url ) ) );
	}

	/**
	 * Whether a post id is a dropped duplicate. Pure.
	 *
	 * @param int $post_id The post id.
	 */
	public static function isDroppedDuplicate( int $post_id ): bool {
		return isset( self::DUPLICATES[ $post_id ] );
	}
}
