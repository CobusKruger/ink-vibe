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
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n, $d = 0 ): string => number_format( (float) $n, (int) $d ) );
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://nuwe-ink.local' . $path );
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
} );

test( 'toHtml renders the pinned works (best work first) when the writer has pins', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'pinned'    => array(
				array( 'title' => 'Vlerke', 'permalink' => '/vlerke', 'type' => 'gedig' ),
			),
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__vasgespel' );
	expect( $html )->toContain( 'Vlerke' );
	expect( $html )->toContain( '/vlerke' );
} );

test( 'toHtml renders no pinned-works heading when there are no pins', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'pinned'    => array(),
		)
	);

	expect( $html )->not->toContain( 'ink-skrywerprofiel__vasgespel-titel' );
} );

test( 'toHtml renders the Lesergradering aggregate + approved reviews when present', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'aggregate' => array( 'count' => 2, 'average' => 4.5 ),
			'reviews'   => array(
				array( 'user_id' => 7, 'score' => 5, 'resensie' => 'Pragtig geskryf.' ),
			),
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__lesergradering' );
	expect( $html )->toContain( '4.5' );
	expect( $html )->toContain( 'leseroordele' ); // plural count label
	expect( $html )->toContain( 'Pragtig geskryf.' );
} );

test( 'toHtml renders the empty Lesergradering state when nothing is approved (held pre-18.4)', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'aggregate' => array( 'count' => 0, 'average' => 0.0 ),
			'reviews'   => array(),
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__lesergradering-leeg' );
	expect( $html )->not->toContain( 'ink-skrywerprofiel__oordele' );
} );

test( 'toHtml renders the cover image only when set, with the has-omslag modifier', function (): void {
	$withCover = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'cover'     => 'https://example.test/omslag.jpg',
		)
	);

	expect( $withCover )->toContain( 'has-omslag' );
	expect( $withCover )->toContain( 'ink-skrywerprofiel__omslag' );
	expect( $withCover )->toContain( 'https://example.test/omslag.jpg' );

	$withoutCover = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'cover'     => '',
		)
	);

	expect( $withoutCover )->not->toContain( 'has-omslag' );
	expect( $withoutCover )->not->toContain( 'ink-skrywerprofiel__omslag' );
} );

test( 'toHtml renders genre pills, joined date and the stats strip from real data', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'joined'    => 'Aangesluit April 2024',
			'genres'    => array( 'Gedig', 'Storie' ),
			'works'     => array(
				'total' => 5,
				'items' => array(
					array( 'label' => 'Storie', 'count' => 3 ),
					array( 'label' => 'Gedig', 'count' => 2 ),
				),
			),
			'hartjies'  => 120,
			'aggregate' => array( 'count' => 4, 'average' => 4.5 ),
		)
	);

	expect( $html )->toContain( 'Aangesluit April 2024' );
	expect( $html )->toContain( 'ink-skrywerprofiel__genre' );
	expect( $html )->toContain( 'Gedig' );
	expect( $html )->toContain( 'Storie' );
	expect( $html )->toContain( 'ink-skrywerprofiel__statistieke' );
	expect( $html )->toContain( '120' ); // hartjies total
} );

test( 'toHtml renders the accomplishments rail from real Gradering-history rows', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'            => 'Anja Brand',
			'bio'             => '',
			'avatar'          => '',
			'badge'           => '',
			'volgeling'       => '0 volgelinge',
			'volg'            => '',
			'accomplishments' => array(
				array( 'label' => 'Silwer', 'detail' => '12 Januarie 2026' ),
			),
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__prestasies-lys' );
	expect( $html )->toContain( 'ink-skrywerprofiel__prestasie' );
	expect( $html )->toContain( 'Silwer' );
	expect( $html )->toContain( '12 Januarie 2026' );
} );

test( 'toHtml keeps the empty Prestasies shell when there is no Gradering history', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__prestasies-titel' );
	expect( $html )->not->toContain( 'ink-skrywerprofiel__prestasies-lys' );
} );

