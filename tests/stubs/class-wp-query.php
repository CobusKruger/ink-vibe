<?php
/**
 * Minimal `WP_Query` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real `WP_Query` (from
 * WP core) is absent. Story 14.2's {@see \Ink\Sponsors\Campaign::activeSponsors()}
 * constructs `new WP_Query( … )` and iterates `$query->posts`, so the symbol must
 * exist for the thin WP wrapper (and the Api delegation through it) to be exercised in
 * a unit test. This double does NOT run a query — it simply returns the posts the test
 * stages via {@see WP_Query::$ink_test_posts} (a static the test sets in `beforeEach`),
 * so the test controls the input without depending on a real DB. The integration suite
 * (wp-env, Story 18.8) uses the real WP_Query.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_Query' ) ) {
	/**
	 * Light stand-in for WordPress's WP_Query in mocked unit tests.
	 */
	class WP_Query {
		/**
		 * The posts a test stages for the NEXT WP_Query construction(s).
		 *
		 * @var array<int, mixed>
		 */
		public static array $ink_test_posts = array();

		/**
		 * The args the most recent construction received (lets a test assert them).
		 *
		 * @var array<string, mixed>
		 */
		public static array $ink_test_last_args = array();

		/**
		 * The resolved posts for this query instance.
		 *
		 * @var array<int, mixed>
		 */
		public array $posts = array();

		/**
		 * Mirror WP_Query's constructor signature; return the staged posts.
		 *
		 * @param array<string, mixed> $args The query args.
		 */
		public function __construct( array $args = array() ) {
			self::$ink_test_last_args = $args;
			$this->posts              = self::$ink_test_posts;
		}
	}
}
