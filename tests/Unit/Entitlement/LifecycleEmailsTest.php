<?php
/**
 * Unit tests for the lidmaatskap lifecycle expiry-warning emails (Story 4.8, FR-9a / R5).
 *
 * Target: {@see \Ink\Entitlement\LifecycleEmails} — the expiry-warning half of R5: a
 * 1-week-prior warning on EVERY term + a 1-month-prior warning on LONGER terms (6/12),
 * scheduled off the shared SAST expiry anchor ({@see \Ink\Kernel\Sast}) via Action
 * Scheduler, re-checked LIVE on send, each type toggleable PER TERM (the {type}×{term}
 * matrix on the 1.12 Notifications store, fail-safe OFF).
 *
 * Brain Monkey, no WordPress/DB, no WooCommerce / WC-Memberships / Action Scheduler
 * loaded and NO network — every platform function (`as_*`, `wc_memberships_*`,
 * `wp_mail`, `get_userdata`, `get_option`, `current_datetime`) is mocked. The real
 * `Ink\Entitlement\*` + `Ink\Notifications\*` + `Ink\Kernel\Sast` are autoloaded so the
 * asserted reuse / toggle / anchor behaviour is genuine.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\LidmaatskapTerm;
use Ink\Entitlement\LifecycleEmails;
use Ink\Entitlement\PurchaseActivation;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Notifier;
use Ink\Notifications\TemplateStore;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// `__()` is identity in unit context (no .mo) so Afrikaans source renders.
	Functions\when( '__' )->returnArg( 1 );

	// Reset the Notifications facade so each test wires a known store/notifier.
	$facade = new \ReflectionClass( Notifications::class );
	foreach ( array( 'store', 'notifier' ) as $prop ) {
		$facade->getProperty( $prop )->setValue( null, null );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Wire a fresh known store + notifier into the Notifications facade and return the store.
 */
function ink_le_wire_notifications(): TemplateStore {
	$store    = new TemplateStore();
	$notifier = new Notifier( $store );
	Notifications::bootstrap( $store, $notifier );

	return $store;
}

/**
 * A WC Memberships user-membership double exposing the methods LifecycleEmails reads:
 * id, owner, status, end-date timestamp, and a plan with the given product ids.
 *
 * @param list<int> $product_ids The plan's product ids (the 4.1 mapping inverse).
 */
function ink_le_membership(
	int $id,
	int $user_id,
	string $status,
	int|string|null $end_ts,
	array $product_ids
): object {
	$plan = new class( $product_ids ) {
		/** @param list<int> $ids */
		public function __construct( private array $ids ) {}
		/** @return list<int> */
		public function get_product_ids(): array {
			return $this->ids;
		}
	};

	return new class( $id, $user_id, $status, $end_ts, $plan ) {
		public function __construct(
			private int $id,
			private int $user_id,
			private string $status,
			private int|string|null $end_ts,
			private object $plan
		) {}
		public function get_id(): int {
			return $this->id;
		}
		public function get_user_id(): int {
			return $this->user_id;
		}
		public function get_status(): string {
			return $this->status;
		}
		public function get_end_date( string $format = '' ): int|string|null {
			return $this->end_ts;
		}
		public function get_plan(): object {
			return $this->plan;
		}
	};
}

/**
 * A `\WP_User` double — the recipient + greeting source.
 */
function ink_le_userdata( string $email, string $display = 'Jan', string $login = 'jan' ): \WP_User {
	return new \WP_User( 7, $email, $display, $login );
}

/**
 * The 4.1 product map drives `LidmaatskapTerm` resolution: 1mo→101, 6mo→106, 12mo→112.
 * Drive both the product map AND the per-term/base toggles via `get_option` READS.
 *
 * @param array<string, array<string, bool>> $toggles Per-key toggle rows for TemplateStore::OPTION.
 */
function ink_le_get_option( array $toggles = array() ): void {
	Functions\when( 'get_option' )->alias(
		function ( string $name, $default = false ) use ( $toggles ) {
			if ( 'ink_membership_plan_products' === $name ) {
				return array( 1 => 101, 6 => 106, 12 => 112 );
			}
			if ( TemplateStore::OPTION === $name ) {
				return $toggles;
			}
			return $default;
		}
	);
	Functions\when( 'apply_filters' )->returnArg( 2 );
}

