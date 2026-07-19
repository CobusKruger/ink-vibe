<?php
/**
 * Unit tests for the home current-challenge card (Story 19.3, §3 + §5).
 *
 * Target: {@see \Ink\Challenges\CurrentChallenge} — the `ink/huidige-uitdaging` block.
 * We test INK-owned OUTCOMES: the pure {@see CurrentChallenge::queryArgs()} (the
 * open-challenge scan args we build) and {@see CurrentChallenge::toHtml()} (the card
 * markup we emit, per variant, and its graceful empty-collapse). The thin impure
 * render()/current() touch WP_Query and are exercised by the integration suite.
 * Brain-Monkey-mocked — no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\CurrentChallenge;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	ink_reset_guard_spies(); // seed `init` as fired so Terms::label() resolves quietly.
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the block name + variant constants are the single-source values', function (): void {
	expect( CurrentChallenge::BLOCK )->toBe( 'ink/huidige-uitdaging' );
	expect( CurrentChallenge::VARIANT_COMPACT )->toBe( 'kompak' );
	expect( CurrentChallenge::VARIANT_FEATURE )->toBe( 'kenmerk' );
} );

// --- queryArgs(): the newest published uitdagings, bounded ---

test( 'queryArgs targets published uitdagings newest-first, bounded to the given limit', function (): void {
	$args = CurrentChallenge::queryArgs( 10 );

	expect( $args['post_type'] )->toBe( PostTypes::UITDAGING );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 10 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['no_found_rows'] )->toBeTrue();
} );

test( 'queryArgs never fetches an unbounded (-1) or zero-sized page', function (): void {
	expect( CurrentChallenge::queryArgs( 0 )['posts_per_page'] )->toBe( 1 );
	expect( CurrentChallenge::queryArgs( -5 )['posts_per_page'] )->toBe( 1 );
} );

// --- toHtml(): collapses when there is no open challenge ---

test( 'toHtml COLLAPSES to empty markup when there is no open challenge (no title)', function (): void {
	expect( CurrentChallenge::toHtml( array() ) )->toBe( '' );
	expect( CurrentChallenge::toHtml( array( 'title' => '   ' ) ) )->toBe( '' );
} );

// --- toHtml(): compact hero card (§3) ---

test( 'toHtml (compact) renders the §3 hero card: badge, h2 title, excerpt, deadline meta, CTA', function (): void {
	$html = CurrentChallenge::toHtml(
		array(
			'title'    => 'Nuwe begin',
			'url'      => 'https://ink.test/uitdaging/nuwe-begin',
			'excerpt'  => 'Skryf oor \'n vars begin.',
			'deadline' => '31 Julie 2026',
		),
		CurrentChallenge::VARIANT_COMPACT
	);

	// The compact variant + the block namespace.
	expect( $html )->toContain( 'ink-huidige-uitdaging--kompak' );

	// The type badge carries the "Uitdaging" glossary label (rank text, never icon-only).
	expect( $html )->toContain( 'ink-huidige-uitdaging__kenteken' );
	expect( $html )->toContain( 'Uitdaging' );

	// Title is an h2 (correct order under the hero h1); the body excerpt renders.
	expect( $html )->toContain( '<h2 class="ink-huidige-uitdaging__titel">Nuwe begin</h2>' );
	expect( $html )->toContain( 'Skryf oor' );

	// Deadline meta ("Sluit <date>") + the "Skryf in" CTA linking the challenge.
	expect( $html )->toContain( 'Sluit 31 Julie 2026' );
	expect( $html )->toContain( 'Skryf in' );
	expect( $html )->toContain( 'href="https://ink.test/uitdaging/nuwe-begin"' );

	// The decorative corner tint is aria-hidden (never announced / never clickable).
	expect( $html )->toContain( 'ink-huidige-uitdaging__hoek' );
	expect( $html )->toContain( 'aria-hidden="true"' );
} );

test( 'toHtml (compact) does NOT render the feature-only eyebrow / entry-count meta', function (): void {
	$html = CurrentChallenge::toHtml(
		array(
			'title'       => 'Nuwe begin',
			'url'         => 'https://ink.test/u/1',
			'eyebrow'     => 'Julie-uitdaging',
			'entry_count' => 42,
		),
		CurrentChallenge::VARIANT_COMPACT
	);

	// Non-vacuous: the card renders...
	expect( $html )->toContain( 'ink-huidige-uitdaging--kompak' );

	// ...but the feature eyebrow + the Users entry-count meta are feature-only.
	expect( $html )->not->toContain( 'ink-huidige-uitdaging__boskrif' );
	expect( $html )->not->toContain( 'inskrywings' );
} );

// --- toHtml(): feature card (§5) ---

test( 'toHtml (feature) renders the §5 Uitdaging card: eyebrow, icon tile, title, deadline + entry-count meta', function (): void {
	$html = CurrentChallenge::toHtml(
		array(
			'title'       => 'Nuwe begin',
			'url'         => 'https://ink.test/u/1',
			'excerpt'     => 'Skryf oor \'n vars begin.',
			'deadline'    => '31 Julie 2026',
			'eyebrow'     => 'Julie-uitdaging',
			'entry_count' => 42,
		),
		CurrentChallenge::VARIANT_FEATURE
	);

	expect( $html )->toContain( 'ink-huidige-uitdaging--kenmerk' );
	expect( $html )->toContain( 'ink-huidige-uitdaging__ikoon' );
	expect( $html )->toContain( 'Julie-uitdaging' );
	expect( $html )->toContain( '<h2 class="ink-huidige-uitdaging__titel">Nuwe begin</h2>' );

	// The meta row shows the deadline + the pluralised entry count (_n af plural).
	expect( $html )->toContain( 'ink-huidige-uitdaging__meta' );
	expect( $html )->toContain( 'Sluit 31 Julie 2026' );
	expect( $html )->toContain( '42 inskrywings' );
} );

test( 'toHtml (feature) uses the singular entry-count form for a single entry (_n)', function (): void {
	$html = CurrentChallenge::toHtml(
		array(
			'title'       => 'Nuwe begin',
			'deadline'    => '31 Julie 2026',
			'entry_count' => 1,
		),
		CurrentChallenge::VARIANT_FEATURE
	);

	expect( $html )->toContain( '1 inskrywing' );
	expect( $html )->not->toContain( '1 inskrywings' );
} );

test( 'toHtml (feature) omits the entry-count meta gracefully when there are zero entries', function (): void {
	$html = CurrentChallenge::toHtml(
		array(
			'title'       => 'Nuwe begin',
			'deadline'    => '31 Julie 2026',
			'entry_count' => 0,
		),
		CurrentChallenge::VARIANT_FEATURE
	);

	expect( $html )->toContain( 'Sluit 31 Julie 2026' );
	expect( $html )->not->toContain( 'inskrywing' );
} );
