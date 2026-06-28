<?php
/**
 * Once-off writer-tier CSV import — Story 16.3 (FL 16.3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Imports the legacy writer-tier spreadsheet into `ink_writer_tier` user meta —
 * a once-off, idempotent DB update (FL 16.3).
 *
 * The CSV join key is EMAIL. Each row's tier is parsed against the canonical
 * {@see Tier} enum (case-/whitespace-insensitive); a missing, empty, or
 * unrecognised value defaults to {@see Tier::default()} (`brons`) AND sets a
 * review flag ({@see FLAG_META}) — the import NEVER guesses a higher grade
 * (Silwer/Goud/Meester). A row whose email matches no WP account is counted and
 * surfaced for manual follow-up, never used to create an account.
 *
 * The grade is written through {@see Api::importBaselineGrade()} — the
 * sanctioned, Tiers-owned baseline SET (NOT a logged promotion: no
 * `PromotionLog` entry, no `promoted_at`, no win-count reset). Routing through
 * the Tiers facade keeps tier writes inside `Ink\Tiers` (THE conflation
 * guardrail), so the Migration layer never pokes the grade meta directly.
 *
 * Once-off + guarded: a completion option ({@see OPTION_DONE}) makes a re-run a
 * no-op (`--force` re-runs), and the trigger is WP-CLI only
 * (`wp ink migrate-tiers <path>`) — NEVER a web request. THE conflation rule:
 * tier is not subscription — this sets the writer grade only, reading no
 * membership and adding no `Entitlement` coupling (deptrac edge `Migration → Tiers`).
 *
 * Not `final`: the file/DB methods are overridable seams so the orchestration is
 * unit-testable without a CSV file or the WordPress user API.
 *
 * @package Ink\Core
 */
class TierImport {

	/**
	 * The completion flag option — set once the import has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_tiers_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-tiers <path>`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-tiers';

	/**
	 * The review-flag user-meta key — set when a row defaulted to `brons` because
	 * its tier was missing/ambiguous (staff resolve these by hand; never guessed).
	 *
	 * Deliberately NOT a `ink_writer_tier*` key (it is a migration-review marker,
	 * not Gradering state) so it stays clear of the tier-write conflation guardrail.
	 *
	 * @var string
	 */
	public const FLAG_META = 'ink_tier_import_review_flag';

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
				$path = isset( $args[0] ) ? (string) $args[0] : '';

				if ( '' === $path ) {
					\WP_CLI::error( 'Verskaf die pad na die gradering-CSV.' );
					return;
				}

