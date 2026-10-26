<?php
/**
 * The public profile lives at /lede/<name>.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * A writer's public showcase is the theme's author profile, served at
 * `/lede/<name>` (not WordPress's default `/author/<name>`). BuddyPress's own
 * member area also answers at `/lede/`, with friendships, messages and settings
 * the new site does not use; it is not reachable. Every `/lede/` request is
 * resolved here before BuddyPress sees it:
 *
 * - `/lede/<name>`          the public profile.
 * - `/lede/<name>/<more>`   redirects to the public profile.
 * - `/lede`                 redirects to Ontdek.
 * - `/author/<name>`        redirects (301) to `/lede/<name>`.
 *
 * The private page is `/my-profiel`.
 *
 * @package Ink\Core
 */
final class PublicProfileRoutes {

	/**
	 * The public profile base.
	 */
	public const BASE = 'lede';

	/**
	 * Where the bare member directory goes.
	 */
	public const DIRECTORY_REDIRECT = '/ontdek/';

	/**
	 * Register the hooks.
	 */
	public function register(): void {
		add_filter( 'author_link', array( $this, 'authorLink' ), 10, 3 );
		add_filter( 'request', array( $this, 'route' ), 1 );
	}

	/**
	 * Author URLs point at `/lede/<name>`.
	 *
	 * @param string $link     The default author URL.
	 * @param int    $id       The author id.
	 * @param string $nicename The author's URL name.
	 * @return string
	 */
	public function authorLink( string $link, int $id, string $nicename ): string {
		unset( $link, $id );

		return home_url( self::pathFor( $nicename ) );
	}

	/**
	 * Resolve a /lede/ or /author/ request before BuddyPress or WordPress route it.
	 *
	 * @param array<string, mixed> $query_vars The parsed query vars.
	 * @return array<string, mixed>
	 */
	public function route( array $query_vars ): array {
		global $wp;

		$decision = self::decide( is_object( $wp ) && isset( $wp->request ) ? (string) $wp->request : '' );

		if ( 'profile' === $decision['action'] ) {
			return array( 'author_name' => $decision['target'] );
		}

		if ( 'redirect' === $decision['action'] ) {
			wp_safe_redirect( home_url( $decision['target'] ), 301 );
			exit;
		}

		return $query_vars;
	}

	/**
	 * The public profile path for an author URL name. Pure.
	 *
	 * @param string $nicename The author's URL name.
	 */
	public static function pathFor( string $nicename ): string {
		return '/' . self::BASE . '/' . rawurlencode( $nicename ) . '/';
	}

	/**
	 * What to do with a request path. Pure.
	 *
	 * @param string $path The request path, without the site base.
	 * @return array{action:string, target:string} `profile` (target: the author name), `redirect` (target: a site path) or `none`.
	 */
	public static function decide( string $path ): array {
		$path = trim( rawurldecode( $path ), '/' );

		if ( self::BASE === $path ) {
			return array(
				'action' => 'redirect',
				'target' => self::DIRECTORY_REDIRECT,
			);
		}

		if ( 1 === preg_match( '#^' . self::BASE . '/([^/]+)$#', $path, $m ) ) {
			return array(
				'action' => 'profile',
				'target' => $m[1],
			);
		}

		if ( 1 === preg_match( '#^' . self::BASE . '/([^/]+)/.+$#', $path, $m ) ) {
			return array(
				'action' => 'redirect',
				'target' => self::pathFor( $m[1] ),
			);
		}

		if ( 1 === preg_match( '#^author/([^/]+)(?:/.*)?$#', $path, $m ) ) {
			return array(
				'action' => 'redirect',
				'target' => self::pathFor( $m[1] ),
			);
		}

		return array(
			'action' => 'none',
			'target' => '',
		);
	}
}