/**
 * A LifecycleEmails subclass that pins `now` and forces Action Scheduler available so the
 * schedule/anchor maths is deterministic without mocking PHP internals (the 4.1/4.2/4.3
 * testability-seam precedent).
 */
function ink_le_scheduler( \DateTimeImmutable $now, bool $as_available = true ): LifecycleEmails {
	return new class( $now, $as_available ) extends LifecycleEmails {
		public function __construct( private \DateTimeImmutable $pinnedNow, private bool $asAvailable ) {}
		protected function now(): \DateTimeImmutable {
			return $this->pinnedNow;
		}
		protected function isActionSchedulerAvailable(): bool {
			return $this->asAvailable;
		}
	};
}

/**
 * AC-1/AC-6: the template + hook keys are exact single-source constants.
 */
test( 'the warning template and Action Scheduler hook keys are the exact single-source constants', function (): void {
	expect( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY )->toBe( 'ink_membership_expiry_1week_email' );
	expect( LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY )->toBe( 'ink_membership_expiry_1month_email' );
	expect( LifecycleEmails::HOOK_SEND_WARNING )->toBe( 'ink_entitlement_send_expiry_warning' );
} );

/**
 * AC-4: the per-term toggle-key derivation is `"{base}_{months}"` — one source for the matrix.
 */
test( 'the per-term toggle key derivation is base_months', function (): void {
	expect( LifecycleEmails::toggleKeyFor( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY, LidmaatskapTerm::SixMonths ) )
		->toBe( 'ink_membership_expiry_1week_email_6' );
	expect( LifecycleEmails::toggleKeyFor( LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY, LidmaatskapTerm::TwelveMonths ) )
		->toBe( 'ink_membership_expiry_1month_email_12' );
} );

/**
 * AC-1: the single-source warning-type list encodes "1-week → every term; 1-month →
 * longer terms only" — the 1-month type NEVER applies to the 1-month term.
 */
test( 'the warning-type list applies 1-week to every term and 1-month to longer terms only', function (): void {
	$types = ( new LifecycleEmails() )->warningTypes();

	$by_key = array();
	foreach ( $types as $type ) {
		$by_key[ $type['key'] ] = $type['terms'];
	}

	expect( $by_key[ LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY ] )->toBe( LidmaatskapTerm::cases() );

	$one_month_terms = $by_key[ LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY ];
	expect( $one_month_terms )->toContain( LidmaatskapTerm::SixMonths );
	expect( $one_month_terms )->toContain( LidmaatskapTerm::TwelveMonths );
	expect( $one_month_terms )->not->toContain( LidmaatskapTerm::OneMonth );
} );

/**
 * AC-1/AC-5: the two warning templates register Afrikaans-source, DISABLED (base toggle
 * OFF), {skrywer} greeting + authored Afrikaans body (no placeholder marker), no English leak.
 */
test( 'the warning templates register Afrikaans-source, DISABLED, with the authored body', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	$store = ink_le_wire_notifications();

	( new LifecycleEmails() )->registerTemplates();

	foreach ( array( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY, LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY ) as $key ) {
		expect( $store->isRegistered( $key ) )->toBeTrue();
		expect( $store->isEnabled( $key ) )->toBeFalse(); // base toggle OFF.
		expect( $store->body( $key ) )->toContain( '{skrywer}' );
		expect( $store->body( $key ) )->not->toContain( '[WAG OP MENSLIKE KOPIE]' ); // human copy landed.
		expect( $store->body( $key ) )->toContain( 'verval' );

		$subject = strtolower( $store->subject( $key ) );
		foreach ( array( 'membership', 'expire', 'expiry', 'warning', 'reminder', 'renew' ) as $english ) {
			expect( $subject )->not->toContain( $english );
		}
	}
} );

/**
 * AC-6: registerTemplates does NOT register a second activation template — the activation
 * thank-you stays the ONE PurchaseActivation template (no duplicate, no double-send).
 */
test( 'LifecycleEmails does not register an activation template (exactly one activation template)', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	$store = ink_le_wire_notifications();

	( new LifecycleEmails() )->registerTemplates();

	expect( $store->isRegistered( PurchaseActivation::ACTIVATED_TEMPLATE_KEY ) )->toBeFalse();
} );

/**
 * AC-1/AC-3: a 6-month activation schedules a 1-week warning AND a 1-month warning, each
 * at `anchor − lead` off the SAST end-of-day anchor; both clear any stale prior schedule.
 */