				$summary = $this->run( $path, isset( $assoc['force'] ) );
				\WP_CLI::success(
					sprintf(
						'Graderings ingevoer: %d gestel, %d na brons verstek + gemerk, %d sonder rekening%s.',
						(int) $summary['set'],
						(int) $summary['defaulted'],
						(int) $summary['no_account'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * Parse a raw CSV tier value into a {@see Tier}. Pure.
	 *
	 * Case-/whitespace-insensitive exact match against the four canonical grades.
	 * Returns null for an empty or unrecognised value — the caller defaults to
	 * `brons` + a flag, NEVER guessing a higher grade.
	 *
	 * @param string $raw The raw CSV cell.
	 * @return Tier|null
	 */
	public static function parseTier( string $raw ): ?Tier {
		$normalised = strtolower( trim( $raw ) );

		if ( '' === $normalised ) {
			return null;
		}

		return Tier::tryFrom( $normalised );
	}

	/**
	 * Locate the email + tier columns in a CSV header row. Pure.
	 *
	 * Matches on header-name fragments so column order / exact labels do not
	 * matter: email via `mail`/`e-pos`/`epos`, tier via `tier`/`grad` (gradering).
	 *
	 * @param array<int, string> $header The header cells.
	 * @return array{email:int|null, tier:int|null}
	 */
	public static function columnIndexes( array $header ): array {
		$email = null;
		$tier  = null;

		foreach ( $header as $i => $name ) {
			$n = strtolower( trim( (string) $name ) );

			if ( null === $email && ( str_contains( $n, 'mail' ) || str_contains( $n, 'e-pos' ) || str_contains( $n, 'epos' ) ) ) {
				$email = (int) $i;
			}

			if ( null === $tier && ( str_contains( $n, 'tier' ) || str_contains( $n, 'grad' ) ) ) {
				$tier = (int) $i;
			}
		}

		return array(
			'email' => $email,
			'tier'  => $tier,
		);
	}

	/**
	 * Run the once-off import. Idempotent unless `$force`.
	 *
	 * @param string $csv_path The path to the tier CSV.
	 * @param bool   $force    Re-run even when already completed.
	 * @return array{skipped:bool, set:int, defaulted:int, no_account:int}
	 */
	public function run( string $csv_path, bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'    => true,
				'set'        => 0,
				'defaulted'  => 0,
				'no_account' => 0,
			);
		}

		$set        = 0;
		$defaulted  = 0;
		$no_account = 0;

		foreach ( $this->readRows( $csv_path ) as $row ) {
			$email = isset( $row['email'] ) ? trim( (string) $row['email'] ) : '';

			if ( '' === $email ) {
				continue;
			}

			$user_id = $this->userIdForEmail( $email );

			if ( $user_id <= 0 ) {
				++$no_account;
				continue;
			}

			$tier = self::parseTier( isset( $row['tier'] ) ? (string) $row['tier'] : '' );

			if ( $tier instanceof Tier ) {
				$this->setTier( $user_id, $tier );
				++$set;
				continue;
			}

			// Missing/ambiguous → brons + flag. NEVER a guessed higher grade.
			$this->setTier( $user_id, Tier::default() );
			$this->flagForReview( $user_id, 'ontbrekende-of-dubbelsinnige-gradering' );
			++$defaulted;
		}

		$this->markDone();

		return array(
			'skipped'    => false,
			'set'        => $set,
			'defaulted'  => $defaulted,
			'no_account' => $no_account,
		);
	}

	/**
	 * Whether the import has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the import complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * Read the CSV into a list of `['email'=>…, 'tier'=>…]` rows. Overridable seam.
	 *
	 * Resolves the email + tier columns from the header via {@see columnIndexes()}.
	 * Returns an empty list when the file is unreadable or the columns are absent.
	 *
	 * @param string $path The CSV path.
	 * @return list<array{email:string, tier:string}>
	 */
	protected function readRows( string $path ): array {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_get_contents_fopen, WordPress.WP.AlternativeFunctions.file_get_contents_fclose -- once-off CLI CSV reader; WP_Filesystem is for managed uploads, not a one-shot local migration file stream.
		if ( '' === $path || ! is_readable( $path ) ) {
			return array();
		}

		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return array();
		}

		$rows   = array();
		$header = fgetcsv( $handle );

		if ( ! is_array( $header ) ) {
			fclose( $handle );
			return array();
		}

		$cols = self::columnIndexes( array_map( 'strval', $header ) );

		if ( null === $cols['email'] || null === $cols['tier'] ) {
			fclose( $handle );
			return array();
		}

		$line = fgetcsv( $handle );

		while ( false !== $line ) {
			$rows[] = array(
				'email' => isset( $line[ $cols['email'] ] ) ? (string) $line[ $cols['email'] ] : '',
				'tier'  => isset( $line[ $cols['tier'] ] ) ? (string) $line[ $cols['tier'] ] : '',
			);

			$line = fgetcsv( $handle );
		}

		fclose( $handle );

		return $rows;
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_get_contents_fopen, WordPress.WP.AlternativeFunctions.file_get_contents_fclose
	}

	/**
	 * The WP user id for an email, or 0 when no account matches. Overridable seam.
	 *
	 * @param string $email The join-key email.
	 * @return int
	 */
	protected function userIdForEmail( string $email ): int {
		$user = get_user_by( 'email', $email );

		return ( false !== $user ) ? (int) $user->ID : 0;
	}

	/**
	 * Write the writer grade through the Tiers facade (the sanctioned baseline-set
	 * path — tier writes stay inside `Ink\Tiers`). Overridable seam.
	 *
	 * @param int  $user_id The user id.
	 * @param Tier $tier    The grade to set.
	 */
	protected function setTier( int $user_id, Tier $tier ): void {
		Api::importBaselineGrade( $user_id, $tier );
	}

	/**
	 * Flag a defaulted row for staff review. Overridable seam.
	 *
	 * @param int    $user_id The user id.
	 * @param string $reason  The flag reason.
	 */
	protected function flagForReview( int $user_id, string $reason ): void {
		update_user_meta( $user_id, self::FLAG_META, $reason );
	}
}
