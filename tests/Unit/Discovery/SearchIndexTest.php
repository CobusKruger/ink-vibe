<?php
/**
 * Unit tests for the search index assembly (FR-35).
 *
 * Target: {@see \Ink\Discovery\SearchIndex} pure assembly — title key, body key and the
 * writer name index, all {@see \Ink\Migration\TextKey} keys (no case, accents, punctuation
 * or whitespace).
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

test( 'titleKey drops case, accents, spacing and punctuation', function (): void {
	expect( SearchIndex::titleKey( 'Die  Reën, ’n Môre!' ) )->toBe( 'die reen n more' );
} );

test( 'bodyKey strips tags and ignores line endings and spacing', function (): void {
	Functions\when( 'wp_strip_all_tags' )->alias( static fn ( string $html ): string => strip_tags( $html ) );

	expect( SearchIndex::bodyKey( "<p>val <strong>sag</strong></p>\r\n<p>neer</p>" ) )->toBe( 'val sag neer' );
	expect( SearchIndex::bodyKey( "val sag\nneer" ) )->toBe( SearchIndex::bodyKey( "val\r\nsag neer" ) );
} );

test( 'bodyKey keeps words apart across <br> and paragraphs, and never indexes tags or attributes', function (): void {
	Functions\when( 'wp_strip_all_tags' )->alias( static fn ( string $html ): string => strip_tags( $html ) );

	expect( SearchIndex::bodyKey( 'lyn een<br>lyn twee<br />' ) )->toBe( 'lyn een lyn twee' );
	expect( SearchIndex::bodyKey( '<p class="intro" id="x">hallo</p><a href="http://x.y">link</a>' ) )->toBe( 'hallo link' );
	expect( SearchIndex::bodyKey( 'sag<em>te</em>' ) )->toBe( 'sagte' ); // inline tags don't split a word
} );

test( 'bodyKey decodes entities so &eacute; and é index the same', function (): void {
	Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );

	expect( SearchIndex::bodyKey( 'caf&eacute;&nbsp;bar' ) )->toBe( SearchIndex::bodyKey( 'café bar' ) );
} );

test( 'skrywerIndexValue holds display name, nice name and user name as separate |-wrapped keys', function (): void {
	expect( SearchIndex::skrywerIndexValue( 'Anna Visser', 'anna visser', 'avisser' ) )->toBe( '|anna visser|avisser|' );
	expect( SearchIndex::skrywerIndexValue( 'Anna Visser', 'anna', 'avisser' ) )->toBe( '|anna visser|anna|avisser|' );
} );

test( 'skrywerIndexValue is empty when no name is usable, and carries no bio or genre text', function (): void {
	expect( SearchIndex::skrywerIndexValue( '', '', '' ) )->toBe( '' );
	expect( SearchIndex::skrywerIndexValue( 'Anna', 'anna', 'anna' ) )->not->toContain( 'digkuns' );
} );
