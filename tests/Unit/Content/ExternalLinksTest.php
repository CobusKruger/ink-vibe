<?php
/**
 * Unit tests for opening off-site links in a new tab.
 *
 * Target: {@see \Ink\Content\ExternalLinks}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\ExternalLinks;

test( 'an off-site link opens in a new tab with a safe rel', function (): void {
	$out = ExternalLinks::rewrite( '<p><a href="https://example.com/x">x</a></p>', 'ink.org.za' );

	expect( $out )->toBe( '<p><a href="https://example.com/x" target="_blank" rel="noopener noreferrer">x</a></p>' );
} );

test( 'on-site, relative, anchor, mailto and tel links are left alone', function (): void {
	foreach ( array( 'https://ink.org.za/a/', 'https://www.ink.org.za/a/', '/skryf/', '#nota', 'mailto:a@b.co', 'tel:+27123', '//ink.org.za/x' ) as $href ) {
		$tag = '<a href="' . $href . '">x</a>';
		expect( ExternalLinks::rewrite( $tag, 'ink.org.za' ) )->toBe( $tag );
	}
} );

test( 'a protocol-relative off-site link and single quotes are handled', function (): void {
	expect( ExternalLinks::rewrite( "<a href='//example.com'>x</a>", 'ink.org.za' ) )
		->toBe( "<a href='//example.com' target=\"_blank\" rel=\"noopener noreferrer\">x</a>" );
} );

test( 'an existing target or rel is kept, and only the missing one is added', function (): void {
	expect( ExternalLinks::rewrite( '<a target="_self" href="https://example.com">x</a>', 'ink.org.za' ) )
		->toBe( '<a target="_self" href="https://example.com" rel="noopener noreferrer">x</a>' );
	expect( ExternalLinks::rewrite( '<a href="https://example.com" rel="nofollow">x</a>', 'ink.org.za' ) )
		->toBe( '<a href="https://example.com" rel="nofollow" target="_blank">x</a>' );
} );

test( 'content without anchors is returned untouched, and an anchor without href is skipped', function (): void {
	expect( ExternalLinks::rewrite( '<p>geen skakel</p>', 'ink.org.za' ) )->toBe( '<p>geen skakel</p>' );
	expect( ExternalLinks::rewrite( '<a name="x">x</a>', 'ink.org.za' ) )->toBe( '<a name="x">x</a>' );
} );

test( 'an entity-encoded href is decoded before the host check', function (): void {
	expect( ExternalLinks::rewrite( '<a href="https://example.com/?a=1&amp;b=2">x</a>', 'ink.org.za' ) )
		->toContain( 'target="_blank"' );
} );
