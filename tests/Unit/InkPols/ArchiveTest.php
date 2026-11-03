<?php
/**
 * Unit tests for the InkPols archive (N2, `04-inkpols` §4, §5) and the shared
 * current-issue renderer (§3): {@see \Ink\InkPols\Archive}, {@see \Ink\InkPols\CurrentIssue}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\Content\FieldSets;
use Ink\InkPols\Api;
use Ink\InkPols\Archive;
use Ink\InkPols\CurrentIssue;
use Ink\InkPols\Issue;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'home_url' )->returnArg( 1 );
	Functions\when( 'get_permalink' )->alias( static fn ( int $id ): string => '/inkpols/' . $id );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( '/cover.jpg' );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A full issue read-model.
 */
function ink_inkpols_full_issue( int $id, string $date ): Issue {
	return new Issue(
		$id,
		'Naam ' . $id,
		$date,
		5,
		6,
		'Beskrywing ' . $id,
		'Titel ' . $id,
		array(
			1 => array( 'title' => 'T1', 'writer' => 'W1', 'tipe' => 'Onderhoud' ),
			2 => array( 'title' => 'T2', 'writer' => 'W2', 'tipe' => 'Resensie' ),
			3 => array( 'title' => 'T3', 'writer' => 'W3', 'tipe' => 'Gedig' ),
		)
	);
}

test( 'MAX_ISSUES is a sane defensive bound (not an unbounded -1)', function (): void {
	expect( Archive::MAX_ISSUES )->toBeGreaterThan( 0 );
} );

test( 'the archive context: ids, a linked cover, copy debt and no download', function (): void {
	$html = CurrentIssue::toHtml( ink_inkpols_full_issue( 1, '2026-07-01' ), 'archive' );

	foreach ( array( 'magazine-current', 'magazine-current-cover-link', 'magazine-current-cover', 'magazine-current-body', 'magazine-current-eyebrow', 'magazine-current-h2', 'magazine-current-text', 'magazine-current-highlights-wrap', 'magazine-current-highlights', 'magazine-current-highlights-2-author', 'magazine-current-actions', 'magazine-current-btn-read', 'magazine-current-btn-read-icon' ) as $id ) {
		expect( $html )->toContain( 'data-audit-id="' . $id . '"' );
	}

	expect( $html )->toContain( '[NEEDS HUMAN AFRIKAANS] INKPOLS-HUIDIGE · Naam 1' );
	expect( $html )->toContain( 'Lees aanlyn' );
	expect( $html )->not->toContain( 'pdf' );
	expect( $html )->not->toContain( 'download' );
	expect( $html )->not->toContain( '<section' );
} );

test( 'the home context: its own ids, an unlinked cover, the label and the past link', function (): void {
	$html = CurrentIssue::toHtml( ink_inkpols_full_issue( 1, '2026-07-01' ), 'home' );

	foreach ( array( 'magazine-grid', 'magazine-cover-col', 'magazine-cover', 'magazine-body', 'magazine-eyebrow', 'magazine-h2', 'magazine-text', 'magazine-inside-label', 'magazine-inside-list', 'magazine-inside-item-0', 'magazine-inside-item-2-author', 'magazine-actions', 'magazine-btn-read', 'magazine-past-link' ) as $id ) {
		expect( $html )->toContain( 'data-audit-id="' . $id . '"' );
	}

	expect( $html )->toContain( 'ink-cover--home' );
	expect( $html )->toContain( 'InkPols · Naam 1' );
	expect( $html )->toContain( 'In hierdie uitgawe' );
	expect( $html )->toContain( 'Alle uitgawes' );
	expect( $html )->not->toContain( 'magazine-current' );
	expect( $html )->not->toContain( 'download' );
} );

test( 'the list has three items in glans order with their own tipe as the kind label, in all three variants', function (): void {
	$issue = ink_inkpols_full_issue( 1, '2026-07-01' );

	$default = CurrentIssue::listHtml( $issue, 'magazine-current', CurrentIssue::LIST_DEFAULT );
	expect( substr_count( $default, '<li ' ) )->toBe( 3 );
	expect( strpos( $default, 'Onderhoud' ) )->toBeLessThan( strpos( $default, 'Resensie' ) );
	expect( strpos( $default, 'Resensie' ) )->toBeLessThan( strpos( $default, 'Gedig' ) );
	expect( strpos( $default, 'T1' ) )->toBeLessThan( strpos( $default, 'T2' ) );
	expect( strpos( $default, 'T2' ) )->toBeLessThan( strpos( $default, 'T3' ) );
	expect( $default )->toContain( 'data-audit-id="magazine-current-highlights-0-kind">Onderhoud<' );
	expect( $default )->toContain( 'data-audit-id="magazine-current-highlights-2-kind">Gedig<' );

	foreach ( array( CurrentIssue::LIST_HOME, CurrentIssue::LIST_COMPACT ) as $variant ) {
		$other = CurrentIssue::listHtml( $issue, 'magazine-inside', $variant );
		expect( strpos( $other, 'Onderhoud' ) )->toBeLessThan( strpos( $other, 'Resensie' ) );
	}

	// A different tipe shows as typed: nothing maps it to a fixed Gedig/Storie/Artikel.
	$other = new Issue( 2, 'N', '2026-01-01', 0, 0, '', 'K', array( 1 => array( 'title' => 'X', 'writer' => 'Y', 'tipe' => 'Fotoverhaal' ) ) );
	expect( CurrentIssue::listHtml( $other, 'p', CurrentIssue::LIST_DEFAULT ) )->toContain( '>Fotoverhaal<' );

	expect( CurrentIssue::listHtml( $issue, 'magazine-inside', CurrentIssue::LIST_HOME ) )->toContain( 'ink-inkpols__lys--home' );
	expect( CurrentIssue::listHtml( $issue, 'magazine-past-0', CurrentIssue::LIST_COMPACT ) )->toContain( 'ink-inkpols__lys--compact' );
	expect( CurrentIssue::listHtml( $issue, '', CurrentIssue::LIST_COMPACT ) )->not->toContain( 'data-audit-id' );
} );

