<?php
/**
 * Unit tests for the InkPols module facade (Story 13.1, FR-57).
 *
 * Target: {@see \Ink\InkPols\Api} — the sole public cross-module surface. The
 * issue read-model lookup ({@see Api::issueFor()}, type-guarded) and the
 * canonical meta-key set ({@see Api::metaKeys()}) are unit-testable with WP mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Api;
use Ink\InkPols\Issue;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'metaKeys returns exactly the five FR-57 issue meta keys, single-sourced from FieldSets', function (): void {
	expect( Api::metaKeys() )->toBe(
		array(
			FieldSets::INKPOLS_ISSUE_DATE,
			FieldSets::INKPOLS_VOLUME,
			FieldSets::INKPOLS_COVER_ID,
			FieldSets::INKPOLS_PDF_ID,
			FieldSets::INKPOLS_TEASER,
		)
	);
} );

test( 'issueFor returns an Issue read-model for a real inkpols_uitgawe post', function (): void {
	Functions\when( 'get_post_type' )->justReturn( PostTypes::INKPOLS_UITGAWE );
	Functions\when( 'get_the_title' )->justReturn( 'Uitgawe 1' );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	$issue = Api::issueFor( 42 );

	expect( $issue )->toBeInstanceOf( Issue::class );
	expect( $issue->postId )->toBe( 42 );
} );

test( 'issueFor returns null for a post of a different type (no phantom issue)', function (): void {
	Functions\when( 'get_post_type' )->justReturn( PostTypes::GEDIG );

	expect( Api::issueFor( 42 ) )->toBeNull();
} );

test( 'issueFor returns null for a non-positive id without touching get_post_type', function (): void {
	// get_post_type intentionally NOT stubbed — a 0 id must short-circuit before it.
	expect( Api::issueFor( 0 ) )->toBeNull();
	expect( Api::issueFor( -3 ) )->toBeNull();
} );
