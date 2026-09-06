<?php
/**
 * Minimal `WP_REST_Response` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real
 * `WP_REST_Response` (from WP core) is absent. My Profiel rebuild §5.1's
 * {@see \Ink\Social\ProfileController::handleUpdate()} returns
 * `new WP_REST_Response( … )`, so the symbol must exist for the unit tests to
 * instantiate and inspect the response's data directly. This double carries
 * only the constructor-supplied data + `get_data()`; the integration suite
 * (wp-env) uses the real WP_REST_Response.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Light stand-in for WordPress's WP_REST_Response in mocked unit tests.
	 */
	class WP_REST_Response {
		/** @var mixed The response data. */
		private $data;

		/**
		 * @param mixed $data The response body data.
		 */
		public function __construct( $data = null ) {
			$this->data = $data;
		}

		/**
		 * Mirrors `WP_REST_Response::get_data()`.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}
	}
}
