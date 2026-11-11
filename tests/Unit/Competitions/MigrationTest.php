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
		public array $map      = array();
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

		protected function storeOldPages( array $pages ): void {
			$this->map = $pages;
		}

		protected function pageBody( int $page_id ): string {
			return $this->bodies[ $page_id ] ?? '<p>Ou blad ' . $page_id . '</p>';
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
 * The page tree, all untagged: two results Pages found by title (2026 and 2025), the 2016 results
 * Page with its Fotos Page, the index, a "gala" Page with no Fotos Page, and a Foto page under nothing.
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
 * A tagged tree: 2022 and 2016 results Pages and Fotos Pages, the 2016 duplicate 7802 (untagged, parent
 * of the Fotos Page), and kept-role Pages, one of which has "gala" and a Foto child in its title.
 *
 * @return list<array<string, mixed>>
 */
function ink_kompetisie_tagged_pages(): array {
	$t = static fn ( int $id, int $parent, string $title, string $date, string $uitslae = '', string $fotos = '', string $kept = '' ): array => array(
		'id'      => $id,
		'parent'  => $parent,
		'title'   => $title,
		'date'    => $date,
		'uitslae' => $uitslae,
		'fotos'   => $fotos,
		'kept'    => $kept,
	);

	return array(
		$t( 28477, 0, 'INK se Gala-Aande', '2018-11-09 17:45:07' ),
		$t( 52672, 0, '12 NOVEMBER 2022 - 7de AAND', '2022-10-01 00:00:00', '2022' ),
		$t( 52674, 52672, 'Prente', '2022-11-20 00:00:00', '', '2022' ),
		$t( 49428, 0, 'INK dig- en Skryfkompetisie 2022', '2022-02-14 00:00:00', '', '', 'ink_old_kompetisie_aankondiging' ),
		$t( 49440, 0, 'Kompetisie 2022 gesluit', '2022-06-01 00:00:00', '', '', 'ink_old_kompetisie_sluiting' ),
		$t( 5945, 0, 'Gala 2016 aankondiging', '2016-05-01 00:00:00', '', '', 'ink_old_gala_aankondiging' ),
		$t( 5946, 5945, 'Foto voorbeeld', '2016-05-02 00:00:00' ),
		$t( 7802, 0, '22 Oktober 2016 - 1ste Gala aand', '2016-08-18 17:02:00' ),
		$t( 9878, 0, '22 Oktober 2016 - Uitslae', '2016-10-23 10:00:00', '2016' ),
		$t( 9949, 7802, "Foto's", '2016-10-24 15:43:52', '', '2016' ),
	);
}

/**
 * A Fotos Page body with a grid, a gallery and a repeat.
 */
const INK_KOMP_FOTOS = '[vc_row][vc_media_grid grid_id="x" include="11,12,13"][vc_gallery type="image_grid" images="14, 12,15"][vc_single_image image="16"][/vc_row]';

test( 'discover finds each year from the title fallback: key, name, year, ids, newest first, one row a year', function (): void {
	$found = Migration::discover( ink_kompetisie_pages() );

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
	// The index, a Foto page under nothing and a gala with no Fotos Page are not imported; the last is reported.
	expect( $found['notes'] )->toHaveCount( 1 );
	expect( $found['notes'][0] )->toContain( 'Page 80001' );
} );

test( 'tagged Pages are found by their meta, with no warning; kept-role Pages and the 2016 duplicate are not rows', function (): void {
	$found = Migration::discover( ink_kompetisie_tagged_pages() );

	expect( $found['notes'] )->toBe( array() );
	expect( array_column( $found['galas'], 'sleutel' ) )->toBe( array( 'gala-2022', 'gala-2016' ) );
	expect( $found['galas'][0] )->toBe(
		array(
			'sleutel'      => 'gala-2022',
			'naam'         => '12 NOVEMBER 2022 - 7de AAND',
			'jaar'         => '2022',
			'bladsy'       => '52672',
			'fotos_bladsy' => '52674',
		)
	);
	// 2016: the results Page is 9878; its Fotos Page hangs under the duplicate 7802, found by the year tag.
	expect( $found['galas'][1]['bladsy'] )->toBe( '9878' );
	expect( $found['galas'][1]['fotos_bladsy'] )->toBe( '9949' );
	// Only the untagged duplicate 7802 is reported (ignored), never a kept-role Page.
	expect( $found['warnings'] )->toHaveCount( 1 );
	expect( $found['warnings'][0] )->toContain( 'Page 7802' )->toContain( 'ignored' );
} );

test( 'untagged results Pages are still found by title, and each is warned about', function (): void {
	$found = Migration::discover( ink_kompetisie_pages() );

	expect( $found['warnings'] )->toContain( 'Results Page 64611 "15 November 2025 - 10de Gala" found by title; tag it (ink_old_uitslae).' );
	expect( $found['warnings'] )->toContain( 'Fotos Page 64688 of results Page 64611 found by title; tag it (ink_old_gala_fotos).' );
	expect( $found['warnings'] )->toHaveCount( 6 );
} );

test( 'a results Page with only one of its two tags is found, and only the missing tag is warned about', function (): void {
	$pages = array(
		// Results tagged, Fotos Page not: the Foto child is found by title.
		array( 'id' => 64611, 'parent' => 0, 'title' => '15 November 2025 - 10de Gala', 'date' => '2025-10-01 10:00:00', 'uitslae' => '2025', 'fotos' => '', 'kept' => '' ),
		array( 'id' => 64688, 'parent' => 64611, 'title' => 'FOTOS - 15 NOVEMBER 2025', 'date' => '2025-11-26 13:44:01', 'uitslae' => '', 'fotos' => '', 'kept' => '' ),
		// Fotos Page tagged, results Page not: found by title, takes the tagged child.
		array( 'id' => 61225, 'parent' => 0, 'title' => '9 Nov 2024 - 9DE GALA AAND', 'date' => '2024-10-01 10:00:00', 'uitslae' => '', 'fotos' => '', 'kept' => '' ),
		array( 'id' => 61228, 'parent' => 61225, 'title' => 'Fotos', 'date' => '2024-11-20 10:00:00', 'uitslae' => '', 'fotos' => '2024', 'kept' => '' ),
	);

	$found = Migration::discover( $pages );

	expect( array_column( $found['galas'], 'fotos_bladsy', 'sleutel' ) )->toBe( array( 'gala-2025' => '64688', 'gala-2024' => '61228' ) );
	expect( $found['warnings'] )->toBe(
		array(
			'Fotos Page 64688 of results Page 64611 found by title; tag it (ink_old_gala_fotos).',
			'Results Page 61225 "9 Nov 2024 - 9DE GALA AAND" found by title; tag it (ink_old_uitslae).',
		)
	);
} );

test( 'a tagged Fotos Page with no results Page still gets its year; a second results Page for a year is reported', function (): void {
	$pages = array(
		array( 'id' => 1, 'parent' => 0, 'title' => 'Prente 2019', 'date' => '2019-11-01 00:00:00', 'uitslae' => '', 'fotos' => '2019', 'kept' => '' ),
		array( 'id' => 2, 'parent' => 0, 'title' => 'Uitslae A', 'date' => '2020-11-01 00:00:00', 'uitslae' => '2020', 'fotos' => '', 'kept' => '' ),
		array( 'id' => 3, 'parent' => 0, 'title' => 'Uitslae B', 'date' => '2020-11-02 00:00:00', 'uitslae' => '2020', 'fotos' => '', 'kept' => '' ),
	);

	$found = Migration::discover( $pages );

	expect( $found['galas'][1] )->toBe( array( 'sleutel' => 'gala-2019', 'naam' => 'Prente 2019', 'jaar' => '2019', 'bladsy' => '', 'fotos_bladsy' => '1' ) );
	expect( $found['galas'][0]['bladsy'] )->toBe( '2' );
	expect( $found['notes'] )->toHaveCount( 1 );
	expect( $found['notes'][0] )->toContain( 'Results Page 3' );
} );

test( 'a year imports in full: results body kept as it is and published, tipe jaarliks, photos, Pages trashed, map stored', function (): void {
	$m                = ink_kompetisie_migration();
	$m->pages         = ink_kompetisie_tagged_pages();
	$m->bodies[52672] = '[vc_row][vc_column_text]<p>Die wenners is <strong>Anna</strong>.</p>[/vc_column_text][/vc_row]';
	$m->bodies[52674] = INK_KOMP_FOTOS;
	$m->bodies[9949]  = INK_KOMP_FOTOS;

	$summary = $m->run();

	expect( $summary['error'] )->toBe( '' );
	expect( $summary['created'] )->toBe( 2 );
	expect( $summary['photos'] )->toBe( 12 );
	expect( $summary['problems'] )->toBe( array() );
	expect( $summary['per_row'] )->toBe( array( 'gala-2022' => 6, 'gala-2016' => 6 ) );

	expect( $m->posts['gala-2022'] )->toBe(
		array(
			'post_type'    => PostTypes::KOMPETISIE,
			'post_status'  => 'publish',
			'post_title'   => '12 NOVEMBER 2022 - 7de AAND',
			'post_content' => '',
		)
	);

	$meta = $m->meta[ $m->existing['gala-2022'] ];

	expect( $meta[ Migration::SOURCE_KEY_META ] )->toBe( 'gala-2022' );
	expect( $meta[ FieldSets::kompetisieKey( 'jaar' ) ] )->toBe( 2022 );
	expect( $meta[ FieldSets::kompetisieKey( 'tipe' ) ] )->toBe( 'jaarliks' );
	expect( $meta[ FieldSets::kompetisieKey( 'uitslae_gepubliseer' ) ] )->toBe( 'ja' );
	expect( $meta[ FieldSets::kompetisieKey( 'uitslae_oud' ) ] )->toBe( '<p>Die wenners is <strong>Anna</strong>.</p>' );
	expect( $meta[ FieldSets::kompetisieKey( 'gala_fotos' ) ] )->toBe( '11,12,13,14,15,16' );
	expect( $meta )->not->toHaveKey( FieldSets::kompetisieKey( 'beskrywing' ) );

	// The results and Fotos Pages and the index go to the trash; the kept-role Pages and the duplicate 7802 do not.
	expect( $m->trashed )->toBe( array( Migration::GALA_INDEX_PAGE, 52672, 52674, 9878, 9949 ) );
	expect( $m->trashed )->not->toContain( 49428 );
	expect( $m->trashed )->not->toContain( 49440 );
	expect( $m->trashed )->not->toContain( 5945 );
	expect( $m->trashed )->not->toContain( 7802 );
	expect( $summary['retired'] )->toBe( 5 );

	// Old paths for the existing redirect; the id, path, year and target kind for K7.
	expect( $m->map )->toBe(
		array(
			array( 'id' => 52672, 'path' => '/oud-52672', 'jaar' => 2022, 'soort' => 'uitslae' ),
			array( 'id' => 52674, 'path' => '/oud-52674', 'jaar' => 2022, 'soort' => 'fotos' ),
			array( 'id' => 9878, 'path' => '/oud-9878', 'jaar' => 2016, 'soort' => 'uitslae' ),
			array( 'id' => 9949, 'path' => '/oud-9949', 'jaar' => 2016, 'soort' => 'fotos' ),
		)
	);
} );

test( 'a Fotos Page that yields no photos is reported and kept; the results are still imported and their Page retired', function (): void {
	$m        = ink_kompetisie_migration();
	$m->pages = ink_kompetisie_tagged_pages();

	$summary = $m->run();

	expect( $summary['problems'] )->toContain( 'Competition "gala-2022": no photos read from Fotos Page 52674 (shortcodes already cleaned?); nothing linked and the Page is kept.' );
	expect( $m->meta[ $m->existing['gala-2022'] ] )->not->toHaveKey( FieldSets::kompetisieKey( 'gala_fotos' ) );
	expect( $m->meta[ $m->existing['gala-2022'] ][ FieldSets::kompetisieKey( 'uitslae_gepubliseer' ) ] )->toBe( 'ja' );
	expect( $m->trashed )->toBe( array( Migration::GALA_INDEX_PAGE, 52672, 9878 ) );
	expect( array_column( $m->map, 'soort' ) )->toBe( array( 'uitslae', 'uitslae' ) );
} );

test( 'an empty results Page publishes nothing and is kept; a non-attachment id is reported and left out', function (): void {
	$m                = ink_kompetisie_migration();
	$m->pages         = ink_kompetisie_tagged_pages();
	$m->bodies[52672] = '[vc_row][/vc_row]<p>&nbsp;</p>';
	$m->bodies[52674] = '[vc_media_grid include="7,9500,8"]';

	$summary = $m->run();

	expect( $summary['problems'] )->toContain( 'Competition "gala-2022": results Page 52672 not found or empty; results not published and the Page is kept.' );
	expect( $summary['problems'] )->toContain( 'Competition "gala-2022": id 9500 on the Fotos Page is not an attachment.' );
	expect( $m->meta[ $m->existing['gala-2022'] ] )->not->toHaveKey( FieldSets::kompetisieKey( 'uitslae_gepubliseer' ) );
	expect( $m->meta[ $m->existing['gala-2022'] ] )->not->toHaveKey( FieldSets::kompetisieKey( 'uitslae_oud' ) );
	expect( $m->meta[ $m->existing['gala-2022'] ][ FieldSets::kompetisieKey( 'gala_fotos' ) ] )->toBe( '7,8' );
	expect( $m->trashed )->not->toContain( 52672 );
	expect( $m->trashed )->toContain( 52674 );
} );

test( 'resultsBody strips WPBakery tags only, keeps the markup, and calls a text-less body empty', function (): void {
	expect( Migration::resultsBody( '[vc_row]<h2>Wenners</h2>[caption]x[/caption][/vc_row]' ) )->toBe( '<h2>Wenners</h2>[caption]x[/caption]' );
	expect( Migration::resultsBody( '[vc_row]<p> </p>[/vc_row]' ) )->toBe( '' );
	expect( Migration::resultsBody( '<p><img src="a.jpg"></p>' ) )->toBe( '<p><img src="a.jpg"></p>' );
} );

test( 'the retired and kept tag names are the agreed ones', function (): void {
	expect( Migration::META_UITSLAE )->toBe( 'ink_old_uitslae' );
	expect( Migration::META_FOTOS )->toBe( 'ink_old_gala_fotos' );
	expect( Migration::META_KEPT )->toBe( array( 'ink_old_kompetisie_aankondiging', 'ink_old_gala_aankondiging', 'ink_old_kompetisie_sluiting', 'ink_old_gala_herinnering' ) );
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

test( 'only the gala index stays in the fixed list; the year Pages redirect from the stored map (K7)', function (): void {
	expect( StaticRedirects::EXACT['/ink-se-gala-aande'] )->toBe( '/kompetisies/' );
	expect( array_filter( StaticRedirects::EXACT, static fn ( string $to ): bool => '/kompetisies/' === $to ) )->toHaveCount( 1 );
	expect( StaticRedirects::prefixTarget( '/ink-se-gala-aande/' ) )->toBe( '/kompetisies/' );
	expect( StaticRedirects::prefixTarget( '/37975-2/fotos-26-oktober-2019-4de-gala-aand/' ) )->toBeNull();
	// Page 49428 is not one of the ruled redirects.
	expect( StaticRedirects::prefixTarget( '/ink-dig-en-skryfkompetisie-2022/' ) )->toBeNull();
} );

test( 'the stored-map targets are the real gala photos and results routes', function (): void {
	$pages = array(
		array( 'id' => 1, 'path' => '/a', 'jaar' => 2025, 'soort' => 'fotos' ),
		array( 'id' => 2, 'path' => '/b', 'jaar' => 2025, 'soort' => 'uitslae' ),
	);

	expect( StaticRedirects::oldPageTarget( '/a', $pages ) )->toBe( \Ink\Competitions\GalaPage::path( 2025 ) );
	expect( StaticRedirects::oldPageTarget( '/b', $pages ) )->toBe( \Ink\Competitions\UitslaePage::path( 2025 ) );
} );
