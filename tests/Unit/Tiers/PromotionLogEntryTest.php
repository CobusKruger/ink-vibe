<?php
/**
 * Unit tests for the Gradering audit record value object (Story 5.3).
 *
 * Target: {@see \Ink\Tiers\PromotionLogEntry} — the typed, immutable view over
 * one `ink_tier_history` row, incl. the grade-coercing `fromRow()` mapper.
 *
 * Pure value type: no WordPress / Brain Monkey needed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\PromotionLogEntry;

/**
 * Build a raw row object as `$wpdb->get_results()` would return it.
 */
function ink_tier_history_row( array $overrides = array() ): object {
	return (object) array_merge(
		array(
			'id'           => '7',
			'user_id'      => '42',
			'from_tier'    => 'brons',
			'to_tier'      => 'silwer',
			'actor_id'     => '3',
			'reason'       => 'Wen van die Oktober-uitdaging',
			'challenge_id' => '11',
			'created_at'   => '2026-06-25 09:30:00',
		),
		$overrides
	);
}

/**
 * AC-2: fromRow maps every field with the right types and grade cases.
 */
test( 'fromRow maps every field to the typed entry', function (): void {
	$entry = PromotionLogEntry::fromRow( ink_tier_history_row() );

	expect( $entry->id )->toBe( 7 );
	expect( $entry->userId )->toBe( 42 );
	expect( $entry->from )->toBe( Tier::Brons );
	expect( $entry->to )->toBe( Tier::Silwer );
	expect( $entry->actorId )->toBe( 3 );
	expect( $entry->reason )->toBe( 'Wen van die Oktober-uitdaging' );
	expect( $entry->challengeId )->toBe( 11 );
	expect( $entry->createdAt )->toBe( '2026-06-25 09:30:00' );
} );

/**
 * AC-2: a stale/garbage stored grade is coerced to the default, never throws.
 */
test( 'fromRow coerces an unrecognised stored grade to the default', function (): void {
	$entry = PromotionLogEntry::fromRow( ink_tier_history_row( array( 'to_tier' => 'platinum', 'from_tier' => '' ) ) );

	expect( $entry->from )->toBe( Tier::default() );
	expect( $entry->to )->toBe( Tier::default() );
} );

/**
 * AC-1: actor_id 0 marks a system (automatic) change.
 */
test( 'isSystem is true only when actorId is 0', function (): void {
	$system = PromotionLogEntry::fromRow( ink_tier_history_row( array( 'actor_id' => '0' ) ) );
	$staff  = PromotionLogEntry::fromRow( ink_tier_history_row( array( 'actor_id' => '3' ) ) );

	expect( $system->isSystem() )->toBeTrue();
	expect( $staff->isSystem() )->toBeFalse();
} );

/**
 * AC-1: an optional challenge link is reflected by isChallengeLinked.
 */
test( 'isChallengeLinked reflects the optional challenge link', function (): void {
	$linked   = PromotionLogEntry::fromRow( ink_tier_history_row( array( 'challenge_id' => '11' ) ) );
	$unlinked = PromotionLogEntry::fromRow( ink_tier_history_row( array( 'challenge_id' => '0' ) ) );

	expect( $linked->isChallengeLinked() )->toBeTrue();
	expect( $unlinked->isChallengeLinked() )->toBeFalse();
} );
