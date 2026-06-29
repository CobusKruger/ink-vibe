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
 * bydrae. Story 18.9 routes the recording through {@see Analytics}: bot/self-view
 * filtering (the hardening 8.3 deferred) plus a vetted-plugin provider hand-off
 * (the counter here is the fallback when no analytics provider is wired).
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

		$author = (int) get_post_field( 'post_author', $post_id );

		// Story 18.9: filter bots + the author's own self-view before recording, then
		// hand the view to the vetted analytics provider (or the ink-core fallback
		// counter inside Analytics::recordView when no provider is wired).
		if ( ! Analytics::shouldRecordView( $this->userAgent(), $this->viewerId(), $author ) ) {
			return;
		}

		Analytics::recordView( $post_id, $author );
	}

	/**
	 * The request user-agent. Overridable seam.
	 */
	protected function userAgent(): string {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) || ! is_scalar( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	/**
	 * The current viewer's user id (0 when anonymous). Overridable seam.
	 */
	protected function viewerId(): int {
		return (int) get_current_user_id();
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
