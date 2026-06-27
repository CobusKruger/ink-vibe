<?php
/**
 * Unit tests for the graderingsgeskiedenis audit-trail display (Story 12.7, FR-51/UJ-5).
 *
 * Target: {@see \Ink\Challenges\PromotionHistory} — renders a writer's Gradering
 * history with each promotion's OPTIONAL linked challenge resolved to a title/link.
 * Pure rowView + toHtml; the challenge resolution is an overridable seam.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\PromotionHistory;
use Ink\Tiers\PromotionLogEntry;
use Ink\Kernel\Tier;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

function ink_log_entry( int $challengeId, int $actorId = 7 ): PromotionLogEntry {
	return new PromotionLogEntry(
		1,
		42,
		Tier::Brons,
		Tier::Silwer,
		$actorId,
		'Uitdaging-wen',
		$challengeId,
		'2026-10-31 12:00:00',
	);
}

test( 'rowView carries the from/to grade labels, reason and a resolved challenge link', function (): void {
	$row = PromotionHistory::rowView( ink_log_entry( 9 ), array( 'title' => 'Oktober-uitdaging', 'permalink' => 'https://ink.test/uitdaging/oktober' ) );

	expect( $row['from'] )->toBe( 'Brons' );
	expect( $row['to'] )->toBe( 'Silwer' );
	expect( $row['reason'] )->toBe( 'Uitdaging-wen' );
	expect( $row['is_system'] )->toBeFalse();
	expect( $row['challenge']['title'] )->toBe( 'Oktober-uitdaging' );
} );

test( 'rowView marks a system (actor 0) promotion and carries no challenge when unlinked', function (): void {
	$row = PromotionHistory::rowView( ink_log_entry( 0, 0 ), null );

	expect( $row['is_system'] )->toBeTrue();
	expect( $row['challenge'] )->toBeNull();
} );

test( 'toHtml renders the heading and a row per entry, linking the challenge', function (): void {
	$rows = array(
		PromotionHistory::rowView( ink_log_entry( 9 ), array( 'title' => 'Oktober-uitdaging', 'permalink' => 'https://ink.test/uitdaging/oktober' ) ),
		PromotionHistory::rowView( ink_log_entry( 0, 0 ), null ),
	);

	$html = PromotionHistory::toHtml( $rows );

	expect( $html )->toContain( 'Graderingsgeskiedenis' );
	expect( substr_count( $html, '<tr' ) )->toBeGreaterThanOrEqual( 2 );
	expect( $html )->toContain( 'Oktober-uitdaging' );
	expect( $html )->toContain( 'https://ink.test/uitdaging/oktober' );
} );

test( 'toHtml renders a graceful empty state with no history', function (): void {
	$html = PromotionHistory::toHtml( array() );

	expect( $html )->toContain( 'Graderingsgeskiedenis' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->not->toContain( '<tbody' );
} );
