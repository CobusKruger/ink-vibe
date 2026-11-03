<?php
/**
 * Unit tests for the results read model and win records (WP1).
 *
 * Targets: {@see \Ink\Challenges\RoundResults}, {@see \Ink\Challenges\WinRecords}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Entry;
use Ink\Challenges\ModeratorFeedback;
use Ink\Challenges\Placements;
use Ink\Challenges\RoundResults;
use Ink\Challenges\WinRecords;
use Ink\Content\FieldSets;
use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	ink_reset_guard_spies();
	Functions\when( '__' )->returnArg( 1 );
	\WP_Query::$ink_test_posts = array();
} );

afterEach( function (): void {
	\WP_Query::$ink_test_posts = array();
	Monkey\tearDown();
} );

/**
 * A placed entry, staged for the WP_Query behind Pools::forRound().
 */
function ink_results_post( int $id, string $type, int $author ): \WP_Post {
	$post              = new \WP_Post();
	$post->ID          = $id;
	$post->post_type   = $type;
	$post->post_author = $author;

	return $post;
}

/**
 * Stub one round's placed entries.
 *
 * @param array<int, array{0:string, 1:int}> $meta Post id → [gradering, placement].
 * @param array<int, bool>                   $opted Author → opted in to citations.
 */
function ink_results_stub( array $meta, array $opted = array() ): void {
	Functions\when( 'get_post_meta' )->alias(
		function ( int $id, string $key ) use ( $meta ) {
			return match ( $key ) {
				Entry::GRADERING_META_KEY          => $meta[ $id ][0] ?? '',
				Placements::PLACEMENT_META_KEY     => $meta[ $id ][1] ?? 0,
				default                            => '',
			};
		}
	);
	Functions\when( 'get_post' )->alias(
		function ( int $id ) {
			foreach ( \WP_Query::$ink_test_posts as $post ) {
				if ( $post->ID === $id ) {
					return $post;
				}
			}

			return null;
		}
	);
	Functions\when( 'get_the_title' )->alias( fn ( $post ): string => 'Titel ' . ( is_object( $post ) ? $post->ID : $post ) );
	Functions\when( 'get_the_author_meta' )->alias( fn ( string $f, int $id ): string => 'Skrywer ' . $id );
	Functions\when( 'has_excerpt' )->justReturn( true );
	Functions\when( 'get_the_excerpt' )->justReturn( 'Uittreksel' );
	Functions\when( 'get_user_meta' )->alias( fn ( int $id, string $key ) => ModeratorFeedback::DISPLAY_META === $key && ( $opted[ $id ] ?? false ) ? '1' : '' );
	Functions\when( 'rest_sanitize_boolean' )->alias( fn ( $v ): bool => (bool) $v );
	Functions\when( 'get_post_field' )->alias( fn ( string $f, int $id ) => 'post_author' === $f ? $id * 10 : '' );
	Functions\when( 'get_comments' )->justReturn( array() );
}

test( 'forRound gives one row per placed entry, in the spec shape', function (): void {
	\WP_Query::$ink_test_posts = array( ink_results_post( 5, 'gedig', 50 ), ink_results_post( 6, 'storie', 60 ) );
	ink_results_stub(
		array(
			5 => array( 'goud', 1 ),
			6 => array( 'silwer', 2 ),
		)
	);

	$rows = RoundResults::forRound( 9 );

	expect( $rows )->toHaveCount( 2 );

	$by_id = array_column( $rows, null, 'post_id' );

	expect( $by_id[5] )->toBe(
		array(
			'category' => 'gedig',
			'tier'     => 'goud',
			'position' => 1,
			'post_id'  => 5,
			'title'    => 'Titel 5',
			'author'   => 'Skrywer 50',
			'user_id'  => 50,
			'excerpt'  => 'Uittreksel',
			'citation' => '',
		)
	);
	expect( $by_id[6]['tier'] )->toBe( '' ); // a Storie has no Gradering (D7)
	expect( $by_id[6]['position'] )->toBe( 2 );
} );

test( 'a citation shows only for an author who opted in (D6)', function (): void {
	\WP_Query::$ink_test_posts = array( ink_results_post( 5, 'gedig', 50 ), ink_results_post( 6, 'gedig', 60 ) );
	ink_results_stub(
		array(
			5 => array( 'goud', 1 ),
			6 => array( 'goud', 2 ),
		),
		array( 50 => true )
	);

	// The comments are read for the opted-in author only; the other never gets that far.
	$read = array();
	Functions\when( 'get_comments' )->alias(
		function ( array $args ) use ( &$read ): array {
			$read[] = $args['post_id'];
			return array();
		}
	);

	$by_id = array_column( RoundResults::forRound( 9 ), null, 'post_id' );

	expect( $read )->toBe( array( 5 ) );
	expect( $by_id[6]['citation'] )->toBe( '' );
} );

