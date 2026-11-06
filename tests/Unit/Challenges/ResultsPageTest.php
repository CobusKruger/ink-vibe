<?php
/**
 * Unit tests for the results page, banner and reading aside (WP1, WP2).
 *
 * Targets: {@see \Ink\Challenges\ResultsPage}, {@see \Ink\Challenges\ResultsBanner},
 * {@see \Ink\Challenges\ReadingAward}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\ReadingAward;
use Ink\Challenges\ResultsBanner;
use Ink\Challenges\ResultsPage;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( '_n' )->alias( static fn ( string $one, string $many, int $n ): string => 1 === $n ? $one : $many );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'get_avatar_url' )->justReturn( 'https://ink.test/a.png' );
	Functions\when( 'get_permalink' )->alias( static fn ( int $id ): string => 'https://ink.test/' . $id );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A result row.
 *
 * @param array<string, mixed> $over The fields to set.
 * @return array<string, mixed>
 */
function ink_row( array $over ): array {
	return array_merge(
		array(
			'category' => 'gedig',
			'tier'     => 'goud',
			'position' => 1,
			'post_id'  => 5,
			'title'    => 'Titel',
			'author'   => 'Skrywer',
			'user_id'  => 3,
			'excerpt'  => 'Uittreksel',
			'citation' => '',
		),
		$over
	);
}

test( 'the hero states period, theme, date and count, with debt for what has no Afrikaans yet', function (): void {
	$html = ResultsPage::heroHtml( 'Oktober 2026', 'Tema', '4 November 2026', 12 );

	expect( $html )->toContain( 'Uitslae · Oktober 2026-uitdaging' )
		->toContain( '“Tema” — Uitslae' )
		->toContain( 'Aangekondig 4 November 2026 · 12 inskrywings · Gedigte, stories en artikels' )
		->toContain( 'ink-band ink-band--step ink-band--intro' )
		->toContain( 'ink-heading-h1-index' );
} );

test( 'the letter hides with no paragraphs, and only the first two paragraphs carry ids', function (): void {
	expect( ResultsPage::letterHtml( array() ) )->toBe( '' );

	$html = ResultsPage::letterHtml( array( 'een', 'twee', 'drie' ) );

	expect( $html )->toContain( 'results-letter-p-0' )->toContain( 'results-letter-p-1' )->not->toContain( 'results-letter-p-2' );
	expect( $html )->toContain( '>drie</p>' );
} );

test( 'all three tabs always show, the first active, the others hidden; tier headings show when empty', function (): void {
	$html = ResultsPage::podiumHtml( array() );

	expect( substr_count( $html, 'role="tab"' ) )->toBe( 3 );
	expect( substr_count( $html, 'role="tabpanel"' ) )->toBe( 3 );
	expect( substr_count( $html, ' hidden>' ) )->toBe( 2 );
	expect( $html )->toContain( 'aria-selected="true" data-audit-id="results-tab-poetry"' );
	expect( $html )->toContain( 'results-tier-gold-h3' )->toContain( 'results-tier-silver-h3' )->toContain( 'results-tier-bronze-h3' );
	expect( $html )->not->toContain( 'results-podium-gold' );
} );

test( 'the first by position leads and the rest follow, whatever order the rows hold', function (): void {
	$html = ResultsPage::podiumHtml(
		array(
			ink_row( array( 'position' => 3, 'title' => 'Derde' ) ),
			ink_row( array( 'position' => 1, 'title' => 'Eerste' ) ),
			ink_row( array( 'position' => 2, 'title' => 'Tweede' ) ),
		)
	);

	expect( $html )->toContain( 'class="ink-card--award ink-card--award-lead" data-audit-id="results-award-gold-1"' );
	expect( $html )->toContain( 'class="ink-card--award" data-audit-id="results-award-gold-2"' );
	expect( strpos( $html, 'Eerste' ) )->toBeLessThan( strpos( $html, 'results-podium-gold-rest' ) );
	expect( strpos( $html, 'results-podium-gold-rest' ) )->toBeLessThan( strpos( $html, 'Tweede' ) );
	expect( $html )->toContain( 'ink-pill--place-3' );
} );

