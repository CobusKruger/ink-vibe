<?php
/**
 * Unit tests for the submission-entitlement gate (Story 4.3, FR-6/FR-13/FR-19, AD-2).
 *
 * Target: {@see \Ink\Entitlement\SubmissionGate} (facaded by
 * {@see \Ink\Entitlement\Api::can_submit()}) — "may this user plaas right now?".
 *
 * AD-2 is the spec: the gate evaluates the WooCommerce Membership END DATE compared
 * to "now" in SAST (valid THROUGH 23:59:59 SAST on the expiry day), NOT WooCommerce's
 * cron-flipped `expired`/`active` status string (which lags). Auto-revoke is EMERGENT
 * — a lapsed end date simply returns false; there is no revoke routine and the denial
 * path performs NO write (no account delete, no `ink_writer_tier` write, no post
 * status change). THE conflation rule (AD-1): the gate reads ONLY membership state —
 * zero `Ink\Tiers` / `ink_writer_tier`.
 *
 * Brain Monkey, no WordPress/WooCommerce/DB loaded — the WC Memberships API
 * (`wc_memberships_get_user_memberships`, read ACROSS statuses so the gate — not a
 * status pre-filter — is the authority) and the membership objects are mocked, and the
 * availability seam is forced via a test subclass (the 4.1/4.2 precedent — Brain
 * Monkey-defined function symbols persist within a process, so an inline
 * `function_exists` mock cannot simulate "absent"). "Now" is pinned via the gate's
 * injectable clock so the SAST boundary maths is deterministic.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\SubmissionGate;
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
 * SAST timezone helper for building deterministic dates in the tests.
 */
function ink_sast(): \DateTimeZone {
	return new \DateTimeZone( 'Africa/Johannesburg' );
}

/**
 * A mock WooCommerce Memberships user-membership object.
 *
 * Mirrors the small surface the gate reads: `get_status()` (the ADMINISTRATIVE-
 * revocation governor — `cancelled`/`paused`/`pending`/`pending_cancellation` always
 * deny; `active`/`complimentary`/`free`/`free_trial`/`expired` proceed to the date
 * check), `get_end_date( 'timestamp' )` (the TIME authority, AD-2), and `get_plan()` →
 * product ids (to identify an INK lidmaatskap membership). An `?int $endTimestamp` of
 * null models an open-ended / lifetime membership. `$rawEndDate` (when not the `false`
 * sentinel) overrides the returned end-date value verbatim — used to model a
 * present-but-non-numeric / unparseable end date (anomaly → fail-safe deny).
 */
