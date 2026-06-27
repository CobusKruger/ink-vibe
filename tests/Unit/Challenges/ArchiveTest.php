<?php
/**
 * Unit tests for the uitdagings list page (Story 12.2, FR-46).
 *
 * Target: {@see \Ink\Challenges\Archive} — the `ink/uitdaging-argief` server block
 * (paginated card grid with a per-card countdown). Pure layers only (queryArgs /
 * countdownLabel / cardHtml / toHtml); the thin impure render() is integration-tested.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Archive;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	// ArchiveRender::pagination uses these; stub to inert passthroughs.
	Functions\when( 'add_query_arg' )->justReturn( '#' );
	Functions\when( 'remove_query_arg' )->justReturn( '#' );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'queryArgs lists published uitdagings newest-first, paged and bounded', function (): void {
	$args = Archive::queryArgs( 2, 12 );

	expect( $args['post_type'] )->toBe( PostTypes::UITDAGING );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 12 );
	expect( $args['paged'] )->toBe( 2 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
} );

test( 'queryArgs floors a non-positive page to 1', function (): void {
	expect( Archive::queryArgs( 0, 12 )['paged'] )->toBe( 1 );
	expect( Archive::queryArgs( -5, 12 )['paged'] )->toBe( 1 );
} );

test( 'countdownLabel counts whole SAST days remaining while open', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-10-26 09:00:00', $sast ); // 5 calendar days before

	expect( Archive::countdownLabel( $deadline, $now ) )->toContain( '5' );
	expect( Archive::countdownLabel( $deadline, $now ) )->toContain( 'dae' );
} );

test( 'countdownLabel uses the singular dag for exactly one day out', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-10-30 09:00:00', $sast );

	expect( Archive::countdownLabel( $deadline, $now ) )->toBe( 'Nog 1 dag' );
} );

test( 'countdownLabel says Sluit vandag on the deadline SAST day', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 23:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-10-31 08:00:00', $sast );

	expect( Archive::countdownLabel( $deadline, $now ) )->toBe( 'Sluit vandag' );
} );

test( 'countdownLabel says Gesluit once the inclusive deadline has passed', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-11-01 00:00:01', $sast );

	expect( Archive::countdownLabel( $deadline, $now ) )->toBe( 'Gesluit' );
} );

test( 'countdownLabel renders nothing without a deadline', function (): void {
	expect( Archive::countdownLabel( null, new \DateTimeImmutable( 'now' ) ) )->toBe( '' );
} );

test( 'cardHtml renders the title→permalink, tema, deadline and countdown with an open marker', function (): void {
	$html = Archive::cardHtml(
		array(
			'title'     => 'Oktober-uitdaging',
			'permalink' => 'https://ink.test/uitdaging/oktober',
			'tema'      => 'Herfs',
			'deadline'  => '31 Oktober 2026',
			'countdown' => 'Nog 5 dae',
			'is_open'   => true,
		)
	);

	expect( $html )->toContain( 'Oktober-uitdaging' );
	expect( $html )->toContain( 'https://ink.test/uitdaging/oktober' );
	expect( $html )->toContain( 'Herfs' );
	expect( $html )->toContain( '31 Oktober 2026' );
	expect( $html )->toContain( 'Nog 5 dae' );
	expect( $html )->toContain( 'is-oop' );
} );

test( 'toHtml renders the heading and a card per challenge', function (): void {
	$cards = array(
		Archive::cardHtml(
			array(
				'title'     => 'A',
				'permalink' => '#a',
				'tema'      => '',
				'deadline'  => '',
				'countdown' => '',
				'is_open'   => false,
			)
		),
		Archive::cardHtml(
			array(
				'title'     => 'B',
				'permalink' => '#b',
				'tema'      => '',
				'deadline'  => '',
				'countdown' => '',
				'is_open'   => false,
			)
		),
	);

	$html = Archive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 1 ) );

	expect( $html )->toContain( 'ink-uitdagings' );
	expect( substr_count( $html, 'ink-uitdagings__item' ) )->toBe( 2 );
} );

test( 'toHtml renders a graceful empty state with no challenges', function (): void {
	$html = Archive::toHtml( array(), array( 'paged' => 1, 'max_pages' => 0 ) );

	expect( $html )->toContain( 'ink-uitdagings' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->not->toContain( 'ink-uitdagings__item' );
} );
