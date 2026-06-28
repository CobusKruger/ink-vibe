<?php
/**
 * Read-only subscription verification — Story 16.4 (FL 16.4, MR-5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the cloned WooCommerce Memberships data on the new host — a READ-ONLY
 * pre-cutover report (FL 16.4). There is NO import: subscriptions ride the DB
 * clone, and this command only confirms their integrity (MR-5).
 *
 * The report covers total memberships, counts per status, the distinct plan IDs
 * in use (with counts), expiry coverage (active time-limited vs unlimited), and
 * a FLAGGED list of records a human must resolve before cutover — a membership
 * with no plan ID, or an unrecognised status.
 *
 * Records are read through the documented WooCommerce Memberships API
 * ({@see wc_memberships_get_user_membership()}), behind a `function_exists`
 * guard (the `Entitlement\SubmissionGate` house pattern) — never by assuming
 * WC's internal table structure. With WC Memberships absent the command reports
 * "not available" and exits cleanly.
 *
 * Unlike the other Epic-16 commands this carries NO idempotency flag: a read-only
 * report is naturally re-runnable. Conflation-clean: reads membership state only
 * (zero `Ink\Tiers` coupling); the WC read is an external-dependency call, so no
 * INK cross-module edge.
 *
 * Not `final`: the WC-reading methods are overridable seams so the report logic
 * is unit-testable without WooCommerce.
 *
 * @package Ink\Core
 */
class SubscriptionVerifier {

	/**
	 * The WP-CLI command name (`wp ink verify-subscriptions`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink verify-subscriptions';

	/**
	 * The documented WC Memberships single-membership reader (guarded with
	 * `function_exists` before use).
	 *
	 * @var string
	 */
	public const WC_MEMBERSHIPS_FN = 'wc_memberships_get_user_membership';

	/**
	 * Register the read-only WP-CLI trigger — ONLY under WP-CLI (never a web request).
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
			function (): void {
				$report = $this->verify();

				if ( empty( $report['available'] ) ) {
					\WP_CLI::warning( 'WooCommerce Memberships is nie aktief nie — geen verifikasie moontlik.' );
					return;
				}

				foreach ( $report['by_status'] as $status => $count ) {
					\WP_CLI::log( sprintf( '  • %s: %d', (string) $status, (int) $count ) );
				}

				\WP_CLI::success(
					sprintf(
						'Lidmaatskappe geverifieer: %d totaal, %d met vervaldatum, %d onbeperk, %d gemerk vir aandag.',
						(int) $report['total'],
						(int) $report['active_with_expiry'],
						(int) $report['active_unlimited'],
						count( $report['flagged'] )
					)
				);
			}
		);
	}

	/**
	 * The recognised WooCommerce Memberships statuses. Pure.
	 *
	 * @return list<string>
	 */
	public static function knownStatuses(): array {
		return array( 'active', 'paused', 'expired', 'cancelled', 'pending', 'free_trial', 'complimentary' );
	}

	/**
	 * Shape a list of normalised membership records into the verification report. Pure.
	 *
	 * @param list<array{user_id?:int, plan_id?:int, status?:string, end_date?:string}> $records The normalised memberships.
	 * @return array{total:int, by_status:array<string,int>, plans:array<int,int>, active_with_expiry:int, active_unlimited:int, flagged:list<array{user_id:int, plan_id:int, status:string, reasons:list<string>}>}
	 */
	public static function summarise( array $records ): array {
		$by_status          = array();
		$plans              = array();
		$active_with_expiry = 0;
		$active_unlimited   = 0;
		$flagged            = array();

		foreach ( $records as $record ) {
			$user_id  = (int) ( $record['user_id'] ?? 0 );
			$plan_id  = (int) ( $record['plan_id'] ?? 0 );
			$status   = strtolower( trim( (string) ( $record['status'] ?? '' ) ) );
			$end_date = trim( (string) ( $record['end_date'] ?? '' ) );

			$by_status[ $status ] = ( $by_status[ $status ] ?? 0 ) + 1;

			if ( $plan_id > 0 ) {
				$plans[ $plan_id ] = ( $plans[ $plan_id ] ?? 0 ) + 1;
			}

			if ( 'active' === $status ) {
				if ( '' !== $end_date ) {
					++$active_with_expiry;
				} else {
					++$active_unlimited;
				}
			}

			$reasons = array();

			if ( $plan_id <= 0 ) {
				$reasons[] = 'geen-plan';
			}

			if ( ! in_array( $status, self::knownStatuses(), true ) ) {
				$reasons[] = 'onbekende-status';
			}

			if ( array() !== $reasons ) {
				$flagged[] = array(
					'user_id' => $user_id,
					'plan_id' => $plan_id,
					'status'  => $status,
					'reasons' => $reasons,
				);
			}
		}

		ksort( $by_status );
		ksort( $plans );

		return array(
			'total'              => count( $records ),
			'by_status'          => $by_status,
			'plans'              => $plans,
			'active_with_expiry' => $active_with_expiry,
			'active_unlimited'   => $active_unlimited,
			'flagged'            => $flagged,
		);
	}

	/**
	 * Build the verification report. Read-only.
	 *
	 * @return array{available:bool, total:int, by_status:array<string,int>, plans:array<int,int>, active_with_expiry:int, active_unlimited:int, flagged:list<array{user_id:int, plan_id:int, status:string, reasons:list<string>}>}
	 */
	public function verify(): array {
		if ( ! $this->wooMembershipsAvailable() ) {
			return array(
				'available'          => false,
				'total'              => 0,
				'by_status'          => array(),
				'plans'              => array(),
				'active_with_expiry' => 0,
				'active_unlimited'   => 0,
				'flagged'            => array(),
			);
		}

		return array( 'available' => true ) + self::summarise( $this->membershipRecords() );
	}

	/**
	 * Whether the WooCommerce Memberships read API is available. Overridable seam.
	 */
	protected function wooMembershipsAvailable(): bool {
		return function_exists( self::WC_MEMBERSHIPS_FN );
	}

	/**
	 * Read every user membership into normalised records. Overridable seam.
	 *
	 * Uses the documented `wc_memberships_get_user_membership()` getters — never WC
	 * internals. Returns `{user_id, plan_id, status, end_date}` per membership.
	 *
	 * @return list<array{user_id:int, plan_id:int, status:string, end_date:string}>
	 */
	protected function membershipRecords(): array {
		// WC Memberships is a premium plugin with no PHPStan stub, so guard the
		// direct reader call here too (in addition to the verify() availability
		// gate) — keeps static analysis honest and is a second fail-safe (the
		// Entitlement\SubmissionGate house pattern).
		if ( ! function_exists( self::WC_MEMBERSHIPS_FN ) ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'        => 'wc_user_membership',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$records = array();

		foreach ( $ids as $id ) {
			$membership = wc_memberships_get_user_membership( (int) $id );

			if ( ! is_object( $membership ) ) {
				continue;
			}

			$records[] = array(
				'user_id'  => (int) $membership->get_user_id(),
				'plan_id'  => (int) $membership->get_plan_id(),
				'status'   => (string) $membership->get_status(),
				'end_date' => (string) $membership->get_end_date( 'Y-m-d' ),
			);
		}

		return $records;
	}
}