test( 'the production path: Api sorts by datum, so an older issue published late never becomes current', function (): void {
	$sorted = Api::newestFirst( array( ink_inkpols_full_issue( 1, '2025-11-01' ), ink_inkpols_full_issue( 2, '2026-07-01' ) ) );

	expect( $sorted[0]->postId )->toBe( 2 );
	expect( Archive::toHtml( $sorted ) )->toContain( 'INKPOLS-HUIDIGE · Naam 2' );
} );

test( 'one issue: the feature shows and past issues are hidden', function (): void {
	$html = Archive::toHtml( array( ink_inkpols_full_issue( 1, '2026-07-01' ) ) );

	expect( $html )->toContain( 'magazine-current' );
	expect( $html )->not->toContain( 'magazine-past' );
} );

test( 'no issue: the hero text and the empty state, nothing else', function (): void {
	$html = Archive::toHtml( array() );

	expect( $html )->toContain( 'Die Argief' );
	expect( $html )->toContain( 'Al ons uitgawes, gratis beskikbaar om aanlyn te lees' );
	expect( $html )->toContain( 'ink-card--empty' );
	expect( $html )->not->toContain( 'magazine-current' );
	expect( $html )->not->toContain( 'magazine-past' );
} );

test( 'year chips: Alles active first, years newest first, ids by position, later chips without', function (): void {
	$html = Archive::chips(
		array(
			ink_inkpols_full_issue( 1, '2024-01-01' ),
			ink_inkpols_full_issue( 2, '2026-01-01' ),
			ink_inkpols_full_issue( 3, '2025-01-01' ),
		)
	);

	expect( $html )->toContain( 'aria-pressed="true" data-jaar="alles" data-audit-id="magazine-past-filter-all">Alles' );
	expect( $html )->toContain( 'data-jaar="2026" data-audit-id="magazine-past-filter-2025">2026' );
	expect( $html )->toContain( 'data-jaar="2025" data-audit-id="magazine-past-filter-2024">2025' );
	expect( $html )->toContain( 'data-jaar="2024">2024' );
} );

test( 'past issues: all but the newest, the sixth without ids, no download, every card filterable', function (): void {
	$issues = array();

	foreach ( range( 1, 7 ) as $n ) {
		$issues[] = ink_inkpols_full_issue( $n, '2026-0' . $n . '-01' );
	}

	$html = Archive::toHtml( $issues );

	expect( $html )->toContain( 'data-audit-id="magazine-past-4"' );
	expect( $html )->not->toContain( 'magazine-past-5' );
	expect( substr_count( $html, '<article' ) )->toBe( 6 );
	expect( substr_count( $html, 'data-jaar="2026"' ) )->toBe( 7 );
	expect( $html )->toContain( 'ink-btn ink-btn--sm ink-btn--primary' );
	expect( $html )->not->toContain( 'btn-pdf' );
} );

test( 'a card without a cover has no broken image', function (): void {
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

	expect( Archive::card( ink_inkpols_full_issue( 1, '2026-07-01' ) ) )->not->toContain( '<img' );
} );

test( 'no hardcoded Gedig/Storie/Artikel label is left in the InkPols renderers or the model', function (): void {
	$dir = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/InkPols';

	foreach ( array( 'Issue.php', 'CurrentIssue.php', 'SingleIssue.php', 'Archive.php', 'HomeSection.php', 'PublishGuard.php' ) as $file ) {
		$code = (string) file_get_contents( $dir . '/' . $file );

		expect( preg_match( "/Terms::label\(\s*['\"](gedig|storie|artikel)['\"]|'(gedig|storie|artikel)'\s*=>|INKPOLS_(GEDIG|STORIE|ARTIKEL)/i", $code ) )->toBe( 0, $file );
	}

	// The glans fields' own labels do not name a kind either.
	foreach ( FieldSets::glansKeys() as $keys ) {
		foreach ( $keys as $key ) {
			expect( FieldSets::labelFor( $key ) )->not->toMatch( '/gedig|storie|artikel/i' );
		}
	}
} );
