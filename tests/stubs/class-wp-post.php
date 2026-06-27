<?php
/**
 * Minimal `WP_Post` test double for the UNIT suite (NFR-9, Story 1.11).
 *
 * The unit suite mocks WordPress rather than loading it, so the real `WP_Post` (from
 * WP core) is absent. Story 12.3's {@see \Ink\Content\FieldSets::save()} type-hints
 * `WP_Post $post` and reads `$post->post_type`, so the symbol must exist for the unit
 * tests to build post doubles. This double carries only the `ID`/`post_type` fields
 * those needs require; the integration suite (wp-env) uses the real WP_Post.
 *
 * Defined in the global namespace to match WordPress core.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Light stand-in for WordPress's WP_Post in mocked unit tests.
	 */
	class WP_Post {
		/** @var int Mirrors WP_Post::$ID. */
		public int $ID = 0;

		/** @var string Mirrors WP_Post::$post_type. */
		public string $post_type = '';

		/** @var string Mirrors WP_Post::$post_status. */
		public string $post_status = '';

		/** @var int Mirrors WP_Post::$post_author. */
		public int $post_author = 0;
	}
}
