<?php
/**
 * Reading-template structural guardrails (Story 7.1, FR-24).
 *
 * Story 7.1 adds a single reading template per prose CPT (storie/artikel) plus
 * the shared reading patterns they reference. These are FSE `.html` templates +
 * `.php` patterns, so the guard reads them off disk and asserts on their block
 * markup — no WordPress runtime needed.
 *
 * The "no WP comments" assertion (AC #4) is made NON-VACUOUS by first asserting
 * the positive markers (post-title + post-content are present and the reading
 * column is constrained to the 768px measure): a blank or missing template fails
 * the positive checks loudly rather than passing the comment-absence check on
 * emptiness.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

// The comment-block markers that must NEVER appear on a reading surface (AC #4).
$ink_comment_markers = array(
	'wp:comments',
	'wp:post-comments-form',
	'wp:comment-template',
	'wp:comments-title',
	'wp:post-comments',
);

test( 'a single reading template exists per prose CPT and resolves by CPT slug', function () use ( $ink_read ): void {
	// FSE auto-resolves single-{post_type}.html by the template hierarchy — the
	// filename IS the binding, no PHP registration needed.
	foreach ( array( 'single-storie.html', 'single-artikel.html' ) as $template ) {
		$markup = $ink_read( 'templates/' . $template );
		expect( $markup )->toContain( 'wp:template-part' );          // header/footer chrome
		expect( $markup )->toContain( 'ink-foundation/reading-' );    // references the reading pattern
	}
} );

test( 'each reading pattern renders the post via core blocks at the 768px reading measure', function () use ( $ink_read ): void {
	foreach ( array( 'reading-storie.php', 'reading-artikel.php' ) as $pattern ) {
		$markup = $ink_read( 'patterns/' . $pattern );

		// Positive markers (these make the no-comments check non-vacuous).
		expect( $markup )->toContain( 'wp:post-title' );
		expect( $markup )->toContain( 'wp:post-content' );
		expect( $markup )->toContain( '"contentSize":"768px"' );
	}
} );

test( 'reading patterns carry NO WP comments UI', function () use ( $ink_read, $ink_comment_markers ): void {
	foreach ( array( 'reading-storie.php', 'reading-artikel.php' ) as $pattern ) {
		$markup = $ink_read( 'patterns/' . $pattern );

		// Non-vacuous: the file was proven to contain real reading markup above,
		// so this absence assertion runs against a populated template.
		expect( $markup )->toContain( 'wp:post-content' );

		foreach ( $ink_comment_markers as $marker ) {
			expect( $markup )->not->toContain( $marker );
		}
	}
} );

test( 'reading templates carry NO WP comments UI', function () use ( $ink_read, $ink_comment_markers ): void {
	foreach ( array( 'single-storie.html', 'single-artikel.html' ) as $template ) {
		$markup = $ink_read( 'templates/' . $template );

		expect( $markup )->toContain( 'wp:template-part' ); // non-vacuous: real template body

		foreach ( $ink_comment_markers as $marker ) {
			expect( $markup )->not->toContain( $marker );
		}
	}
} );

test( 'reading-storie and reading-artikel eyebrows source the type label from the terminology bridge', function () use ( $ink_read ): void {
	// Controlled-vocabulary labels come from the ink-core terminology registry via
	// the theme bridge — never inlined as bare literals (project-context Gate D).
	$storie = $ink_read( 'patterns/reading-storie.php' );
	expect( $storie )->toContain( "ink_foundation_term( 'storie'" );

	$artikel = $ink_read( 'patterns/reading-artikel.php' );
	expect( $artikel )->toContain( "ink_foundation_term( 'artikel'" );
} );
