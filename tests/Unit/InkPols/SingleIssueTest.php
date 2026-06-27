<?php
/**
 * Unit tests for the InkPols single-issue metadata block (Story 13.2, FR-57).
 *
 * Target: {@see \Ink\InkPols\SingleIssue}. The pure `metaHtml()` (cover/date/
 * volume/teaser rows, each omitted when absent; '' when the whole set is empty)
 * is unit-testable without WordPress.
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
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	// displayDate() touches wp_date/get_option; Brain Monkey leaves wp_date defined
	// process-wide once any test stubs it, so stub both deterministically here.
	Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
	Functions\when( 'wp_date' )->alias( static fn ( string $fmt, int $ts ): string => gmdate( 'Y-m-d', $ts ) );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'metaHtml renders cover, date, volume and teaser rows for a full issue', function (): void {
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( '/cover.jpg' );

	$issue = new Issue( 1, 'Herfsuitgawe', '2026-04-01', 'Jaargang 12', 345, 0, 'Lente en letterkunde.' );
	$html  = SingleIssue::metaHtml( $issue );

	expect( $html )->toContain( 'ink-inkpols-besonderhede' );
	expect( $html )->toContain( '/cover.jpg' );
	expect( $html )->toContain( '2026-04-01' );          // displayDate Y-m-d fallback (no wp_date)
	expect( $html )->toContain( 'Jaargang 12' );
	expect( $html )->toContain( 'Lente en letterkunde.' );
} );

test( 'metaHtml omits each field that is absent (no malformed empty rows)', function (): void {
	// No cover, no date, only a volume.
	$issue = new Issue( 1, 't', '', 'Jaargang 1', 0, 0, '' );
	$html  = SingleIssue::metaHtml( $issue );

	expect( $html )->toContain( 'Jaargang 1' );
	expect( $html )->not->toContain( 'ink-inkpols-besonderhede__omslag' );
	expect( $html )->not->toContain( 'ink-inkpols-besonderhede__datum' );
	expect( $html )->not->toContain( 'ink-inkpols-besonderhede__voorskou' );
} );

test( 'metaHtml returns empty string when the issue carries no metadata at all', function (): void {
	$issue = new Issue( 1, 't', '', '', 0, 0, '' );
	expect( SingleIssue::metaHtml( $issue ) )->toBe( '' );
} );