test( 'slots: a Meester row shows under Goud; Storie and Artikel use their own slots; ids never repeat', function (): void {
	$html = ResultsPage::podiumHtml(
		array(
			ink_row( array( 'tier' => 'meester', 'post_id' => 1 ) ),
			ink_row( array( 'tier' => 'goud', 'post_id' => 2 ) ), // A tie for position 1.
			ink_row( array( 'category' => 'storie', 'post_id' => 3 ) ),
			ink_row( array( 'category' => 'artikel', 'post_id' => 4 ) ),
		)
	);

	preg_match_all( '/data-audit-id="([^"]+)"/', $html, $ids );

	expect( $ids[1] )->toEqual( array_unique( $ids[1] ) );
	expect( $ids[1] )->toContain( 'results-award-gold-1', 'results-award-short-story-1', 'results-award-article-1' );
} );

test( 'a Gedig win with no grade shows below the tiers, with no ids (D13)', function (): void {
	$html = ResultsPage::podiumHtml( array( ink_row( array( 'tier' => '', 'title' => 'Sonder graad' ) ) ) );

	expect( $html )->toContain( 'Sonder graad' );
	expect( strpos( $html, 'results-tier-bronze-h3' ) )->toBeLessThan( strpos( $html, 'Sonder graad' ) );
	expect( $html )->not->toContain( 'results-award-' );
} );

test( 'a win with no post has no button and no placeholder', function (): void {
	$html = ResultsPage::podiumHtml( array( ink_row( array( 'post_id' => 0 ) ) ) );

	expect( $html )->not->toContain( 'results-award-gold-1-soon' )->not->toContain( 'results-award-gold-1-btn' );

	$html = ResultsPage::podiumHtml( array( ink_row( array() ) ) );

	expect( $html )->toContain( 'results-award-gold-1-btn"' )->toContain( 'href="https://ink.test/5"' )->toContain( 'ink-btn ink-btn--sm ink-btn--outline' );
} );

test( 'the citation block is absent with no opted-in citation (D6)', function (): void {
	expect( ResultsPage::podiumHtml( array( ink_row( array() ) ) ) )->not->toContain( 'citation' );

	$html = ResultsPage::podiumHtml( array( ink_row( array( 'citation' => 'Mooi' ) ) ) );

	expect( $html )->toContain( 'ink-accent-block ink-accent-block--citation' )->toContain( 'Mooi' )->toContain( '— Terugvoer van die moderator' );
} );

test( 'mentions: heading and list hide without rows; thanks stay; the button needs an open round', function (): void {
	$bare = ResultsPage::mentionsHtml( array(), '' );

	expect( $bare )->not->toContain( 'results-mentions-h2' )->not->toContain( 'results-mentions-btn' )->toContain( 'results-mentions-thanks' );

	$full = ResultsPage::mentionsHtml( array( array( 'title' => 'T', 'author' => 'S' ) ), 'https://ink.test/oop' );

	expect( $full )->toContain( 'Eervolle vermeldings' )->toContain( '“T”' )->toContain( 'href="https://ink.test/oop"' )->toContain( 'Skryf in' );
} );

test( 'the banner: home has the eyebrow and results-banner ids; the round page neither', function (): void {
	$home = ResultsBanner::toHtml( 'https://ink.test/u/', 'Tema', 'Oktober 2026' );

	expect( $home )->toContain( 'data-audit-id="results-banner"' )
		->toContain( 'Uitslae aangekondig · Oktober 2026' )
		->toContain( 'href="https://ink.test/u/"' )
		->toContain( 'ink-band ink-band--banner' )
		->toContain( 'ink-card--banner' );

	$round = ResultsBanner::toHtml( 'https://ink.test/u/', 'Tema', 'Oktober 2026', ResultsBanner::CONTEXT_ROUND );

	expect( $round )->toContain( 'data-audit-id="round-results-banner"' )
		->not->toContain( 'eyebrow' )
		->not->toContain( '"results-banner' );
} );

test( 'the aside: the link always, citation and attribution only with opt-in (D6)', function (): void {
	$bare = ReadingAward::toHtml( 'https://ink.test/u/', 'Etiket', '' );

	expect( $bare )->toContain( 'data-audit-id="reading-award-link"' )->toContain( 'href="https://ink.test/u/"' )->toContain( 'Etiket' )
		->not->toContain( 'reading-award-citation' );

	$full = ReadingAward::toHtml( 'https://ink.test/u/', 'Etiket', 'Mooi' );

	expect( $full )->toContain( 'data-audit-id="reading-award-citation"' )->toContain( 'data-audit-id="reading-award-citation-by"' )->toContain( 'Mooi' );
} );

