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
	 * Dropped Opleiding section placeholders (empty pages that only gave a menu item a
	 * page) → the main Opleiding page. Exact paths only, so their moved children, which
	 * share the `/opleiding/gedigte/` prefix, are still redirected via the stored map.
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
	);

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
	 * Whether a post id is a dropped duplicate. Pure.
	 *
	 * @param int $post_id The post id.
	 */
	public static function isDroppedDuplicate( int $post_id ): bool {
		return isset( self::DUPLICATES[ $post_id ] );
	}
}