test( 'a 6-month activation schedules a 1-week and a 1-month warning off the SAST anchor', function (): void {
	ink_le_get_option();

	// End date 2026-12-01; anchor = 23:59:59 SAST that day.
	$end_ts = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now    = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) );

	$anchor       = \Ink\Kernel\Sast::endOfDay( new \DateTimeImmutable( '@' . $end_ts ) );
	$expect_week  = $anchor->modify( '-1 week' )->getTimestamp();
	$expect_month = $anchor->modify( '-1 month' )->getTimestamp();

	$scheduled = array();
	Functions\when( 'as_unschedule_all_actions' )->justReturn( null );
	Functions\when( 'as_schedule_single_action' )->alias(
		function ( int $ts, string $hook, array $args, string $group ) use ( &$scheduled ) {
			$scheduled[] = array( $ts, $args[2] ); // [ timestamp, base_key ]
		}
	);

	$membership = ink_le_membership( 55, 7, 'active', $end_ts, array( 106 ) ); // 6-month product.
	ink_le_scheduler( $now )->scheduleWarnings( $membership );

	expect( $scheduled )->toHaveCount( 2 );

	$by_key = array();
	foreach ( $scheduled as $row ) {
		$by_key[ $row[1] ] = $row[0];
	}
	expect( $by_key[ LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY ] )->toBe( $expect_week );
	expect( $by_key[ LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY ] )->toBe( $expect_month );
} );

/**
 * AC-1: a 1-month-term activation schedules the 1-week warning ONLY — never the
 * 1-month warning (the 1-month-prior instant would fall on/before activation).
 */
test( 'a 1-month-term activation schedules ONLY the 1-week warning (no 1-month warning)', function (): void {
	ink_le_get_option();

	$end_ts = ( new \DateTimeImmutable( '2026-07-23 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now    = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) );

	$keys = array();
	Functions\when( 'as_unschedule_all_actions' )->justReturn( null );
	Functions\when( 'as_schedule_single_action' )->alias(
		function ( int $ts, string $hook, array $args, string $group ) use ( &$keys ) {
			$keys[] = $args[2];
		}
	);

	$membership = ink_le_membership( 55, 7, 'active', $end_ts, array( 101 ) ); // 1-month product.
	ink_le_scheduler( $now )->scheduleWarnings( $membership );

	expect( $keys )->toBe( array( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY ) );
} );

/**
 * AC-3: a warning instant in the PAST is not scheduled (skip, never fired immediately).
 * Here "now" is only 3 days before expiry, so `anchor − 1 week` is already past.
 */
test( 'a past-instant warning is not scheduled (skip, no immediate fire)', function (): void {
	ink_le_get_option();

	$end_ts = ( new \DateTimeImmutable( '2026-06-26 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now    = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) ); // 3 days before.

	$count = 0;
	Functions\when( 'as_unschedule_all_actions' )->justReturn( null );
	Functions\when( 'as_schedule_single_action' )->alias(
		function () use ( &$count ) {
			++$count;
		}
	);

	// 1-month term: only the 1-week warning applies, and its instant is already past.
	$membership = ink_le_membership( 55, 7, 'active', $end_ts, array( 101 ) );
	ink_le_scheduler( $now )->scheduleWarnings( $membership );

	expect( $count )->toBe( 0 );
} );

/**
 * AC-3: a transition OUT of active (here to expired) cancels any scheduled warnings.
 */
test( 'a transition out of active cancels the scheduled warnings', function (): void {
	ink_le_get_option();

	$unscheduled = array();
	Functions\when( 'as_unschedule_all_actions' )->alias(
		function ( string $hook, array $args, string $group ) use ( &$unscheduled ) {
			$unscheduled[] = $args[2]; // base_key
		}
	);
	Functions\expect( 'as_schedule_single_action' )->never();

	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now        = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) );
	$membership = ink_le_membership( 55, 7, 'expired', $end_ts, array( 106 ) );

	ink_le_scheduler( $now )->onMembershipStatusChanged( $membership, 'active', 'expired' );

	// Both warning types' schedules cleared for the membership.
	expect( $unscheduled )->toContain( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY );
	expect( $unscheduled )->toContain( LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY );
} );

/**
 * AC-3/AC-6: Action Scheduler absent ⇒ graceful no-op (no schedule, no fatal).
 */
test( 'Action Scheduler absent is a graceful no-op', function (): void {
	ink_le_get_option();
	Functions\expect( 'as_schedule_single_action' )->never();
	Functions\expect( 'as_unschedule_all_actions' )->never();

	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now        = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) );
	$membership = ink_le_membership( 55, 7, 'active', $end_ts, array( 106 ) );

	ink_le_scheduler( $now, as_available: false )->scheduleWarnings( $membership );
} );

