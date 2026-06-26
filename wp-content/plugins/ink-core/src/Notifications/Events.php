<?php
/**
 * Kennisgewing source subscriptions — Story 9.9 (FR-44).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

use Ink\Content\PostTypes;
use Ink\Entitlement\LifecycleEmails;
use Ink\Social\Api as SocialApi;

defined( 'ABSPATH' ) || exit;

/**
 * Subscribes the kennisgewing sources to the underlying WP/INK events and routes
 * each through {@see Kennisgewings::add()}.
 *
 * Wired sources (their hooks fire today):
 * - new Gemeenskapsreaksie + @mention → `wp_insert_comment` (the `ink_reaksie`
 *   comment-insert; FR-44 fires off the comment hook).
 * - followed-writer new work → `transition_post_status` (publish of a readable
 *   bydrae → fan out to the author's volgelinge).
 * - lidmaatskap-expiry → the SAME Action Scheduler hook the 4.8 lifecycle emails
 *   fire on ({@see LifecycleEmails::HOOK_SEND_WARNING}) — one schedule, two outputs.
 *
 * Deferred sources (emitter ready, source not built): uitdaging announce/deadline
 * (Epic 12 events) and the read-receipt milestone (Story 9.11 / R7) call the same
 * `Kennisgewings::add()` when their source lands.
 *
 * All BP writes are guarded inside {@see Kennisgewings::add()} (no-op without
 * BuddyPress). No self-notify. "Hook, don't edit."
 *
 * @package Ink\Core
 */
final class Events {

	/**
	 * The sanctioned community-response comment type (Engagement, AD-5a). A WP
	 * comment_type string — the integration contract, matched directly here.
	 */
	private const REAKSIE_COMMENT_TYPE = 'ink_reaksie';

	/**
	 * Subscribe the sources on `init`.
	 */
	public function register(): void {
		add_action( 'wp_insert_comment', array( $this, 'onComment' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'onTransition' ), 10, 3 );
		add_action( LifecycleEmails::HOOK_SEND_WARNING, array( $this, 'onExpiryWarning' ), 10, 3 );
	}

	/**
	 * New Gemeenskapsreaksie + @mentions → kennisgewings.
	 *
	 * @param int    $comment_id The new comment id.
	 * @param object $comment    The comment object.
	 */
	public function onComment( int $comment_id, $comment ): void {
		if ( ! is_object( $comment ) || self::REAKSIE_COMMENT_TYPE !== ( $comment->comment_type ?? '' ) ) {
			return;
		}

		$post_id  = (int) ( $comment->comment_post_ID ?? 0 );
		$actor_id = (int) ( $comment->user_id ?? 0 );

		if ( $post_id <= 0 ) {
			return;
		}

		// The work's author gets the "new reaksie" kennisgewing (never self).
		$author_id = (int) get_post_field( 'post_author', $post_id );
		Kennisgewings::add( $author_id, NotificationType::Reaksie, $comment_id, $actor_id );

		// Each @mentioned lid gets a "mention" kennisgewing.
		foreach ( self::mentionedLogins( (string) ( $comment->comment_content ?? '' ) ) as $login ) {
			$user = get_user_by( 'login', $login );

			if ( $user instanceof \WP_User ) {
				Kennisgewings::add( (int) $user->ID, NotificationType::Mention, $comment_id, $actor_id );
			}
		}
	}

	/**
	 * Followed-writer new work → fan out to the author's volgelinge.
	 *
	 * @param string $new_status The new post status.
	 * @param string $old_status The previous post status.
	 * @param object $post       The post.
	 */
	public function onTransition( string $new_status, string $old_status, $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status || ! is_object( $post ) ) {
			return;
		}

		if ( ! in_array( (string) ( $post->post_type ?? '' ), PostTypes::readableTypes(), true ) ) {
			return;
		}

		$post_id   = (int) ( $post->ID ?? 0 );
		$author_id = (int) ( $post->post_author ?? 0 );

		if ( $post_id <= 0 || $author_id <= 0 ) {
			return;
		}

		foreach ( SocialApi::followerIdsFor( $author_id ) as $follower_id ) {
			Kennisgewings::add( $follower_id, NotificationType::VolgWerk, $post_id, $author_id );
		}
	}

	/**
	 * Lidmaatskap-expiry reminder — shares the 4.8 lifecycle anchor.
	 *
	 * @param int    $membership_id The membership (item id).
	 * @param int    $user_id       The membership owner (recipient).
	 * @param string $base_key      The warning template base key (unused here).
	 */
	public function onExpiryWarning( int $membership_id, int $user_id, string $base_key ): void {
		unset( $base_key );

		Kennisgewings::add( $user_id, NotificationType::LidmaatskapVerval, $membership_id );
	}

	/**
	 * Pure: extract the @mention logins from a reaksie body.
	 *
	 * Matches `@handle` tokens (letters/digits/_/-), deduped, lowercased; plain
	 * text with no `@`, and an embedded `@` (e.g. an email address), yield none.
	 * Trailing punctuation is excluded. (Resolution to a real user is the caller's.)
	 *
	 * @param string $body The reaksie content.
	 * @return list<string>
	 */
	public static function mentionedLogins( string $body ): array {
		if ( ! preg_match_all( '/(?:^|\s)@([A-Za-z0-9_\-]+)/', $body, $matches ) ) {
			return array();
		}

		$out = array();

		foreach ( $matches[1] as $handle ) {
			$handle = strtolower( $handle );

			if ( '' !== $handle && ! in_array( $handle, $out, true ) ) {
				$out[] = $handle;
			}
		}

		return $out;
	}
}
