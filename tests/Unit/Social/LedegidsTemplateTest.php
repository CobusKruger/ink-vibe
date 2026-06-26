<?php
/**
 * Structural tests for the ledegids (member directory) template + pattern
 * (Story 9.7, FR-43). Mirrors OntdekTemplateTest.
 *
 * The ledegids reuses the Story 8.3 `ink/ontdek-skrywers` writer-discovery block
 * (no parallel directory) and uses the glossary `ledegids` term.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $path ): string {
	expect( is_readable( $path ) )->toBeTrue();

	return (string) file_get_contents( $path );
};

test( 'page-ledegids.html hosts the ledegids pattern within the chrome', function () use ( $ink_theme, $ink_read ): void {
	$markup = $ink_read( $ink_theme() . '/templates/page-ledegids.html' );

	expect( $markup )->toContain( 'wp:template-part' );        // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/ledegids' ); // the directory pattern
} );

test( 'the ledegids pattern reuses the 8.3 writer discovery + the glossary term', function () use ( $ink_theme, $ink_read ): void {
	$markup = $ink_read( $ink_theme() . '/patterns/ledegids.php' );

	// Reuse, not rebuild: the 8.3 writer-discovery block is the listing.
	expect( $markup )->toContain( 'wp:ink/ontdek-skrywers' );
	// The glossary term via the bridge — never a raw "directory"/"members list" literal.
	expect( $markup )->toContain( "ink_foundation_term( 'ledegids'" );
} );
