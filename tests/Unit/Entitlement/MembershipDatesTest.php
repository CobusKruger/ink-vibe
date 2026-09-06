<?php
/**
 * Unit tests for the lidmaatskap status-card date read-model (My Profiel
 * Lidmaatskap tab, `docs/my-profiel-rebuild-strategy.md` §5.9 / §7 item 6).
 *
 * Target: {@see \Ink\Entitlement\MembershipDates} (facaded by
 * {@see \Ink\Entitlement\Api::memberSinceFor()} / {@see \Ink\Entitlement\Api::renewalDateFor()})
 * — the "Lid sedert" / "Hernieu" date getters for the status card. Only a
 * membership that is BOTH an INK lidmaatskap (its plan grants a Story-4.1
 * product) AND literally WooCommerce-status `active` yields dates; every other
 * case (WooCommerce Memberships absent, no membership, a non-INK membership, a
 * non-active INK membership, or an unparseable date) degrades to null — never a
 * fatal.
 *
 * Brain Monkey, no WordPress/WooCommerce/DB loaded — the WC Memberships API
 * (`wc_memberships_get_user_memberships`) and the membership objects are
 * mocked, and the availability seam is forced via a test subclass (the
 * 4.1/4.2/4.3 precedent — Brain Monkey-defined function symbols persist within
 * a process, so an inline `function_exists` mock cannot simulate "absent").
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\MembershipDates;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A mock WooCommerce Memberships user-membership object.
 *
 * Mirrors the small surface this read-model needs: `get_status()` (must be
 * literally `active` to surface dates), `get_start_date( 'timestamp' )` /
 * `get_end_date( 'timestamp' )` (the two date getters), and `get_plan()` →
 * product ids (to identify an INK lidmaatskap membership). `$rawStart`/
 * `$rawEnd` (when not the `false` sentinel) override the returned value
 * verbatim — used to model an absent/unparseable date.
 */
function ink_dates_membership(
	?int $startTimestamp,
	?int $endTimestamp,
	string $status,
	array $productIds,
	mixed $rawStart = false,
	mixed $rawEnd = false
): object {
	return new class( $startTimestamp, $endTimestamp, $status, $productIds, $rawStart, $rawEnd ) {
		public function __construct(
			private ?int $startTimestamp,
			private ?int $endTimestamp,
			private string $status,
			private array $productIds,
			private mixed $rawStart,
			private mixed $rawEnd
		) {}

		public function get_start_date( string $format = 'mysql' ): mixed {
			if ( false !== $this->rawStart ) {
				return $this->rawStart;
			}
			if ( null === $this->startTimestamp ) {
				return '';
			}
			return 'timestamp' === $format ? $this->startTimestamp : gmdate( 'Y-m-d H:i:s', $this->startTimestamp );
		}

		public function get_end_date( string $format = 'mysql' ): mixed {
			if ( false !== $this->rawEnd ) {
				return $this->rawEnd;
			}
			if ( null === $this->endTimestamp ) {
				return '';
			}
			return 'timestamp' === $format ? $this->endTimestamp : gmdate( 'Y-m-d H:i:s', $this->endTimestamp );
		}

		public function get_status(): string {
			return $this->status;
		}

		public function get_plan(): object {
			return new class( $this->productIds ) {
				/** @param list<int> $productIds */
				public function __construct( private array $productIds ) {}
				/** @return list<int> */
				public function get_product_ids(): array {
					return $this->productIds;
				}
			};
		}
	};
}

/**
 * A MembershipDates test double: WooCommerce Memberships forced AVAILABLE, the
 * memberships supplied directly, the INK product ids pinned — so the read
 * model is exercised without WordPress/WooCommerce. Mirrors the
 * {@see \Ink\Tests\Unit\Entitlement\ink_gate()} precedent.
 */
function ink_dates_model( array $memberships, array $inkProductIds = array( 101 ) ): MembershipDates {
	return new class( $memberships, $inkProductIds ) extends MembershipDates {
		public function __construct(
			private array $memberships,
			private array $inkProductIds
		) {}

		protected function isMembershipsAvailable(): bool {
			return true;
		}

		protected function userMemberships( int $user_id ): array {
			return $this->memberships;
		}

		protected function inkProductIds(): array {
			return $this->inkProductIds;
		}
	};
}

/**
 * WooCommerce Memberships inactive → both getters degrade to null, never a
 * fatal. The default (non-overridden) `isMembershipsAvailable()` seam is
 * exercised here (Brain Monkey never defines `wc_memberships_get_user_memberships`).
 */
test( 'both date getters return null when WooCommerce Memberships is unavailable', function (): void {
	$dates = new MembershipDates();

	expect( $dates->memberSinceFor( 42 ) )->toBeNull();
	expect( $dates->renewalDateFor( 42 ) )->toBeNull();
} );

/**
 * An invalid user id (0 / negative) degrades to null without ever reading a
 * membership — a defensive fail-safe mirroring the 4.3 gate's `resolveUserId()`.
 */
test( 'both date getters return null for a non-positive user id', function (): void {
	$dates = ink_dates_model( array( ink_dates_membership( 1000, 2000, 'active', array( 101 ) ) ) );

	expect( $dates->memberSinceFor( 0 ) )->toBeNull();
	expect( $dates->renewalDateFor( -5 ) )->toBeNull();
} );