test( 'toHtml renders each pinned card\'s excerpt, age and engagement counts', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'pinned'    => array(
				array(
					'title'        => 'Vlerke',
					'permalink'    => '/vlerke',
					'type'         => 'gedig',
					'excerpt'      => 'n Gedig oor vlug.',
					'daysAgo'      => '3 dae gelede',
					'hartjies'     => 42,
					'hartjieLabel' => '42 hartjies',
					'gemeenskap'   => 7,
				),
			),
		)
	);

	expect( $html )->toContain( 'n Gedig oor vlug.' );
	expect( $html )->toContain( '3 dae gelede' );
	expect( $html )->toContain( '42' );
	expect( $html )->toContain( 'ink-skrywerprofiel__vasgespel-tellings' );
	expect( $html )->toContain( 'Sien alle werke' );
} );

test( 'toHtml renders the Deel (share) button only when a shareUrl is present', function (): void {
	$withShare = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'shareUrl'  => '/skrywer/anja-brand',
		)
	);

	expect( $withShare )->toContain( 'ink-skrywerprofiel__deel' );
	expect( $withShare )->toContain( '/skrywer/anja-brand' );

	$withoutShare = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'shareUrl'  => '',
		)
	);

	expect( $withoutShare )->not->toContain( 'ink-skrywerprofiel__deel' );
} );

test( 'toHtml renders the closing follow CTA with the writer\'s first name', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '<button class="ink-volg-knoppie">Volg</button>',
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__cta' );
	expect( $html )->toContain( 'Anja' ); // first name only, matching the ratified copy
	expect( $html )->toContain( 'Ontdek meer skrywers' );
} );

test( 'toHtml renders the "Oor [naam]" heading above the bio when a bio is present', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => "'n Digter uit die Karoo.",
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
		)
	);

	expect( $html )->toContain( 'ink-skrywerprofiel__oor-titel' );
	expect( $html )->toContain( 'Oor Anja' );
} );

test( 'toHtml renders a 4-full/1-half/0-empty star row for a 4.8 average (matches the Writer.tsx reference rounding rule)', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'aggregate' => array( 'count' => 312, 'average' => 4.8 ),
		)
	);

	expect( substr_count( $html, 'ink-skrywerprofiel__ster is-vol' ) )->toBe( 4 );
	expect( $html )->toContain( 'ink-skrywerprofiel__ster is-half' );
	expect( $html )->not->toContain( 'ink-skrywerprofiel__ster is-leeg' );
} );

test( 'toHtml renders a 4-full/0-half/1-empty star row for a 4.4 average (below the >= 0.5 half threshold)', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'aggregate' => array( 'count' => 10, 'average' => 4.4 ),
		)
	);

	expect( substr_count( $html, 'ink-skrywerprofiel__ster is-vol' ) )->toBe( 4 );
	expect( $html )->not->toContain( 'ink-skrywerprofiel__ster is-half' );
	expect( substr_count( $html, 'ink-skrywerprofiel__ster is-leeg' ) )->toBe( 1 );
} );

test( 'toHtml renders a 3-full/1-half/1-empty star row for a 3.5 average (exact half boundary)', function (): void {
	$html = SkrywerProfiel::toHtml(
		array(
			'name'      => 'Anja Brand',
			'bio'       => '',
			'avatar'    => '',
			'badge'     => '',
			'volgeling' => '0 volgelinge',
			'volg'      => '',
			'aggregate' => array( 'count' => 10, 'average' => 3.5 ),
		)
	);

	expect( substr_count( $html, 'ink-skrywerprofiel__ster is-vol' ) )->toBe( 3 );
	expect( $html )->toContain( 'ink-skrywerprofiel__ster is-half' );
	expect( substr_count( $html, 'ink-skrywerprofiel__ster is-leeg' ) )->toBe( 1 );
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
