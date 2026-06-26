<?php
/**
 * Unit tests for the contextual reading-prompt block (Story 7.5, FR-30).
 *
 * Target: {@see \Ink\Engagement\ContextualPrompts}. `promptsFor`/`toHtml` are
 * pure. The copy is human-authored Afrikaans used verbatim (ui-copy-translations
 * 286–287) — the tests assert those exact strings (so a future edit that silently
 * swaps in invented copy fails) and that the mechanism accepts every bydrae type.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ContextualPrompts;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'promptsFor returns the authored heading + body for every bydrae type', function (): void {
	foreach ( array( 'gedig', 'storie', 'artikel' ) as $type ) {
		$prompt = ContextualPrompts::promptsFor( $type );

		expect( $prompt['heading'] )->toBe( 'Reageer met bedoeling' );
		expect( $prompt['body'] )->toBe( 'Merk \'n sin uit. Los \'n gestruktureerde nota. Sê vir \'n skrywer wat geraak het, in plaas van om verby te blaai.' );
	}
} );

test( 'toHtml renders the heading and body inside the prompt area', function (): void {
	$html = ContextualPrompts::toHtml( ContextualPrompts::promptsFor( 'gedig' ) );

	expect( $html )->toContain( 'ink-leesprompte' );
	expect( $html )->toContain( 'Reageer met bedoeling' );
	expect( $html )->toContain( 'Sê vir \'n skrywer wat geraak het' );
} );
