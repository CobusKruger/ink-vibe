<?php
/**
 * Once-off user role reassignment + profile cleanup — Story 16.2 (FL 16.2, FR-2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Kernel\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Collapses every legacy member account onto a single base role and drops legacy
 * profile noise — a once-off, idempotent DB update (FL 16.2, FR-2).
 *
 * Users ride the DB clone (no account import); this command does the scriptable
 * part the migration plan calls out: reassign each non-staff user to the single
 * member base role ({@see BASE_ROLE} = WP `subscriber`) and clean legacy
 * Youzify/BuddyPress profile fields. `WP_User::set_role()` REPLACES all of a
 * user's roles with the one base role, so any legacy reader/writer/Youzify/BP
 * custom role is dropped in the same call — FR-2's "no reader/writer
 * distinction; any member may publish once subscribed".
 *
 * **Staff are preserved**: a user holding `administrator` or `editor` (redakteur)
 * is skipped — demoting an editor to subscriber would lock staff out of the
 * admin. The protected set is sourced from {@see Capabilities} (single source).
 *
 * **Profile cleanup is opt-in**: deleting the wrong meta is destructive and the
 * exact legacy field set is site-specific, so {@see legacyMetaKeys()} defaults to
 * EMPTY (the {@see \Ink\Challenges\Migration::legacyCategories()} safe-default
 * convention). An un-configured run drops legacy roles but cleans no meta.
 *
 * Once-off + guarded: a completion option ({@see OPTION_DONE}) makes a re-run a
 * no-op (`--force` re-runs), and the trigger is WP-CLI only (`wp ink migrate-users`)
 * — NEVER a web request. Conflation-clean: reads `Kernel\Capabilities` + the WP
 * user API only; it never writes `ink_writer_tier` (Story 16.3) and reads no
 * membership (zero `Tiers`/`Entitlement` coupling).
 *
 * Not `final`: the I/O methods are overridable seams so the orchestration is
 * unit-testable without the WordPress user API.
 *
 * @package Ink\Core
 */
class UserReclassifier {

	/**
	 * The completion flag option — set once the reassignment has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_users_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-users`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-users';

	/**
	 * The single member base role (the default self-registration role — every
	 * non-staff account collapses onto it, FR-2).
	 *
	 * @var string
	 */
	public const BASE_ROLE = 'subscriber';

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
						'Gebruikers herklassifiseer: %d na basisrol, %d personeel behou, %d profielvelde opgeruim%s.',
						(int) $summary['reassigned'],
						(int) $summary['staff_preserved'],
						(int) $summary['meta_cleaned'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The roles never demoted to the member base role (staff). Pure.
	 *
	 * @return list<string>
	 */
	public static function preservedRoles(): array {
		return array( Capabilities::ADMIN_ROLE, Capabilities::EDITOR_ROLE );
	}

	/**
	 * Whether a user's role set marks them as staff (one or more preserved roles). Pure.
	 *
	 * @param list<string> $roles The user's current roles.
	 * @return bool
	 */
	public static function isStaff( array $roles ): bool {
		return array() !== array_intersect( $roles, self::preservedRoles() );
	}

	/**
	 * Run the once-off reassignment. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, reassigned:int, staff_preserved:int, meta_cleaned:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'         => true,
				'reassigned'      => 0,
				'staff_preserved' => 0,
				'meta_cleaned'    => 0,
			);
		}

		$reassigned      = 0;
		$staff_preserved = 0;
		$meta_cleaned    = 0;

		foreach ( $this->legacyUserIds() as $user_id ) {
			$user_id = (int) $user_id;

			if ( $user_id <= 0 ) {
				continue;
			}

			if ( self::isStaff( $this->userRoles( $user_id ) ) ) {
				++$staff_preserved;
				continue;
			}

			$this->reassignToBaseRole( $user_id );
			++$reassigned;

			$meta_cleaned += $this->cleanLegacyMeta( $user_id );
		}

		$this->markDone();

		return array(
			'skipped'         => false,
			'reassigned'      => $reassigned,
			'staff_preserved' => $staff_preserved,
			'meta_cleaned'    => $meta_cleaned,
		);
	}

	/**
	 * Whether the reassignment has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the reassignment complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The user ids to reassign. Overridable seam — defaults to every account.
	 *
	 * Unlike the challenge/inkpols migrations (where the SELECTION is ambiguous),
	 * "all users" is the unambiguous, intended scope here: every account collapses
	 * onto the base role, and the {@see isStaff()} guard keeps the operation safe
	 * (staff are never demoted regardless of the input set).
	 *
	 * @return array<int, int|string>
	 */
	protected function legacyUserIds(): array {
		$ids = get_users( array( 'fields' => 'ID' ) );

		return is_array( $ids ) ? $ids : array();
	}

	/**
	 * A user's current roles. Overridable seam.
	 *
	 * @param int $user_id The user id.
	 * @return list<string>
	 */
	protected function userRoles( int $user_id ): array {
		$user = get_userdata( $user_id );

		if ( false === $user ) {
			return array();
		}

		return array_values( array_map( 'strval', (array) $user->roles ) );
	}

	/**
	 * Replace ALL of a user's roles with the single base role. Overridable seam.
	 *
	 * `set_role()` clears every existing role first, so legacy reader/writer/
	 * Youzify/BuddyPress custom roles are dropped in this one call (FR-2).
	 *
	 * @param int $user_id The user id.
	 */
	protected function reassignToBaseRole( int $user_id ): void {
		$user = new \WP_User( $user_id );
		$user->set_role( self::BASE_ROLE );
	}

	/**
	 * The legacy profile meta keys to delete. Overridable seam.
	 *
	 * SAFE DEFAULT: EMPTY. Deleting profile meta is destructive and the exact
	 * Youzify/BuddyPress field set is site-specific ("scriptable once the field
	 * mapping is confirmed"), so a site MUST override this with the confirmed
	 * legacy keys before the once-off run; an un-overridden run cleans no meta.
	 *
	 * @return list<string>
	 */
	protected function legacyMetaKeys(): array {
		return array();
	}

	/**
	 * Delete the configured legacy profile meta for one user. Overridable seam.
	 *
	 * @param int $user_id The user id.
	 * @return int The number of meta keys cleaned.
	 */
	protected function cleanLegacyMeta( int $user_id ): int {
		$cleaned = 0;

		foreach ( $this->legacyMetaKeys() as $key ) {
			$key = (string) $key;

			if ( '' === $key ) {
				continue;
			}

			delete_user_meta( $user_id, $key );
			++$cleaned;
		}

		return $cleaned;
	}
}
