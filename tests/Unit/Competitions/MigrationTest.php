<?php
/**
 * Unit tests for importing old galas and competitions from the page tree (`05-kompetisies` §3.6, §3.7).
 *
 * Targets: {@see \Ink\Competitions\Migration}, the gala rows of
 * {@see \Ink\Migration\StaticRedirects}, and the `kompetisies` step of `migrate-all`.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Ink\Competitions\Migration;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\MigrationData;
use Ink\Migration\MigrateAll;
use Ink\Migration\StaticRedirects;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A migration with its I/O recorded.
 *
 */
function ink_kompetisie_migration(): Migration {
	return new class() extends Migration {
		public array $posts    = array();
		public array $meta     = array();
		public array $trashed  = array();
		public array $existing = array();
		public array $bodies   = array();
		public array $stored   = array();
		public array $pages    = array();
		public bool $done      = false;
		private int $next      = 100;

		public function __construct() {
			$this->pages = ink_kompetisie_pages();
		}

		public function hasRun(): bool {
			return $this->done;
		}

		protected function markDone(): void {
			$this->done = true;
		}

		protected function candidatePages(): array {
			return $this->pages;
		}

		protected function oldPath( int $page_id ): string {
			return '/oud-' . $page_id;
		}

		protected function storeOldPaths( array $paths ): void {
			$this->stored = $paths;
		}

		protected function pageBody( int $page_id ): string {
			return $this->bodies[ $page_id ] ?? 'Ou blad ' . $page_id;
		}

		protected function ensurePost( string $key, array $postarr ): array {
			$this->posts[ $key ] = $postarr;

			if ( isset( $this->existing[ $key ] ) ) {
				return array( 'id' => $this->existing[ $key ], 'created' => false );
			}

			$this->existing[ $key ] = ++$this->next;

			return array( 'id' => $this->next, 'created' => true );
		}

		protected function isAttachment( int $attachment_id ): bool {
			return $attachment_id < 9000;
		}

		protected function setMeta( int $post_id, string $key, int|string $value ): void {
			$this->meta[ $post_id ][ $key ] = $value;
		}

		protected function trashPage( int $page_id ): bool {
			$this->trashed[] = $page_id;
			return true;
		}
	};
}

/**
 * The page tree: two galas (one, 2026, untagged), the index, a gala with no
 * Fotos Page, and a Foto page that hangs under nothing.
 *
 * @return list<array{id:int, parent:int, title:string, date:string}>
 */
function ink_kompetisie_pages(): array {
	return array(
		array( 'id' => 28477, 'parent' => 0, 'title' => 'INK se Gala-Aande', 'date' => '2018-11-09 17:45:07' ),
		array( 'id' => 64611, 'parent' => 0, 'title' => '15 November 2025 - 10de Gala', 'date' => '2025-10-01 10:00:00' ),
		array( 'id' => 64688, 'parent' => 64611, 'title' => 'FOTOS - 15 NOVEMBER 2025', 'date' => '2025-11-26 13:44:01' ),
		array( 'id' => 70001, 'parent' => 0, 'title' => '14 November 2026 - 11de Gala', 'date' => '2026-10-01 10:00:00' ),
		array( 'id' => 70002, 'parent' => 70001, 'title' => "Foto's 14 November 2026", 'date' => '2026-11-20 09:00:00' ),
		array( 'id' => 7802, 'parent' => 0, 'title' => '22 Oktober 2016 - 1ste Gala aand', 'date' => '2016-08-18 17:02:00' ),
		array( 'id' => 9949, 'parent' => 7802, 'title' => "Foto's", 'date' => '2016-10-24 15:43:52' ),
		array( 'id' => 80001, 'parent' => 0, 'title' => 'Gala kaartjies', 'date' => '2026-01-01 00:00:00' ),
		array( 'id' => 80002, 'parent' => 0, 'title' => "Foto's van niks", 'date' => '2026-01-01 00:00:00' ),
	);
}

/**
 * A Fotos Page body with a grid, a gallery and a repeat.
 */
