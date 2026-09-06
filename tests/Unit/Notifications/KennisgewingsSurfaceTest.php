<?php
/**
 * Unit tests for the kennisgewings read-model (My Profiel rebuild §5.8).
 *
 * Target: {@see \Ink\Notifications\KennisgewingsSurface}. The pure grouping
 * (`groupRows`), template mapping (`textFor`), unread counting
 * (`countUnreadRows`), deadline maths (`daysUntilClose`) and rendering
 * (`toHtml`), plus the full guarded `rows()`/`recent()`/`unreadCount()` pipeline
 * against mocked BuddyPress functions.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Content\FieldSets;
use Ink\Notifications\Kennisgewings;
use Ink\Notifications\KennisgewingsSurface;
use Ink\Notifications\NotificationType;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	// Every rows()/recent()/unreadCount() call reads the mark-all-read boundary
	// unconditionally (Kennisgewings::boundaryFor -> get_user_meta) regardless of
	// whether BuddyPress is present — default to "never marked" unless a test
	// overrides it.
	Functions\when( 'get_user_meta' )->justReturn( '' );
	// Brain Monkey leaks a mocked function's mere EXISTENCE across tests once any
	// test in the process has called `Functions\when()`/`expect()` on it (the same
	// leak class `tests/bootstrap.php` documents for class stubs) — so once one
	// test below mocks `current_datetime`, every later test that reaches
	// `Sast::now()` needs its OWN mock too, or the call errors as "not defined nor
	// mocked" rather than falling through to `function_exists() === false`. A
	// harmless baseline here covers every test that touches an Uitdaging row but
	// doesn't care about the exact "now".
	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-09-06 00:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) ) );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// ---------------------------------------------------------------------------
// daysUntilClose (pure)
// ---------------------------------------------------------------------------

test( 'daysUntilClose counts whole days through the 23:59:59 SAST boundary', function (): void {
	// endOfDay('2026-09-11') = 2026-09-11 23:59:59 SAST; now is exactly 5*86400
	// seconds earlier, so the diff is a clean 5 days.
	$now = new \DateTimeImmutable( '2026-09-06 23:59:59', new \DateTimeZone( 'Africa/Johannesburg' ) );

	expect( KennisgewingsSurface::daysUntilClose( '2026-09-11', $now ) )->toBe( 5 );
} );

test( 'daysUntilClose floors at 0 for an already-closed challenge', function (): void {
	$now = new \DateTimeImmutable( '2026-09-06 10:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	expect( KennisgewingsSurface::daysUntilClose( '2026-09-01', $now ) )->toBe( 0 );
} );

test( 'daysUntilClose returns 0 for an empty deadline string', function (): void {
	expect( KennisgewingsSurface::daysUntilClose( '', new \DateTimeImmutable( 'now' ) ) )->toBe( 0 );
} );

test( 'daysUntilClose returns 0 for an unparseable deadline string', function (): void {
	expect( KennisgewingsSurface::daysUntilClose( 'not-a-date', new \DateTimeImmutable( 'now' ) ) )->toBe( 0 );
} );

// ---------------------------------------------------------------------------
// groupRows (pure)
// ---------------------------------------------------------------------------

test( 'groupRows collapses same-subject Reaksie rows into one, keeping the most recent actor', function (): void {
	$rows = KennisgewingsSurface::groupRows(
		array(
			array( 'type' => 'reaksie', 'subject_id' => 100, 'actor_id' => 11, 'created_gmt' => '2026-09-01 10:00:00' ),
			array( 'type' => 'reaksie', 'subject_id' => 100, 'actor_id' => 22, 'created_gmt' => '2026-09-02 10:00:00' ),
		)
	);

	expect( $rows )->toHaveCount( 1 );
	expect( $rows[0]['actor_id'] )->toBe( 22 ); // the more recent actor is the displayed one.
	expect( $rows[0]['created_gmt'] )->toBe( '2026-09-02 10:00:00' );
	expect( $rows[0]['n_others'] )->toBe( 1 ); // 2 distinct actors - 1 displayed.
} );

test( 'groupRows never collapses rows of a different type, even on the same subject', function (): void {
	$rows = KennisgewingsSurface::groupRows(
		array(
			array( 'type' => 'reaksie', 'subject_id' => 100, 'actor_id' => 11, 'created_gmt' => '2026-09-01 10:00:00' ),
			array( 'type' => 'mention', 'subject_id' => 100, 'actor_id' => 22, 'created_gmt' => '2026-09-02 10:00:00' ),
		)
	);

	expect( $rows )->toHaveCount( 2 );
} );

test( 'groupRows leaves non-Reaksie rows one-per-row with n_others = 0', function (): void {
	$rows = KennisgewingsSurface::groupRows(
		array(
			array( 'type' => 'mention', 'subject_id' => 200, 'actor_id' => 33, 'created_gmt' => '2026-09-03 09:00:00' ),
		)
	);

	expect( $rows )->toHaveCount( 1 );
	expect( $rows[0]['n_others'] )->toBe( 0 );
} );

test( 'groupRows sorts every row (grouped and passthrough) newest-first', function (): void {
	$rows = KennisgewingsSurface::groupRows(
		array(
			array( 'type' => 'reaksie', 'subject_id' => 100, 'actor_id' => 11, 'created_gmt' => '2026-09-02 10:00:00' ),
			array( 'type' => 'uitdaging', 'subject_id' => 300, 'actor_id' => 0, 'created_gmt' => '2026-09-04 08:00:00' ),
			array( 'type' => 'mention', 'subject_id' => 200, 'actor_id' => 33, 'created_gmt' => '2026-09-03 09:00:00' ),
		)
	);

	expect( array_column( $rows, 'type' ) )->toBe( array( 'uitdaging', 'mention', 'reaksie' ) );
} );

test( 'groupRows a single reaksie actor yields n_others = 0 (no phantom others)', function (): void {
	$rows = KennisgewingsSurface::groupRows(
		array(
			array( 'type' => 'reaksie', 'subject_id' => 100, 'actor_id' => 11, 'created_gmt' => '2026-09-01 10:00:00' ),
		)
	);

	expect( $rows[0]['n_others'] )->toBe( 0 );
} );

// ---------------------------------------------------------------------------
// textFor (pure, per ratified template)
// ---------------------------------------------------------------------------

test( 'textFor renders the Reaksie "liked" template', function (): void {
	$text = KennisgewingsSurface::textFor( NotificationType::Reaksie, 'Anja', 'My Storie', 1, 0 );

	expect( $text )->toBe( 'Anja en nog 1 ander het "My Storie" liefgehad' );
} );

test( 'textFor renders the Mention "critique" template', function (): void {
	$text = KennisgewingsSurface::textFor( NotificationType::Mention, 'Piet', 'Ander Storie', 0, 0 );

	expect( $text )->toBe( 'Piet het terugvoer gelewer op "Ander Storie"' );
} );

test( 'textFor renders the Uitdaging "closes in N days" template', function (): void {
	$text = KennisgewingsSurface::textFor( NotificationType::Uitdaging, '', 'Herfs Uitdaging', 0, 5 );

	expect( $text )->toBe( 'Herfs Uitdaging sluit oor 5 dae' );
} );

// The genuine ambiguity investigated for this build: NO NotificationType case
// represents "someone started following you" — VolgWerk's WIRED meaning is "a
// followed writer published new work" (see the class docblock). textFor()
// must NOT render the ratified "volg jou nou" copy for it (that would state
// the follow direction backwards), nor invent new copy.
test( 'textFor renders a flagged placeholder for VolgWerk, never the mismatched "volg jou nou" copy', function (): void {
	$text = KennisgewingsSurface::textFor( NotificationType::VolgWerk, 'Jan', 'n Nuwe Storie', 0, 0 );

	expect( $text )->toContain( 'NEEDS DEV DECISION' );
	expect( $text )->not->toContain( 'volg jou nou' );
} );

test( 'textFor renders the same flagged placeholder for the two other templateless types', function (): void {
	expect( KennisgewingsSurface::textFor( NotificationType::LidmaatskapVerval, '', '', 0, 0 ) )->toContain( 'NEEDS DEV DECISION' );
	expect( KennisgewingsSurface::textFor( NotificationType::Ontvangs, 'Jan', 'n Storie', 0, 0 ) )->toContain( 'NEEDS DEV DECISION' );
} );

// ---------------------------------------------------------------------------
// countUnreadRows (pure)
// ---------------------------------------------------------------------------

test( 'countUnreadRows counts only rows flagged unread', function (): void {
	$rows = array(
		array( 'unread' => true ),
		array( 'unread' => false ),
		array( 'unread' => true ),
	);

	expect( KennisgewingsSurface::countUnreadRows( $rows ) )->toBe( 2 );
} );

test( 'countUnreadRows is 0 for an empty row list', function (): void {
	expect( KennisgewingsSurface::countUnreadRows( array() ) )->toBe( 0 );
} );

// ---------------------------------------------------------------------------
// toHtml (escaping only)
// ---------------------------------------------------------------------------

test( 'toHtml renders the heading, the mark-all-read button and each row with its unread state', function (): void {
	$html = KennisgewingsSurface::toHtml(
		array(
			array( 'type' => 'reaksie', 'created_gmt' => '2026-09-02 10:00:00', 'unread' => true, 'text' => 'Anja het liefgehad' ),
			array( 'type' => 'mention', 'created_gmt' => '2026-09-01 10:00:00', 'unread' => false, 'text' => 'Piet het terugvoer gelewer' ),
		)
	);

	expect( $html )->toContain( 'Kennisgewings' );
	expect( $html )->toContain( 'data-ink-kennisgewings-merk-alles' );
	expect( $html )->toContain( 'Merk alles as gelees' );
	expect( $html )->toContain( 'is-unread' );
	expect( $html )->toContain( 'data-ink-kennisgewing-status="unread"' );
	expect( $html )->toContain( 'data-ink-kennisgewing-status="gelees"' );
	expect( $html )->toContain( 'Anja het liefgehad' );
	expect( $html )->toContain( 'Piet het terugvoer gelewer' );
} );

test( 'toHtml renders a flagged empty state (no ratified copy yet) rather than a bare empty list', function (): void {
	$html = KennisgewingsSurface::toHtml( array() );

	expect( $html )->toContain( 'NEEDS HUMAN AFRIKAANS' );
	expect( $html )->not->toContain( '<ul' );
	// The button still renders — logged-out-of-content is not logged-out-of-the-affordance.
	expect( $html )->toContain( 'data-ink-kennisgewings-merk-alles' );
} );

// ---------------------------------------------------------------------------
// rows() / recent() / unreadCount() — the full guarded pipeline
// ---------------------------------------------------------------------------

test( 'rows returns nothing for a non-positive user id (no WordPress calls made)', function (): void {
	expect( KennisgewingsSurface::rows( 0 ) )->toBe( array() );
	expect( KennisgewingsSurface::rows( -5 ) )->toBe( array() );
} );

// MUST stay the first test in this file that touches `rows()`/BuddyPress:
// Brain Monkey/Patchwork makes a mocked function's mere EXISTENCE leak across
// every LATER test in the process once ANY test calls `Functions\when()`/
// `expect()` on it — `function_exists()` then reports `true` even though this
// test never configured a return value, which raises "MissingFunctionExpectations"
// instead of exercising the real guarded-absence branch. Every other test below
// mocks `bp_notifications_get_notifications_for_user`, so this one must run
// before them (top-to-bottom Pest order within one file); do not add another
// test file that mocks this same function name and could run earlier in the
// suite (see the matching note in `ApiTest.php`, which deliberately mocks
// nothing so it stays safe regardless of file run order).
test( 'rows returns nothing when BuddyPress is absent (clean no-op, mirrors Kennisgewings::add)', function (): void {
	// bp_notifications_get_notifications_for_user is left undefined.
	expect( KennisgewingsSurface::rows( 7 ) )->toBe( array() );
} );

test( 'rows maps, groups, orders and marks unread across every wired NotificationType', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '2026-09-02 12:00:00' ); // the mark-all-read boundary.
	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-09-06 23:59:59', new \DateTimeZone( 'Africa/Johannesburg' ) ) );

	Functions\when( 'bp_notifications_get_notifications_for_user' )->justReturn(
		array(
			// Two Reaksie rows on the SAME post (different comments) -> collapse to one, read (before boundary).
			array( 'component_action' => 'reaksie', 'item_id' => 501, 'secondary_item_id' => 11, 'date_notified' => '2026-09-01 10:00:00' ),
			array( 'component_action' => 'reaksie', 'item_id' => 502, 'secondary_item_id' => 22, 'date_notified' => '2026-09-02 10:00:00' ),
			// A Mention (unread, after boundary).
			array( 'component_action' => 'mention', 'item_id' => 601, 'secondary_item_id' => 33, 'date_notified' => '2026-09-03 09:00:00' ),
			// An Uitdaging (unread).
			array( 'component_action' => 'uitdaging', 'item_id' => 300, 'secondary_item_id' => 0, 'date_notified' => '2026-09-04 08:00:00' ),
			// A VolgWerk (unread, ambiguous copy — see textFor tests).
			array( 'component_action' => 'volg_werk', 'item_id' => 400, 'secondary_item_id' => 44, 'date_notified' => '2026-09-05 07:00:00' ),
			// A foreign/unknown BuddyPress notification type — never surfaced here.
			array( 'component_action' => 'some_unknown_bp_type', 'item_id' => 999, 'secondary_item_id' => 1, 'date_notified' => '2026-09-06 00:00:00' ),
		)
	);

	Functions\when( 'get_comment' )->alias(
		static function ( int $comment_id ) {
			return (object) array( 'comment_post_ID' => 501 === $comment_id || 502 === $comment_id ? 100 : 200 );
		}
	);

	Functions\when( 'get_the_title' )->alias(
		static function ( int $post_id ): string {
			return match ( $post_id ) {
				100     => 'My Storie',
				200     => 'Ander Storie',
				300     => 'Herfs Uitdaging',
				default => '',
			};
		}
	);

	Functions\when( 'get_userdata' )->alias(
		static function ( int $user_id ) {
			$names = array( 22 => 'Anja', 33 => 'Piet', 44 => 'Jan' );

			if ( ! isset( $names[ $user_id ] ) ) {
				return false;
			}

			return new \WP_User( $user_id, '', $names[ $user_id ] );
		}
	);

	Functions\when( 'get_post_meta' )->justReturn( '2026-09-11' ); // Uitdaging deadline.

	$rows = KennisgewingsSurface::rows( 7 );

	// 6 raw rows -> 2 Reaksie collapse to 1, the unknown type is dropped -> 4 rows.
	expect( $rows )->toHaveCount( 4 );

	// Newest-first ordering.
	expect( array_column( $rows, 'type' ) )->toBe( array( 'volg_werk', 'uitdaging', 'mention', 'reaksie' ) );

	// The grouped Reaksie row is READ (created before the boundary) — the others are UNREAD.
	expect( $rows[3]['unread'] )->toBeFalse();
	expect( $rows[3]['text'] )->toBe( 'Anja en nog 1 ander het "My Storie" liefgehad' );

	expect( $rows[2]['unread'] )->toBeTrue();
	expect( $rows[2]['text'] )->toBe( 'Piet het terugvoer gelewer op "Ander Storie"' );

	expect( $rows[1]['unread'] )->toBeTrue();
	expect( $rows[1]['text'] )->toBe( 'Herfs Uitdaging sluit oor 5 dae' );

	expect( $rows[0]['unread'] )->toBeTrue();
	expect( $rows[0]['text'] )->toContain( 'NEEDS DEV DECISION' ); // VolgWerk — see textFor tests.
} );

test( 'rows tolerates object-shaped raw rows (the documented BP_Notifications_Notification::get() shape)', function (): void {
	Functions\when( 'bp_notifications_get_notifications_for_user' )->justReturn(
		array(
			(object) array(
				'component_action'  => 'mention',
				'item_id'           => 601,
				'secondary_item_id' => 33,
				'date_notified'     => '2026-09-03 09:00:00',
			),
		)
	);

	Functions\when( 'get_comment' )->justReturn( (object) array( 'comment_post_ID' => 200 ) );
	Functions\when( 'get_the_title' )->justReturn( 'Ander Storie' );
	Functions\when( 'get_userdata' )->justReturn( new \WP_User( 33, '', 'Piet' ) );

	$rows = KennisgewingsSurface::rows( 7 );

	expect( $rows )->toHaveCount( 1 );
	expect( $rows[0]['text'] )->toBe( 'Piet het terugvoer gelewer op "Ander Storie"' );
} );

test( 'recent slices the first N rows off the same newest-first ordering', function (): void {
	Functions\when( 'bp_notifications_get_notifications_for_user' )->justReturn(
		array(
			array( 'component_action' => 'uitdaging', 'item_id' => 300, 'secondary_item_id' => 0, 'date_notified' => '2026-09-04 08:00:00' ),
			array( 'component_action' => 'mention', 'item_id' => 601, 'secondary_item_id' => 33, 'date_notified' => '2026-09-03 09:00:00' ),
			array( 'component_action' => 'volg_werk', 'item_id' => 400, 'secondary_item_id' => 44, 'date_notified' => '2026-09-05 07:00:00' ),
		)
	);

	Functions\when( 'get_comment' )->justReturn( (object) array( 'comment_post_ID' => 200 ) );
	Functions\when( 'get_the_title' )->justReturn( 'Titel' );
	Functions\when( 'get_userdata' )->justReturn( false );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	$recent = KennisgewingsSurface::recent( 7, 2 );

	expect( $recent )->toHaveCount( 2 );
	expect( array_column( $recent, 'type' ) )->toBe( array( 'volg_werk', 'uitdaging' ) );
} );

test( 'recent defaults to RECENT_LIMIT (3) when no limit is given', function (): void {
	expect( KennisgewingsSurface::RECENT_LIMIT )->toBe( 3 );

	Functions\when( 'bp_notifications_get_notifications_for_user' )->justReturn(
		array(
			array( 'component_action' => 'uitdaging', 'item_id' => 1, 'secondary_item_id' => 0, 'date_notified' => '2026-09-01 00:00:00' ),
			array( 'component_action' => 'uitdaging', 'item_id' => 2, 'secondary_item_id' => 0, 'date_notified' => '2026-09-02 00:00:00' ),
			array( 'component_action' => 'uitdaging', 'item_id' => 3, 'secondary_item_id' => 0, 'date_notified' => '2026-09-03 00:00:00' ),
			array( 'component_action' => 'uitdaging', 'item_id' => 4, 'secondary_item_id' => 0, 'date_notified' => '2026-09-04 00:00:00' ),
		)
	);
	Functions\when( 'get_the_title' )->justReturn( 'Titel' );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	expect( KennisgewingsSurface::recent( 7 ) )->toHaveCount( 3 );
} );

test( 'unreadCount shares the same rows() query and counts only unread rows', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '2026-09-02 12:00:00' );

	Functions\when( 'bp_notifications_get_notifications_for_user' )->justReturn(
		array(
			array( 'component_action' => 'uitdaging', 'item_id' => 300, 'secondary_item_id' => 0, 'date_notified' => '2026-09-01 00:00:00' ), // read
			array( 'component_action' => 'mention', 'item_id' => 601, 'secondary_item_id' => 33, 'date_notified' => '2026-09-05 00:00:00' ), // unread
		)
	);

	Functions\when( 'get_comment' )->justReturn( (object) array( 'comment_post_ID' => 200 ) );
	Functions\when( 'get_the_title' )->justReturn( 'Titel' );
	Functions\when( 'get_userdata' )->justReturn( false );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	expect( KennisgewingsSurface::unreadCount( 7 ) )->toBe( 1 );
} );

test( 'the Uitdaging days-until-close reads FieldSets::UITDAGING_DEADLINE off the subject post', function (): void {
	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-09-06 23:59:59', new \DateTimeZone( 'Africa/Johannesburg' ) ) );

	Functions\when( 'bp_notifications_get_notifications_for_user' )->justReturn(
		array(
			array( 'component_action' => 'uitdaging', 'item_id' => 300, 'secondary_item_id' => 0, 'date_notified' => '2026-09-04 08:00:00' ),
		)
	);

	Functions\when( 'get_the_title' )->justReturn( 'Herfs Uitdaging' );
	Functions\expect( 'get_post_meta' )->once()->with( 300, FieldSets::UITDAGING_DEADLINE, true )->andReturn( '2026-09-11' );

	$rows = KennisgewingsSurface::rows( 7 );

	expect( $rows[0]['text'] )->toBe( 'Herfs Uitdaging sluit oor 5 dae' );
} );
