<?php
/**
 * Unit tests for the uitdaging single-page surface (Story 12.1, FR-45).
 *
 * Target: {@see \Ink\Challenges\SinglePage} — the `ink/uitdaging-besonderhede`
 * server block (deadline/status line + entries list) rendered on a single uitdaging.
 *
 * Pure layers only (queryArgs / isOpen / statusHtml / entriesHtml / toHtml); the
 * thin impure render() touches WP and is exercised by the integration suite. Brain
 * Monkey stubs the i18n + escaping passthroughs (tests/bootstrap.php precedent).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\SinglePage;
use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'entriesQueryArgs targets published readable bydraes filtered by the round term, newest-first', function (): void {
	$args = SinglePage::entriesQueryArgs( 7 );

	expect( $args['post_type'] )->toBe( PostTypes::readableTypes() );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );

	expect( $args['tax_query'][0]['taxonomy'] )->toBe( Taxonomies::UITDAGINGSRONDTE );
	expect( $args['tax_query'][0]['field'] )->toBe( 'slug' );
	expect( $args['tax_query'][0]['terms'] )->toBe( ChallengeRound::slugFor( 7 ) );
} );

test( 'entriesQueryArgs with a non-positive id matches nothing (post__in [0])', function (): void {
	$args = SinglePage::entriesQueryArgs( 0 );

	expect( $args['post__in'] )->toBe( array( 0 ) );
	expect( $args )->not->toHaveKey( 'tax_query' );
} );

test( 'isOpen is true through the inclusive end-of-day-SAST deadline and false after', function (): void {
	$deadline = new \DateTimeImmutable( '2026-10-31 00:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	// 23:59:59 SAST on the deadline day is still open (inclusive boundary).
	$open = new \DateTimeImmutable( '2026-10-31 21:59:59', new \DateTimeZone( 'UTC' ) );
	expect( SinglePage::isOpen( $deadline, $open ) )->toBeTrue();

	// One second later (next SAST day) is closed.
	$closed = new \DateTimeImmutable( '2026-10-31 22:00:00', new \DateTimeZone( 'UTC' ) );
	expect( SinglePage::isOpen( $deadline, $closed ) )->toBeFalse();
} );

test( 'statusHtml renders the sluitingsdatum with an Oop marker while open', function (): void {
	$html = SinglePage::statusHtml( '31 Oktober 2026', true );

	expect( $html )->toContain( 'Sluitingsdatum' );
	expect( $html )->toContain( '31 Oktober 2026' );
	expect( $html )->toContain( 'Oop' );
	expect( $html )->toContain( 'is-oop' );
} );

test( 'statusHtml renders a Gesluit marker once closed', function (): void {
	$html = SinglePage::statusHtml( '31 Oktober 2026', false );

	expect( $html )->toContain( 'Gesluit' );
	expect( $html )->toContain( 'is-gesluit' );
} );

test( 'statusHtml renders nothing when there is no deadline', function (): void {
	expect( SinglePage::statusHtml( '', true ) )->toBe( '' );
} );

test( 'entriesHtml lists each entry as a title→permalink, newest-first', function (): void {
	$entries = array(
		array(
			'title'     => 'My gedig',
			'permalink' => 'https://ink.test/gedig/my-gedig',
		),
		array(
			'title'     => 'Sy storie',
			'permalink' => 'https://ink.test/storie/sy-storie',
		),
	);

	$html = SinglePage::entriesHtml( $entries );

	expect( $html )->toContain( 'Inskrywings' );
	expect( $html )->toContain( 'My gedig' );
	expect( $html )->toContain( 'https://ink.test/gedig/my-gedig' );
	expect( $html )->toContain( 'Sy storie' );
	expect( substr_count( $html, '<li' ) )->toBe( 2 );
} );

test( 'entriesHtml renders a graceful empty state with no entries (no empty list shell)', function (): void {
	$html = SinglePage::entriesHtml( array() );

	expect( $html )->toContain( 'Geen' );
	expect( $html )->not->toContain( '<li' );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'toHtml composes the status line and entries list inside the section shell', function (): void {
	$html = SinglePage::toHtml( '<p class="ink-uitdaging__status">x</p>', '<ul class="ink-uitdaging__inskrywings"></ul>' );

	expect( $html )->toContain( 'ink-uitdaging' );
	expect( $html )->toContain( 'ink-uitdaging__status' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywings' );
} );