const INK_KOMP_FOTOS = '[vc_row][vc_media_grid grid_id="x" include="11,12,13"][vc_gallery type="image_grid" images="14, 12,15"][vc_single_image image="16"][/vc_row]';

test( 'discoverGalas finds each gala and its Fotos Page from the tree: key, name, year, ids, newest first', function (): void {
	$found = Migration::discoverGalas( ink_kompetisie_pages() );

	expect( array_column( $found['galas'], 'sleutel' ) )->toBe( array( 'gala-2026', 'gala-2025', 'gala-2016' ) );
	expect( $found['galas'][0] )->toBe(
		array(
			'sleutel'      => 'gala-2026',
			'naam'         => '14 November 2026 - 11de Gala',
			'jaar'         => '2026',
			'bladsy'       => '70001',
			'fotos_bladsy' => '70002',
		)
	);
	// The index, a Foto page under nothing and a gala with no Fotos Page are not galas; the last is reported.
	expect( $found['notes'] )->toHaveCount( 1 );
	expect( $found['notes'][0] )->toContain( 'Page 80001' );
} );

test( 'tagged Pages are found by their meta, with no warning, even when the title says nothing', function (): void {
	$pages = array(
		array( 'id' => 28477, 'parent' => 0, 'title' => 'INK se Gala-Aande', 'date' => '2018-11-09 17:45:07' ),
		array( 'id' => 52672, 'parent' => 0, 'title' => '12 NOVEMBER 2022 - 7de AAND', 'date' => '2022-10-01 00:00:00', 'gala' => '2022', 'fotos' => '', 'competition' => '' ),
		array( 'id' => 52674, 'parent' => 52672, 'title' => 'Prente', 'date' => '2022-11-20 00:00:00', 'gala' => '', 'fotos' => '2022', 'competition' => '' ),
		array( 'id' => 49428, 'parent' => 0, 'title' => 'INK dig- en Skryfkompetisie 2022', 'date' => '2022-02-14 00:00:00', 'gala' => '', 'fotos' => '', 'competition' => '2022' ),
	);

	$found = Migration::discoverGalas( $pages );

	expect( $found['warnings'] )->toBe( array() );
	expect( $found['notes'] )->toBe( array() );
	expect( array_column( $found['galas'], 'sleutel' ) )->toBe( array( 'gala-2022', 'kompetisie-2022' ) );
	expect( $found['galas'][0]['fotos_bladsy'] )->toBe( '52674' );
	expect( $found['galas'][1] )->toBe(
		array(
			'sleutel'      => 'kompetisie-2022',
			'naam'         => 'INK dig- en Skryfkompetisie 2022',
			'jaar'         => '2022',
			'bladsy'       => '49428',
			'fotos_bladsy' => '',
		)
	);
} );

test( 'untagged galas are still found by title, and each is warned about', function (): void {
	$found = Migration::discoverGalas( ink_kompetisie_pages() );

	expect( array_column( $found['galas'], 'sleutel' ) )->toBe( array( 'gala-2026', 'gala-2025', 'gala-2016' ) );
	expect( $found['warnings'] )->toContain( 'Gala Page 64611 "15 November 2025 - 10de Gala" found by title; tag it (ink_old_gala).' );
	expect( $found['warnings'] )->toContain( 'Fotos Page 64688 of gala Page 64611 found by title; tag it (ink_old_gala_fotos).' );
	expect( $found['warnings'] )->toHaveCount( 6 );
} );

