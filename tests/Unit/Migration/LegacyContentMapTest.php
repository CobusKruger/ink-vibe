<?php
/**
 * Unit tests for the committed legacy Biblioteek/Opleiding mapping and its migration path.
 *
 * Target: {@see \Ink\Migration\LegacyContentMap} + the legacy-CPT branch of
 * {@see \Ink\Migration\LibraryTrainingMigrator::run()}. No WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Migration\LegacyContentMap;
use Ink\Migration\LibraryTrainingMigrator;

test( 'library collections and the Gedigte shelf become biblioteek_item with a genre term', function (): void {
	$d = LegacyContentMap::decide( 'bibliotekon', array( 'flipvis-se-verhale', 'n-verhaal' ) );

	expect( $d['action'] )->toBe( LegacyContentMap::MIGRATE );
	expect( $d['cpt'] )->toBe( PostTypes::BIBLIOTEEK_ITEM );
	expect( $d['taxonomy'] )->toBe( Taxonomies::GENRE );
	expect( $d['term'] )->toBe( 'Flipvis se verhale' );

	expect( LegacyContentMap::decide( 'bibliotekon', array( 'gedigte', 'totius' ) )['term'] )->toBe( 'Gedigte' );
} );

test( 'both Kinderrympies roots share ONE collection term', function (): void {
	$a = LegacyContentMap::decide( 'bibliotekon', array( 'kinderrympies', 'x' ) );
	$b = LegacyContentMap::decide( 'bibliotekon', array( 'kinderrympies-vingerversies', 'y' ) );

	expect( $a['term'] )->toBe( 'Kinderrympies' );
	expect( $b['term'] )->toBe( 'Kinderrympies' );
} );

test( 'winners pages and the gala photo item are skipped, never migrated', function (): void {
	expect( LegacyContentMap::decide( 'bibliotekon', array( 'projek-wenners', 'wen-gedigte', 'g' ) )['action'] )->toBe( LegacyContentMap::SKIP );
	expect( LegacyContentMap::decide( 'bibliotekon', array( 'fotos-11-november-2023-8ste-gala-aand' ) )['action'] )->toBe( LegacyContentMap::SKIP );
} );

test( 'training stays opleiding_artikel under a vaardigheid term; Stof en tegniek is Digkuns', function (): void {
	$d = LegacyContentMap::decide( 'opleiding', array( 'stof-en-tegniek-gert-strydom', '6-die-waardering-van-gedigte' ) );

	expect( $d['cpt'] )->toBe( PostTypes::OPLEIDING_ARTIKEL );
	expect( $d['taxonomy'] )->toBe( Taxonomies::VAARDIGHEID );
	expect( $d['term'] )->toBe( 'Digkuns' );
	expect( LegacyContentMap::decide( 'opleiding', array( 'pastiche' ) )['term'] )->toBe( 'Digkuns' );
	expect( LegacyContentMap::decide( 'opleiding', array( 'n-paar-wenke-vir-my-medeskrywers-dirk-loriet-netwerk-24' ) )['term'] )->toBe( 'Algemene wenke' );
} );

test( 'dictionaries move from Opleiding to the Biblioteek Woordeboeke shelf, wherever they sit', function (): void {
	foreach ( array( array( 'woordeboek-wat' ), array( 'gedigte', 'dignet-woordeboek' ), array( 'gedigte', 'letterkundige-terme-woordeboek' ) ) as $chain ) {
		$d = LegacyContentMap::decide( 'opleiding', $chain );

		expect( $d['cpt'] )->toBe( PostTypes::BIBLIOTEEK_ITEM );
		expect( $d['taxonomy'] )->toBe( Taxonomies::GENRE );
		expect( $d['term'] )->toBe( 'Woordeboeke' );
	}
} );

test( 'the stergradering announcement is skipped; a container row is flagged and its children keep their destination', function (): void {
	expect( LegacyContentMap::decide( 'opleiding', array( 'inligtingstuk-stergradering-projekte-en-slypskool' ) )['action'] )->toBe( LegacyContentMap::SKIP );
	expect( LegacyContentMap::decide( 'opleiding', array( 'gedigte' ) )['action'] )->toBe( LegacyContentMap::CONTAINER );

	$child = LegacyContentMap::decide( 'opleiding', array( 'gedigte', 'digvorme' ) );
	expect( $child['action'] )->toBe( LegacyContentMap::MIGRATE );
	expect( $child['term'] )->toBe( 'Digkuns' );
} );

test( 'an unknown root is skipped and reported, never guessed', function (): void {
	expect( LegacyContentMap::decide( 'opleiding', array( 'iets-nuuts', 'x' ) )['action'] )->toBe( LegacyContentMap::SKIP );
	expect( LegacyContentMap::unmappedRoots( 'opleiding', array( 'pastiche', 'iets-nuuts', 'iets-nuuts' ) ) )->toBe( array( 'iets-nuuts' ) );
} );

test( 'legacyPath rebuilds the old URL from the slug chain', function (): void {
	expect( LegacyContentMap::legacyPath( 'bibliotekon', array( 'gedigte', 'totius' ) ) )->toBe( '/biblioteek/gedigte/totius/' );
	expect( LegacyContentMap::legacyPath( 'opleiding', array( 'pastiche' ) ) )->toBe( '/opleiding/pastiche/' );
} );

test( 'run migrates legacy-CPT rows via the mapping, recording the rebuilt URL and detaching the parent', function (): void {
	$migration = new class() extends LibraryTrainingMigrator {
		/** @var list<array{0:int,1:string}> */
		public array $set = array();
		/** @var array<int,string> */
		public array $urls = array();
		/** @var list<array{0:int,1:string,2:string}> */
		public array $named = array();
		/** @var list<int> */
		public array $detached = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyContent(): array {
			return array();
		}
		protected function legacyCptContent(): array {
			return array(
				(object) array( 'id' => 10, 'parent' => 0, 'legacy_type' => 'bibliotekon', 'slugs' => array( 'flipvis-se-verhale' ) ),
				(object) array( 'id' => 11, 'parent' => 10, 'legacy_type' => 'bibliotekon', 'slugs' => array( 'flipvis-se-verhale', 'deel-1' ) ),
				(object) array( 'id' => 12, 'parent' => 0, 'legacy_type' => 'bibliotekon', 'slugs' => array( 'projek-wenners', 'prosa', 'x' ) ),
				(object) array( 'id' => 13, 'parent' => 0, 'legacy_type' => 'opleiding', 'slugs' => array( 'gedigte' ) ),
				(object) array( 'id' => 14, 'parent' => 13, 'legacy_type' => 'opleiding', 'slugs' => array( 'gedigte', 'dignet-woordeboek' ) ),
				(object) array( 'id' => 15, 'parent' => 0, 'legacy_type' => 'opleiding', 'slugs' => array( 'onbekend' ) ),
				(object) array( 'id' => 16, 'parent' => 13, 'legacy_type' => 'opleiding', 'slugs' => array( 'gedigte', 'digvorme' ) ),
				(object) array( 'id' => 17, 'parent' => 0, 'legacy_type' => 'opleiding', 'slugs' => array( 'digkuns' ) ),
			);
		}
		protected function recordSourceUrlFor( int $post_id, string $url ): void {
			$this->urls[ $post_id ] = $url;
		}
		protected function setPostType( int $post_id, string $type ): void {
			$this->set[] = array( $post_id, $type );
		}
		protected function clearParent( int $post_id ): void {
			$this->detached[] = $post_id;
		}
		protected function assignNamedTerm( int $post_id, string $taxonomy, string $name ): int {
			$this->named[] = array( $post_id, $taxonomy, $name );
			return 1;
		}
		protected function flushRewrites(): void {}
		protected function markDone(): void {}
	};

	$summary = $migration->run();

	expect( $summary['biblioteek'] )->toBe( 3 );      // 10, 11 + dictionary 14
	expect( $summary['opleiding'] )->toBe( 2 );       // 16 + its kept container 13
	expect( $summary['legacy_skipped'] )->toBe( 2 ); // winners 12 + unknown root 15
	expect( $summary['containers'] )->toBe( 1 );     // empty placeholder 17 is dropped
	expect( $summary['unmapped_roots'] )->toBe( array( 'onbekend' ) );
	expect( $migration->urls[11] )->toBe( '/biblioteek/flipvis-se-verhale/deel-1/' );
	expect( $migration->urls[14] )->toBe( '/opleiding/gedigte/dignet-woordeboek/' );
	// Nested URLs are kept (11 under 10, 16 under 13); the dictionary moved to the library is detached.
	expect( $migration->detached )->toBe( array( 14 ) );
	expect( $migration->named )->toContain( array( 14, Taxonomies::GENRE, 'Woordeboeke' ) );
	expect( $migration->named )->toContain( array( 13, Taxonomies::VAARDIGHEID, 'Digkuns' ) );
} );
