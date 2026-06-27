<?php
/**
 * Unit tests for the InkPols issue read-model (Story 13.1, FR-57).
 *
 * Target: {@see \Ink\InkPols\Issue}. The `forPost()` meta read (default-safe), the
 * pure `year()` archive grouping key, the `displayDate()` formatter, and the
 * `coverUrl()`/`pdfUrl()`/`hasPdf()` attachment resolvers are unit-testable with
 * WordPress mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Issue;
use Ink\Content\FieldSets;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Stub `get_post_meta` to return values from a key→value map (the stored meta).
 *
 * @param array<string, mixed> $meta The stored meta, keyed by meta key.
 */
function ink_inkpols_stub_meta( array $meta ): void {
	Functions\when( 'get_post_meta' )->alias(
		static fn ( int $id, string $key, bool $single = false ) => $meta[ $key ] ?? ''
	);
}

// --- forPost ---

test( 'forPost reads all five FR-57 fields off the post into typed properties', function (): void {
	Functions\when( 'get_the_title' )->justReturn( 'Herfsuitgawe 2026' );
	ink_inkpols_stub_meta(
		array(
			FieldSets::INKPOLS_ISSUE_DATE => '2026-04-01',
			FieldSets::INKPOLS_VOLUME     => 'Jaargang 12, Nr. 2',
			FieldSets::INKPOLS_COVER_ID   => '345',
			FieldSets::INKPOLS_PDF_ID     => '678',
			FieldSets::INKPOLS_TEASER     => 'Lente, liefde en letterkunde.',
		)
	);

	$issue = Issue::forPost( 42 );

	expect( $issue->postId )->toBe( 42 );
	expect( $issue->title )->toBe( 'Herfsuitgawe 2026' );
	expect( $issue->issueDate )->toBe( '2026-04-01' );
	expect( $issue->volume )->toBe( 'Jaargang 12, Nr. 2' );
	expect( $issue->coverId )->toBe( 345 );
	expect( $issue->pdfId )->toBe( 678 );
	expect( $issue->teaser )->toBe( 'Lente, liefde en letterkunde.' );
} );

test( 'forPost is default-safe — missing/non-scalar meta degrades to typed empty defaults', function (): void {
	Functions\when( 'get_the_title' )->justReturn( '' );
	// Every key absent → '' from the stub; cover/pdf coerce to 0, never a fatal.
	ink_inkpols_stub_meta( array( FieldSets::INKPOLS_COVER_ID => array( 'not', 'scalar' ) ) );

	$issue = Issue::forPost( 7 );

	expect( $issue->issueDate )->toBe( '' );
	expect( $issue->volume )->toBe( '' );
	expect( $issue->coverId )->toBe( 0 ); // non-scalar → floored to 0.
	expect( $issue->pdfId )->toBe( 0 );
	expect( $issue->teaser )->toBe( '' );
} );

test( 'forPost never stores a negative attachment id (legacy/hand-edited meta floored to 0)', function (): void {
	Functions\when( 'get_the_title' )->justReturn( 'x' );
	ink_inkpols_stub_meta(
		array(
			FieldSets::INKPOLS_COVER_ID => '-5',
			FieldSets::INKPOLS_PDF_ID   => '-1',
		)
	);

	$issue = Issue::forPost( 1 );

	expect( $issue->coverId )->toBe( 0 );
	expect( $issue->pdfId )->toBe( 0 );
} );

// --- year (pure) ---

test( 'year extracts the 4-digit publication year — the by-year archive grouping key', function (): void {
	$issue = new Issue( 1, 't', '2024-11-30', '', 0, 0, '' );
	expect( $issue->year() )->toBe( '2024' );
} );

test( 'year returns empty string for an absent or malformed date', function (): void {
	expect( ( new Issue( 1, 't', '', '', 0, 0, '' ) )->year() )->toBe( '' );
	expect( ( new Issue( 1, 't', 'nonsense', '', 0, 0, '' ) )->year() )->toBe( '' );
	expect( ( new Issue( 1, 't', '20-1-1', '', 0, 0, '' ) )->year() )->toBe( '' );
} );