test( 'a gala with only one of its two tags is found, and only the missing tag is warned about', function (): void {
	$pages = array(
		// Gala tagged, Fotos Page not: the Foto child is found by title.
		array( 'id' => 64611, 'parent' => 0, 'title' => '15 November 2025 - 10de Gala', 'date' => '2025-10-01 10:00:00', 'gala' => '2025', 'fotos' => '', 'competition' => '' ),
		array( 'id' => 64688, 'parent' => 64611, 'title' => 'FOTOS - 15 NOVEMBER 2025', 'date' => '2025-11-26 13:44:01', 'gala' => '', 'fotos' => '', 'competition' => '' ),
		// Fotos Page tagged, gala not: the gala is found by title and takes the tagged child.
		array( 'id' => 61225, 'parent' => 0, 'title' => '9 Nov 2024 - 9DE GALA AAND', 'date' => '2024-10-01 10:00:00', 'gala' => '', 'fotos' => '', 'competition' => '' ),
		array( 'id' => 61228, 'parent' => 61225, 'title' => 'Fotos', 'date' => '2024-11-20 10:00:00', 'gala' => '', 'fotos' => '2024', 'competition' => '' ),
	);

	$found = Migration::discoverGalas( $pages );

	expect( array_column( $found['galas'], 'fotos_bladsy', 'sleutel' ) )->toBe( array( 'gala-2025' => '64688', 'gala-2024' => '61228' ) );
	expect( $found['warnings'] )->toBe(
		array(
			'Gala Page 61225 "9 Nov 2024 - 9DE GALA AAND" found by title; tag it (ink_old_gala).',
			'Fotos Page 64688 of gala Page 64611 found by title; tag it (ink_old_gala_fotos).',
		)
	);
} );

test( 'a tagged competition imports and its Page is kept; warnings reach the summary, not the problems', function (): void {
	$m        = ink_kompetisie_migration();
	$m->pages = array(
		array( 'id' => 52672, 'parent' => 0, 'title' => '12 NOVEMBER 2022 - 7de GALA AAND', 'date' => '2022-10-01 00:00:00', 'gala' => '2022', 'fotos' => '', 'competition' => '' ),
		array( 'id' => 52674, 'parent' => 52672, 'title' => 'Fotos', 'date' => '2022-11-20 00:00:00', 'gala' => '', 'fotos' => '2022', 'competition' => '' ),
		array( 'id' => 49428, 'parent' => 0, 'title' => 'INK dig- en Skryfkompetisie 2022', 'date' => '2022-02-14 00:00:00', 'gala' => '', 'fotos' => '', 'competition' => '2022' ),
	);

	$m->bodies[52674] = INK_KOMP_FOTOS;

	$summary = $m->run();

	expect( $summary['created'] )->toBe( 2 );
	expect( $summary['warnings'] )->toBe( array() );
	expect( $summary['problems'] )->toBe( array() );
	expect( $m->posts['kompetisie-2022']['post_title'] )->toBe( 'INK dig- en Skryfkompetisie 2022' );
	expect( $m->posts['kompetisie-2022']['post_content'] )->toBe( 'Ou blad 49428' );
	expect( $m->meta[ $m->existing['kompetisie-2022'] ][ FieldSets::kompetisieKey( 'jaar' ) ] )->toBe( 2022 );
	expect( $m->trashed )->not->toContain( 49428 );
} );

test( 'two galas in one year get -2 on the second, in id order', function (): void {
	$pages   = ink_kompetisie_pages();
	$pages[] = array( 'id' => 90001, 'parent' => 0, 'title' => '3 Mei 2026 - Bonus Gala', 'date' => '2026-04-01 00:00:00' );
	$pages[] = array( 'id' => 90002, 'parent' => 90001, 'title' => 'Fotos', 'date' => '2026-05-04 00:00:00' );

	expect( array_column( Migration::discoverGalas( $pages )['galas'], 'sleutel' ) )->toBe( array( 'gala-2026', 'gala-2026-2', 'gala-2025', 'gala-2016' ) );
} );

