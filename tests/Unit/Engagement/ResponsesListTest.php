<?php
/**
 * Unit tests for the Gemeenskapsreaksie list/form renderer (Story 7.4, FR-27).
 *
 * Target: {@see \Ink\Engagement\ResponsesList::toHtml()} — pure (Terms + escaping
 * only). `__`/`esc_*` are mocked as identity so the assertions are about OUR
 * structure: the count heading, the typed badges, escaped content, and the typed
 * form (the three enum radios + a submit).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ResponsesList;
use Ink\Kernel\ResponseType;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n, $d = 0 ): string => number_format( (float) $n, (int) $d ) );
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );

	if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
		define( 'MINUTE_IN_SECONDS', 60 );
	}
	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders the count heading, typed badges and escaped content', function (): void {
	$responses = array(
		array(
			'id'      => 1,
			'type'    => ResponseType::Lof,
			'content' => 'Pragtige beeldspraak.',
			'author'  => 'Lid Een',
			'date'    => '2026-06-26 10:00:00',
		),
	);

	$html = ResponsesList::toHtml( 42, $responses, 1 );

	expect( $html )->toContain( '1 Gemeenskapsreaksie' );          // singular count heading
	expect( $html )->toContain( 'ink-reaksie--lof' );               // typed badge class
	expect( $html )->toContain( 'Lof' );                            // badge label (from Terms)
	expect( $html )->toContain( 'Pragtige beeldspraak.' );          // escaped content
	expect( $html )->toContain( 'Lid Een' );
} );

test( 'toHtml uses the plural heading for a count other than one', function (): void {
	$html = ResponsesList::toHtml( 42, array(), 0 );

	expect( $html )->toContain( '0 Gemeenskapsreaksies' );
} );

test( 'toHtml renders the typed form with all three response-type radios and a submit', function (): void {
	$html = ResponsesList::toHtml( 42, array(), 0 );

	expect( $html )->toContain( 'data-ink-post="42"' );
	foreach ( ResponseType::values() as $value ) {
		expect( $html )->toContain( 'value="' . $value . '"' );
	}
	expect( $html )->toContain( 'name="ink_reaksie_content"' );
	expect( $html )->toContain( 'Plaas' ); // submit label (from Terms)
} );

test( 'toHtml renders the authored instruction line before the type radios', function (): void {
	$html = ResponsesList::toHtml( 42, array(), 0 );

	expect( $html )->toContain( 'ink-reaksies__intro' );
	expect( $html )->toContain( "Deel 'n deurdagte reaksie" );
	expect( strpos( $html, 'ink-reaksies__intro' ) )->toBeLessThan( strpos( $html, 'ink-reaksies__types' ) );
} );
