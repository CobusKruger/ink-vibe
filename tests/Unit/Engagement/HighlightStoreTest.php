<?php
/**
 * Unit tests for saved text highlights.
 *
 * Target: {@see \Ink\Engagement\HighlightStore::normalise()} and
 * {@see \Ink\Engagement\HighlightController::isHighlightable()}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\HighlightController;
use Ink\Engagement\HighlightStore;

test( 'a real selection is kept, with the quote trimmed', function (): void {
	expect( HighlightStore::normalise( 2, 3, 22, "  steraand sit ek rus \n" ) )
		->toBe( array( 'para' => 2, 'start' => 3, 'end' => 22, 'quote' => 'steraand sit ek rus' ) );
} );

test( 'an impossible selection is refused', function (): void {
	expect( HighlightStore::normalise( -1, 0, 5, 'x' ) )->toBeNull()
		->and( HighlightStore::normalise( 0, -2, 5, 'x' ) )->toBeNull()
		->and( HighlightStore::normalise( 0, 5, 5, 'x' ) )->toBeNull()
		->and( HighlightStore::normalise( 0, 9, 5, 'x' ) )->toBeNull()
		->and( HighlightStore::normalise( 0, 0, 5, '   ' ) )->toBeNull()
		->and( HighlightStore::normalise( 0, 0, 20001, 'x' ) )->toBeNull();
} );

test( 'a very long quote is cut to the stored limit', function (): void {
	$out = HighlightStore::normalise( 0, 0, 2000, str_repeat( 'a', 900 ) );

	expect( mb_strlen( $out['quote'] ) )->toBe( HighlightStore::MAX_QUOTE );
} );

test( 'only stories and articles carry text highlights; poems use line hearts', function (): void {
	expect( HighlightController::isHighlightable( 'storie' ) )->toBeTrue()
		->and( HighlightController::isHighlightable( 'artikel' ) )->toBeTrue()
		->and( HighlightController::isHighlightable( 'gedig' ) )->toBeFalse()
		->and( HighlightController::isHighlightable( 'post' ) )->toBeFalse();
} );