/**
 * AC-4/AC-5: sendWarning does NOTHING when the per-term toggle is OFF (fail-safe) — the
 * recipient resolution is never reached, no wp_mail. The live membership IS re-read first
 * (the term is re-resolved live, FIX 2/3), but the OFF toggle short-circuits the send.
 */
test( 'sendWarning does not send when the per-term toggle is OFF', function (): void {
	ink_le_get_option(); // no toggle rows ⇒ fail-safe OFF.
	ink_le_wire_notifications();

	// Live membership resolves to a 6-month INK lidmaatskap so the term is known and the
	// ONLY thing suppressing the send is the OFF per-term toggle.
	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$membership = ink_le_membership( 55, 7, 'active', $end_ts, array( 106 ) );
	Functions\when( 'wc_memberships_get_user_membership' )->justReturn( $membership );

	Functions\expect( 'get_userdata' )->never();
	Functions\expect( 'wp_mail' )->never();

	( new LifecycleEmails() )->sendWarning( 55, 7, LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY );
} );

/**
 * AC-3/AC-4/AC-5: with BOTH the per-term AND base toggles ON and the live membership
 * still within the window, sendWarning dispatches the merged Afrikaans warning once.
 */
test( 'sendWarning sends once when per-term + base toggles are ON and the live membership is still in-window', function (): void {
	$week_key   = LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY;
	$toggle_key = LifecycleEmails::toggleKeyFor( $week_key, LidmaatskapTerm::SixMonths );

	// Base key ON (copy-ready master) + the per-term (week,6mo) pair ON.
	ink_le_get_option(
		array(
			$week_key   => array( 'enabled' => true ),
			$toggle_key => array( 'enabled' => true ),
		)
	);
	$store = ink_le_wire_notifications();
	( new LifecycleEmails() )->registerTemplates(); // so the base template body/subject exist.

	Functions\when( 'get_userdata' )->justReturn( ink_le_userdata( 'lid@ink.test' ) );
	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-11-25 12:00:00', new \DateTimeZone( 'UTC' ) ) );

	// Live membership: active, end date still in the future ⇒ still in-window.
	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$membership = ink_le_membership( 55, 7, 'active', $end_ts, array( 106 ) );
	Functions\when( 'wc_memberships_get_user_membership' )->justReturn( $membership );

	Functions\expect( 'wp_mail' )->once()->andReturn( true );

	( new LifecycleEmails() )->sendWarning( 55, 7, $week_key );
} );

/**
 * AC-3 (the no-warn-if-renewed re-check): with the toggles ON but the live membership
 * CANCELLED (administrative revocation), sendWarning does NOT dispatch.
 */
test( 'sendWarning does not send when the live membership is cancelled (renewed/revoked re-check)', function (): void {
	$week_key   = LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY;
	$toggle_key = LifecycleEmails::toggleKeyFor( $week_key, LidmaatskapTerm::SixMonths );
	ink_le_get_option(
		array(
			$week_key   => array( 'enabled' => true ),
			$toggle_key => array( 'enabled' => true ),
		)
	);
	ink_le_wire_notifications();
	( new LifecycleEmails() )->registerTemplates();

	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-11-25 12:00:00', new \DateTimeZone( 'UTC' ) ) );
	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$membership = ink_le_membership( 55, 7, 'cancelled', $end_ts, array( 106 ) ); // admin-revoked.
	Functions\when( 'wc_memberships_get_user_membership' )->justReturn( $membership );

	Functions\expect( 'get_userdata' )->never(); // re-check fails before recipient resolution.
	Functions\expect( 'wp_mail' )->never();

	( new LifecycleEmails() )->sendWarning( 55, 7, $week_key );
} );

/**
 * AC-3 (expired lid): the live membership has lapsed — "now" is past the live end date's
 * end-of-day window ⇒ no warning. Here "now" is after the live end date's end-of-day, so
 * isThroughEndOfDay() is false. (Distinct from the renewed-FORWARD case below.)
 */
