<?php
/**
 * Unit tests for every photo of a gala year: `/kompetisies/gala/<jaar>/` and the "Sien alles"
 * link (owner ruling 2026-11-02).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Ink\Competitions\Data;
use Ink\Competitions\Gala;
use Ink\Competitions\GalaPage;
use Ink\Competitions\GalaYear;
use Ink\Kernel\ArchiveRender;
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
	Functions\when( 'add_query_arg' )->alias( static fn ( string $key, int $value ): string => '?' . $key . '=' . $value );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * One photo as the page renders it.
 *
 * @param string $caption The caption.
 * @return array{url:string, width:int, height:int, caption:string}
 */
function ink_gala_photo( string $caption = '' ): array {
	return array(
		'url'     => '/f.jpg',
		'width'   => 768,
		'height'  => 512,
		'caption' => $caption,
	);
}

test( 'the route is registered for the template guard, and the template exists', function (): void {
	expect( SystemPages::routeOf( GalaPage::TEMPLATE, array() ) )->toBe( 'endpoint' );
	expect( GalaPage::QUERY_VAR )->toBe( 'ink_gala_jaar' );
	expect( is_file( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/templates/' . GalaPage::TEMPLATE . '.html' ) )->toBeTrue();
	expect( GalaPage::path( 2025 ) )->toBe( '/kompetisies/gala/2025/' );
} );

test( 'batches: pages of 24 in gallery order, the last one short, at least one page', function (): void {
	$ids = range( 1, 656 );

	expect( GalaPage::pageCount( 656 ) )->toBe( 28 );
	expect( GalaPage::pageCount( 24 ) )->toBe( 1 );
	expect( GalaPage::pageCount( 0 ) )->toBe( 1 );
	expect( GalaPage::pageIds( $ids, 1 ) )->toBe( range( 1, 24 ) );
	expect( GalaPage::pageIds( $ids, 2 )[0] )->toBe( 25 );
	expect( GalaPage::pageIds( $ids, 28 ) )->toBe( range( 649, 656 ) );
	expect( GalaPage::pageIds( $ids, 29 ) )->toBe( array() );
} );

test( 'an unknown year, or a year without photos, has no gala page', function (): void {
	$galas = array( new GalaYear( 2025, 'H', array( 1, 2 ) ), new GalaYear( 2024, 'H', array() ) );

	expect( Data::pickGalaYear( $galas, 2025 ) )->toBe( $galas[0] );
	expect( Data::pickGalaYear( $galas, 2024 ) )->toBeNull();
	expect( Data::pickGalaYear( $galas, 1999 ) )->toBeNull();
} );

test( 'the page: recipes only, lazy images with a size, year heading, real paged links', function (): void {
	$pager = ArchiveRender::pagination( 2, 3, 'ink-komp-gala-fotos', GalaPage::PAGED_VAR );
	$html  = GalaPage::toHtml( 2025, array( ink_gala_photo( 'Onder' ), ink_gala_photo() ), $pager, '/kompetisies/' );

	expect( $html )->toContain( 'ink-heading-h1-index' );
	expect( $html )->toContain( 'Ink se Presige Gala-aand 2025' );
	expect( $html )->toContain( 'ink-nav-link--footer" href="/kompetisies/"' );
	expect( substr_count( $html, 'class="ink-photo"' ) )->toBe( 2 );
	expect( substr_count( $html, 'loading="lazy"' ) )->toBe( 2 );
	expect( $html )->toContain( 'width="768" height="512"' );
	expect( substr_count( $html, '<figcaption' ) )->toBe( 1 );
	expect( $html )->toContain( 'data-ink-gala-fotos' );
	expect( $html )->toContain( 'class="ink-komp-gala-fotos__vorige ink-pager__link" href="?bladsy=1"' );
	expect( $html )->toContain( 'class="ink-komp-gala-fotos__volgende ink-pager__link" href="?bladsy=3"' );
} );

test( 'a clip from an old Fotos Page stays reachable, in the same framed box', function (): void {
	$html = GalaPage::toHtml( 2025, array( array( 'url' => '/v.mp4', 'width' => 0, 'height' => 0, 'caption' => '', 'video' => true ) ), '', '/kompetisies/' );

	expect( $html )->toContain( '<video class="ink-photo" src="/v.mp4" controls preload="none" playsinline></video>' );
} );

test( '/kompetisies/: five photos a year, then "Sien alles" only when there are more', function (): void {
	$six  = array_fill( 0, 6, array( 'url' => '/a.jpg', 'caption' => '' ) );
	$html = Gala::toHtml(
		null,
		array( new GalaYear( 2025, 'H', range( 1, 6 ) ), new GalaYear( 2024, 'H', range( 1, 5 ) ) ),
		array( 2025 => $six, 2024 => array_slice( $six, 0, 5 ) ),
		'https://x.test/'
	);

	expect( substr_count( $html, 'class="ink-photo"' ) )->toBe( 10 );
	expect( $html )->not->toContain( 'competitions-gala-past-photo-5' );
	expect( substr_count( $html, 'Sien alles' ) )->toBe( 1 );
	expect( $html )->toContain( 'class="ink-komp-gala__alles ink-link--ink" href="https://x.test/kompetisies/gala/2025/" data-audit-id="competitions-gala-past-all"' );
} );