test( 'the importer plugs in behind the filter, with the round id', function (): void {
	ink_results_stub( array() );

	$no_post = array(
		'category' => 'storie',
		'tier'     => '',
		'position' => 3,
		'post_id'  => 0,
		'title'    => 'Een dollar per jaar',
		'author'   => 'Rika Gerryts',
		'user_id'  => 0,
		'excerpt'  => '',
		'citation' => '',
	);

	Filters\expectApplied( RoundResults::ROWS_FILTER )->once()->with( array(), 9 )->andReturn( array( $no_post ) );

	expect( RoundResults::forRound( 9 ) )->toBe( array( $no_post ) );
} );

test( 'hasResults: a linked post, or a row; neither means no results', function (): void {
	ink_results_stub( array() );

	Functions\when( 'get_posts' )->justReturn( array( 68532 ) );
	expect( RoundResults::hasResults( 9 ) )->toBeTrue();

	Functions\when( 'get_posts' )->justReturn( array() );
	expect( RoundResults::hasResults( 9 ) )->toBeFalse();

	\WP_Query::$ink_test_posts = array( ink_results_post( 5, 'gedig', 50 ) );
	ink_results_stub( array( 5 => array( 'goud', 1 ) ) );
	expect( RoundResults::hasResults( 9 ) )->toBeTrue();

	expect( RoundResults::hasResults( 0 ) )->toBeFalse();
} );

test( 'announcements reads the round link, newest first, and casts', function (): void {
	$args = null;

	Functions\when( 'get_posts' )->alias(
		function ( array $a ) use ( &$args ): array {
			$args = $a;
			return array( '68708', '68700' );
		}
	);

	expect( RoundResults::announcements( 12 ) )->toBe( array( 68708, 68700 ) );
	expect( $args['meta_value'] )->toBe( '12' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['post_status'] )->toBe( 'publish' );
} );

test( 'the letter splits on a blank line; a blank field has no paragraphs', function (): void {
	expect( RoundResults::letterParagraphs( "Eerste.\nNog eerste.\n\n  Tweede.\r\n\r\n\r\nDerde." ) )
		->toBe( array( "Eerste.\nNog eerste.", 'Tweede.', 'Derde.' ) );
	expect( RoundResults::letterParagraphs( "  \n " ) )->toBe( array() );
} );

test( 'mentions read one per line, the writer optional, blanks skipped', function (): void {
	expect( RoundResults::parseMentions( "Lig op water | Anna Smit\n\n  Nag  \nTitel | Met | Bar" ) )->toBe(
		array(
			array( 'title' => 'Lig op water', 'author' => 'Anna Smit' ),
			array( 'title' => 'Nag', 'author' => '' ),
			array( 'title' => 'Titel', 'author' => 'Met | Bar' ),
		)
	);
	expect( RoundResults::parseMentions( '' ) )->toBe( array() );
} );

test( 'mentionsFor and letterFor read the two new fields', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		fn ( int $id, string $key ) => array(
			FieldSets::UITDAGING_VERMELDINGS => 'Lig | Anna',
			FieldSets::UITDAGING_BRIEF       => 'Brief.',
		)[ $key ] ?? ''
	);

	expect( RoundResults::mentionsFor( 9 ) )->toBe( array( array( 'title' => 'Lig', 'author' => 'Anna' ) ) );
	expect( RoundResults::letterFor( 9 ) )->toBe( array( 'Brief.' ) );
} );

// --- Win records -----------------------------------------------------------------------

test( 'recordEntry stores a win from the placed post: round, post, account, position, category, grade, title', function (): void {
	$added = array();

	Functions\when( 'get_post_type' )->justReturn( 'gedig' );
	Functions\when( 'get_post_meta' )->alias(
		function ( int $id, string $key, bool $single = false ) {
			return Entry::GRADERING_META_KEY === $key ? 'goud' : ( $single ? '' : array() );
		}
	);
	Functions\when( 'get_the_title' )->justReturn( "Ma se \"lied\"" );
	Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	Functions\when( 'wp_slash' )->alias( 'addslashes' );
	Functions\when( 'add_post_meta' )->alias(
		function ( int $id, string $key, string $value ) use ( &$added ): int {
			$added[] = array( $id, $key, json_decode( stripslashes( $value ), true ) );
			return 1;
		}
	);

	expect( WinRecords::recordEntry( 9, array( 'post_id' => 5, 'rank' => 2, 'author_id' => 50 ) ) )->toBeTrue();

	expect( $added )->toBe(
		array(
			array(
				9,
				WinRecords::META,
				array(
					'round_id' => 9,
					'post_id'  => 5,
					'user_id'  => 50,
					'position' => 2,
					'category' => 'gedig',
					'grade'    => 'goud',
					'title'    => 'Ma se "lied"',
				),
			),
		)
	);
} );

