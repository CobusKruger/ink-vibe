<?php
/**
 * Unit tests for K3: the hero and the gala band follow the edition state (`05-kompetisies` §14.2).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Competitions\Data;
use Ink\Competitions\Edition;
use Ink\Competitions\Gala;
use Ink\Competitions\GalaYear;
use Ink\Competitions\Hero;

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
 * A real, current edition with these fields.
 *
 * @param int                   $id     The post id.
 * @param array<string, string> $values Field values.
 */
function k3_edition( int $id, array $values ): Edition {
	return new Edition( $id, 'Kompetisie ' . $id, $values + array( 'beskrywing' => 'B', 'jaar' => '2026' ) );
}

/**
 * The stored default announcement.
 */
function k3_default(): Edition {
	return new Edition( 99, 'Standaard', array( 'standaard' => 'ja', 'beskrywing' => 'Generies' ) );
}

test( 'pickShown: its own announcement only while announced or open, for either type', function ( array $values, bool $own ): void {
	$real   = k3_edition( 1, $values );
	$picked = Data::pickShown( array( $real, k3_default() ), new \DateTimeImmutable( '2026-03-01' ) );

	expect( $picked === $real )->toBe( $own );
	expect( $picked->isDefault() )->toBe( ! $own );
} )->with( array(
	'announced jaarliks' => array( array( 'oopdatum' => '2026-04-01', 'sluitingsdatum' => '2026-05-31' ), true ),
	'announced vennoot'  => array( array( 'oopdatum' => '2026-04-01', 'sluitingsdatum' => '2026-05-31', 'tipe' => 'vennoot' ), true ),
	'open jaarliks'      => array( array( 'sluitingsdatum' => '2026-05-31' ), true ),
	'open vennoot'       => array( array( 'sluitingsdatum' => '2026-05-31', 'tipe' => 'vennoot' ), true ),
	'closed jaarliks'    => array( array( 'sluitingsdatum' => '2026-02-01' ), false ),
	'closed vennoot'     => array( array( 'sluitingsdatum' => '2026-02-01', 'tipe' => 'vennoot' ), false ),
	'results jaarliks'   => array( array( 'sluitingsdatum' => '2026-05-31', 'uitslae_gepubliseer' => 'ja' ), false ),
	'results vennoot'    => array( array( 'sluitingsdatum' => '2026-05-31', 'uitslae_gepubliseer' => 'ja', 'tipe' => 'vennoot' ), false ),
) );

test( 'pickShown: an archive edition falls back to the default', function (): void {
	$old   = k3_edition( 1, array( 'jaar' => '2025', 'sluitingsdatum' => '2099-01-01' ) );
	$newer = k3_edition( 2, array( 'jaar' => '2027', 'beskrywing' => '' ) ); // Newer, but no description: not current.

	expect( Data::pickShown( array( $old, $newer, k3_default() ), new \DateTimeImmutable( '2026-03-01' ) )->isDefault() )->toBeTrue();
} );

test( 'pickShown: no editions falls back to the built-in announcement', function (): void {
	$shown = Data::pickShown( array(), new \DateTimeImmutable( '2026-03-01' ) );

	expect( $shown->isDefault() )->toBeTrue()->and( $shown->acceptsEntries() )->toBeFalse();
} );

test( 'pickShown: a stored default post wins over the built-in one', function (): void {
	$default = k3_default();

	expect( Data::pickShown( array( k3_edition( 1, array() ), $default ), new \DateTimeImmutable( '2026-06-01' ) ) )->toBe( $default );
} );

test( 'pickResultsHero: only the latest edition in state results', function (): void {
	$now     = new \DateTimeImmutable( '2026-06-01' );
	$done    = k3_edition( 1, array( 'jaar' => '2026', 'uitslae_gepubliseer' => 'ja' ) );
	$old     = k3_edition( 2, array( 'jaar' => '2025', 'uitslae_gepubliseer' => 'ja' ) );
	$default = new Edition( 99, 'S', array( 'standaard' => 'ja', 'uitslae_gepubliseer' => 'ja', 'jaar' => '2030' ) );

	expect( Data::pickResultsHero( array( $old, $done, $default ), $now ) )->toBe( $done );
	expect( Data::pickResultsHero( array( $old ), $now ) )->toBe( $old );
	expect( Data::pickResultsHero( array( $old, k3_edition( 3, array( 'jaar' => '2027' ) ) ), $now ) )->toBeNull(); // Newer exists: archive.
	expect( Data::pickResultsHero( array( k3_edition( 4, array() ) ), $now ) )->toBeNull(); // No published results.
	expect( Data::pickResultsHero( array(), $now ) )->toBeNull();
} );

test( 'hero: the results line links to the results route; absent otherwise', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	$with = Hero::toHtml( k3_default(), '/skryf/', '/kompetisies/2026/uitslae/', 2026 );

	expect( $with )->toContain( 'href="/kompetisies/2026/uitslae/"' )
		->and( $with )->toContain( 'Nou bekend: Die wenners van die 2026 Ink Kompetisie' )
		->and( $with )->not->toContain( 'href="/skryf/"' ) // The generic announcement has no Enter.
		->and( Hero::toHtml( k3_default(), '/skryf/' ) )->not->toContain( 'Nou bekend' );
} );

test( 'hero: an open edition keeps its Enter buttons to /skryf/', function (): void {
	$html = Hero::toHtml( k3_edition( 1, array( 'sluitingsdatum' => 'Julie' ) ), '/skryf/' ); // Not a date: no wp_date.

	expect( $html )->toContain( 'competitions-hero-btn-primary" href="/skryf/"' )->and( $html )->not->toContain( 'Nou bekend' );
} );

test( 'gala: a vennoot edition shows no band and no gala content, even with past galas', function (): void {
	$vennoot = k3_edition( 1, array( 'tipe' => 'vennoot', 'gala_naam' => 'Gala' ) );

	expect( Gala::toHtml( $vennoot, array( new GalaYear( 2025, 'Hoog', array( 1 ) ) ), array() ) )->toBe( '' );
} );

test( 'gala: a closed jaarliks edition with a planned gala keeps tickets and Reserve, with no results link', function (): void {
	$closed = k3_edition(
		1,
		array(
			'sluitingsdatum'   => '2026-02-01',
			'gala_naam'        => 'Gala 2026',
			'kaartjie1_etiket' => 'Algemeen',
			'kaartjie1_prys'   => 'R100',
		)
	);
	$html   = Gala::toHtml( $closed, array(), array() );

	expect( $html )->toContain( 'Gala 2026' )
		->and( $html )->toContain( 'competitions-gala-ticket-0' )
		->and( $html )->toContain( 'competitions-gala-btn-reserve' )
		->and( $html )->not->toContain( 'uitslae' );
} );

test( 'past galas leave out vennoot editions', function (): void {
	$vennoot = k3_edition( 1, array( 'tipe' => 'vennoot', 'jaar' => '2024', 'gala_hoogtepunt' => 'X' ) );
	$annual  = k3_edition( 2, array( 'jaar' => '2025', 'gala_hoogtepunt' => 'Y' ) );

	expect( Data::pickPastGalas( array( $vennoot, $annual ) ) )->toHaveCount( 1 );
} );
