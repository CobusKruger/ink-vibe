<?php
/**
 * Unit tests for the competition winner records (K4, `05-kompetisies` §14.4).
 *
 * Targets: {@see \Ink\Content\Repeater}, {@see \Ink\Content\FieldSets} (winner field, sanitiser),
 * {@see \Ink\Competitions\Winner}, {@see \Ink\Competitions\Edition}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Competitions\Edition;
use Ink\Competitions\Winner;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Content\Repeater;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->alias( static fn ( $v ) => trim( strip_tags( (string) $v ) ) );
	Functions\when( 'sanitize_textarea_field' )->alias( static fn ( $v ) => trim( strip_tags( (string) $v ) ) );
	Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	// Users 7 and 9 exist; anyone else has been deleted.
	Functions\when( 'get_userdata' )->alias( static fn ( $id ) => in_array( (int) $id, array( 7, 9 ), true ) ? (object) array( 'ID' => (int) $id ) : false );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A posted winner row.
 *
 * @param array<string, scalar> $cells Cells over a blank row.
 * @return array<string, scalar>
 */
function k4_row( array $cells ): array {
	return $cells + array(
		'toekenning'       => '',
		'wenner_gebruiker' => '',
		'wenner_naam'      => '',
		'inskrywing'       => '',
		'foto'             => '',
		'aantekening'      => '',
	);
}

/**
 * Edition with winner records stored as the sanitiser writes them.
 *
 * @param array<int, array<string, scalar>> $rows   Posted rows.
 * @param array<string, string>             $values Other field values.
 */
function k4_edition( array $rows, array $values = array() ): Edition {
	return new Edition( 5, 'Kompetisie 2025', $values + array( 'wenners' => FieldSets::sanitizeWinners( $rows ) ) );
}

it( 'reads rows from a posted array in order, skipping the marker and blank rows', function (): void {
	$posted = array(
		Repeater::PRESENT => '1',
		0                 => array( 'a' => 'eerste' ),
		1                 => array( 'a' => '  ' ),
		2                 => 'junk',
		3                 => array( 'a' => 'tweede', 'b' => array( 'nested' ) ),
	);

	expect( Repeater::rows( $posted ) )->toBe( array( array( 'a' => 'eerste' ), array( 'a' => 'tweede' ) ) )
		->and( Repeater::rows( 'not json' ) )->toBe( array() )
		->and( Repeater::rows( 42 ) )->toBe( array() );
} );

it( 'caps the rows a repeater keeps', function (): void {
	$posted = array_fill( 0, Repeater::MAX_ROWS + 20, array( 'a' => 'x' ) );

	expect( Repeater::rows( $posted ) )->toHaveCount( Repeater::MAX_ROWS );
} );

it( 'round-trips unicode and quotes through the stored JSON unchanged', function (): void {
	$rows = array( array( 'naam' => 'Eloïse "Elmarie" \\ Schoeman – ŉ storie' ) );

	expect( Repeater::decode( Repeater::encode( $rows ) ) )->toBe( $rows )
		->and( Repeater::encode( array() ) )->toBe( '' );
} );

it( 'declares the winner field as a repeater of the six columns, stored under wenners', function (): void {
	$method = new \ReflectionMethod( FieldSets::class, 'definitions' );
	$method->setAccessible( true );
	$fields = array();

	foreach ( $method->invoke( null )[ PostTypes::KOMPETISIE ]['fields'] as $field ) {
		$fields[ $field['key'] ] = $field;
	}

	expect( $fields )->toHaveKeys( array( 'ink_kompetisie_wenners', 'ink_kompetisie_uitslae_gepubliseer', 'ink_kompetisie_uitslae_inleiding' ) )
		->and( $fields['ink_kompetisie_wenners']['input'] )->toBe( 'repeater' )
		->and( array_column( $fields['ink_kompetisie_wenners']['columns'], 'key' ) )->toBe( Winner::COLUMNS )
		->and( $fields['ink_kompetisie_uitslae_gepubliseer']['default'] )->toBe( 'nee' )
		->and( FieldSets::usesRepeaterInputs( PostTypes::KOMPETISIE ) )->toBeTrue()
		->and( FieldSets::usesRepeaterInputs( PostTypes::GEDIG ) )->toBeFalse();
} );

it( 'keeps winners in the posted order and every column', function (): void {
	$edition = k4_edition(
		array(
			k4_row( array( 'toekenning' => 'Gedigte: eerste', 'wenner_gebruiker' => '7', 'inskrywing' => 'Water', 'foto' => '31', 'aantekening' => 'Pragtig.' ) ),
			k4_row( array( 'toekenning' => 'Boekprys', 'wenner_naam' => 'Sarel Botha' ) ),
		)
	);
	$winners = $edition->winners();

	expect( $winners )->toHaveCount( 2 )
		->and( $winners[0] )->toBeInstanceOf( Winner::class )
		->and( $winners[0]->award )->toBe( 'Gedigte: eerste' )
		->and( $winners[0]->userId )->toBe( 7 )
		->and( $winners[0]->hasUser() )->toBeTrue()
		->and( $winners[0]->name )->toBe( '' )
		->and( $winners[0]->entryTitle )->toBe( 'Water' )
		->and( $winners[0]->photoId )->toBe( 31 )
		->and( $winners[0]->hasPhoto() )->toBeTrue()
		->and( $winners[0]->note )->toBe( 'Pragtig.' )
		->and( $winners[1]->award )->toBe( 'Boekprys' )
		->and( $winners[1]->hasUser() )->toBeFalse()
		->and( $winners[1]->hasPhoto() )->toBeFalse()
		->and( $winners[1]->name )->toBe( 'Sarel Botha' );
} );