test( 'a win with no account or post is stored all the same; a bad position is not', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( array() );
	Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	Functions\when( 'wp_slash' )->returnArg( 1 );
	Functions\expect( 'add_post_meta' )->once()->andReturn( 1 );

	expect( WinRecords::record( WinRecords::row( 9, 0, 0, 3, 'storie', '', 'Een dollar per jaar' ) ) )->toBeTrue();
	expect( WinRecords::record( WinRecords::row( 9, 0, 0, 0, 'storie', '', 'x' ) ) )->toBeFalse();
	expect( WinRecords::record( WinRecords::row( 0, 5, 1, 1, 'storie', '', 'x' ) ) )->toBeFalse();
} );

test( 'a row the round already holds is not added twice', function (): void {
	$row = WinRecords::row( 9, 5, 50, 1, 'gedig', 'goud', 'T' );

	Functions\when( 'get_post_meta' )->justReturn( array( json_encode( $row ) ) );
	Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	Functions\when( 'wp_slash' )->returnArg( 1 );
	Functions\expect( 'add_post_meta' )->never();

	expect( WinRecords::record( $row ) )->toBeFalse();
} );

test( 'the lifetime count is a writer win rows over all rounds, found by the account in the JSON', function (): void {
	$row = WinRecords::row( 9, 0, 50, 1, 'storie', '', 'T', 'Skrywer', 'import' );

	expect( $row )->toBe(
		array(
			'round_id' => 9,
			'post_id'  => 0,
			'user_id'  => 50,
			'position' => 1,
			'category' => 'storie',
			'grade'    => '',
			'title'    => 'T',
			'byline'   => 'Skrywer',
			'source'   => 'import',
		)
	);

	// The needle the count searches for sits in a row exactly as it is stored, and 5 does not match 50.
	expect( json_encode( $row ) )->toContain( '"user_id":50,' );
	expect( json_encode( $row ) )->not->toContain( '"user_id":5,' );
	expect( WinRecords::userLike( '"user_id":50,' ) )->toBe( '%"user_id":50,%' );

	expect( WinRecords::lifetimeCountFor( 0 ) )->toBe( 0 );
	expect( WinRecords::clearSource( '' ) )->toBe( 0 );
} );

test( 'the importer\'s records join the placements: a win with no post still shows, and a placed post is not listed twice', function (): void {
	\WP_Query::$ink_test_posts = array( ink_results_post( 5, 'gedig', 50 ) );
	ink_results_stub( array( 5 => array( 'goud', 1 ) ) );

	$records = array(
		json_encode( WinRecords::row( 9, 5, 50, 1, 'gedig', 'goud', 'Titel 5', '', 'import' ) ),     // the placed post, again
		json_encode( WinRecords::row( 9, 0, 0, 2, 'storie', '', 'Verlore woorde', 'Agie', 'import' ) ), // no post, no account
		json_encode( WinRecords::row( 9, 0, 70, 3, 'gedig', 'silwer', 'Ou gedig', '', 'import' ) ),    // no post, an account
	);

	$base = fn ( int $id, string $key ) => match ( $key ) {
		Entry::GRADERING_META_KEY      => 'goud',
		Placements::PLACEMENT_META_KEY => 1,
		default                        => '',
	};

	Functions\when( 'get_post_meta' )->alias(
		function ( int $id, string $key, bool $single = false ) use ( $records, $base ) {
			return WinRecords::META === $key ? $records : $base( $id, $key );
		}
	);

	$rows  = RoundResults::forRound( 9 );
	$by_id = array();

	foreach ( $rows as $row ) {
		$by_id[ $row['title'] ] = $row;
	}

	expect( $rows )->toHaveCount( 3 );
	expect( $by_id['Titel 5']['tier'] )->toBe( 'goud' );
	expect( $by_id['Verlore woorde'] )->toMatchArray(
		array(
			'category' => 'storie',
			'tier'     => '', // a Storie has no Gradering (D7)
			'position' => 2,
			'post_id'  => 0,
			'author'   => 'Agie',
			'user_id'  => 0,
			'excerpt'  => '',
		)
	);
	expect( $by_id['Ou gedig'] )->toMatchArray(
		array(
			'tier'    => 'silwer',
			'post_id' => 0,
			'author'  => 'Skrywer 70',
		)
	);
} );
