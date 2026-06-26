<?php
/**
 * Unit tests for the folded search index assembly (Story 8.4, FR-35).
 *
 * Target: {@see \Ink\Discovery\SearchIndex} pure assembly — the work index
 * (title + tag-stripped body) and the skrywer index (name + bio + genre labels),
 * both folded for accent-insensitive matching.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\SearchIndex;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'worksIndexFor folds the title and the tag-stripped body', function (): void {
	Functions\when( 'wp_strip_all_tags' )->alias( static fn ( string $html ): string => strip_tags( $html ) );

	$index = SearchIndex::worksIndexFor( 'Die Reën', '<p>val <strong>sag</strong></p>' );

	expect( $index )->toBe( 'die reen val sag' ); // tags stripped, folded, lowercased
} );

test( 'skrywerIndexValue folds the name, bio and genre labels together', function (): void {
	$index = SearchIndex::skrywerIndexValue( 'Anna Visser', 'Digter uit die Karoo', array( 'Digkuns', 'Prosa' ) );

	expect( $index )->toBe( 'anna visser digter uit die karoo digkuns prosa' );
} );

test( 'an accented work indexes to its folded (searchable) form', function (): void {
	Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );

	// A reader searching "more" must hit a work titled "Môre".
	expect( SearchIndex::worksIndexFor( 'Môre', '' ) )->toContain( 'more' );
} );
