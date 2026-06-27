<?php
/**
 * Minimal `WP_Term` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real `WP_Term`
 * (from WP core) is absent. Story 10.5's {@see \Ink\Library\WinnerLinkage} reads
 * `get_the_terms()` results and checks `$term instanceof \WP_Term` before reading
 * `$term->slug`, so the symbol must exist for the unit tests to build term doubles.
 * This double carries only the `slug`/`term_id`/`name` fields those needs require;
 * the integration suite (wp-env) uses the real WP_Term.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_Term' ) ) {
	/**
	 * Light stand-in for WordPress's WP_Term in mocked unit tests.
	 */
	class WP_Term {
		/** @var int Mirrors WP_Term::$term_id. */
		public int $term_id = 0;

		/** @var string Mirrors WP_Term::$slug. */
		public string $slug = '';

		/** @var string Mirrors WP_Term::$name. */
		public string $name = '';

		/**
		 * @param string $slug    Optional term slug (the round-term join key).
		 * @param int    $term_id Optional term id.
		 * @param string $name    Optional term name.
		 */
		public function __construct( string $slug = '', int $term_id = 0, string $name = '' ) {
			$this->slug    = $slug;
			$this->term_id = $term_id;
			$this->name    = $name;
		}
	}
}
