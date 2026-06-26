<?php
/**
 * Structural tests for the profile templates + patterns (Story 9.4, FR-40).
 *
 * Mirrors OntdekTemplateTest: reads the theme template/pattern files and the
 * block source, asserting the public Skrywerprofiel and private My Profiel are
 * wired and — the load-bearing FR-40 guarantee — that private surfaces (read
 * counts, wins-needed) live on My Profiel only, never on the public block.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';
$ink_core  = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core';

$ink_read = static function ( string $path ): string {
	expect( is_readable( $path ) )->toBeTrue();

	return (string) file_get_contents( $path );
};

test( 'author.html hosts the public Skrywerprofiel block within the chrome', function () use ( $ink_theme, $ink_read ): void {
	$markup = $ink_read( $ink_theme() . '/templates/author.html' );

	expect( $markup )->toContain( 'wp:template-part' );    // header/footer chrome
	expect( $markup )->toContain( 'wp:ink/skrywerprofiel' ); // the public profile block
} );

test( 'page-my-profiel.html hosts the my-profiel pattern within the chrome', function () use ( $ink_theme, $ink_read ): void {
	$markup = $ink_read( $ink_theme() . '/templates/page-my-profiel.html' );

	expect( $markup )->toContain( 'wp:template-part' );
	expect( $markup )->toContain( 'ink-foundation/my-profiel' );
} );

test( 'the my-profiel pattern embeds the private surfaces + reused blocks', function () use ( $ink_theme, $ink_read ): void {
	$markup = $ink_read( $ink_theme() . '/patterns/my-profiel.php' );

	expect( $markup )->toContain( 'ink_foundation_gradering_wins_needed' ); // wins-needed (private)
	expect( $markup )->toContain( 'data-ink-slot="leesgetalle"' );          // read-count slot (9.12)
	expect( $markup )->toContain( 'wp:ink/leesgetalle' );                    // the read-count surface (9.12, private)
	expect( $markup )->toContain( 'wp:ink/volg-voer' );                      // following-feed (9.3)
	expect( $markup )->toContain( 'wp:ink/leeslys' );                        // leeslys (7.7)
	expect( $markup )->toContain( 'ink-foundation/lidmaatskap-hernu' );      // renewal section (4.5)
} );

test( 'FR-40 separation: the public Skrywerprofiel block source carries NO private surfaces', function () use ( $ink_core, $ink_read ): void {
	$source = $ink_read( $ink_core() . '/src/Social/SkrywerProfiel.php' );

	// Non-vacuous: the block DOES render the public gradering badge + volgeling.
	expect( $source )->toContain( 'gradingView' );
	expect( $source )->toContain( 'volgelingLabel' );
	// ...but NEVER the private My-Profiel-only surfaces (the FR-40 split).
	expect( $source )->not->toContain( 'wins_needed' );
	expect( $source )->not->toContain( 'winsNeededSubtext' );
	expect( $source )->not->toContain( 'leesgetalle' );
} );
