<?php
/**
 * Unit tests for the reader-rating form block (Story 9.6, FR-42).
 *
 * Target: {@see \Ink\Social\RatingForm}. The pure `toHtml()` (star select +
 * review + the held-for-moderation note) and the visibility gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\RatingForm;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders a 1-5 star select, a review field and the moderation note', function (): void {
	$html = RatingForm::toHtml( 42 );

	expect( $html )->toContain( 'data-ink-skrywer="42"' );
	expect( $html )->toContain( 'name="ink-oordeel-score"' );
	expect( $html )->toContain( 'value="1"' );
	expect( $html )->toContain( 'value="5"' );
	expect( $html )->toContain( 'ink-oordeel-resensie' );
	expect( $html )->toContain( 'gemodereer' ); // the held-for-moderation note
} );

test( 'render returns nothing for a logged-out reader', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( RatingForm::render( array( 'skrywerId' => 42 ) ) )->toBe( '' );
} );

test( 'render returns nothing when the target is the viewer (cannot rate yourself)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 42 );

	expect( RatingForm::render( array( 'skrywerId' => 42 ) ) )->toBe( '' );
} );

test( 'render shows the form for a third-party writer (non-vacuous)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 7 );

	$html = RatingForm::render( array( 'skrywerId' => 42 ) );

	expect( $html )->toContain( 'ink-leseroordeel-vorm' );
	expect( $html )->toContain( 'data-ink-skrywer="42"' );
} );
