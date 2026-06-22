<?php
/**
 * Minimal `WP_Error` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real
 * `WP_Error` (from WP core) is absent. {@see \Ink\Accounts\Approval::blockPendingLogin()}
 * returns `new \WP_Error( … )` to gate a pending login the WP-native way, so the
 * symbol must exist for the unit tests to instantiate and inspect it. This double
 * carries only the small surface those tests use (code + message accessors); the
 * integration suite (wp-env) uses the real WP_Error.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Light stand-in for WordPress's WP_Error in mocked unit tests.
	 */
	class WP_Error {
		/** @var array<string, list<string>> Error codes → messages. */
		private array $errors = array();

		/**
		 * @param string|int $code    Optional error code.
		 * @param string     $message Optional error message.
		 */
		public function __construct( $code = '', string $message = '' ) {
			if ( '' !== $code ) {
				$this->errors[ (string) $code ][] = $message;
			}
		}

		/**
		 * The first (or given) error code, mirroring WP_Error::get_error_code().
		 *
		 * @return string The error code, or '' when none.
		 */
		public function get_error_code(): string {
			$codes = array_keys( $this->errors );

			return $codes[0] ?? '';
		}

		/**
		 * The message for $code (or the first code), mirroring WP_Error::get_error_message().
		 *
		 * @param string|int $code Optional error code.
		 * @return string The error message, or '' when none.
		 */
		public function get_error_message( $code = '' ): string {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}

			return $this->errors[ (string) $code ][0] ?? '';
		}
	}
}
