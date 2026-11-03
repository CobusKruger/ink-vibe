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
	 * stored map. The gala Pages → Kompetisies.
	 *
	 * @var array<string, string>
	 */
	public const EXACT = array(
		'/opleiding/digkuns'                              => '/opleiding/',
		'/opleiding/skryfkuns'                            => '/opleiding/',
		'/opleiding/taalgidse'                            => '/opleiding/',
		'/opleiding/woordeboeke'                          => '/opleiding/',
		'/opleiding/algemene-wenke'                       => '/opleiding/',
		'/opleiding/gedigte'                              => '/opleiding/',
		'/opleiding/opleiding'                            => '/opleiding/',
		// The gala Pages and their Fotos Pages (owner, `owner-rulings.md`). `wp ink migrate-kompetisies` trashes them.
		'/ink-se-gala-aande'                              => '/kompetisies/',
		'/15-november-2025-10de-gala'                     => '/kompetisies/',
		'/15-november-2025-10de-gala/fotos-15-november-2025' => '/kompetisies/',
		'/9-nov-2024-9de-gala-aand'                       => '/kompetisies/',
		'/9-nov-2024-9de-gala-aand/fotos-9-nov-2024'      => '/kompetisies/',
		'/11-november-2023-8ste-gala-aand'                => '/kompetisies/',
		'/11-november-2023-8ste-gala-aand/fotos-11-november-2023-8ste-gala-aand' => '/kompetisies/',
		'/12-november-2022-7de-gala-aand'                 => '/kompetisies/',
		'/12-november-2022-7de-gala-aand/fotos-12-november-2022-gala-geleentheid' => '/kompetisies/',
		'/13-november-2021-gala-aand'                     => '/kompetisies/',
		'/13-november-2021-gala-aand/fotos-13-november-2021-6de-gala-geleentheid' => '/kompetisies/',
		'/21-november-2020-5de-gala-aand'                 => '/kompetisies/',
		'/21-november-2020-5de-gala-aand/fotos-21-november-2020-5de-gala-aand' => '/kompetisies/',
		'/37975-2'                                        => '/kompetisies/',
		'/37975-2/fotos-26-oktober-2019-4de-gala-aand'    => '/kompetisies/',
		'/10-november-2018-3de-gala-aand'                 => '/kompetisies/',
		'/10-november-2018-3de-gala-aand/fotos-gala-aand-10-nov-2018' => '/kompetisies/',
		'/4-november-2017-2de-gala-aand'                  => '/kompetisies/',
		'/4-november-2017-2de-gala-aand/fotos-4-nov-2017' => '/kompetisies/',
		'/22-oktober-2016-1ste-gala-aand'                 => '/kompetisies/',
		'/22-oktober-2016-1ste-gala-aand/fotos'           => '/kompetisies/',
	);

	/**
	 * The option holding the old paths of gala Pages `wp ink migrate-kompetisies` retired. It
	 * covers a gala added to production after {@see EXACT} was written.
	 *
	 * @var string
	 */
	public const OPTION_GALA_PATHS = 'ink_gala_old_paths';

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
	 * Kompetisies, when the request is one of the stored retired gala paths. Pure.
	 *
	 * @param string       $request_path The incoming path or URL.
	 * @param array<mixed> $stored       The {@see OPTION_GALA_PATHS} value.
	 */
	public static function storedGalaTarget( string $request_path, array $stored ): ?string {
		$key = RedirectGenerator::normalisePath( $request_path );

		foreach ( $stored as $path ) {
			if ( is_string( $path ) && RedirectGenerator::normalisePath( $path ) === $key ) {
				return '/kompetisies/';
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
