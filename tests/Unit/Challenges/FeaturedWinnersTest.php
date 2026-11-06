<?php
/**
 * Unit tests for the Tuis winners section (WP3, E1, D10).
 *
 * Target: {@see \Ink\Challenges\FeaturedWinners}.
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
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://ink.test' . $path );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A full slot view.
 *
 * @param string $title The title.
 * @return array<string, string>
 */
function ink_winner_view( string $title ): array {
	return array(
		'title'      => $title,
		'url'        => 'https://ink.test/' . $title,
		'author'     => 'Skrywer ' . $title,
		'quote'      => "reel een\nreel twee",
		'avatar_url' => 'https://ink.test/a.png',
		'win_label'  => '3de wen',
	);
}

test( 'the block name is the single-source constant', function (): void {
	expect( FeaturedWinners::BLOCK )->toBe( 'ink/wenner-kollig' );
} );

test( 'toHtml collapses with no winner: no band, no placeholder', function (): void {
	expect( FeaturedWinners::toHtml( array() ) )->toBe( '' );
	expect( FeaturedWinners::toHtml( array( 'month' => 'Mei', 'theme' => 'T', 'slots' => array() ) ) )->toBe( '' );
} );

test( 'toHtml shows the four cards in D10 order, whatever order the payload holds', function (): void {
	$html = FeaturedWinners::toHtml(
		array(
			'month' => 'Oktober',
			'theme' => 'Tema',
			'slots' => array(
				'artikel'      => ink_winner_view( 'A' ),
				'silwer-gedig' => ink_winner_view( 'S' ),
				'storie'       => ink_winner_view( 'P' ),
				'goud-gedig'   => ink_winner_view( 'G' ),
			),
		)
	);

	expect( substr_count( $html, '<article' ) )->toBe( 4 );
	expect( strpos( $html, '"G"' ) )->toBeLessThan( strpos( $html, '"P"' ) );
	expect( strpos( $html, '"P"' ) )->toBeLessThan( strpos( $html, '"S"' ) );
	expect( strpos( $html, '"S"' ) )->toBeLessThan( strpos( $html, '"A"' ) );
	expect( $html )->toContain( 'Oktober-uitdaging · "Tema"' );
	expect( $html )->toContain( 'data-audit-id="challenge-grid"' );
} );

test( 'audit ids pair by type: the poem takes winner-gedig-*, the prose winner-*, the second pair none', function (): void {
	$html = FeaturedWinners::toHtml(
		array(
			'slots' => array(
				'goud-gedig'   => ink_winner_view( 'G' ),
				'storie'       => ink_winner_view( 'P' ),
				'silwer-gedig' => ink_winner_view( 'S' ),
				'artikel'      => ink_winner_view( 'A' ),
			),
		)
	);

	preg_match_all( '/data-audit-id="([^"]+)"/', $html, $ids );

	expect( $ids[1] )->toEqual( array_unique( $ids[1] ) );
	expect( $ids[1] )->toContain( 'winner-gedig-card', 'winner-card', 'winner-gedig-btn-icon', 'winner-btn-icon' );
	expect( substr_count( $html, 'data-audit-id="winner-gedig-card"' ) )->toBe( 1 );

	// The poem's markup holds the G card, not the P card.
	$gedig = substr( $html, (int) strpos( $html, 'winner-gedig-card' ), (int) strpos( $html, 'data-audit-id="winner-card"' ) - (int) strpos( $html, 'winner-gedig-card' ) );

	expect( $gedig )->toContain( '"G"' )->toContain( 'ink-accent-block--poem' )->toContain( 'Lees gedig' );
} );

test( 'primitive classes: card, caps-gold pill, gold button, gold-ring avatar', function (): void {
	$html = FeaturedWinners::toHtml( array( 'slots' => array( 'storie' => ink_winner_view( 'P' ) ) ) );

	expect( $html )->toContain( 'ink-card--winner' )
		->toContain( 'ink-pill ink-pill--caps ink-pill--caps-gold' )
		->toContain( 'ink-btn ink-btn--gold' )
		->toContain( 'ink-avatar ink-avatar--gold-ring' )
		->toContain( 'Lees storie' );
} );

test( 'the Brons Gedig card follows the four, and the grid ends with the link to previous winners', function (): void {
	$html = FeaturedWinners::toHtml(
		array(
			'slots' => array(
				'brons-gedig' => ink_winner_view( 'B' ),
				'storie'      => ink_winner_view( 'P' ),
			),
		)
	);

	expect( substr_count( $html, '<article' ) )->toBe( 2 );
	expect( strpos( $html, '"P"' ) )->toBeLessThan( strpos( $html, '"B"' ) );
	expect( $html )->toContain( 'Brons Gedig' )
		->toContain( 'Lees vorige wenners' )
		->toContain( '/ontdek/?werke_sorteer=wenners' );
	expect( strpos( $html, 'Lees vorige wenners' ) )->toBeGreaterThan( strpos( $html, '"B"' ) );
} );

test( 'slotFor: a Gedig is labelled by its Gradering (Meester is Goud), prose by its type, others have none', function (): void {
	expect( FeaturedWinners::slotFor( 'gedig', 'meester' )['label'] )->toBe( 'Goud Gedig' );
	expect( FeaturedWinners::slotFor( 'gedig', 'silwer' )['label'] )->toBe( 'Silwer Gedig' );
	expect( FeaturedWinners::slotFor( 'gedig', '' )['label'] )->toBe( 'Gedig' );
	expect( FeaturedWinners::slotFor( 'storie', 'goud' )['poem'] )->toBeFalse();
	expect( FeaturedWinners::slotFor( 'skryfwerk', '' ) )->toBeNull();
} );

test( 'a slot with no post, count or avatar drops the button, wins and image', function (): void {
	$html = FeaturedWinners::toHtml( array( 'slots' => array( 'storie' => array( 'title' => 'T', 'author' => 'S' ) ) ) );

	expect( $html )->not->toContain( 'class="ink-btn' )
		->and( $html )->not->toContain( '<img' )
		->and( $html )->not->toContain( 'winner-author-wins' );
} );

test( 'winLabel: 3de wen, 1ste, 8ste, 20ste; nothing for no wins', function (): void {
	expect( FeaturedWinners::winLabel( 0 ) )->toBe( '' );
	expect( FeaturedWinners::winLabel( 1 ) )->toBe( '1ste wen' );
	expect( FeaturedWinners::winLabel( 3 ) )->toBe( '3de wen' );
	expect( FeaturedWinners::winLabel( 8 ) )->toBe( '8ste wen' );
	expect( FeaturedWinners::winLabel( 19 ) )->toBe( '19de wen' );
	expect( FeaturedWinners::winLabel( 20 ) )->toBe( '20ste wen' );
} );
