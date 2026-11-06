<?php
/**
 * The site header (build spec 06-chrome): link list, audit ids, mobile panel markup.
 *
 * The link helpers run for real. The pattern, block style and script are read off
 * disk: they need a WordPress runtime and a browser to run.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Theme;

use Brain\Monkey;
use Brain\Monkey\Functions;

require_once dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/inc/header-nav.php';

$ink_theme_file = static fn ( string $relative ): string => (string) file_get_contents( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/' . $relative );

beforeEach(
	function (): void {
		Monkey\setUp();
		Functions\stubEscapeFunctions();
		Functions\when( 'esc_url' )->returnArg(); // Brain Monkey's stub prefixes http://; core keeps a root-relative URL.
	}
);

afterEach(
	function (): void {
		Monkey\tearDown();
	}
);

test( 'the header has Kompetisies between Uitdagings and Gemeenskap, and no InkPols', function (): void {
	expect( array_column( ink_foundation_header_links( false ), 'label' ) )->toBe(
		array( 'Tuis', 'Ontdek', 'Biblioteek', 'Opleiding', 'Uitdagings', 'Kompetisies', 'Gemeenskap' )
	);
} );

test( 'in the panel list, My profiel is last and only for a logged-in visitor', function (): void {
	$labels = array_column( ink_foundation_header_links( true ), 'label' );

	expect( end( $labels ) )->toBe( 'My profiel' );
	expect( $labels )->toHaveCount( 8 );
} );

test( 'each authored trailing-slash URL gets its map audit id', function ( string $url, string $id ): void {
	expect( ink_foundation_header_nav_audit_id( $url ) )->toBe( $id );
} )->with(
	array(
		array( '/', 'header-nav-home' ),
		array( '/ontdek/', 'header-nav-browse' ),
		array( '/biblioteek/', 'header-nav-library' ),
		array( '/opleiding/', 'header-nav-training' ),
		array( '/uitdaging/', 'header-nav-challenges' ),
		array( '/kompetisies/', 'header-nav-competitions' ),
		array( '/gemeenskap/', 'header-nav-community' ),
	)
);

test( 'a URL that is not a header link gets no audit id', function (): void {
	expect( ink_foundation_header_nav_audit_id( '/ontdek' ) )->toBeNull();
	expect( ink_foundation_header_nav_audit_id( '/inkpols/' ) )->toBeNull();
	expect( ink_foundation_header_nav_audit_id( '/my-profiel/' ) )->toBeNull();
} );

test( 'the desktop row has one link per entry and no My profiel, which the avatar menu has', function () use ( $ink_theme_file ): void {
	$blocks = ink_foundation_header_nav_blocks();

	expect( substr_count( $blocks, '<!-- wp:navigation-link ' ) )->toBe( 7 );
	expect( $blocks )->toContain( '{"label":"Kompetisies","url":"/kompetisies/"}' );
	expect( $blocks )->not->toContain( 'my-profiel' );
	expect( $ink_theme_file( 'patterns/header-main.php' ) )->toContain( 'role="menuitem" href="/my-profiel"' );
} );

test( 'the header collapses below 1024, not 768, in the style, the primitives and the script', function () use ( $ink_theme_file ): void {
	$functions = $ink_theme_file( 'functions.php' );
	$row       = '.wp-block-group.is-style-ink-header .ink-header-row > ';

	expect( $functions )->toMatch( '/@media \(max-width:1023\.98px\)\{\'\s*\. \'' . preg_quote( $row . '.wp-block-navigation', '/' ) . '/' );
	expect( $functions )->toMatch( '/@media \(min-width:1024px\)\{\'\s*\. \'' . preg_quote( $row . '.ink-icon-btn--menu{display:none;}', '/' ) . '/' );
	expect( $functions )->not->toContain( '767.98px' );
	expect( $functions )->toContain( 'ACCEPTED DIVERGENCE' );

	expect( $ink_theme_file( 'assets/css/primitives.css' ) )->toMatch( '/@media \(min-width: 1024px\) \{(?:(?!@media).)*\.ink-mobile-menu \{\s*display: none;/s' );

	$script = $ink_theme_file( 'assets/js/header-menu.js' );
	expect( $script )->toContain( "matchMedia( '(min-width: 1024px)' )" );
	expect( $script )->not->toContain( '768px' );
} );

test( 'logged out, the panel ships hidden with the map ids and no profile link', function (): void {
	$html = ink_foundation_header_mobile_menu( false, 'Teken in', '/teken-in', 'Sluit aan', '/registreer' );

	expect( $html )->toContain( '<div id="ink-mobile-menu" data-audit-id="header-mobile-panel" class="ink-mobile-menu alignwide" hidden ' );
	foreach ( array( 'home', 'browse', 'library', 'training', 'challenges', 'competitions', 'community' ) as $id ) {
		expect( $html )->toContain( 'data-audit-id="header-mobile-nav-' . $id . '"' );
	}
	expect( $html )->not->toContain( 'header-mobile-nav-profile' );
	expect( $html )->toContain( '<a data-audit-id="header-mobile-btn-signin" class="ink-btn ink-btn--ghost" href="/teken-in">Teken in</a>' );
	expect( $html )->toContain( '<a data-audit-id="header-mobile-btn-join" class="ink-btn ink-btn--primary" href="/registreer">Sluit aan</a>' );
} );

test( 'logged in, the panel has the profile link and unstamped Teken uit and Begin skryf', function (): void {
	$html = ink_foundation_header_mobile_menu( true, 'Teken uit', '/uit?x=1', 'Begin skryf', '/skryf' );

	expect( $html )->toContain( 'data-audit-id="header-mobile-nav-profile"' );
	expect( $html )->toContain( '<a class="ink-btn ink-btn--ghost" href="/uit?x=1">Teken uit</a>' );
	expect( $html )->toContain( '<a class="ink-btn ink-btn--primary" href="/skryf">Begin skryf</a>' );
	expect( $html )->not->toContain( 'header-mobile-btn-' );
} );

test( 'the action row sits inside the nav, as in the design', function (): void {
	$html = ink_foundation_header_mobile_menu( false, 'a', '/a', 'b', '/b' );

	expect( strpos( $html, 'header-mobile-actions' ) )->toBeLessThan( (int) strpos( $html, '</nav>' ) );
} );

test( 'the pattern uses our toggle and keeps the logged-in avatar menu', function () use ( $ink_theme_file ): void {
	$pattern = $ink_theme_file( 'patterns/header-main.php' );

	expect( $pattern )->toContain( '"overlayMenu":"never"' );
	expect( $pattern )->toContain( 'data-audit-id="header-menu-toggle" class="ink-icon-btn ink-icon-btn--menu" aria-expanded="false" aria-controls="ink-mobile-menu"' );
	expect( $pattern )->toContain( "esc_attr__( 'Menu', 'ink-foundation' )" );
	expect( $pattern )->toContain( 'data-ink-user-menu-trigger' );
	expect( $pattern )->not->toContain( 'overlayBackgroundColor' );
	// The logged-out actions need the class the block style hides below 1024.
	expect( $pattern )->toContain( 'data-audit-id="header-actions" class="ink-header-actions"' );
} );

test( 'the panel keeps the design order, with the same links as the desktop row', function (): void {
	expect( array_column( ink_foundation_header_mobile_links( true ), 'id' ) )->toBe(
		array( 'home', 'browse', 'challenges', 'competitions', 'library', 'training', 'community', 'profile' )
	);
	expect( ink_foundation_header_mobile_links( false ) )->toHaveCount( count( ink_foundation_header_links( false ) ) );
} );

test( 'core overlay styling is retired and the blur is on the header element', function () use ( $ink_theme_file ): void {
	$functions = $ink_theme_file( 'functions.php' );

	expect( $functions )->not->toContain( '.wp-block-navigation__responsive-container.is-menu-open{' );
	expect( $functions )->not->toContain( '@media (max-width:599px)' );
	expect( $functions )->not->toContain( 'is-style-ink-header::before' );
	expect( $functions )->toMatch( '/header\.wp-block-template-part\{.*backdrop-filter:blur\(4px\)/s' );
	expect( $ink_theme_file( 'assets/css/primitives.css' ) )->not->toContain( 'wp-block-navigation__responsive-container' );
} );

test( 'the toggle script loads for every visitor and handles Escape', function () use ( $ink_theme_file ): void {
	$functions = $ink_theme_file( 'functions.php' );
	$start     = strpos( $functions, 'function ink_foundation_enqueue_header_menu' );
	$body      = substr( $functions, (int) $start, (int) strpos( $functions, "add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_header_menu' )" ) - (int) $start );

	expect( $body )->toContain( 'assets/js/header-menu.js' );
	expect( $body )->not->toContain( 'is_user_logged_in' );

	$script = $ink_theme_file( 'assets/js/header-menu.js' );
	expect( $script )->toContain( "'aria-expanded'" );
	expect( $script )->toContain( "'Escape'" );
} );

test( 'the Opleiding shelf heading uses the section role; Biblioteek stays as it is', function (): void {
	$src = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/';

	expect( (string) file_get_contents( $src . 'Training/Hub.php' ) )->toContain( 'ink-opleiding__rak-titel ink-heading-h2-section' );
	expect( (string) file_get_contents( $src . 'Library/Archive.php' ) )->toContain( 'ink-biblioteek__uitgelig-titel ink-heading-h2-sub' );
} );