function ink_membership( ?int $endTimestamp, string $status, array $productIds, mixed $rawEndDate = false ): object {
	return new class( $endTimestamp, $status, $productIds, $rawEndDate ) {
		/**
		 * @param int|null  $endTimestamp End-date UNIX timestamp, or null (open-ended).
		 * @param string    $status       The WC-stored status (the admin-revocation governor).
		 * @param list<int> $productIds   The plan's product ids.
		 * @param mixed     $rawEndDate   Verbatim end-date override, or the `false` sentinel.
		 */
		public function __construct(
			private ?int $endTimestamp,
			private string $status,
			private array $productIds,
			private mixed $rawEndDate
		) {}

		public function get_end_date( string $format = 'mysql' ): mixed {
			if ( false !== $this->rawEndDate ) {
				return $this->rawEndDate; // Verbatim (e.g. present-but-non-numeric anomaly).
			}
			if ( null === $this->endTimestamp ) {
				return ''; // WC returns '' for an open-ended membership.
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
 * A SubmissionGate test double: WooCommerce Memberships forced AVAILABLE, the active
 * memberships supplied directly, "now" and the INK product ids pinned — so the gate
 * logic is exercised without WordPress/WooCommerce. Mirrors the 4.1/4.2
 * `isWooCommerceAvailable()`-override testability seam.
 */
function ink_gate( array $memberships, \DateTimeImmutable $now, array $inkProductIds = array( 101 ) ): SubmissionGate {
	return new class( $memberships, $now, $inkProductIds ) extends SubmissionGate {
		/**
		 * @param list<object>      $memberships   Mock active memberships.
		 * @param \DateTimeImmutable $now           Pinned "now".
		 * @param list<int>         $inkProductIds The configured INK lidmaatskap product ids.
		 */
		public function __construct(
			private array $memberships,
			private \DateTimeImmutable $now,
			private array $inkProductIds
		) {}

		protected function isMembershipsAvailable(): bool {
			return true;
		}

		protected function userMemberships( int $user_id ): array {
			return $this->memberships;
		}

		protected function now(): \DateTimeImmutable {
			return $this->now;
		}

		protected function inkProductIds(): array {
			return $this->inkProductIds;
		}
	};
}

/**
 * AC-1: an active INK membership whose end date is in the FUTURE → may plaas (true).
 */
test( 'canSubmit is true for a membership with a future end date', function (): void {
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	$gate = ink_gate( array( ink_membership( $future, 'active', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeTrue();
} );

/**
 * AC-1/AC-3 (the AD-2 lag case): the end date is TODAY (SAST) but WooCommerce has NOT
 * yet flipped the status — it still says `active` (or even already `expired` from a
 * lagging cron). The gate evaluates the END DATE through end-of-day SAST, so the lid
 * is STILL entitled today regardless of the status string.
 */
test( 'canSubmit is true on the expiry day through end-of-day SAST regardless of WC status', function (): void {
	// "Now" is mid-afternoon SAST on the expiry day; end date is that same SAST day.
	$now   = new \DateTimeImmutable( '2026-06-22 15:00:00', ink_sast() );
	$today = ( new \DateTimeImmutable( '2026-06-22 00:00:00', ink_sast() ) )->getTimestamp();

	// Status still 'active' (cron not yet run): true.
	$gateActive = ink_gate( array( ink_membership( $today, 'active', array( 101 ) ) ), $now );
	expect( $gateActive->canSubmit( 7 ) )->toBeTrue();

	// Status ALREADY 'expired' (lagging cron flipped it early / wrongly): still true,
	// because the end-date window — not the status flag — is the authority (AD-2).
	$gateExpired = ink_gate( array( ink_membership( $today, 'expired', array( 101 ) ) ), $now );
	expect( $gateExpired->canSubmit( 7 ) )->toBeTrue();
} );

/**
 * AC-3 (the AD-2 core): the end date PASSED (yesterday) → denied (false), even though
 * WooCommerce's stored status still says `active` (the flag lags the true expiry). The
 * gate trusts the end date, never the status.
 */
test( 'canSubmit is false once the end date has passed even if WC status still says active', function (): void {
	$now       = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$yesterday = ( new \DateTimeImmutable( '2026-06-21 00:00:00', ink_sast() ) )->getTimestamp();

	$gate = ink_gate( array( ink_membership( $yesterday, 'active', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-2: the end-of-day SAST boundary — just before 23:59:59 SAST on the expiry day is
 * entitled (true); just after midnight SAST the next day is denied (false).
 */
test( 'canSubmit honours the end-of-day SAST boundary', function (): void {
	$expiry = ( new \DateTimeImmutable( '2026-06-22 00:00:00', ink_sast() ) )->getTimestamp();

	$justBefore = new \DateTimeImmutable( '2026-06-22 23:59:58', ink_sast() );
	$justAfter  = new \DateTimeImmutable( '2026-06-23 00:00:01', ink_sast() );

	$gateBefore = ink_gate( array( ink_membership( $expiry, 'active', array( 101 ) ) ), $justBefore );
	$gateAfter  = ink_gate( array( ink_membership( $expiry, 'active', array( 101 ) ) ), $justAfter );

	expect( $gateBefore->canSubmit( 7 ) )->toBeTrue();
	expect( $gateAfter->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-1: an open-ended (lifetime / no end date) INK membership → entitled (true). A
 * missing end date is not an expiry — it is "never expires". (Documented as legitimate
 * WC "unlimited membership" semantics, NOT a launch product — see SubmissionGate docs.)
 */
test( 'canSubmit is true for an open-ended membership with no end date', function (): void {
	$now = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );

	$gate = ink_gate( array( ink_membership( null, 'active', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeTrue();
} );

/**
 * AC-3 (Edge Hunter [HIGH] — suspension revocation): a `paused` (suspended) INK
 * membership with a FUTURE end date → denied (false). STATUS governs administrative
 * revocation: `paused` is an always-deny status REGARDLESS of how far in the future the
 * date is. This is now genuinely reachable because the gate reads memberships ACROSS
 * statuses (not active-only) and applies the revocation taxonomy itself.
 */
test( 'canSubmit is false for a paused (suspended) membership even with a future end date', function (): void {
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	$gate = ink_gate( array( ink_membership( $future, 'paused', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-3 (cancellation revocation): a `cancelled` INK membership with a FUTURE end date →
 * denied (false). `cancelled` is an always-deny administrative-revocation status.
 */
test( 'canSubmit is false for a cancelled membership even with a future end date', function (): void {
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	$gate = ink_gate( array( ink_membership( $future, 'cancelled', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-1/AC-3 (AD-2 cron-lag, the headline guarantee — now genuinely produced by the
 * production code path, not a mock bypass): a membership WooCommerce has ALREADY flipped
 * to `expired` (a lagging cron ran), whose end date is STILL within end-of-day SAST, is
 * GRANTED. `expired` is a TIME state, not an administrative revocation — it passes the
 * status check and is then evaluated by the DATE, which is still valid → true.
 */
test( 'canSubmit is true for an expired-status membership whose end date is still within EOD SAST', function (): void {
	$now   = new \DateTimeImmutable( '2026-06-22 15:00:00', ink_sast() );
	$today = ( new \DateTimeImmutable( '2026-06-22 00:00:00', ink_sast() ) )->getTimestamp();

	$gate = ink_gate( array( ink_membership( $today, 'expired', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeTrue();
} );

/**
 * AC-3 (the date is still the time-authority for an `expired` membership): a membership
 * WC has flipped to `expired` whose end date has PASSED → denied. `expired` passes the
 * administrative check, then fails the date check.
 */
test( 'canSubmit is false for an expired-status membership whose end date has passed', function (): void {
	$now       = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$yesterday = ( new \DateTimeImmutable( '2026-06-21 00:00:00', ink_sast() ) )->getTimestamp();

	$gate = ink_gate( array( ink_membership( $yesterday, 'expired', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * Edge Hunter [MED] (fail-safe deny): a PRESENT-but-non-numeric / unparseable end date
 * is an ANOMALY, not an "unlimited" signal — it must DENY (fail-safe), never fall into
 * the open-ended grant branch. Distinguished from a genuinely ABSENT end date (null / ''
 * / 0 — WC's unlimited signal, which grants).
 */
test( 'canSubmit is false for a present-but-non-numeric (unparseable) end date', function (): void {
	$now = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );

	// A present garbage end-date value (not null/''/0) → anomaly → fail-safe deny.
	$gate = ink_gate( array( ink_membership( null, 'active', array( 101 ), 'not-a-date' ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-4 (fail-safe deny): a null user and a logged-out / id-0 user → false, never a
 * fatal. The user is never even looked up.
 */
test( 'canSubmit is false for a null or logged-out user', function (): void {
	$now  = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$gate = ink_gate( array(), $now );

	expect( $gate->canSubmit( null ) )->toBeFalse();
	expect( $gate->canSubmit( 0 ) )->toBeFalse();
} );

/**
 * AC-4 (fail-safe deny): a member with NO membership at all → false.
 */
test( 'canSubmit is false when the user has no membership', function (): void {
	$now  = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$gate = ink_gate( array(), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-4 (fail-safe deny): WooCommerce Memberships inactive/absent (the availability
 * seam is false) → false, never a fatal on a missing API function.
 */
test( 'canSubmit is false when WooCommerce Memberships is unavailable', function (): void {
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	// Availability forced OFF; even a valid future membership is denied (fail-safe).
	$gate = new class( $future, $now ) extends SubmissionGate {
		public function __construct( private int $future, private \DateTimeImmutable $nowValue ) {}
		protected function isMembershipsAvailable(): bool {
			return false; // WooCommerce Memberships inactive.
		}
		protected function now(): \DateTimeImmutable {
			return $this->nowValue;
		}
	};

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-4 (precision): a member whose ACTIVE membership is NOT an INK lidmaatskap (its
 * plan's products do not intersect the configured INK product ids) → false. A
 * non-INK WooCommerce membership must not grant INK submission entitlement.
 */
test( 'canSubmit is false for an active membership that is not an INK lidmaatskap', function (): void {
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	// Membership product 555 is not in the configured INK product ids (101).
	$gate = ink_gate( array( ink_membership( $future, 'active', array( 555 ) ) ), $now, array( 101 ) );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-3 (auto-revoke is EMERGENT — NO write): the denial path (lapsed membership)
 * performs ZERO state change — no account deletion, no `ink_writer_tier` write, no
 * post status change. We assert these WordPress mutators are NEVER called.
 */
test( 'a denied (expired) evaluation performs no destructive write', function (): void {
	$now       = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$yesterday = ( new \DateTimeImmutable( '2026-06-21 00:00:00', ink_sast() ) )->getTimestamp();

	// These must never fire on the denial path (no auto-revoke routine exists).
	Functions\expect( 'wp_delete_user' )->never();
	Functions\expect( 'wp_update_post' )->never();
	Functions\expect( 'wp_trash_post' )->never();
	Functions\expect( 'update_user_meta' )->never();

	$gate = ink_gate( array( ink_membership( $yesterday, 'active', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeFalse();
} );

/**
 * AC-3/AC-5 (conflation — no tier read): even a GRANTED evaluation reads no
 * `ink_writer_tier` meta. The gate is computed purely from membership state; a tier
 * read would couple entitlement to Gradering. `get_user_meta` is never called.
 */
test( 'canSubmit reads no ink_writer_tier meta on a granted evaluation', function (): void {
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	Functions\expect( 'get_user_meta' )->never();

	$gate = ink_gate( array( ink_membership( $future, 'active', array( 101 ) ) ), $now );

	expect( $gate->canSubmit( 7 ) )->toBeTrue();
} );

/**
 * Strip PHP comments + docblocks from a source file, leaving only executable CODE —
 * so the conflation scan asserts against logic, not the doc prose that legitimately
 * names `Ink\Tiers` to STATE the rule (the 4.1/3.6 precedent).
 *
 * @param string $file Absolute path to a PHP source file.
 * @return string The concatenated code tokens (no comments).
 */
function ink_gate_code_only( string $file ): string {
	$code = '';

	foreach ( token_get_all( (string) file_get_contents( $file ) ) as $token ) {
		if ( is_array( $token ) ) {
			if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$code .= $token[1];
			continue;
		}

		$code .= $token;
	}

	return $code;
}

/**
 * AC-5 (THE conflation rule): the Entitlement gate CODE — and the whole Entitlement
 * module + the new Kernel SAST helper — carry ZERO reference to `Ink\Tiers`.
 * Comment-stripped scan so the conflation-rule DOC prose (which must name `Ink\Tiers`
 * to state the rule) is not a false positive.
 */
test( 'the entitlement gate and SAST helper code have no Ink\\Tiers coupling', function (): void {
	$root  = dirname( __DIR__, 3 );
	$files = array_merge(
		(array) glob( $root . '/wp-content/plugins/ink-core/src/Entitlement/*.php' ),
		array( $root . '/wp-content/plugins/ink-core/src/Kernel/Sast.php' )
	);

	foreach ( $files as $file ) {
		expect( is_file( $file ) )->toBeTrue();
		$code = ink_gate_code_only( $file );
		expect( $code )->not->toContain( 'use Ink\Tiers' );
		expect( $code )->not->toContain( 'Ink\Tiers\\' );
		expect( $code )->not->toContain( 'ink_writer_tier' );
	}
} );

/**
 * AC-6: the facade exposes `can_submit()` and delegates to the gate — a future
 * membership via the facade returns true. (Wires the lazy-singleton path through Api.)
 */
test( 'Api::can_submit delegates to the gate', function (): void {
	// The Api facade builds a real SubmissionGate; force its WC-availability + active
	// memberships via the global function mocks so the facade path is exercised.
	$now    = new \DateTimeImmutable( '2026-06-22 12:00:00', ink_sast() );
	$future = ( new \DateTimeImmutable( '2026-09-01 00:00:00', ink_sast() ) )->getTimestamp();

	// The real gate (built by Api) reads these globals: WC availability + memberships
	// (across statuses) + the INK product map + "now". Mock them all.
	Functions\when( 'wc_memberships_get_user_memberships' )->justReturn(
		array( ink_membership( $future, 'active', array( 101 ) ) )
	);
	Functions\when( 'get_option' )->justReturn( array( 1 => 101 ) );
	Functions\when( 'current_datetime' )->justReturn( $now );

	expect( \Ink\Entitlement\Api::can_submit( 7 ) )->toBeTrue();
} );
