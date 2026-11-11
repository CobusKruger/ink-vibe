<?php
/**
 * Unit tests for the announcement route `/kompetisies/<jaar>/` (K8, `05-kompetisies` §14.8).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Ink\Competitions\AankondigingPage;
use Ink\Competitions\Data;
use Ink\Competitions\Edition;
use Ink\Competitions\GalaPage;
use Ink\Competitions\UitslaePage;
use Ink\Kernel\SystemPages;
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
 * An edition of a year.
 *
 * @param int                   $year   The year.
 * @param array<string, string> $values Extra field values.
 * @param int                   $post   Post id seed.
 * @return Edition
 */
function ink_aankondiging_edition( int $year, array $values = array(), int $post = 1 ): Edition {
	return new Edition( $post, 'Ink Kompetisie ' . $year, array_merge( array( 'jaar' => (string) $year ), $values ) );
}

test( 'the route is registered for the template guard, and the template and pattern exist', function (): void {
	expect( SystemPages::routeOf( AankondigingPage::TEMPLATE, array() ) )->toBe( 'endpoint' );
	expect( AankondigingPage::QUERY_VAR )->toBe( 'ink_aankondiging_jaar' );
	expect( AankondigingPage::QUERY_VAR )->not->toBe( UitslaePage::QUERY_VAR );
	expect( AankondigingPage::QUERY_VAR )->not->toBe( GalaPage::QUERY_VAR );
	expect( is_file( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/templates/' . AankondigingPage::TEMPLATE . '.html' ) )->toBeTrue();
	expect( is_file( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/patterns/' . AankondigingPage::TEMPLATE . '.php' ) )->toBeTrue();
	expect( AankondigingPage::path( 2026 ) )->toBe( '/kompetisies/2026/' );
} );

test( 'only a real edition with a description has an announcement page', function (): void {
	$new      = ink_aankondiging_edition( 2026, array( 'beskrywing' => 'Skryf in.' ), 3 );
	$imported = ink_aankondiging_edition( 2025, array(), 2 );
	$default  = ink_aankondiging_edition( 2026, array( 'beskrywing' => 'Binnekort.', 'standaard' => 'ja' ), 1 );
	$all      = array( $new, $imported, $default );

	expect( Data::pickAnnouncementEdition( $all, 2026 ) )->toBe( $new );
	expect( Data::pickAnnouncementEdition( $all, 2025 ) )->toBeNull();
	expect( Data::pickAnnouncementEdition( $all, 1999 ) )->toBeNull();
} );

test( 'the page: heading, description, dates, categories, gala and tickets; blank parts do not render', function (): void {
	$edition = ink_aankondiging_edition(
		2026,
		array(
			'uitgawe'         => '10de',
			'beskrywing'      => 'Skryf in vir die groot kompetisie.',
			'sluitingsdatum'  => '2026-08-31',
			'kat1_naam'       => 'Gedigte',
			'kat1_riglyne'    => "Maksimum 40 reëls\nGeen titel nodig",
			'gala_naam'       => 'Ink se Gala',
			'gala_plek'       => 'Pretoria',
			'gala_program'    => "Verwelkoming\nPrysuitreiking",
			'kaartjie1_etiket' => 'Gewone kaartjie',
			'kaartjie1_prys'  => 'R150',
		)
	);
	$html    = AankondigingPage::toHtml( $edition, '/kompetisies/' );

	expect( $html )->toContain( '<h1' )->toContain( 'Ink Kompetisie 2026' )
		->toContain( 'Skryf in vir die groot kompetisie.' )
		->toContain( 'Sluit ' )
		->toContain( 'Gedigte' )->toContain( 'Maksimum 40 reëls' )
		->toContain( 'Ink se Gala' )->toContain( 'Plek: Pretoria' )->toContain( 'Prysuitreiking' )
		->toContain( 'Gewone kaartjie R150' )
		->toContain( '10de' )
		->not->toContain( 'data-audit-id' )
		->not->toContain( 'Uitslae 2026' )
		->not->toContain( "Gala-foto's 2026" );

	$plain = AankondigingPage::toHtml( ink_aankondiging_edition( 2026, array( 'beskrywing' => 'Net teks.' ) ), '/kompetisies/' );

	expect( $plain )->not->toContain( '<ul>' )->not->toContain( '<h2' );
} );

test( 'the page links to the results and the gala photos only when they exist', function (): void {
	$edition = ink_aankondiging_edition( 2026, array( 'beskrywing' => 'Teks.' ) );
	$html    = AankondigingPage::toHtml( $edition, '/kompetisies/', '/kompetisies/2026/uitslae/', '/kompetisies/gala/2026/' );

	expect( substr_count( $html, '/kompetisies/2026/uitslae/' ) )->toBe( 1 );
	expect( substr_count( $html, '/kompetisies/gala/2026/' ) )->toBe( 1 );
} );
