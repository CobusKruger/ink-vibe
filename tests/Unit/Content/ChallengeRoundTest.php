<?php
/**
 * Unit tests for the round-term ↔ uitdaging slug convention (Stories 6.6 / 10.5).
 *
 * Target: {@see \Ink\Content\ChallengeRound} — the single source both the writer
 * (Submission) and the reader (Library winner→challenge linkage) consume. Pure,
 * no WordPress needed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\ChallengeRound;

test( 'slugFor encodes the uitdaging id with the stable prefix', function (): void {
	expect( ChallengeRound::slugFor( 7 ) )->toBe( 'uitdaging-7' );
	expect( ChallengeRound::slugFor( 123 ) )->toBe( 'uitdaging-123' );
} );

test( 'uitdagingIdFromSlug round-trips a slugFor value', function (): void {
	foreach ( array( 1, 7, 42, 999 ) as $id ) {
		expect( ChallengeRound::uitdagingIdFromSlug( ChallengeRound::slugFor( $id ) ) )->toBe( $id );
	}
} );

test( 'uitdagingIdFromSlug returns null for slugs that do not encode a positive uitdaging id', function (): void {
	foreach ( array( '', 'foo', 'uitdaging-', 'uitdaging-0', 'uitdaging-abc', 'uitdaging-1a', 'x-uitdaging-1', 'uitdaging-01' ) as $bad ) {
		expect( ChallengeRound::uitdagingIdFromSlug( $bad ) )->toBeNull();
	}
} );

test( 'isCompetition picks out competitions by name, and leaves the real rounds alone', function (): void {
	expect( ChallengeRound::isCompetition( 'INK dig- en skryfkompetisie 2022' ) )->toBeTrue();
	expect( ChallengeRound::isCompetition( 'GrysKrag kompetisie' ) )->toBeTrue();
	expect( ChallengeRound::isCompetition( 'INK-Slypskool' ) )->toBeFalse();
	expect( ChallengeRound::isCompetition( 'Julie 2022 Wat skryf ek projek' ) )->toBeFalse();
} );
