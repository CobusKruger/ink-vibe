<?php
/**
 * Minimal `WP_REST_Request` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real
 * `WP_REST_Request` (from WP core) is absent. My Profiel rebuild §5.1's
 * {@see \Ink\Social\ProfileController::handleUpdate()} type-hints
 * `WP_REST_Request $request` and calls `get_param()`, so the symbol must exist
 * for the unit tests to build request doubles and exercise the handler's
 * per-field update paths / partial updates directly (not just its trivial
 * `permission()` + pure `validate()`). This double carries only the
 * constructor-supplied param map + `get_param()`; the integration suite
 * (wp-env) uses the real WP_REST_Request.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Light stand-in for WordPress's WP_REST_Request in mocked unit tests.
	 */
	class WP_REST_Request {
		/** @var array<string, mixed> The request params, keyed by name. */
		private array $params;

		/**
		 * @param array<string, mixed> $params The request params (query/body/route, flattened).
		 */
		public function __construct( array $params = array() ) {
			$this->params = $params;
		}

		/**
		 * Mirrors `WP_REST_Request::get_param()` — null when the key is absent
		 * (never set), which is exactly what "was this field submitted at all"
		 * checks in this codebase rely on.
		 *
		 * @param string $key The param name.
		 * @return mixed
		 */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}