test( 'sendWarning does not send when now is already past the live end date window', function (): void {
	$week_key   = LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY;
	$toggle_key = LifecycleEmails::toggleKeyFor( $week_key, LidmaatskapTerm::SixMonths );
	ink_le_get_option(
		array(
			$week_key   => array( 'enabled' => true ),
			$toggle_key => array( 'enabled' => true ),
		)
	);
	ink_le_wire_notifications();
	( new LifecycleEmails() )->registerTemplates();

	// "now" is AFTER the live end date's end-of-day-SAST window.
	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-12-05 12:00:00', new \DateTimeZone( 'UTC' ) ) );
	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$membership = ink_le_membership( 55, 7, 'expired', $end_ts, array( 106 ) );
	Functions\when( 'wc_memberships_get_user_membership' )->justReturn( $membership );

	Functions\expect( 'wp_mail' )->never();

	( new LifecycleEmails() )->sendWarning( 55, 7, $week_key );
} );

/**
 * FIX 1 (the in-place renewal gap): an active→active end-date EXTENSION (no status
 * transition) reschedules the warnings off the NEW anchor AND clears the OLD ones. The
 * `wc_memberships_user_membership_saved` listener handles the save while still active.
 */
test( 'an active-to-active end-date extension reschedules off the new anchor and clears the old', function (): void {
	ink_le_get_option();

	// Renewed forward: the live end date is now 2026-12-01 (months out).
	$new_end = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now     = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) );

	$anchor       = \Ink\Kernel\Sast::endOfDay( new \DateTimeImmutable( '@' . $new_end ) );
	$expect_week  = $anchor->modify( '-1 week' )->getTimestamp();
	$expect_month = $anchor->modify( '-1 month' )->getTimestamp();

	$unscheduled = array();
	$scheduled   = array();
	Functions\when( 'as_unschedule_all_actions' )->alias(
		function ( string $hook, array $args, string $group ) use ( &$unscheduled ) {
			$unscheduled[] = $args[2]; // base_key — the invariant identity (FIX 2), term-independent.
		}
	);
	Functions\when( 'as_schedule_single_action' )->alias(
		function ( int $ts, string $hook, array $args, string $group ) use ( &$scheduled ) {
			// The scheduled args are the INVARIANT IDENTITY only: [ membership_id, user_id, base_key ].
			expect( $args )->toHaveCount( 3 );
			$scheduled[ $args[2] ] = $ts;
		}
	);

	$membership = ink_le_membership( 55, 7, 'active', $new_end, array( 106 ) ); // 6-month, still active.

	// The renewal save (no status transition) fires onMembershipSaved → reschedule.
	ink_le_scheduler( $now )->onMembershipSaved( $membership );

	// OLD anchor's warnings of BOTH types were cleared (unschedule by invariant identity)…
	expect( $unscheduled )->toContain( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY );
	expect( $unscheduled )->toContain( LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY );
	// …and fresh warnings scheduled off the NEW anchor.
	expect( $scheduled[ LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY ] )->toBe( $expect_week );
	expect( $scheduled[ LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY ] )->toBe( $expect_month );
} );

/**
 * FIX 2 (invariant-identity unschedule): a TERM-CHANGE renewal (6mo→12mo) clears the OLD
 * term's scheduled warnings — the unschedule matches on [ membership_id, user_id, base_key ],
 * NOT the mutable term, so no stale 6-month warning survives / no duplicate accumulation.
 */
