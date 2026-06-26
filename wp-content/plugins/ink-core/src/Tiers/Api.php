<?php
/**
 * Tiers module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\I18n\Terms;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * Tiers module facade — the sole public cross-module surface for Tiers (Epic 5).
 *
 * Exposes the Gradering read API (used by Challenges for pool segmentation and
 * by the profile/discovery surfaces); `promote()` (the sole tier write path)
 * lands in Story 5.7/5.8. Other modules reach Tiers only through this facade
 * (AD-1). MUST NOT reference `Ink\Entitlement\*` (THE conflation rule) and reads
 * only the Kernel `Tier` value type + WordPress — never another domain module
 * (deptrac `Tiers: [Kernel]`).
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The typed, default-safe writer Gradering for a user.
	 *
	 * THE read path every Epic-5 consumer must use instead of a raw
	 * `get_user_meta()`. The `register_meta` `default` ({@see \Ink\Content\UserMeta})
	 * only resolves on WP's default-aware read paths, so a raw
	 * `get_user_meta( $id, 'ink_writer_tier', true )` for a writer who never had a
	 * tier written returns `''` — not `brons`. This accessor guarantees a `Tier`
	 * case in every case: an unset/empty/non-scalar value yields
	 * {@see Tier::default()} (Brons), and any unrecognised stored string is
	 * coerced back to the default — never `null`, never a raw string. Closes the
	 * Epic-2 review deferral on Story 2.3.
	 *
	 * @param int $user_id The WordPress user id.
	 * @return Tier The writer's Gradering, defaulting to Brons.
	 */
	public static function forUser( int $user_id ): Tier {
		$raw = get_user_meta( $user_id, Tier::META_KEY, true );

		if ( ! is_scalar( $raw ) || '' === (string) $raw ) {
			return Tier::default();
		}

		return Tier::tryFrom( (string) $raw ) ?? Tier::default();
	}

	/**
	 * THE sole write path for `ink_writer_tier`.
	 *
	 * Every Gradering change — manual (Story 5.2 admin UI) or automatic (Story
	 * 5.8 engine, which calls this with `$actor_id = 0`) — goes through here:
	 * it writes the grade, stamps a normalised GMT `ink_tier_promoted_at`,
	 * appends the append-only `graderingsgeskiedenis` audit record
	 * ({@see PromotionLog::record()}), and fires the `ink/tier_promoted` event
	 * (the seam Story 5.10's congratulation email subscribes to). A no-op change
	 * (`$from === $to`) writes nothing, logs nothing, fires nothing, returns
	 * false.
	 *
	 * The `ink_tier_win_count` counter is reset to 0 inside this method on every
	 * promotion (Story 5.7). THE conflation rule (AD-1): this reads/writes only
	 * the Kernel `Tier` + this module's log + WordPress; it never references
	 * `Ink\Entitlement`.
	 *
	 * If the audit append fails to persist (e.g. the `ink_tier_history` table is
	 * missing on an un-upgraded install, or a DB error), the promotion itself
	 * still stands but the failure is surfaced — the `ink/tier_promotion_log_failed`
	 * monitoring seam fires and, under `WP_DEBUG`, a developer warning is emitted —
	 * rather than the FR-12 audit row being dropped silently.
	 *
	 * @param int    $user_id      The writer whose grade changes.
	 * @param Tier   $to           The target grade.
	 * @param int    $actor_id     The acting staff user id, or 0 for the automatic engine.
	 * @param string $reason       The reason recorded in the audit log.
	 * @param int    $challenge_id Optional linked challenge id (0 = none).
	 * @return bool True when a change was applied; false on a no-op.
	 */
	public static function promote(
		int $user_id,
		Tier $to,
		int $actor_id = 0,
		string $reason = '',
		int $challenge_id = 0
	): bool {
		$from = self::forUser( $user_id );

		if ( $from === $to ) {
			return false;
		}

		update_user_meta( $user_id, Tier::META_KEY, $to->value );
		update_user_meta( $user_id, Tier::PROMOTED_AT_META_KEY, current_time( 'mysql', true ) );

		// Reset the win counter on every promotion (Story 5.7, R3) — accumulation
		// toward the next Gradering restarts at the new grade.
		update_user_meta( $user_id, Tier::WIN_COUNT_META_KEY, 0 );

		$logged = PromotionLog::record( $user_id, $from, $to, $actor_id, $reason, $challenge_id );

		if ( false === $logged ) {
			/**
			 * Fires when a committed Gradering change could NOT persist its
			 * append-only audit row (FR-12) — the promotion stands, but the
			 * history is incomplete. A monitoring/alerting seam so the loss is
			 * observable rather than silent.
			 *
			 * @param int  $user_id      The writer.
			 * @param Tier $from         The previous grade.
			 * @param Tier $to           The new grade.
			 * @param int  $actor_id     The acting staff id, or 0 for the automatic engine.
			 * @param int  $challenge_id The linked challenge id (0 = none).
			 */
			do_action( 'ink/tier_promotion_log_failed', $user_id, $from, $to, $actor_id, $challenge_id ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD).

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				wp_trigger_error(
					__METHOD__,
					sprintf( 'Gradering audit row failed to persist for user %d (the ink_tier_history table may be missing on an un-upgraded install).', $user_id ),
					E_USER_WARNING
				);
			}
		}

		/**
		 * Fires after a writer's Gradering changes (the seam for the Story 5.10
		 * congratulation email and any future audit/notification consumer).
		 *
		 * @param int  $user_id      The writer.
		 * @param Tier $from         The previous grade.
		 * @param Tier $to           The new grade.
		 * @param int  $actor_id     The acting staff id, or 0 for the automatic engine.
		 * @param int  $challenge_id The linked challenge id (0 = none).
		 */
		// The `ink/...` slash namespace is INK's deliberate event-surface
		// convention (architecture.md line 483), distinguishing first-party domain
		// events from WordPress core/plugin hooks; WPCS's underscore preference is
		// intentionally overridden for this surface.
		do_action( 'ink/tier_promoted', $user_id, $from, $to, $actor_id, $challenge_id ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD).

		return true;
	}

	/**
	 * The writer's current accumulated top-3 win count toward the next Gradering.
	 *
	 * Reads `ink_tier_win_count` (Story 5.7), defaulting to 0 for an unset/junk
	 * value — the typed read path consumers use instead of raw `get_user_meta`.
	 * The count is floored at 0 on read so a negative stored value (a manual DB
	 * edit, or a legacy pre-`absint` value) can never leak a negative count into
	 * the accumulator or the Story 5.8 threshold comparison.
	 *
	 * @param int $user_id The writer.
	 */
	public static function winCountForUser( int $user_id ): int {
		$raw = get_user_meta( $user_id, Tier::WIN_COUNT_META_KEY, true );

		return is_scalar( $raw ) ? max( 0, (int) $raw ) : 0;
	}

	/**
	 * Accumulate top-3 wins onto the writer's counter, returning the new total.
	 *
	 * The dumb accumulator: it records wins but does NOT check thresholds or
	 * trigger a promotion — that is the Story 5.8 engine (which calls this, then
	 * compares against {@see Tier::isAutoPromotable()} + the 5/15 thresholds, then
	 * calls {@see self::promote()}). A non-positive `$count` never decreases the
	 * counter and writes nothing — the current total is returned untouched, so a
	 * no-op call fires no `update_user_meta` hooks.
	 *
	 * @param int $user_id The writer.
	 * @param int $count   The number of wins to add (default 1).
	 * @return int The new accumulated total.
	 */
	public static function recordWin( int $user_id, int $count = 1 ): int {
		$current = self::winCountForUser( $user_id );
		$add     = max( 0, $count );

		if ( 0 === $add ) {
			return $current;
		}

		$new = $current + $add;

		update_user_meta( $user_id, Tier::WIN_COUNT_META_KEY, $new );

		return $new;
	}

	/**
	 * Award top-3 win(s) and auto-promote on a threshold — the cross-module
	 * facade for the automatic promotion engine (Story 5.8).
	 *
	 * The surface a future Challenges / R2-ingestion step (Story 12A.3) calls;
	 * delegates to {@see PromotionEngine::award()} (Brons→Silwer at 5,
	 * Silwer→Goud at 15; Goud/Meester have no auto-threshold). Promotions are
	 * recorded as `actor_id = 0` (system).
	 *
	 * @param int $user_id      The writer.
	 * @param int $wins         The number of top-3 wins to award (default 1).
	 * @param int $challenge_id Optional linked challenge id.
	 * @return Tier|null The new grade if promoted; null otherwise.
	 */
	public static function awardWins( int $user_id, int $wins = 1, int $challenge_id = 0 ): ?Tier {
		return PromotionEngine::award( $user_id, $wins, $challenge_id );
	}

	/**
	 * The presentation-ready view of a writer's Gradering (Story 5.4).
	 *
	 * The single source for profile display: the typed grade ({@see self::forUser()}),
	 * its glossary label, and the Meester-is-special flag. The theme bridge
	 * renders the accessible badge from this view; Story 9.4 embeds it on the
	 * Skrywerprofiel + My Profiel templates.
	 *
	 * @param int $user_id The writer.
	 */
	public static function gradingView( int $user_id ): GraderingView {
		$tier = self::forUser( $user_id );

		return new GraderingView(
			$tier,
			Terms::label( $tier->value ),
			Tier::Meester === $tier,
		);
	}

	/**
	 * The private-My-Profiel "wins needed" subtext for a writer (Story 5.9).
	 *
	 * Returns the Afrikaans `_n()` sentence (e.g. "4 top 3 uitslae nodig om Silwer
	 * te bereik" / "1 top 3 uitslag nodig om …") toward the next Gradering, or
	 * null when the writer is at a terminal grade (Goud/Meester — the subtext is
	 * hidden). The 5/15 thresholds come from the single-source
	 * {@see PromotionEngine::progressFor()}; the next-grade label from `Terms`.
	 *
	 * @param int $user_id The writer.
	 */
	public static function winsNeededSubtext( int $user_id ): ?string {
		$progress = PromotionEngine::progressFor(
			self::forUser( $user_id ),
			self::winCountForUser( $user_id )
		);

		if ( null === $progress ) {
			return null;
		}

		return sprintf(
			/* translators: 1: number of top-3 wins still needed; 2: the next Gradering label. */
			_n(
				'%1$d top 3 uitslag nodig om %2$s te bereik',
				'%1$d top 3 uitslae nodig om %2$s te bereik',
				$progress['needed'],
				'ink-core'
			),
			$progress['needed'],
			Terms::label( $progress['next']->value )
		);
	}

	/**
	 * The user IDs of writers at a given Gradering — the discovery filter /
	 * segmentation primitive (Story 5.5).
	 *
	 * A `get_users()` meta filter on the single-source `ink_writer_tier` key
	 * (never raw SQL). Optional caller args (e.g. `number`, `paged`) are merged,
	 * but the grade filter and `fields => 'ID'` are authoritative — a caller
	 * cannot override `meta_key`/`meta_value`/`fields`. Returns positive int IDs
	 * only. The Epic-8 Ontdek filters consume this.
	 *
	 * @param Tier                 $tier The grade to filter by.
	 * @param array<string, mixed> $args Optional extra `WP_User_Query` args.
	 * @return list<int> Writer user IDs at the grade.
	 */
	public static function usersByGrade( Tier $tier, array $args = array() ): array {
		// Filtering writers by their Gradering meta is the intended query; the
		// slow-query advisory is accepted (the result set is staff/discovery-scoped
		// and callers pass paging args). Caller `$args` go FIRST so the grade
		// filter + `fields => 'ID'` are authoritative and cannot be overridden
		// (a caller passing `fields => 'all'` would otherwise return WP_User
		// objects that the int cast below mangles).
		$users = get_users(
			array_merge(
				$args,
				array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'   => Tier::META_KEY,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value' => $tier->value,
					'fields'     => 'ID',
				)
			)
		);

		$ids = array_map( 'intval', (array) $users );

		return array_values( array_filter( $ids, static fn ( int $id ): bool => $id > 0 ) );
	}

	/**
	 * Compose a winner label carrying the Gradering context (Story 5.5), e.g.
	 * "Oktober Goud-wenner".
	 *
	 * The grade label + the "wenner" term come from the single-source `Terms`
	 * registry (glossary-backed); the period is supplied by the caller (the
	 * Epic-12 challenge surface).
	 *
	 * @param Tier   $grade  The winner's Gradering.
	 * @param string $period The challenge period label (e.g. "Oktober").
	 */
	public static function winnerLabel( Tier $grade, string $period ): string {
		return sprintf(
			'%1$s %2$s-%3$s',
			$period,
			Terms::label( $grade->value ),
			Terms::label( 'wenner' )
		);
	}
}