test( 'a gala imports in full: name, body, year, photos from its Fotos Page, Pages trashed, paths stored', function (): void {
	$m                = ink_kompetisie_migration();
	$m->bodies[70002] = INK_KOMP_FOTOS;

	$summary = $m->run();

	expect( $summary['error'] )->toBe( '' );
	expect( $summary['created'] )->toBe( 3 );
	expect( $summary['photos'] )->toBe( 6 );
	expect( $summary['per_row'] )->toBe( array( 'gala-2026' => 6, 'gala-2025' => 0, 'gala-2016' => 0 ) );

	expect( $m->posts['gala-2026'] )->toBe(
		array(
			'post_type'    => PostTypes::KOMPETISIE,
			'post_status'  => 'publish',
			'post_title'   => '14 November 2026 - 11de Gala',
			'post_content' => 'Ou blad 70001',
		)
	);

	$meta = $m->meta[ $m->existing['gala-2026'] ];

	expect( $meta[ Migration::SOURCE_KEY_META ] )->toBe( 'gala-2026' );
	expect( $meta[ FieldSets::kompetisieKey( 'jaar' ) ] )->toBe( 2026 );
	expect( $meta[ FieldSets::kompetisieKey( 'gala_fotos' ) ] )->toBe( '11,12,13,14,15,16' );
	expect( $meta )->not->toHaveKey( FieldSets::kompetisieKey( 'beskrywing' ) );

	// The new gala's two Pages go to the trash with the others, and their paths are kept for the 301.
	expect( $m->trashed )->toContain( 70001 );
	expect( $m->trashed )->toContain( 70002 );
	expect( $m->stored )->toContain( '/oud-70001' );
	expect( $m->stored )->toContain( '/oud-70002' );
	expect( $m->stored )->toContain( '/oud-28477' );
} );

test( 'the gala Page, its Fotos Page and the index go to the trash once photos are linked', function (): void {
	$m = ink_kompetisie_migration();

	foreach ( array( 64688, 70002, 9949 ) as $fotos ) {
		$m->bodies[ $fotos ] = INK_KOMP_FOTOS;
	}

	$summary = $m->run();

	expect( $m->trashed )->toBe( array( Migration::GALA_INDEX_PAGE, 70001, 70002, 64611, 64688, 7802, 9949 ) );
	expect( $m->trashed )->not->toContain( 49428 );
	expect( $summary['retired'] )->toBe( 7 );
	expect( Migration::GALA_INDEX_PAGE )->toBe( 28477 );
} );

test( 'a Fotos Page that yields no photos is reported, keeps its Pages and does not blank linked photos', function (): void {
	$m = ink_kompetisie_migration();

	$summary = $m->run();

	expect( $summary['problems'] )->toContain( 'Competition "gala-2026": no photos read from Fotos Page 70002 (shortcodes already cleaned?); nothing linked and the Pages are kept.' );
	expect( $m->meta[ $m->existing['gala-2026'] ] )->not->toHaveKey( FieldSets::kompetisieKey( 'gala_fotos' ) );
	expect( $m->trashed )->toBe( array( Migration::GALA_INDEX_PAGE ) );
} );

test( 'an id that is not an attachment is reported and left out; an empty Fotos Page keeps its Pages', function (): void {
	$m                = ink_kompetisie_migration();
	$m->bodies[70002] = '[vc_media_grid include="7,9500,8"]';
	$m->bodies[64688] = '';

	$summary = $m->run();

	expect( $summary['problems'] )->toContain( 'Competition "gala-2026": id 9500 on the Fotos Page is not an attachment.' );
	expect( $summary['problems'] )->toContain( 'Competition "gala-2025": Fotos Page 64688 not found or empty; its photos are not linked and the Page is kept.' );
	expect( $m->meta[ $m->existing['gala-2026'] ][ FieldSets::kompetisieKey( 'gala_fotos' ) ] )->toBe( '7,8' );
	expect( $m->trashed )->not->toContain( 64611 );
	expect( $m->trashed )->toContain( 70001 );
} );

test( 'once-off: a second run writes nothing; force reconciles by marker without duplicating', function (): void {
	$m = ink_kompetisie_migration();

	expect( $m->run()['created'] )->toBe( 3 );
	$m->posts = array();

	$again = $m->run();
	expect( $again['skipped'] )->toBeTrue();
	expect( $m->posts )->toBe( array() );

	$forced = $m->run( true );
	expect( $forced['created'] )->toBe( 0 );
	expect( $forced['updated'] )->toBe( 3 );
} );


