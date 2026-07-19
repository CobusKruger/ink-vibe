<?php
/**
 * Unit tests for the home featured-bydraes stream (Story 19.4, §6).
 *
 * Target: {@see \Ink\Discovery\FeaturedStream} — the `ink/uitgesoekte-bydraes` block.
 * We test the INK-owned OUTCOMES: the pure {@see FeaturedStream::queryArgs()} (the
 * newest-first bydrae args we build) and {@see FeaturedStream::toHtml()} (the section
 * markup we emit — asymmetric grid, cards, ink-core-owned read-time + counts, and its
 * graceful full-collapse). The thin impure render()/stream() touch WP_Query + meta and
 * are exercised by the integration suite. Brain-Monkey-mocked — no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\FeaturedStream;
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
	Functions\when( 'home_url' )->returnArg( 1 );
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A fully-computed card row (as resolveStream() would produce).
 *
 * @param array<string, mixed> $overrides Field overrides.
 * @return array<string, mixed>
 */
function ink_featured_item( array $overrides = array() ): array {
	return array_merge(
		array(
			'title'          => 'Fluister in die tuin',
			'url'            => 'https://ink.test/storie/fluister',
			'category'      => 'Kortverhaal',
			'read_minutes'   => 8,
			'excerpt'        => 'Sy het die briewe onder die rose gevind.',
			'author'         => 'Elena Vasquez',
			'avatar_url'     => 'https://ink.test/avatar/elena.jpg',
			'hart_count'     => 342,
			'response_count' => 12,
		),
		$overrides
	);
}

test( 'the block name + seam + size constants are the single-source values', function (): void {
	expect( FeaturedStream::BLOCK )->toBe( 'ink/uitgesoekte-bydraes' );
	expect( FeaturedStream::DATA_FILTER )->toBe( 'ink_home_featured_stream' );
	expect( FeaturedStream::MAX_ITEMS )->toBe( 4 );
} );

// --- queryArgs(): newest published bydraes, bounded ---

test( 'queryArgs targets published readable bydraes newest-first, bounded to the given limit', function (): void {
	$args = FeaturedStream::queryArgs( 4 );

	expect( $args['post_type'] )->toBe( PostTypes::readableTypes() );
	expect( $args['post_type'] )->toContain( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	expect( $args['post_type'] )->not->toContain( PostTypes::SKRYFWERK );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 4 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['no_found_rows'] )->toBeTrue();
} );

test( 'queryArgs never fetches an unbounded (-1) or zero-sized page', function (): void {
	expect( FeaturedStream::queryArgs( 0 )['posts_per_page'] )->toBe( 1 );
	expect( FeaturedStream::queryArgs( -5 )['posts_per_page'] )->toBe( 1 );
} );

// --- toHtml(): collapses entirely when the feed is empty ---

test( 'toHtml COLLAPSES the WHOLE section (header included) when the feed is empty', function (): void {
	expect( FeaturedStream::toHtml( array() ) )->toBe( '' );
} );

test( 'toHtml COLLAPSES when no item has a title (never an orphan header)', function (): void {
	$html = FeaturedStream::toHtml(
		array(
			array( 'title' => '   ' ),
			array( 'url' => 'https://ink.test/x' ),
		)
	);

	expect( $html )->toBe( '' );
} );

// --- toHtml(): the section header (§6) ---

test( 'toHtml renders the eyebrow + serif title + focusable "Sien alle werke" link', function (): void {
	$html = FeaturedStream::toHtml( array( ink_featured_item() ) );

	// UPPERCASE terracotta eyebrow (authored copy) + the serif h2 title.
	expect( $html )->toContain( 'Die redakteur se keuse' );
	expect( $html )->toContain( '<h2 id="ink-uitgesoekte-bydraes__titel"' );
	expect( $html )->toContain( 'Hierdie week se uitgesoektes' );

	// "Sien alle werke" link to the Ontdek hub, underline-slide, real href (no #).
	expect( $html )->toContain( 'Sien alle werke' );
	expect( $html )->toContain( 'href="/ontdek"' );
	expect( $html )->toContain( 'ink-underline-slide' );
	expect( $html )->not->toContain( 'href="#"' );

	// The section is labelled by its title (a11y).
	expect( $html )->toContain( 'aria-labelledby="ink-uitgesoekte-bydraes__titel"' );

	// No Lovable placeholder copy ever reaches output.
	expect( $html )->not->toContain( 'Titel van die werk' );
	expect( $html )->not->toContain( '[skrywer]' );
} );

// --- toHtml(): the asymmetric grid — first card featured (spans two) ---

