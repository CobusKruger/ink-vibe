<?php
/**
 * Origin-side security hardening — Story 18.3 (§14.16).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Surface-reduction hardening applied at the origin, behind Cloudflare.
 *
 * Each measure is gated by its own `apply_filters( 'ink_security_*', true )` so a
 * deployment can opt out without code changes (e.g. if the edge already covers it):
 *  - **XML-RPC off** — a brute-force-amplification + pingback-DDoS vector INK never uses.
 *  - **Username-enumeration block** — `?author=N` archive probing redirects home for
 *    UNAUTHENTICATED requests; the public REST `/wp/v2/users` collection is removed
 *    for unauthenticated requests (kept for logged-in editors, who need it in the
 *    block editor).
 *  - **Version disclosure removed** — the `the_generator` / `wp_generator` meta leaks
 *    the WP version, which helps an attacker target version-specific CVEs.
 *
 * Decisions are pure helpers taking primitives, so they unit-test without WordPress.
 * Conflation-clean: zero Tiers/Entitlement.
 *
 * @package Ink\Core
 */
class Hardening {

	/**
	 * The REST routes that expose the user list to anonymous callers.
	 *
	 * @var list<string>
	 */
	private const REST_USER_ROUTES = array(
		'/wp/v2/users',
		'/wp/v2/users/(?P<id>[\d]+)',
	);

	/**
	 * Wire each hardening behind its opt-out filter.
	 */
	public function register(): void {
		if ( (bool) apply_filters( 'ink_security_disable_xmlrpc', true ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}

		if ( (bool) apply_filters( 'ink_security_block_user_enumeration', true ) ) {
			add_action( 'template_redirect', array( $this, 'blockAuthorEnumeration' ) );
			add_filter( 'rest_endpoints', array( $this, 'filterRestUserRoutes' ) );
		}

		if ( (bool) apply_filters( 'ink_security_hide_version', true ) ) {
			add_filter( 'the_generator', array( $this, 'emptyGenerator' ) );
			remove_action( 'wp_head', 'wp_generator' );
		}
	}

	/**
	 * Whether a request is anonymous author-enumeration (`?author=N` probing). Pure.
	 *
	 * @param array<string, mixed> $get        The query params ($_GET).
	 * @param bool                 $is_author  Whether this resolved to an author archive.
	 * @param bool                 $logged_in  Whether the current user is logged in.
	 */
	public function isAuthorEnumeration( array $get, bool $is_author, bool $logged_in ): bool {
		if ( $logged_in ) {
			return false;
		}

		return $is_author && array_key_exists( 'author', $get );
	}

	/**
	 * The REST routes restricted from anonymous callers. Pure.
	 *
	 * @return list<string>
	 */
	public function restrictedRestRoutes(): array {
		return self::REST_USER_ROUTES;
	}

	/**
	 * Whether the public users endpoint should be hidden for this caller. Pure.
	 *
	 * @param bool $logged_in Whether the current user is logged in.
	 */
	public function shouldRestrictUsersEndpoint( bool $logged_in ): bool {
		return ! $logged_in;
	}

	/**
	 * Redirect an anonymous `?author=N` enumeration probe to the home page.
	 */
	public function blockAuthorEnumeration(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only detection of an enumeration probe; only the presence of the `author` key is read, no values used.
		$get = (array) wp_unslash( $_GET );

		if ( ! $this->isAuthorEnumeration( $get, $this->isAuthorArchive(), is_user_logged_in() ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}

	/**
	 * Remove the user-listing REST routes for anonymous callers.
	 *
	 * @param array<string, mixed> $endpoints The registered REST endpoints.
	 * @return array<string, mixed>
	 */
	public function filterRestUserRoutes( array $endpoints ): array {
		if ( ! $this->shouldRestrictUsersEndpoint( is_user_logged_in() ) ) {
			return $endpoints;
		}

		foreach ( $this->restrictedRestRoutes() as $route ) {
			unset( $endpoints[ $route ] );
		}

		return $endpoints;
	}

	/**
	 * Empty the generator string (version disclosure removal).
	 *
	 * @param string $generator The generator markup (unused — always emptied).
	 * @return string
	 */
	public function emptyGenerator( string $generator ): string {
		unset( $generator );

		return '';
	}

	/**
	 * Whether the current request is an author archive. Overridable seam.
	 */
	protected function isAuthorArchive(): bool {
		return is_author();
	}
}
