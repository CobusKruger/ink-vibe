<?php
/**
 * Unit tests for the `/kompetisies/` blocks (N4, `05-kompetisies` §4, §5).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Ink\Competitions\Categories;
use Ink\Competitions\Directory;
use Ink\Competitions\Edition;
use Ink\Competitions\Gala;
use Ink\Competitions\GalaYear;
use Ink\Competitions\Hero;
use Ink\Competitions\Listing;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A current edition with these fields.
 *
 * @param array<string, string> $values Field values.
 */
function ink_komp_edition( array $values = array() ): Edition {
	return new Edition( 1, 'Die Kompetisie', $values + array( 'beskrywing' => 'B', 'jaar' => '2026' ) );
}

/**
 * A listing with these fields.
 *
 * @param array<string, string> $values Field values.
 */
function ink_komp_listing( array $values = array() ): Listing {
	return new Listing( 1, 'Lys', $values + array( 'genre' => 'gedigte', 'url' => 'https://example.org' ) );
}

test( 'the hero: dark recipes, Enter goes to /skryf/, Guidelines has no target', function (): void {
	$html = Hero::toHtml( ink_komp_edition( array( 'uitgawe' => 'U', 'sluitingsdatum' => 'Julie' ) ), '/skryf/' );

	expect( $html )->toContain( 'ink-band ink-band--dark ink-band--flush ink-band--no-border' );
	expect( $html )->toContain( 'ink-texture ink-texture--on-ink' );
	expect( $html )->toContain( 'ink-heading-h1-index ink-heading--on-ink' );
	expect( $html )->toContain( 'data-audit-id="competitions-hero-btn-primary" href="/skryf/"' );
	expect( $html )->toContain( '<button type="button" class="ink-btn ink-btn--lg ink-btn--ghost-on-ink"' );
	expect( $html )->toContain( 'competitions-hero-meta-close' );
	expect( $html )->not->toContain( 'competitions-hero-meta-entry' ); // Blank note, no pill.
} );

test( 'categories: a blank slot does not render; ids follow Lovable; Enter goes to /skryf/', function (): void {
	$html = Categories::toHtml(
		ink_komp_edition(
			array(
				'kat1_naam'   => 'Een',
				'kat1_riglyne' => "R1\nR2",
				'kat3_naam'   => 'Drie',
			)
		),
		'/skryf/'
	);

	expect( $html )->toContain( 'data-audit-id="competitions-category"' );
	expect( $html )->toContain( 'data-audit-id="competitions-category-0-body"' );
	expect( $html )->toContain( 'data-audit-id="competitions-category-1"' );
	expect( $html )->not->toContain( 'competitions-category-2' );
	expect( $html )->toContain( 'competitions-category-0-guideline-1-dot' );
	expect( $html )->not->toContain( 'competitions-category-1-guidelines' );
	expect( substr_count( $html, 'href="/skryf/"' ) )->toBe( 2 );
} );

test( 'gala: all years, first active, ids only on the active year, Reserve has no target', function (): void {
	$html = Gala::toHtml(
		ink_komp_edition( array( 'gala_naam' => 'Gala' ) ),
		array( new GalaYear( 2025, 'Hoog', array( 1 ) ), new GalaYear( 2024, 'Laer', array( 2 ) ) ),
		array(
			2025 => array( array( 'url' => '/a.jpg', 'caption' => 'A' ) ),
			2024 => array( array( 'url' => '/b.jpg', 'caption' => 'B' ) ),
		)
	);

	expect( $html )->toContain( 'data-audit-id="competitions-gala-past-year-2025"' );
	expect( $html )->toContain( 'data-audit-id="competitions-gala-past-year-2024"' );
	expect( substr_count( $html, 'data-audit-id="competitions-gala-past-photo-0"' ) )->toBe( 1 );
	expect( substr_count( $html, 'data-ink-aid="competitions-gala-past-photo-0"' ) )->toBe( 1 );
	expect( $html )->toContain( 'data-ink-gala-paneel="2024" hidden' );
	expect( $html )->toContain( '<button type="button" class="ink-btn ink-btn--lg ink-btn--primary" data-audit-id="competitions-gala-btn-reserve"' );
	expect( $html )->not->toContain( 'LET WEL' );
} );

test( 'gala: each column hides alone, and the band with both empty', function (): void {
	expect( Gala::toHtml( null, array(), array() ) )->toBe( '' );
	expect( Gala::toHtml( ink_komp_edition(), array(), array() ) )->toBe( '' );

	$past_only = Gala::toHtml( null, array( new GalaYear( 2025, 'Hoog', array() ) ), array() );
	expect( $past_only )->toContain( 'competitions-gala-past' );
	expect( $past_only )->not->toContain( 'competitions-gala-details' );
	expect( $past_only )->not->toContain( 'competitions-gala-past-grid' );

	$details_only = Gala::toHtml( ink_komp_edition( array( 'gala_naam' => 'Gala' ) ), array(), array() );
	expect( $details_only )->toContain( 'competitions-gala-details' );
	expect( $details_only )->not->toContain( 'competitions-gala-past' );
} );

test( 'the mailto string is the ruled one', function (): void {
	expect( Directory::mailto() )->toBe( 'mailto:skryfafrikaans@ink.org.za?subject=Nuwe%20kompetisie%20kennisgewing' );
	expect( Directory::toHtml( array() ) )->toContain( 'href="mailto:skryfafrikaans@ink.org.za?subject=Nuwe%20kompetisie%20kennisgewing"' );
} );

test( 'directory: blank fee is free, external link attributes, optional note', function (): void {
	$html = Directory::toHtml( array( ink_komp_listing(), ink_komp_listing( array( 'fooi' => 'R50', 'nota' => 'N' ) ) ) );

	expect( $html )->toContain( 'data-audit-id="competitions-external"' );
	expect( $html )->toContain( 'data-audit-id="competitions-external-1"' );
	expect( $html )->toContain( 'data-fooi="gratis"' );
	expect( $html )->toContain( 'data-fooi="betaal"' );
	expect( $html )->toContain( 'target="_blank" rel="noopener noreferrer"' );
	expect( $html )->not->toContain( 'competitions-external-0-note' );
	expect( $html )->toContain( 'competitions-external-1-note' );
	expect( $html )->toContain( 'data-ink-aid="competitions-external-empty"' );
} );

test( 'directory: no listing shows the empty state with no clear button', function (): void {
	$html = Directory::toHtml( array() );

	expect( $html )->toContain( 'data-audit-id="competitions-external-empty"' );
	expect( $html )->not->toContain( 'competitions-external-empty-btn' );
	expect( $html )->toContain( 'data-ink-aid="competitions-external-grid"' );
} );

test( 'the tenth listing and later carry no audit ids', function (): void {
	$html = Directory::toHtml( array_fill( 0, 10, ink_komp_listing() ) );

	expect( $html )->toContain( 'competitions-external-8-link-icon' );
	expect( $html )->not->toContain( 'competitions-external-9' );
} );
