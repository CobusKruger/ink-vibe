<?php
/**
 * Read-analytics provider seam + hardening — Story 18.9 (FR-44b, R8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * The vetted-plugin analytics SEAM for read counts (Story 18.9) — analytics is
 * NOT reimplemented in ink-core (project-context: don't reimplement plugin
 * capability). It does two things the naive per-request counter (Story 8.3) could
 * not, which that story explicitly deferred here:
 *
 *  1. **Hardening** — {@see shouldRecordView()} filters obvious bots (by user-agent)
 *     and excludes an author viewing their OWN work, so read counts mean something.
 *  2. **Provider hand-off** — when a vetted analytics plugin is active
 *     (`apply_filters( 'ink_analytics_provider_active', false )`), {@see recordView()}
 *     hands the event to it (`do_action( 'ink/analytics_record_view', … )`) instead of
 *     bumping ink-core's own meta; with no provider it falls back to the existing
 *     {@see ReadCount} denormalized counter. {@see viewCount()} is the symmetric read
 *     seam (the provider can supply a richer count; default = the `_ink_read_count` meta).
 *
 * POPIA/OQ-3: the chosen provider must be privacy-respecting (cookieless /
 * IP-anonymised) — see docs/analytics-provider-decision.md. The pure decisions take
 * primitives so they unit-test without WordPress. Conflation-clean: zero Tiers/Entitlement.
 *
 * @package Ink\Core
 */
final class Analytics {

	/**
	 * User-agent substrings that mark an obvious bot/crawler (lower-cased). A
	 * tripwire, not an exhaustive list — the vetted provider does the real filtering.
	 *
	 * @var list<string>
	 */
	private const BOT_MARKERS = array( 'bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'headless' );

	/**
	 * Whether an obvious bot/crawler sent this request. Pure.
	 *
	 * @param string $user_agent The request user-agent.
	 */
	public static function isBot( string $user_agent ): bool {
		if ( '' === trim( $user_agent ) ) {
			return true; // no UA → treat as non-human (fail-safe against empty-UA bots).
		}

		$ua = strtolower( $user_agent );

		foreach ( self::BOT_MARKERS as $marker ) {
			if ( str_contains( $ua, $marker ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a view should be recorded. Pure: not a bot, and not the author
	 * viewing their own work (self-views must not inflate read counts).
	 *
	 * @param string $user_agent The request user-agent.
	 * @param int    $viewer_id  The current user id (0 = anonymous).
	 * @param int    $author_id  The work's author id.
	 */
	public static function shouldRecordView( string $user_agent, int $viewer_id, int $author_id ): bool {
		if ( self::isBot( $user_agent ) ) {
			return false;
		}

		// An author opening their own work is not a read.
		if ( $viewer_id > 0 && $viewer_id === $author_id ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether a vetted analytics provider is wired. Filter seam.
	 */
	public static function providerActive(): bool {
		return (bool) apply_filters( 'ink_analytics_provider_active', false );
	}

	/**
	 * Record a read view — via the vetted provider when active, else the ink-core
	 * fallback counter ({@see ReadCount}).
	 *
	 * @param int $post_id   The work.
	 * @param int $author_id The work's author (0 when unknown).
	 */
	public static function recordView( int $post_id, int $author_id ): void {
		if ( self::providerActive() ) {
			/**
			 * Fires so the vetted analytics plugin records a read view. ink-core does
			 * NOT also bump its own counter when a provider owns the data.
			 *
			 * @param int $post_id   The work.
			 * @param int $author_id The work's author.
			 */
			do_action( 'ink/analytics_record_view', $post_id, $author_id ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD).

			return;
		}

		ReadCount::incrementPost( $post_id );

		if ( $author_id > 0 ) {
			ReadCount::incrementAuthor( $author_id );
		}
	}

	/**
	 * A work's read count — from the vetted provider when it supplies one, else the
	 * denormalized `_ink_read_count` meta (the My Profiel surface, Story 9.12, reads
	 * this seam so it transparently picks up a provider).
	 *
	 * @param int $post_id The work.
	 */
	public static function viewCount( int $post_id ): int {
		$default = (int) get_post_meta( $post_id, ReadCount::READ_COUNT_META, true );

		return (int) apply_filters( 'ink_analytics_view_count', $default, $post_id );
	}
}
