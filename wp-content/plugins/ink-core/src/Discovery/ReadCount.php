<?php
/**
 * Read-count tracking — Story 8.3 (FR-34, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Increments the denormalized read counts on each single-bydrae view.
 *
 * Drives the skrywers tab's "Meeste gelees" sort (and reading analytics, AD-7):
 * the post's `_ink_read_count` and the author's
 * {@see SkrywerIndex::READ_TOTAL_META} are bumped when a reader opens a published
 * bydrae. Per-request increment — bot-filtering + per-user/session dedup are an
 * analytics-hardening concern (Epic 18 / 18.9), deliberately out of scope here.
 *
 * Conflation-clean: post/user-meta only; zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class ReadCount {

	/**
	 * Post-meta: a work's denormalized read count (AD-7 — discovery sort + analytics).
	 *
	 * @var string
	 */
	public const READ_COUNT_META = '_ink_read_count';

	/**
	 * Hook the front-end view.
	 */
	public function register(): void {
		add_action( 'wp', array( $this, 'maybeCount' ) );
	}

	/**
	 * Count a view when the main request is a single readable bydrae.
	 */
	public function maybeCount(): void {
		if ( is_admin() || is_feed() || is_preview() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_singular( SkrywerIndex::readableTypes() ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();

		if ( $post_id <= 0 ) {
			return;
		}

		self::incrementPost( $post_id );

		$author = (int) get_post_field( 'post_author', $post_id );

		if ( $author > 0 ) {
			self::incrementAuthor( $author );
		}
	}

	/**
	 * Increment a work's read count by one.
	 *
	 * @param int $post_id The work.
	 */
	public static function incrementPost( int $post_id ): void {
		$count = (int) get_post_meta( $post_id, self::READ_COUNT_META, true );

		update_post_meta( $post_id, self::READ_COUNT_META, $count + 1 );
	}

	/**
	 * Increment a writer's denormalized total read count by one.
	 *
	 * @param int $author_id The writer.
	 */
	public static function incrementAuthor( int $author_id ): void {
		$count = (int) get_user_meta( $author_id, SkrywerIndex::READ_TOTAL_META, true );

		update_user_meta( $author_id, SkrywerIndex::READ_TOTAL_META, $count + 1 );
	}
}
