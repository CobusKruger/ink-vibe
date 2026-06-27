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
 * - lidmaatskap-expiry → the 4.8 post-gate event ({@see LifecycleEmails::EVENT_EXPIRY_WARNED}),
 *   fired only AFTER the lifecycle email passes every live staleness re-check — so
 *   the in-app reminder shares the schedule AND the gates (a renewed/revoked member
 *   never gets a false expiry alert). Subscribing to the raw schedule hook would skip
 *   those gates.
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
	 * Post-meta sentinel: set once a work's "new work" fan-out has fired, so a
	 * later republish never re-announces it to the author's volgelinge.
	 */
	public const ANNOUNCED_META = '_ink_volg_aangekondig';

	/**
	 * Subscribe the sources on `init`.
	 */
	public function register(): void {
		add_action( 'wp_insert_comment', array( $this, 'onComment' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'onTransition' ), 10, 3 );
		add_action( LifecycleEmails::EVENT_EXPIRY_WARNED, array( $this, 'onExpiryWarning' ), 10, 3 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD-6).
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

		// Only published works notify — `wp_insert_comment` fires off the RAW insert,
		// so a programmatic `ink_reaksie` on a draft/trashed/private post (import,
		// tooling) must not emit a kennisgewing for a non-public work.
		if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) ) {
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

		// Announce new work at most ONCE per post: a later unpublish→republish (or a
		// restore from pending/trash/draft) is a non-publish→publish transition that
		// would otherwise re-fan-out "new work" to every volgeling. A one-time
		// post-meta sentinel makes the fan-out idempotent across re-publish cycles.
		if ( '' !== (string) get_post_meta( $post_id, self::ANNOUNCED_META, true ) ) {
			return;
		}

		update_post_meta( $post_id, self::ANNOUNCED_META, current_time( 'mysql', true ) );

		foreach ( SocialApi::followerIdsFor( $author_id ) as $follower_id ) {
			Kennisgewings::add( $follower_id, NotificationType::VolgWerk, $post_id, $author_id );
		}
	}

	/**
	 * Lidmaatskap-expiry reminder — fired off the 4.8 POST-GATE event
	 * ({@see LifecycleEmails::EVENT_EXPIRY_WARNED}), so it only runs when the email
	 * actually sent (all live staleness gates passed). Payload is recipient-first.
	 *
	 * @param int    $user_id       The membership owner (recipient).
	 * @param int    $membership_id The membership (item id).
	 * @param string $base_key      The warning template base key (unused here).
	 */
	public function onExpiryWarning( int $user_id, int $membership_id, string $base_key ): void {
		unset( $base_key );

		Kennisgewings::add( $user_id, NotificationType::LidmaatskapVerval, $membership_id );
	}

	/**
	 * Pure: extract the @mention logins from a reaksie body.
	 *
	 * Matches `@handle` tokens (letters/digits/_/-), deduped, lowercased; plain
	 * text with no `@`, and an embedded `@` (e.g. an email address), yield none.
	 * A negative lookbehind for a word char means a handle preceded by punctuation
	 * — `(@anja)`, `@jan,@piet`, `—@kobus` — still matches, while `jan@example.com`
	 * (preceded by `n`) does not. (Resolution to a real user is the caller's.)
	 *
	 * @param string $body The reaksie content.
	 * @return list<string>
	 */
	public static function mentionedLogins( string $body ): array {
		if ( ! preg_match_all( '/(?<![A-Za-z0-9_])@([A-Za-z0-9_\-]+)/', $body, $matches ) ) {
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
