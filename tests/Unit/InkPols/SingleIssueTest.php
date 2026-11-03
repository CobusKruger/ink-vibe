<?php
/**
 * Unit tests for the InkPols issue page (N3, `04-inkpols` §4, §6): {@see \Ink\InkPols\SingleIssue}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Issue;
use Ink\InkPols\SingleIssue;
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
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An issue read-model with one featured item.
 */
function ink_inkpols_single_issue( int $id ): Issue {
	return new Issue( $id, 'Naam ' . $id, '2026-07-01', 0, 0, '', 'Titel ' . $id, array( 1 => array( 'title' => 'S', 'writer' => 'SS', 'tipe' => 'Gedig' ) ) );
}

test( 'the bar has naam, titel, the debt back link and no download', function (): void {
	$html = SingleIssue::barHtml( ink_inkpols_single_issue( 1 ) );

	expect( $html )->toContain( 'data-audit-id="magazine-issue-h1">Titel 1' );
	expect( $html )->toContain( 'data-audit-id="magazine-issue-eyebrow">Naam 1' );
	expect( $html )->toContain( '[NEEDS HUMAN AFRIKAANS] INKPOLS-TERUG' );
	expect( $html )->toContain( 'href="/inkpols/"' );
	expect( $html )->not->toContain( 'btn-pdf' );
} );

test( 'a middle issue links Vorige to the older and Volgende to the newer', function (): void {
	$html = SingleIssue::contentsHtml( ink_inkpols_single_issue( 2 ), ink_inkpols_single_issue( 1 ), ink_inkpols_single_issue( 3 ) );

	expect( $html )->toContain( 'data-audit-id="magazine-issue-nav-older" href="/inkpols/1"' );
	expect( $html )->toContain( 'data-audit-id="magazine-issue-nav-newer" href="/inkpols/3"' );
	expect( $html )->toContain( 'Vorige' );
	expect( $html )->toContain( 'Volgende' );
	expect( $html )->not->toContain( 'nav-spacer' );
	expect( $html )->toContain( 'In hierdie uitgawe' );
	expect( $html )->toContain( 'ink-accent-block--top' );
} );

test( 'the oldest holds the left slot with the spacer; the newest has no Volgende', function (): void {
	expect( SingleIssue::contentsHtml( ink_inkpols_single_issue( 1 ), null, ink_inkpols_single_issue( 2 ) ) )->toContain( 'magazine-issue-nav-spacer' );
	expect( SingleIssue::contentsHtml( ink_inkpols_single_issue( 2 ), ink_inkpols_single_issue( 1 ), null ) )->not->toContain( 'nav-newer' );
} );

test( 'neighbours reads a newest-first list', function (): void {
	$list = array( ink_inkpols_single_issue( 3 ), ink_inkpols_single_issue( 2 ), ink_inkpols_single_issue( 1 ) );

	$near = SingleIssue::neighbours( $list, 2 );
	expect( $near['older']?->postId )->toBe( 1 );
	expect( $near['newer']?->postId )->toBe( 3 );

	expect( SingleIssue::neighbours( $list, 3 )['newer'] )->toBeNull();
	expect( SingleIssue::neighbours( $list, 1 )['older'] )->toBeNull();
	expect( SingleIssue::neighbours( $list, 9 ) )->toBe( array( 'older' => null, 'newer' => null ) );
} );

test( 'the issue page lists the glans items in order, each with its own tipe', function (): void {
	$issue = new Issue(
		1,
		'Naam',
		'2026-07-01',
		0,
		0,
		'',
		'Titel',
		array(
			1 => array( 'title' => 'Een', 'writer' => 'A', 'tipe' => 'Onderhoud' ),
			2 => array( 'title' => 'Twee', 'writer' => 'B', 'tipe' => 'Fotoverhaal' ),
			3 => array( 'title' => 'Drie', 'writer' => 'C', 'tipe' => 'Resensie' ),
		)
	);

	$html = SingleIssue::contentsHtml( $issue, null, null );

	expect( $html )->toContain( 'data-audit-id="magazine-issue-item-0-kind">Onderhoud<' );
	expect( $html )->toContain( 'data-audit-id="magazine-issue-item-1-kind">Fotoverhaal<' );
	expect( strpos( $html, 'Een' ) )->toBeLessThan( strpos( $html, 'Twee' ) );
	expect( strpos( $html, 'Twee' ) )->toBeLessThan( strpos( $html, 'Drie' ) );
} );