test( 'the aside label: placement, type with the Gedig grade, Meester as Goud, and the period', function (): void {
	expect( ReadingAward::label( 1, 'gedig', 'goud', 'Oktober 2026' ) )->toBe( 'Algehele wenner · Goud gedigte · Oktober 2026-uitdaging' );
	expect( ReadingAward::label( 2, 'gedig', 'meester', 'Oktober 2026' ) )->toBe( 'Wenner · Goud gedigte · Oktober 2026-uitdaging' );
	expect( ReadingAward::label( 1, 'storie', 'goud', 'Oktober 2026' ) )->toBe( 'Algehele wenner · Stories · Oktober 2026-uitdaging' );
	expect( ReadingAward::label( 3, 'artikel', '', '' ) )->toBe( 'Wenner · Artikels' );
} );

test( 'production path: render gives nothing for a work with no placement', function (): void {
	Functions\when( 'get_the_ID' )->justReturn( 77 );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	expect( ReadingAward::render() )->toBe( '' );
} );

test( 'production path: render gives nothing when the round cannot be resolved', function (): void {
	Functions\when( 'get_the_ID' )->justReturn( 77 );
	Functions\when( 'get_post_meta' )->justReturn( 1 );
	Functions\when( 'get_the_terms' )->justReturn( false );

	expect( ReadingAward::render() )->toBe( '' );
} );

test( 'production path: the home banner renders nothing with no announcement', function (): void {
	Functions\when( 'get_posts' )->justReturn( array() );

	expect( ResultsBanner::render() )->toBe( '' );
} );

test( 'the page and pattern are code: template, pattern and the four parts', function (): void {
	$theme    = dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/';
	$template = (string) file_get_contents( $theme . 'templates/single-uitdaging-uitslae.html' );
	$pattern  = (string) file_get_contents( $theme . 'patterns/uitslae.php' );

	expect( $template )->toContain( '"slug":"ink-foundation/uitslae"' )->toContain( '"slug":"footer"' );

	foreach ( array( 'kop', 'brief', 'podium', 'vermeldings' ) as $part ) {
		expect( $pattern )->toContain( '<!-- wp:ink/uitslae {"deel":"' . $part . '"} /-->' );
	}
} );

test( 'Tuis order follows the owner\'s ruling, and the old band and demo filter are gone', function (): void {
	$theme = dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/';
	$front = (string) file_get_contents( $theme . 'templates/front-page.html' );
	$order = array( 'ink-foundation/hero', 'ink/uitslae-banier', 'ink/inkpols-tuis', 'ink/wenner-kollig', 'ink-foundation/featured-grid', 'ink-foundation/borg-strook', 'ink-foundation/cta-band' );
	$last  = -1;

	foreach ( $order as $block ) {
		$at = strpos( $front, $block );

		expect( $at )->toBeGreaterThan( $last );
		$last = (int) $at;
	}

	expect( $front )->not->toContain( 'ink-feature-band' );
	expect( (string) file_get_contents( $theme . 'functions.php' ) )->not->toContain( 'ink_foundation_homepage_demo_winner' );
} );

test( 'each reading pattern embeds the aside, before the logged-in hint', function (): void {
	$patterns = dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/patterns/';

	foreach ( array( 'reading-gedig.php', 'reading-storie.php', 'reading-artikel.php' ) as $file ) {
		$src = (string) file_get_contents( $patterns . $file );

		expect( strpos( $src, 'wp:ink/lees-wenner' ) )->toBeLessThan( strpos( $src, 'is_user_logged_in()' ) );
		expect( strpos( $src, 'wp:ink/lees-wenner' ) )->toBeGreaterThan( strpos( $src, 'ink-lees-byline' ) );
	}
} );

test( 'the round page embeds the banner between the hero and the prompt', function (): void {
	$src = (string) file_get_contents( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/patterns/reading-uitdaging.php' );

	expect( strpos( $src, 'wp:ink/uitslae-banier {"konteks":"rondte"}' ) )->toBeGreaterThan( strpos( $src, 'uitdaging-hero' ) );
	expect( strpos( $src, 'wp:ink/uitslae-banier {"konteks":"rondte"}' ) )->toBeLessThan( strpos( $src, 'data-audit-id="uitdaging-prompt"' ) );
} );
