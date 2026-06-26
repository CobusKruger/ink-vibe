<?php
/**
 * Unit tests for the pinned-works curation block (Story 9.5, FR-41).
 *
 * Target: {@see \Ink\Social\PinnedWorksManager}. The pure `toHtml()` (a
 * state-correct pin toggle per own work) and the logged-out gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\PinnedWorksManager;
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

test( 'toHtml renders a state-correct pin toggle per own work', function (): void {
	$html = PinnedWorksManager::toHtml(
		array(
			array( 'id' => 7, 'title' => 'Vlerke', 'is_pinned' => true ),
			array( 'id' => 42, 'title' => 'Brug', 'is_pinned' => false ),
		)
	);

	expect( $html )->toContain( 'Vlerke' );
	expect( $html )->toContain( 'data-ink-post="7"' );
	expect( $html )->toContain( 'aria-pressed="true"' );  // pinned
	expect( $html )->toContain( '>Vasgespeld<' );

	expect( $html )->toContain( 'data-ink-post="42"' );
	expect( $html )->toContain( 'aria-pressed="false"' ); // not pinned
	expect( $html )->toContain( '>Speld vas<' );
} );

test( 'toHtml renders an empty state when the writer has no published works', function (): void {
	$html = PinnedWorksManager::toHtml( array() );

	expect( $html )->toContain( 'ink-vasgespel__leeg' );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'render returns nothing for a logged-out visitor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( PinnedWorksManager::render() )->toBe( '' );
} );
