<?php
/**
 * Unit tests for the reaction-totals block (Story 7.8, FR-28).
 *
 * Target: {@see \Ink\Engagement\ReactionTotals::toHtml()} — pure. Renders a
 * verb-less count per reaction via the single-source formatter. `_n` aliased to
 * the real plural rule; `esc_*` identity.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReactionTotals;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	// The 'enkel' variant resolves its data-audit-id from the current singular
	// post type (gedig vs storie) — outside any real WP request context here,
	// so `is_singular()` is always false (no audit-id printed).
	Functions\when( 'is_singular' )->justReturn( false );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders a verb-less count for every reaction via the single-source formatter', function (): void {
	$html = ReactionTotals::toHtml( array( 'hartjie' => 342, 'duim_op' => 0, 'wow' => 5 ) );

	expect( $html )->toContain( '342 hartjies' );
	expect( $html )->toContain( '0 duim op' );  // n=0 handled
	expect( $html )->toContain( '5 wows' );
	expect( $html )->toContain( 'ink-reaksie-tellers--hartjie' );
} );

test( 'toHtml treats a missing reaction key as zero', function (): void {
	$html = ReactionTotals::toHtml( array( 'hartjie' => 7 ) ); // duim_op / wow absent

	expect( $html )->toContain( '7 hartjies' );
	expect( $html )->toContain( '0 duim op' );
	expect( $html )->toContain( '0 wows' );
} );

test( 'toHtml with the enkel variant renders only the hartjie count, no per-reaction labels', function (): void {
	$html = ReactionTotals::toHtml( array( 'hartjie' => 12, 'duim_op' => 9, 'wow' => 4 ), 'enkel' );

	expect( $html )->toContain( 'ink-reaksie-tellers--enkel' );
	expect( $html )->toContain( '>12<' ); // bare visible count, no "N hartjies" label text
	expect( $html )->not->toContain( '9 duim op' );
	expect( $html )->not->toContain( '4 wow' );
} );

test( 'toHtml with the enkel variant treats a missing hartjie key as zero', function (): void {
	$html = ReactionTotals::toHtml( array( 'duim_op' => 3 ), 'enkel' );

	expect( $html )->toContain( '>0<' );
} );

test( 'toHtml with the enkel variant and a real post_id renders a clickable button anchored to the first content line/paragraph', function (): void {
	$post              = new \WP_Post();
	$post->post_type   = 'gedig';
	$post->post_content = "eerste reël\n\ntweede reël";

	Functions\when( 'get_post' )->justReturn( $post );
	Functions\when( 'is_user_logged_in' )->justReturn( true );

	$html = ReactionTotals::toHtml( array( 'hartjie' => 3 ), 'enkel', 42 );

	expect( $html )->toStartWith( '<button' );
	expect( $html )->toEndWith( '</button>' );
	expect( $html )->toContain( 'data-ink-post="42"' );
	expect( $html )->toContain( 'data-ink-line="0"' );
	expect( $html )->not->toContain( 'data-ink-guest' );
} );

test( 'toHtml with the enkel variant flags a logged-out visitor for a guest redirect, and dispatches the storie paragraph anchor', function (): void {
	$post              = new \WP_Post();
	$post->post_type   = 'storie';
	$post->post_content = "eerste paragraaf\n\ntweede paragraaf";

	Functions\when( 'get_post' )->justReturn( $post );
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	$html = ReactionTotals::toHtml( array( 'hartjie' => 0 ), 'enkel', 7 );

	expect( $html )->toContain( 'data-ink-line="0"' );
	expect( $html )->toContain( 'data-ink-guest="1"' );
} );

test( 'toHtml with the enkel variant falls back to the plain non-interactive display when there is no valid anchor', function (): void {
	Functions\when( 'get_post' )->justReturn( null );

	$html = ReactionTotals::toHtml( array( 'hartjie' => 5 ), 'enkel', 99 );

	expect( $html )->toStartWith( '<div' );
	expect( $html )->not->toContain( 'data-ink-post' );
} );
