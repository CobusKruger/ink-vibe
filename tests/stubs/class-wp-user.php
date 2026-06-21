<?php
/**
 * Minimal `WP_User` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real
 * `WP_User` (from WP core) is absent. AdminLanguageTest builds
 * `Mockery::mock( \WP_User::class )` and `I18n::forceStaffAdminLocale()` is
 * typed `int|\WP_User`, so the symbol must exist for the type hint and for
 * Mockery to mock by name. This double carries only what those needs require;
 * the integration suite (wp-env) uses the real WP_User.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_User' ) ) {
	/**
	 * Light stand-in for WordPress's WP_User in mocked unit tests.
	 */
	class WP_User {
		/** @var int Mirrors WP_User::$ID. */
		public int $ID = 0;

		/**
		 * @param int $id Optional user id.
		 */
		public function __construct( int $id = 0 ) {
			$this->ID = $id;
		}
	}
}
