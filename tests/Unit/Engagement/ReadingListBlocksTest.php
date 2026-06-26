<?php
/**
 * Unit tests for the leeslys blocks (Story 7.7, FR-29).
 *
 * Pure `toHtml` of the save toggle (server-rendered saved state) and the profile
 * list. `__`/`esc_*` mocked as identity so assertions are about OUR structure.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReadingList;
use Ink\Engagement\ReadingListToggle;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the toggle reflects the unsaved state', function (): void {
	$html = ReadingListToggle::toHtml( 42, false );

	expect( $html )->toContain( 'data-ink-post="42"' );
	expect( $html )->toContain( 'aria-pressed="false"' );
	expect( $html )->not->toContain( 'is-saved' );
	expect( $html )->toContain( 'Leeslys' ); // label from Terms
} );

test( 'the toggle reflects the saved state (server-rendered, no flash)', function (): void {
	$html = ReadingListToggle::toHtml( 42, true );

	expect( $html )->toContain( 'is-saved' );
	expect( $html )->toContain( 'aria-pressed="true"' );
} );

test( 'the profile list renders the heading and a card per saved work', function (): void {
	$cards = array(
		array( 'title' => 'Herfsblare', 'permalink' => '/gedig/herfsblare', 'type' => 'gedig' ),
	);

	$html = ReadingList::toHtml( $cards );

	expect( $html )->toContain( 'Leeslys' );        // heading from Terms
	expect( $html )->toContain( 'Herfsblare' );
	expect( $html )->toContain( '/gedig/herfsblare' );
} );

test( 'the profile list renders the heading gracefully when empty', function (): void {
	$html = ReadingList::toHtml( array() );

	expect( $html )->toContain( 'ink-leeslys' );
	expect( $html )->toContain( 'Leeslys' );
	expect( $html )->not->toContain( 'ink-leeslys__item' );
} );
