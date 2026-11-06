<?php
/**
 * Prod view + like counts carried across — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Discovery\ReadCount;
use Ink\Discovery\SkrywerIndex;
use Ink\Engagement\ReactionStore;

defined( 'ABSPATH' ) || exit;

/**
 * `wp ink migrate-counts` — the `counts` step of `migrate-all`: brings the old site's
 * per-work view and like counts across, so nobody sees 400 turn into 0.
 *
 * Prod's counters (owner-confirmed 2026-11-06): views = the theme's
 * `qodef_count_post_views_meta`; likes = `_qodef-like`. (`post_views_count` and
 * `_post_like_count` are other, stale counters and are NOT used.)
 *  - views → `_ink_read_count` (never lowered), and each writer's read total is the
 *    sum of their works' counts (drives "Meeste gelees");
 *  - likes → `ink_reaksie_telling` itself (the work's hartjie count, {@see LikeStore}): primed
 *    with the old figure, then moved +1 / -1 by real members' hearts. No separate legacy field.
 *
 * Idempotent and never lowers a live count, so a re-run cannot clobber views gained since.
 * Once-off, pre-cutover, WP-CLI only.
 *
 * @package Ink\Core
 */
class LegacyCountsImporter {

	public const CLI_COMMAND = 'ink migrate-counts';

	/**
	 * Prod's view counter (the theme's).
	 */
	public const LEGACY_VIEWS_META = 'qodef_count_post_views_meta';

	/**
	 * Prod's like counter.
	 */
	public const LEGACY_LIKES_META = '_qodef-like';

	/**
	 * Register the WP-CLI trigger — ONLY under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function (): void {
				$r = $this->run();
				\WP_CLI::success(
					sprintf(
						'Tellings oorgedra: %d werke (%d lesings, %d likes), %d skrywers se lees-totaal herbereken.',
						$r['works'],
						$r['views'],
						$r['likes'],
						$r['authors']
					)
				);
			}
		);
	}

	/**
	 * The count a work ends up with: the legacy figure, never below what it already has. Pure.
	 *
	 * @param int $legacy  The prod count.
	 * @param int $current The count already on the work.
	 */
	public static function merged( int $legacy, int $current ): int {
		return max( 0, $legacy, $current );
	}

	/**
	 * Carry the counts across.
	 *
	 * @return array{works:int, views:int, likes:int, authors:int}
	 */
	public function run(): array {
		$ids = get_posts(
			array(
				'post_type'        => SkrywerIndex::readableTypes(),
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		$works   = 0;
		$views   = 0;
		$likes   = 0;
		$by_user = array();

		foreach ( $ids as $id ) {
			$id     = (int) $id;
			$legacy = (int) get_post_meta( $id, self::LEGACY_VIEWS_META, true );
			$read   = self::merged( $legacy, (int) get_post_meta( $id, ReadCount::READ_COUNT_META, true ) );

			update_post_meta( $id, ReadCount::READ_COUNT_META, $read );

			$author             = (int) get_post_field( 'post_author', $id );
			$by_user[ $author ] = ( $by_user[ $author ] ?? 0 ) + $read;

			// Prime the field live hearts will update (never lowered: a re-run keeps real likes).
			$like_base = self::merged( (int) get_post_meta( $id, self::LEGACY_LIKES_META, true ), (int) get_post_meta( $id, ReactionStore::TOTAL_META_KEY, true ) );

			update_post_meta( $id, ReactionStore::TOTAL_META_KEY, $like_base );

			if ( $like_base > 0 ) {
				++$likes;
			}

			$views += $read;
			++$works;
		}

		foreach ( $by_user as $user_id => $total ) {
			if ( $user_id > 0 ) {
				update_user_meta( $user_id, SkrywerIndex::READ_TOTAL_META, $total );
			}
		}

		return array(
			'works'   => $works,
			'views'   => $views,
			'likes'   => $likes,
			'authors' => count( $by_user ),
		);
	}
}