it( 'accepts a non-competition award with a free-text name and no account', function (): void {
	$winners = k4_edition( array( k4_row( array( 'toekenning' => 'Uitstaande bydrae op die Facebook-groep', 'wenner_naam' => 'Annelie' ) ) ) )->winners();

	expect( $winners )->toHaveCount( 1 )->and( $winners[0]->award )->toBe( 'Uitstaande bydrae op die Facebook-groep' );
} );

it( 'drops a record with no award, or with neither a user nor a name', function (): void {
	$edition = k4_edition(
		array(
			k4_row( array( 'wenner_naam' => 'Sonder toekenning' ) ),
			k4_row( array( 'toekenning' => 'Sonder wenner' ) ),
			k4_row( array( 'toekenning' => 'Net geldig', 'wenner_naam' => 'Jan' ) ),
		)
	);

	expect( $edition->winners() )->toHaveCount( 1 )->and( $edition->winners()[0]->award )->toBe( 'Net geldig' );
} );

it( 'does not let a deleted or unknown user satisfy the user-or-name rule', function (): void {
	$edition = k4_edition(
		array(
			k4_row( array( 'toekenning' => 'A', 'wenner_gebruiker' => '404' ) ),
			k4_row( array( 'toekenning' => 'B', 'wenner_gebruiker' => '404', 'wenner_naam' => 'Reg met naam' ) ),
		)
	);

	expect( $edition->winners() )->toHaveCount( 1 )
		->and( $edition->winners()[0]->award )->toBe( 'B' )
		->and( $edition->winners()[0]->userId )->toBe( 0 );
} );

it( 'sanitises cells: tags stripped, negative and junk ids to zero, long text cut', function (): void {
	$json = FieldSets::sanitizeWinners(
		array(
			k4_row(
				array(
					'toekenning'       => '<b>Prys</b>' . str_repeat( 'x', 500 ),
					'wenner_gebruiker' => '-7',
					'wenner_naam'      => '<script>alert(1)</script>Naam',
					'foto'             => 'abc',
					'aantekening'      => str_repeat( 'n', 2000 ),
				)
			),
		)
	);
	$row  = Repeater::decode( $json )[0];

	expect( mb_strlen( $row['toekenning'] ) )->toBe( FieldSets::WINNER_AWARD_MAX )
		->and( $row['toekenning'] )->toStartWith( 'Prys' )
		->and( $row['wenner_gebruiker'] )->toBe( 7 )
		->and( $row['wenner_naam'] )->not->toContain( '<' )
		->and( $row['foto'] )->toBe( 0 )
		->and( mb_strlen( $row['aantekening'] ) )->toBe( FieldSets::WINNER_NOTE_MAX );
} );

it( 'accepts the stored JSON back (the REST path) and is idempotent', function (): void {
	$once = FieldSets::sanitizeWinners( array( k4_row( array( 'toekenning' => 'Prys', 'wenner_naam' => 'Wiëtse "Wit"' ) ) ) );

	expect( FieldSets::sanitizeWinners( $once ) )->toBe( $once )
		->and( FieldSets::sanitizeWinners( '' ) )->toBe( '' )
		->and( FieldSets::sanitizeWinners( null ) )->toBe( '' );
} );

it( 'stores no winners as an empty string, and an edition reads none from junk', function (): void {
	expect( FieldSets::sanitizeWinners( array( Repeater::PRESENT => '1' ) ) )->toBe( '' )
		->and( ( new Edition( 1, 'x', array( 'wenners' => '{broken' ) ) )->winners() )->toBe( array() )
		->and( ( new Edition( 1, 'x', array() ) )->winners() )->toBe( array() );
} );

it( 'ignores stored records that are no longer valid when reading', function (): void {
	$edition = new Edition( 1, 'x', array( 'wenners' => '[{"toekenning":"","wenner_naam":"X"},{"toekenning":"Y"},{"toekenning":"Z","wenner_naam":"Z"}]' ) );

	expect( $edition->winners() )->toHaveCount( 1 )->and( $edition->winners()[0]->award )->toBe( 'Z' );
} );

it( 'groups winners by award in first-seen order, keeping record order inside an award', function (): void {
	$groups = k4_edition(
		array(
			k4_row( array( 'toekenning' => 'Kortverhale', 'wenner_naam' => 'Een' ) ),
			k4_row( array( 'toekenning' => 'Gedigte', 'wenner_naam' => 'Twee' ) ),
			k4_row( array( 'toekenning' => 'Kortverhale', 'wenner_naam' => 'Drie' ) ),
		)
	)->winnersByAward();

	expect( array_column( $groups, 'award' ) )->toBe( array( 'Kortverhale', 'Gedigte' ) )
		->and( array_map( static fn ( Winner $w ): string => $w->name, $groups[0]['winners'] ) )->toBe( array( 'Een', 'Drie' ) );
} );

it( 'splits the results intro into paragraphs on blank lines', function (): void {
	$edition = new Edition( 1, 'x', array( 'uitslae_inleiding' => "Eerste.\nNog 'n reël.\n\n\nTweede." ) );

	expect( $edition->resultsIntro() )->toBe( array( "Eerste.\nNog 'n reël.", 'Tweede.' ) )
		->and( ( new Edition( 1, 'x', array() ) )->resultsIntro() )->toBe( array() );
} );

it( 'sanitises the results flag to ja or nee', function (): void {
	expect( FieldSets::sanitizeResultsFlag( 'ja' ) )->toBe( 'ja' )
		->and( FieldSets::sanitizeResultsFlag( 'nee' ) )->toBe( 'nee' )
		->and( FieldSets::sanitizeResultsFlag( 'JA' ) )->toBe( 'nee' )
		->and( FieldSets::sanitizeResultsFlag( 1 ) )->toBe( 'nee' );
} );
