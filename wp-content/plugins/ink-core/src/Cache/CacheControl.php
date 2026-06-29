<?php
/**
 * Page-cache bypass for INK's dynamic surfaces — Story 18.5 (NFR-3, §14.9).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Signals LiteSpeed + the generic page cache to NOT cache INK's private/transactional
 * surfaces.
 *
 * Two clean seams (no coupling to Forms/Discovery/Social constants):
 *  - the `ink_` admin-post prefix — any INK `admin-post.php?action=ink_*` round-trip
 *    (contact, report, …) bypasses the cache, detected generically;
 *  - the {@see BYPASS_FILTER} filter — any personalised surface opts its own request
 *    out (`add_filter( 'ink_cache_bypass', '__return_true' )`).
 *
 * The page/URI/block exclusion list for the LiteSpeed + Cloudflare config lives in
 * docs/caching-runbook.md (it is cache configuration, not code), so this module stays
 * Kernel-only. The decision is a pure function over primitives; the request reads and
 * the constant define are overridable seams so it unit-tests without WordPress.
 *
 * @package Ink\Core
 */
class CacheControl {

	/**
	 * The prefix every INK `admin-post` action carries (single source — the
	 * generic cache-bypass signal for all INK forms, no Forms dependency).
	 */
	public const ADMIN_POST_PREFIX = 'ink_';

	/**
	 * The filter a personalised surface uses to opt its request out of the cache.
	 */
	public const BYPASS_FILTER = 'ink_cache_bypass';

	/**
	 * Evaluate the current request and bypass the cache when it is private/dynamic.
	 */
	public function register(): void {
		add_action( 'send_headers', array( $this, 'maybeBypass' ) );
	}

	/**
	 * Whether this request must bypass the page cache. Pure.
	 *
	 * @param array{ink_admin_post?:bool, filtered_bypass?:bool} $context Request signals.
	 */
	public function shouldBypassCache( array $context ): bool {
		return true === ( $context['ink_admin_post'] ?? false )
			|| true === ( $context['filtered_bypass'] ?? false );
	}

	/**
	 * Bypass the cache for the current request when needed.
	 */
	public function maybeBypass(): void {
		$context = array(
			'ink_admin_post'  => $this->isInkAdminPost(),
			'filtered_bypass' => (bool) apply_filters( self::BYPASS_FILTER, false ),
		);

		if ( ! $this->shouldBypassCache( $context ) ) {
			return;
		}

		$this->bypass();
	}

	/**
	 * Signal both cache layers to skip this response. Overridable seam.
	 */
	protected function bypass(): void {
		nocache_headers();

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// LiteSpeed Cache no-cache API — inert when LiteSpeed is absent (no listener).
		do_action( 'litespeed_control_set_nocache', 'ink: dynamic/private surface' );
	}

	/**
	 * Whether the current request is an INK `admin-post` action. Overridable seam.
	 */
	protected function isInkAdminPost(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check (action name only); the handlers themselves verify the nonce.
		$action = isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- as above.
			? sanitize_key( wp_unslash( $_REQUEST['action'] ) )
			: '';

		return '' !== $action && str_starts_with( $action, self::ADMIN_POST_PREFIX );
	}
}
