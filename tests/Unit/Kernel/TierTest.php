<?php
/**
 * Unit tests for the Kernel writer-Gradering value type (Story 5.1).
 *
 * Target: {@see \Ink\Kernel\Tier} — the four-grade enum, its data-model
 * behaviour (default, manual-only/auto-promotable semantics) and the
 * Kernel-owned meta-key single source the `Content` registrar and the `Tiers`
 * reader share.
 *
 * No WordPress / Brain Monkey needed: the enum is a pure value type, so the
 * cases and methods run for real.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\Tier;

/**
 * AC-1: the four grades exist with the exact lowercase Afrikaans backing strings.
 */
test( 'the four grades carry the exact persisted backing strings', function (): void {
	expect( Tier::Brons->value )->toBe( 'brons' );
	expect( Tier::Silwer->value )->toBe( 'silwer' );
	expect( Tier::Goud->value )->toBe( 'goud' );
	expect( Tier::Meester->value )->toBe( 'meester' );
	expect( Tier::cases() )->toHaveCount( 4 );
} );

/**
 * AC-1: the single-source default is Brons.
 */
test( 'default() is Brons', function (): void {
	expect( Tier::default() )->toBe( Tier::Brons );
} );

/**
 * AC-1: Meester is the only manual-only grade.
 */
test( 'isManualOnly() is true only for Meester', function (): void {
	expect( Tier::Meester->isManualOnly() )->toBeTrue();
	expect( Tier::Brons->isManualOnly() )->toBeFalse();
	expect( Tier::Silwer->isManualOnly() )->toBeFalse();
	expect( Tier::Goud->isManualOnly() )->toBeFalse();
} );

/**
 * AC-1: only Brons and Silwer participate in auto-promotion; Goud and Meester
 * are terminal for the engine (Meester is also never auto-PRODUCED — manual-only).
 */
test( 'isAutoPromotable() is true for Brons/Silwer and false for Goud/Meester', function (): void {
	expect( Tier::Brons->isAutoPromotable() )->toBeTrue();
	expect( Tier::Silwer->isAutoPromotable() )->toBeTrue();
	expect( Tier::Goud->isAutoPromotable() )->toBeFalse();
	expect( Tier::Meester->isAutoPromotable() )->toBeFalse();
} );

/**
 * AC-3: the meta-key single source carries the exact `ink_`-prefixed literals
 * (migration-load-bearing — must never drift).
 */
test( 'the Kernel meta-key constants are the exact prefixed IDs', function (): void {
	expect( Tier::META_KEY )->toBe( 'ink_writer_tier' );
	expect( Tier::PROMOTED_AT_META_KEY )->toBe( 'ink_tier_promoted_at' );
} );
