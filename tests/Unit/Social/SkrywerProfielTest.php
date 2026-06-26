<?php
/**
 * Unit tests for the public Skrywerprofiel block (Story 9.4, FR-40).
 *
 * Target: {@see \Ink\Social\SkrywerProfiel}. The pure `toHtml()` (public card)
 * and the `render()` context gate. The load-bearing assertion: the PUBLIC card
 * renders the gradering badge + volgeling count but NO private read-count /
 * wins-needed surface.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\SkrywerProfiel;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'render returns nothing outside an author (skrywer) archive context', function (): void {
	Functions\when( 'is_author' )->justReturn( false );

	expect( SkrywerProfiel::render() )->toBe( '' );
} );

test( 'toHtml renders the public profile card: name, bio, gradering, volgeling, volg toggle', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => "'n Digter uit die Karoo.",
			'avatar'    => '<img class="avatar" alt="" />',
			'badge'     => '<span class="ink-gradering ink-gradering--goud"><span class="ink-gradering__label">Goud</span></span>',
			'volgeling' => '12 volgelinge',
			'volg'      => '<button class="ink-volg-knoppie">Volg</button>',
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel' );
	expect( $html )->toContain( 'Anja Brand' );
	expect( $html )->toContain( "'n Digter uit die Karoo." );
	expect( $html )->toContain( 'ink-gradering--goud' );   // gradering badge (display)
	expect( $html )->toContain( '12 volgelinge' );          // volgeling count
	expect( $html )->toContain( 'ink-volg-knoppie' );       // Volg toggle
	expect( $html )->toContain( 'ink-skrywerprofiel__vasgespel' ); // reserved 9.5 slot
} );

test( 'the PUBLIC card renders NO private surfaces (no read counts, no wins-needed)', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => 'Bio.',
			'avatar'    => '',
			'badge'     => '<span class="ink-gradering ink-gradering--silwer"></span>',
			'volgeling' => '3 volgelinge',
			'volg'      => '',
		)
	);

	// Non-vacuous: the card DOES carry the public gradering + volgeling...
	expect( $html )->toContain( 'ink-gradering' );
	expect( $html )->toContain( '3 volgelinge' );
	// ...but NOT the private My-Profiel-only surfaces (FR-40 separation).
	expect( $html )->not->toContain( 'wins-needed' );
	expect( $html )->not->toContain( 'leesgetalle' );
	expect( $html )->not->toContain( 'read-count' );
} );
