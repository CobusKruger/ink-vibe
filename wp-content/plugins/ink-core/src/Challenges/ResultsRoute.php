<?php
/**
 * The per-round results URL — WP1 (N1, D5; `01-uitslae` §7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;
use Ink\Kernel\SystemPages;

defined( 'ABSPATH' ) || exit;

/**
 * Routes `/uitdaging/<slug>/uitslae/` to `templates/single-uitdaging-uitslae.html`, and
 * sends each old result post to its own round's page.
 *
 *  - **Endpoint:** {@see ENDPOINT} on `EP_PERMALINK`, flushed once per {@see VERSION}. The
 *    bare `/uitslae/` gives an EMPTY query var, so presence is tested, never truthiness.
 *  - **The mask is wider than rounds:** it also matches posts and other post types, so
 *    `/gedig/x/uitslae/` must 404, as must a round with no results ({@see RoundResults}).
 *  - **Core's 404 guess is off for these URLs:** without that, a 404 here lands on an
 *    old result post through `redirect_guess_404_permalink()`.
 *  - **301 (D5, owner):** a result post stays stored, because it holds the announced date,
 *    but it redirects to the page of the round it is linked to. Each post, its own round.
 *
 * Routing decisions are pure statics; the `template_redirect` handlers are thin shells.
 *
 * @package Ink\Core
 */
final class ResultsRoute {

	/**
	 * The endpoint name. The single source is the guard-tested list in {@see SystemPages}.
	 *
	 * @var string
	 */
	public const ENDPOINT = SystemPages::ENDPOINT_TEMPLATES[ self::TEMPLATE ];

	/**
	 * The theme template, without its extension.
	 *
	 * @var string
	 */
	public const TEMPLATE = 'single-uitdaging-uitslae';

	/**
	 * Bump when the rewrite rules this class adds change.
	 *
	 * @var string
	 */
	public const VERSION = '1';

	/**
	 * The option holding the {@see VERSION} already flushed.
	 *
	 * @var string
	 */
	public const VERSION_OPTION = 'ink_uitslae_rewrite_version';

	/**
	 * Wire the endpoint and handlers. Runs on `init`, after the post types register.
	 */
	public function register(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_PERMALINK );

		// Late, so every other module's rules are in the flushed set.
		add_action( 'init', array( self::class, 'flushIfStale' ), 99 );
		add_filter( 'single_template_hierarchy', array( self::class, 'templateHierarchy' ) );
		add_filter( 'do_redirect_guess_404_permalink', array( self::class, 'guessAllowed' ) );
		// Ahead of redirect_canonical (10), which is what acts on the guess.
		add_action( 'template_redirect', array( self::class, 'guard' ), 1 );
		add_action( 'template_redirect', array( self::class, 'redirectResultPost' ), 2 );
	}

	/**
	 * Flush the rewrite rules once per {@see VERSION}.
	 */
	public static function flushIfStale(): void {
		if ( self::VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Whether this request carries the endpoint: `/uitslae/` holds an empty value.
	 *
	 * @return bool
	 */
	public static function endpointPresent(): bool {
		return null !== get_query_var( self::ENDPOINT, null );
	}

	/**
	 * Whether this request is a round's results view.
	 *
	 * @return bool
	 */
	public static function isResultsView(): bool {
		return self::endpointPresent() && is_singular( PostTypes::UITDAGING );
	}

	/**
	 * Put the results template first on a results view.
	 *
	 * @param list<string> $templates The hierarchy.
	 * @return list<string>
	 */
	public static function templateHierarchy( array $templates ): array {
		if ( ! self::isResultsView() ) {
			return $templates;
		}

		array_unshift( $templates, self::TEMPLATE . '.php' );

		return $templates;
	}

	/**
	 * Turn off core's 404 guess on this endpoint.
	 *
	 * @param bool $allowed The incoming decision.
	 * @return bool
	 */
	public static function guessAllowed( bool $allowed ): bool {
		return self::endpointPresent() ? false : $allowed;
	}

	/**
	 * 404 the endpoint on anything but a round with results.
	 */
	public static function guard(): void {
		if ( ! self::endpointPresent() || is_404() || ! is_singular() ) {
			return;
		}

		$id = (int) get_queried_object_id();

		if ( is_singular( PostTypes::UITDAGING ) && RoundResults::hasResults( $id ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * 301 an old result post to its own round's results page.
	 */
	public static function redirectResultPost(): void {
		if ( self::endpointPresent() || ! is_singular( 'post' ) || is_preview() || is_feed() ) {
			return;
		}

		$target = self::redirectTarget( (int) get_queried_object_id() );

		if ( '' !== $target && wp_safe_redirect( $target, 301 ) ) {
			exit;
		}
	}

	/**
	 * Where a result post redirects: '' for a post with no link, or a link to something that
	 * isn't a published round.
	 *
	 * @param int $post_id The post.
	 * @return string
	 */
	public static function redirectTarget( int $post_id ): string {
		$round = Scalar::asNonNegativeInt( get_post_meta( $post_id, WinnersPost::UITDAGING_META, true ) );

		if ( $round <= 0 || PostTypes::UITDAGING !== get_post_type( $round ) || 'publish' !== get_post_status( $round ) ) {
			return '';
		}

		return self::urlFor( $round );
	}

	/**
	 * A round's results URL.
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	public static function urlFor( int $uitdaging_id ): string {
		return self::compose( (string) get_permalink( $uitdaging_id ) );
	}

	/**
	 * A results URL from a round's permalink. Pure.
	 *
	 * @param string $permalink The round's permalink.
	 * @return string
	 */
	public static function compose( string $permalink ): string {
		return rtrim( $permalink, '/' ) . '/' . self::ENDPOINT . '/';
	}
}
