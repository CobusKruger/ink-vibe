<?php
/**
 * Unit tests for the QA-fixture title predicate (Epic-19 theme-fidelity rework).
 *
 * Target: {@see \Ink\Kernel\QaFixture} — the single place that recognises a seeded
 * QA-fixture post by its `QA FIXTURE — ` title convention, so every live-query
 * consumer (Sponsors\Campaign, Challenges\CurrentChallenge, Discovery\FeaturedStream)
 * excludes fixtures the same way. Pure PHP — no WordPress, no Brain Monkey doubles.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\QaFixture;

test( 'isFixtureTitle() recognises the QA FIXTURE — prefix', function (): void {
	expect( QaFixture::isFixtureTitle( 'QA FIXTURE — Somerreën' ) )->toBeTrue();
	expect( QaFixture::isFixtureTitle( 'QA FIXTURE — Die Wind Onthou' ) )->toBeTrue();
} );

test( 'isFixtureTitle() tolerates leading/trailing whitespace', function (): void {
	expect( QaFixture::isFixtureTitle( '  QA FIXTURE — Somerreën  ' ) )->toBeTrue();
} );

test( 'isFixtureTitle() rejects real editorial titles', function (): void {
	expect( QaFixture::isFixtureTitle( 'Die Laaste Reën' ) )->toBeFalse();
	expect( QaFixture::isFixtureTitle( '' ) )->toBeFalse();
	// A title that merely mentions the phrase mid-string is NOT the prefix convention.
	expect( QaFixture::isFixtureTitle( 'Nie \'n QA FIXTURE nie' ) )->toBeFalse();
} );

test( 'TITLE_PREFIX is the single-source convention string', function (): void {
	expect( QaFixture::TITLE_PREFIX )->toBe( 'QA FIXTURE' );
} );
