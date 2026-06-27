<?php
/**
 * Unit tests for the InkPols by-year archive (Story 13.2, FR-57).
 *
 * Target: {@see \Ink\InkPols\Archive}. The pure `queryArgs()` (bounded published
 * issues), `groupByYear()` (year-DESC grouping, in-year date-DESC, undated last)
 * and `toHtml()`/`card()` (year sections, cards, cover-omission, empty state) are
 * unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Archive;
use Ink\InkPols\Issue;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Register the WP stubs the render/card path needs — escaping + permalink.
 */
function ink_inkpols_archive_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'get_permalink' )->alias( static fn ( int $id ): string => '/inkpols/' . $id );
	// displayDate() touches wp_date/get_option; Brain Monkey leaves wp_date defined
	// process-wide once any test stubs it, so stub both deterministically here.
	Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
	Functions\when( 'wp_date' )->alias( static fn ( string $fmt, int $ts ): string => gmdate( 'Y-m-d', $ts ) );
}

/**
 * A bare Issue read-model for grouping/render tests (no WP reads).
 */
function ink_inkpols_issue( int $id, string $date, string $volume = '', int $coverId = 0, string $teaser = '' ): Issue {
	return new Issue( $id, 'Uitgawe ' . $id, $date, $volume, $coverId, 0, $teaser );
}

// --- queryArgs ---

test( 'queryArgs lists published inkpols_uitgawe newest-first, bounded by the cap', function (): void {
	$args = Archive::queryArgs( 500 );

	expect( $args['post_type'] )->toBe( PostTypes::INKPOLS_UITGAWE );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 500 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['no_found_rows'] )->toBeTrue();
} );

test( 'queryArgs floors a non-positive cap to at least one', function (): void {
	expect( Archive::queryArgs( 0 )['posts_per_page'] )->toBe( 1 );
	expect( Archive::queryArgs( -10 )['posts_per_page'] )->toBe( 1 );
} );

test( 'MAX_ISSUES is a sane defensive bound (not an unbounded -1)', function (): void {
	expect( Archive::MAX_ISSUES )->toBeGreaterThan( 0 );
} );

// --- groupByYear ---

test( 'groupByYear groups by year DESC with in-year issues sorted by date DESC', function (): void {
	$issues = array(
		ink_inkpols_issue( 1, '2024-03-01' ),
		ink_inkpols_issue( 2, '2026-06-01' ),
		ink_inkpols_issue( 3, '2026-01-01' ),
		ink_inkpols_issue( 4, '2025-12-01' ),
	);

	$groups = Archive::groupByYear( $issues );

	expect( array_column( $groups, 'year' ) )->toBe( array( '2026', '2025', '2024' ) );
	// 2026 group: June before January (date DESC).
	expect( $groups[0]['issues'][0]->postId )->toBe( 2 );
	expect( $groups[0]['issues'][1]->postId )->toBe( 3 );
} );

test( 'groupByYear breaks a same-date tie by post id DESC (stable order for migrated Y-m-01 dates) — R13 review', function (): void {
	// Migration sets every issue to a `Y-m-01` date, so same-month issues collide;
	// the comparator must apply a deterministic post-id tiebreak.
	$issues = array(
		ink_inkpols_issue( 3, '2026-05-01' ),
		ink_inkpols_issue( 9, '2026-05-01' ),
		ink_inkpols_issue( 5, '2026-05-01' ),
	);

	$groups = Archive::groupByYear( $issues );

	expect( array_column( $groups[0]['issues'], 'postId' ) )->toBe( array( 9, 5, 3 ) );
} );

test( 'groupByYear puts undated issues in a trailing empty-year bucket (never dropped)', function (): void {
	$issues = array(
		ink_inkpols_issue( 1, '' ),          // undated
		ink_inkpols_issue( 2, '2025-05-01' ),
		ink_inkpols_issue( 3, 'malformed' ), // also undated
	);

	$groups = Archive::groupByYear( $issues );

	expect( array_column( $groups, 'year' ) )->toBe( array( '2025', '' ) );
	// Both undated issues survive in the trailing bucket.
	expect( count( $groups[1]['issues'] ) )->toBe( 2 );
} );

// --- toHtml / card ---

test( 'toHtml renders the heading and a year section with a card per issue', function (): void {
	ink_inkpols_archive_stubs();

	$groups = Archive::groupByYear(
		array(
			ink_inkpols_issue( 1, '2026-04-01', 'Jaargang 12', 0, 'Lente.' ),
			ink_inkpols_issue( 2, '2025-04-01' ),
		)
	);

	$html = Archive::toHtml( $groups );

	expect( $html )->toContain( 'InkPols' );
	expect( $html )->toContain( 'ink-inkpols__jaar' );
	expect( $html )->toContain( '2026' );
	expect( $html )->toContain( '2025' );
	expect( $html )->toContain( 'Uitgawe 1' );
	expect( $html )->toContain( '/inkpols/1' );
	expect( $html )->toContain( 'Jaargang 12' );
	expect( $html )->toContain( 'Lente.' );
} );

test( 'a card omits the cover image when there is no cover (no broken img)', function (): void {
	ink_inkpols_archive_stubs();

	$html = Archive::card( ink_inkpols_issue( 7, '2026-04-01' ) );
	expect( $html )->not->toContain( 'ink-inkpols__omslag' );
	expect( $html )->toContain( 'ink-inkpols__titel' );
} );

test( 'a card renders the cover image when a cover resolves', function (): void {
	ink_inkpols_archive_stubs();
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( '/cover.jpg' );

	$html = Archive::card( ink_inkpols_issue( 7, '2026-04-01', '', 345 ) );
	expect( $html )->toContain( 'ink-inkpols__omslag' );
	expect( $html )->toContain( '/cover.jpg' );
} );

test( 'the undated group renders no year heading but still lists its cards', function (): void {
	ink_inkpols_archive_stubs();

	$groups = Archive::groupByYear( array( ink_inkpols_issue( 9, '' ) ) );
	$html   = Archive::toHtml( $groups );

	// Non-vacuous: the card is present...
	expect( $html )->toContain( 'Uitgawe 9' );
	// ...but no year-title element for the undated bucket.
	expect( $html )->not->toContain( 'ink-inkpols__jaar-titel' );
} );

test( 'toHtml shows the empty-state line (heading + Geen Uitgawes) when there are no issues', function (): void {
	ink_inkpols_archive_stubs();

	$html = Archive::toHtml( array() );

	expect( $html )->toContain( 'InkPols' );      // heading still renders
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-inkpols__leeg' );
	expect( $html )->not->toContain( 'ink-inkpols__lys' );
} );
