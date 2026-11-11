<?php
/**
 * Unit tests for the results route `/kompetisies/<jaar>/uitslae/` (K5, `05-kompetisies` §14.4).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Ink\Competitions\Data;
use Ink\Competitions\Edition;
use Ink\Competitions\Gala;
use Ink\Competitions\GalaPage;
use Ink\Competitions\GalaYear;
use Ink\Competitions\Module;
use Ink\Competitions\UitslaePage;
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
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An edition of a year.
 *
 * @param int    $year      The year.
 * @param bool   $published Whether results are published.
 * @param string $post      Post id seed.
 * @return Edition
 */
function ink_uitslae_edition( int $year, bool $published, int $post = 1 ): Edition {
	return new Edition( $post, 'Ink Kompetisie ' . $year, array(
		'jaar'                => (string) $year,
		'uitslae_gepubliseer' => $published ? 'ja' : '',
	) );
}

test( 'the route is registered for the template guard, and the template and pattern exist', function (): void {
	expect( SystemPages::routeOf( UitslaePage::TEMPLATE, array() ) )->toBe( 'endpoint' );
	expect( UitslaePage::QUERY_VAR )->toBe( 'ink_uitslae_jaar' );
	expect( UitslaePage::QUERY_VAR )->not->toBe( GalaPage::QUERY_VAR );
	expect( is_file( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/templates/' . UitslaePage::TEMPLATE . '.html' ) )->toBeTrue();
	expect( is_file( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/patterns/' . UitslaePage::TEMPLATE . '.php' ) )->toBeTrue();
	expect( UitslaePage::path( 2025 ) )->toBe( '/kompetisies/2025/uitslae/' );
} );

test( 'only a year whose edition published its results has a page', function (): void {
	$editions = array(
		ink_uitslae_edition( 2026, false, 1 ),
		ink_uitslae_edition( 2025, true, 2 ),
		ink_uitslae_edition( 2024, false, 3 ),
		new Edition( 4, 'Standaard', array( 'standaard' => 'ja', 'jaar' => '2023', 'uitslae_gepubliseer' => 'ja' ) ),
	);

	expect( Data::pickResultsEdition( $editions, 2025 ) )->toBe( $editions[1] );
	expect( Data::pickResultsEdition( $editions, 2026 ) )->toBeNull();
	expect( Data::pickResultsEdition( $editions, 2024 ) )->toBeNull();
	expect( Data::pickResultsEdition( $editions, 2023 ) )->toBeNull();
	expect( Data::pickResultsEdition( $editions, 1999 ) )->toBeNull();
} );

test( 'two editions in one year: the newest with published results wins (route undefined)', function (): void {
	$editions = array( ink_uitslae_edition( 2025, false, 9 ), ink_uitslae_edition( 2025, true, 8 ), ink_uitslae_edition( 2025, true, 7 ) );

	expect( Data::pickResultsEdition( $editions, 2025 ) )->toBe( $editions[1] );
} );

test( 'a winner name: the account first, the typed name when the account is gone, blank skips', function (): void {
	expect( UitslaePage::displayName( 'Anna Botha', 'A. B.' ) )->toBe( 'Anna Botha' );
	expect( UitslaePage::displayName( '', ' Facebook-groep ' ) )->toBe( 'Facebook-groep' );
	expect( UitslaePage::displayName( '  ', '' ) )->toBe( '' );
} );

test( 'the page: heading, intro, winners grouped by award, linked name, photo, back and gala links', function (): void {
	$groups = array(
		array(
			'award'   => 'Gedigte',
			'winners' => array(
				array( 'name' => 'Anna', 'url' => '/skrywer/anna/', 'entry' => 'Reën', 'note' => 'Pragtig', 'photo' => array( 'url' => '/a.jpg', 'width' => 768, 'height' => 512 ) ),
				array( 'name' => 'Piet', 'url' => '', 'entry' => '', 'note' => '', 'photo' => null ),
			),
		),
		array(
			'award'   => 'Boekprys',
			'winners' => array( array( 'name' => 'Groep', 'url' => '', 'entry' => '', 'note' => '', 'photo' => null ) ),
		),
	);
	$html   = UitslaePage::toHtml( 2025, 'Ink Kompetisie 2025', array( 'Een.', 'Twee.' ), $groups, '/kompetisies/gala/2025/', '/kompetisies/' );

	expect( $html )->toContain( 'ink-heading-h1-index' )->toContain( 'Ink Kompetisie 2025' );
	expect( substr_count( $html, 'ink-komp-uitslae__teks' ) )->toBe( 2 );
	expect( substr_count( $html, '<h2 class="ink-heading-h2-section">' ) )->toBe( 2 );
	expect( strpos( $html, 'Gedigte' ) )->toBeLessThan( strpos( $html, 'Boekprys' ) );
	expect( $html )->toContain( '<a class="ink-link--ink" href="/skrywer/anna/">Anna</a>' );
	expect( $html )->toContain( '<h3 class="ink-heading-h3">Piet</h3>' );
	expect( substr_count( $html, 'class="ink-photo"' ) )->toBe( 1 );
	expect( $html )->toContain( 'width="768" height="512"' );
	expect( $html )->toContain( 'Reën' )->toContain( 'Pragtig' );
	expect( $html )->toContain( 'ink-nav-link--footer" href="/kompetisies/"' );
	expect( $html )->toContain( 'href="/kompetisies/gala/2025/"' );
	expect( $html )->not->toContain( 'data-audit-id' );
} );

test( 'no gala photos means no gala link; a blank title falls back to the year heading', function (): void {
	$html = UitslaePage::toHtml( 2025, '', array(), array(), '', '/kompetisies/' );

	expect( $html )->not->toContain( 'ink-link--ink' );
	expect( $html )->toContain( 'Die wenners van die 2025 Ink Kompetisie' );
} );

test( 'the gala photos page links to the results only when published', function (): void {
	$with    = GalaPage::toHtml( 2025, array(), '', '/kompetisies/', '/kompetisies/2025/uitslae/' );
	$without = GalaPage::toHtml( 2025, array(), '', '/kompetisies/' );

	expect( $with )->toContain( 'href="/kompetisies/2025/uitslae/"' );
	expect( $without )->not->toContain( 'uitslae' );
} );

test( 'the year chips on /kompetisies/: a results link in the panel of a year with published results only', function (): void {
	$galas = array(
		new GalaYear( 2025, 'H', array( 1 ), array(), ink_uitslae_edition( 2025, true ) ),
		new GalaYear( 2024, 'H', array( 1 ), array(), ink_uitslae_edition( 2024, false ) ),
		new GalaYear( 2023, 'H', array( 1 ) ),
	);
	$html  = Gala::toHtml( null, $galas, array(), 'https://x.test/' );

	expect( $galas[0]->hasResults() )->toBeTrue();
	expect( $galas[1]->hasResults() )->toBeFalse();
	expect( $galas[2]->hasResults() )->toBeFalse();
	expect( substr_count( $html, '/kompetisies/2025/uitslae/' ) )->toBe( 1 );
	expect( $html )->not->toContain( '/kompetisies/2024/uitslae/' );
	expect( $html )->not->toContain( '/kompetisies/2023/uitslae/' );
} );

test( 'saving a kompetisie purges its results, gala photos and announcement URLs', function (): void {
	expect( Module::yearPaths( 2025 ) )->toBe( array( '/kompetisies/2025/uitslae/', '/kompetisies/gala/2025/', '/kompetisies/2025/' ) );
	expect( Module::yearPaths( 0 ) )->toBe( array() );
} );

test( 'a migrated old results post is shown after the intro, filtered, and only when there is one', function (): void {
	Functions\when( 'wp_kses_post' )->alias( static fn ( string $html ): string => str_replace( '<script>', '', $html ) );

	$html = UitslaePage::toHtml( 2022, 'Uitslae', array( 'Intro.' ), array(), '', '/kompetisies/', '<h2>Wenners</h2><script><p>Anna</p>' );

	expect( $html )->toContain( '<div class="ink-komp-uitslae__oud"><h2>Wenners</h2><p>Anna</p></div>' );
	expect( strpos( $html, 'Intro.' ) )->toBeLessThan( strpos( $html, 'ink-komp-uitslae__oud' ) );
	expect( UitslaePage::toHtml( 2022, 'Uitslae', array(), array(), '', '/kompetisies/' ) )->not->toContain( 'ink-komp-uitslae__oud' );
	expect( ( new Edition( 1, 'x', array( 'uitslae_oud' => " <p>Oud</p>\n" ) ) )->resultsBody() )->toBe( '<p>Oud</p>' );
	expect( ( new Edition( 1, 'x', array() ) )->resultsBody() )->toBe( '' );
} );
