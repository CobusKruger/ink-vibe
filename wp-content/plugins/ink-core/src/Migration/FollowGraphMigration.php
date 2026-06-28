<?php
/**
 * Once-off BuddyPress friendship → follow migration — Story 16.9 (FL 16.9, MR-8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Social\FollowStore;

defined( 'ABSPATH' ) || exit;

/**
 * Transforms the legacy BuddyPress friend graph into INK's asymmetric follow
 * graph — a once-off, idempotent migration (FL 16.9, MR-8).
 *
 * BuddyPress Friend Connections are OFF in the new site, so the cloned friend
 * tables are READ and transformed, never the live store. Each CONFIRMED
 * friendship (A,B) becomes TWO directed follow records — A→B and B→A — written
 * through the canonical {@see FollowStore::follow()} (whose unique-edge table
 * dedups naturally). Pending friend requests are NOT converted; self-edges and
 * non-positive ids are skipped; an edge whose follower or followee is not a valid
 * imported account is skipped (orphaned).
 *
 * BuddyPress activity older than {@see ACTIVITY_RETENTION_YEARS} is trimmed;
 * private messaging is deferred (it rides the DB clone, untouched here).
 *
 * Once-off + guarded ({@see OPTION_DONE}; `--force` re-runs); WP-CLI only
 * (`wp ink migrate-follows`) — never a web request. Conflation-clean: writes the
 * follow graph via `Social\FollowStore` only; zero `Tiers`/`Entitlement`.
 *
 * Not `final`: the BP/follow/user methods are overridable seams so the transform
 * logic is unit-testable without BuddyPress or the follow table.
 *
 * @package Ink\Core
 */
class FollowGraphMigration {

