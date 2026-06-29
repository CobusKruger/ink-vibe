<?php
/**
 * Staff two-factor-authentication coverage audit — Story 18.3 (§14.16).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies that "staff 2FA in place" actually holds.
 *
 * 2FA itself is a plugin (project-context: don't reimplement plugin capability) —
 * this collaborator does NOT implement TOTP. It audits coverage: which
 * editors/administrators lack a registered second factor. The pure
 * {@see staffMissingTwoFactor()} takes already-resolved `{id, login, has_2fa}`
 * rows so it unit-tests without WordPress; the `wp ink audit-2fa` CLI surfaces the
 * gap (Afrikaans operator output). The "has 2FA" predicate is an overridable seam
 * so it can bind to whichever 2FA plugin is installed.
 *
 * Conflation-clean: zero Tiers/Entitlement.
 *
 * @package Ink\Core
 */
class TwoFactorAudit {

	/**
	 * The WP-CLI command name (`wp ink audit-2fa`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink audit-2fa';

	/**
	 * Register the audit command — WP-CLI only.
	 *
	 * All `WP_CLI::*` I/O lives inside the closure, after the `class_exists` guard.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP-CLI command-callback signature; audit-2fa takes no args.
				unset( $args, $assoc );
				$missing = self::staffMissingTwoFactor( $this->staffUsers() );

				if ( array() === $missing ) {
					\WP_CLI::success( 'Alle redakteurs en administrateurs het 2FA aktief.' );
					return;
				}

				$logins = array_map(
					static fn ( array $u ): string => (string) ( $u['login'] ?? '' ),
					$missing
				);

				\WP_CLI::warning(
					sprintf(
						'%d personeellid(e) sonder 2FA: %s',
						count( $missing ),
						implode( ', ', $logins )
					)
				);
			}
		);
	}

	/**
	 * Staff rows that lack a second factor. Pure.
	 *
	 * @param array<int, array{id?:int, login?:string, has_2fa?:bool}> $users Staff rows.
	 * @return array<int, array{id?:int, login?:string, has_2fa?:bool}>
	 */
	public static function staffMissingTwoFactor( array $users ): array {
		return array_values(
			array_filter(
				$users,
				static fn ( array $u ): bool => true !== ( $u['has_2fa'] ?? false )
			)
		);
	}

	/**
	 * The staff users (editors + administrators) with a resolved 2FA flag.
	 * Overridable seam — binds to the installed 2FA plugin's "is enabled for user" check.
	 *
	 * @return array<int, array{id:int, login:string, has_2fa:bool}>
	 */
	protected function staffUsers(): array {
		$users = get_users(
			array(
				'role__in' => array( 'administrator', 'editor' ),
				'fields'   => array( 'ID', 'user_login' ),
			)
		);

		$rows = array();

		foreach ( $users as $user ) {
			$id     = (int) ( $user->ID ?? 0 );
			$rows[] = array(
				'id'      => $id,
				'login'   => (string) ( $user->user_login ?? '' ),
				'has_2fa' => $this->hasTwoFactor( $id ),
			);
		}

		return $rows;
	}

	/**
	 * Whether a user has a second factor registered. Overridable seam.
	 *
	 * Default: honour a filter so the installed 2FA plugin (or a deployment) can
	 * report per-user 2FA status without ink-core depending on a specific plugin.
	 *
	 * @param int $user_id The user.
	 */
	protected function hasTwoFactor( int $user_id ): bool {
		return (bool) apply_filters( 'ink_security_user_has_2fa', false, $user_id );
	}
}
