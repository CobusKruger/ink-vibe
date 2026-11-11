<?php
/**
 * Unit tests for a competition's type and derived state (K1, `05-kompetisies` §14.1-14.2).
 *
 * Targets: {@see \Ink\Competitions\Edition}, {@see \Ink\Competitions\EditionState},
 * {@see \Ink\Competitions\Data}, {@see \Ink\Kernel\CompetitionType}, {@see \Ink\Content\FieldSets}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Competitions\Data;
use Ink\Competitions\Edition;
use Ink\Competitions\EditionState;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\CompetitionType;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An edition with these values.
 *
 * @param array<string, string> $values Field values by suffix.
 * @param int                   $id     Post id.
 */
function k1_edition( array $values = array(), int $id = 1 ): Edition {
	return new Edition( $id, 'Kompetisie', $values );
}

/**
 * The `kompetisie` field definitions (the map is private; read it as the admin box does).
 *
 * @return list<array<string, mixed>>
 */
function k1_fields(): array {
	$method = new \ReflectionMethod( FieldSets::class, 'definitions' );
	$method->setAccessible( true );

	return $method->invoke( null )[ PostTypes::KOMPETISIE ]['fields'];
}

function k1_day( string $date ): \DateTimeImmutable {
	return new \DateTimeImmutable( $date . ' 12:00:00' );
}

it( 'has the two types, annual first and by default', function (): void {
	expect( CompetitionType::values() )->toBe( array( 'jaarliks', 'vennoot' ) )
		->and( CompetitionType::default() )->toBe( CompetitionType::Jaarliks )
		->and( CompetitionType::Jaarliks->hasGala() )->toBeTrue()
		->and( CompetitionType::Vennoot->hasGala() )->toBeFalse();
} );

it( 'folds a missing or junk type to annual', function (): void {
	foreach ( array( '', null, 'weekliks', 42, array() ) as $stored ) {
		expect( CompetitionType::fromMeta( $stored ) )->toBe( CompetitionType::Jaarliks );
	}
} );

it( 'reads the type from the post, defaulting existing posts to annual', function (): void {
	expect( k1_edition()->type() )->toBe( CompetitionType::Jaarliks )
		->and( k1_edition( array( 'tipe' => 'vennoot' ) )->type() )->toBe( CompetitionType::Vennoot )
		->and( k1_edition( array( 'tipe' => 'rommel' ) )->type() )->toBe( CompetitionType::Jaarliks );
} );

it( 'derives the state from the dates, both ends inclusive', function ( string $now, EditionState $expected ): void {
	$edition = k1_edition( array( 'oopdatum' => '2026-02-01', 'sluitingsdatum' => '2026-05-31' ) );

	expect( $edition->state( k1_day( $now ) ) )->toBe( $expected );
} )->with(
	array(
		'before opening'     => array( '2026-01-31', EditionState::Announced ),
		'opening day'        => array( '2026-02-01', EditionState::Open ),
		'mid window'         => array( '2026-03-15', EditionState::Open ),
		'closing day'        => array( '2026-05-31', EditionState::Open ),
		'day after closing'  => array( '2026-06-01', EditionState::Closed ),
	)
);

it( 'treats a blank open date as already open, but a blank or bad close date as closed', function (): void {
	$now = k1_day( '2026-03-01' );

	expect( k1_edition( array( 'sluitingsdatum' => '2026-05-31' ) )->state( $now ) )->toBe( EditionState::Open )
		->and( k1_edition( array( 'oopdatum' => '2026-02-01' ) )->state( $now ) )->toBe( EditionState::Closed )
		->and( k1_edition( array( 'oopdatum' => '2026-02-01', 'sluitingsdatum' => 'volgende week' ) )->state( $now ) )->toBe( EditionState::Closed )
		->and( k1_edition()->state( $now ) )->toBe( EditionState::Closed );
} );

it( 'is archive once a newer edition exists, whatever its dates', function (): void {
	$edition = k1_edition( array( 'oopdatum' => '2026-02-01', 'sluitingsdatum' => '2026-05-31' ) );

	expect( $edition->state( k1_day( '2026-03-01' ), true ) )->toBe( EditionState::Archive );
} );