	/**
	 * The completion flag option — set once the migration has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_follows_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-follows`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-follows';

	/**
	 * How many years of BuddyPress activity to retain (older is trimmed).
	 *
	 * @var int
	 */
	public const ACTIVITY_RETENTION_YEARS = 2;

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI (never a web request).
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			return;
		}

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );
				\WP_CLI::success(
					sprintf(
						'Vriendskappe → volg: %d volg-rekords geskep, %d hangend oorgeslaan, %d wees-rande oorgeslaan, %d aktiwiteite gesnoei%s.',
						(int) $summary['follows_created'],
						(int) $summary['pending_skipped'],
						(int) $summary['orphaned_skipped'],
						(int) $summary['activity_trimmed'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The deduped directed follow pairs for the CONFIRMED friendships. Pure.
	 *
	 * Each confirmed friendship (A,B) yields `[A,B]` and `[B,A]`. Pending
	 * friendships, self-edges, and non-positive ids are skipped; reciprocal /
	 * duplicate rows collapse to one directed pair each.
	 *
	 * @param array<int, array{initiator_user_id?:int, friend_user_id?:int, is_confirmed?:mixed}> $friendships Raw friendship rows.
	 * @return list<array{0:int,1:int}>
	 */
	public static function followPairsFromFriendships( array $friendships ): array {
		$seen  = array();
		$pairs = array();

		foreach ( $friendships as $friendship ) {
			if ( empty( $friendship['is_confirmed'] ) ) {
				continue; // pending — never converted.
			}

			$a = (int) ( $friendship['initiator_user_id'] ?? 0 );
			$b = (int) ( $friendship['friend_user_id'] ?? 0 );

			if ( $a <= 0 || $b <= 0 || $a === $b ) {
				continue;
			}

			foreach ( array( array( $a, $b ), array( $b, $a ) ) as $pair ) {
				$key = $pair[0] . ':' . $pair[1];

				if ( isset( $seen[ $key ] ) ) {
					continue;
				}

				$seen[ $key ] = true;
				$pairs[]      = $pair;
			}
		}

		return $pairs;
	}

	/**
	 * The activity-trim cutoff date (now − retention years). Pure.
	 *
	 * @param string $now A `Y-m-d H:i:s` timestamp.
	 * @return string The cutoff `Y-m-d H:i:s`.
	 */
	public static function cutoffDate( string $now ): string {
		$base = date_create_immutable( $now );

		if ( false === $base ) {
			$base = date_create_immutable( '@0' );
		}

		return $base->modify( '-' . self::ACTIVITY_RETENTION_YEARS . ' years' )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Run the once-off migration. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, follows_created:int, pending_skipped:int, orphaned_skipped:int, activity_trimmed:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'          => true,
				'follows_created'  => 0,
				'pending_skipped'  => 0,
				'orphaned_skipped' => 0,
				'activity_trimmed' => 0,
			);
		}

		$friendships     = $this->friendships();
		$pending_skipped = 0;

		foreach ( $friendships as $friendship ) {
			if ( empty( $friendship['is_confirmed'] ) ) {
				++$pending_skipped;
			}
		}

		$pairs            = self::followPairsFromFriendships( $friendships );
		$follows_created  = 0;
		$orphaned_skipped = 0;

		foreach ( $pairs as $pair ) {
			$follower = $pair[0];
			$followee = $pair[1];

			if ( ! $this->validUser( $follower ) || ! $this->validUser( $followee ) ) {
				++$orphaned_skipped;
				continue;
			}

			if ( $this->recordFollow( $follower, $followee ) ) {
				++$follows_created;
			}
		}

		$activity_trimmed = $this->trimOldActivity( self::cutoffDate( $this->now() ) );

		$this->markDone();

		return array(
			'skipped'          => false,
			'follows_created'  => $follows_created,
			'pending_skipped'  => $pending_skipped,
			'orphaned_skipped' => $orphaned_skipped,
			'activity_trimmed' => $activity_trimmed,
		);
	}

	/**
	 * Whether the migration has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the migration complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The legacy BuddyPress friendship rows. Overridable seam.
	 *
	 * Default reads the `{prefix}bp_friends` table (a no-op when absent), returning
	 * `{initiator_user_id, friend_user_id, is_confirmed}` rows.
	 *
	 * @return array<int, array{initiator_user_id:int, friend_user_id:int, is_confirmed:int}>
	 */
	protected function friendships(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'bp_friends';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- once-off CLI read of the legacy BP friends table (name derived from $wpdb->prefix; no user input).
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		if ( $exists !== $table ) {
			return array();
		}

		$rows = $wpdb->get_results( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$table}", ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn ( array $row ): array => array(
				'initiator_user_id' => (int) ( $row['initiator_user_id'] ?? 0 ),
				'friend_user_id'    => (int) ( $row['friend_user_id'] ?? 0 ),
				'is_confirmed'      => (int) ( $row['is_confirmed'] ?? 0 ),
			),
			$rows
		);
	}

	/**
	 * Whether a user id is a valid imported account (not orphaned). Overridable seam.
	 *
	 * @param int $user_id The user id.
	 * @return bool
	 */
	protected function validUser( int $user_id ): bool {
		return $user_id > 0 && false !== get_userdata( $user_id );
	}

	/**
	 * Record one directed follow through the canonical store. Overridable seam.
	 *
	 * @param int $follower The follower user id.
	 * @param int $followee The followed user id.
	 * @return bool Whether a record was written.
	 */
	protected function recordFollow( int $follower, int $followee ): bool {
		return FollowStore::follow( $follower, $followee );
	}

	/**
	 * Trim BuddyPress activity older than the cutoff. Overridable seam.
	 *
	 * A no-op when the activity table is absent. Messaging is NOT touched.
	 *
	 * @param string $cutoff The `Y-m-d H:i:s` cutoff (older rows are removed).
	 * @return int Rows removed.
	 */
	protected function trimOldActivity( string $cutoff ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'bp_activity';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- once-off CLI trim of the legacy BP activity table (name derived from $wpdb->prefix; cutoff bound via prepare()).
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		if ( $exists !== $table ) {
			return 0;
		}

		$affected = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE date_recorded < %s", $cutoff ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return is_numeric( $affected ) ? (int) $affected : 0;
	}

	/**
	 * The current timestamp (timezone-aware). Overridable seam.
	 *
	 * @return string A `Y-m-d H:i:s` timestamp.
	 */
	protected function now(): string {
		return (string) current_time( 'mysql' );
	}
}
