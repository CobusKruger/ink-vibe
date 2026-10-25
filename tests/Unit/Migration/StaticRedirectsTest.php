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