test( 'a term-change renewal clears the old term scheduled warnings via the invariant identity', function (): void {
	ink_le_get_option();

	$new_end = ( new \DateTimeImmutable( '2027-06-23 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$now     = new \DateTimeImmutable( '2026-06-23 12:00:00', new \DateTimeZone( 'UTC' ) );

	$unschedule_args = array();
	Functions\when( 'as_unschedule_all_actions' )->alias(
		function ( string $hook, array $args, string $group ) use ( &$unschedule_args ) {
			$unschedule_args[] = $args;
		}
	);
	Functions\when( 'as_schedule_single_action' )->justReturn( null );

	// Membership renewed onto the 12-month product (was 6-month before).
	$membership = ink_le_membership( 55, 7, 'active', $new_end, array( 112 ) );
	ink_le_scheduler( $now )->scheduleWarnings( $membership );

	// Every unschedule call carries exactly the term-INDEPENDENT identity (3 args, no months),
	// so the OLD 6-month warning of each type is matched and cleared regardless of term.
	foreach ( $unschedule_args as $args ) {
		expect( $args )->toHaveCount( 3 );
		expect( $args[0] )->toBe( 55 );
		expect( $args[1] )->toBe( 7 );
	}
	$keys = array_map( static fn ( array $a ): string => $a[2], $unschedule_args );
	expect( $keys )->toContain( LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY );
	expect( $keys )->toContain( LifecycleEmails::WARN_1MONTH_TEMPLATE_KEY );
} );

/**
 * FIX 3 (robust send-time re-check — renewed-FORWARD): a STILL-ACTIVE member whose live
 * end date moved FORWARD (renewed months out) is NOT warned at the OLD anchor instant,
 * even though the membership is active and still in-window — the recomputed fire instant
 * off the LIVE anchor is far in the future, outside the ~1-day tolerance. This is the
 * genuine "renewed lid" case (the old test only covered an EXPIRED member).
 */
test( 'sendWarning does not send a renewed-forward member at the old anchor instant', function (): void {
	$week_key   = LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY;
	$toggle_key = LifecycleEmails::toggleKeyFor( $week_key, LidmaatskapTerm::SixMonths );
	ink_le_get_option(
		array(
			$week_key   => array( 'enabled' => true ),
			$toggle_key => array( 'enabled' => true ),
		)
	);
	ink_le_wire_notifications();
	( new LifecycleEmails() )->registerTemplates();

	// "now" is the OLD anchor's 1-week-prior instant (the warning would have fired about
	// the OLD term). But the member RENEWED: the LIVE end date is now far in the future.
	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-11-24 12:00:00', new \DateTimeZone( 'UTC' ) ) );

	// Live membership: ACTIVE, still in-window, but the end date moved to 2027-05-01 (renewed).
	$live_end   = ( new \DateTimeImmutable( '2027-05-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$membership = ink_le_membership( 55, 7, 'active', $live_end, array( 106 ) );
	Functions\when( 'wc_memberships_get_user_membership' )->justReturn( $membership );

	Functions\expect( 'wp_mail' )->never(); // recomputed fire instant is months out ⇒ suppressed.

	( new LifecycleEmails() )->sendWarning( 55, 7, $week_key );
} );

/**
 * FIX 4 (single source): shouldStillWarn() reuses SubmissionGate::REVOKED_STATUSES rather
 * than a duplicated hardcoded set — every revoked status the 4.3 gate denies also
 * suppresses a warning. Asserts the shared source is consulted (paused ⇒ no send).
 */
test( 'sendWarning reuses SubmissionGate::REVOKED_STATUSES (paused suppresses the warning)', function (): void {
	$week_key   = LifecycleEmails::WARN_1WEEK_TEMPLATE_KEY;
	$toggle_key = LifecycleEmails::toggleKeyFor( $week_key, LidmaatskapTerm::SixMonths );
	ink_le_get_option(
		array(
			$week_key   => array( 'enabled' => true ),
			$toggle_key => array( 'enabled' => true ),
		)
	);
	ink_le_wire_notifications();
	( new LifecycleEmails() )->registerTemplates();

	// `paused` is in SubmissionGate::REVOKED_STATUSES — sanity-assert the shared source.
	expect( \Ink\Entitlement\SubmissionGate::REVOKED_STATUSES )->toContain( 'paused' );

	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-11-25 12:00:00', new \DateTimeZone( 'UTC' ) ) );
	$end_ts     = ( new \DateTimeImmutable( '2026-12-01 10:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$membership = ink_le_membership( 55, 7, 'paused', $end_ts, array( 106 ) );
	Functions\when( 'wc_memberships_get_user_membership' )->justReturn( $membership );

	Functions\expect( 'wp_mail' )->never();

	( new LifecycleEmails() )->sendWarning( 55, 7, $week_key );
} );

/**
 * AC-6 (conflation): the LifecycleEmails CODE carries ZERO reference to `Ink\Tiers` and
 * never writes `ink_writer_tier`. Scans the comment-stripped source (so doc prose about
 * the conflation rule is not a false positive).
 */
test( 'the lifecycle-emails code has no Ink\\Tiers coupling (conflation rule)', function (): void {
	$file = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/LifecycleEmails.php';
	expect( is_file( $file ) )->toBeTrue();

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

	expect( $code )->not->toContain( 'use Ink\Tiers' );
	expect( $code )->not->toContain( 'Ink\Tiers\\' );
	expect( $code )->not->toContain( 'ink_writer_tier' );
} );
