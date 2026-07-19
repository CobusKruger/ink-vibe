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

test( 'toHtml renders the announcement linked + winner CARDS in algehele-wenner-first order', function (): void {
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

	// The upgraded DOM emits card articles, not a flat <ul>/<li> list (§5).
	expect( $html )->toContain( 'ink-wenner-kollig__kaart' );
	expect( $html )->toContain( '<article' );
	expect( $html )->not->toContain( 'ink-wenner-kollig__lys' );

	// Rank is conveyed by TEXT (the placement label), not colour alone (a11y).
	expect( $html )->toContain( 'ink-wenner-kollig__rang-teks' );

	// Each work title is an h3 linked to its permalink.
	expect( $html )->toContain( '<h3 class="ink-wenner-kollig__werk">' );

	// Algehele wenner's work appears before the ordinary wenner's work.
	expect( strpos( $html, 'Algehele werk' ) )->toBeLessThan( strpos( $html, 'Tweede werk' ) );

	// The algehele wenner card carries its distinguishing modifier + the gradient hook.
	expect( $html )->toContain( 'ink-wenner-kollig__kaart--algehele' );
	expect( $html )->toContain( 'ink-wenner-kollig__kaart--goud-gradient' );

	// Each placed work carries a "Lees die volledige storie" read-more link (ui-copy 83).
	expect( $html )->toContain( 'Lees die volledige storie' );
} );

test( 'toHtml renders the optional card fields (avatar with alt, quote, author) when the seam supplies them', function (): void {
	$html = FeaturedWinners::toHtml(
		array(
			'title'   => 'Desember-uitslae',
			'url'     => 'https://ink.test/w/dec',
			'winners' => array(
				array(
					'id'         => 10,
					'rank'       => 1,
					'title'      => 'Die laaste lig',
					'url'        => 'https://ink.test/w/1',
					'month'      => 'Desember',
					'author'     => 'Sarah Mitchell',
					'quote'      => 'Die kers flikker teen die ruit...',
					'avatar_url' => 'https://ink.test/avatar.jpg',
					'avatar_alt' => 'Sarah Mitchell',
					'win_label'  => '3de wen',
				),
			),
		)
	);

	// Quote is a blockquote; author + avatar (with alt) + win_label all render.
	expect( $html )->toContain( '<blockquote class="ink-wenner-kollig__aanhaling">' );
	expect( $html )->toContain( 'Die kers flikker' );
	expect( $html )->toContain( 'ink-wenner-kollig__foto' );
	expect( $html )->toContain( 'alt="Sarah Mitchell"' );
	expect( $html )->toContain( 'Sarah Mitchell' );
	expect( $html )->toContain( '3de wen' );

	// The eyebrow joins month + "algehele wenner" with a SPACE for the algehele wenner.
	expect( $html )->toContain( 'Desember algehele wenner' );
} );

test( 'toHtml OMITS the optional card sub-parts gracefully when the seam supplies only id/rank/title/url', function (): void {
	$html = FeaturedWinners::toHtml(
		array(
			'title'   => 'Mei-uitslae',
			'url'     => 'https://ink.test/w/mei',
			'winners' => array(
				array( 'id' => 10, 'rank' => 1, 'title' => 'Werk sonder besonderhede', 'url' => 'https://ink.test/w/1' ),
			),
		)
	);

	// Non-vacuous: the card itself renders...
	expect( $html )->toContain( 'ink-wenner-kollig__kaart' );
	expect( $html )->toContain( 'Werk sonder besonderhede' );

	// ...but no quote / author block / avatar chrome when the seam did not supply them.
	expect( $html )->not->toContain( 'ink-wenner-kollig__aanhaling' );
	expect( $html )->not->toContain( 'ink-wenner-kollig__outeur' );
	expect( $html )->not->toContain( 'ink-wenner-kollig__foto' );
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

// --- orderFeed(): the FEED keeps every winner, algehele wenner(s) first (Story 12A.7) ---

test( 'orderFeed puts the algehele wenner first, ahead of ordinary wenners', function (): void {
	$feed = FeaturedWinners::orderFeed(
		array(
			array( 'id' => 30, 'rank' => 3, 'title' => 'Derde' ),
			array( 'id' => 10, 'rank' => 1, 'title' => 'Algehele' ),
			array( 'id' => 20, 'rank' => 2, 'title' => 'Tweede' ),
		)
	);

	expect( array_column( $feed, 'rank' ) )->toBe( array( 1, 2, 3 ) );
	expect( $feed[0]['is_algehele_wenner'] )->toBeTrue();
} );

test( 'orderFeed PRESERVES multiple algehele wenners (one per category pool) — unlike order()', function (): void {
	// Two rank-1s = the algehele wenner of two different (Gradering × category) pools.
	$winners = array(
		array( 'id' => 12, 'rank' => 1, 'title' => 'Gedig-wenner' ),
		array( 'id' => 11, 'rank' => 1, 'title' => 'Storie-wenner' ),
		array( 'id' => 20, 'rank' => 2, 'title' => 'Tweede' ),
	);

	// Non-vacuous: order() WOULD collapse the two rank-1s to one...
	expect( FeaturedWinners::order( $winners ) )->toHaveCount( 2 );

	// ...but the FEED keeps both algehele wenners, ordered (lowest id first), then the wenner.
	$feed = FeaturedWinners::orderFeed( $winners );
	expect( $feed )->toHaveCount( 3 );
	expect( array_column( $feed, 'id' ) )->toBe( array( 11, 12, 20 ) );
	$rank_ones = array_filter( $feed, static fn ( array $r ): bool => 1 === $r['rank'] );
	expect( $rank_ones )->toHaveCount( 2 );
} );

test( 'orderFeed drops rows with no id or a non-placement rank', function (): void {
	$feed = FeaturedWinners::orderFeed(
		array(
			array( 'id' => 0, 'rank' => 1 ),
			array( 'id' => 7, 'rank' => 0 ),
			array( 'id' => 9, 'rank' => 1 ),
		)
	);

	expect( $feed )->toHaveCount( 1 );
	expect( $feed[0]['id'] )->toBe( 9 );
} );
