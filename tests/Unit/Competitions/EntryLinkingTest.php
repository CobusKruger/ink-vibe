<?php
/**
 * Unit tests for competition entries on Skryf (K2, `05-kompetisies` §14.3).
 *
 * Target: {@see \Ink\Competitions\EntryLinking}: the offer list, the window, the per-writer cap,
 * the get-or-create term at the first submission, and that none of it touches the challenge round.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Competitions\Edition;
use Ink\Competitions\EntryLinking;
use Ink\Content\Taxonomies;
use Ink\Submission\SubmissionForm;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An edition.
 *
 * @param int                   $id     Post id.
 * @param int                   $year   Year.
 * @param array<string, string> $values More field values.
 */
function k2_edition( int $id, int $year, array $values = array() ): Edition {
	return new Edition( $id, "Kompetisie $year", array( 'jaar' => (string) $year ) + $values );
}

/**
 * Noon on a day, site-local.
 *
 * @param string $day Y-m-d.
 */
function k2_now( string $day = '2026-03-10' ): \DateTimeImmutable {
	return new \DateTimeImmutable( $day . ' 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
}

/**
 * A linker over fixed editions with recorded writes.
 *
 * @param list<Edition> $editions The published editions.
 * @param int           $count    Existing entries of the type.
 */
function k2_linker( array $editions, int $count = 0 ): EntryLinking {
	return new class( $editions, $count ) extends EntryLinking {
		/** @var list<array{0:int,1:int}> */
		public array $assigned = array();
		/** @var list<int> */
		public array $resolved = array();

		/**
		 * @param list<Edition> $eds Editions.
		 * @param int           $cnt Existing entries.
		 */
		public function __construct( private array $eds, private int $cnt ) {
		}

		protected function editions(): array {
			return $this->eds;
		}

		protected function now(): \DateTimeImmutable {
			return k2_now();
		}

		protected function entryCountFor( int $post_id, int $kompetisie_id ): int {
			return $this->cnt;
		}

		protected function resolveEntryTerm( int $kompetisie_id ): int {
			$this->resolved[] = $kompetisie_id;

			return 9000 + $kompetisie_id;
		}

		protected function assign( int $post_id, int $term_id ): void {
			$this->assigned[] = array( $post_id, $term_id );
		}
	};
}

test( 'only a published competition in state open is offered, partners included, the default never', function (): void {
	$open_annual  = k2_edition(
		10,
		2026,
		array(
			'oopdatum'       => '2026-02-01',
			'sluitingsdatum' => '2026-05-31',
		)
	);
	$open_partner = k2_edition(
		11,
		2026,
		array(
			'tipe'           => 'vennoot',
			'oopdatum'       => '2026-03-01',
			'sluitingsdatum' => '2026-04-01',
		)
	);
	$announced    = k2_edition(
		12,
		2026,
		array(
			'oopdatum'       => '2026-04-01',
			'sluitingsdatum' => '2026-06-01',
		)
	);
	$closed       = k2_edition(
		13,
		2026,
		array(
			'oopdatum'       => '2026-01-01',
			'sluitingsdatum' => '2026-03-09',
		)
	);
	$default      = k2_edition(
		14,
		2026,
		array(
			'standaard'      => 'ja',
			'oopdatum'       => '2026-01-01',
			'sluitingsdatum' => '2026-12-31',
		)
	);
	$results      = k2_edition(
		15,
		2026,
		array(
			'uitslae_gepubliseer' => 'ja',
			'sluitingsdatum'      => '2026-12-31',
		)
	);
	$legacy       = k2_edition( 16, 2022 ); // No dates: legacy posts never open.

	$offer = EntryLinking::offerable( array( $open_annual, $open_partner, $announced, $closed, $default, $results, $legacy ), k2_now() );

	expect( array_column( $offer, 'id' ) )->toBe( array( 10, 11 ) );
	expect( $offer[0]['title'] )->toBe( 'Kompetisie 2026' );
} );

test( 'the window is inclusive of the closing day, an invalid closing date is closed', function (): void {
	$last_day  = k2_edition( 1, 2026, array( 'sluitingsdatum' => '2026-03-10' ) );
	$day_after = k2_edition( 2, 2026, array( 'sluitingsdatum' => '2026-03-09' ) );
	$invalid   = k2_edition( 3, 2026, array( 'sluitingsdatum' => 'volgende week' ) );

	expect( array_column( EntryLinking::offerable( array( $last_day, $day_after, $invalid ), k2_now() ), 'id' ) )->toBe( array( 1 ) );
} );

test( 'an older competition is no longer offered once a newer edition exists (archive)', function (): void {
	$old = k2_edition( 1, 2025, array( 'sluitingsdatum' => '2026-12-31' ) );
	$new = k2_edition(
		2,
		2026,
		array(
			'oopdatum'       => '2026-12-01',
			'sluitingsdatum' => '2026-12-31',
		)
	);

	expect( EntryLinking::offerable( array( $old, $new ), k2_now() ) )->toBe( array() );
} );

test( 'acceptable keeps only offered ids, once each, in the order ticked', function (): void {
	$offer = array(
		array(
			'id'    => 10,
			'title' => 'A',
		),
		array(
			'id'    => 11,
			'title' => 'B',
		),
	);

	expect( EntryLinking::acceptable( array( 11, 99, 10, 11, 0, -3 ), $offer ) )->toBe( array( 11, 10 ) );
	expect( EntryLinking::acceptable( array( 10 ), array() ) )->toBe( array() );
} );

test( 'the cap allows three entries of a type per competition and refuses the fourth', function (): void {
	expect( EntryLinking::withinCap( 2 ) )->toBeTrue();
	expect( EntryLinking::withinCap( 3 ) )->toBeFalse();
} );

test( 'link re-checks the window itself: a closed or unknown id posted by the form is ignored', function (): void {
	$open   = k2_edition( 10, 2026, array( 'sluitingsdatum' => '2026-05-31' ) );
	$closed = k2_edition( 11, 2026, array( 'sluitingsdatum' => '2026-01-31' ) );
	$linker = k2_linker( array( $open, $closed ) );

	expect( $linker->link( 5, array( 11, 10, 10, 777, 0 ) ) )->toBe( array( 10 ) );
	expect( $linker->assigned )->toBe( array( array( 5, 9010 ) ) );
	expect( $linker->resolved )->toBe( array( 10 ) ); // No term is made for a refused one.
} );

test( 'link does not link (and makes no term) when the writer is at the cap', function (): void {
	$linker = k2_linker( array( k2_edition( 10, 2026, array( 'sluitingsdatum' => '2026-05-31' ) ) ), 3 );

	expect( $linker->link( 5, array( 10 ) ) )->toBe( array() );
	expect( $linker->assigned )->toBe( array() );
	expect( $linker->resolved )->toBe( array() );
} );

test( 'the entry term is keyed to the kompetisie post and reused when it exists', function (): void {
	expect( EntryLinking::slugFor( 42 ) )->toBe( 'kompetisie-42' );

	$method = new \ReflectionMethod( EntryLinking::class, 'resolveEntryTerm' );

	Functions\expect( 'get_term_by' )->once()->with( 'slug', 'kompetisie-42', EntryLinking::TAXONOMY )->andReturn( new \WP_Term( 'kompetisie-42', 77 ) );
	Functions\expect( 'wp_insert_term' )->never();
	expect( $method->invoke( new EntryLinking(), 42 ) )->toBe( 77 );
} );

test( 'the entry term is created at the first entry, with the post id as term meta', function (): void {
	$method = new \ReflectionMethod( EntryLinking::class, 'resolveEntryTerm' );

	Functions\when( 'get_the_title' )->justReturn( 'Kompetisie 2026' );
	Functions\when( 'get_term_by' )->justReturn( false );
	Functions\when( 'is_wp_error' )->justReturn( false );
	Functions\expect( 'wp_insert_term' )->once()->with( 'Kompetisie 2026', EntryLinking::TAXONOMY, array( 'slug' => 'kompetisie-42' ) )->andReturn( array( 'term_id' => 88 ) );
	Functions\expect( 'add_term_meta' )->once()->with( 88, EntryLinking::TERM_META, 42, true );

	expect( $method->invoke( new EntryLinking(), 42 ) )->toBe( 88 );
} );

test( 'a competition entry can never be a challenge entry: own taxonomy, own slug, own field', function (): void {
	expect( EntryLinking::TAXONOMY )->not->toBe( Taxonomies::UITDAGINGSRONDTE );
	expect( Taxonomies::all() )->not->toContain( EntryLinking::TAXONOMY );
	expect( EntryLinking::SLUG_PREFIX )->not->toBe( \Ink\Content\ChallengeRound::SLUG_PREFIX );
	expect( EntryLinking::FIELD )->not->toBe( \Ink\Submission\ChallengeLinking::FIELD );
	// Skryf cannot depend on this module, so it repeats the literal: they must agree.
	expect( SubmissionForm::FIELD_COMPETITIONS )->toBe( EntryLinking::FIELD );
} );