it( 'never stores the state', function (): void {
	$keys = array_column( k1_fields(), 'key' );

	expect( $keys )->not->toContain( 'ink_kompetisie_state' )->not->toContain( 'ink_kompetisie_staat' );
} );

it( 'has published results only when the editor sets the flag (K4), and then the state is results', function (): void {
	$dates = array( 'oopdatum' => '2025-02-01', 'sluitingsdatum' => '2025-05-31' );
	$later = k1_day( '2025-09-01' );

	expect( k1_edition( $dates )->hasPublishedResults() )->toBeFalse()
		->and( k1_edition( $dates + array( 'uitslae_gepubliseer' => 'nee' ) )->hasPublishedResults() )->toBeFalse()
		->and( k1_edition( $dates + array( 'uitslae_gepubliseer' => 'ja' ) )->hasPublishedResults() )->toBeTrue()
		->and( k1_edition( $dates )->state( $later ) )->toBe( EditionState::Closed )
		->and( k1_edition( $dates + array( 'uitslae_gepubliseer' => 'ja' ) )->state( $later ) )->toBe( EditionState::Results )
		->and( k1_edition( $dates + array( 'uitslae_gepubliseer' => 'ja' ) )->state( $later, true ) )->toBe( EditionState::Archive );
} );

it( 'treats the default announcement as generic info: closed, and never newer', function (): void {
	$default = k1_edition( array( 'standaard' => 'ja', 'sluitingsdatum' => '2099-01-01' ), 1 );
	$real    = k1_edition( array( 'jaar' => '2025' ), 2 );

	expect( $default->state( k1_day( '2026-03-01' ), true ) )->toBe( EditionState::Closed )
		->and( Data::hasNewer( $real, array( $default, $real ) ) )->toBeFalse();
} );

it( 'finds a newer edition by year among real editions only', function (): void {
	$old   = k1_edition( array( 'jaar' => '2025' ), 1 );
	$new   = k1_edition( array( 'jaar' => '2026' ), 2 );
	$other = k1_edition( array( 'jaar' => '2025' ), 3 );
	$all   = array( $old, $new, $other );

	expect( Data::hasNewer( $old, $all ) )->toBeTrue()
		->and( Data::hasNewer( $new, $all ) )->toBeFalse()
		->and( Data::hasNewer( $other, array( $other, $old ) ) )->toBeFalse()
		->and( Data::stateOf( $old, $all, k1_day( '2026-03-01' ) ) )->toBe( EditionState::Archive )
		->and( Data::stateOf( $new, $all, k1_day( '2026-03-01' ) ) )->toBe( EditionState::Closed );
} );

it( 'registers tipe, vennoot_naam and oopdatum beside sluitingsdatum', function (): void {
	$fields = array();

	foreach ( k1_fields() as $field ) {
		$fields[ $field['key'] ] = $field;
	}

	$keys = array_keys( $fields );

	expect( $fields )->toHaveKeys( array( 'ink_kompetisie_tipe', 'ink_kompetisie_vennoot_naam', 'ink_kompetisie_oopdatum' ) )
		->and( $fields['ink_kompetisie_tipe']['input'] )->toBe( 'select' )
		->and( $fields['ink_kompetisie_tipe']['default'] )->toBe( 'jaarliks' )
		->and( array_keys( $fields['ink_kompetisie_tipe']['options'] ) )->toBe( CompetitionType::values() )
		->and( $fields['ink_kompetisie_oopdatum']['input'] )->toBe( 'date' )
		->and( array_search( 'ink_kompetisie_oopdatum', $keys, true ) + 1 )->toBe( array_search( 'ink_kompetisie_sluitingsdatum', $keys, true ) );
} );

it( 'sanitises the type to the closed set', function (): void {
	expect( FieldSets::sanitizeCompetitionType( 'vennoot' ) )->toBe( 'vennoot' )
		->and( FieldSets::sanitizeCompetitionType( 'jaarliks' ) )->toBe( 'jaarliks' )
		->and( FieldSets::sanitizeCompetitionType( '' ) )->toBe( 'jaarliks' )
		->and( FieldSets::sanitizeCompetitionType( 'maandeliks' ) )->toBe( 'jaarliks' );
} );