test( 'year treats a well-shaped but INVALID calendar date as undated (R13 review)', function (): void {
	// These pass FieldSets::sanitizeDate (shape only) but are not real dates.
	expect( ( new Issue( 1, 't', '2026-02-30', '', 0, 0, '' ) )->year() )->toBe( '' );
	expect( ( new Issue( 1, 't', '2026-13-01', '', 0, 0, '' ) )->year() )->toBe( '' );
} );

test( 'displayDate is CONSISTENT with year for an invalid calendar date — both treat it as undated (R13 review)', function (): void {
	$issue = new Issue( 1, 't', '2026-02-30', '', 0, 0, '' );
	// Neither groups it under a year nor renders a (shifted) date.
	expect( $issue->year() )->toBe( '' );
	expect( $issue->displayDate() )->toBe( '' );
} );

// --- displayDate ---

test( 'displayDate localises the issue date via wp_date', function (): void {
	Functions\when( 'get_option' )->justReturn( 'j F Y' );
	Functions\when( 'wp_date' )->justReturn( '1 April 2026' );

	$issue = new Issue( 1, 't', '2026-04-01', '', 0, 0, '' );
	expect( $issue->displayDate() )->toBe( '1 April 2026' );
} );

test( 'displayDate returns empty string when there is no issue date', function (): void {
	$issue = new Issue( 1, 't', '', '', 0, 0, '' );
	expect( $issue->displayDate() )->toBe( '' );
} );

test( 'displayDate falls back to a deterministic Y-m-d when wp_date yields nothing', function (): void {
	// Brain Monkey leaves wp_date defined process-wide once any test stubs it
	// (functions cannot be undefined), so exercise the fallback by having the
	// resolver return an empty string rather than relying on function-absence.
	Functions\when( 'get_option' )->justReturn( 'j F Y' );
	Functions\when( 'wp_date' )->justReturn( '' );

	$issue = new Issue( 1, 't', '2026-04-01', '', 0, 0, '' );
	expect( $issue->displayDate() )->toBe( '2026-04-01' );
} );

// --- attachment resolvers ---

test( 'coverUrl resolves a positive cover id to the attachment URL at the requested size', function (): void {
	Functions\when( 'wp_get_attachment_image_url' )->alias(
		static fn ( int $id, string $size ) => 'https://ink.test/cover-' . $id . '-' . $size . '.jpg'
	);

	$issue = new Issue( 1, 't', '', '', 345, 0, '' );
	expect( $issue->coverUrl() )->toBe( 'https://ink.test/cover-345-large.jpg' );
	expect( $issue->coverUrl( 'medium' ) )->toBe( 'https://ink.test/cover-345-medium.jpg' );
} );

test( 'coverUrl returns empty string when there is no cover id', function (): void {
	$issue = new Issue( 1, 't', '', '', 0, 0, '' );
	expect( $issue->coverUrl() )->toBe( '' );
} );

test( 'pdfUrl resolves a positive pdf id and hasPdf is true', function (): void {
	Functions\when( 'wp_get_attachment_url' )->alias(
		static fn ( int $id ) => 'https://ink.test/inkpols-' . $id . '.pdf'
	);

	$issue = new Issue( 1, 't', '', '', 0, 678, '' );
	expect( $issue->pdfUrl() )->toBe( 'https://ink.test/inkpols-678.pdf' );
	expect( $issue->hasPdf() )->toBeTrue();
} );

test( 'pdfUrl is empty and hasPdf false without a pdf id (or when it does not resolve)', function (): void {
	$noId = new Issue( 1, 't', '', '', 0, 0, '' );
	expect( $noId->pdfUrl() )->toBe( '' );
	expect( $noId->hasPdf() )->toBeFalse();

	// A positive id whose attachment no longer exists (resolver returns false).
	Functions\when( 'wp_get_attachment_url' )->justReturn( false );
	$dangling = new Issue( 1, 't', '', '', 0, 99, '' );
	expect( $dangling->pdfUrl() )->toBe( '' );
	expect( $dangling->hasPdf() )->toBeFalse();
} );
