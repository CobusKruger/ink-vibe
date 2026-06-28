<?php
/**
 * Unit tests for the home winners featured slot + ordering (Story 15.6, FR-50-R2).
 *
 * Target: {@see \Ink\Challenges\FeaturedWinners} — the `ink/wenner-kollig` home block.
 * We test INK-owned OUTCOMES: the pure {@see FeaturedWinners::order()} (algehele wenner
 * first) and {@see FeaturedWinners::toHtml()} (collapses with no announcement; renders
 * the announcement + ordered winners otherwise). Brain-Monkey-mocked — no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\FeaturedWinners;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the block name is the single-source constant', function (): void {
	expect( FeaturedWinners::BLOCK )->toBe( 'ink/wenner-kollig' );
} );

// --- order(): algehele wenner first ---

test( 'order puts the algehele wenner (rank 1) first, ahead of ordinary wenners', function (): void {
	$ordered = FeaturedWinners::order(
		array(
			array( 'id' => 30, 'rank' => 3, 'title' => 'Derde' ),
			array( 'id' => 10, 'rank' => 1, 'title' => 'Algehele' ),
			array( 'id' => 20, 'rank' => 2, 'title' => 'Tweede' ),
		)
	);

	expect( array_column( $ordered, 'rank' ) )->toBe( array( 1, 2, 3 ) );
	expect( $ordered[0]['is_algehele_wenner'] )->toBeTrue();
	expect( $ordered[1]['is_algehele_wenner'] )->toBeFalse();
} );

test( 'order resolves a same-rank tie deterministically to the lowest id (one per rank)', function (): void {
	$ordered = FeaturedWinners::order(
		array(
			array( 'id' => 55, 'rank' => 2, 'title' => 'B' ),
			array( 'id' => 11, 'rank' => 2, 'title' => 'A' ),
		)
	);

	// One-per-rank: the lowest-id placement wins the rank slot deterministically.
	expect( array_column( $ordered, 'id' ) )->toBe( array( 11 ) );
} );

test( 'order drops rows with no id or a non-placement rank', function (): void {
	$ordered = FeaturedWinners::order(
		array(
			array( 'id' => 0, 'rank' => 1 ),   // no id
			array( 'id' => 7, 'rank' => 0 ),   // not placed
			array( 'id' => 9, 'rank' => 1 ),   // valid
		)
	);

	expect( $ordered )->toHaveCount( 1 );
	expect( $ordered[0]['id'] )->toBe( 9 );
} );

// --- toHtml(): collapses when empty (the forward-compatible 12A invariant) ---

test( 'toHtml COLLAPSES to empty markup when there is no announcement (12A not yet supplying)', function (): void {
	expect( FeaturedWinners::toHtml( array() ) )->toBe( '' );
	expect( FeaturedWinners::toHtml( array( 'title' => '   ' ) ) )->toBe( '' );
} );

// --- toHtml(): renders the announcement + ordered winners when populated ---

test( 'toHtml renders the announcement linked + winners in algehele-wenner-first order', function (): void {
	$html = FeaturedWinners::toHtml(
		array(
			'title'   => 'Junie-uitslae',
			'url'     => 'https://ink.test/wenneraankondiging/junie',
			'winners' => array(
				array( 'id' => 20, 'rank' => 2, 'title' => 'Tweede werk', 'url' => 'https://ink.test/w/2' ),
				array( 'id' => 10, 'rank' => 1, 'title' => 'Algehele werk', 'url' => 'https://ink.test/w/1' ),
			),
		)
	);

	// Non-vacuous: the announcement heading links to its permalink.
	expect( $html )->toContain( 'ink-wenner-kollig' );
	expect( $html )->toContain( 'Junie-uitslae' );
	expect( $html )->toContain( 'href="https://ink.test/wenneraankondiging/junie"' );

	// Algehele wenner's work appears before the ordinary wenner's work.
	expect( strpos( $html, 'Algehele werk' ) )->toBeLessThan( strpos( $html, 'Tweede werk' ) );

	// The algehele wenner item carries its distinguishing modifier class.
	expect( $html )->toContain( 'ink-wenner-kollig__item--algehele' );

	// Each placed work carries a "Lees die volledige storie" read-more link (ui-copy 83).
	expect( $html )->toContain( 'Lees die volledige storie' );
} );

test( 'order collapses duplicate ranks so there is never a second algehele wenner', function (): void {
	$ordered = FeaturedWinners::order(
		array(
			array( 'id' => 11, 'rank' => 1, 'title' => 'Eerste-een' ),
			array( 'id' => 12, 'rank' => 1, 'title' => 'Eerste-twee' ),
			array( 'id' => 20, 'rank' => 2, 'title' => 'Tweede' ),
		)
	);

	// Only one rank-1 survives (the lowest id), so the slot can never show two
	// algehele wenners even if 12A ingestion feeds a dirty payload.
	$rank_ones = array_filter( $ordered, static fn ( array $r ): bool => 1 === $r['rank'] );
	expect( $rank_ones )->toHaveCount( 1 );
	expect( $ordered[0]['id'] )->toBe( 11 );
	expect( $ordered )->toHaveCount( 2 );
} );