/**
 * The core happy path: an active INK membership yields both dates, formatted
 * via `date_i18n()` against the site's `date_format` option — never a
 * hardcoded format string.
 */
test( 'an active INK membership yields both dates formatted via date_i18n + date_format', function (): void {
	$start = strtotime( '2024-01-15 00:00:00 UTC' );
	$end   = strtotime( '2025-01-15 00:00:00 UTC' );

	$dates = ink_dates_model( array( ink_dates_membership( $start, $end, 'active', array( 101 ) ) ) );

	Functions\when( 'get_option' )->justReturn( 'j F Y' );

	Functions\expect( 'date_i18n' )
		->once()
		->with( 'j F Y', $start )
		->andReturn( '15 Januarie 2024' );

	expect( $dates->memberSinceFor( 7 ) )->toBe( '15 Januarie 2024' );

	Functions\expect( 'date_i18n' )
		->once()
		->with( 'j F Y', $end )
		->andReturn( '15 Januarie 2025' );

	expect( $dates->renewalDateFor( 7 ) )->toBe( '15 Januarie 2025' );
} );

/**
 * A non-INK WooCommerce membership (no shared product) grants no dates — the
 * conflation-free "is this an INK lidmaatskap" check mirrors
 * {@see \Ink\Entitlement\SubmissionGate::isInkMembership()}.
 */
test( 'a non-INK membership yields null even though it is active', function (): void {
	$dates = ink_dates_model(
		array( ink_dates_membership( 1000, 2000, 'active', array( 999 ) ) ), // 999 not an INK product.
		array( 101 )
	);

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} );

/**
 * A NON-active INK membership (e.g. `expired`, `cancelled`, `paused`,
 * `pending`) yields null — this status card only surfaces dates for a
 * genuinely active membership (distinct from the 4.3 gate's broader
 * cron-lag-tolerant time-authority window; this is a display surface, not an
 * entitlement gate).
 */
test( 'a non-active INK membership status yields null for both dates', function ( string $status ): void {
	$dates = ink_dates_model( array( ink_dates_membership( 1000, 2000, $status, array( 101 ) ) ) );

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} )->with( array( 'expired', 'cancelled', 'paused', 'pending', 'pending_cancellation' ) );

/**
 * No membership at all for the user → null, gracefully (no crash iterating an
 * empty list).
 */
test( 'no memberships at all yields null for both dates', function (): void {
	$dates = ink_dates_model( array() );

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} );

/**
 * No INK lidmaatskap product configured at all (an empty product map) → null,
 * without ever calling the WC Memberships reader (mirrors the 4.3 gate's
 * early-exit).
 */
test( 'an empty configured INK product set yields null for both dates', function (): void {
	$dates = ink_dates_model(
		array( ink_dates_membership( 1000, 2000, 'active', array( 101 ) ) ),
		array() // No INK product configured.
	);

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} );

/**
 * A malformed membership object missing `get_status()` is skipped
 * conservatively (never guessed as "active") — the 4.3 gate's fail-safe
 * discipline for a malformed object.
 */
test( 'a malformed membership object missing get_status() is skipped, not guessed active', function (): void {
	$malformed = new class() {
		public function get_plan(): object {
			return new class() {
				public function get_product_ids(): array {
					return array( 101 );
				}
			};
		}
		// Deliberately no get_status().
	};

	$dates = ink_dates_model( array( $malformed ) );

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} );

/**
 * A present-but-unparseable (non-numeric) date value degrades to null rather
 * than inventing a date — mirrors the 4.3/4.8 "anomaly → fail-safe" discipline
 * for a garbage end/start date.
 */
test( 'a present-but-non-numeric date value degrades to null, not a fatal', function (): void {
	$dates = ink_dates_model(
		array( ink_dates_membership( null, null, 'active', array( 101 ), 'not-a-timestamp', 'also-not-one' ) )
	);

	Functions\when( 'get_option' )->justReturn( 'j F Y' );

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} );

/**
 * A genuinely absent date (`''` — WooCommerce's "unlimited"/"not set" signal)
 * degrades to null rather than a fatal or an invented date.
 */
test( 'a genuinely absent (empty) date value degrades to null', function (): void {
	$dates = ink_dates_model(
		array( ink_dates_membership( null, null, 'active', array( 101 ) ) )
	);

	expect( $dates->memberSinceFor( 7 ) )->toBeNull();
	expect( $dates->renewalDateFor( 7 ) )->toBeNull();
} );

/**
 * `Api::memberSinceFor()` / `Api::renewalDateFor()` delegate to
 * {@see MembershipDates} — the AD-1 facade rule (Api is the sole public
 * cross-module surface). Verified end-to-end through the real facade with
 * WooCommerce Memberships unavailable (the deterministic branch reachable
 * without a test subclass), proving the facade wires through rather than
 * reimplementing.
 */
test( 'Api facades memberSinceFor and renewalDateFor through MembershipDates', function (): void {
	expect( \Ink\Entitlement\Api::memberSinceFor( 42 ) )->toBeNull();
	expect( \Ink\Entitlement\Api::renewalDateFor( 42 ) )->toBeNull();
} );