test( 'photoIds reads the four shortcodes in page order, drops repeats and blanks', function (): void {
	expect( Migration::photoIds( INK_KOMP_FOTOS ) )->toBe( array( 11, 12, 13, 14, 15, 16 ) );
	expect( Migration::photoIds( '[vc_masonry_media_grid style="x" include="5,,6"] [vc_gallery images=""] tekst' ) )->toBe( array( 5, 6 ) );
	expect( Migration::photoIds( 'geen galery' ) )->toBe( array() );
} );

test( 'unhandledGalleries flags other forms and a gallery with no ids, not the four read ones', function (): void {
	expect( Migration::unhandledGalleries( INK_KOMP_FOTOS ) )->toBe( array() );
	expect( Migration::unhandledGalleries( '[gallery ids="1,2"] [vc_gallery type="x"] [vc_basic_grid post_type="post"]' ) )->toHaveCount( 3 );
	expect( Migration::unhandledGalleries( '<p><img src="a.jpg"></p>' ) )->toHaveCount( 1 );
} );

test( 'summaryLine satisfies migrate-all: real work is ok, the rerun is ok', function (): void {
	$base = array( 'skipped' => false, 'created' => 11, 'updated' => 0, 'photos' => 40, 'retired' => 21, 'problems' => array() );

	expect( MigrateAll::verdict( 'kompetisies', Migration::summaryLine( $base ) ) )->toBe( MigrateAll::VERDICT_OK );
	expect( MigrateAll::verdict( 'kompetisies', Migration::summaryLine( array( 'skipped' => true, 'created' => 0, 'updated' => 0, 'photos' => 0, 'retired' => 0, 'problems' => array() ) ) ) )->not->toBe( MigrateAll::VERDICT_ALL_ZERO );
} );

test( 'the step runs in migrate-all ahead of generate-redirects', function (): void {
	$ids = array_map( static fn ( array $s ): string => $s[0], MigrateAll::steps() );

	expect( $ids )->toContain( 'kompetisies' );
	expect( array_search( 'kompetisies', $ids, true ) )->toBeLessThan( array_search( 'generate-redirects', $ids, true ) );
	expect( array_column( MigrateAll::steps(), 1, 0 )['kompetisies'] )->toBe( Migration::CLI_COMMAND );
} );

test( 'a retired gala path outside the fixed list redirects from the stored option', function (): void {
	$stored = array( '/14-november-2026-11de-gala', '/14-november-2026-11de-gala/fotos-14-november-2026' );

	expect( StaticRedirects::prefixTarget( '/14-november-2026-11de-gala/' ) )->toBeNull();
	expect( StaticRedirects::storedGalaTarget( 'https://ink.org.za/14-november-2026-11de-gala/', $stored ) )->toBe( '/kompetisies/' );
	expect( StaticRedirects::storedGalaTarget( '/14-november-2026-11de-gala/fotos-14-november-2026', $stored ) )->toBe( '/kompetisies/' );
	expect( StaticRedirects::storedGalaTarget( '/ander-blad', $stored ) )->toBeNull();
} );

test( 'all 21 old gala paths redirect to /kompetisies/, trailing slash or not', function (): void {
	$gala = array_filter( StaticRedirects::EXACT, static fn ( string $to ): bool => '/kompetisies/' === $to );

	expect( $gala )->toHaveCount( 21 );
	expect( StaticRedirects::prefixTarget( '/ink-se-gala-aande/' ) )->toBe( '/kompetisies/' );
	expect( StaticRedirects::prefixTarget( '/37975-2/' ) )->toBe( '/kompetisies/' );
	expect( StaticRedirects::prefixTarget( '/37975-2/fotos-26-oktober-2019-4de-gala-aand/' ) )->toBe( '/kompetisies/' );
	expect( StaticRedirects::prefixTarget( 'https://ink.org.za/22-oktober-2016-1ste-gala-aand/fotos' ) )->toBe( '/kompetisies/' );
	// Page 49428 is not one of the ruled redirects.
	expect( StaticRedirects::prefixTarget( '/ink-dig-en-skryfkompetisie-2022/' ) )->toBeNull();
} );
