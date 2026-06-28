<?php
/**
 * Once-off DB clone sanitiser — Story 16.1 (FL 16.1, migration order step 1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Strips the cloned production DB to a clean migration baseline — a once-off,
 * idempotent DB sanitise (FL 16.1).
 *
 * Removes two classes of disposable rows and NOTHING else:
 *   1. **Transients** — every `_transient_*` / `_site_transient_*` option row
 *      (cache; regenerates on demand), matched with `esc_like()`-escaped LIKE
 *      prefixes so a literal underscore is never read as a wildcard.
 *   2. **Action Scheduler logs** — finished actions ({@see purgeableActionStatuses()}:
 *      `complete`/`failed`/`canceled`) and their orphaned log rows. PENDING and
 *      in-progress actions are PRESERVED — dropping them would lose live scheduled
 *      work. Every table touch is guarded by {@see tableExists()} (the clone may
 *      not carry the tables).
 *
 * Everything migration must preserve — members, subscriptions, content, media —
 * survives by omission: this class never issues a write against a user, post,
 * term, comment, or WooCommerce/Memberships table. The blast radius is the two
 * transient option-name namespaces plus the two `actionscheduler_*` tables.
 *
 * Once-off + guarded: a completion option ({@see OPTION_DONE}) makes a re-run a
 * no-op (a `--force` re-run is opt-in), and the trigger is WP-CLI only
 * (`wp ink migrate-sanitise`) — NEVER auto-run on a web request (production
 * hygiene). Conflation-clean: touches only `$wpdb` + WP options; zero
 * `Ink\Tiers`/`Ink\Entitlement` coupling.
 *
 * Not `final`: the I/O methods are overridable seams so the orchestration is
 * unit-testable without a database (the {@see \Ink\Challenges\Migration} precedent).
 *
 * @package Ink\Core
 */
class DbSanitiser {

	/**
	 * The completion flag option — set once the sanitiser has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_sanitise_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-sanitise`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-sanitise';

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
						'Databasis gesuiwer: %d tydelike inskrywings, %d voltooide take, %d wees-logboekinskrywings verwyder%s.',
						(int) $summary['transients'],
						(int) $summary['actions'],
						(int) $summary['logs'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The option-name LIKE prefixes for the transient rows to delete. Pure.
	 *
	 * Returned WITHOUT the trailing `%` and unescaped — the deleting seam wraps
	 * each in `esc_like( $prefix ) . '%'` so the literal underscores never act as
	 * single-char wildcards. Every prefix is confined to a transient namespace,
	 * so the LIKE can never reach a non-transient option (the safety invariant
	 * asserted by the guard test).
	 *
	 * @return list<string>
	 */
	public static function transientLikePrefixes(): array {
		return array(
			'_transient_',
			'_transient_timeout_',
			'_site_transient_',
			'_site_transient_timeout_',
		);
	}

	/**
	 * The Action Scheduler statuses safe to purge as log noise. Pure.
	 *
	 * Deliberately EXCLUDES `pending` and `in-progress` — those are live scheduled
	 * work, not logs, and dropping them would lose queued jobs on the new host.
	 *
	 * @return list<string>
	 */
	public static function purgeableActionStatuses(): array {
		return array( 'complete', 'failed', 'canceled' );
	}

	/**
	 * Run the once-off sanitise. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, transients:int, actions:int, logs:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'    => true,
				'transients' => 0,
				'actions'    => 0,
				'logs'       => 0,
			);
		}

		$transients = $this->deleteTransients();
		$actions    = $this->deleteFinishedActions();
		$logs       = $this->deleteOrphanLogs();

		$this->markDone();

		return array(
			'skipped'    => false,
			'transients' => $transients,
			'actions'    => $actions,
			'logs'       => $logs,
		);
	}

	/**
	 * Whether the sanitise has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the sanitise complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * Delete every transient option row. Overridable seam.
	 *
	 * Each prefix is `esc_like()`-escaped (literal underscores stay literal) and
	 * the LIKE bound through `prepare()`. Overlapping prefixes are harmless: the
	 * broader `_transient_%` delete removes the timeout rows too, so the narrower
	 * follow-up simply affects zero rows — the total is never inflated.
	 *
	 * @return int Rows removed.
	 */
	protected function deleteTransients(): int {
		global $wpdb;

		$deleted = 0;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- once-off CLI sanitise; caching is irrelevant and the migration must hit the DB directly.
		foreach ( self::transientLikePrefixes() as $prefix ) {
			$like     = $wpdb->esc_like( $prefix ) . '%';
			$affected = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$like
				)
			);

			$deleted += is_numeric( $affected ) ? (int) $affected : 0;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $deleted;
	}

	/**
	 * Delete finished Action Scheduler actions (NOT pending/in-progress).
	 * Overridable seam; a no-op when the table is absent on the clone.
	 *
	 * @return int Rows removed.
	 */
	protected function deleteFinishedActions(): int {
		global $wpdb;

		$table = $wpdb->prefix . 'actionscheduler_actions';

		if ( ! $this->tableExists( $table ) ) {
			return 0;
		}

		$statuses     = self::purgeableActionStatuses();
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- once-off CLI sanitise; $table derives from $wpdb->prefix and $placeholders is a fixed-count list of %s bound via prepare() over $statuses.
		$affected = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE status IN ({$placeholders})", $statuses ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return is_numeric( $affected ) ? (int) $affected : 0;
	}

	/**
	 * Delete Action Scheduler log rows orphaned by the finished-action purge.
	 * Overridable seam; a no-op when the logs table is absent.
	 *
	 * @return int Rows removed.
	 */
	protected function deleteOrphanLogs(): int {
		global $wpdb;

		$logs    = $wpdb->prefix . 'actionscheduler_logs';
		$actions = $wpdb->prefix . 'actionscheduler_actions';

		if ( ! $this->tableExists( $logs ) ) {
			return 0;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- once-off CLI sanitise; both table names derive from $wpdb->prefix and no user input is interpolated, so there is nothing to bind.
		$affected = $wpdb->query( "DELETE l FROM {$logs} l LEFT JOIN {$actions} a ON l.action_id = a.action_id WHERE a.action_id IS NULL" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return is_numeric( $affected ) ? (int) $affected : 0;
	}

	/**
	 * Whether a DB table exists. Overridable seam.
	 *
	 * @param string $table The fully-prefixed table name.
	 * @return bool
	 */
	protected function tableExists( string $table ): bool {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- once-off CLI table-existence probe before a migration delete.
		$found = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_string( $found ) && $found === $table;
	}
}
