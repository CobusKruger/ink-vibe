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

test( 'the hero badge is sentence case: no caps class', function (): void {
	$html = Hero::toHtml( ink_komp_edition( array( 'uitgawe' => 'U' ) ), '/skryf/' );

	expect( $html )->toContain( 'ink-pill--gold"' );
	expect( $html )->not->toContain( 'gold-caps' );
} );

test( 'the default announcement is a full hero, with no pills and no way to enter', function (): void {
	$html = Hero::toHtml( Edition::builtIn(), '/skryf/' );

	expect( $html )->toContain( 'competitions-hero-h1' );
	expect( $html )->toContain( 'Ons jaarlikse kompetisie' );
	expect( $html )->toContain( 'competitions-hero-badge' );
	expect( $html )->toContain( 'competitions-hero-text' );
	expect( $html )->not->toContain( 'competitions-hero-btn-primary' );
	expect( $html )->not->toContain( 'competitions-hero-btn-guidelines' );
	expect( $html )->not->toContain( 'competitions-hero-meta' );
} );

test( 'an editor-written default shows her text; a date she adds still shows as a pill', function (): void {
	$html = Hero::toHtml( new Edition( 7, 'Groot nuus', array( 'standaard' => 'ja', 'beskrywing' => 'Bly ingelig.', 'sluitingsdatum' => 'Later' ) ), '/skryf/' );

	expect( $html )->toContain( 'Groot nuus' );
	expect( $html )->toContain( 'Bly ingelig.' );
	expect( $html )->toContain( 'competitions-hero-meta-close' );
	expect( $html )->not->toContain( 'competitions-hero-btn-primary' );
} );

test( 'categories: the editor\'s heading and intro replace the standard words', function (): void {
	$own = Categories::toHtml( ink_komp_edition( array( 'kat1_naam' => 'Een', 'kat_titel' => 'My opskrif', 'kat_inleiding' => 'My inleiding' ) ), '/skryf/' );
	$std = Categories::toHtml( ink_komp_edition( array( 'kat1_naam' => 'Een', 'naaswenner_nota' => 'Naaswenners ook.' ) ), '/skryf/' );

	expect( $own )->toContain( 'My opskrif' );
	expect( $own )->toContain( 'My inleiding' );
	expect( $own )->not->toContain( 'Ons kompetisie word anoniem beoordeel.' );
	expect( $std )->toContain( 'Ons kompetisie word anoniem beoordeel. Naaswenners ook.' );
} );

test( 'categories of the default announcement show without Enter buttons; none means no section', function (): void {
	$html = Categories::toHtml( new Edition( 7, 'Nuus', array( 'standaard' => 'ja', 'kat1_naam' => 'Gedigte', 'kat2_naam' => 'Stories' ) ), '/skryf/' );

	expect( $html )->toContain( 'competitions-category-1-body' );
	expect( $html )->not->toContain( 'href="/skryf/"' );
	expect( Categories::toHtml( Edition::builtIn(), '/skryf/' ) )->toBe( '' );
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

test( 'gala: the band hides only with no gala at all; a planned gala stands alone', function (): void {
	expect( Gala::toHtml( null, array(), array() ) )->toBe( '' );
	expect( Gala::toHtml( ink_komp_edition(), array(), array() ) )->toBe( '' );

	$details_only = Gala::toHtml( ink_komp_edition( array( 'gala_naam' => 'Gala' ) ), array(), array() );
	expect( $details_only )->toContain( 'competitions-gala-details' );
	expect( $details_only )->not->toContain( 'competitions-gala-past' );
} );

test( 'gala with no open competition: the last gala\'s details, labelled as past, no tickets or Reserve', function (): void {
	$last = new Edition( 9, 'INK Gala 2025', array( 'jaar' => '2025', 'gala_plek' => 'Die Saal', 'gala_beskrywing' => "Wat 'n aand.", 'kaartjie1_etiket' => 'Algemeen', 'kaartjie1_prys' => 'R1', 'gala_foto1' => '5' ) );
	$html = Gala::toHtml( null, array( GalaYear::fromEdition( $last ) ), array( 2025 => array( array( 'url' => '/a.jpg', 'caption' => 'A' ) ) ) );

	expect( $html )->toContain( 'competitions-gala-details' );
	expect( $html )->toContain( 'Die vorige gala · 2025' );
	expect( $html )->toContain( 'INK Gala 2025' );
	expect( $html )->toContain( 'Die Saal' );
	expect( $html )->toContain( "Wat 'n aand." );
	expect( $html )->not->toContain( 'competitions-gala-btn-reserve' );
	expect( $html )->not->toContain( 'competitions-gala-ticket' );
	expect( $html )->toContain( 'competitions-gala-past-photo-0' );
} );

test( 'gala: a past gala with nothing but photos still gets a titled details column', function (): void {
	$html = Gala::toHtml( null, array( new GalaYear( 2024, '', array() ) ), array() );

	expect( $html )->toContain( 'competitions-gala-h2' );
	expect( $html )->toContain( 'Ink se Presige Gala-aand 2024' );
	expect( $html )->not->toContain( 'competitions-gala-info' );
	expect( $html )->not->toContain( 'competitions-gala-text' );
} );

test( 'gala: the current edition\'s gala becomes a past gala once it has photos', function (): void {
	$done = ink_komp_edition( array( 'gala_naam' => 'Gala 2026', 'jaar' => '2026', 'kaartjie1_etiket' => 'Algemeen', 'gala_foto1' => '5' ) );
	$html = Gala::toHtml( $done, array( GalaYear::fromEdition( $done ) ), array() );

	expect( $html )->toContain( 'Die vorige gala · 2026' );
	expect( $html )->not->toContain( 'competitions-gala-btn-reserve' );
} );

test( 'gala: five feature photos make one wide and four small; their own captions win', function (): void {
	$photos = array();

	foreach ( range( 0, 4 ) as $n ) {
		$photos[] = array( 'url' => "/p{$n}.jpg", 'caption' => "Foto {$n}" );
	}

	$html = Gala::toHtml( null, array( new GalaYear( 2025, '', range( 1, 5 ) ) ), array( 2025 => $photos ) );

	expect( substr_count( $html, 'class="ink-photo"' ) )->toBe( 5 );
	expect( substr_count( $html, 'ink-komp-gala__foto--breed' ) )->toBe( 1 );
	expect( $html )->toContain( 'competitions-gala-past-photo-4' );
	expect( $html )->toContain( 'Foto 3' );
	expect( $html )->not->toContain( 'Sien alles' ); // Nothing beyond the five.
} );

test( 'directory: external link attributes, optional note', function (): void {
	$html = Directory::toHtml( array( ink_komp_listing(), ink_komp_listing( array( 'fooi' => 'R50', 'nota' => 'N' ) ) ) );

	expect( $html )->toContain( 'data-audit-id="competitions-external"' );
	expect( $html )->toContain( 'data-audit-id="competitions-external-1"' );
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
