<?php
/**
 * Trending-score ("Opspraakwekkend") recomputation — Story 8.2 (FR-33, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;
use Ink\Engagement\Api as EngagementApi;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the `ink_trending_score` post-meta + its Action Scheduler refresh job.
 *
 * "Opspraakwekkend" is a STORED, recomputed score (AD-7) — never computed live
 * per request. A daily Action Scheduler action (group `ink`) recomputes the score
 * for every published bydrae from the denormalized reaction total
 * ({@see EngagementApi::reactionTotalMetaKey()}) and the work's age, so the
 * Ontdek sort is a cheap `orderby=meta_value_num`. The score is a pure,
 * recency-weighted gravity function: a fresh, modestly-reacted work can out-rank
 * an old work whose engagement has gone stale.
 *
 * Conflation-clean: references only `Ink\Content\PostTypes` (slug source) + the
 * `Ink\Engagement\Api` facade (the denormalized-count contract) + WP/Action
 * Scheduler — zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class TrendingScore {

	/**
	 * Post-meta key holding the stored trending score (single source).
	 *
	 * @var string
	 */
	public const META_KEY = 'ink_trending_score';

	/**
	 * The Action Scheduler hook that triggers a full recompute.
	 *
	 * @var string
	 */
	public const HOOK_RECOMPUTE = 'ink_discovery_recompute_trending';

	/**
	 * The Action Scheduler group (shared INK namespace).
	 *
	 * @var string
	 */
	public const AS_GROUP = 'ink';

	/**
	 * How many published bydraes to score per query page during a recompute.
	 *
	 * @var int
	 */
	private const BATCH = 200;

	/**
	 * Wire the recompute callback + (idempotently) schedule the daily job.
	 */
	public function register(): void {
		add_action( self::HOOK_RECOMPUTE, array( self::class, 'recomputeAll' ) );
		add_action( 'init', array( self::class, 'maybeSchedule' ) );
	}

	/**
	 * Schedule the daily recompute once, when Action Scheduler is present and the
	 * action is not already scheduled. Graceful no-op without Action Scheduler.
	 */
	public static function maybeSchedule(): void {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		if ( false !== as_next_scheduled_action( self::HOOK_RECOMPUTE, array(), self::AS_GROUP ) ) {
			return;
		}

		as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::HOOK_RECOMPUTE, array(), self::AS_GROUP );
	}

	/**
	 * The readable bydrae types scored for trending (skryfwerk bucket excluded).
	 *
	 * @return list<string>
	 */
	public static function scorableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * Recency-weighted gravity score. Pure — no WordPress, no DB.
	 *
	 * Monotonic in the reaction total (fixed age) and decaying with age (fixed
	 * reactions), so a newer, more-reacted work ranks above an old, stale one.
	 * `+1`/`+2` keep a zero-reaction or same-day work finite and ordered.
	 *
	 * @param int $reaction_total The denormalized total reaction count.
	 * @param int $age_days       The work's age in days (clamped to >= 0).
	 * @return float
	 */
	public static function compute( int $reaction_total, int $age_days ): float {
		$reactions = max( 0, $reaction_total );
		$age       = max( 0, $age_days );

		return ( $reactions + 1 ) / pow( $age + 2, 1.5 );
	}

	/**
	 * Recompute + persist the trending score for every published bydrae.
	 *
	 * Action Scheduler callback. Reads the denormalized reaction total (seeding it
	 * to 0 when absent, for migrated content) and the work's age, then writes the
	 * stored score. Batched so the meta query stays bounded.
	 */
	public static function recomputeAll(): void {
		$total_key = EngagementApi::reactionTotalMetaKey();
		$now       = time();
		$paged     = 1;

		do {
			$query = new \WP_Query(
				array(
					'post_type'              => self::scorableTypes(),
					'post_status'            => 'publish',
					'posts_per_page'         => self::BATCH,
					'paged'                  => $paged,
					'fields'                 => 'ids',
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'ignore_sticky_posts'    => true,
				)
			);

			foreach ( $query->posts as $post_id ) {
				$post_id = (int) $post_id;

				$stored = get_post_meta( $post_id, $total_key, true );

				if ( '' === (string) $stored ) {
					// Seed the denormalized total for migrated/legacy content.
					update_post_meta( $post_id, $total_key, 0 );
					$reaction_total = 0;
				} else {
					$reaction_total = (int) $stored;
				}

				$age_days = self::ageInDays( get_post_time( 'U', true, $post_id ), $now );

				update_post_meta( $post_id, self::META_KEY, self::compute( $reaction_total, $age_days ) );
			}

			++$paged;
		} while ( (int) $query->max_num_pages >= $paged );
	}

	/**
	 * Whole days between a GMT publish timestamp and now (>= 0). Pure.
	 *
	 * @param int|false $published_gmt The publish time as a GMT unix timestamp.
	 * @param int       $now           The current unix timestamp.
	 * @return int
	 */
	public static function ageInDays( $published_gmt, int $now ): int {
		if ( ! is_int( $published_gmt ) || $published_gmt <= 0 ) {
			return 0;
		}

		return (int) max( 0, floor( ( $now - $published_gmt ) / DAY_IN_SECONDS ) );
	}
}
