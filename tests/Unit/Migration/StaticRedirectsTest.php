<?php
/**
 * Unit tests for the committed static redirect decisions.
 *
 * Target: {@see \Ink\Migration\StaticRedirects}. Pure; no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\StaticRedirects;

test( 'old lede-bydraes category URLs go to the Ontdek Bydraes tab', function (): void {
	expect( StaticRedirects::prefixTarget( '/kategorie/lede-bydraes/gedigte/' ) )->toBe( '/ontdek/#bydraes' );
	expect( StaticRedirects::prefixTarget( 'https://ink.org.za/kategorie/lede-bydraes' ) )->toBe( '/ontdek/#bydraes' );
} );

test( 'old projek-wenners URLs go to the Ontdek winners tab', function (): void {
	expect( StaticRedirects::prefixTarget( '/biblioteek/projek-wenners/wen-gedigte/x/' ) )->toBe( '/ontdek/#wenners' );
} );

test( 'unrelated and look-alike paths are not redirected', function (): void {
	expect( StaticRedirects::prefixTarget( '/biblioteek/gedigte/totius/' ) )->toBeNull();
	expect( StaticRedirects::prefixTarget( '/kategorie/lede-bydraes-ekstra/' ) )->toBeNull();
} );

test( 'the plagiaat duplicate is a dropped duplicate, the original is not', function (): void {
	expect( StaticRedirects::isDroppedDuplicate( 35127 ) )->toBeTrue();
	expect( StaticRedirects::isDroppedDuplicate( 21602 ) )->toBeFalse();
} );

test( 'dropped Opleiding placeholders redirect to the Opleiding page, but their children do not match', function (): void {
	foreach ( array( '/opleiding/digkuns/', '/opleiding/gedigte', 'https://ink.org.za/opleiding/taalgidse/' ) as $path ) {
		expect( StaticRedirects::prefixTarget( $path ) )->toBe( '/opleiding/' );
	}
	expect( StaticRedirects::prefixTarget( '/opleiding/gedigte/digvorme/' ) )->toBeNull();
} );

// --- retired results and Fotos Pages (K7) ---

dataset( 'old pages', array( array( array(
	array( 'id' => 64688, 'path' => '/15-november-2025-10de-gala/fotos-15-november-2025', 'jaar' => 2025, 'soort' => 'fotos' ),
	array( 'id' => 64600, 'path' => '/15-november-2025-10de-gala', 'jaar' => 2025, 'soort' => 'uitslae' ),
	array( 'id' => 5, 'path' => '', 'jaar' => 2019, 'soort' => 'uitslae' ),
) ) ) );

test( 'a Fotos Page redirects to the gala photos page by id and by path', function ( array $pages ): void {
	expect( StaticRedirects::oldPageTarget( '/?p=64688', $pages ) )->toBe( '/kompetisies/gala/2025/' );
	expect( StaticRedirects::oldPageTarget( '/index.php?page_id=64688', $pages ) )->toBe( '/kompetisies/gala/2025/' );
	expect( StaticRedirects::oldPageTarget( '/15-november-2025-10de-gala/fotos-15-november-2025/', $pages ) )->toBe( '/kompetisies/gala/2025/' );
	expect( StaticRedirects::oldPageTarget( 'https://ink.org.za/15-november-2025-10de-gala/fotos-15-november-2025', $pages ) )->toBe( '/kompetisies/gala/2025/' );
} )->with( 'old pages' );

test( 'a results Page redirects to the results route by id and by path', function ( array $pages ): void {
	expect( StaticRedirects::oldPageTarget( '/?p=64600', $pages ) )->toBe( '/kompetisies/2025/uitslae/' );
	expect( StaticRedirects::oldPageTarget( '/15-november-2025-10de-gala/', $pages ) )->toBe( '/kompetisies/2025/uitslae/' );
} )->with( 'old pages' );

test( 'the match ignores case, a query string and an encoded path', function ( array $pages ): void {
	expect( StaticRedirects::oldPageTarget( '/15-November-2025-10de-GALA/?utm_source=x', $pages ) )->toBe( '/kompetisies/2025/uitslae/' );
	expect( StaticRedirects::oldPageTarget( '/15-november-2025-10de-gala/fotos-15-november-2025/?x=1&p=999', $pages ) )->toBe( '/kompetisies/gala/2025/' );
	expect( StaticRedirects::oldPageTarget( '/15%2Dnovember-2025-10de-gala', $pages ) )->toBe( '/kompetisies/2025/uitslae/' );
} )->with( 'old pages' );

test( 'an entry with an empty path matches by id only, and never the site root', function ( array $pages ): void {
	expect( StaticRedirects::oldPageTarget( '/?p=5', $pages ) )->toBe( '/kompetisies/2019/uitslae/' );
	expect( StaticRedirects::oldPageTarget( '/', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '', $pages ) )->toBeNull();
} )->with( 'old pages' );

test( 'no match is null: unknown id, other path, look-alike path, junk id, empty or malformed map', function ( array $pages ): void {
	expect( StaticRedirects::oldPageTarget( '/?p=7802', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/?p=64688abc', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/?p[]=64688', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/15-november-2025-10de-gala/fotos', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/ander-blad/', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/?p=64688', array() ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/?p=1', array( 'x', array( 'id' => 1, 'jaar' => 0, 'soort' => 'fotos' ), array( 'id' => 1, 'jaar' => 2020, 'soort' => 'ander' ) ) ) )->toBeNull();
} )->with( 'old pages' );

test( 'the gala index and kept announcements are not in the stored-map redirects', function ( array $pages ): void {
	expect( StaticRedirects::oldPageTarget( '/ink-se-gala-aande/', $pages ) )->toBeNull();
	expect( StaticRedirects::oldPageTarget( '/?p=49428', $pages ) )->toBeNull();
} )->with( 'old pages' );