test( 'toHtml marks the FIRST card featured (spans two columns) and the rest standard', function (): void {
	$html = FeaturedStream::toHtml(
		array(
			ink_featured_item( array( 'title' => 'Eerste' ) ),
			ink_featured_item( array( 'title' => 'Tweede' ) ),
		)
	);

	// The grid container + exactly one featured (spanning) card.
	expect( $html )->toContain( 'ink-uitgesoekte-bydraes__rooster' );
	expect( substr_count( $html, 'ink-uitgesoekte-bydraes__kaart--uitgesoek' ) )->toBe( 1 );

	// The featured modifier attaches to the first card, before the second title.
	$featured_pos = strpos( $html, '--uitgesoek' );
	$eerste_pos   = strpos( $html, 'Eerste' );
	$tweede_pos   = strpos( $html, 'Tweede' );
	expect( $featured_pos )->toBeLessThan( $eerste_pos );
	expect( $eerste_pos )->toBeLessThan( $tweede_pos );
} );

// --- toHtml(): card content (§6) ---

test( 'toHtml renders the card: pill, read-time, h3 title link, excerpt, avatar (alt) + author', function (): void {
	$html = FeaturedStream::toHtml( array( ink_featured_item() ) );

	// Category pill.
	expect( $html )->toContain( 'ink-uitgesoekte-bydraes__pil' );
	expect( $html )->toContain( 'Kortverhaal' );

	// Read-time (ink-core-computed) — "8 min" with a Clock icon.
	expect( $html )->toContain( 'ink-uitgesoekte-bydraes__leestyd' );
	expect( $html )->toContain( '8 min' );

	// Title is an h3 (correct order under the section h2), linked to the work.
	expect( $html )->toContain( '<h3 class="ink-uitgesoekte-bydraes__werk"><a href="https://ink.test/storie/fluister">Fluister in die tuin</a></h3>' );

	// Excerpt.
	expect( $html )->toContain( 'Sy het die briewe' );

	// Avatar 32px with alt = author name (never empty a11y), + author name.
	expect( $html )->toContain( 'src="https://ink.test/avatar/elena.jpg"' );
	expect( $html )->toContain( 'alt="Elena Vasquez"' );
	expect( $html )->toContain( 'width="32" height="32"' );
	expect( $html )->toContain( 'Elena Vasquez' );
} );

test( 'toHtml renders verb-less engagement counts with full accessible labels (Heart = hartjies, MessageCircle = reaksies)', function (): void {
	$html = FeaturedStream::toHtml( array( ink_featured_item( array( 'hart_count' => 342, 'response_count' => 12 ) ) ) );

	// Heart count: verb-less number visible, full label on the accessible name.
	expect( $html )->toContain( 'aria-label="342 hartjies"' );
	expect( $html )->toContain( '>342<' );
	expect( $html )->not->toContain( 'mense het gehou' ); // never vanity-framed.

	// MessageCircle count: the controlled-vocabulary Gemeenskapsreaksie label.
	expect( $html )->toContain( 'aria-label="12 Gemeenskapsreaksies"' );
	expect( $html )->toContain( '>12<' );
} );

test( 'toHtml uses the singular hartjie/Gemeenskapsreaksie form for a count of one (_n)', function (): void {
	$html = FeaturedStream::toHtml( array( ink_featured_item( array( 'hart_count' => 1, 'response_count' => 1 ) ) ) );

	expect( $html )->toContain( 'aria-label="1 hartjie"' );
	expect( $html )->not->toContain( '1 hartjies' );
	expect( $html )->toContain( 'aria-label="1 Gemeenskapsreaksie"' );
} );

// --- toHtml(): graceful sub-part omission (non-vacuous) ---

test( 'toHtml omits the read-time gracefully when a body is empty (0 min → no "0 min")', function (): void {
	$html = FeaturedStream::toHtml( array( ink_featured_item( array( 'read_minutes' => 0 ) ) ) );

	// Non-vacuous: the card still renders...
	expect( $html )->toContain( 'ink-uitgesoekte-bydraes__kaart' );
	expect( $html )->toContain( 'Fluister in die tuin' );

	// ...but no read-time chip / "0 min" surfaces.
	expect( $html )->not->toContain( 'ink-uitgesoekte-bydraes__leestyd' );
	expect( $html )->not->toContain( '0 min' );
} );

test( 'toHtml omits the avatar gracefully when there is none (no empty img)', function (): void {
	$html = FeaturedStream::toHtml( array( ink_featured_item( array( 'avatar_url' => '' ) ) ) );

	// Non-vacuous: the author name still renders...
	expect( $html )->toContain( 'Elena Vasquez' );

	// ...but no orphan <img> with an empty src.
	expect( $html )->not->toContain( 'ink-uitgesoekte-bydraes__foto' );
	expect( $html )->not->toContain( 'src=""' );
} );
